<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

function pms_stripe_connect_get_api_credentials(){

    $environment = pms_is_payment_test_mode() ? 'test' : 'live';

    return array(
        'publishable_key' => get_option( 'pms_stripe_connect_'. $environment .'_publishable_key', '' ),
        'secret_key'      => get_option( 'pms_stripe_connect_'. $environment .'_secret_key', '' )
    );

}

/**
 * Get the Stripe webhook signing secret for the current environment
 *
 * Returns the webhook signing secret used for verifying webhook signatures.
 * The secret is environment-specific (test or live mode).
 *
 * @return string The webhook signing secret, or empty string if not configured
 */
function pms_stripe_connect_get_webhook_secret(){

    $environment = pms_is_payment_test_mode() ? 'test' : 'live';

    return get_option( 'pms_stripe_connect_'. $environment .'_webhook_secret', '' );

}

function pms_stripe_connect_get_account_status(){

    $api_credentials = pms_stripe_connect_get_api_credentials();

    if( empty( $api_credentials['secret_key'] ) )
        return false;

    $stripe = new \Stripe\StripeClient( $api_credentials['secret_key'] );

    $account = pms_stripe_connect_get_account();

    if( empty( $account ) )
        return false;

    try {

        $account = $stripe->accounts->retrieve( $account, array() );

    } catch( Exception $e ){

        return [ 'message' => $e->getMessage() ];

    }

    if( $account->details_submitted != true )
        return 'details_submitted_missing';

    if( $account->charges_enabled != true )
        return 'charges_enabled_missing';

    if( $account->details_submitted == true && $account->charges_enabled == true )
        return true;

    return false;

}

function pms_stripe_connect_get_account(){

    $environment = pms_is_payment_test_mode() ? 'test' : 'live';

    return get_option( 'pms_stripe_connect_'. $environment .'_account_id', false );

}

function pms_stripe_connect_get_account_country(){

    $environment = pms_is_payment_test_mode() ? 'test' : 'live';

    $country = get_option( 'pms_stripe_connect_account_country_' . $environment, false );

    if( empty( $country ) ){
        $country = get_option( 'pms_stripe_connect_account_country', false );
    }

    return $country;

}

function pms_get_active_stripe_gateway(){

    $settings = get_option( 'pms_payments_settings', array() );

    if( !isset( $settings['active_pay_gates'] ) )
        return false;

    $active_gateway = false;

    foreach( $settings['active_pay_gates'] as $gateway_slug ){
        if( strpos( $gateway_slug, 'stripe' ) !== false )
            $active_gateway = $gateway_slug;
    }

    return $active_gateway;

}

function pms_stripe_get_generated_errors(){

    $generated_errors = array();
    $error_obj        = pms_errors();

    if( !empty( $error_obj->errors ) ){
        foreach( $error_obj->errors as $key => $error ){

            if( !empty( $error[0] ) )
                $generated_errors[] = array(
                    'target'  => $key,
                    'message' => $error[0]
                );

        }
    }

    return $generated_errors;

}

function pms_stripe_validate_wppb_form_fields(){

    if( !isset( $_POST['wppb_fields'] ) )
        return '';

    // Load fields
    include_once( WPPB_PLUGIN_DIR .'/front-end/default-fields/default-fields.php' );
    if( function_exists( 'wppb_include_extra_fields_files' ) )
        wppb_include_extra_fields_files();

    // Load WPPB fields data
    $wppb_manage_fields = get_option( 'wppb_manage_fields', 'not_found' );

    $output_field_errors = array();

    foreach( $_POST['wppb_fields'] as $id => $value ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        $field = array();

        // return field name from field class
        $field_name = explode( ' ', $value['class'] );
        $field_name = substr( $field_name[1], 5 );
        $field_name = esc_attr( $field_name );

        // return field title by removing required sign *
        if( isset( $value['title'] ) ) {
            $field['field-title'] = str_replace( '*', '', $value['title'] );
            $field['field-title'] = sanitize_text_field( $field['field-title'] );
        }

        // return the id of the field from the field li (wppb-form-element-XX)
        if( isset( $id ) ) {
            $field_id = intval( substr( $id, 18 ) );
        }

        // check for fields errors for woocommerce billing fields
        if( $field_name == 'woocommerce-customer-billing-address' ) {
            if( ( function_exists( 'wppb_woo_billing_fields_array' ) && function_exists( 'wppb_check_woo_individual_fields_val' ) ) || ( function_exists( 'wppb_in_woo_billing_fields_array' ) && function_exists( 'wppb_in_check_woo_individual_fields_val' ) ) ) {
                $field['field'] = 'WooCommerce Customer Billing Address';

                if( function_exists('wppb_woo_billing_fields_array') )
                    $billing_fields = wppb_woo_billing_fields_array();
                else if( function_exists('wppb_in_woo_billing_fields_array') )
                    $billing_fields = wppb_in_woo_billing_fields_array();

                if( ! empty( $_POST['billing_country'] ) && class_exists( 'WC_Countries' ) ) {
                    $WC_Countries_Obj = new WC_Countries();
                    $locale = $WC_Countries_Obj->get_country_locale();

                    if( isset( $locale[sanitize_text_field( $_POST['billing_country'] )]['state']['required'] ) && ( $locale[sanitize_text_field( $_POST['billing_country'] )]['state']['required'] == false ) ) {
                        if( is_array( $billing_fields ) && isset( $billing_fields['billing_state'] ) ) {
                            $billing_fields['billing_state']['required'] = 'No';
                        }
                    }
                }

                if( isset( $value['fields'] ) ) {
                    foreach( $value['fields'] as $key => $woo_field_label ) {
                        $key = sanitize_text_field( $key );

                        if( function_exists('wppb_check_woo_individual_fields_val') )
                            $woo_error_for_field = wppb_check_woo_individual_fields_val( '', $billing_fields[$key], $key, $_POST, isset( $_POST['form_type'] ) ? sanitize_text_field( $_POST['form_type'] ) : '' );
                        else if( function_exists('wppb_in_check_woo_individual_fields_val') )
                            $woo_error_for_field = wppb_in_check_woo_individual_fields_val( '', $billing_fields[$key], $key, $_POST, isset( $_POST['form_type'] ) ? sanitize_text_field( $_POST['form_type'] ) : '' );

                        if( ! empty( $woo_error_for_field ) ) {
                            $output_field_errors[$key]['field'] = $key;
                            $output_field_errors[$key]['error'] = '<span class="wppb-form-error">'. $woo_error_for_field .'</span>';
                            $output_field_errors[$key]['type'] = 'woocommerce';
                        }
                    }
                }
            }
        }

        // check for fields errors for woocommerce shipping fields
        if( $field_name == 'woocommerce-customer-shipping-address' ) {
            if( ( function_exists( 'wppb_woo_shipping_fields_array' ) && function_exists( 'wppb_check_woo_individual_fields_val' ) ) || ( function_exists( 'wppb_in_woo_shipping_fields_array' ) && function_exists( 'wppb_in_check_woo_individual_fields_val' ) ) ) {
                $field['field'] = 'WooCommerce Customer Shipping Address';

                if( function_exists('wppb_woo_shipping_fields_array') )
                    $shipping_fields = wppb_woo_shipping_fields_array();
                else if( function_exists('wppb_in_woo_shipping_fields_array') )
                    $shipping_fields = wppb_in_woo_shipping_fields_array();

                if( ! empty( $_POST['shipping_country'] ) && class_exists( 'WC_Countries' ) ) {
                    $WC_Countries_Obj = new WC_Countries();
                    $locale = $WC_Countries_Obj->get_country_locale();

                    if( isset( $locale[sanitize_text_field( $_POST['shipping_country'] )]['state']['required'] ) && ( $locale[ sanitize_text_field( $_POST['shipping_country'] ) ]['state']['required'] == false ) ) {
                        if( is_array( $shipping_fields ) && isset( $shipping_fields['shipping_state'] ) ) {
                            $shipping_fields['shipping_state']['required'] = 'No';
                        }
                    }
                }

                if( isset( $value['fields'] ) ) {
                    foreach( $value['fields'] as $key => $woo_field_label ) {
                        $key = sanitize_text_field( $key );

                        if( function_exists('wppb_check_woo_individual_fields_val') )
                            $woo_error_for_field = wppb_check_woo_individual_fields_val( '', $shipping_fields[$key], $key, $_POST, isset( $_POST['form_type'] ) ? sanitize_text_field( $_POST['form_type'] ) : '' );
                        else if( function_exists('wppb_in_check_woo_individual_fields_val') )
                            $woo_error_for_field = wppb_in_check_woo_individual_fields_val( '', $shipping_fields[$key], $key, $_POST, isset( $_POST['form_type'] ) ? sanitize_text_field( $_POST['form_type'] ) : '' );

                        if( ! empty( $woo_error_for_field ) ) {
                            $output_field_errors[$key]['field'] = $key;
                            $output_field_errors[$key]['error'] = '<span class="wppb-form-error">'. $woo_error_for_field .'</span>';
                            $output_field_errors[$key]['type'] = 'woocommerce';
                        }
                    }
                }
            }
        }

        // add repeater fields to fields array
        if( isset( $value['extra_groups_count'] ) ) {
            $wppb_manage_fields = apply_filters( 'wppb_form_fields', $wppb_manage_fields, array( 'context' => 'multi_step_forms', 'extra_groups_count' => esc_attr( $value['extra_groups_count'] ), 'global_request' => $_POST, 'form_type' => 'register' ) );
        }

        // search for fields in fields array by meta-name or id (if field does not have a mata-name)
        if( ! empty( $value['meta-name'] ) && $value['meta-name'] != 'passw1' && $value['meta-name'] != 'passw2' && pms_wppb_msf_get_field_options( $value['meta-name'], $wppb_manage_fields ) !== false ) {
            $field = pms_wppb_msf_get_field_options( $value['meta-name'], $wppb_manage_fields );
        } elseif( ! empty( $field_id ) && pms_wppb_msf_get_field_options( $field_id, $wppb_manage_fields, 'id' ) !== false
            && $field_name != 'woocommerce-customer-billing-address' && $field_name != 'woocommerce-customer-shipping-address' ) {

            $field = pms_wppb_msf_get_field_options( $field_id, $wppb_manage_fields, 'id' );
        }


        // check for fields errors
        if( $field_name != 'woocommerce-customer-billing-address' && $field_name != 'woocommerce-customer-shipping-address' ) {
            $error_for_field = apply_filters( 'wppb_check_form_field_'. $field_name, '', $field, $_POST, 'register' );
        }

        // construct the array with fields errors
        if( ( ! empty( $value['meta-name'] ) || $field_name == 'subscription-plans' ) && ! empty( $error_for_field ) ) {
            $output_field_errors[esc_attr( $value['meta-name'] )]['field'] = $field_name;
            $output_field_errors[esc_attr( $value['meta-name'] )]['error'] = '<span class="wppb-form-error">'. wp_kses_post( $error_for_field ) .'</span>';
        }

    }

    $output_field_errors = apply_filters( 'wppb_output_field_errors_filter', $output_field_errors );

    return $output_field_errors;

}

/**
 * Function that search in multidimensional arrays
 * Copied from MultiStep Forms add-on
 */
function pms_wppb_msf_get_field_options( $needle, $haystack, $type = 'meta-name' ) {

    foreach( $haystack as $item ) {
        if( is_array( $item ) && isset( $item[$type] ) && $item[$type] == $needle ) {
            return $item;
        }
    }

    return false;

}

function pms_stripe_is_domain_registered_for_payment_methods(){

    if( !is_admin() )
        return;

    $gateway = pms_get_payment_gateway( 'stripe_connect' );

    return $gateway->domain_is_registered();

}

function pms_stripe_get_meta_entry( $meta_key, $meta_value ){
    global $wpdb;

    $result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value ), 'ARRAY_A' );

    if( !empty( $result ) )
        return $result;

    return false;
}

function pms_stripe_generate_customer_session(){

    if( !is_user_logged_in() )
        return false;

    if( !class_exists('\Stripe\Service\CustomerSessionService') )
        return false;

    $customer_id = get_user_meta( get_current_user_id(), 'pms_stripe_customer_id', true );

    if( empty( $customer_id ) )
        return false;

    $api_credentials = pms_stripe_connect_get_api_credentials();

    if( empty( $api_credentials['secret_key'] ) )
        return false;

    $stripe = new \Stripe\StripeClient( $api_credentials['secret_key'] );

    try {

        $customer_session = $stripe->customerSessions->create([
            'customer' => $customer_id,
            'components' => [
                'payment_element' => [
                    'enabled' => true,
                    'features' => [
                        'payment_method_redisplay' => 'enabled',
                        'payment_method_save' => 'enabled',
                        'payment_method_save_usage' => 'off_session',
                    ],
                ],
            ],
        ]);

    } catch( Exception $e ){
        return false;
    }

    return $customer_session->client_secret;

}
