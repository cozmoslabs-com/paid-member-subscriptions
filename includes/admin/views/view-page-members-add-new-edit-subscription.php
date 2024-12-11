<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * View for adding / editting member subscriptions
 *
 */

$subpage   = isset( $_GET['subpage'] ) ? sanitize_text_field( $_GET['subpage'] ) : '';
$member_id = ( ! empty( $_GET['member_id'] ) ? (int)$_GET['member_id'] : 0 );

$first_subscription = false;

if( empty( $member_id ) && $subpage == 'add_subscription' )
	$first_subscription = true;

if( ! empty( $_POST ) ) {

	$form_data = pms_array_sanitize_text_field( $_POST );

	// Set the subscription id if it exists
	$form_data['id'] = ( ! empty( $_GET['subscription_id'] ) ? (int)$_GET['subscription_id'] : 0 );

	if( isset( $_GET['subscription_id'] ) )
		$member_subscription = pms_get_member_subscription( (int)$_GET['subscription_id'] );

} else {

	if( $subpage == 'edit_subscription' ) {

		$member_subscription = pms_get_member_subscription( (int)$_GET['subscription_id'] );

		if( is_null( $member_subscription ) )
			return;

		$form_data = $member_subscription->to_array();

	} else

		// Set some defaults for add_new subscription
		$form_data = array(
			'start_date' => date( 'Y-m-d' ),
			'status'	 => 'active'
		);

}
?>


<div class="wrap cozmoslabs-wrap">

    <h1></h1>
    <!-- WordPress Notices are added after the h1 tag -->

    <div class="cozmoslabs-page-header">
        <div class="cozmoslabs-section-title">

            <h3 class="cozmoslabs-page-title">
                <?php
                if( $subpage == 'edit_subscription' )
                    esc_html_e( 'Edit Member Subscription', 'paid-member-subscriptions' );
                else
                    esc_html_e( 'Add Member Subscription', 'paid-member-subscriptions' );
                ?>
            </h3>

        </div>
    </div>

    <form id="pms-form-<?php echo ( $subpage == 'add_subscription' ? 'add' : 'edit' ); ?>-member-subscription" class="pms-form" method="POST">

        <!-- Member/User Data -->
        <div id="pms-member-details" class="postbox cozmoslabs-form-subsection-wrapper">

            <h3 class="hndle cozmoslabs-subsection-title">
                <span><?php esc_html_e( 'Member', 'paid-member-subscriptions' ); ?></span>
            </h3>

            <div class="inside cozmoslabs-form-field-wrapper">

                <?php if( $subpage == 'add_subscription' && $first_subscription ) : ?>

                    <?php
                    $users = pms_count_users();

                    if( $users < apply_filters( 'pms_add_new_member_select_user_limit', '8000' ) ) : ?>
                        <label class="cozmoslabs-form-field-label" for="pms-member-username"><?php esc_html_e( 'User', 'paid-member-subscriptions' ) ?></label>
                        <select id="pms-member-username" name="pms-member-username" class="widefat pms-chosen">
                            <option value=""><?php esc_html_e( 'Select...', 'paid-member-subscriptions' ); ?></option>
                            <?php
                                $users = pms_get_users_non_members();

                                foreach( $users as $user ) {
                                    $display_name = $user['username'] . ' (' . $user['user_email'] . ')';
                                    echo '<option ' . ( ! empty( $form_data['user_id'] ) ? selected( $form_data['user_id'], $user['id'], false ) : '' ) . ' value="' . esc_attr( $user['id'] ) . '">' . esc_html( apply_filters( 'pms_add_new_member_dropdown_display_name', $display_name, $user['id'], $form_data ) ) . '</option>';
                                }
                            ?>
                        </select>

                        <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php printf( wp_kses_post( __( 'Select the username you wish to associate a subscription plan with. You can create a new user <a href="%s">here</a>.', 'paid-member-subscriptions' ) ), esc_url( admin_url('user-new.php') ) ); ?></p>
                    <?php else : ?>
                        <label for="pms-member-username-input" class="cozmoslabs-form-field-label"><?php esc_html_e( 'Username', 'paid-member-subscriptions' ) ?></label>
                        <input type="text" id="pms-member-username-input" name="pms-member-username" value="<?php echo !empty( $form_data['pms-member-username'] ) ? esc_attr( $form_data['pms-member-username'] ) : ''; ?>" />

                        <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php printf( wp_kses_post( __( 'Enter the username you wish to associate a subscription plan with. You can create a new user <a href="%s">here</a>.', 'paid-member-subscriptions' ) ), esc_url( admin_url('user-new.php') ) ); ?></p>
                    <?php endif; ?>

                    <input type="hidden" id="pms-member-user-id" name="user_id" class="widefat" value="<?php echo ( ! empty( $form_data['user_id'] ) ? esc_attr( $form_data['user_id'] ) : 0 ); ?>" />

                <?php elseif( ( $subpage == 'add_subscription' && ! $first_subscription ) || $subpage == 'edit_subscription' ): ?>

                    <?php
                        if( empty( $member_id ) )
                            $user_id = (int)$form_data['user_id'];
                        else
                            $user_id = $member_id;

                        $user = get_user_by( 'id', $user_id );
                    ?>

                    <input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
                    <div id="pms-user-details">
                    <strong><?php echo esc_html( $user->display_name ); ?></strong><br />
                    <?php echo esc_html( $user->user_email ); ?>
                    </div>
                    <div id="pms-user-subscriptions">
                    <a class="button-secondary" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pms-members-page', 'subpage' => 'edit_member', 'member_id' => (int)$user_id ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html__( 'View all subscriptions', 'paid-member-subscriptions' ); ?></a>
                    </div>

                    <?php do_action( 'pms_view_edit_subscription_after_member_data', $user_id ); ?>

                <?php endif; ?>

            </div>
        </div>

        <!-- Member Subscription Details Meta-box -->
        <div id="pms-member-subscription-details" class="postbox cozmoslabs-form-subsection-wrapper">

            <?php $disable_subscription_editing = apply_filters( 'pms_view_edit_add_new_subscription_disable_subscription_editing', false, $member_id ); ?>

            <h3 class="hndle cozmoslabs-subsection-title">
                <span><?php echo esc_html__( 'Subscription Details', 'paid-member-subscriptions' ); ?></span>
            </h3>
            <div class="inside">

                <!-- Subscription Plan -->
                <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper" id="subscription-plan-id">

                    <label for="pms-subscription-plan-id" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Subscription Plan', 'paid-member-subscriptions' ); ?> <span>*</span></label>

                    <select id="pms-subscription-plan-id" name="subscription_plan_id" class="pms-subscription-field" required <?php echo $disable_subscription_editing == true ? 'disabled' : ''; ?>>

                    <?php
                        if( $subpage == 'add_subscription' )
                            echo '<option value="0">' . esc_html__( 'Choose...', 'paid-member-subscriptions' ) . '</option>';
                    ?>

                    <?php

                        /**
                         * Grab all subscription plans if it's the member's first subscription
                         *
                         */
                        if( $subpage == 'add_subscription' && $first_subscription )
                            $subscription_plans = pms_get_subscription_plans();

                        /**
                         * If the member already has subscriptions, grab all subscription plans, but exclude the ones
                         * from the member's existing subscription plan groups
                         *
                         */
                        elseif( $subpage == 'add_subscription' && ! $first_subscription ) {

                            $existing_member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $member_id ) );
                            $subscription_plans 		   = pms_get_subscription_plans();

                            foreach( $existing_member_subscriptions as $existing_member_subscription ) {

                                $subscription_plans_group = pms_get_subscription_plans_group( $existing_member_subscription->subscription_plan_id );
                                $subscription_plans 	  = array_udiff( $subscription_plans, $subscription_plans_group, '_pms_compare_subscription_plans' );

                            }

                        /**
                         * If we edit a subcription grab only the subscriptions plans group of the current subscription plan
                         *
                         */
                        } else {
                            if( $member_subscription->is_auto_renewing() )
                                $subscription_plans = array( pms_get_subscription_plan( $member_subscription->subscription_plan_id ) );
                            else
                                $subscription_plans = pms_get_subscription_plans_group( $form_data['subscription_plan_id'], false );
                        }

                        foreach( $subscription_plans as $subscription_plan ) {
                            echo '<option value="' . esc_attr( $subscription_plan->id ) . '"' . selected( $subscription_plan->id, ( ! empty( $form_data['subscription_plan_id'] ) ? (int)$form_data['subscription_plan_id'] : 0 ), false ) . '>' . esc_html( $subscription_plan->name ) . '</option>';
                        }
                    ?>

                    </select>

                    <div class="spinner" style="float: none; margin-top: 0;"><!-- --></div>

                </div>

                <!-- Start Date -->
                <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

                    <label for="pms-subscription-start-date" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Start Date', 'paid-member-subscriptions' ); ?> <span>*</span></label>

                    <?php if( !$disable_subscription_editing ) : ?>
                        <input id="pms-subscription-start-date" type="text" name="start_date" class="datepicker pms-subscription-field" value="<?php echo ( ! empty( $form_data['start_date'] ) ? esc_attr( pms_sanitize_date( $form_data['start_date'] ) ) : '' ); ?>" required />
                    <?php else : ?>
                        <span class="readonly medium"><strong><?php echo ( ! empty( $form_data['start_date'] ) ? esc_attr( pms_sanitize_date( $form_data['start_date'] ) ) : '' ); ?></strong></span>
                    <?php endif; ?>

                </div>

                <?php
                    $settings             = get_option( 'pms_payments_settings' );
                    $hide_expiration_date = false;

                    if( isset( $member_subscription ) ) {

                        $plan = pms_get_subscription_plan( $member_subscription->subscription_plan_id );

                        if( $member_subscription->is_auto_renewing() ){

                            if( ( in_array( $member_subscription->payment_gateway, array( 'stripe_intents', 'stripe_connect' ) ) || ( $member_subscription->payment_gateway == 'paypal_express' && !empty( $settings['gateways']['paypal']['reference_transactions'] ) ) ) )
                                $hide_expiration_date = true;
                            elseif ( $plan->is_fixed_period_membership() && $plan->fixed_period_renewal_allowed() )
                                $hide_expiration_date = true;

                        } elseif ( $plan->is_fixed_period_membership() && $plan->fixed_period_renewal_allowed() )
                            $hide_expiration_date = true;

                    }

                    if( !isset( $member_subscription ) || !apply_filters( 'pms_view_add_new_edit_subscription_hide_expiration_date', $hide_expiration_date, $member_subscription ) ) :
                ?>
                        <!-- Expiration Date -->
                        <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

                            <label for="pms-subscription-expiration-date" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Expiration Date', 'paid-member-subscriptions' ); ?></label>

                            <?php if( !$disable_subscription_editing ) : ?>
                                <input id="pms-subscription-expiration-date" type="text" name="expiration_date" class="datepicker pms-subscription-field" value="<?php echo ( ! empty( $form_data['expiration_date'] ) ? esc_attr( pms_sanitize_date( $form_data['expiration_date'] ) ) : '' ); ?>" />
                            <?php else : ?>
                                <span class="readonly medium"><strong><?php echo ( ! empty( $form_data['expiration_date'] ) ? esc_attr( pms_sanitize_date( $form_data['expiration_date'] ) ) : '' ); ?></strong></span>
                            <?php endif; ?>
                        </div>
                <?php endif; ?>

                <!-- Status -->
                <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

                    <label for="pms-subscription-status" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Status', 'paid-member-subscriptions' ); ?> <span>*</span></label>

                    <select id="pms-subscription-status" name="status" class="pms-subscription-field" required <?php echo $disable_subscription_editing == true ? 'disabled' : ''; ?>>

                        <?php
                            foreach( pms_get_member_subscription_statuses() as $member_status_slug => $member_status_name ) {
                                echo '<option value="' . esc_attr( $member_status_slug ) . '"' . selected( $member_status_slug, $form_data['status'], false ) . '>' . esc_html( $member_status_name ) . '</option>';
                            }
                        ?>

                    </select>

                </div>

                <!-- Trial End -->
                <?php if( pms_payment_gateways_support( pms_get_payment_gateways( true ), 'subscription_free_trial' ) ): ?>
                <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

                    <label for="pms-subscription-trial-end" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Trial End', 'paid-member-subscriptions' ); ?></label>

                    <?php if( !$disable_subscription_editing ) : ?>
                        <input id="pms-subscription-trial-end" type="text" name="trial_end" class="datepicker pms-subscription-field" value="<?php echo ( ! empty( $form_data['trial_end'] ) ? esc_attr( pms_sanitize_date( $form_data['trial_end'] ) ) : '' ); ?>" />
                    <?php else : ?>
                        <span class="readonly medium"><strong><?php echo ( ! empty( $form_data['trial_end'] ) ? esc_attr( pms_sanitize_date( $form_data['trial_end'] ) ) : '-' ); ?></strong></span>
                    <?php endif; ?>

                </div>
                <?php endif; ?>

                <!-- Group Name and Description -->
                <?php
                $multiple_subscription_addon_active = apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-multiple-subscriptions-per-user/index.php' );
                if( $subpage == 'add_subscription' && ( $first_subscription || $multiple_subscription_addon_active ) )
                    echo esc_html( do_action('pms_admin_new_subscription_after_form_fields') );
                ?>

            </div>

        </div>

        <!-- Logs -->
        <?php if( $subpage == 'edit_subscription' && isset( $member_subscription ) ) :

            $subscription_logs = pms_get_member_subscription_meta( $member_subscription->id, 'logs', true );

            if( !empty( $subscription_logs ) ) :
            ?>
                    <div id="pms-member-subscription-logs" class="postbox cozmoslabs-form-subsection-wrapper">
                        <h3 class="hndle cozmoslabs-subsection-title">
                            <span><?php echo esc_html__( 'Subscription Logs', 'paid-member-subscriptions' ); ?></span>
                        </h3>

                        <div class="inside pms-logs-holder cozmoslabs-form-field-wrapper">
                            <?php foreach( array_reverse( $subscription_logs ) as $log ) echo $this->get_logs_row( $log ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                        <div class="inside cozmoslabs-form-field-wrapper">
                            <input type="text" name="pms_admin_log" value="" placeholder="<?php esc_html_e( 'Add entry manually...', 'paid-member-subscriptions' ); ?>" />
                            <input type="hidden" name="pms_subscription_id" value="<?php echo esc_attr( $member_subscription->id ) ?>" />
                            <?php wp_nonce_field( 'pms_add_log_entry', 'pms_nonce' ); ?>
                            <input type="submit" value="Add Log" class="button button-secondary" id="pms_add_log_entry" />
                        </div>
                    </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Member Subscription Billing Schedule Meta-box -->
        <?php if( isset( $member_subscription ) && pms_payment_gateways_support( array( $member_subscription->payment_gateway ), 'recurring_payments' ) && ( !empty( $form_data['payment_profile_id'] ) || pms_payment_gateways_support( array( $member_subscription->payment_gateway ), 'change_subscription_payment_method_admin' ) || apply_filters( 'pms_edit_subscription_edit_payment_profile_id', false ) ) ) : ?>

            <div id="pms-member-subscriptions-billing-schedule" class="postbox cozmoslabs-form-subsection-wrapper">

                <h3 class="hndle cozmoslabs-subsection-title">
                    <span><?php esc_html_e( 'Subscription Billing Schedule', 'paid-member-subscriptions' ); ?></span>
                </h3>

                    <?php if( ! empty( $form_data['payment_profile_id'] ) || apply_filters( 'pms_edit_subscription_edit_payment_profile_id', false ) ) : ?>

                        <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">
                            <label class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Payment Gateway Subscription ID', 'paid-member-subscriptions' ); ?></label>

                            <?php if( !apply_filters( 'pms_edit_subscription_edit_payment_profile_id', false ) ) : ?>
                                <span class="readonly medium"><strong><?php echo esc_html( $form_data['payment_profile_id'] ); ?></strong></span>
                            <?php else : ?>
                                <input id="pms-subscription-payment-profile-id" name="payment_profile_id" type="text" value="<?php echo !empty( $form_data['payment_profile_id'] ) ? esc_attr( $form_data['payment_profile_id'] ) : '' ?>" />
                            <?php endif; ?>

                            <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'The subscription payment schedule is handled by the payment gateway.', 'paid-member-subscriptions' ); ?></p>

                            <?php do_action( 'pms_meta_box_field_payment_profile_id', $form_data ); ?>

                        </div>

                    <?php elseif( pms_payment_gateways_support( array( $member_subscription->payment_gateway ), 'change_subscription_payment_method_admin' ) ) : ?>

                        <!-- Recurring Duration and Duration Unit -->
                        <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

                            <label for="pms-subscription-billing-duration" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Recurring Once Every', 'paid-member-subscriptions' ); ?></label>

                            <input type="text" id="pms-subscription-billing-duration" name="billing_duration" value="<?php echo ( ! empty( $form_data['billing_duration'] ) ? esc_attr( $form_data['billing_duration'] ) : '' ); ?>" />

                            <select id="pms-subscription-plan-duration-unit" name="billing_duration_unit">
                                <option value=""><?php esc_html_e( 'Choose...', 'paid-member-subscriptions' ); ?></option>
                                <option value="day"   <?php selected( 'day', ( ! empty( $form_data['billing_duration_unit'] ) ? $form_data['billing_duration_unit'] : '' ), true ); ?>><?php esc_html_e( 'Day(s)', 'paid-member-subscriptions' ); ?></option>
                                <option value="week"  <?php selected( 'week', ( ! empty( $form_data['billing_duration_unit'] ) ? $form_data['billing_duration_unit'] : '' ), true ); ?>><?php esc_html_e( 'Week(s)', 'paid-member-subscriptions' ); ?></option>
                                <option value="month" <?php selected( 'month', ( ! empty( $form_data['billing_duration_unit'] ) ? $form_data['billing_duration_unit'] : '' ), true ); ?>><?php esc_html_e( 'Month(s)', 'paid-member-subscriptions' ); ?></option>
                                <option value="year"  <?php selected( 'year', ( ! empty( $form_data['billing_duration_unit'] ) ? $form_data['billing_duration_unit'] : '' ), true ); ?>><?php esc_html_e( 'Year(s)', 'paid-member-subscriptions' ); ?></option>
                            </select>

                        </div>

                        <!-- Billing Next Payment -->
                        <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

                            <label for="pms-subscription-billing-next-payment" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Next Payment', 'paid-member-subscriptions' ); ?></label>

                            <span class="readonly medium"><strong><?php echo !empty( $form_data['billing_amount'] ) ? esc_html( pms_format_price( $form_data['billing_amount'], apply_filters( 'pms_last_payment_currency', '', $form_data ) ) ) : ''; ?></strong></span>
                            <?php echo esc_html_x( 'on', 'This is part of a payment amount: 100$ on 12/10/2025', 'paid-member-subscriptions' ); ?>
                            <input id="pms-subscription-billing-next-payment" type="text" name="billing_next_payment" class="datepicker pms-subscription-field" value="<?php echo ( ! empty( $form_data['billing_next_payment'] ) ? esc_attr( pms_sanitize_date( $form_data['billing_next_payment'] ) ) : '' ); ?>" />

                        </div>

                        <!-- Payment Gateway -->
                        <?php $payment_gateways = pms_get_payment_gateways(); ?>
                        <?php if( !apply_filters( 'pms_edit_subscription_enable_payment_gateway_editing', false ) ) : ?>
                            <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">
                                <label class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Payment Gateway', 'paid-member-subscriptions' ); ?></label>

                                <span class="readonly medium"><strong><?php echo !empty( $payment_gateways[$form_data['payment_gateway']]['display_name_admin'] ) ? esc_html( $payment_gateways[$form_data['payment_gateway']]['display_name_admin'] ) : esc_html( $form_data['payment_gateway'] ); ?></strong></span>
                                <input type="hidden" name="payment_gateway" value="<?php echo !empty( $form_data['payment_gateway'] ) ? esc_attr( $form_data['payment_gateway'] ) : ''; ?>" />
                            </div>
                        <?php else : ?>
                            <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">
                                <label class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Payment Gateway', 'paid-member-subscriptions' ); ?></label>
                                <input type="hidden" name="payment_gateway" value="<?php echo !empty( $form_data['payment_gateway'] ) ? esc_attr( $form_data['payment_gateway'] ) : ''; ?>" />

                                <select id="pms-payment-gateway" name="payment_gateway" class="pms-payment-gateway-field" required>

                                    <?php
                                    foreach( pms_get_active_payment_gateways() as $gateway ) {
                                        echo '<option value="' . esc_attr( $gateway ) . '"' . selected( $gateway, $form_data['payment_gateway'], false ) . '>' . esc_html( $payment_gateways[$gateway]['display_name_admin'] ) . '</option>';
                                    }
                                    ?>

                                </select>
                            </div>
                        <?php endif; ?>

                        <!-- Payment gateway extra custom fields -->
                        <?php
                            echo '<div id="pms-meta-box-fields-wrapper-payment-gateways">';

                            foreach( $payment_gateways as $payment_gateway_slug => $payment_gateway_details ) {

                                echo '<div data-payment-gateway="' . esc_attr( $payment_gateway_slug ) . '">';

                                    /**
                                     * Action to add extra payment gateway fields
                                     *
                                     * @param int    $subscription_id
                                     * @param string $payment_gateway_slug
                                     * @param array  $payment_gateway_details
                                     *
                                     */
                                    do_action( 'pms_view_add_new_edit_subscription_payment_gateway_extra', ( ! empty( $form_data['id'] ) ? $form_data['id'] : 0 ), $payment_gateway_slug, $payment_gateway_details );

                                echo '</div>';

                            }

                            echo '</div>';
                        ?>

                        <?php
                        if( pms_is_payment_retry_enabled() ) {

                            $subscription_payment_retry = pms_get_member_subscription_meta( $member_subscription->id, 'pms_retry_payment', true );

                            if( $subscription_payment_retry == 'active' ) {
                                ?>
                                <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">
                                    <label class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Payment Retry', 'paid-member-subscriptions' ); ?></label>

                                    <span class="readonly medium"><strong><?php esc_html_e( 'Active', 'paid-member-subscriptions' ); ?></strong></span>

                                    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php printf( esc_html__( 'A new payment attempt will be made on %s. After %s more attempts, the subscription will remain expired.', 'paid-member-subscriptions' ), '<strong>' . esc_html( $member_subscription->billing_next_payment ) . '</strong>', '<strong>' . ( ( (int)apply_filters( 'pms_retry_payment_count', 3 ) - pms_get_subscription_payments_retry_count( $member_subscription->id ) ) + 1 ) . '</strong>' ); ?></p>
                                </div>
                                <?php
                            }

                        }
                        ?>

                    <?php endif; ?>

            </div>

        <?php endif; ?>

        <?php wp_nonce_field( 'pms_' . $subpage . '_nonce' ); ?>


        <!-- Update Subscription -->
        <div class="submit">
            <h3 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Update Member Subscription', 'paid-member-subscriptions' ); ?></h3>
            <div class="cozmoslabs-publish-button-group">
                <input type="submit" class="button button-primary right" value="<?php ( $subpage == 'edit_subscription' ? esc_attr_e( 'Save Subscription', 'paid-member-subscriptions' ) : esc_attr_e( 'Add Subscription', 'paid-member-subscriptions' ) ); ?>"/>

                <?php if( $subpage == 'edit_subscription' ): ?>
                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'subscription_id' => $form_data['id'] ), admin_url( 'admin.php?page=pms-members-page' ) ), 'pms_delete_subscription_nonce' ) ) ?>" class="submitdelete deletion" onclick="return confirm( '<?php esc_html_e( 'Are you sure you want to delete this Subscription? \nThis action is irreversible.', 'paid-member-subscriptions' ); ?>' )"><?php esc_html_e( 'Delete Subscription', 'paid-member-subscriptions' ); ?></a>
                <?php endif; ?>
            </div>
        </div>

    </form>

</div>
