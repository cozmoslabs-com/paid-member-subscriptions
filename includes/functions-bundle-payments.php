<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Bundle (Order Bumps) payment support that lives in core
 *
 * - the Order Bumps add-on writes bundle data (_pms_order_items and the related bump meta) on a payment at checkout
 * - the surfaces below read that persisted data, so they are kept in core and keep working after the add-on is deactivated: Payments list filtering, refund cascade, refund modal notice, the bundle subscription-log messages and the per-plan amount breakdown on the Payments list and payment-history amount cells
 *
 */


/**
 * Returns whether any Order Bumps bundle payments exist in the database
 *
 * - detected by the presence of `_pms_order_items` payment meta, written on bundle checkout
 * - lives in core (not the add-on) so admin surfaces that read persisted bundle data keep working after the add-on is deactivated; result is cached because it gates UI that renders on every Payments list table load
 *
 */
function pms_payments_bundle_data_exists() {

    $exists = get_transient( 'pms_payments_bundle_data_exists' );

    if( $exists === false ) {

        global $wpdb;

        $found = $wpdb->get_var( "SELECT 1 FROM {$wpdb->prefix}pms_paymentmeta WHERE meta_key = '_pms_order_items' LIMIT 1" );

        $exists = !empty( $found ) ? 'yes' : 'no';

        set_transient( 'pms_payments_bundle_data_exists', $exists, HOUR_IN_SECONDS );

    }

    return $exists === 'yes';

}


/**
 * Returns payment IDs whose Order Bumps bundle includes the given subscription plan as a bump
 *
 * - reads `_pms_order_bumps_subscription_plan_ids` payment meta (the list of bump plan IDs stored per bundle payment); the SQL prefilter uses a LIKE on the serialized integer fragment, then each candidate is unserialized and verified with in_array so a serialized-key coincidence cannot false-match
 * - lives in core so the Payments list table's plan filter keeps matching bumps after the add-on is deactivated (the meta persists)
 *
 */
function pms_get_payment_ids_with_plan_as_bump( $plan_id ) {

    global $wpdb;

    $plan_id = absint( $plan_id );

    if( empty( $plan_id ) )
        return array();

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT payment_id, meta_value FROM {$wpdb->prefix}pms_paymentmeta WHERE meta_key = %s AND meta_value LIKE %s",
        '_pms_order_bumps_subscription_plan_ids',
        '%i:' . $plan_id . ';%'
    ), ARRAY_A );

    if( empty( $rows ) )
        return array();

    $matched = array();

    foreach( $rows as $row ) {

        $bump_plan_ids = maybe_unserialize( $row['meta_value'] );

        if( !is_array( $bump_plan_ids ) )
            continue;

        if( in_array( $plan_id, array_map( 'absint', $bump_plan_ids ), true ) )
            $matched[] = (int) $row['payment_id'];

    }

    return $matched;

}


/**
 * Injects the bundle-specific conditions into the payments query WHERE clause
 *
 * - keeps add-on-specific SQL out of the core pms_get_payments() / pms_get_payments_count() functions: core builds a plain WHERE clause and exposes it on pms_get_payments_query_where, this callback rewrites the relevant fragments
 * - "bundle_payment" is a virtual type with no payment row column, so the literal type match core emits is replaced with an EXISTS on the bundle's _pms_order_items meta
 * - the opt-in plan-as-bump match widens the scalar plan condition to also include bundle payments where the plan is a bump, not only the primary
 * - runs before $wpdb->prepare, so it only rewrites pre-sanitized integer fragments and never touches the %d / %s placeholders
 *
 */
function pms_bundle_payments_filter_query_where( $query_where, $args ) {

    global $wpdb;

    if( !empty( $args['type'] ) && $args['type'] === 'bundle_payment' ) {
        $query_where = str_replace(
            "pms_payments.type LIKE 'bundle_payment'",
            "EXISTS (SELECT 1 FROM {$wpdb->prefix}pms_paymentmeta pms_bundle_meta WHERE pms_bundle_meta.payment_id = pms_payments.id AND pms_bundle_meta.meta_key = '_pms_order_items')",
            $query_where
        );
    }

    // only the scalar plan match is widened; the array (IN) form is left untouched, mirroring the original inline behavior
    if( !empty( $args['match_subscription_plan_as_bump'] ) && !empty( $args['subscription_plan_id'] ) && !is_array( $args['subscription_plan_id'] ) ) {

        $subscription_plan_id = (int) trim( $args['subscription_plan_id'] );
        $bump_payment_ids     = pms_get_payment_ids_with_plan_as_bump( $subscription_plan_id );

        if( !empty( $bump_payment_ids ) ) {

            $bump_payment_ids = implode( ',', array_map( 'absint', $bump_payment_ids ) );

            $query_where = str_replace(
                "pms_payments.subscription_plan_id = {$subscription_plan_id}",
                "( pms_payments.subscription_plan_id = {$subscription_plan_id} OR pms_payments.id IN ({$bump_payment_ids}) )",
                $query_where
            );

        }

    }

    return $query_where;

}
add_filter( 'pms_get_payments_query_where', 'pms_bundle_payments_filter_query_where', 10, 2 );


/**
 * Cancels or expires Order Bump subscriptions when their bundle payment is refunded
 *
 * - refund-side counterpart to PMS's existing primary-subscription refund handling; the three refund paths (admin refund button, Stripe charge.refunded webhook, PayPal PAYMENT.CAPTURE.REFUNDED webhook) all converge on pms_payment_update with status refunded, so a single handler covers every refund path
 * - lives in core so existing bundle payments still cascade correctly when the Order Bumps add-on is deactivated after checkout - the bump member subscriptions and the bundle payment meta remain in the database regardless of the add-on's state
 * - mirrors the primary subscription's chosen fate across every bump in the bundle: admin's subscription_status_after choice on the admin button, gateway-refund-behavior misc setting on webhooks; idempotent on replayed webhooks via the per-bump status check inside the loop
 * - trial-anchored bundles refunded while the primary is still in its trial window also force-expire the primary subscription (the customer has no recurring charge yet, so the "keep access until billing date" canceled-state semantics do not apply); admin choice still drives the bump cascade in this case
 *
 */
function pms_cascade_bundle_payment_refund( $payment_id, $data, $old_payment ) {

    if( !isset( $data['status'] ) || $data['status'] !== 'refunded' )
        return;

    // only initial bundle payments carry _pms_order_items meta; single-plan payments and later renewal payments short-circuit here
    $order_items = pms_get_payment_meta( $payment_id, '_pms_order_items', true );

    if( empty( $order_items ) || !is_array( $order_items ) )
        return;

    // skip stale lineage: when a failed bundle payment was replaced by a successful retry, the retry adopted this bundle's subscriptions. Refunding the old superseded row must not cascade into the live retry lineage and expire/cancel its active subscriptions
    $superseded_by_payment_id = pms_get_payment_meta( $payment_id, '_pms_order_bumps_superseded_by_payment_id', true );

    if( !empty( $superseded_by_payment_id ) )
        return;

    $cascade = pms_resolve_bundle_refund_cascade( $payment_id );

    if( empty( $cascade['bump_target_status'] ) && empty( $cascade['force_primary_expire'] ) )
        return;

    if( !empty( $cascade['bump_target_status'] ) ) {

        $bump_member_subscription_ids = pms_get_payment_meta( $payment_id, '_pms_order_bumps_member_subscription_ids', true );

        if( !empty( $bump_member_subscription_ids ) && is_array( $bump_member_subscription_ids ) ) {

            foreach( $bump_member_subscription_ids as $bump_member_subscription_id ) {

                $bump = pms_get_member_subscription( absint( $bump_member_subscription_id ) );

                if( empty( $bump->id ) )
                    continue;

                // idempotency: a replayed pms_payment_update on an already-cascaded bundle is a no-op for bumps already in the target status
                if( $bump->status === $cascade['bump_target_status'] )
                    continue;

                // pms_resolve_bundle_refund_cascade() narrows the value to either 'canceled' or 'expired' for cascading paths, so the else branch below covers the 'expired' case
                if( $cascade['bump_target_status'] === 'canceled' ) {
                    $subscription_data = array(
                            'status'               => 'canceled',
                            'expiration_date'      => !empty( $bump->billing_next_payment ) ? $bump->billing_next_payment : $bump->expiration_date,
                            'billing_next_payment' => '',
                    );
                } else {
                    $subscription_data = array(
                            'status'                => 'expired',
                            'expiration_date'       => date( 'Y-m-d H:i:s' ),
                            'billing_next_payment'  => '',
                            'billing_duration'      => '',
                            'billing_duration_unit' => '',
                    );
                }

                if( $bump->update( $subscription_data ) ) {
                    pms_add_member_subscription_log( $bump->id, 'order_bump_canceled_via_refund', array(
                            'bundle_payment_id' => $payment_id,
                            'new_status'        => $cascade['bump_target_status'],
                    ) );
                }

            }

        }

    }

    if( !empty( $cascade['force_primary_expire'] ) ) {

        $primary_member_subscription_id = (int) pms_get_payment_meta( $payment_id, 'subscription_id', true );

        if( !empty( $primary_member_subscription_id ) ) {

            $primary_subscription = pms_get_member_subscription( $primary_member_subscription_id );

            // idempotency: replayed pms_payment_update on an already-expired primary is a no-op
            if( !empty( $primary_subscription->id ) && $primary_subscription->status !== 'expired' ) {

                $primary_subscription->update( array(
                        'status'                => 'expired',
                        'expiration_date'       => date( 'Y-m-d H:i:s' ),
                        'trial_end'             => '',
                        'billing_next_payment'  => '',
                        'billing_duration'      => '',
                        'billing_duration_unit' => '',
                ) );

                pms_add_member_subscription_log( $primary_subscription->id, 'bundle_primary_trial_refunded', array(
                        'bundle_payment_id' => $payment_id,
                ) );

            }

        }

    }

}
add_action( 'pms_payment_update', 'pms_cascade_bundle_payment_refund', 10, 3 );


/**
 * Resolves the target subscription status for a bundle refund cascade
 *
 * - admin refunds carry $_POST['pms_refund_data'] and mirror the admin's explicit subscription_status_after choice (current_status / canceled / expired); an unrecognised admin choice falls back to expired so a tampered or otherwise unexpected value still ends access safely
 * - gateway webhooks do not carry pms_refund_data, so the cascade mirrors the same gateway-refund-behavior misc setting that controls PMS's primary expiration: when the setting is on, no cascade; when off (the default), expired
 * - trial-anchored bundles (identified by _pms_order_bumps_primary_trial_end payment meta with a future timestamp) additionally force the primary subscription to expire even when the admin chose current_status, because no recurring charge exists yet to "keep access until billing date"; the gateway-refund-behavior=1 setting still overrides this (it is the admin's blanket opt-out of automatic actions on webhook refunds)
 * - returns an array with three keys: bump_target_status (string: 'canceled' / 'expired' / '' for no-cascade), primary_in_trial (bool), force_primary_expire (bool)
 *
 * @param int $payment_id optional bundle payment id used to detect trial anchoring; pass 0 (or omit) when only the bump cascade decision is needed
 *
 */
function pms_resolve_bundle_refund_cascade( $payment_id = 0 ) {

    $refund_data        = !empty( $_POST['pms_refund_data'] ) && is_array( $_POST['pms_refund_data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['pms_refund_data'] ) ) : array();
    $is_admin_context   = !empty( $refund_data );
    $bump_target_status = '';
    $no_cascade_setting = false;

    if( $is_admin_context ) {

        $admin_choice = !empty( $refund_data['subscription_status_after'] ) ? $refund_data['subscription_status_after'] : '';

        if( !empty( $admin_choice ) && $admin_choice !== 'current_status' )
            $bump_target_status = in_array( $admin_choice, array( 'canceled', 'expired' ), true ) ? $admin_choice : 'expired';

    } else {

        $pms_settings = get_option( 'pms_misc_settings', array() );

        if( isset( $pms_settings['gateway-refund-behavior'] ) && (int) $pms_settings['gateway-refund-behavior'] === 1 )
            $no_cascade_setting = true;
        else
            $bump_target_status = 'expired';

    }

    $primary_trial_end = $payment_id ? pms_get_payment_meta( $payment_id, '_pms_order_bumps_primary_trial_end', true ) : '';
    $primary_in_trial  = !empty( $primary_trial_end ) && strtotime( $primary_trial_end ) > time();

    return array(
            'bump_target_status'   => $bump_target_status,
            'primary_in_trial'     => $primary_in_trial,
            'force_primary_expire' => $primary_in_trial && !$no_cascade_setting,
    );

}


/**
 * Adds the readable subscription log messages for bundle refund cascade events
 *
 * - covers both branches the cascade produces: order_bump_canceled_via_refund for bumps and bundle_primary_trial_refunded for the primary force-expire on trial-anchored bundles
 * - hooked into the same pms_subscription_logs_system_error_messages filter the Order Bumps add-on uses for order_bump_added and order_bump_activated
 * - lives in core because the cascade itself lives in core and can fire after the Order Bumps add-on is deactivated; the log renderer needs to be loaded regardless of the add-on's state to translate the slug into a readable string
 *
 */
function pms_subscription_logs_refund_cascade_message( $message, $log ) {

    if( empty( $log['type'] ) )
        return $message;

    if( !in_array( $log['type'], array( 'order_bump_canceled_via_refund', 'bundle_primary_trial_refunded' ), true ) )
        return $message;

    $bundle_payment_id   = !empty( $log['data']['bundle_payment_id'] ) ? absint( $log['data']['bundle_payment_id'] ) : 0;
    $bundle_payment_link = '';

    if( !empty( $bundle_payment_id ) ) {

        $bundle_payment_url = add_query_arg(
            array(
                'page'       => 'pms-payments-page',
                'pms-action' => 'edit_payment',
                'payment_id' => $bundle_payment_id,
            ),
            admin_url( 'admin.php' )
        );

        $bundle_payment_link = sprintf( '<a href="%1$s"><strong>#%2$s</strong></a>', esc_url( $bundle_payment_url ), $bundle_payment_id );

    }

    if( $log['type'] === 'order_bump_canceled_via_refund' ) {

        if( !empty( $bundle_payment_link ) )
            return sprintf( __( 'Order Bump canceled because the bundle payment %s was refunded.', 'paid-member-subscriptions' ), $bundle_payment_link );

        return __( 'Order Bump canceled because the bundle payment was refunded.', 'paid-member-subscriptions' );

    }

    if( $log['type'] === 'bundle_primary_trial_refunded' ) {

        if( !empty( $bundle_payment_link ) )
            return sprintf( __( 'Subscription expired because the bundle payment %s was refunded during its trial period.', 'paid-member-subscriptions' ), $bundle_payment_link );

        return __( 'Subscription expired because the bundle payment was refunded during its trial period.', 'paid-member-subscriptions' );

    }

    return $message;

}
add_filter( 'pms_subscription_logs_system_error_messages', 'pms_subscription_logs_refund_cascade_message', 10, 2 );


/**
 * Adds the readable subscription log messages for Order Bumps bundle creation and activation events
 *
 * - covers order_bump_added (each bump created at checkout), order_bumps_bundle_primary_created (primary subscription flagged as bundle anchor) and order_bump_activated (each bump flipped to active after the bundle payment completes)
 * - lives in core for the same reason as pms_subscription_logs_refund_cascade_message: log rows persist past add-on deactivation, so the renderer needs to be available whether the Order Bumps add-on is active or not — otherwise the admin sees the generic fallback message for every historical bundle entry the moment the add-on is turned off
 *
 */
function pms_subscription_logs_order_bump_messages( $message, $log ) {

    if( empty( $log['type'] ) )
        return $message;

    if( !in_array( $log['type'], array( 'order_bump_added', 'order_bump_activated', 'order_bumps_bundle_primary_created' ), true ) )
        return $message;

    $bundle_payment_id         = !empty( $log['data']['bundle_payment_id'] ) ? absint( $log['data']['bundle_payment_id'] ) : 0;
    $bundle_payment_link       = '';
    $primary_subscription_link = '';

    if( !empty( $bundle_payment_id ) ) {

        $bundle_payment_url = add_query_arg(
                array(
                        'page'       => 'pms-payments-page',
                        'pms-action' => 'edit_payment',
                        'payment_id' => $bundle_payment_id,
                ),
                admin_url( 'admin.php' )
        );

        $bundle_payment_link     = sprintf( '<a href="%1$s"><strong>#%2$s</strong></a>', esc_url( $bundle_payment_url ), $bundle_payment_id );
        $primary_subscription_id = (int) pms_get_payment_meta( $bundle_payment_id, 'subscription_id', true );

        if( !empty( $primary_subscription_id ) ) {

            $primary_subscription_url = add_query_arg(
                    array(
                            'page'            => 'pms-members-page',
                            'subpage'         => 'edit_subscription',
                            'subscription_id' => $primary_subscription_id,
                    ),
                    admin_url( 'admin.php' )
            );

            $primary_subscription_link = sprintf( '<a href="%1$s"><strong>#%2$s</strong></a>', esc_url( $primary_subscription_url ), $primary_subscription_id );

        }

    }

    switch( $log['type'] ) {

        case 'order_bumps_bundle_primary_created':

            $bump_member_subscription_ids = !empty( $log['data']['bump_member_subscription_ids'] ) && is_array( $log['data']['bump_member_subscription_ids'] ) ? array_map( 'absint', $log['data']['bump_member_subscription_ids'] ) : array();
            $bump_subscription_links      = array();

            foreach( $bump_member_subscription_ids as $bump_member_subscription_id ) {

                if( empty( $bump_member_subscription_id ) )
                    continue;

                $bump_subscription_url = add_query_arg(
                        array(
                                'page'            => 'pms-members-page',
                                'subpage'         => 'edit_subscription',
                                'subscription_id' => $bump_member_subscription_id,
                        ),
                        admin_url( 'admin.php' )
                );

                $bump_subscription_links[] = sprintf( '<a href="%1$s"><strong>#%2$s</strong></a>', esc_url( $bump_subscription_url ), $bump_member_subscription_id );

            }

            if( empty( $bump_subscription_links ) )
                return __( 'This is the primary subscription for an Order Bumps bundle.', 'paid-member-subscriptions' );

            if( count( $bump_subscription_links ) === 1 )
                return sprintf( __( 'This is the primary subscription for a bundle that also includes Order Bump subscription %s.', 'paid-member-subscriptions' ), $bump_subscription_links[0] );

            $last_bump_subscription_link = array_pop( $bump_subscription_links );

            return sprintf( __( 'This is the primary subscription for a bundle that also includes Order Bump subscriptions %1$s and %2$s.', 'paid-member-subscriptions' ), implode( ', ', $bump_subscription_links ), $last_bump_subscription_link );

        case 'order_bump_added':

            if( !empty( $primary_subscription_link ) )
                return sprintf( __( 'Subscription created as an Order Bump alongside the primary subscription %s.', 'paid-member-subscriptions' ), $primary_subscription_link );

            return __( 'Subscription created as an Order Bump alongside the primary subscription.', 'paid-member-subscriptions' );

        case 'order_bump_activated':

            if( !empty( $bundle_payment_link ) )
                return sprintf( __( 'Order Bump activated after the bundle payment %s was completed.', 'paid-member-subscriptions' ), $bundle_payment_link );

            return __( 'Order Bump activated after the bundle payment was completed.', 'paid-member-subscriptions' );

    }

    return $message;

}
add_filter( 'pms_subscription_logs_system_error_messages', 'pms_subscription_logs_order_bump_messages', 10, 2 );


/**
 * Outputs a bundle-aware notice in the payment refund modal
 *
 * - only renders when the payment being refunded is an Order Bumps bundle (_pms_order_items meta present and the bundle lists at least one bump in _pms_order_bumps_member_subscription_ids)
 * - lets the admin see, before confirming the refund, that the status change they pick will cascade to every bump in the bundle (not only the primary subscription PMS handles natively)
 * - on trial-anchored bundles that are still within the primary's trial window, appends an extra paragraph explaining that the primary subscription will be force-expired regardless of the chosen status (no recurring charge has fired yet, so the canceled-state "keep access until billing date" semantics do not apply)
 * - hooked on pms_admin_refund_modal_before_form so the notice appears above the form's Subscription status after refund dropdown
 *
 */
function pms_admin_refund_modal_bundle_notice( $payment_id ) {

    $order_items = pms_get_payment_meta( $payment_id, '_pms_order_items', true );

    if( empty( $order_items ) || !is_array( $order_items ) )
        return;

    $bump_member_subscription_ids = pms_get_payment_meta( $payment_id, '_pms_order_bumps_member_subscription_ids', true );
    $bump_member_subscription_ids = is_array( $bump_member_subscription_ids ) ? array_filter( array_map( 'absint', $bump_member_subscription_ids ) ) : array();
    $bump_count                   = count( $bump_member_subscription_ids );

    if( empty( $bump_count ) )
        return;

    $primary_member_subscription_id = (int) pms_get_payment_meta( $payment_id, 'subscription_id', true );
    $primary_subscription_link      = '';
    $bump_subscription_links        = array();
    $primary_trial_end              = pms_get_payment_meta( $payment_id, '_pms_order_bumps_primary_trial_end', true );
    $primary_in_trial               = !empty( $primary_trial_end ) && strtotime( $primary_trial_end ) > time();

    if( !empty( $primary_member_subscription_id ) ) {

        $primary_subscription_link = sprintf(
                '<a href="%1$s"><strong>#%2$s</strong></a>',
                esc_url( add_query_arg( array( 'page' => 'pms-members-page', 'subpage' => 'edit_subscription', 'subscription_id' => $primary_member_subscription_id ), admin_url( 'admin.php' ) ) ),
                $primary_member_subscription_id
        );

    }

    foreach( $bump_member_subscription_ids as $bump_member_subscription_id ) {

        $bump_subscription_links[] = sprintf(
                '<a href="%1$s"><strong>#%2$s</strong></a>',
                esc_url( add_query_arg( array( 'page' => 'pms-members-page', 'subpage' => 'edit_subscription', 'subscription_id' => $bump_member_subscription_id ), admin_url( 'admin.php' ) ) ),
                $bump_member_subscription_id
        );

    }

    ?>
    <div class="notice notice-warning pms-notice pms-refund-modal__bundle-notice">
        <p> <?php printf( esc_html( _n( 'This is a bundle payment with %1$d bump.', 'This is a bundle payment with %1$d bumps.', $bump_count, 'paid-member-subscriptions' ) ), absint( $bump_count ) ); ?> </p>

        <ul style="font-size: 13px;">
            <?php if( !empty( $primary_subscription_link ) ) : ?>
                <li> <?php printf( wp_kses_post( __( '<strong>Primary subscription:</strong> %s', 'paid-member-subscriptions' ) ), wp_kses_post( $primary_subscription_link ) ); ?> </li>
            <?php endif; ?>

            <?php if( !empty( $bump_subscription_links ) ) : ?>
                <li> <?php printf( wp_kses_post( _n( '<strong>Bump subscription:</strong> %s', '<strong>Bump subscriptions:</strong> %s', $bump_count, 'paid-member-subscriptions' ) ), wp_kses_post( implode( ', ', $bump_subscription_links ) ) ); ?> </li>
            <?php endif; ?>
        </ul>

        <p> <?php esc_html_e( 'The status change selected below will be applied to every subscription in this bundle: the primary subscription and all bump subscriptions.', 'paid-member-subscriptions' ); ?> </p>

        <p> <?php esc_html_e( 'Renewal payments for these subscriptions are not refunded by this action. Only this initial bundle payment is refunded.', 'paid-member-subscriptions' ); ?> </p>

        <?php if( $primary_in_trial ) : ?>
            <p> <?php esc_html_e( 'The primary subscription on this bundle is currently within its trial period. It will be expired immediately on refund regardless of the status you choose above, because no recurring charge has fired yet to preserve access against.', 'paid-member-subscriptions' ); ?> </p>
        <?php endif; ?>
    </div>
    <?php

}
add_action( 'pms_admin_refund_modal_before_form', 'pms_admin_refund_modal_bundle_notice' );


/**
 * Formats the bundle's stored tax rate as a label prefix
 *
 * - reads the rate persisted on the order at checkout (_pms_order_items['tax_rate']), the exact rate Tax applied
 * - trims trailing zeros so 21.00 reads as "21% " and 8.50 as "8.5% "
 * - returns an empty string when no rate is stored or it is zero, so the tax row falls back to a bare "TAX/VAT" label
 *
 */
function pms_bundle_payment_tax_rate_prefix( $order ) {

    $rate = isset( $order['tax_rate'] ) && is_numeric( $order['tax_rate'] ) ? (float) $order['tax_rate'] : 0;

    if( $rate == 0 )
        return '';

    return rtrim( rtrim( sprintf( '%.2f', $rate ), '0' ), '.' ) . '% ';

}


/**
 * Adds a per-plan breakdown to the Payments list Amount column for bundle payments
 *
 * - only acts on Order Bumps bundle payments (_pms_order_items meta present); single-plan payments are left to Tax and core untouched
 * - reads the persisted per-item snapshot, so it keeps working after the add-on is deactivated, matching the other bundle surfaces in this file
 * - mirrors the Tax add-on's amount-column bubble: the discount code (when present), one net (pre-tax) row per plan, the pre-tax subtotal, the bundle tax prefixed with its effective rate, and the bundle total
 * - the subtotal and tax rows only render when tax was charged; the rate comes from the value persisted on the order at checkout (pms_bundle_payment_tax_rate_prefix)
 * - runs after the Tax breakdown (priority 20); Tax bails on bundles for lack of a single pms_tax_rate meta, so the two never render together
 *
 */
function pms_admin_bundle_payment_amount_breakdown( $output, $item ) {

    if( empty( $item['id'] ) )
        return $output;

    $order = pms_get_payment_meta( $item['id'], '_pms_order_items', true );

    if( empty( $order['items'] ) || !is_array( $order['items'] ) )
        return $output;

    $payment  = pms_get_payment( $item['id'] );
    $currency = !empty( $order['currency'] ) ? $order['currency'] : ( !empty( $payment->currency ) ? $payment->currency : pms_get_active_currency() );
    $total    = isset( $order['total'] ) ? $order['total'] : $item['amount'];

    // bundle-level tax = sum of the per-item tax; feeds both the tax row and the pre-tax subtotal (total - tax) below
    $tax_total = 0;
    foreach( $order['items'] as $order_item )
        $tax_total += !empty( $order_item['tax_amount'] ) ? (float) $order_item['tax_amount'] : 0;

    ob_start(); ?>

        <span class="pms-has-bubble">

            <?php echo esc_html( pms_format_price( $item['amount'], $currency ) ); ?>
            <?php if( !empty( $item['discount_code'] ) ) : ?>
                <span class="pms-discount-dot"> % </span>
            <?php endif; ?>

            <div class="pms-bubble">

                <?php if( !empty( $item['discount_code'] ) ) : ?>
                    <div>
                        <span class="alignleft"><?php esc_html_e( 'Discount code', 'paid-member-subscriptions' ); ?></span>
                        <span class="alignright"><?php echo esc_html( $item['discount_code'] ); ?></span>
                    </div><br>
                <?php endif; ?>

                <?php

                // one net (pre-tax) row per plan (item total minus its own tax), so the plan rows add up to the subtotal, mirroring Tax's subtotal/tax/total waterfall
                foreach( $order['items'] as $order_item ) :
                    $item_net = ( isset( $order_item['total'] ) ? (float) $order_item['total'] : 0 ) - ( !empty( $order_item['tax_amount'] ) ? (float) $order_item['tax_amount'] : 0 ); ?>
                    <div>
                        <span class="alignleft"><?php echo esc_html( $order_item['name'] ); ?></span>
                        <span class="alignright"><?php echo esc_html( pms_format_price( $item_net, $currency ) ); ?></span>
                    </div><br>
                <?php endforeach; ?>

                <?php

                // subtotal (total - tax) and tax rows render only when tax was charged, so a no-tax bundle does not show a redundant subtotal equal to the total
                if( $tax_total > 0 ) : ?>

                    <div>
                        <span class="alignleft"><?php esc_html_e( 'Subtotal', 'paid-member-subscriptions' ); ?></span>
                        <span class="alignright"><?php echo esc_html( pms_format_price( $total - $tax_total, $currency ) ); ?></span>
                    </div><br>
                    <div>
                        <span class="alignleft"><?php echo esc_html( pms_bundle_payment_tax_rate_prefix( $order ) ) . esc_html__( 'TAX/VAT', 'paid-member-subscriptions' ); ?></span>
                        <span class="alignright"><?php echo esc_html( pms_format_price( $tax_total, $currency ) ); ?></span>
                    </div><br>
                <?php endif; ?>

                <div>
                    <span class="alignleft"><?php esc_html_e( 'Total', 'paid-member-subscriptions' ); ?></span>
                    <span class="alignright"><?php echo esc_html( pms_format_price( $total, $currency ) ); ?></span>
                </div><br>

            </div>
        </span>

    <?php return ob_get_clean();

}
add_filter( 'pms_payments_list_table_column_amount', 'pms_admin_bundle_payment_amount_breakdown', 30, 2 );


/**
 * Adds a per-plan breakdown to the front-end payment history Amount tooltip for bundle payments
 *
 * - the payment-history shortcode exposes the amount cell's title attribute through pms_payment_history_amount_row_title, where the Tax add-on echoes its single-plan breakdown; bundles have no single pms_tax_rate, so nothing is shown for them
 * - only acts on Order Bumps bundle payments (_pms_order_items meta present) and echoes plain text into the title, mirroring the Tax add-on's convention for this filter (the title attribute holds no markup)
 * - lives in core next to the list-table breakdown so it keeps working after the add-on is deactivated
 * - runs after the Tax breakdown (priority 20), which bails on bundles, so only one contributor writes the title
 *
 */
function pms_account_bundle_payment_amount_breakdown( $output, $payment ) {

    if( empty( $payment->id ) )
        return $output;

    $order = pms_get_payment_meta( $payment->id, '_pms_order_items', true );

    if( empty( $order['items'] ) || !is_array( $order['items'] ) )
        return $output;

    $currency = !empty( $order['currency'] ) ? $order['currency'] : ( !empty( $payment->currency ) ? $payment->currency : pms_get_active_currency() );
    $total    = isset( $order['total'] ) ? $order['total'] : $payment->amount;

    $lines     = array();
    $tax_total = 0;

    // one net (pre-tax) line per plan; the running tax_total feeds the subtotal (total - tax) and the tax line
    foreach( $order['items'] as $order_item ) {
        $tax_total += !empty( $order_item['tax_amount'] ) ? (float) $order_item['tax_amount'] : 0;
        $item_net = ( isset( $order_item['total'] ) ? (float) $order_item['total'] : 0 ) - ( !empty( $order_item['tax_amount'] ) ? (float) $order_item['tax_amount'] : 0 );
        $lines[] = esc_attr( $order_item['name'] ) . ': ' . pms_format_price( $item_net, $currency );
    }

    // subtotal and tax lines only when tax was charged; the net plan lines above sum to this subtotal, and subtotal + tax = total
    if( $tax_total > 0 ) {
        $lines[] = esc_html__( 'Subtotal', 'paid-member-subscriptions' ) . ': ' . pms_format_price( $total - $tax_total, $currency );
        $lines[] = pms_bundle_payment_tax_rate_prefix( $order ) . esc_html__( 'TAX/VAT', 'paid-member-subscriptions' ) . ': ' . pms_format_price( $tax_total, $currency );
    }

    $lines[] = esc_html__( 'Total', 'paid-member-subscriptions' ) . ': ' . pms_format_price( $total, $currency );

    echo implode( "\n", $lines ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    return $output;

}
add_filter( 'pms_payment_history_amount_row_title', 'pms_account_bundle_payment_amount_breakdown', 30, 2 );
