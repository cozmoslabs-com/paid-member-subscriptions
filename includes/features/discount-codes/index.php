<?php
/**
 * Paid Member Subscriptions - Discount Codes Add-on
 * License: GPL2
 *
 * == Copyright ==
 * Copyright 2018 Cozmoslabs (www.cozmoslabs.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

define( 'PMS_IN_DC_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'PMS_IN_DC_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

/**
 * Include the files needed
 *
 */

// Discount code object class
if( file_exists( PMS_IN_DC_PLUGIN_DIR_PATH . 'includes/functions-discount.php' ) )
    include_once( PMS_IN_DC_PLUGIN_DIR_PATH  . 'includes/functions-discount.php' );

if( file_exists( PMS_IN_DC_PLUGIN_DIR_PATH . 'includes/class-discount-code.php' ) )
    include_once( PMS_IN_DC_PLUGIN_DIR_PATH . 'includes/class-discount-code.php' );

// Discount Codes custom post type
if( file_exists( PMS_IN_DC_PLUGIN_DIR_PATH . 'includes/class-admin-discount-codes.php' ) )
    include_once( PMS_IN_DC_PLUGIN_DIR_PATH. 'includes/class-admin-discount-codes.php' );

// Bulk Add Discount Codes
if( file_exists( PMS_IN_DC_PLUGIN_DIR_PATH . 'includes/class-admin-discount-codes-bulk-add.php' ) )
    include_once( PMS_IN_DC_PLUGIN_DIR_PATH. 'includes/class-admin-discount-codes-bulk-add.php' );

// Meta box for discount codes cpt
if( file_exists( PMS_IN_DC_PLUGIN_DIR_PATH . 'includes/class-metabox-discount-codes-details.php' ) )
    include_once( PMS_IN_DC_PLUGIN_DIR_PATH . 'includes/class-metabox-discount-codes-details.php' );


/**
 * Adding Admin scripts
 *
 */
function pms_in_dc_add_admin_scripts(){

    // If the file exists where it should be, enqueue it
    if( file_exists( PMS_IN_DC_PLUGIN_DIR_PATH . 'assets/js/cpt-discount-codes.js' ) )
        wp_enqueue_script( 'pms-discount-codes-js', PMS_IN_DC_PLUGIN_DIR_URL . 'assets/js/cpt-discount-codes.js', array( 'jquery','jquery-ui-datepicker' ), PMS_VERSION );

    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-style', PMS_PLUGIN_DIR_URL . 'assets/css/admin/jquery-ui.min.css', array(), PMS_VERSION );

    // add back-end css for Discount Codes cpt
    wp_enqueue_style( 'pms-dc-style-back-end', PMS_IN_DC_PLUGIN_DIR_URL . 'assets/css/style-back-end.css' );

}
add_action('pms_cpt_enqueue_admin_scripts_pms-discount-codes','pms_in_dc_add_admin_scripts');


/**
 * Adding Front-end scripts
 *
 */
function pms_in_dc_add_frontend_scripts(){
    
    if( !pms_should_load_scripts() )
        return;

    if( file_exists( PMS_IN_DC_PLUGIN_DIR_PATH . 'assets/js/frontend-discount-code.js' ) ) {

        wp_enqueue_script('pms-frontend-discount-code-js', PMS_IN_DC_PLUGIN_DIR_URL . 'assets/js/frontend-discount-code.js', array('jquery'), PMS_VERSION );

        $js_data = array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
        );

        $discounted_subscriptions = pms_in_dc_get_discounted_subscriptions();

        if ( $discounted_subscriptions != false ){
            $js_data['discounted_subscriptions'] = json_encode( $discounted_subscriptions, JSON_FORCE_OBJECT );
        }

        wp_localize_script( 'pms-frontend-discount-code-js', 'pms_discount_object', $js_data );

    }

    // add front-end CSS for discount code box
    if ( file_exists( PMS_IN_DC_PLUGIN_DIR_PATH . 'assets/css/style-front-end.css') ) {
        wp_enqueue_style('pms-dc-style-front-end', PMS_IN_DC_PLUGIN_DIR_URL. 'assets/css/style-front-end.css' );
    }


}
add_action('wp_footer','pms_in_dc_add_frontend_scripts');


/**
 * Positioning the Discount Codes label under Payments in PMS submenu
 *
 */
function pms_in_dc_submenu_order( $menu_order){
    global $submenu;

    if ( isset($submenu['paid-member-subscriptions']) ) {

        foreach ($submenu['paid-member-subscriptions'] as $key => $value) {
            if ($value[2] == 'edit.php?post_type=pms-discount-codes') $discounts_key = $key;
            if ($value[2] == 'pms-payments-page') $payments_key = $key;
        }

        if (isset($payments_key) && isset($discounts_key)) {
            $discounts_value = $submenu['paid-member-subscriptions'][$discounts_key];

            if ($payments_key > $discounts_key) $payments_key--;
            unset($submenu['paid-member-subscriptions'][$discounts_key]);

            $array1 = array_slice($submenu['paid-member-subscriptions'], 0, $payments_key);
            $array2 = array_slice($submenu['paid-member-subscriptions'], $payments_key);
            array_push($array1, $discounts_value);

            $submenu['paid-member-subscriptions'] = array_merge($array1, $array2);

        }
    }

    return $menu_order;

}
add_filter('custom_menu_order','pms_in_dc_submenu_order');


/**
 * Output discount code box on the front-end
 *
 * */
function pms_in_dc_output_discount_box( $output, $include, $exclude_id_group, $member, $pms_settings, $subscription_plans ){

    // Don't display the discount field on account pages
    if( !empty( $member ) )
        return $output;

    if( empty( $subscription_plans ) )
        return $output;

    if( !( pms_in_are_active_discounts_defined() > 0 ) )
        return $output;
    
    // Calculate the total price of the subscription plans
    $total_price = 0;
    foreach( $subscription_plans as $subscription_plan ) {
        $total_price += (int)$subscription_plan->price;
        $total_price += (int)$subscription_plan->sign_up_fee;
    }


    $discount_code_value = !empty( $_POST['discount_code'] ) ? sanitize_text_field( $_POST['discount_code'] ) : '';

    if( !empty( $_GET['discount_code'] ) ){
        
        $discount_code = pms_in_get_discount_by_code( sanitize_text_field( $_GET['discount_code'] ) );

        if( !empty( $discount_code->id ) )
            $discount_code_value = $discount_code->code;

    }

    $discount_code_value = apply_filters( 'pms_discount_code_field_value', $discount_code_value );

    // Return the discount code field only if we have paid plans
    if( $total_price !== 0 ) {
        $discount_output  = '<div id="pms-subscription-plans-discount">';
        $discount_output .= '<label for="pms-subscription-plans-discount">' . apply_filters('pms_form_label_discount_code', __('Discount Code: ', 'paid-member-subscriptions')) . '</label>';
        $discount_output .= '<input id="pms_subscription_plans_discount_code" name="discount_code" placeholder="' . apply_filters( 'pms_form_input_placeholder_discount_code', __( 'Enter discount', 'paid-member-subscriptions' ) ) . '" type="text" value="' . esc_attr( $discount_code_value ) . '" />';
        $discount_output .= '<input id="pms-apply-discount" class="pms-submit button" type="submit" value="' . apply_filters( 'pms_form_submit_discount_code', __( 'Apply', 'paid-member-subscriptions' ) ) . '">';
        $discount_output .= '</span>';
        $discount_output .= '</div>';

        $message_output  = '<div id="pms-subscription-plans-discount-messages-wrapper">';
            $message_output .= '<div id="pms-subscription-plans-discount-messages" ' . (pms_errors()->get_error_message('discount_error') ? 'class="pms-discount-error"' : '') . '>';
            $message_output .= pms_errors()->get_error_message('discount_error');
            $message_output .= '</div>';

            $message_output .= '<div id="pms-subscription-plans-discount-messages-loading">';
            $message_output .= __( 'Applying discount code. Please wait...', 'paid-member-subscriptions' );
            $message_output .= '</div>';
        $message_output .= '</div>';

        $output .= $discount_output . $message_output;
    }

    return $output;
}
add_filter('pms_output_subscription_plans', 'pms_in_dc_output_discount_box', 25, 6 );


/**
 * Function that returns the front-end discount code errors or success message
 *
 */
function pms_in_dc_output_apply_discount_message() {

    $response     = array(); // initialize response
    $code         = '';
    $subscription = '';
    $user_checked_auto_renew = false;
    $pwyw_price = '';

    // Clean-up and setup data
    if( !empty( $_POST['code'] ) )
        $code = sanitize_text_field( $_POST['code'] );

    if( !empty( $_POST['subscription'] ) )
        $subscription = absint( $_POST['subscription'] );

    // User checked the auto-renew checkbox
    if( !empty( $_POST['recurring'] ) )
        $user_checked_auto_renew = true;

    // Pay What You Want Pricing is enabled for the selected plan
    if ( !empty( $_POST['pwyw_price'] ) )
        $pwyw_price = sanitize_text_field( $_POST['pwyw_price'] );

    // Assemble the response
    if ( !empty( $code ) && !empty( $subscription ) ) {

        $error = pms_in_dc_get_discount_error( $code, $subscription );

        // Setup user message
        if( ! empty( $error ) )
            $response['error']['message'] = $error;
        else
            $response['success']['message'] = pms_in_dc_apply_discount_success_message( $code, $subscription, $user_checked_auto_renew, $pwyw_price );

        // Determine wether the discount code is a partial discount or a full discount
        $response['is_full_discount'] = pms_in_dc_check_is_full_discount( $code, $subscription, $user_checked_auto_renew, $pwyw_price );

        // Add new price to response
        $plan          = pms_get_subscription_plan( $subscription );
        $form_location = PMS_Form_Handler::get_request_form_location( 'pmstkn_original' );
        $amount        = (float)$plan->price;

        if ( in_array( $form_location, apply_filters( 'pms_checkout_signup_fee_form_locations', array( 'register', 'new_subscription', 'retry_payment', 'register_email_confirmation', 'change_subscription', 'wppb_register' ) ) ) && !empty( $plan->sign_up_fee ) && pms_payment_gateways_support( pms_get_active_payment_gateways(), 'subscription_sign_up_fee' ) ) {
            // Check if there is a Free Trial period
            if ( !empty( $plan->trial_duration ) )
                $amount = $plan->sign_up_fee;
            else
                $amount += (float)$plan->sign_up_fee;
        }

        // Cache the unfiltered value which could be a future payment done by the user 
        $response['original_discounted_price'] = pms_in_calculate_discounted_amount( $amount, pms_in_get_discount_by_code( $code ) );

        $amount = apply_filters( 'pms_dc_output_apply_discount_message_amount', $amount );

        $response['discounted_price'] = pms_in_calculate_discounted_amount( $amount, pms_in_get_discount_by_code( $code ) );

        $discount_meta = PMS_IN_Discount_Codes_Meta_Box::get_discount_meta_by_code( $code );
        
        if( !empty( $discount_meta ) )
            $response['recurring_payments'] = $discount_meta['pms_discount_recurring_payments'][0] == 'checked' ? 1 : 0;

        wp_send_json($response);

    }

}
add_action( 'wp_ajax_pms_discount_code', 'pms_in_dc_output_apply_discount_message' );
add_action( 'wp_ajax_nopriv_pms_discount_code', 'pms_in_dc_output_apply_discount_message' );

/**
 * Validates the discount code on the different form
 *
 */
function pms_in_dc_add_form_discount_error(){

    if ( !empty($_POST['discount_code']) && !empty($_POST['subscription_plans']) ) {

        $code                 = sanitize_text_field( $_POST['discount_code'] );
        $subscription_plan_id = absint( $_POST['subscription_plans'] );

        $error = pms_in_dc_get_discount_error( $code, $subscription_plan_id );

        if ( !empty($error) ) {
            pms_errors()->add('discount_error', $error);
        }
    }
}
add_action( 'pms_register_form_validation',                   'pms_in_dc_add_form_discount_error' );
add_action( 'pms_new_subscription_form_validation',           'pms_in_dc_add_form_discount_error' );
add_action( 'pms_upgrade_subscription_form_validation',       'pms_in_dc_add_form_discount_error' );
add_action( 'pms_renew_subscription_form_validation',         'pms_in_dc_add_form_discount_error' );
add_action( 'pms_change_subscription_form_validation',        'pms_in_dc_add_form_discount_error' );
add_action( 'pms_retry_payment_subscription_form_validation', 'pms_in_dc_add_form_discount_error' );
add_action( 'pms_ec_process_checkout_validations',            'pms_in_dc_add_form_discount_error' );

function pms_in_dc_add_pbform_discount_error( $message ) {

    if ( !empty($_POST['discount_code']) && !empty($_POST['subscription_plans']) ) {
        $code                 = sanitize_text_field( $_POST['discount_code'] );
        $subscription_plan_id = absint( $_POST['subscription_plans'] );

        $error = pms_in_dc_get_discount_error( $code, $subscription_plan_id );

        if ( !empty( $error ) )
            $message = $error;
    }

    return $message;

}
add_filter( 'wppb_check_form_field_subscription-plans', 'pms_in_dc_add_pbform_discount_error', 20, 4 );

/**
 * Checks to see if the checkout has a full discount applied and handles the validations
 * for this case.
 *
 * In case there is a full discount the "pay_gate" element is not sent in the $_POST. This case is similar
 * for free plans. If the "pay_gate" elements is missing Paid Member Subscriptions does some validations
 * to see if the selected subscription plan is free. If it is not, it adds some errors.
 *
 * In the case of a full discount the errors will be present, because this validations is done very early in the
 * execution. We will remove this errors if the discount is a full one.
 *
 */
function pms_in_dc_process_checkout_validation_payment_gateway() {

    if( ! empty( $_POST['pay_gate'] ) )
        return;

    if ( empty( $_POST['discount_code'] ) )
        return;

    $payment_gateway_errors = pms_errors()->get_error_message( 'payment_gateway' );

    if( empty( $payment_gateway_errors ) )
        return;

    $code          = sanitize_text_field( $_POST['discount_code'] );
    $discount_code = pms_in_get_discount_by_code( $code );

    if( false == $discount_code )
        return;

    // User checked auto-renew checkbox on checkout
    $user_checked_auto_renew = ( ! empty( $_POST['recurring'] ) ? true : false );

    // Get selected subscription plan id
    $subscription_plan_id    = ( ! empty( $_POST['subscription_plans'] ) ? (int)$_POST['subscription_plans'] : 0 );

    // Check if is full discount applied
    $is_full_discount = pms_in_dc_check_is_full_discount( $code, $subscription_plan_id, $user_checked_auto_renew );

    // If the discount is full, remove the errors for the payment gateways
    if( $is_full_discount )
        pms_errors()->remove( 'payment_gateway' );

}
add_action( 'pms_process_checkout_validations', 'pms_in_dc_process_checkout_validation_payment_gateway' );

/**
 * Function that returns payment data after applying the discount code (if there are no discount errors)
 *
 *
 */
function pms_in_dc_register_payment_data_after_discount( $payment_data, $payments_settings ) {

    if ( empty( $_POST['discount_code'] ) )
        return $payment_data;

    $discount = pms_in_get_discount_by_code( sanitize_text_field( $_POST['discount_code'] ) );

    if( false == $discount )
        return $payment_data;

    if( empty( $_POST['subscription_plans'] ) )
        return $payment_data;

    $subscription_plan_id = (int)$_POST['subscription_plans'];

    $error = pms_in_dc_get_discount_error( $discount->code, $subscription_plan_id );

    if ( !empty( $error ) )
        return $payment_data;

    $payment_data['sign_up_amount'] = pms_in_calculate_discounted_amount( $payment_data['amount'], $discount );

    if( false == $payment_data['recurring'] )
        $payment_data['amount'] = $payment_data['sign_up_amount'];

    if( true == $payment_data['recurring'] && ! empty( $discount->recurring_payments ) )
        $payment_data['amount'] = $payment_data['sign_up_amount'];


    // Save corresponding discount code for the payment in the db
    if ( class_exists( 'PMS_Payment' ) ) {

        /**
         * Add the discount code to the payment_data
         *
         */
        $payment_data['discount_code'] = $discount->code;

        $payment = pms_get_payment(isset($payment_data['payment_id']) ? $payment_data['payment_id'] : 0);

        $update_args = array(
            'discount_code' => $discount->code
        );

        // Update payment amount if it was discounted
        if ( !is_null($payment_data['sign_up_amount']) ) {

            $update_args['amount'] = $payment_data['sign_up_amount'];
            $update_args['status'] = $payment_data['sign_up_amount'] == 0 ? 'completed' : $payment->status;

        }

        $payment->update( $update_args );

    }

    return $payment_data;

}
add_filter( 'pms_register_payment_data', 'pms_in_dc_register_payment_data_after_discount', 20, 2 ); //has a later execution so we can discount the Pay What You Want pricing as well

/**
 * Modifies the billing amount on the checkout subscription data to the discounted value
 *
 * @param array $subscription_data
 * @param array $checkout_data
 *
 * @return array
 *
 */
function pms_in_dc_modify_subscription_data_billing_amount( $subscription_data = array(), $checkout_data = array() ) {

    if ( empty( $_POST['discount_code'] ) )
        return $subscription_data;

    if( empty( $subscription_data ) )
        return array();

    if( ! $checkout_data['is_recurring'] )
        return $subscription_data;

    // Get discount
    $discount = pms_in_get_discount_by_code( sanitize_text_field( $_POST['discount_code'] ) );

    if( false == $discount )
        return $subscription_data;

    if( empty( $discount->recurring_payments ) )
        return $subscription_data;

    /**
     * If the subscription has a set billing amount, calculate the discounted price from it
     * and modify the billing amount with the discounted one
     *
     */
    if( ! empty( $subscription_data['payment_gateway'] ) && ! empty( $subscription_data['billing_amount'] ) ) {

        $discounted_amount = pms_in_calculate_discounted_amount( $subscription_data['billing_amount'], $discount );

        $subscription_data['billing_amount'] = $discounted_amount;

    /**
     * If the subscription does not have a billing amount set, calculate if based on the attached
     * subscription plan's price
     *
     */
    } else {

        $subscription_plan = pms_get_subscription_plan( $subscription_data['subscription_plan_id'] );

        $discounted_amount = pms_in_calculate_discounted_amount( $subscription_plan->price, $discount );

    }

    /**
     * If the recurring discounted amount is zero (full discount), it means basically that
     * no payments should be made for this subscription and it should be set as unlimited
     *
     */
    if( $discounted_amount == 0 ) {

        $subscription_data['expiration_date'] = '';
        $subscription_data['status']          = 'active';

    }

    return $subscription_data;

}
add_filter( 'pms_process_checkout_subscription_data', 'pms_in_dc_modify_subscription_data_billing_amount', 20, 2 ); //has a later execution so we can discount the Pay What You Want pricing as well

/**
 * Function that saves discount id inside _pms_member_subscriptionmeta table.
 * This is done when the discount code is to be applied only to the first payment of a recurring subscription with a free trial.
 * In this case we save the discount so we can apply it when the cron job triggers the first payment (after the free trial has ended)
 *
 * See 'pms_cron_process_member_subscriptions_payments'.
 *
 * @param object $subscription
 * @param  array $checkout_data
 */
function pms_in_dc_save_discount_inside_subscriptionmeta( $subscription, $checkout_data ){

    // check if subscription has free trial
    if ( isset( $_POST['discount_code'] ) && !empty( $subscription->trial_end ) ) {

        // Get discount
        $discount = pms_in_get_discount_by_code( sanitize_text_field( $_POST['discount_code'] ) );

        // Make sure discount doesn't apply to all recurring payment (just the first one)
        if( !empty( $discount ) && ( empty( $discount->recurring_payments ) || !$checkout_data['is_recurring'] ) && function_exists( 'pms_update_member_subscription_meta' ) ) {

            // Save the discount inside _pms_member_subscriptionmeta
            pms_update_member_subscription_meta($subscription->id, '_discount_code_id', $discount->id);

        }
    }
}
add_action( 'pms_after_inserting_subscription_data_inside_db', 'pms_in_dc_save_discount_inside_subscriptionmeta', 10, 2 );

/**
 * Function that applies the saved (non-recurring) discount to the first subscription payment generated by the cron job, after the free trial has ended
 *
 * @param  array $payment_data
 * @param object $subscription
 * return array $payment_data
 */
function pms_in_dc_modify_first_payment_data( $payment_data, $subscription ){

    //check if there is any discount saved for this subscription
    if ( function_exists( 'pms_get_member_subscription_meta' ) ){
        $discount_id = pms_get_member_subscription_meta( $subscription->id, '_discount_code_id', true);
    }

    // Get discount data
    $discount = '';
    if ( !empty($discount_id) ) {
        $discount = pms_in_get_discount( $discount_id );
    }

    if ( is_object( $discount ) ) {

        //Apply discount
        $discounted_amount = pms_in_calculate_discounted_amount( $payment_data['amount'], $discount );
        $payment_data['amount'] = $discounted_amount;

        //Remove discount from db, because it applies only to the first payment
        if ( function_exists( 'pms_delete_member_subscription_meta' ) )
            pms_delete_member_subscription_meta( $subscription->id, '_discount_code_id', $discount_id);

    }

    return $payment_data;
}
add_filter( 'pms_cron_process_member_subscriptions_payment_data' , 'pms_in_dc_modify_first_payment_data', 10, 2 );
add_filter( 'pms_paypal_process_member_subscriptions_payment_data', 'pms_in_dc_modify_first_payment_data', 10, 2 );


/**
 * Function that updates discount data after it has been used
 *
 *
 */
function pms_in_dc_update_discount_data_after_use( $payment_id, $data, $old_data ) {

    // Get discount code used for the payment
    if( !empty( $data['status'] ) && $data['status'] == 'completed' ) {
        if( !empty( $payment_id ) && function_exists( 'pms_get_payment' ) ) {
            $payment = pms_get_payment( $payment_id );
            $code    = $payment->discount_code;
        }
    }

    if ( !empty($code) ) { // the payment used a discount code

        $discount_meta = PMS_IN_Discount_Codes_Meta_Box::get_discount_meta_by_code( $code );

        if ( !empty($discount_meta) ) {  // the discount code exists

            if ( isset($discount_meta['pms_discount_uses'][0]) )
                $discount_meta['pms_discount_uses'][0]++;
            else
                $discount_meta['pms_discount_uses'][0] = 1;

            $discount_ID = PMS_IN_Discount_Codes_Meta_Box::get_discount_ID_by_code( $code );

            if ( !empty($discount_ID) ) {
                update_post_meta($discount_ID, 'pms_discount_uses', $discount_meta['pms_discount_uses'][0]);

                if( ! empty( $discount_meta['pms_discount_max_uses'][0] ) && $discount_meta['pms_discount_uses'][0] >= $discount_meta['pms_discount_max_uses'][0])
                    PMS_IN_Discount_Code::deactivate($discount_ID);
            }

            /**
             * Update (increment) discount uses for this user; they are stored inside the usermeta key 'pms_discount_uses_per_user_'.$code
             * */
            if ( !empty($payment->user_id) ) {

                $meta = get_user_meta($payment->user_id, 'pms_discount_uses_per_user_' . $code, true);

                $user_discount_uses = ( !empty( $meta ) ) ? (int)$meta : 0;
                update_user_meta( $payment->user_id, 'pms_discount_uses_per_user_'.$code, $user_discount_uses + 1 );

            }

        }
    }

}
add_action( 'pms_payment_update', 'pms_in_dc_update_discount_data_after_use', 10, 3 );


/**
 * Update Discount Code usage data when:
 * - Subscription Plan has Free Trial
 * - Stripe was used as payment gateway
 * - Discount Code used is valid
 *
 * @param $subscription
 * @param $form_location
 * @return void
 */
function pms_in_dc_update_stripe_free_trial_discount_data( $subscription, $form_location ){

    if( !isset( $_POST['discount_code'] ) || empty( $subscription->subscription_plan_id ) || ( $subscription->payment_gateway !== 'stripe_connect' && $subscription->payment_gateway !== 'stripe_intents' ) )
        return;

    $code = sanitize_text_field( $_POST['discount_code'] );
    $subscription_plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

    if( empty( $code ) || !$subscription_plan->has_trial() )
        return;

    $discount_meta = PMS_IN_Discount_Codes_Meta_Box::get_discount_meta_by_code( $code );

    if ( empty($discount_meta) )
        return;


    /**
     * Update (increment) discount code total uses
     * */

    if ( isset($discount_meta['pms_discount_uses'][0]) )
        $discount_meta['pms_discount_uses'][0]++;
    else
        $discount_meta['pms_discount_uses'][0] = 1;

    $discount_ID = PMS_IN_Discount_Codes_Meta_Box::get_discount_ID_by_code( $code );

    if ( !empty($discount_ID) ) {
        update_post_meta($discount_ID, 'pms_discount_uses', $discount_meta['pms_discount_uses'][0]);

        if( ! empty( $discount_meta['pms_discount_max_uses'][0] ) && $discount_meta['pms_discount_uses'][0] >= $discount_meta['pms_discount_max_uses'][0])
            PMS_IN_Discount_Code::deactivate($discount_ID);
    }

    /**
     * Update (increment) discount code uses for this user
     * - stored inside the user meta key 'pms_discount_uses_per_user_'.$code
     * */

    if ( !empty($subscription->user_id) ) {

        $meta = get_user_meta($subscription->user_id, 'pms_discount_uses_per_user_' . $code, true);

        $user_discount_uses = ( !empty( $meta ) ) ? (int)$meta : 0;
        update_user_meta( $subscription->user_id, 'pms_discount_uses_per_user_'.$code, $user_discount_uses + 1 );

        pms_add_member_subscription_meta( $subscription->id, 'pms_discount_code', $code );

    }

}
add_action( 'pms_after_checkout_is_processed', 'pms_in_dc_update_stripe_free_trial_discount_data', 20, 3 );
