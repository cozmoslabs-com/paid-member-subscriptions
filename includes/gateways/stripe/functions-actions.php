<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

add_action( 'wp_footer', 'pms_stripe_enqueue_front_end_scripts' );
function pms_stripe_enqueue_front_end_scripts(){

    if( !pms_should_load_scripts() )
        return;

    $active_gateways = pms_get_active_payment_gateways();

    if( !in_array( 'stripe_connect', $active_gateways ) )
        return;

    wp_enqueue_script( 'pms-stripe-js', 'https://js.stripe.com/v3/', array( 'jquery' ) );

    $pms_stripe_script_vars = array( 
        'ajax_url'                    => admin_url( 'admin-ajax.php' ),
        'empty_credit_card_message'   => __( 'Please enter a credit card number.', 'paid-member-subscriptions' ),
        'invalid_card_details_error'  => __( 'Your card details do not seem to be valid.', 'paid-member-subscriptions' ),
        'checkout_unexpected_error'   => __( 'Something went wrong while processing your request. Please try again or contact the website administrator.', 'paid-member-subscriptions' ),
        'pms_validate_currency_nonce' => wp_create_nonce( 'pms_validate_currency' ),
        'currency'                    => strtolower( apply_filters( 'pms_stripe_sdk_currency', pms_get_active_currency() ) ),
        'pms_mc_addon_active'         => apply_filters( 'pms_stripe_mc_addon_active', apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-multiple-currencies/index.php' ) ),
    );

    wp_enqueue_script( 'pms-stripe-script', PMS_PLUGIN_DIR_URL . 'includes/gateways/stripe/assets/front-end-connect.js', array('jquery', 'pms-front-end'), PMS_VERSION );

    $environment = pms_is_payment_test_mode() ? 'test' : 'live';

    $connected_account = get_option( 'pms_stripe_connect_'. $environment .'_account_id', false );

    if( !empty( $connected_account ) )
        $pms_stripe_script_vars['stripe_connected_account'] = $connected_account;

    $current_locale = get_locale();

    if( !empty( $current_locale ) ){

        $stripe_locale = substr( $current_locale, 0, 2 );

        $pms_stripe_script_vars['stripe_locale'] = apply_filters( 'pms_stripe_elements_locale', $stripe_locale );

    }

    $pms_stripe_script_vars['stripe_return_url']           = add_query_arg( 'pms_stripe_connect_return_url', 1, home_url() );
    $pms_stripe_script_vars['stripe_account_country']      = pms_stripe_connect_get_account_country();
    $pms_stripe_script_vars['pms_elements_appearance_api'] = apply_filters( 'pms_stripe_connect_elements_styling', array( 'theme' => 'stripe' ) );
    $pms_stripe_script_vars['pms_customer_session']        = pms_stripe_generate_customer_session();
    
    if( is_user_logged_in() ){
        $user = get_userdata( get_current_user_id() );
        $pms_stripe_script_vars['pms_customer_email'] = $user->user_email;
        $pms_stripe_script_vars['pms_customer_name'] = $user->display_name;
    } else {
        $pms_stripe_script_vars['pms_customer_email'] = '';
        $pms_stripe_script_vars['pms_customer_name'] = '';
    }

    $pms_stripe_script_vars['off_session_payments'] = 1;

    // Disable off-session payments if the global recurring setting is set to Never renew automatically
    $payment_settings = get_option( 'pms_payments_settings', array() );

    if( isset( $payment_settings['recurring'] ) && $payment_settings['recurring'] == 3 )
        $pms_stripe_script_vars['off_session_payments'] = 0;

    wp_localize_script( 'pms-stripe-script', 'pms', $pms_stripe_script_vars );

}

/**
 * Used to process the payment after a payment method redirects off-site and then returns the user
 */
add_action( 'template_redirect', 'pms_stripe_connect_handle_payment_method_return_url' );
function pms_stripe_connect_handle_payment_method_return_url(){

    if( !isset( $_GET['pms_stripe_connect_return_url'] ) || $_GET['pms_stripe_connect_return_url'] != 1 )
        return;

    $payment_intent_id = false;

    if( !empty( $_GET['payment_intent'] ) )
        $payment_intent_id = sanitize_text_field( $_GET['payment_intent'] );
    else if( !empty( $_GET['setup_intent'] ) )
        $payment_intent_id = sanitize_text_field( $_GET['setup_intent'] );

    if( empty( $payment_intent_id ) )
        return;

    $payment = pms_get_payments( array( 'transaction_id' => $payment_intent_id ) );

    if( empty( $payment ) || empty( $payment[0] ) ){
        $payment_id = 0;

        // Try to find a subscription with this intent ID
        $subscription_meta = pms_stripe_get_meta_entry( 'pms_stripe_initial_payment_intent', $payment_intent_id );

        if( !empty( $subscription_meta[0] ) && !empty( $subscription_meta[0]['member_subscription_id'] ) )
            $subscription_id = absint( $subscription_meta[0]['member_subscription_id'] );

    } else {

        if( $payment[0]->status == 'completed' )
            return;

        $payment_id      = $payment[0]->id;
        $subscription_id = $payment[0]->member_subscription_id;

    }

    if( empty( $subscription_id ) )
        return;

    // Setup the global variables necessary for the class
    if( isset( $_GET['setup_intent'] ) ){
        $_REQUEST['setup_intent']   = true;
        $_REQUEST['payment_intent'] = $payment_intent_id;
    }

    $gateway = pms_get_payment_gateway( 'stripe_connect' );

    $response = $gateway->process_payment( $payment_id, $subscription_id );

    if( !empty( $response['redirect_url'] ) ){
        wp_redirect( $response['redirect_url'] );
        die();
    }

    return;

}

add_filter( 'wppb_register_form_content', 'pms_stripe_wppb_register_success_message' );
function pms_stripe_wppb_register_success_message( $content ){

    if( isset( $_REQUEST['pmsscscd'] ) && isset( $_REQUEST['pmsscsmsg'] ) ){
        $message_code =  sanitize_text_field( base64_decode( $_REQUEST['pmsscscd'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $message      =  sanitize_text_field( base64_decode( $_REQUEST['pmsscsmsg'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        return '<p class="alert wppb-success" id="wppb_form_general_message">' . esc_html( $message ) . '</p>';
    }

    return $content;

}