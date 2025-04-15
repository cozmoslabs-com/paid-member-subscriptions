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

    $pms_stripe_script_vars = array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'empty_credit_card_message' => __( 'Please enter a credit card number.', 'paid-member-subscriptions' ), 'invalid_card_details_error' => __( 'Your card details do not seem to be valid.', 'paid-member-subscriptions' ) );

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
    $pms_stripe_script_vars['pms_active_currency']         = strtolower( pms_get_active_currency() );
    $pms_stripe_script_vars['pms_elements_appearance_api'] = apply_filters( 'pms_stripe_connect_elements_styling', array( 'theme' => 'stripe' ) );

    wp_localize_script( 'pms-stripe-script', 'pms', $pms_stripe_script_vars );

}

// AJAX hooks
/**
 * This is triggered each time a Subscription Plan is selected in the form in order to update
 * the amount of the Payment Intent
 */
add_action( 'wp_ajax_pms_update_payment_intent_connect', 'pms_stripe_connect_update_payment_intent' );
add_action( 'wp_ajax_nopriv_pms_update_payment_intent_connect', 'pms_stripe_connect_update_payment_intent' );
function pms_stripe_connect_update_payment_intent(){

    if( !check_ajax_referer( 'pms_stripe_connect_update_payment_intent', 'pms_nonce' ) )
        die();

    if( !isset( $_POST['subscription_plans'] ) )
        die();

    if( empty( $_POST['intent_secret'] ) )
        die();

    // Verify validity of Subscription Plan
    $subscription_plan = pms_get_subscription_plan( absint( $_POST['subscription_plans'] ) );

    if( !isset( $subscription_plan->id ) )
        die();

    // Calculate new amount
    $amount = pms_calculate_payment_amount( $subscription_plan );

    // Initialize gateway
    $gateway = pms_get_payment_gateway( 'stripe_connect' );

    $response = $gateway->update_payment_intent( sanitize_text_field( $_POST['intent_secret'] ), $amount, $subscription_plan );

    if( !empty( $response ) )
        echo json_encode( array( 'status' => $response->status, 'data' => array( 'plan_name' => $subscription_plan->name, 'amount' => $gateway->process_amount( $amount, pms_get_active_currency() ) ) ) );

    die();

}

/**
 * Get payment and setup intents
 */
add_action( 'wp_ajax_pms_stripe_get_payment_intents', 'pms_stripe_connect_get_payment_intents' );
add_action( 'wp_ajax_nopriv_pms_stripe_get_payment_intents', 'pms_stripe_connect_get_payment_intents' );
function pms_stripe_connect_get_payment_intents(){

    $gateway = pms_get_payment_gateway( 'stripe_connect' );

    $payment_intent = $gateway->create_initial_payment_intent();
    $setup_intent   = $gateway->create_initial_setup_intent();

    $data = array(
        'payment_intent'    => $payment_intent['client_secret'],
        'payment_intent_id' => $payment_intent['id'],
        'setup_intent'      => $setup_intent['client_secret'],
        'setup_intent_id'   => $setup_intent['id'],
    );

    echo json_encode( $data );
    die();

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

add_filter( 'pms_request_form_location', 'pms_stripe_filter_request_form_location', 20, 2 );
function pms_stripe_filter_request_form_location( $location, $request ){

    if( !wp_doing_ajax() )
        return $location;

    if( !isset( $request['form_type'] ) )
        return $location;

    // if( in_array( $request['form_type'], array( 'pms', 'wppb', 'pms_register' ) ) && isset( $request['action'] ) && $request['action'] == 'pms_stripe_connect_process_payment' && empty( $location ) )
    //     $location = 'register';

    if( $request['form_type'] == 'wppb' && isset( $request['action'] ) && $request['action'] == 'pms_update_payment_intent_connect' && isset( $request['pmstkn_original'] ) && $request['pmstkn_original'] == 'wppb_register' )
        $location = 'register';

    // set form location for wppb register AJAX request
    // if( $request['form_type'] == 'wppb' && isset( $request['action'] ) && $request['action'] == 'pms_process_checkout' )
    //     $location = 'register';

    return $location;

}

add_filter( 'wppb_register_form_content', 'pms_stripe_wppb_register_success_message' );
function pms_stripe_wppb_register_success_message( $content ){

    if( isset( $_REQUEST['pmsscscd'] ) && isset( $_REQUEST['pmsscsmsg'] ) ){
        $message_code =  base64_decode( sanitize_text_field( $_REQUEST['pmsscscd'] ) );
        $message      =  base64_decode( sanitize_text_field( $_REQUEST['pmsscsmsg'] ) );

        return '<p class="alert wppb-success" id="wppb_form_general_message">' . $message . '</p>';
    }

    return $content;

}