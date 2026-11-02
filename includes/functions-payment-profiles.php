<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cancels the member's previous PayPal profiles from the paid plan's group once a checkout payment is completed
 *
 * @param int   $payment_id
 * @param array $data
 * @param array $old_data
 *
 */
function pms_cancel_previous_paypal_profiles( $payment_id, $data, $old_data ) {

    if( empty( $data['status'] ) || $data['status'] != 'completed' || ( isset( $old_data['status'] ) && $old_data['status'] == 'completed' ) )
        return;

    $checkout_types = array(
        'subscription_initial_payment',
        'subscription_installment_initial_payment',
        'subscription_upgrade_payment',
        'subscription_downgrade_payment',
        'subscription_renewal_payment',
        'recurring_payment_profile_created',
        'expresscheckout',
        'web_accept_paypal_standard',
    );

    $payment = pms_get_payment( $payment_id );

    if( empty( $payment->id ) || empty( $payment->user_id ) || empty( $payment->subscription_id ) || !in_array( $payment->type, $checkout_types ) )
        return;

    $member_subscription_id = $payment->member_subscription_id;

    if( empty( $member_subscription_id ) ) {
        $current_subscription   = pms_get_current_subscription_from_tier( $payment->user_id, $payment->subscription_id );
        $member_subscription_id = !empty( $current_subscription->id ) ? $current_subscription->id : 0;
    }

    pms_cancel_replaced_paypal_profiles( $payment->user_id, $payment->subscription_id, $member_subscription_id, $payment );

}
add_action( 'pms_payment_update', 'pms_cancel_previous_paypal_profiles', 20, 3 );


/**
 * Cancels the member's previous PayPal profiles from the plan's group when a plan change goes through
 *
 * @param PMS_Member_Subscription $subscription
 *
 */
function pms_cancel_previous_paypal_profiles_on_plan_change( $subscription ) {

    if( empty( $subscription->id ) || empty( $subscription->user_id ) )
        return;

    pms_cancel_replaced_paypal_profiles( $subscription->user_id, $subscription->subscription_plan_id, $subscription->id );

}
add_action( 'pms_psp_before_upgrade_subscription', 'pms_cancel_previous_paypal_profiles_on_plan_change' );
add_action( 'pms_psp_before_downgrade_subscription', 'pms_cancel_previous_paypal_profiles_on_plan_change' );
add_action( 'pms_psp_before_change_subscription', 'pms_cancel_previous_paypal_profiles_on_plan_change' );


/**
 * Cancels the member's PayPal profiles from the plan's group that a completed checkout payment, or a plan change without
 * one, replaces
 *
 * @param int              $user_id
 * @param int              $plan_id
 * @param int              $log_subscription_id
 * @param PMS_Payment|null $payment
 *
 */
function pms_cancel_replaced_paypal_profiles( $user_id, $plan_id, $log_subscription_id, $payment = null ) {

    static $tried_profiles = array();

    global $wpdb;

    $user_id    = (int)$user_id;
    $payment_id = !empty( $payment ) ? (int)$payment->id : 0;
    $since      = !empty( $payment ) ? strtotime( $payment->date ) : time();

    $subscription_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, subscription_plan_id, payment_profile_id, payment_gateway FROM {$wpdb->prefix}pms_member_subscriptions WHERE user_id = %d AND payment_profile_id LIKE %s",
        $user_id,
        $wpdb->esc_like( 'I-' ) . '%'
    ) );

    $payment_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, subscription_plan_id, profile_id, type FROM {$wpdb->prefix}pms_payments WHERE user_id = %d AND profile_id LIKE %s AND date >= %s",
        $user_id,
        $wpdb->esc_like( 'I-' ) . '%',
        date( 'Y-m-d H:i:s', $since - 400 * DAY_IN_SECONDS )
    ) );

    if( empty( $subscription_rows ) && empty( $payment_rows ) )
        return;

    $group_plan_ids = wp_list_pluck( pms_get_subscription_plans_group( $plan_id, false ), 'id' );

    $failed_attempts  = get_option( 'pms_api_failed_attempts', array() );
    $handled_profiles = get_user_meta( $user_id, 'pms_paypal_profile_cancel_requested' );

    if( !empty( $failed_attempts[$user_id] ) && is_array( $failed_attempts[$user_id] ) )
        $handled_profiles = array_merge( $handled_profiles, array_column( $failed_attempts[$user_id], 'payment_profile_id' ) );

    $profiles = array_diff_key( pms_select_previous_paypal_profiles( $payment, $subscription_rows, $payment_rows, $group_plan_ids, $handled_profiles ), $tried_profiles );

    foreach( $profiles as $profile_id => $profile_plan_id ) {

        $tried_profiles[ $profile_id ] = true;

        $note = $payment_id ? sprintf( 'Subscription canceled because payment #%d replaced it.', $payment_id ) : 'Subscription canceled because the member changed plans.';

        $cancel_result = pms_in_api_cancel_paypal_subscription( $profile_id, 'cancel_previous_profile', $note );

        $log_data = array( 'profile_id' => $profile_id, 'payment_id' => $payment_id );

        if( $cancel_result === true ) {

            $log_type = $payment_id ? 'paypal_previous_profile_canceled' : 'paypal_previous_profile_canceled_on_plan_change';

        } else {

            $log_data['error'] = $cancel_result['error'];
            $log_data['retry'] = pms_schedule_previous_paypal_profile_cancel_retry( $user_id, $profile_plan_id, $profile_id, $payment_id, $cancel_result['error'] );

            $log_type = $payment_id ? 'paypal_previous_profile_cancel_failed' : 'paypal_previous_profile_cancel_failed_on_plan_change';

        }

        if( $cancel_result === true || $log_data['retry'] )
            add_user_meta( $user_id, 'pms_paypal_profile_cancel_requested', $profile_id );

        if( !empty( $log_subscription_id ) )
            pms_add_member_subscription_log( $log_subscription_id, $log_type, $log_data );

    }

}


/**
 * Returns the PayPal profiles a completed checkout payment, or a plan change without one, replaces, as profile id => plan id
 *
 * Keeps the payment's own profile, the profile of a PayPal Standard subscription when the payment is on PayPal Standard
 * (Standard writes the new subscr_id on the subscription only after the payment completes) and profiles created by a
 * later checkout. Returns nothing when a kept profile was already canceled: the payment completed late.
 *
 * @param PMS_Payment|null $payment
 * @param array            $subscription_rows
 * @param array            $payment_rows
 * @param array            $group_plan_ids
 * @param array            $handled_profiles
 *
 * @return array
 *
 */
function pms_select_previous_paypal_profiles( $payment, $subscription_rows, $payment_rows, $group_plan_ids, $handled_profiles ) {

    $keep            = !empty( $payment->profile_id ) ? array( $payment->profile_id ) : array();
    $plan_by_profile = array();
    $newer           = array();

    foreach( $subscription_rows as $row ) {

        if( !in_array( $row->subscription_plan_id, $group_plan_ids ) )
            continue;

        if( !empty( $payment ) && $payment->payment_gateway == 'paypal_standard' && $row->payment_gateway == 'paypal_standard' )
            $keep[] = $row->payment_profile_id;

        $plan_by_profile[ $row->payment_profile_id ] = (int)$row->subscription_plan_id;

    }

    foreach( $payment_rows as $row ) {

        if( !in_array( $row->subscription_plan_id, $group_plan_ids ) )
            continue;

        if( !empty( $payment ) && $row->type == 'recurring_payment_profile_created' && (int)$row->id > (int)$payment->id )
            $newer[] = $row->profile_id;

        if( !isset( $plan_by_profile[ $row->profile_id ] ) )
            $plan_by_profile[ $row->profile_id ] = (int)$row->subscription_plan_id;

    }

    $late_completion = array_intersect( $keep, $handled_profiles );

    if( !empty( $late_completion ) )
        return array();

    return array_diff_key( $plan_by_profile, array_flip( array_merge( $keep, $newer, $handled_profiles ) ) );

}


/**
 * Hands a failed cancel to the hourly retry, pms_in_ppsrp_retry_cancel_paypal_subscription()
 *
 * @return bool - whether the hourly retry now covers the profile
 *
 */
function pms_schedule_previous_paypal_profile_cancel_retry( $user_id, $plan_id, $profile_id, $payment_id, $error ) {

    if( pms_get_paypal_api_credentials() == false )
        return false;

    $failed_attempts = get_option( 'pms_api_failed_attempts', array() );

    if( !is_array( $failed_attempts ) )
        $failed_attempts = array();

    if( !empty( $failed_attempts[$user_id][$plan_id] ) )
        return false;

    $failed_attempts[$user_id][$plan_id] = array(
        'payment_profile_id' => $profile_id,
        'payment_id'         => $payment_id,
        'retry_count'        => 1,
        'retries'            => array( array( 'time' => time(), 'error' => $error ) ),
    );

    update_option( 'pms_api_failed_attempts', $failed_attempts, false );

    $args = array( $user_id, $plan_id, 'cancel_previous_profile' );

    if( wp_get_schedule( 'pms_api_cancel_paypal_subscription', $args ) == false )
        wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'pms_api_cancel_paypal_subscription', $args );

    return true;

}


/**
 * Shows administrators the previous PayPal profiles the hourly retry has not canceled yet
 *
 */
function pms_add_previous_paypal_profile_cancel_notice() {

    if( wp_doing_ajax() )
        return;

    $failed_attempts = get_option( 'pms_api_failed_attempts', array() );

    if( empty( $failed_attempts ) || !is_array( $failed_attempts ) )
        return;

    $lines    = array();
    $profiles = array();

    foreach( $failed_attempts as $user_id => $plans ) {
        foreach( (array)$plans as $entry ) {

            if( !isset( $entry['payment_id'] ) || empty( $entry['payment_profile_id'] ) )
                continue;

            $last_retry  = !empty( $entry['retries'] ) ? end( $entry['retries'] ) : array();
            $last_error  = esc_html( !empty( $last_retry['error'] ) ? $last_retry['error'] : '' );
            $user        = get_userdata( $user_id );
            $member_link = '<a href="' . esc_url( add_query_arg( array( 'page' => 'pms-members-page', 'subpage' => 'edit_member', 'member_id' => (int)$user_id ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( !empty( $user->user_login ) ? $user->user_login : $user_id ) . '</a>';

            if( !empty( $entry['payment_id'] ) )
                $lines[] = sprintf( __( 'Profile <strong>%1$s</strong> of %2$s, after payment #%3$s: %4$s', 'paid-member-subscriptions' ), esc_html( $entry['payment_profile_id'] ), $member_link, (int)$entry['payment_id'], $last_error );
            else
                $lines[] = sprintf( __( 'Profile <strong>%1$s</strong> of %2$s, after a plan change: %3$s', 'paid-member-subscriptions' ), esc_html( $entry['payment_profile_id'] ), $member_link, $last_error );

            $profiles[] = $entry['payment_profile_id'];

        }
    }

    if( empty( $lines ) )
        return;

    $notice_id = 'pms_previous_paypal_profile_cancel_failed_' . substr( md5( implode( ',', $profiles ) ), 0, 12 );

    $message  = __( '<strong>Paid Member Subscriptions</strong> could not cancel these previous PayPal profiles, so PayPal may still charge these members. It retries every hour and stops after 10 failed attempts. Cancel any profile that stays on this list in your PayPal account.', 'paid-member-subscriptions' );
    $message .= '<br>' . implode( '<br>', $lines );
    $message .= sprintf( __( ' %1$sDismiss%2$s', 'paid-member-subscriptions' ), "<a href='" . esc_url( wp_nonce_url( add_query_arg( $notice_id . '_dismiss_notification', '0' ), 'pms_general_notice_dismiss' ) ) . "' type='button' class='notice-dismiss'><span class='screen-reader-text'>", "</span></a>" );

    new PMS_Add_General_Notices( $notice_id, $message, 'notice-error' );

}
add_action( 'admin_init', 'pms_add_previous_paypal_profile_cancel_notice', 9 );


/**
 * Adds the subscription log messages for a previous PayPal profile canceled after a plan change without a payment
 *
 * @param string $message
 * @param array  $log
 *
 * @return string
 *
 */
function pms_add_previous_paypal_profile_plan_change_log_messages( $message, $log ) {

    if( empty( $log ) )
        return $message;

    switch ( $log['type'] ) {
        case 'paypal_previous_profile_canceled_on_plan_change':
            $message = sprintf( __( 'Canceled the previous PayPal profile <strong>%s</strong> after the plan change.', 'paid-member-subscriptions' ), esc_html( $log['data']['profile_id'] ) );
            break;
        case 'paypal_previous_profile_cancel_failed_on_plan_change':
            $message  = sprintf( __( 'Could not cancel the previous PayPal profile <strong>%1$s</strong> after the plan change: %2$s', 'paid-member-subscriptions' ), esc_html( $log['data']['profile_id'] ), esc_html( $log['data']['error'] ) );
            $message .= ' ' . ( !empty( $log['data']['retry'] ) ? __( 'PMS retries every hour.', 'paid-member-subscriptions' ) : __( 'Cancel it in PayPal.', 'paid-member-subscriptions' ) );
            break;
    }

    return $message;

}
add_filter( 'pms_subscription_logs_system_error_messages', 'pms_add_previous_paypal_profile_plan_change_log_messages', 10, 2 );


/**
 * Removes the leftover events of the deleted PayPal Express retry
 *
 * An older Pro can still schedule them until it updates, so each one is removed the first time it fires.
 *
 */
function pms_unschedule_legacy_express_cancel_retries() {

    wp_unschedule_hook( 'pms_api_retry_cancel_paypal_subscription' );

    remove_action( 'pms_api_retry_cancel_paypal_subscription', 'pms_in_api_retry_cancel_paypal_subscription', 10 );

}
add_action( 'pms_api_retry_cancel_paypal_subscription', 'pms_unschedule_legacy_express_cancel_retries', 1 );
