<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;


/**
 * Wrapper function that returns a discount code object
 *
 * @param mixed $id_or_post
 *
 * @return PMS_IN_Discount_Code
 *
 */
function pms_in_get_discount( $id_or_post ) {

    return new PMS_IN_Discount_Code( $id_or_post );

}


/**
 * Wrapper function that returns a discount code object by the
 * code provided, not by the id or the post
 *
 * @param string $code
 *
 * @return PMS_IN_Discount_Code | false
 *
 */
function pms_in_get_discount_by_code( $code = '' ) {

    if( empty( $code ) )
        return false;

    $code = sanitize_text_field( $code );

    $discount_codes = get_posts( array(
        'post_type'   => 'pms-discount-codes',
        'post_status' => 'any',
        'meta_key'    => 'pms_discount_code',
        'meta_value'  => $code
    ));

    if ( ! empty($discount_codes) && ( $discount_codes[0]->post_status == 'active') ) { // discount code exists and is active
        return new PMS_IN_Discount_Code( $discount_codes[0] );
    }

    return false;

}


/**
 * Calculates and returns the discounted amount for a given amount
 * and discount code
 *
 * @param int $amount
 * @param PMS_IN_Discount_Code $discount
 *
 * @return int
 *
 */
function pms_in_calculate_discounted_amount( $amount, $discount ) {

    if( ! is_a( $discount, 'PMS_IN_Discount_Code' ) )
        return $amount;

    // Filter the amount before applying the discount
    $amount = apply_filters( 'pms_dc_amount_before_calculating_discount', $amount );

    if ( $discount->type == 'percent' )
        $amount = $amount * ( 100 - (float)$discount->amount ) / 100;

    if ( $discount->type == 'fixed' )
        $amount = $amount - (float)$discount->amount;

    //If it's a negative amount make it zero
    if( $amount < 0 )
        $amount = 0;

    $amount = round( $amount, 2 );

    return $amount;

}

function pms_in_dc_get_discounted_subscriptions(){

    // First count number of rows, abort if > 500
    global $wpdb;

    $discounts_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE `post_type` = 'pms-discount-codes'" );    

    if( $discounts_count > 500 )
        return false;

    $discounts = new WP_Query( array(
        'post_type'      => 'pms-discount-codes',
        'fields'         => 'ids',
        'meta_key'       => 'pms_discount_status',
        'meta_value'     => 'active',
        'posts_per_page' => -1,
    ));

    $discounted_subscriptions = array();

    if( $discounts->have_posts() && !empty( $discounts->posts ) ){

        foreach( $discounts->posts as $discount_id ){

            $discount = pms_in_get_discount( $discount_id );

            // only take into account discounts that are valid to be used
            if( !empty( $discount->start_date ) && ( strtotime( $discount->start_date ) > time() ) )
                continue;

            if( !empty( $discount->expiration_date ) && ( strtotime( $discount->expiration_date ) <= time() ) )
                continue;

            if( !empty( $discount->max_uses ) && isset( $discount->uses ) && $discount->max_uses <= $discount->uses )
                continue;

            if ( !empty( $discount->max_uses_per_user ) && $discount->max_uses_per_user <= pms_in_dc_get_discount_uses_per_user( $discount->code ) )
                continue;

            $subscriptions = explode( ',', $discount->subscriptions );

            $discounted_subscriptions = array_unique( array_merge( $subscriptions, $discounted_subscriptions ) );

        }

    }

    return $discounted_subscriptions;

}


/**
 * Function that returns the success message and the billing amount when the discount was successfully applied
 *
 * @param string $code - The entered discount code
 * @param string $subscription - Subscription plan id
 * @param bool $user_checked_auto_renew - Whether or not the user checked the "Automatically renew subscription" checkbox
 * @param string $pwyw_price - The price entered by the user if the selected subscription has Pay What You Want pricing enabled
 * @return string
 */
function pms_in_dc_apply_discount_success_message( $code, $subscription, $user_checked_auto_renew, $pwyw_price = '') {

    if ( empty( $code ) || empty( $subscription ) )
        return;

    //Determine form location
    if( isset( $_REQUEST['pmstkn'] ) )
        $form_location = PMS_Form_Handler::get_request_form_location();
    elseif( isset( $_REQUEST['pmstkn_original'] ) )
        $form_location = PMS_Form_Handler::get_request_form_location( 'pmstkn_original' );

    //Get Discount object
    $discount = pms_in_get_discount_by_code( $code );

    // Get Subscription plan object
    $subscription_plan = pms_get_subscription_plan( $subscription );

    // Get Currency
    $currency = apply_filters( 'pms_dc_success_message_currency', pms_get_active_currency(), $subscription_plan, $form_location );

    // Check if subscription payment will be recurring
    $is_recurring = pms_in_dc_subscription_is_recurring( $subscription_plan, $user_checked_auto_renew );

    // If Pay What You Want pricing is enabled for this subscription plan, and the user entered a price, modify subscription price
    if ( $pwyw_price !== '' )
        $subscription_plan->price = (float)$pwyw_price;

    // Filter subscription plan price that is used
    $subscription_plan_price = apply_filters( 'pms_dc_success_message_plan_price', (float)$subscription_plan->price, $subscription_plan, $form_location );

    $initial_payment = $subscription_plan_price;

    // Take into account the Sign-up Fee as well
    if ( in_array( $form_location, apply_filters( 'pms_checkout_signup_fee_form_locations', array( 'register', 'new_subscription', 'retry_payment', 'register_email_confirmation', 'change_subscription', 'wppb_register' ) ) ) && !empty( $subscription_plan->sign_up_fee ) && pms_payment_gateways_support( pms_get_active_payment_gateways(), 'subscription_sign_up_fee' ) ) {
        // Check if there is a Free Trial period
        if ( !empty( $subscription_plan->trial_duration ) )
            $initial_payment = $subscription_plan->sign_up_fee;
        else
            $initial_payment += (float)$subscription_plan->sign_up_fee;
    }

    $initial_payment = pms_in_calculate_discounted_amount( $initial_payment, $discount );

    if ( $is_recurring ) {

        $recurring_payment = apply_filters( 'pms_dc_success_message_recurring_plan_price', (float)$subscription_plan->price, $subscription_plan, $form_location );

        // Check if we need to apply discount to recurring payments as well
        if ( !empty( $discount->recurring_payments ) )
            $recurring_payment = pms_in_calculate_discounted_amount( $recurring_payment, $discount );

    }

    if ( in_array( $form_location, array( 'register', 'new_subscription', 'retry_payment' ) ) && pms_payment_gateways_support( pms_get_active_payment_gateways(), 'subscription_free_trial' ) ) {

        if( $subscription_plan->trial_duration > 0) {
            switch ($subscription_plan->trial_duration_unit) {
                case 'day':
                    $trial_duration = sprintf( _n( '%s day', '%s days', $subscription_plan->trial_duration, 'paid-member-subscriptions' ), $subscription_plan->trial_duration );
                    break;
                case 'week':
                    $trial_duration = sprintf( _n( '%s week', '%s weeks', $subscription_plan->trial_duration, 'paid-member-subscriptions' ), $subscription_plan->trial_duration );
                    break;
                case 'month':
                    $trial_duration = sprintf( _n( '%s month', '%s months', $subscription_plan->trial_duration, 'paid-member-subscriptions' ), $subscription_plan->trial_duration );
                    break;
                case 'year':
                    $trial_duration = sprintf( _n( '%s year', '%s years', $subscription_plan->trial_duration, 'paid-member-subscriptions' ), $subscription_plan->trial_duration );
                    break;
            }

            // The case of a fixed period membership with trial expiration date after plan expiration date
            if( $subscription_plan->is_fixed_period_membership() && strtotime( $subscription_plan->get_expiration_date() ) < strtotime( '+' . $trial_duration ) ){
                $days_difference = ( strtotime( $subscription_plan->get_expiration_date() ) - strtotime( 'today' ) ) / 86400;
                $trial_duration = sprintf( _n( '%s day', '%s days', $days_difference, 'paid-member-subscriptions' ), $days_difference );
            }
        }
    }

    if( !$subscription_plan->is_fixed_period_membership() && $subscription_plan->duration > 0 ) {

        switch ($subscription_plan->duration_unit) {
            case 'day':
                $duration = sprintf( _n( '%s day', '%s days', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                break;
            case 'week':
                $duration = sprintf( _n( '%s week', '%s weeks', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                break;
            case 'month':
                $duration = sprintf( _n( '%s month', '%s months', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                break;
            case 'year':
                $duration = sprintf( _n( '%s year', '%s years', $subscription_plan->duration, 'paid-member-subscriptions' ), $subscription_plan->duration );
                break;
        }
    }

    if( $subscription_plan->is_fixed_period_membership() ){
        if( in_array( $form_location, array( 'renew_subscription' ) ) )
            $time = '+ 1 year';
        else
            $time = '';
        $duration = sprintf( __( 'until %s' , 'paid-member-subscriptions' ), date_i18n( get_option( 'date_format' ), strtotime( $subscription_plan->get_expiration_date() . $time ) ) );
    }

    // Set currency position according to the PMS Settings page
    $initial_payment_price = pms_format_price( $initial_payment, $currency );
    $after_trial_payment   = pms_format_price( $subscription_plan_price, $currency );

    // If both trial and sign-up fees are added, add discount to both initial payment and after trial payment
    if( !empty( $trial_duration ) && !empty( $subscription_plan->sign_up_fee ) )
        $after_trial_payment = pms_format_price( pms_in_calculate_discounted_amount( $subscription_plan_price, $discount ), $currency );

    /**
     * Start building the response
     */
    $response = __( 'Discount successfully applied! ', 'paid-member-subscriptions' );

    if ( $is_recurring && $recurring_payment != 0 ) {

        $recurring_payment_price = pms_format_price( $recurring_payment, $currency );

        if ( !empty( $trial_duration ) && empty( $subscription_plan->sign_up_fee ) ){
            if( $subscription_plan->is_fixed_period_membership() ){
                $response .= sprintf( __( 'Amount to be charged after %1$s is %2$s %3$s, then %4$s repeated yearly.', 'paid-member-subscriptions' ), $trial_duration, $initial_payment_price, $duration, $recurring_payment_price );
            } else{
                $response .= sprintf( __( 'Amount to be charged after %1$s is %2$s, then %3$s every %4$s.', 'paid-member-subscriptions' ), $trial_duration, $initial_payment_price, $recurring_payment_price, $duration );
            }
        }
        else if ( !empty( $trial_duration ) && !empty( $subscription_plan->sign_up_fee ) ){
            if( $subscription_plan->is_fixed_period_membership() ){
                $response .= sprintf( __( 'Amount to be charged now is %1$s, then after %2$s %3$s %4$s and repeated yearly.', 'paid-member-subscriptions' ), $initial_payment_price, $trial_duration, $recurring_payment_price, $duration );
            } else{
                $response .= sprintf( __( 'Amount to be charged now is %1$s, then after %2$s %3$s every %4$s.', 'paid-member-subscriptions' ), $initial_payment_price, $trial_duration, $recurring_payment_price, $duration );
            }
        }
        else {

            if( $initial_payment == $recurring_payment ){
                if( $subscription_plan->is_fixed_period_membership() ){
                    $response .= sprintf( __( 'Amount to be charged is %1$s %2$s and repeated yearly.', 'paid-member-subscriptions' ), $initial_payment_price, $duration );
                } else{
                    $response .= sprintf( __( 'Amount to be charged is %1$s every %2$s.', 'paid-member-subscriptions' ), $initial_payment_price, $duration );
                }
            } else {
                if( $subscription_plan->is_fixed_period_membership() ){
                    $response .= sprintf( __( 'Amount to be charged %1$s is %2$s, then %3$s yearly.', 'paid-member-subscriptions' ), $duration, $initial_payment_price, $recurring_payment_price );
                } else{
                    $response .= sprintf( __( 'Amount to be charged now is %1$s, then %2$s every %3$s.', 'paid-member-subscriptions' ), $initial_payment_price, $recurring_payment_price, $duration );
                }
            }
        }

    } else {

        if ( !empty( $trial_duration ) && empty( $subscription_plan->sign_up_fee ) )
            $response .= sprintf( __( 'Amount to be charged after %1$s is %2$s.', 'paid-member-subscriptions' ), $trial_duration, $initial_payment_price );
        else if ( !empty( $trial_duration ) && !empty( $subscription_plan->sign_up_fee ) )
            $response .= sprintf( __( 'Amount to be charged now is %1$s, then after %2$s %3$s.', 'paid-member-subscriptions' ), $initial_payment_price, $trial_duration, $after_trial_payment );
        else
            $response .= sprintf( __( 'Amount to be charged is %s.', 'paid-member-subscriptions' ), $initial_payment_price );

    }

    /**
     * Filter discount applied successfully message.
     *
     * @param string $code The entered discount code
     * @param string $subscription The subscription plan id
     * @param string $pwyw_price The Pay What You Want price entered by the user, if enabled
     */
    return apply_filters('pms_dc_apply_discount_success_message', $response, $code, $subscription, $pwyw_price, $is_recurring, $initial_payment, isset( $recurring_payment ) ? $recurring_payment : '' );
}



/**
 * Determines whether the discount
 *
 * @param string $code
 * @param int    $subscription_plan_id
 * @param bool   $user_checked_auto_renew - Whether or not the user checked the "Automatically renew subscription" checkbox
 * @param string $pwyw_price - Is set when Pay What You Want pricing is enabled and the user enters an amount
 *
 * @return bool
 *
 */
function pms_in_dc_check_is_full_discount( $code = '', $subscription_plan_id = 0, $user_checked_auto_renew = false, $pwyw_price = '' ) {

    if( empty( $code ) )
        return false;

    if( empty( $subscription_plan_id ) )
        return false;

    $discount_code     = pms_in_get_discount_by_code( $code );
    $subscription_plan = pms_get_subscription_plan( $subscription_plan_id );

    $checkout_is_recurring = pms_in_dc_subscription_is_recurring( $subscription_plan, $user_checked_auto_renew );

    // If Pay What You Want price is set, modify subscription price
    if ( $pwyw_price !== '' ){
        $subscription_plan->price = (float)$pwyw_price;
    }

    $discounted_amount = pms_in_calculate_discounted_amount( $subscription_plan->price, $discount_code );

    $is_full_discount = false;

    // If the checkout creates a subscription with recurring payments
    if( $checkout_is_recurring ) {

        if( ! empty( $discount_code->recurring_payments ) ) {

            if( $discounted_amount == 0 )
                $is_full_discount = true;

        }

    } else {

        if( $discounted_amount == 0 && empty( $subscription_plan->sign_up_fee ) )
            $is_full_discount = true;

    }

    return apply_filters( 'pms_dc_is_full_discount', $is_full_discount, $discount_code, $subscription_plan, $checkout_is_recurring );

}


/**
 * Function that checks if a given subscription is recurring, taking into consideration also if the user checked the "Automatically renew subscription" checkbox
 *
 * @param PMS_Subscription_Plan $subscription_plan - The subscription plan object
 * @param bool                  $user_checked_auto_renew - Whether or not the user checked the "Automatically renew subscription" checkbox
 *
 * @return bool
 *
 */
function pms_in_dc_subscription_is_recurring( $subscription_plan, $user_checked_auto_renew ){

    // Subscription plan is never ending
    if( !$subscription_plan->is_fixed_period_membership() && empty( $subscription_plan->duration ) )
        return false;

    // Subscription plan is fixed and option allow renew is not checked
    if( $subscription_plan->is_fixed_period_membership() && !$subscription_plan->fixed_period_renewal_allowed() )
        return false;

    // Subscription plan has options: always recurring
    if( $subscription_plan->recurring == 2 )
        return true;

    // Subscription plan has option: never recurring
    if( $subscription_plan->recurring == 3 )
        return false;

    // Subscription plan has options: customer opts in
    if( $subscription_plan->recurring == 1 )
       return $user_checked_auto_renew;


    // Subscription plan has option: settings default
    if( empty( $subscription_plan->recurring ) ) {

        if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 ) {
            $settings           = get_option( 'pms_settings', array() );
            $settings_recurring = empty( $settings['payments']['recurring'] ) ? 0 : (int)$settings['payments']['recurring'];
        } else {
            $settings           = get_option( 'pms_payments_settings', array() );
            $settings_recurring = empty( $settings['recurring'] ) ? 0 : (int)$settings['recurring'];
        }

        if( empty( $settings_recurring ) )
            return false;

        // Settings has option: always recurring
        if( $settings_recurring == 2 )
            return true;

        // Settings has option: never recurring
        if( $settings_recurring == 3 )
            return false;

        // Settings has option: customer opts in
        if( $settings_recurring == 1 )
            return $user_checked_auto_renew;

    }

}

/**
 * Function that checks for and returns the discount errors
 * @param string $code The discount code entered
 * @param string $subscription The subscription plan ID
 * @return string
 */
function pms_in_dc_get_discount_error( $code, $subscription ){

    if ( !empty($code) ) {
        // Get all the discount data
        $discount_meta = PMS_IN_Discount_Codes_Meta_Box::get_discount_meta_by_code( $code );

        if ( !empty($discount_meta) ) { //discount is active

            //Determine form location
            if( isset( $_REQUEST['pmstkn'] ) )
                $form_location = PMS_Form_Handler::get_request_form_location();
            elseif( isset( $_REQUEST['pmstkn_original'] ) )
                $form_location = PMS_Form_Handler::get_request_form_location( 'pmstkn_original' );

            $discount_subscriptions = array();
            if (!empty($discount_meta['pms_discount_subscriptions']))
                $discount_subscriptions = explode( ',' , $discount_meta['pms_discount_subscriptions'][0] );

            if ( empty($subscription) )
                return __('Please select a subscription plan and try again.', 'paid-member-subscriptions');

            if ( !in_array( $subscription, $discount_subscriptions ) || ( !empty( $discount_meta['pms_discount_new_users_only'][0] ) && in_array( $form_location, array( 'renew_subscription' ) ) ) ) {
                //discount not valid for this subscription
                return __('The discount is not valid for this subscription plan.', 'paid-member-subscriptions');
            }

            if ( !empty($discount_meta['pms_discount_start_date'][0]) && (strtotime($discount_meta['pms_discount_start_date'][0]) > time()) ) {
                //start date is in the future
                return __('The discount code you entered is not active yet.', 'paid-member-subscriptions');
            }

            if ( !empty($discount_meta['pms_discount_expiration_date'][0]) && (strtotime($discount_meta['pms_discount_expiration_date'][0]) <= time()) ) {
                //expiration date has passed
                return __('The discount code you entered has expired.', 'paid-member-subscriptions');
            }

            if ( !empty($discount_meta['pms_discount_max_uses'][0]) && isset($discount_meta['pms_discount_uses'][0]) && ( $discount_meta['pms_discount_max_uses'][0] <= $discount_meta['pms_discount_uses'][0]) ) {
                //all uses for this discount have been consumed
                return __('The discount code maximum uses have been reached.', 'paid-member-subscriptions');
            }

            if ( !isset($discount_meta['pms_discount_max_uses_per_user'][0]) ) {
                // set default value for discounts created before this option was added
                $discount_meta['pms_discount_max_uses_per_user'][0] = 1;
            }

            if ( $discount_meta['pms_discount_max_uses_per_user'][0] != 0 && $discount_meta['pms_discount_max_uses_per_user'][0] <= pms_in_dc_get_discount_uses_per_user($code) ) {
                // the maximum discount uses for this user have been reached
                return __('The discount code maximum uses for this user have been reached.', 'paid-member-subscriptions');
            }

            /**
             * Hook for adding custom validation for discount codes
             *
             * @param string $error Error message that will be returned
             * @param string $code The discount code entered
             * @param string $subscription The subscription plan ID
             * @param array $discount_meta The discount code details
             * @return string
             */
            $extra_validations = apply_filters( 'pms_dc_get_discount_error', '', $code, $subscription, $discount_meta );

            if ( !empty( $extra_validations ) )
                return $extra_validations;

        }
        else {
            // Entered discount code was not found or is inactive
            return __('The discount code you entered is invalid.', 'paid-member-subscriptions');
            }
    }
    return '';
}

/**
 * Returns the number of times the current user has used this discount code
 *
 */
function pms_in_dc_get_discount_uses_per_user( $code ){
    $user_discount_uses = 0;
    $user_id = 0;

    // When trying to use the discount more than once, the user should be logged in ( for renewal, retrying the payment, upgrading, buying a different subscription )
    if ( is_user_logged_in() ){
        $user_id = get_current_user_id();
    }

    if ( !empty($user_id) ){

        $meta = get_user_meta( $user_id, 'pms_discount_uses_per_user_'.$code, true );

        if ( !empty( $meta ) )
            $user_discount_uses = (int)$meta;

    }

    return $user_discount_uses;
}

function pms_in_are_active_discounts_defined(){

    $pms_discounts_are_defined = get_transient( 'pms_discounts_are_defined_data' );

    if ( false === $pms_discounts_are_defined ) {

        global $wpdb;

        $pms_discounts_are_defined = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts as a INNER JOIN $wpdb->postmeta as b ON a.ID = b.post_id WHERE a.post_type = 'pms-discount-codes' AND b.meta_key = 'pms_discount_status' AND b.meta_value = 'active'" );

        set_transient( 'pms_discounts_are_defined_data', $pms_discounts_are_defined, DAY_IN_SECONDS );

    }

    return $pms_discounts_are_defined;

}
