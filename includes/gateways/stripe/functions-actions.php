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

    wp_enqueue_style( 'pms-stripe-style', PMS_PLUGIN_DIR_URL . 'includes/gateways/stripe/assets/pms-stripe.css', array(), PMS_VERSION );

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
add_action( 'wp_ajax_pms_process_checkout', 'pms_stripe_process_checkout' );
add_action( 'wp_ajax_nopriv_pms_process_checkout', 'pms_stripe_process_checkout' );
function pms_stripe_process_checkout(){

    if( !check_ajax_referer( 'pms_process_checkout', 'pms_nonce' ) )
        die();

    // this is simply added so the AJAX request to the website triggers the regular
    // form processing of the plugin

    // Process WPPB form manually based on the request data
    if( isset( $_REQUEST['form_type'] ) && $_REQUEST['form_type'] == 'wppb' ){
        pms_stripe_process_wppb_checkout();
    }

}

add_action( 'wp_ajax_pms_validate_checkout', 'pms_stripe_validate_checkout_handler' );
add_action( 'wp_ajax_nopriv_pms_validate_checkout', 'pms_stripe_validate_checkout_handler' );
function pms_stripe_validate_checkout_handler(){

    if( !check_ajax_referer( 'pms_process_checkout', 'pms_nonce' ) )
        die();

    pms_stripe_validate_checkout();

    $data = array(
        'success' => true,
    );

    echo json_encode( $data );

    die();

}

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
    $amount = pms_stripe_calculate_payment_amount( $subscription_plan );

    // Initialize gateway
    $gateway = new PMS_Payment_Gateway_Stripe_Connect();
    $gateway->init();

    $response = $gateway->update_payment_intent( sanitize_text_field( $_POST['intent_secret'] ), $amount, $subscription_plan );

    if( !empty( $response ) )
        echo json_encode( array( 'status' => $response->status, 'data' => array( 'plan_name' => $subscription_plan->name, 'amount' => $gateway->process_amount( $amount, pms_get_active_currency() ) ) ) );

    die();

}

/**
 * This is triggered when the payment has finished in the front-end and we need to update the
 * payment and subscription on the website
 */
add_action( 'wp_ajax_pms_stripe_connect_process_payment', 'pms_stripe_connect_process_payment' );
add_action( 'wp_ajax_nopriv_pms_stripe_connect_process_payment', 'pms_stripe_connect_process_payment' );
function pms_stripe_connect_process_payment(){

    // We use the same nonce as the process request for this one
    if( !check_ajax_referer( 'pms_process_payment', 'pms_nonce' ) )
        die();

    $payment_id      = !empty( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;
    $subscription_id = !empty( $_POST['subscription_id'] ) ? absint( $_POST['subscription_id'] ) : 0;

    // Initialize gateway
    $gateway = new PMS_Payment_Gateway_Stripe_Connect();
    $gateway->init();

    $gateway->process_payment( $payment_id, $subscription_id );
    die();

}

/**
 * Grab a fresh process payment nonce
 */
add_action( 'wp_ajax_pms_stripe_update_nonce', 'pms_stripe_connect_update_process_payment_nonce' );
add_action( 'wp_ajax_nopriv_pms_stripe_update_nonce', 'pms_stripe_connect_update_process_payment_nonce' );
function pms_stripe_connect_update_process_payment_nonce(){

    echo json_encode( wp_create_nonce( 'pms_process_payment' ) );
    die();

}

/**
 * Get payment intents
 */
add_action( 'wp_ajax_pms_stripe_get_payment_intents', 'pms_stripe_connect_get_payment_intents' );
add_action( 'wp_ajax_nopriv_pms_stripe_get_payment_intents', 'pms_stripe_connect_get_payment_intents' );
function pms_stripe_connect_get_payment_intents(){

    $gateway = new PMS_Payment_Gateway_Stripe_Connect();

    $data = array(
        'payment_intent' => $gateway->create_initial_payment_intent(),
        'setup_intent'   => $gateway->create_initial_setup_intent(),
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

    $gateway = new PMS_Payment_Gateway_Stripe_Connect();
    $gateway->init();

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

    if( in_array( $request['form_type'], array( 'pms', 'wppb', 'pms_register' ) ) && isset( $request['action'] ) && $request['action'] == 'pms_stripe_connect_process_payment' && empty( $location ) )
        $location = 'register';

    if( $request['form_type'] == 'wppb' && isset( $request['action'] ) && $request['action'] == 'pms_update_payment_intent_connect' && isset( $request['pmstkn_original'] ) && $request['pmstkn_original'] == 'wppb_register' )
        $location = 'register';

    // set form location for wppb register AJAX request
    if( $request['form_type'] == 'wppb' && isset( $request['action'] ) && $request['action'] == 'pms_process_checkout' )
        $location = 'register';

    return $location;

}

// When the WPPB form uses PMS we reorder the fields in the ajax request so that the Subscription Plans field is last
// This happens because on the save hook of that field, PMS does the necessary processing to create the payment and
// subscription. A request which is then intercepted by the same redirect failure action from the Stripe Connect class
add_filter( 'wppb_change_form_fields', 'pms_stripe_reorder_fields_when_doing_ajax_requests', 20, 2 );
function pms_stripe_reorder_fields_when_doing_ajax_requests( $fields, $form_args ){

    if( isset( $form_args['pms_custom_ajax_request'] ) && $form_args['pms_custom_ajax_request'] == true ){

        if( !empty( $fields ) ){

            $plans = null;

            foreach( $fields as $key => $field ){

                if( $field['field'] == 'Subscription Plans' ){
                    $plans[$key] = $field;
                    unset( $fields[$key] );
                }

            }

            if( !empty( $plans ) )
                $fields = array_merge( $fields, $plans );

        }

    }

    return $fields;

}

function pms_stripe_process_wppb_checkout(){

    if( defined( 'WPPB_PLUGIN_DIR' ) )
        include_once( WPPB_PLUGIN_DIR . '/front-end/class-formbuilder.php' );
    else
        return false;

    $args = array(
        'form_type'               => 'register',
        'form_name'               => isset( $_REQUEST['form_name'] ) ? sanitize_text_field( $_REQUEST['form_name'] ) : '',
        'form_fields'             => '',
        'role'                    => get_option( 'default_role' ),
        'pms_custom_ajax_request' => true,
    );

    $form = new Profile_Builder_Form_Creator( $args );

    $field_check_errors = $form->wppb_test_required_form_values( $_REQUEST );

    if( empty( $field_check_errors ) ){

        do_action( 'wppb_before_saving_form_values', $_REQUEST, $form->args );

        // Process is started here, it gets completed by the PMS handler that gets triggered when the Subscription Plans field is saved
        $user_id = $form->wppb_save_form_values( $_REQUEST );

        do_action( 'wppb_after_saving_form_values', $_REQUEST, $form->args );

    } else {

        $data = array(
            'success'     => false,
            'wppb_errors' => $field_check_errors,
        );

        echo json_encode( $data );
        die();

    }

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