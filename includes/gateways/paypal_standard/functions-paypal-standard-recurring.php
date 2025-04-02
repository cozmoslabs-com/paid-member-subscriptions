<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/**
 * Output the API username, API password and API signature for the PayPal business account
 *
 * @param array $options    - The settings option for Paid Member Subscriptions
 *
 */
if( !function_exists('pms_in_settings_gateway_paypal_extra_fields') ) {

    function pms_in_settings_gateway_paypal_extra_fields( $options ) {

        // PayPal API fields
        $fields = array(
            'api_username' => array(
                'label' => __( 'API Username', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Username for Live site', 'paid-member-subscriptions' )
            ),
            'api_password' => array(
                'label' => __( 'API Password', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Password for Live site', 'paid-member-subscriptions' )
            ),
            'api_signature' => array(
                'label' => __( 'API Signature', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Signature for Live site', 'paid-member-subscriptions' )
            ),
            'test_api_username' => array(
                'label' => __( 'Test API Username', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Username for Test/Sandbox site', 'paid-member-subscriptions' )
            ),
            'test_api_password' => array(
                'label' => __( 'Test API Password', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Password for Test/Sandbox site', 'paid-member-subscriptions' )
            ),
            'test_api_signature' => array(
                'label' => __( 'Test API Signature', 'paid-member-subscriptions' ),
                'desc'  => __( 'API Signature for Test/Sandbox site', 'paid-member-subscriptions' )
            )
        );

        foreach( $fields as $field_slug => $field_details ) {
            echo '<div class="cozmoslabs-form-field-wrapper">';

            echo '<label class="cozmoslabs-form-field-label" for="paypal-' . esc_attr( str_replace('_', '-', $field_slug) ) . '">' . esc_html( $field_details['label'] ) . '</label>';

            echo '<input id="paypal-' . esc_attr( str_replace('_', '-', $field_slug) ) . '" type="password" name="pms_payments_settings[gateways][paypal][' . esc_attr( $field_slug ) . ']" value="' . ( isset($options['gateways']['paypal'][$field_slug]) ? esc_attr( $options['gateways']['paypal'][$field_slug] ) : '' ) . '" class="widefat" />';

            if( isset( $field_details['desc'] ) )
                echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . esc_html( $field_details['desc'] ) . '</p>';

            echo '</div>';
        }

    }
    add_action( 'pms_settings_page_payment_gateway_paypal_extra_fields', 'pms_in_settings_gateway_paypal_extra_fields' );
}

/**
 * Add extra supported features to the payment gateway
 *
 * @param array $supports
 *
 * @return array
 *
 */
function pms_in_ppsrp_add_payment_gateway_supports( $supports = array() ) {

    array_push( $supports, 'recurring_payments', 'subscription_sign_up_fee', 'subscription_free_trial' );

    return $supports;

}
add_filter( 'pms_payment_gateway_paypal_standard_supports', 'pms_in_ppsrp_add_payment_gateway_supports' );


/**
 * Add PayPal subscription payment to the types of payments
 *
 * @param array $types
 *
 * @return array
 *
 */
function pms_in_ppsrp_add_payment_types( $types = array() ) {

    $types['recurring_payment_profile_created'] = __( 'PayPal Recurring Initial Payment', 'paid-member-subscriptions' );
    $types['subscr_payment']                    = __( 'PayPal Standard - Subscription Payment', 'paid-member-subscriptions' );
    $types['paypal_standard_trial_payment']     = __( 'PayPal Standard - Trial Payment', 'paid-member-subscriptions' );


    return $types;

}
add_filter( 'pms_payment_types', 'pms_in_ppsrp_add_payment_types' );


/*
 * Modify the default payment type in PayPal Standard that is being saved in the database
 *
 */
function pms_in_ppsrp_change_payment_type( $payment_type, $gateway_object, $settings ) {

    if( $gateway_object->recurring == 1 )
        return 'recurring_payment_profile_created';

    return $payment_type;

}
add_filter( 'pms_paypal_standard_payment_type', 'pms_in_ppsrp_change_payment_type', 10, 3 );


/*
 * Function modifies the PayPal arguments so that instead of a direct payment,
 * we create a subscription for the user
 *
 */
function pms_in_ppsrp_paypal_args( $args, $gateway_object, $settings ) {

    $has_trial  = ( $gateway_object->subscription_plan->has_trial() && in_array( $gateway_object->form_location, array( 'register', 'new_subscription', 'retry_payment', 'register_email_confirmation', 'change_subscription' ) ) );
    $used_trial = get_option( 'pms_used_trial_' . $gateway_object->subscription_plan->id, false );

    if( !empty( $used_trial ) && in_array( $gateway_object->user_email, $used_trial ) )
        $has_trial = false;

    // Return if the recurring option is not set or if the subscription plan
    // does not have a duration set
    if( $gateway_object->recurring != 1 || ( !$gateway_object->subscription_plan->is_fixed_period_membership() && $gateway_object->subscription_plan->duration == 0 ) || ( $gateway_object->subscription_plan->is_fixed_period_membership() && !$gateway_object->subscription_plan->fixed_period_renewal_allowed() ) )
        if( !$has_trial )
            return $args;
        else
            $one_time_payment = true;

    // Handle the case in which there is nothing to be paid after the trial ( for non-recurring Fixed Period Memberships with free trial that ends on the expiration date OR free subscription plans that have sign up fee and trial and only the sign up fee is paid)
    if( isset( $one_time_payment ) && $one_time_payment && $has_trial && ( ( $gateway_object->subscription_plan->is_fixed_period_membership() && $gateway_object->subscription_plan->get_trial_expiration_date() == $gateway_object->subscription_plan->get_expiration_date() ) || $gateway_object->subscription_data['billing_amount'] == 0 ) )
        return $args;

    // Modify PayPal args
    unset( $args['amount'] );

    // Add sign up amount for a recurring subscription when there is no trial
    if( !isset( $one_time_payment ) && !$has_trial && !is_null( $gateway_object->sign_up_amount ) )
        $args['a1'] = $gateway_object->sign_up_amount;

    // Add trial period
    if( $has_trial ){

        //Add sign up fee
        $args['a1'] = !is_null( $gateway_object->sign_up_amount ) ? $gateway_object->sign_up_amount : ( $gateway_object->subscription_plan->has_sign_up_fee() ? $gateway_object->subscription_plan->sign_up_fee : 0 );

        $trial_expiration_date = strtotime( 'today +' . $gateway_object->subscription_plan->trial_duration . ' ' . $gateway_object->subscription_plan->trial_duration_unit );

        // For Fixed Period Memberships, if trial end is after expiration date, trial ends on expiration date
        if( $gateway_object->subscription_plan->is_fixed_period_membership() && strtotime( $gateway_object->subscription_plan->get_expiration_date() ) <= $trial_expiration_date ){

            $days_difference_trial = ( strtotime( $gateway_object->subscription_plan->get_expiration_date() ) - strtotime( 'today' ) ) / 86400;

            if( $days_difference_trial <= 90 ){

                $args['p1'] = $days_difference_trial;
                $args['t1'] = 'D';

            }
            else{

                $weeks_difference_trial = round( $days_difference_trial / 7 );

                $args['p1'] = $weeks_difference_trial;
                $args['t1'] = 'W';

            }
        } else{

            $args['p1'] = $gateway_object->subscription_plan->trial_duration;

            switch( $gateway_object->subscription_plan->trial_duration_unit ) {

                case 'day':
                    $args['t1'] = 'D';
                    break;

                case 'week':
                    $args['t1'] = 'W';
                    break;

                case 'month':
                    $args['t1'] = 'M';
                    break;

                case 'year':
                    $args['t1'] = 'Y';
                    break;

            }

        }

    }

    // Add second trial period
    if( ( !is_null( $gateway_object->sign_up_amount ) || !is_null( $gateway_object->amount ) ) && !$gateway_object->subscription_plan->is_fixed_period_membership() ) {

        $args['a2'] = isset( $args['a1'] ) ? $gateway_object->subscription_data['billing_amount'] : $gateway_object->amount;

        $args['p2'] = $gateway_object->subscription_plan->duration;

        switch( $gateway_object->subscription_plan->duration_unit ) {

            case 'day':
                $args['t2'] = 'D';
                break;

            case 'week':
                $args['t2'] = 'W';
                break;

            case 'month':
                $args['t2'] = 'M';
                break;

            case 'year':
                $args['t2'] = 'Y';
                break;

        }

    }

    $args['cmd']    = '_xclick-subscriptions';
    $args['src']    = 1;
    $args['sra']    = 1;
    $args['a3']     = $gateway_object->subscription_data['billing_amount'];

    if( $gateway_object->subscription_plan->is_fixed_period_membership() ){

        if( !isset( $days_difference_trial ) ){

            $days_difference = ( strtotime( $gateway_object->subscription_plan->get_expiration_date() ) - strtotime( 'today' ) ) / 86400;
            if( isset( $trial_expiration_date ) )
                $days_difference = ( strtotime( $gateway_object->subscription_plan->get_expiration_date() ) - $trial_expiration_date ) / 86400;

            $args['a2'] = isset( $args['a1'] ) ? $gateway_object->subscription_data['billing_amount'] : $gateway_object->amount;

            if( $days_difference <= 90 ){

                $args['p2'] = $days_difference;
                $args['t2'] = 'D';

            }
            else{

                $weeks_difference = round( $days_difference / 7 );

                if( $weeks_difference <= 52 ){

                    $args['p2'] = $weeks_difference;
                    $args['t2'] = 'W';

                }
                else{

                    $months_difference = round( $days_difference / 30 );

                    $args['p2'] = $months_difference;
                    $args['t2'] = 'M';
                }

            }
        }

        $args['p3'] = 1;
        $args['t3'] = 'Y';

        if( isset( $one_time_payment ) && $one_time_payment ){

            $args['src'] = 0;
            if( isset( $args['a2'] ) ){

                $args['a3'] = $args['a2'];
                $args['p3'] = $args['p2'];
                $args['t3'] = $args['t2'];

                unset( $args['a2'] );
                unset( $args['p2'] );
                unset( $args['t2'] );

            }

        }

    }
    else{

        if( !isset( $one_time_payment ) ){

            $args['p3']     = $gateway_object->subscription_plan->duration;

            switch( $gateway_object->subscription_plan->duration_unit ) {

                case 'day':
                    $args['t3'] = 'D';
                    break;

                case 'week':
                    $args['t3'] = 'W';
                    break;

                case 'month':
                    $args['t3'] = 'M';
                    break;

                case 'year':
                    $args['t3'] = 'Y';
                    break;

            }

        }
        else{

            $args['src'] = 0;
            if( isset( $args['a2'] ) ){

                $args['a3'] = $args['a2'];
                $args['p3'] = $args['p2'];
                $args['t3'] = $args['t2'];

                unset( $args['a2'] );
                unset( $args['p2'] );
                unset( $args['t2'] );

                if( $args['p3'] == 0 ){

                    $args['p3'] = 5;
                    $args['t3'] = 'Y';

                }

            }

        }

    }

    // Handle the case in which there is a recurring subscription with sign up fee
    if( isset( $args['a1'] ) && !isset( $args['p1'] ) && !isset( $args['t1'] ) ){
        $args['a2'] = $args['a1'];
        unset( $args['a1'] );
    }

    if( $has_trial ){

        $subscriptions = pms_get_member_subscriptions( array( 'user_id' => $gateway_object->user_id, 'subscription_plan_id' => $gateway_object->subscription_data['subscription_plan_id'], 'status' => 'pending' ) );

        if( !empty( $subscriptions ) ) {

            $subscription = $subscriptions[0];

            // Apply discount for the first payment after trial
            $data = apply_filters( 'pms_paypal_process_member_subscriptions_payment_data', array( 'amount' => $args['a3'] ), $subscription );
            if( isset( $args['a2'] ) && isset( $args['a1'] ) && $args['a1'] == 0 )
                $args['a2'] = $data['amount'];
            if( isset( $one_time_payment ) && $one_time_payment == true )
                $args['a3'] = $data['amount'];

        }

        // Mark one time payments with trial as non-recurring
        if( isset( $one_time_payment ) && $one_time_payment ){
            pms_add_member_subscription_meta( $subscription->id, 'pms_payment_type', 'one_time_payment' );
        }

    }


    if ( isset( $gateway_object->sign_up_amount ) && $gateway_object->sign_up_amount == 0 ) {

        $payment_data = array(
            'payment_id'      => $gateway_object->payment_id,
            'user_id'         => $gateway_object->user_id,
            'subscription_id' => $gateway_object->subscription_data['subscription_plan_id'],
            'payment_id'      => $gateway_object->subscription_data['payment_id'],
        );

        pms_in_ppsrp_update_member_subscription_data( $payment_data, array() );

    }

    return $args;
}
add_filter( 'pms_paypal_standard_args', 'pms_in_ppsrp_paypal_args', 10, 3 );


/*
 * Function that processes the IPN sent by PayPal
 *
 */
function pms_in_ppsrp_ipn_listener( $payment_data, $post_data ) {

    $payment              = pms_get_payment( $payment_data['payment_id'] );
    $current_subscription = pms_get_current_subscription_from_tier( $payment_data['user_id'], $payment_data['subscription_id'] );

    if( $payment_data['type'] == 'subscr_payment' ) {

        if ( $payment_data['status'] == 'completed' ) {

            /*
             * Handle payment related information
             *
             * Website payment is not completed, so the IPN is for the current payment, letting us know what happened
             */
            if ( $payment->status != 'completed' ) {

                if ( method_exists( $payment, 'log_data') )
                    $payment->log_data( 'paypal_ipn_received', array( 'data' => $post_data, 'desc' => 'paypal IPN' ) );

                $payment->update(
                    array(
                        'status'         => $payment_data['status'],
                        'transaction_id' => $payment_data['transaction_id'],
                        'date'           => date( 'Y-m-d H:i:s', strtotime( $payment_data['date'] ) )
                    )
                );

                if( function_exists( 'pms_add_member_subscription_log' ) && !empty ( $current_subscription->id ) ){
                    pms_add_member_subscription_log( $current_subscription->id, 'paypal_subscription_setup' );
                    pms_add_member_subscription_log( $current_subscription->id, 'subscription_activated' );
                }

                /*
                 * Handle member related information
                 */
                pms_in_ppsrp_update_member_subscription_data( $payment_data, $post_data );

            }

            // Website payment is completed, so this IPN is for a new payment that we need to register
            if ( $payment->status == 'completed' && $payment->transaction_id != $payment_data['transaction_id'] && $payment->date != date( 'Y-m-d H:i:s', strtotime( $payment_data['date'] ) ) ) {

                if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.9.5' , '>=') ) {
                    // Make sure we don't have another payment with this transaction id
                    $old_payments = pms_get_payments( array( 'transaction_id' => $payment_data['transaction_id'] ) );

                    if( !empty( $old_payments ) && !empty( $old_payments[0]->transaction_id ) )
                        return;
                }

                $new_payment = new PMS_Payment();

                $new_payment->insert( array(
                    'user_id'              => $payment_data['user_id'],
                    'amount'               => $payment_data['amount'],
                    'subscription_plan_id' => $payment_data['subscription_id'],
                    'status'               => $payment_data['status'],
                    'payment_gateway'      => 'paypal_standard'
                ) );

                if ( method_exists( $new_payment, 'log_data' ) ){
                    $new_payment->log_data( 'paypal_ipn_received', array( 'data' => $post_data, 'desc' => 'paypal IPN' ) );
                    $new_payment->log_data( 'new_payment', array( 'user' => -1, 'data' => $payment_data ) );
                }

                pms_add_payment_meta( $new_payment->id, 'subscription_id', $payment->member_subscription_id, true );

                $new_payment->update( array( 'type' => $payment_data['type'], 'transaction_id' => $payment_data['transaction_id'] ) );

                if( function_exists( 'pms_add_member_subscription_log' ) && !empty ( $current_subscription->id ) )
                    pms_add_member_subscription_log( $current_subscription->id, 'subscription_renewed_automatically' );

                /*
                 * Handle member related information
                 */
                pms_in_ppsrp_update_member_subscription_data( $payment_data, $post_data );

            }

        }

    // Trial payments
    } elseif( $payment_data['type'] == 'subscr_signup' ) {

        // For whatever reason, it seems PayPal either sends the `amount1` parameter for this IPN or doesn't, mc_amount1 is always present, we could use that only
        // but I believe we should interpret both cases
        if( isset( $post_data['amount1'] ) )
            $amount = $post_data['amount1'];
        else if( isset( $post_data['mc_amount1'] ) )
            $amount = $post_data['mc_amount1'];
        else
            return;

        if ( method_exists( $payment, 'log_data' ) )
            $payment->log_data( 'paypal_ipn_received', array( 'data' => $post_data, 'desc' => 'paypal IPN' ) );

        $amount = (float)$amount;

        if( empty( $amount ) ) {

            /*
             * Handle payment related information
             */
            if ( $payment->status != 'completed' )
                $payment->update( array('status' => 'completed', 'transaction_id' => '-', 'date' => date( 'Y-m-d H:i:s', strtotime( $post_data['subscr_date'] ) ) ) );

            /*
             * Handle member related information
             */
            pms_in_ppsrp_update_member_subscription_data($payment_data, $post_data);

        }

    } elseif( $payment_data['type'] == 'subscr_cancel' ) {

        if( isset( $current_subscription->payment_profile_id ) && isset( $post_data['payment_profile_id'] ) && $current_subscription->payment_profile_id == $post_data['payment_profile_id']){
            if( !in_array( $current_subscription->status, array( 'canceled', 'pending' ) ) ) {
                $current_subscription->update( array( 'status' => 'canceled' ) );

                if( function_exists( 'pms_add_member_subscription_log' ) && !empty( $current_subscription->id ) )
                    pms_add_member_subscription_log( $current_subscription->id, 'gateway_subscription_canceled' );
            }
        }

    }

}
add_action( 'pms_paypal_ipn_listener_verified', 'pms_in_ppsrp_ipn_listener', 10, 2 );


/*
 * Updates the member data
 *
 */
function pms_in_ppsrp_update_member_subscription_data( $payment_data, $post_data ) {

    if( empty( $payment_data ) || !is_array( $payment_data ) )
        return;

    if( empty( $post_data ) )
        $post_data = $_POST;

    $payment = pms_get_payment( $payment_data['payment_id'] );

    // Get member subscription
    $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $payment_data['user_id'], 'subscription_plan_id' => $payment_data['subscription_id'], 'number' => 1 ) );

    foreach( $member_subscriptions as $member_subscription ) {

        $subscription_plan = pms_get_subscription_plan( $member_subscription->subscription_plan_id );

        // If subscription is pending it is a new one
        if( $member_subscription->status == 'pending' ) {
            $member_subscription_expiration_date = pms_sanitize_date( $subscription_plan->get_expiration_date() ) . ' 23:59:59';

            if( $subscription_plan->has_trial() ){
                // Save email when trial is used
                $user       = get_userdata( $member_subscription->user_id );
                $used_trial = get_option( 'pms_used_trial_' . $member_subscription->subscription_plan_id, false );

                if( $used_trial == false )
                    $used_trial = array( $user->user_email );
                else
                    $used_trial[] = $user->user_email;

                update_option( 'pms_used_trial_' . $member_subscription->subscription_plan_id, $used_trial, false );
            }

            // Extend expiration date to accommodate the trial period
            if( $subscription_plan->has_trial() && !$subscription_plan->is_fixed_period_membership() ){
                $member_subscription_expiration_date = date( 'Y-m-d 23:59:59', strtotime( $member_subscription_expiration_date . "+" . $subscription_plan->trial_duration . ' ' . $subscription_plan->trial_duration_unit ) );
            }

        // This is an old subscription
        } else {

            if( strtotime( $member_subscription->expiration_date ) < time() || ( !$subscription_plan->is_fixed_period_membership() && $subscription_plan->duration === 0 ) || ( $subscription_plan->is_fixed_period_membership() && !$subscription_plan->fixed_period_renewal_allowed() ) || empty( $payment_profile_id ) )
                $member_subscription_expiration_date = pms_sanitize_date( $subscription_plan->get_expiration_date() ) . ' 23:59:59';
            else{
                if( $subscription_plan->is_fixed_period_membership() ){
                    $member_subscription_expiration_date = date( 'Y-m-d 23:59:59', strtotime( $member_subscription->expiration_date . '+ 1 year' ) );
                } else{
                    $member_subscription_expiration_date = date( 'Y-m-d 23:59:59', strtotime( $member_subscription->expiration_date . '+' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit ) );
                }
            }

        }

        // Update subscription
        $member_subscription->update( array(
            'expiration_date'       => $member_subscription_expiration_date,
            'status'                => 'active',
            'payment_profile_id'    => ( ! empty( $post_data['subscr_id'] ) ? $post_data['subscr_id'] : '' ),
            'payment_gateway'       => 'paypal_standard',
            // reset custom schedule
            'billing_amount'        => '',
            'billing_duration'      => '',
            'billing_duration_unit' => '',
            'billing_next_payment'  => ''
        ));

        do_action( 'pms_paypal_subscr_payment_after_subscription_activation', $member_subscription, $payment_data, $post_data );

        pms_delete_member_subscription_meta( $member_subscription->id, 'pms_retry_payment' );

        break;
    }


    /*
    * Change Subscription, upgrade, downgrade flow
    *
    * To grab the relevant subscription, we use the $member_subscription_id property from the payment with backwards compatibility for the old method
    */
    if( !empty( $payment->member_subscription_id ) )
        $current_subscription = pms_get_member_subscription( $payment->member_subscription_id );
    else
        $current_subscription = pms_get_current_subscription_from_tier( $payment_data['user_id'], $payment_data['subscription_id'] );

    if( !empty( $current_subscription ) && $current_subscription->subscription_plan_id != $payment_data['subscription_id'] ) {

        // Keeping the name for backwards compatibility
        do_action( 'pms_paypal_subscr_payment_before_upgrade_subscription', $current_subscription->subscription_plan_id, $payment_data, $post_data );

        $old_plan_id = $current_subscription->subscription_plan_id;

        $new_subscription_plan = pms_get_subscription_plan( $payment_data['subscription_id'] );

        $new_subscription_plan_expiration_date = pms_sanitize_date( $new_subscription_plan->get_expiration_date() ) . ' 23:59:59';

        // Extend expiration date to accommodate the trial period
        if( $new_subscription_plan->has_trial() && !$new_subscription_plan->is_fixed_period_membership() ){
            $new_subscription_plan_expiration_date = date( 'Y-m-d 23:59:59', strtotime( $new_subscription_plan_expiration_date . "+" . $new_subscription_plan->trial_duration . ' ' . $new_subscription_plan->trial_duration_unit ) );
        }

        $subscription_data = array(
            'user_id'              => $payment_data['user_id'],
            'subscription_plan_id' => $new_subscription_plan->id,
            'start_date'           => date( 'Y-m-d H:i:s' ),
            'expiration_date'      => apply_filters( 'pms_paypal_subscr_payment_change_subscription_expiration_date', $new_subscription_plan_expiration_date, $current_subscription, $new_subscription_plan->id, $post_data ),
            'status'               => 'active',
            'payment_profile_id'   => ( ! empty( $post_data['subscr_id'] ) ? $post_data['subscr_id'] : '' ),
            'payment_gateway'       => 'paypal_standard',
            // reset custom schedule
            'billing_amount'        => '',
            'billing_duration'      => '',
            'billing_duration_unit' => '',
            'billing_next_payment'  => ''
        );

        $current_subscription->update( $subscription_data );

        $context = pms_get_change_subscription_plan_context( $old_plan_id, $subscription_data['subscription_plan_id'] );

        if( function_exists( 'pms_add_member_subscription_log' ) )
            pms_add_member_subscription_log( $current_subscription->id, 'subscription_'.$context.'_success', array( 'old_plan' => $old_plan_id, 'new_plan' => $new_subscription_plan->id ) );

        // Keeping the name for backwards compatibility
        do_action( 'pms_paypal_subscr_payment_after_upgrade_subscription', $new_subscription_plan->id, $payment_data, $post_data );

        pms_delete_member_subscription_meta( $current_subscription->id, 'pms_retry_payment' );

    }

}


/*
 * Makes an API call to PayPal to change the status of a subscription profile
 * to cancel
 *
 * @param string $payment_profile_id - profile that we want to cancel
 * @param string $action - whether we call this action when cancelling a subscription or when upgrading one
 *
 */
function pms_in_api_cancel_paypal_subscription( $payment_profile_id, $action = 'cancel', $cancel_reason = '' ) {

    $confirmation = false;
    $error        = NULL;

    // Get API credentials and check if they are complete
    $api_credentials = pms_get_paypal_api_credentials();

    if( !$api_credentials ){
        $error = __( 'PayPal API credentials are missing or are incomplete', 'paid-member-subscriptions' );
        return array( 'error' => $error );
    }

    // Get payment_profile_id
    if( empty( $payment_profile_id ) ){
        $error = __( 'Payment profile ID is empty, nothing to cancel.', 'paid-member-subscriptions' );
        return array( 'error' => $error );
    }

    // Set API endpoint
    if( pms_is_payment_test_mode() )
        $api_endpoint = 'https://api-3t.sandbox.paypal.com/nvp';
    else
        $api_endpoint = 'https://api-3t.paypal.com/nvp';

    //PayPal API arguments
    $args = array(
        'USER'      => $api_credentials['username'],
        'PWD'       => $api_credentials['password'],
        'SIGNATURE' => $api_credentials['signature'],
        'VERSION'   => '76.0',
        'METHOD'    => 'ManageRecurringPaymentsProfileStatus',
        'PROFILEID' => $payment_profile_id,
        'ACTION'    => 'Cancel'
    );

    if( !empty( $cancel_reason ) )
        $args['NOTE'] = $cancel_reason;

    $request   = wp_remote_post( $api_endpoint, array( 'body' => $args, 'timeout' => 30 ) );
    $body      = wp_remote_retrieve_body( $request );


    // Error handling
    if( is_wp_error( $request ) ) {

        $error = $request->get_error_message();

    } else {

        if( is_string( $body ) )
            wp_parse_str( $body, $body );

        if( !isset($request['response']) || empty( $request['response'] ) )
            $error = __( 'No request response received.', 'paid-member-subscriptions' );
        else {

            if( isset( $request['response']['code'] ) && (int)$request['response']['code'] != 200 )
                $error = $request['response']['code'] . ( isset( $request['response']['message'] ) ? ' : ' . $request['response']['message'] : '' );

        }

        if( isset( $body['L_LONGMESSAGE0'] ) )
            $error = $body['L_LONGMESSAGE0'];

        if( isset( $body['ACK'] ) && strtolower( $body['ACK'] ) === 'success' )
            $confirmation = true;

    }

    $subscription = pms_in_ppsrp_get_subscription_by_payment_profile( $payment_profile_id );

    if( !empty( $subscription ) )
        do_action( 'pms_api_cancel_paypal_subscription_before_return', $subscription->user_id, $subscription->subscription_plan_id, $action, $confirmation, $error );


    // If all is good return true, if not return the error
    if( $confirmation && is_null($error) )
        return true;
    else
        return array( 'error' => $error );


}
add_action( 'pms_api_cancel_paypal_subscription', 'pms_in_api_cancel_paypal_subscription', 10, 3 );


/*
 * Cancels the existing PayPal subscription of the member,
 * but if for some reason the cancellation did not happen a cron job is added to
 * try to cancel the subscription once every hour
 *
 * @param int $member_subscription_id
 * @param array $payment_data
 * @param array $post_data
 *
 */
function pms_in_cancel_paypal_subscription_before_upgrade( $member_subscription_id, $payment_data, $post_data ) {

    $user_id              = $payment_data['user_id'];
    $subscription_plan_id = $member_subscription_id;

    // Get payment_profile_id
    $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $subscription_plan_id );

    if( empty( $payment_profile_id ) || !pms_is_paypal_payment_profile_id( $payment_profile_id ) )
        return;

    // Execute a profile cancellation api call to PayPal
    $cancel_result = pms_in_api_cancel_paypal_subscription( $payment_profile_id, 'upgrade', 'Subscription canceled because user upgraded to another one.');

    // If something went wrong repeat cancellation api call to PayPal every hour until the subscription gets cancelled successfully
    if( isset( $cancel_result['error'] ) && wp_get_schedule( 'pms_api_cancel_paypal_subscription', array( $user_id, $subscription_plan_id, 'upgrade' ) ) == false && pms_get_paypal_api_credentials() != false )
        wp_schedule_event( time() + 60 * 60, 'hourly', 'pms_api_cancel_paypal_subscription', array( $user_id, $subscription_plan_id, 'upgrade' ) );

}
add_action( 'pms_paypal_web_accept_before_upgrade_subscription', 'pms_in_cancel_paypal_subscription_before_upgrade', 10, 3 );
add_action( 'pms_paypal_subscr_payment_before_upgrade_subscription', 'pms_in_cancel_paypal_subscription_before_upgrade', 10, 3 );


/*
 * Hooks to 'pms_api_cancel_paypal_subscription_before_return' action to clear the scheduled cron job,
 * if successfully cancelled the payment profile in PayPal
 *
 */
function pms_in_api_cancel_paypal_subscription_upgrade_action( $user_id, $subscription_plan_id, $action, $confirmation, $error ) {

    if( !in_array( $action, array( 'upgrade', 'renew' ) ) )
        return;

    // Get payment_profile_id
    $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $subscription_plan_id );

    // Get failed attempts
    $api_failed_attempts = get_option( 'pms_api_failed_attempts', array() );

    if( !is_array( $api_failed_attempts ) )
        $api_failed_attempts = array();

    // If all is good clear the schedule
    if( $confirmation && is_null($error) ) {

        // Removed information
        if( isset( $api_failed_attempts[$user_id][$subscription_plan_id] ) )
            unset( $api_failed_attempts[$user_id][$subscription_plan_id] );

        // Clear schedule if it exists
        if( wp_get_schedule( 'pms_api_cancel_paypal_subscription', array( $user_id, $subscription_plan_id, $action ) ) )
            wp_clear_scheduled_hook( 'pms_api_cancel_paypal_subscription', array( $user_id, $subscription_plan_id, $action ) );

        update_option( 'pms_api_failed_attempts', $api_failed_attempts, false );

        do_action( 'pms_api_cancel_paypal_subscription_upgrade_successful', $user_id, $subscription_plan_id, $action, $confirmation, $error );

    } else {

        // Add the retry to the list
        $api_failed_attempts[$user_id][$subscription_plan_id]['retries'][] = array(
            'time'  => time(),
            'error' => $error
        );

        // Increment retry count
        if( !isset($api_failed_attempts[$user_id][$subscription_plan_id]['retry_count']) )
            $api_failed_attempts[$user_id][$subscription_plan_id]['retry_count'] = 1;
        else
            $api_failed_attempts[$user_id][$subscription_plan_id]['retry_count']++;

        // Add the payment profile id
        if( !isset($api_failed_attempts[$user_id][$subscription_plan_id]['payment_profile_id']) )
            $api_failed_attempts[$user_id][$subscription_plan_id]['payment_profile_id'] = $payment_profile_id;

        update_option( 'pms_api_failed_attempts', $api_failed_attempts, false );


        do_action( 'pms_api_cancel_paypal_subscription_upgrade_unsuccessful', $user_id, $subscription_plan_id, $action, $confirmation, $error );

    }

}
add_action( 'pms_api_cancel_paypal_subscription_before_return', 'pms_in_api_cancel_paypal_subscription_upgrade_action', 10, 5 );


/*
 * Hooks to 'pms_confirm_cancel_subscription' from PMS to change the default value provided
 * Makes an api call to PayPal to cancel the subscription, if is successful returns returns true,
 * but if not returns an array with 'error'
 *
 * @param $confirmation
 * @param int $user_id
 * @param int $subscription_plan_id
 *
 * @return mixed    - bool true if successful, array if not
 *
 */
function pms_in_ppsrp_confirm_cancel_subscription( $confirmation, $user_id, $subscription_plan_id ) {

    // Get payment_profile_id
    $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $subscription_plan_id );

    if( !pms_is_paypal_payment_profile_id( $payment_profile_id ) )
        return $confirmation;

    return pms_in_api_cancel_paypal_subscription( $payment_profile_id, 'cancel', 'Subscription canceled by user from [pms-account].' );

}
add_filter( 'pms_confirm_cancel_subscription', 'pms_in_ppsrp_confirm_cancel_subscription', 10, 3 );


/*
 * Hide the Renew subscription button from the account shortcode for each
 * member subscription that has a payment profile id saved and is not canceled or expired
 *
 * @param $output               - the current output for the renew button
 * @param $subscription_plan
 * @param $member_subscription
 * @param $user_id              - the member user id
 *
 * @return string
 *
 */
function pms_in_ppsrp_output_subscription_plan_action_renewal( $output, $subscription_plan, $member_subscription, $user_id ) {
    // If subscription is Canceled or Expired, show the renew button
    if ( in_array( $member_subscription['status'], array( 'canceled', 'expired' ) ) )
        return $output;

    // Check whether the subscription is a one time payment with trial (that is non recurring and renewal can be displayed)
    $payment_type = pms_get_member_subscription_meta( $member_subscription['id'], 'pms_payment_type', true );
    if( $payment_type == 'one_time_payment' )
        return $output;

    // Get payment_profile_id
    $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $member_subscription['subscription_plan_id'] );

    if( $payment_profile_id )
        return '';

    return $output;
}
add_filter( 'pms_output_subscription_plan_action_renewal', 'pms_in_ppsrp_output_subscription_plan_action_renewal', 10, 4 );

add_action( 'pms_checkout_after_payment_is_processed', 'pms_in_ppsrp_cancel_subscription_before_subscription_actions', 999, 3 );
function pms_in_ppsrp_cancel_subscription_before_subscription_actions( $payment_response, $subscription, $form_location ) {

    if( !$payment_response )
        return;

    if( !in_array( $form_location, array( 'renew_subscription', 'change_subscription', 'downgrade_subscription', 'upgrade_subscription' ) ) )
        return;

    if( empty( $subscription ) || empty( $subscription->user_id ) )
        return;

    $user_id              = $subscription->user_id;
    $subscription_plan_id = $subscription->subscription_plan_id;

    if ( $user_id == 0 || empty( $subscription_plan_id ) )
        return;

    $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $subscription_plan_id );

    if( empty( $payment_profile_id ) || !pms_is_paypal_payment_profile_id( $payment_profile_id ) )
        return;

    $action = 'renew';

    if( $form_location == 'change_subscription' )
        $action = 'change';
    elseif( $form_location == 'downgrade_subscription' )
        $action = 'downgrade';
    elseif( $form_location == 'upgrade_subscription' )
        $action = 'upgrade';

    $note = 'Subscription canceled because user manually renewed his subscription.';

    if( $action == 'change' )
        $note = 'Subscription canceled because user changed his subscription.';
    elseif( $action == 'downgrade' )
        $note = 'Subscription canceled because user downgraded his subscription.';
    elseif( $action == 'upgrade' )
        $note = 'Subscription canceled because user upgraded his subscription.';
        
    // Execute a profile cancellation api call to PayPal
    $cancel_result = pms_in_api_cancel_paypal_subscription( $payment_profile_id, $action, $note );

    // If something went wrong repeat cancellation api call to PayPal every hour until the subscription gets cancelled successfully
    if( isset( $cancel_result['error'] ) && wp_get_schedule( 'pms_api_cancel_paypal_subscription', array( $user_id, $subscription_plan_id, 'renew' ) ) == false && pms_get_paypal_api_credentials() != false )
        wp_schedule_event( time() + 60 * 60, 'hourly', 'pms_api_cancel_paypal_subscription', array( $user_id, $subscription_plan_id, 'renew' ) );
}

/**
 * Cancel PayPal subscription when an admin deletes the subscription from the back-end
 * @param  int   $subscription_id ID of the subscription that was just deleted
 * @param  array $data            Subscription data before deletion
 * @return void
 */
function pms_in_ppsrp_cancel_subscription_on_admin_deletion( $subscription_id, $data ){

    if( !is_admin() )
        return;

    if( empty( $data['payment_profile_id'] ) || !pms_is_paypal_payment_profile_id( $data['payment_profile_id'] ) )
        return;

    // Execute a profile cancellation api call to PayPal
    pms_in_api_cancel_paypal_subscription( $data['payment_profile_id'], 'cancel', 'Subscription canceled because an admin deleted the members subscription.' );

}
add_action( 'pms_member_subscription_delete', 'pms_in_ppsrp_cancel_subscription_on_admin_deletion', 10, 2 );

/**
 * Cancel PayPal subscription when the status is changed to Canceled or Abandoned while in the back-end interface
 * This usually means the user was deleted from the website, but it could also mean an admin changed
 * the status to Canceled or Abandoned from the back-end interface
 *
 * @param  int   $subscription_id ID of the subscription that was just edited
 * @param  array $data            Subscription data that was changed
 * @param  array $old_data        Old Subscription data
 * @return void
 */
function pms_in_ppsrp_cancel_subscription_on_api_subscription_cancelation( $id, $data, $old_data ){

    if( !is_admin() )
        return;

    if( empty( $old_data['payment_profile_id'] ) || !pms_is_paypal_payment_profile_id( $old_data['payment_profile_id'] ) )
        return;

    if( !empty( $data['status'] ) && ( $data['status'] == 'canceled' ||  $data['status'] == 'abandoned' ) && $data['status'] != $old_data )
        pms_in_api_cancel_paypal_subscription( $old_data['payment_profile_id'], 'cancel', 'Subscription canceled because an admin deleted the user from the website.' );

}
add_action( 'pms_member_subscription_update', 'pms_in_ppsrp_cancel_subscription_on_api_subscription_cancelation', 20, 3 );

add_filter( 'pms_subscription_logs_system_error_messages', 'pms_in_paypal_add_subscription_log_messages', 20, 2 );
function pms_in_paypal_add_subscription_log_messages( $message, $log ){
    if( empty( $log ) )
        return $message;

    switch ( $log['type'] ) {
        case 'paypal_subscription_setup':
            $message = __( 'Subscription setup successfully with PayPal.', 'paid-member-subscriptions' );
            break;
    }

    return $message;

}

function pms_in_ppsrp_get_subscription_by_payment_profile( $payment_profile_id ) {

    global $wpdb;

    $result = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}pms_member_subscriptions WHERE payment_profile_id = '{$payment_profile_id}'" );

    if( !empty( $result ) )
        return pms_get_member_subscription( $result );

    return false;

}
