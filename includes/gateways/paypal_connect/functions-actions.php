<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

add_action( 'wp_footer', 'pms_ppcp_enqueue_front_end_scripts', 15 );
function pms_ppcp_enqueue_front_end_scripts(){

    if( !pms_should_load_scripts() )
        return;

    $active_gateways = pms_get_active_payment_gateways();

    if( !in_array( 'paypal_connect', $active_gateways ) )
        return;

    $credentials = pms_ppcp_get_api_credentials();

    if( empty( $credentials['payer_id'] ) )
        return;

    /**
     * Load PayPal JS SDK Loader
     * This needs to be updated manually if necessary
     * 
     * @link https://www.npmjs.com/package/@paypal/paypal-js
     */
    wp_enqueue_script( 'pms-paypal-js', PMS_PLUGIN_DIR_URL . 'includes/gateways/paypal_connect/assets/sdk/paypal-js.js', array(), '8.2.0' );

    $pms_multiple_currencies_active = apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-multiple-currencies/index.php' );

    $pms_script_vars = array( 
        'ajax_url'                             => admin_url( 'admin-ajax.php' ),
        'pms_ppcp_create_order_nonce'          => wp_create_nonce( 'pms_ppcp_create_order' ),
        'pms_ppcp_create_setup_token_nonce'    => wp_create_nonce('pms_ppcp_create_setup_token'),
        'pms_ppcp_generate_client_token_nonce' => wp_create_nonce('pms_ppcp_generate_client_token'),
        'pms_validate_currency_nonce'          => wp_create_nonce('pms_validate_currency'),
        'paypal_client_id'                     => $credentials['client_id'],
        'paypal_merchant_id'                   => $credentials['payer_id'],
        'paypal_partner_attribution_id'        => pms_ppcp_get_platform_bn_code(),
        'paypal_currency'                      => apply_filters( 'pms_ppcp_sdk_currency', pms_get_active_currency() ),
        'pms_ppcp_mc_addon_active'             => apply_filters( 'pms_ppcp_mc_addon_active', $pms_multiple_currencies_active ),
        'paypal_integration_date'              => '2025-01-28',
        'paypal_button_styles'                 => apply_filters( 'pms_ppcp_paypal_button_styles', array(
            'layout' => 'vertical',
            'color'  => 'gold',
            'shape'  => 'rect',
            'label'  => 'pay',
            'height' => 40
        ) ),
    );

    wp_enqueue_script( 'pms-paypal-script', PMS_PLUGIN_DIR_URL . 'includes/gateways/paypal_connect/assets/pms-paypal-connect-front-end.js', array( 'jquery', 'pms-front-end', 'pms-paypal-js' ), PMS_VERSION );

    wp_localize_script( 'pms-paypal-script', 'pms_paypal', $pms_script_vars );

}

/**
 * AJAX handler for creating a PayPal setup token
 * This is used for the Update Payment Method form
 */
function pms_ppcp_create_setup_token() {

    // Verify nonce
    if( !isset( $_POST['nonce'] ) || !wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'pms_ppcp_create_setup_token' ) ) {
        die();
    }

    // Get subscription plan if provided
    $subscription_plan = null;
    if( !empty( $_POST['subscription_plan_id'] ) ) {
        $subscription_plan = pms_get_subscription_plan( absint( $_POST['subscription_plan_id'] ) );
    }

    // Initialize PayPal gateway
    $gateway = pms_get_payment_gateway( 'paypal_connect' );

    $data = array(
        'success'        => true,
        'setup_token_id' => $gateway->create_setup_token( $subscription_plan )
    );

    echo json_encode( $data );
    die();

}
add_action( 'wp_ajax_pms_ppcp_create_setup_token', 'pms_ppcp_create_setup_token' );

/**
 * AJAX handler for generating a PayPal client token
 */
function pms_ppcp_generate_client_token() {

    // Verify nonce
    if( !isset( $_POST['nonce'] ) || !wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'pms_ppcp_generate_client_token' ) ) {
        echo json_encode( array( 'success' => false, 'error' => 'Invalid nonce' ) );
        die();
    }

    // Check if user is logged in
    if( !is_user_logged_in() ) {
        echo json_encode( array( 'success' => false, 'error' => 'User not logged in' ) );
        die();
    }

    $user_id = get_current_user_id();

    // Get customer ID from request
    $customer_id = get_user_meta( $user_id, 'pms_paypal_customer_id', true );

    if( empty( $customer_id ) ) {
        echo json_encode( array( 'success' => false, 'error' => 'No customer ID found' ) );
        die();
    }

    // Initialize PayPal gateway
    $gateway = pms_get_payment_gateway( 'paypal_connect' );

    $data = array(
        'success'      => true,
        'client_token' => $gateway->generate_client_token( $customer_id )
    );

    echo json_encode( $data );
    die();

}
add_action( 'wp_ajax_pms_ppcp_generate_client_token', 'pms_ppcp_generate_client_token' );
