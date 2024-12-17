<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

use Stripe\Stripe;
use Stripe\Account;
use Stripe\Customer;
use Stripe\Charge;
use Stripe\PaymentMethod;
use Stripe\PaymentIntent;
use Stripe\SetupIntent;

Class PMS_Payment_Gateway_Stripe_Connect extends PMS_Payment_Gateway {

    /** The Stripe Token generated from the credit card information
     *
     * @access protected
     * @var string
     *
     */
    protected $stripe_token;

    /**
     * The Stripe API secret key
     *
     * @access protected
     * @var string
     *
     */
    protected $secret_key;

    /**
     * The discount code being used on checkout
     *
     * @access protected
     * @var string
     *
     */
    protected $discount = false;

    /**
     * The features supported by the payment gateway
     *
     * @access public
     * @var array
     *
     */
    public $supports;

    /**
     * The Customer ID for the current checkout
     *
     * @access protected
     * @var string
     *
     */
    private $customer_id       = '';

    /**
     * Connected Stripe Account ID
     *
     * @access protected
     * @var string
     *
     */
    private $connected_account = false;

    public $gateway_slug = 'stripe_connect';

    /**
     * Initialisation
     *
     */
    public function init() {

        $this->supports = array(
            'plugin_scheduled_payments',
            'recurring_payments',
            'subscription_sign_up_fee',
            'subscription_free_trial',
            'change_subscription_payment_method_admin',
            'update_payment_method',
        );

        // don't add any hooks if the gateway is not active
        if( !in_array( $this->gateway_slug, pms_get_active_payment_gateways() ) )
            return;

        $this->set_appinfo();

        $environment = pms_is_payment_test_mode() ? 'test' : 'live';

        $this->connected_account = get_option( 'pms_stripe_connect_'. $environment .'_account_id', false );

        // Set API secret key
        $api_credentials  = pms_stripe_connect_get_api_credentials();
        $this->secret_key = ( !empty( $api_credentials['secret_key'] ) ? $api_credentials['secret_key'] : '' );

        // Set Stripe token obtained with Stripe JS
        $this->stripe_token = ( !empty( $_POST['stripe_token'] ) ? sanitize_text_field( $_POST['stripe_token'] ) : '' );

        // Set discount
        if( !empty( $_POST['discount_code'] ) && function_exists( 'pms_in_get_discount_by_code' ) )
            $this->discount = pms_in_get_discount_by_code( sanitize_text_field( $_POST['discount_code'] ) );

        if( empty( $this->payment_id ) && isset( $_POST['payment_id'] ) )
            $this->payment_id = (int)$_POST['payment_id'];

        if( empty( $this->form_location ) && isset( $_POST['form_location'] ) )
            $this->form_location = sanitize_text_field( $_POST['form_location'] );

        /**
         * When Stripe Connect is used for checkout we make an AJAX request to the website that triggers
         * the normal flow of the plugin: validation -> register user -> process checkout
         *
         * The checkout will error out since we don't have the required payment data and we just want it
         * to register the user, payment, subscription at this point, then it will reach this action
         *
         * We hook the action in order to return some data to the front-end js in order to complete the
         * processing of this payment
         */
        add_action( 'pms_checkout_error_before_redirect', array( $this, 'handle_checkout_error_redirect' ), 20, 2 );

        if( !is_admin() ) {

            // Add the needed sections for the checkout forms
            add_filter( 'pms_extra_form_sections', array( __CLASS__, 'register_form_sections' ), 25, 2 );

            // Add the needed form fields for the checkout forms
            add_filter( 'pms_extra_form_fields',   array( __CLASS__, 'register_form_fields' ), 25, 2 );

            // In case of a failed payment, replace the default Profile Builder success message
            add_action( 'wppb_save_form_field',          array( $this, 'wppb_success_message_wrappers' ) );

            // Don't let users use the same card for multiple trials using the same subscription plan
            add_action( 'pms_checkout_has_trial', array( $this, 'disable_trial_if_duplicate_card' ) );

            // add Stripe publishable keys into the form
            if( !pms_stripe_check_filter_from_class_exists( 'pms_get_output_payment_gateways', get_class($this), 'field_publishable_key' ) )
                add_filter( 'pms_get_output_payment_gateways', array( $this, 'field_publishable_key' ), 10, 2 );

            // Payment Intent AJAX nonce
            if( !pms_stripe_check_filter_from_class_exists( 'pms_get_output_payment_gateways', get_class($this), 'field_ajax_nonces' ) )
                add_filter( 'pms_get_output_payment_gateways', array( $this, 'field_ajax_nonces' ), 10, 2 );

            // Add Publishable Key to Update Payment method form
            if( !pms_stripe_check_filter_from_class_exists( 'pms_update_payment_method_form_bottom', get_class($this), 'update_payment_form_field_publishable_key' ) )
                add_action( 'pms_update_payment_method_form_bottom', array( $this, 'update_payment_form_field_publishable_key' ) );

            // Add Update Payment Method ajax request nonce to form
            if( !pms_stripe_check_filter_from_class_exists( 'pms_update_payment_method_form_bottom', get_class($this), 'field_update_payment_method_nonce' ) )
                add_action( 'pms_update_payment_method_form_bottom', array( $this, 'field_update_payment_method_nonce' ), 20 );

            // Process Update Payment Method request
            if( !pms_stripe_check_filter_from_class_exists( 'pms_update_payment_method_stripe_connect', get_class($this), 'update_customer_payment_method' ) )
                add_action( 'pms_update_payment_method_stripe_connect', array( $this, 'update_customer_payment_method' ) );

            if( !pms_stripe_check_filter_from_class_exists( 'pms_update_payment_method_stripe_intents', get_class($this), 'update_customer_payment_method' ) )
                add_action( 'pms_update_payment_method_stripe_intents', array( $this, 'update_customer_payment_method' ) );

            // Add Form Fields placeholder
            if( !pms_stripe_check_filter_from_class_exists( 'pms_output_form_field_stripe_placeholder', get_class($this), 'output_form_field_stripe_placeholder' ) )
                add_action( 'pms_output_form_field_stripe_placeholder', array( $this, 'output_form_field_stripe_placeholder' ) );

        }

    }

    /**
     * Create the customer and save the customer's card id in Stripe and also save their ids as metadata
     * for the provided subscription as the payment method metadata needed for future payments
     *
     * @param int $member_subscription_id
     *
     * @return bool
     *
     */
    public function register_automatic_billing_info( $member_subscription_id = 0 ) {

        if( empty( $this->secret_key ) )
            return false;

        if( empty( $member_subscription_id ) )
            return false;

        // Set API key
        Stripe::setApiKey( $this->secret_key );

        // Verify API key
        try {

            Account::retrieve();

        } catch( Exception $e ) {

            return false;

        }

        if( !empty( $this->stripe_token ) ) {

            try {
                //if we receive a Setup Intent ID an error happened, log it
                if( strpos( $this->stripe_token, 'seti_' ) !== false ){

                    //retrieve error from setup intent
                    $setup_intent = \Stripe\SetupIntent::retrieve( $this->stripe_token );

                    if( !empty( $setup_intent['last_setup_error'] ) ) {
                        $data       = array();
                        $error      = $setup_intent['last_setup_error'];

                        $data['data'] = array(
                            'code'              => !empty( $error['code'] ) ? $error['code'] : '',
                            'message'           => !empty( $error['message'] ) ? $error['message'] : '',
                            'doc_url'           => !empty( $error['doc_url'] ) ? $error['doc_url'] : '',
                            'payment_intent_id' => $this->stripe_token,
                        );

                        $error_code = !empty( $error['decline_code'] ) ? $error['decline_code'] : '';

                        $data['message'] = !empty( $error['message'] ) ? $error['message'] : '';
                        $data['desc']    = 'stripe response';

                        $payment = pms_get_payment( $this->payment_id );

                        $payment->log_data( 'payment_failed', $data, $error_code );
                        $payment->update( array( 'status' => 'failed' ) );

                        return false;
                    }
                }

                // WPPB Setup Intent
                if( !empty( $_REQUEST['setup_intent_id'] ) ){

                    $setup_intent = \Stripe\SetupIntent::retrieve( sanitize_text_field( $_REQUEST['setup_intent_id'] ) );

                    if( !empty( $setup_intent->customer ) ){

                        // Save Customer and Card for this subscription
                        pms_update_member_subscription_meta( $member_subscription_id, '_stripe_customer_id', $setup_intent->customer );
                        pms_update_member_subscription_meta( $member_subscription_id, '_stripe_card_id', $this->stripe_token );

                        $subscription = pms_get_member_subscription( $member_subscription_id );

                        // Save Customer to usermeta
                        update_user_meta( $subscription->user_id, 'pms_stripe_customer_id', $setup_intent->customer );

                        $this->update_customer_information( $setup_intent->customer );

                    }

                }

                // If subscription had a trial, save card fingerprint
                $this->save_trial_card( $member_subscription_id, $this->stripe_token );

                // Save card expiration info
                $this->save_payment_method_expiration_data( $member_subscription_id, $this->stripe_token );

            } catch( Exception $e ) {

                $this->log_error_data( $e );

                $payment = pms_get_payment( $this->payment_id );
                $payment->update( array( 'status' => 'failed' ) );

                return false;

            }

            return true;

        }

        return false;

    }

    /**
     * TODO: add comment
     */
    public function process_payment( $payment_id = 0, $subscription_id = 0 ) {

        if( empty( $this->secret_key ) )
            return false;

        // set API key
        Stripe::setApiKey( $this->secret_key );

        if( $payment_id != 0 )
            $this->payment_id = $payment_id;

        $payment = pms_get_payment( $this->payment_id );

        $target = isset( $_REQUEST['pmstkn_original'] ) ? 'pmstkn_original' : 'pmstkn';

        $form_location = PMS_Form_Handler::get_request_form_location( $target );

        if( isset( $_REQUEST['payment_intent'] ) && isset( $_GET['pms_stripe_connect_return_url'] ) && $_GET['pms_stripe_connect_return_url'] == 1 )
            $form_location = 'stripe_return_url';

        // Mark the start of a renewal for a subscription
        if( $form_location == 'renew_subscription' )
            pms_update_member_subscription_meta( $subscription_id, 'pms_subscription_renewal_' . $this->payment_id, 'started' );

        if( isset( $payment->status ) && $payment->status == 'completed' ){

            // If the payment is completed because the webhook was received already we don't want to touch it
            // But the payment can also be completed when a 100% discount code is used and in that scenario we need to continue
            if( empty( $_REQUEST['discount_code'] ) || ( !empty( $_REQUEST['discount_code'] ) && $payment->amount != 0 ) ){
                $data = array(
                    'success'      => true,
                    'redirect_url' => $this->get_success_redirect_url( $form_location ),
                );

                if( wp_doing_ajax() ){
                    echo json_encode( $data );
                    die();
                } else
                    return $data;
            }

        }

        // Set subscription plan
        if( empty( $this->subscription_plan ) ){

            if( !empty( $payment ) )
                $this->subscription_plan = pms_get_subscription_plan( $payment->subscription_id );
            else if( !empty( $_POST['subscription_plan_id'] ) )
                $this->subscription_plan = pms_get_subscription_plan( absint( $_POST['subscription_plan_id'] ) );

        }

        $is_recurring = PMS_Form_Handler::checkout_is_recurring();

        $subscription = pms_get_member_subscription( $subscription_id );

        if( empty( $subscription->id ) )
            return false;

        if( !empty( $_REQUEST['payment_intent'] ) ){
            // SetupIntent
            if( isset( $_REQUEST['setup_intent'] ) && sanitize_text_field( $_REQUEST['setup_intent'] ) == true ){

                $intent = SetupIntent::retrieve( sanitize_text_field( $_REQUEST['payment_intent'] ) );

                if( $form_location == 'stripe_return_url' ){

                    if( !empty( $intent->metadata->request_location ) )
                        $form_location = $intent->metadata->request_location;

                }

                // Set PaymentMethod
                if( !empty( $intent->customer ) ){

                    // Save Customer and Card for this subscription
                    pms_update_member_subscription_meta( $subscription_id, '_stripe_customer_id', $intent->customer );
                    pms_update_member_subscription_meta( $subscription_id, '_stripe_card_id', $intent->payment_method );

                    // Save Customer to usermeta
                    update_user_meta( $subscription->user_id, 'pms_stripe_customer_id', $intent->customer );

                    $this->update_customer_information( $intent->customer );

                }

                if( !empty( $intent->status ) && in_array( $intent->status, array( 'succeeded', 'processing' ) ) ){

                    // Update subscription
                    $this->update_subscription( $subscription, $form_location, true, $is_recurring );

                    // If subscription had a trial, save card fingerprint
                    $this->save_trial_card( $subscription_id, $intent->payment_method );

                    // Save card expiration info
                    $this->save_payment_method_expiration_data( $subscription_id, $intent->payment_method );

                    do_action( 'pms_stripe_checkout_processed', 'setup_intent', $subscription_id, $payment->id, $form_location );

                    $data = array(
                        'success'      => true,
                        'redirect_url' => $this->get_success_redirect_url( $form_location ),
                    );

                    if( wp_doing_ajax() ){
                        echo json_encode( $data );
                        die();
                    } else {
                        return $data;
                    }

                } else {

                    $data = array(
                        'success'      => false,
                        'redirect_url' => $this->get_payment_error_redirect_url(),
                    );

                    if( wp_doing_ajax() ){
                        echo json_encode( $data );
                        die();
                    } else {
                        return $data;
                    }

                }

            // PaymentIntent
            } else {

                // retrieve intent
                $intent = PaymentIntent::retrieve( sanitize_text_field( $_REQUEST['payment_intent'] ) );

                if( $form_location == 'stripe_return_url' ){

                    if( !empty( $intent->metadata->request_location ) )
                        $form_location = $intent->metadata->request_location;

                }

                // Set PaymentMethod
                if( !empty( $intent->customer ) ){

                    // Save Customer and Card for this subscription
                    pms_update_member_subscription_meta( $subscription_id, '_stripe_customer_id', $intent->customer );
                    pms_update_member_subscription_meta( $subscription_id, '_stripe_card_id', $intent->payment_method );


                    // Save Customer to usermeta
                    update_user_meta( $subscription->user_id, 'pms_stripe_customer_id', $intent->customer );

                    $this->update_customer_information( $intent->customer );

                }

                if( !empty( $intent->status ) && in_array( $intent->status, array( 'succeeded', 'processing' ) ) ){

                    // Complete Payment
                    if( $intent->status == 'succeeded' ){

                        $payment->log_data( 'stripe_intent_confirmed' );
                        $payment->update( array( 'status' => 'completed' ) );

                    } else if ( $intent->status == 'processing' ){

                        $payment->log_data( 'stripe_intent_processing' );

                    }

                    // Update subscription
                    $this->update_subscription( $subscription, $form_location, false, $is_recurring, false, $intent->amount );

                    // If subscription had a trial, save card fingerprint
                    $this->save_trial_card( $subscription_id, $intent->payment_method );

                    // Save card expiration info
                    $this->save_payment_method_expiration_data( $subscription_id, $intent->payment_method );
                    
                    do_action( 'pms_stripe_checkout_processed', 'payment_intent', $subscription_id, $payment->id, $form_location );

                    $data = array(
                        'success'      => true,
                        'redirect_url' => $this->get_success_redirect_url( $form_location ),
                    );

                    if( wp_doing_ajax() ){
                        echo json_encode( $data );
                        die();
                    } else {
                        return $data;
                    }

                /**
                 *
                 */
                } else {

                    $intent_error = $this->parse_intent_last_error( $intent );
                    $error_code   = !empty( $intent_error['data']['decline_code'] ) ? $intent_error['data']['decline_code'] : ( !empty( $intent_error['data']['code'] ) ? $intent_error['data']['code'] : 'card_declined' );

                    $payment->log_data( 'payment_failed', $intent_error, $error_code );
                    $payment->update( array( 'status' => 'failed' ) );

                    $data = array(
                        'success'      => false,
                        'redirect_url' => $this->get_payment_error_redirect_url(),
                    );

                    if( wp_doing_ajax() ){
                        echo json_encode( $data );
                        die();
                    } else {
                        return $data;
                    }

                }
            }
        } else {
            // NOTE: When Apple Pay or Google Pay payment window is closed, Stripe returns an incomplete error, but the status of the payment intent does not change
            // This needs to be treated as an error by the plugin because an account is created regardless if this window is closed or not

            if( wp_doing_ajax() && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'pms_stripe_connect_process_payment' && empty( $_REQUEST['payment_intent'] ) ){

                $data = array(
                    'success'      => false,
                    'redirect_url' => $this->get_payment_error_redirect_url(),
                );

                echo json_encode( $data );
                die();

            }
        }

        // Get the customer and card id from the database
        if( ! empty( $subscription_id ) ) {
            $this->customer_id  = pms_get_member_subscription_meta( $subscription_id, '_stripe_customer_id', true );
            $this->stripe_token = pms_get_member_subscription_meta( $subscription_id, '_stripe_card_id', true );
        }

        if( empty( $this->stripe_token ) )
            return false;

        //if form location is empty, the request is from plugin scheduled payments
        if ( empty( $form_location ) )
            $form_location = 'psp';

        if( !empty( $payment->amount ) ) {
            // create payment intent
            try {
                $metadata = apply_filters( 'pms_stripe_transaction_metadata', array(
                    'payment_id'           => $this->payment_id,
                    'request_location'     => $form_location,
                    'subscription_id'      => $subscription_id,
                    'subscription_plan_id' => $this->subscription_plan->id,
                    'home_url'             => home_url(),
                ), $payment, $form_location );

                $args = apply_filters( 'pms_stripe_process_payment_args', array(
                    'payment_method'      => $this->stripe_token,
                    'customer'            => $this->customer_id,
                    'amount'              => $payment->amount,
                    'currency'            => $this->currency,
                    'confirmation_method' => 'manual',
                    'confirm'             => true,
                    'description'         => $this->subscription_plan->name,
                    'off_session'         => true,
                    'metadata'            => $metadata,
                ));

                $args['amount'] = $this->process_amount( $args['amount'], $args['currency'] );

                $args = self::add_application_fee( $args );

                $intent = PaymentIntent::create( $args );

                $payment->log_data( 'stripe_intent_created' );

                //add transaction ID to payment
                $payment->update( array( 'transaction_id' => $intent->id ) );

                if( !empty( $intent->status ) && in_array( $intent->status, array( 'succeeded', 'processing' ) ) ){

                    $payment->log_data( 'stripe_intent_confirmed' );
                    $payment->update( array( 'status' => 'completed' ) );

                    // If subscription had a trial, save card fingerprint
                    $this->save_trial_card( $subscription_id, $this->stripe_token );

                    // If subscription was started using Stripe Intents or Stripe gateways, update this to connect
                    if( $subscription->payment_gateway != $this->gateway_slug ){

                        $update_data = array(
                            'payment_gateway' => $this->gateway_slug,
                        );

                        $payment->update( $update_data );
                        $subscription->update( $update_data );

                        // TODO: add a log message for this change

                    }

                    return true;

                }

            } catch( Exception $e ) {

                $this->log_error_data( $e );

                $trace = $e->getTrace();

                if ( !empty( $trace[0]['args'][0] ) ) {
                    $error_obj = json_decode( $trace[0]['args'][0] );

                    if( isset( $error_obj->error->code ) && $error_obj->error->code == 'authentication_required' ){
                        pms_add_payment_meta( $payment->id, 'authentication', 'yes' );
                        do_action( 'pms_stripe_send_authentication_email', $payment->user_id, $this->generate_auth_url( $error_obj->error->payment_intent, $payment ), $payment->id );
                    }
                    else
                        $payment->update( array( 'status' => 'failed' ) );

                } else
                    $payment->update( array( 'status' => 'failed' ) );

                return false;

            }
        }

        // if( wp_doing_ajax() )
        //     $this->payment_response( $intent );

        //if we get here, the payment has failed
        return false;
    }

    // Fixes amount issue for zero decimal currencies
    public function process_amount( $amount, $currency = '' ) {

        $zero_decimal_currencies = $this->get_zero_decimal_currencies();

        if( empty( $currency ) )
            $currency = $this->currency;

        if ( !in_array( $currency, $zero_decimal_currencies ) )
            $amount = $amount * 100;

        return round( $amount );

    }

    public function get_zero_decimal_currencies(){
        return array(
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'
        );
    }

    /**
     * Handle Checkout Error redirect after a Stripe payment request
     *
     * @param  object    $subscription   PMS_Member_Subscription object
     * @param  object    $payment        PMS_Payment object, can be empty
     * @return JSON
     */
    public function handle_checkout_error_redirect( $subscription, $payment ){

        if( !wp_doing_ajax() || !( $subscription instanceof PMS_Member_Subscription ) )
            return;

        if( empty( $_POST['pms_stripe_connect_payment_intent'] ) )
            return;

        // Save intent ID to Payment
        $payment_intent_id = explode( '_secret_', sanitize_text_field( $_POST['pms_stripe_connect_payment_intent'] ) );

        if( !empty( $payment ) ){
            $payment->log_data( 'stripe_intent_created' );

            if( !empty( $payment_intent_id[0] ) )
                $payment->update( [ 'transaction_id' => $payment_intent_id[0] ] );
        }

        // Add metadata to Payment or Setup Intent
        if( !empty( $this->secret_key ) ){

            // Set API key
            Stripe::setApiKey( $this->secret_key );

            $form_location = PMS_Form_Handler::get_request_form_location();

            if( empty( $form_location ) && !is_user_logged_in() )
                $form_location = 'register';

            $args = array(
                'metadata' => apply_filters( 'pms_stripe_transaction_metadata', array(
                    'payment_id'           => !empty( $payment ) ? $payment->id : '0',
                    'request_location'     => $form_location,
                    'subscription_id'      => $subscription->id,
                    'subscription_plan_id' => !empty( $_POST['subscription_plans'] ) ? absint( $_POST['subscription_plans'] ) : $subscription->subscription_plan_id,
                    'home_url'             => home_url(),
                    'is_recurring'         => PMS_Form_Handler::checkout_is_recurring(),
                ), $payment, $form_location )
            );

            $subscription_plan = pms_get_subscription_plan( !empty( $_POST['subscription_plans'] ) ? absint( $_POST['subscription_plans'] ) : $subscription->subscription_plan_id );

            $amount = pms_stripe_calculate_payment_amount( $subscription_plan );

            // Setup necessary class data
            if( empty( $this->user_id ) ){
                $this->user_id = $subscription->user_id;
            }

            if( empty( $this->user_email ) ){
                $user = get_userdata( $subscription->user_id );

                $this->user_email = $user->user_email;
            }

            if( ( !PMS_Form_Handler::checkout_has_trial() || ( PMS_Form_Handler::checkout_has_trial() && $subscription_plan->has_sign_up_fee() ) ) && !empty( $payment_intent_id[0] ) && !empty( $amount ) ){

                // Set the initial payment intent ID on the subscription.
                // This is updated each the time user does a manual action on the subscription
                pms_update_member_subscription_meta( $subscription->id, 'pms_stripe_initial_payment_intent', $payment_intent_id[0] );

                // Set Customer if necessary
                try {

                    $payment_intent_data = PaymentIntent::retrieve( $payment_intent_id[0] );

                } catch( Exception $e ){ die(); }

                if( empty( $payment_intent_data->customer ) ){
                    $customer = $this->create_customer();

                    $args['customer'] = $customer->id;
                }

                $this->update_payment_intent( sanitize_text_field( $_POST['pms_stripe_connect_payment_intent'] ), $amount, $subscription_plan );

                try {

                    $payment_intent = PaymentIntent::update( $payment_intent_id[0], $args );

                } catch( Exception $e ){ die(); }

            } else if( !empty( $_POST['pms_stripe_connect_setup_intent'] ) ) {

                $setup_intent_id = explode( '_secret_', sanitize_text_field( $_POST['pms_stripe_connect_setup_intent'] ) );

                // Set the initial payment intent ID on the subscription.
                // This is updated each the time user does a manual action on the subscription
                pms_update_member_subscription_meta( $subscription->id, 'pms_stripe_initial_payment_intent', $setup_intent_id[0] );

                // Set Customer if necessary
                try {

                    $payment_intent_data = SetupIntent::retrieve( $setup_intent_id[0] );

                } catch( Exception $e ){ die(); }

                if( empty( $payment_intent_data->customer ) ){
                    $customer = $this->create_customer();

                    $args['customer'] = $customer->id;
                }

                if( !empty( $setup_intent_id[0] ) ){

                    try {

                        $payment_intent = SetupIntent::update( $setup_intent_id[0], $args );

                    } catch( Exception $e ){ die(); }

                }

            }

        }

        if( isset( $_REQUEST['form_type'] ) && $_REQUEST['form_type'] == 'wppb' ){
            $wppb_general_settings = get_option( 'wppb_general_settings' );

            if( isset( $_REQUEST['send_credentials_via_email'] ) && ( $_REQUEST['send_credentials_via_email'] == 'sending' ) )
                $send_credentials_via_email = 'sending';
            else
                $send_credentials_via_email = '';

            $user = get_userdata( $subscription->user_id );

            // Necessary for the function definition. Filter is added for the Auto-Generate password functionality from PB
            $password = apply_filters( 'pms_stripe_wppb_password', '', $user->ID );

            wppb_notify_user_registration_email( get_bloginfo( 'name' ), $user->user_login, $user->user_email, $send_credentials_via_email, $password, ( wppb_get_admin_approval_option_value() === 'yes' ? 'yes' : 'no' ) );
        }

        $data = array(
            'success'              => true,
            'user_id'              => $subscription->user_id,
            'payment_id'           => !empty( $payment ) ? $payment->id : '0',
            'subscription_id'      => $subscription->id,
            'subscription_plan_id' => $subscription->subscription_plan_id,
        );

        echo json_encode( $data );
        die();

    }

    protected function payment_response( $intent ) {

        if ( $intent->status == 'requires_action' && $intent->next_action->type == 'use_stripe_sdk' ) {

            echo json_encode(array(
                'requires_action'              => true,
                'payment_intent_client_secret' => $intent->client_secret,
                'payment_id'                   => $this->payment_id,
                'form_location'                => PMS_Form_Handler::get_request_form_location()
            ));

        } else if ( $intent->status == 'succeeded' ) {

            echo json_encode(array(
                'success'      => true,
                'redirect_url' => $this->get_success_redirect_url()
            ));

        } else {

            http_response_code(500);
            echo json_encode(array('error' => 'Invalid PaymentIntent status'));

        }

        die();
    }

    /**
     * Similar to PMS_Form_Handler::get_redirect_url(), but with a naked else at the end to cover all the
     * logged out form locations
     *
     * @param  string        $form_location
     * @return string        Success redirect URL
     */
    protected function get_success_redirect_url( $form_location ){

        // Logged in actions that happen on the Account page
        if( in_array( $form_location, array( 'change_subscription', 'upgrade_subscription', 'downgrade_subscription', 'renew_subscription', 'retry_payment' ) ) ){

            $account_page = pms_get_page( 'account', true );
            $redirect_url = !empty( $_POST['current_page'] ) ? esc_url_raw( $_POST['current_page'] ) : '';

            if( empty( $redirect_url ) )
                $redirect_url = $account_page;

            $redirect_url = remove_query_arg( array( 'pms-action', 'subscription_id', 'subscription_plan', 'pmstkn' ), $redirect_url );
            $redirect_url = add_query_arg(
                array(
                    'pmsscscd'                   => base64_encode( 'subscription_plans' ),
                    'pms_gateway_payment_action' => base64_encode( $form_location ),
                    'pms_gateway_payment_id'     => !empty( $this->payment_id ) ? base64_encode( $this->payment_id ) : '',
                ),
            $redirect_url );

        // This uses the register success URL, but without the registration message
        } else if ( in_array( $form_location, array( 'new_subscription' ) ) ){

            $redirect_url = pms_get_register_success_url();

            if( empty( $redirect_url ) && !empty( $_POST['current_page'] ) )
                $redirect_url = esc_url_raw( $_POST['current_page'] );

            $redirect_url = remove_query_arg( array( 'pms-action', 'subscription_id', 'subscription_plan', 'pmstkn' ), $redirect_url );
            $redirect_url = add_query_arg(
                array(
                    'pmsscscd'                   => base64_encode( 'subscription_plans' ),
                    'pms_gateway_payment_action' => base64_encode( $form_location ),
                    'pms_gateway_payment_id'     => !empty( $this->payment_id ) ? base64_encode( $this->payment_id ) : '',
                ),
            $redirect_url );

        // Register success page or current page URL
        } else {

            $redirect_url = pms_get_register_success_url();

            // Add a success message if we should stay on the same page
            if( isset( $_POST['current_page'] ) && ( empty( $redirect_url ) || $redirect_url == $_POST['current_page'] ) ){

                $redirect_url = esc_url_raw( $_POST['current_page'] );

                // WPPB Form
                if( isset( $_REQUEST['form_type'] ) && $_REQUEST['form_type'] == 'wppb' ){

                    $payment = pms_get_payment( $this->payment_id );
                    $user    = get_userdata( $payment->user_id );

                    // On the WPPB Form also take into account Form and Custom Redirects
                    $args = array(
                        'form_type'               => 'register',
                        'form_name'               => isset( $_REQUEST['form_name'] ) ? sanitize_text_field( $_REQUEST['form_name'] ) : '',
                        'form_fields'             => '',
                        'role'                    => get_option( 'default_role' ),
                        'pms_custom_ajax_request' => true,
                    );

                    include_once( WPPB_PLUGIN_DIR . '/front-end/class-formbuilder.php' );

                    $form = new Profile_Builder_Form_Creator( $args );

                    if( ! current_user_can( 'manage_options' ) && $form->args['form_type'] != 'edit_profile' && isset( $_POST['custom_field_user_role'] ) ) {
                        $user_role = sanitize_text_field( $_POST['custom_field_user_role'] );
                    } elseif( ! current_user_can( 'manage_options' ) && $form->args['form_type'] != 'edit_profile' && isset( $form->args['role'] ) ) {
                        $user_role = $form->args['role'];
                    } else {
                        $user_role = NULL;
                    }

                    $wppb_redirect_url = false;

                    if( $form->args['redirect_activated'] == '-' ) {
                        $wppb_redirect_url = wppb_get_redirect_url( $form->args['redirect_priority'], 'after_registration', $form->args['redirect_url'], $user, $user_role );
                    } elseif( $form->args['redirect_activated'] == 'Yes' ) {
                        $wppb_redirect_url = $form->args['redirect_url'];
                    }

                    if( empty( $wppb_redirect_url ) ){
                        $message = apply_filters( 'wppb_register_success_message', sprintf( __( 'The account %1s has been successfully created!', 'paid-member-subscriptions' ), $user->user_login ), $user->user_login );

                        $wppb_admin_approval = wppb_get_admin_approval_option_value();

                        if( $wppb_admin_approval == 'yes' )
                            $message = apply_filters( 'wppb_register_success_message', sprintf( __( 'Before you can access your account %1s, an administrator has to approve it. You will be notified via email.', 'paid-member-subscriptions' ), $user->user_login ), $user->user_login );

                        $redirect_url = add_query_arg( 'pms_wppb_custom_success_message', true, $redirect_url );
                    } else {
                        $redirect_url = $wppb_redirect_url;
                    }

                    // Automatic login
                    if( !empty( $redirect_url ) ){
                        if( !empty( $form->args['login_after_register'] ) && strtolower( $form->args['login_after_register'] ) == 'yes' ){
                            $redirect_url = $this->get_wppb_autologin_url( $redirect_url );
                        }
                    }


                } else
                    $message = apply_filters( 'pms_register_subscription_success_message', __( 'Congratulations, you have successfully created an account.', 'paid-member-subscriptions' ) );

                $redirect_url = add_query_arg( array( 'pmsscscd' => base64_encode( 'subscription_plans' ), 'pmsscsmsg' => base64_encode( $message ) ), $redirect_url );

            // Redirecting to a new page
            } else {

                // Take into account autologin from the WPPB form
                if( isset( $_REQUEST['form_type'] ) && $_REQUEST['form_type'] == 'wppb' ){

                    $args = array(
                        'form_type'               => 'register',
                        'form_name'               => isset( $_REQUEST['form_name'] ) ? sanitize_text_field( $_REQUEST['form_name'] ) : '',
                        'form_fields'             => '',
                        'role'                    => get_option( 'default_role' ),
                        'pms_custom_ajax_request' => true,
                    );

                    include_once( WPPB_PLUGIN_DIR . '/front-end/class-formbuilder.php' );

                    $form = new Profile_Builder_Form_Creator( $args );

                    if( !empty( $form->args['redirect_url'] ) )
                        $redirect_url = $form->args['redirect_url'];

                    if( !empty( $form->args['login_after_register'] ) && strtolower( $form->args['login_after_register'] ) == 'yes' ){
                        $redirect_url = $this->get_wppb_autologin_url( $redirect_url );
                    }

                }

            }

            $redirect_url = add_query_arg(
                array(
                    'pmsscscd'                   => base64_encode( 'subscription_plans' ),
                    'pms_gateway_payment_action' => base64_encode( $form_location ),
                    'pms_gateway_payment_id'     => !empty( $this->payment_id ) ? base64_encode( $this->payment_id ) : '',
                ),
            $redirect_url );

        }

        // Same filter as PMS_Form_Handler::process_checkout()
        return apply_filters( 'pms_get_redirect_url', $redirect_url, $form_location );

    }

    protected function get_wppb_autologin_url( $redirect_url ){

        if( is_user_logged_in() || !function_exists( 'wppb_get_admin_approval_option_value' ) ) {
            return $redirect_url;
        }

        $wppb_general_settings = get_option( 'wppb_general_settings' );

        if ( isset( $wppb_general_settings['emailConfirmation'] ) && ( $wppb_general_settings['emailConfirmation'] == 'yes' ) ) {
            return $redirect_url;
        }

        $payment = pms_get_payment( $this->payment_id );

        if( empty( $payment->user_id ) )
            return $redirect_url;

        $user = get_userdata( $payment->user_id );

        if( !$user )
        return $redirect_url;

        $nonce = wp_create_nonce( 'autologin-'. $user->ID .'-'. (int)( time() / 60 ) );

        if ( wppb_get_admin_approval_option_value() === 'yes' ) {
            if( !empty( $wppb_general_settings['adminApprovalOnUserRole'] ) ) {
                foreach ($user->roles as $role) {
                    if ( in_array( $role, $wppb_general_settings['adminApprovalOnUserRole'] ) ) {
                        return $redirect_url;
                    }
                }
            }
            else {
                return $redirect_url;
            }
        }

        $redirect_url = add_query_arg( array( 'autologin' => 'true', 'uid' => $user->ID, '_wpnonce' => $nonce ), $redirect_url );

		return $redirect_url;

    }

    protected function get_payment_error_redirect_url(){

        $account_page = pms_get_page( 'account', true );

        $pms_is_register = is_user_logged_in() ? 0 : 1;

        $redirect_url = !empty( $_POST['current_page'] ) ? esc_url_raw( $_POST['current_page'] ) : $account_page;
        $redirect_url = apply_filters( 'pms_stripe_error_redirect_url', $redirect_url, $this->payment_id, $pms_is_register );

        return add_query_arg( array( 'pms_payment_error' => '1', 'pms_is_register' => $pms_is_register, 'pms_payment_id' => $this->payment_id ), $redirect_url );

    }

    /**
     * Given a Stripe Object it parses error data and returns it as an array
     *
     * @param object  $intent  Object containing error data. Can be Payment Intent, Setup Intent or a regular Event from a webhook
     * @return array           Error array formatted for other plugin functionalities.
     */
    protected function parse_intent_last_error( $intent ){

        $target_key = 'last_payment_error';

        if( empty( $intent->last_payment_error ) )
            $target_key = 'last_setup_error';

        if( empty( $intent->$target_key ) )
            return array();

        $error = array();

        $error['data'] = array(
            'payment_intent_id' => !empty( $intent->id ) ? $intent->id : '',
            'doc_url'           => !empty( $intent->$target_key->doc_url ) ? $intent->$target_key->doc_url : '',
            'code'              => !empty( $intent->$target_key->code ) ? $intent->$target_key->code : '',
            'decline_code'      => !empty( $intent->$target_key->decline_code ) ? $intent->$target_key->decline_code : '',
        );

        $error['message'] = !empty( $intent->$target_key->message ) ? $intent->$target_key->message : '';
        $error['desc']    = 'stripe response';

        return $error;

    }

    /**
     * Checks if the current $_POST data matches an user with a failed payment and returns the ID of that payment
     *
     * @return boolean
     */
    private function is_failed_payment_request(){
        if( !isset( $_POST['stripe_token'] ) )
            return false;

        if( isset( $_POST['username'] ) ){
            $user  = sanitize_user( $_POST['username'] );
            $field = 'login';
        } else if( isset( $_POST['email'] ) ){
            $user  = sanitize_email( $_POST['email'] );
            $field = 'email';
        } else
            return false;

        $user = get_user_by( $field, $user );

        if( $user === false )
            return false;

        $payments = pms_get_payments( array( 'user_id' => $user->ID, 'status' => 'failed' ) );


        if( !empty( $payments ) && !empty( $payments[0]->id ) )
            return $payments[0]->id;

        return false;
    }

    /**
     * Updates a customers payment method for a subscription based on the data received
     */
    public function update_customer_payment_method( $member_subscription ){

        if( ! isset( $_REQUEST['pmstkn'] ) || ! wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_update_payment_method' ) )
            return false;

        if( empty( $this->secret_key ) )
            return false;

        if( empty( $this->stripe_token ) )
            return false;

        // Set API key
        Stripe::setApiKey( $this->secret_key );

        if( empty( $member_subscription ) || empty( $_REQUEST['stripe_token'] ) )
            return false;

        $customer = $this->get_customer( $member_subscription->user_id );

        if( empty( $customer )  ){
            pms_errors()->add( 'update_payment_method', __( 'Something went wrong, please try again.', 'paid-member-subscriptions' ) );
            return false;
        }

        $success_message = false;

        try {

            $payment_method = PaymentMethod::retrieve( $this->stripe_token );

            $payment_method->attach( [ 'customer' => $customer->id ] );

            pms_update_member_subscription_meta( $member_subscription->id, '_stripe_card_id', $this->stripe_token );

            // Update saved credit card details
            if( !empty( $payment_method->card ) ){

                if( !empty( $payment_method->card->last4 ) )
                    pms_update_member_subscription_meta( $member_subscription->id, 'pms_payment_method_number', $payment_method->card->last4 );

                if( !empty( $payment_method->card->brand ) )
                    pms_update_member_subscription_meta( $member_subscription->id, 'pms_payment_method_type', $payment_method->card->brand );

                if( !empty( $payment_method->card->exp_month ) )
                    pms_update_member_subscription_meta( $member_subscription->id, 'pms_payment_method_expiration_month', $payment_method->card->exp_month );

                if( !empty( $payment_method->card->exp_year ) )
                    pms_update_member_subscription_meta( $member_subscription->id, 'pms_payment_method_expiration_year', $payment_method->card->exp_year );
            }

            pms_add_member_subscription_log( $member_subscription->id, 'subscription_payment_method_updated' );

            $success_message = true;

            do_action( 'pms_payment_method_updated', $member_subscription );

        } catch( Exception $e ) {

            // use pms-errors to write something
            pms_errors()->add( 'update_payment_method', __( 'Something went wrong, please try again.', 'paid-member-subscriptions' ) );

        }

        if( $success_message ){
            $redirect_url = remove_query_arg( array( 'pms-action', 'subscription_plan', 'subscription_id', 'pmstkn' ), pms_get_current_page_url() );

            $redirect_url = add_query_arg( array(
                'pmsscscd'  => base64_encode('update_payment_method'),
                'pmsscsmsg' => base64_encode( __( 'Payment method updated successfully.', 'paid-member-subscriptions' ) ),
            ), $redirect_url );


            wp_redirect( esc_url_raw( $redirect_url ) );
            exit;
        }


    }

    public function create_initial_payment_intent(){

        if( empty( $this->secret_key ) )
            return;

        // Stripe Connect Account
        if( empty( $this->connected_account ) )
            return;

        // Set API key
        Stripe::setApiKey( $this->secret_key );

        // Grab existing Customer if logged-in
        if( is_user_logged_in() )
            $customer = $this->get_customer( get_current_user_id() );

        // if( !isset( $customer ) || !isset( $customer->id ) )
        //     $customer = $this->create_customer();

        $args = array(
            'amount'             => $this->get_initial_intent_amount(),
            'currency'           => apply_filters( 'pms_stripe_connect_create_initial_payment_intent_currency', $this->currency ),
            //'customer'           => $customer->id,
            'setup_future_usage' => 'off_session',
            'metadata'           => array(
                'home_url'             => home_url(),
            ),
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        );

        if( is_user_logged_in() && !empty( $customer->id ) ){
            $args['customer'] = $customer->id;
        }

        // Remove setup future usage when recurring payments are disabled
        // NOTE:  Should explore if we can change this setting through a Payment Intent update. In that case we should update it all the time
        //        based on the data coming from the form and always respect the recurring option
        $payment_settings = get_option( 'pms_payments_settings', false );

        if( isset( $payment_settings['recurring'] ) && $payment_settings['recurring'] == 3 ){
            unset( $args['setup_future_usage'] );
        }

        $args['amount'] = $this->process_amount( $args['amount'], $args['currency'] );

        $args = self::add_application_fee( $args );

        try {

            $intent = PaymentIntent::create( apply_filters( 'pms_stripe_connect_create_initial_payment_intent_args', $args ), array( 'stripe_account' => $this->connected_account ) );

        } catch( Exception $e ){

            return;

        }

        return $intent->client_secret;

    }

    public function create_initial_setup_intent(){

        if( empty( $this->secret_key ) )
            return;

        // Stripe Connect Account
        if( empty( $this->connected_account ) )
            return;

        // Set API key
        Stripe::setApiKey( $this->secret_key );

        // Grab existing Customer if logged-in
        if( is_user_logged_in() )
            $customer = $this->get_customer( get_current_user_id() );

        // if( !isset( $customer ) || !isset( $customer->id ) )
        //     $customer = $this->create_customer();

        $args = array(
            //'customer' => $customer->id,
            'metadata' => array(
                'home_url' => home_url(),
            ),
        );

        if( is_user_logged_in() && !empty( $customer->id ) ){
            $args['customer'] = $customer->id;
        }

        try {

            $intent = \Stripe\SetupIntent::create( $args );

        } catch( Exception $e ){

            return;

        }

        return $intent->client_secret;

    }

    /**
     *
     */
    public function update_payment_intent( $client_secret, $amount, $subscription_plan ){

        if( empty( $this->secret_key ) )
            die();

        if( empty( $client_secret ) || empty( $amount ) || empty( $subscription_plan ) )
            die();

        if( is_user_logged_in() ){
            $user  = get_userdata( get_current_user_id() );
            $email = $user->user_email;
        } else
            $email = isset( $_POST['user_email'] ) ? sanitize_email( $_POST['user_email'] ) : ( isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '' );

        if( !empty( $email ) )
            $this->user_email = $email;

        // Set API key
        Stripe::setApiKey( $this->secret_key );

        $payment_intent_id = explode( '_secret_', $client_secret );

        if( empty( $payment_intent_id[0] ) )
            die();

        $payment_intent_id = $payment_intent_id[0];

        try {

            $payment_intent = PaymentIntent::retrieve( $payment_intent_id );

        } catch( Exception $e ){

            die();

        }

        if( empty( $payment_intent ) )
            die();

        $currency = apply_filters( 'pms_stripe_connect_update_payment_intent_currency', $this->currency, $subscription_plan, $payment_intent );

        $args = array(
            'amount'      => $this->process_amount( $amount, $currency ),
            'description' => !empty( $subscription_plan->name ) ? $subscription_plan->name : '',
        );

        $args = self::add_application_fee( $args );

        try {

            $payment_intent = PaymentIntent::update( $payment_intent_id, apply_filters( 'pms_stripe_connect_update_payment_intent_args', $args, $subscription_plan, $payment_intent ) );

        } catch( Exception $e ){

            die();

        }

        return $payment_intent; // maybe remove all the dies

    }

    public function update_subscription( $subscription, $form_location, $has_trial = false, $is_recurring = false, $plan_id = false, $checkout_amount = false ){

        if( empty( $subscription ) || empty( $form_location ) )
            return false;

        // If this is a subscription renewal, skip processing if it was already processed
        if( $form_location == 'renew_subscription' ){

            $renewal_status = pms_get_member_subscription_meta( $subscription->id, 'pms_subscription_renewal_' . $this->payment_id, true );

            if( $renewal_status == 'finished' )
                return true;
        }

        if( !in_array( $form_location, array( 'register', 'new_subscription', 'retry_payment', 'register_email_confirmation' ) ) ){

            $subscription_plan_id = !empty( $_POST['subscription_plans'] ) ? absint( $_POST['subscription_plans'] ) : false;

            if( empty( $subscription_plan_id ) && !empty( $plan_id ) )
                $subscription_plan_id = $plan_id;
            elseif( empty( $subscription_plan_id ) )
                $subscription_plan_id = $subscription->subscription_plan_id;

            $subscription_plan = pms_get_subscription_plan( $subscription_plan_id );

            $subscription_data = PMS_Form_Handler::get_subscription_data( $subscription->user_id, $subscription_plan, $form_location, true, 'stripe_connect', $is_recurring, $has_trial );

            $checkout_data = array(
                'is_recurring'  => $is_recurring,
                'has_trial'     => $has_trial,
                'form_location' => $form_location
            );

            $subscription_data = apply_filters( 'pms_process_checkout_subscription_data', $subscription_data, $checkout_data );

            $subscription_data['status'] = 'active';

        } else {

            $subscription_data = array(
                'status'         => 'active',
            );

            if( $is_recurring && !empty( $checkout_amount ) ){
                //NOTE: stripe checkout amount is usually in cents, but for some currencies the checkout amount is actually the value that we want to charge
                $zero_decimal_currencies = $this->get_zero_decimal_currencies();

                $currency = pms_get_active_currency();

                if( in_array( $currency, $zero_decimal_currencies ) ){
                    $subscription_data['billing_amount'] = $checkout_amount;
                }
            }

        }

        switch( $form_location ) {

            case 'register':
            // new subscription
            case 'new_subscription':
            // register form E-mail Confirmation compatibility
            case 'register_email_confirmation':
            // retry payment
            case 'retry_payment':

                $log_action = true;

                if( $subscription->status == $subscription_data['status'] )
                    $log_action = false;

                $subscription->update( $subscription_data );

                if( isset( $subscription_data['expiration_date'] ) )
                    $args = array( 'until' => $subscription_data['expiration_date'] );
                else if( isset( $subscription_data['billing_next_payment'] ) )
                    $args = array( 'until' => $subscription_data['billing_next_payment'] );
                else
                    $args = array();

                if( $log_action )
                    pms_add_member_subscription_log( $subscription->id, 'subscription_activated', $args );

                break;

            // upgrading the subscription
            case 'upgrade_subscription':
            // downgrade the subscription
            case 'downgrade_subscription':
            // changing the subscription
            case 'change_subscription':

                $log_action = true;

                if( $subscription->subscription_plan_id == $subscription_data['subscription_plan_id'] )
                    $log_action = false;

                do_action( 'pms_psp_before_'. $form_location, $subscription, isset( $payment ) ? $payment : 0, $subscription_data );

                $context = 'change';

                if( $form_location == 'upgrade_subscription' )
                    $context = 'upgrade';
                elseif( $form_location == 'downgrade_subscription' )
                    $context = 'downgrade';

                if( $log_action )
                    pms_add_member_subscription_log( $subscription->id, 'subscription_'. $context .'_success', array( 'old_plan' => $subscription->subscription_plan_id, 'new_plan' => $subscription_data['subscription_plan_id'] ) );

                $subscription->update( $subscription_data );

                do_action( 'pms_psp_after_'. $form_location, $subscription, isset( $payment ) ? $payment : 0 );

                pms_delete_member_subscription_meta( $subscription->id, 'pms_retry_payment' );

                break;

            case 'renew_subscription':

                if( strtotime( $subscription->expiration_date ) < time() || ( !$subscription_plan->is_fixed_period_membership() && $subscription_plan->duration === 0 ) || ( $subscription_plan->is_fixed_period_membership() && !$subscription_plan->fixed_period_renewal_allowed() ) )
                    $expiration_date = $subscription_plan->get_expiration_date();
                else {
                    if( $subscription_plan->is_fixed_period_membership() ){
                        $expiration_date = date( 'Y-m-d 23:59:59', strtotime( $subscription->expiration_date . '+ 1 year' ) );
                    } else {
                        $expiration_date = date( 'Y-m-d 23:59:59', strtotime( $subscription->expiration_date . '+' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit ) );
                    }
                }

                /**
                 * Filter the new expiration date of a subscription that is processed through PSP
                 */
                $expiration_date = apply_filters( 'pms_checkout_renew_subscription_expiration_date', $expiration_date, $subscription );

                if( $is_recurring ) {
                    $subscription_data['billing_next_payment'] = $expiration_date;
                    $subscription_data['expiration_date']      = '';
                } else {
                    $subscription_data['expiration_date']      = $expiration_date;
                }

                $subscription->update( $subscription_data );

                pms_update_member_subscription_meta( $subscription->id, 'pms_subscription_renewal_' . $this->payment_id, 'finished' );

                pms_add_member_subscription_log( $subscription->id, 'subscription_renewed_manually', array( 'until' => $expiration_date ) );

                pms_delete_member_subscription_meta( $subscription->id, 'pms_retry_payment' );

                break;

            default:
                break;

        }

        do_action( 'pms_after_checkout_is_processed', $subscription, $form_location );

        return true;

    }

    //new
    public function output_form_field_stripe_placeholder( $field = array() ) {

        if( $field['type'] != 'stripe_placeholder' )
            return;

        $id = $field['id'] ? $field['id'] : '';

        if( pms_stripe_connect_get_account_status() ){

            $output = '';

            $output .= '<div class="pms-spinner__holder"><div class="pms-spinner"></div></div>';

            $output .= '<div id="'. esc_attr( $id ) .'" style="display:none"></div>';

        } else
            $output = '<div id="'. esc_attr( $id ) .'">Before you can accept payments, you need to connect your Stripe Account by going to Dashboard -> Paid Member Subscriptions -> Settings -> <a href="'.esc_url( admin_url( 'admin.php?page=pms-settings-page&tab=payments' ) ).'">Payments</a></div>';

        echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    }

    // TODO: add comment, refactor to accept payment method without retrieving it
    public function save_payment_method_expiration_data( $subscription_id, $payment_method ){

        if( empty( $subscription_id ) )
            return;

        if( !empty( $payment_method ) ){

            $payment_method = PaymentMethod::retrieve( $payment_method );

            if( !empty( $payment_method->card ) ){

                if( !empty( $payment_method->card->last4 ) )
                    pms_update_member_subscription_meta( $subscription_id, 'pms_payment_method_number', $payment_method->card->last4 );

                if( !empty( $payment_method->card->brand ) )
                    pms_update_member_subscription_meta( $subscription_id, 'pms_payment_method_type', $payment_method->card->brand );

                if( !empty( $payment_method->card->exp_month ) )
                    pms_update_member_subscription_meta( $subscription_id, 'pms_payment_method_expiration_month', $payment_method->card->exp_month );

                if( !empty( $payment_method->card->exp_year ) )
                    pms_update_member_subscription_meta( $subscription_id, 'pms_payment_method_expiration_year', $payment_method->card->exp_year );
            }

        }

    }

    public function process_webhooks() {

        if( !isset( $_GET['pay_gate_listener'] ) || $_GET['pay_gate_listener'] != 'stripe' )
            return;

        if( function_exists( 'sleep' ) )
            sleep(3);

        // Get the input
        $input = @file_get_contents("php://input");
        $event = json_decode( $input );

        // make sure live mode webhooks are processed in live mode
        if( !empty( $event->livemode ) ){

            $api_credentials  = pms_stripe_connect_get_api_credentials();
            $this->secret_key = ( !empty( $api_credentials['secret_key'] ) ? $api_credentials['secret_key'] : '' );

        }

        // Set API key
        \Stripe\Stripe::setApiKey( $this->secret_key );

        $event_id = sanitize_text_field( $event->id );

        // Verify that the event was sent by Stripe
        if( isset( $event->id ) ) {

            try {
                \Stripe\Event::retrieve( $event_id );
            } catch( Exception $e ) {
                die();
            }

        } else
            die();

        // add an option that we later use to tell the admin that webhooks are configured
        update_option( 'pms_stripe_connect_webhook_connection', strtotime( 'now' ) );

        switch( $event->type ) {
            case 'payment_intent.succeeded':

                $data = $event->data->object;

                if( !empty( $data->metadata->home_url ) ){
                    if( $data->metadata->home_url != home_url() )
                        die();
                }

                $payment_id = isset( $data->metadata->payment_id ) ? absint( $data->metadata->payment_id ) : 0;

                if ( $payment_id === 0 )
                    die();

                $payment = pms_get_payment( $payment_id );

                $payment->log_data( 'stripe_webhook_received', array( 'event_id' => $event_id, 'event_type' => 'payment_intent.succeeded', 'data' => $data->metadata ) );

                if( $payment->status != 'completed' ){
                    $payment->log_data( 'stripe_intent_confirmed' );
                    $payment->update( array( 'status' => 'completed' ) );
                }

                // process subscription
                $this->webhooks_process_subscription( $payment, $data );

                break;
            case 'payment_intent.processing':

                $data = $event->data->object;

                if( !empty( $data->metadata->home_url ) ){
                    if( $data->metadata->home_url != home_url() )
                        die();
                }

                $payment_id = isset( $data->metadata->payment_id ) ? absint( $data->metadata->payment_id ) : 0;

                if ( $payment_id === 0 )
                    die();

                $payment = pms_get_payment( $payment_id );

                $payment->log_data( 'stripe_webhook_received', array( 'event_id' => $event_id, 'event_type' => 'payment_intent.processing', 'data' => $data->metadata ) );

                if( $payment->status != 'completed' )
                    $payment->log_data( 'stripe_intent_processing' );

                // process subscription
                $this->webhooks_process_subscription( $payment, $data );

                break;

            case 'payment_intent.payment_failed':

                $data = $event->data->object;

                if( !empty( $data->metadata->home_url ) ){
                    if( $data->metadata->home_url != home_url() )
                        die();
                }

                $payment_id = isset( $data->metadata->payment_id ) ? absint( $data->metadata->payment_id ) : 0;

                if ( $payment_id === 0 )
                    die();

                $payment = pms_get_payment( $payment_id );

                $payment->log_data( 'stripe_webhook_received', array( 'event_id' => $event_id, 'event_type' => 'payment_intent.payment_failed', 'data' => $data->metadata ) );

                if( $payment->status == 'failed' )
                    die();

                $payment->log_data( 'payment_failed', $this->parse_intent_last_error( $data ) );

                $payment->update( array( 'status' => 'failed' ) );

                // Update subscription
                $member_subscription = pms_get_member_subscription( $payment->member_subscription_id );

                if( !in_array( $member_subscription->status, array( 'abandoned', 'pending' ) ) )
                    $member_subscription->update( array( 'status' => 'expired' ) );

                break;

            case 'setup_intent.succeeded':

                $data = $event->data->object;

                if( !empty( $data->metadata->home_url ) ){
                    if( $data->metadata->home_url != home_url() )
                        die();
                }

                $subscription_id = isset( $data->metadata->subscription_id ) ? absint( $data->metadata->subscription_id ) : 0;

                if ( $subscription_id === 0 )
                    die();

                $member_subscription = pms_get_member_subscription( $subscription_id );

                // update subscription
                if( $member_subscription->status != 'active' && !empty( $data->metadata->request_location ) ){

                    $this->update_subscription( $member_subscription, sanitize_text_field( $data->metadata->request_location ), false, sanitize_text_field( $data->metadata->is_recurring ) );

                }

                if( !empty( $data->latest_charge ) ){

                    // Set correct payment method for SEPA Direct Debit recurring transactions.
                    // The initial charge can be made through iDEAL for example, but for subsequent charges, the generated SEPA Debit payment method needs to be used
                    $payment_method = $this->get_alternative_payment_method( $data->latest_charge );

                    if( !empty( $payment_method ) )
                        pms_update_member_subscription_meta( $member_subscription->id, '_stripe_card_id', sanitize_text_field( $payment_method ) );

                }

                break;

            case 'setup_intent.setup_failed':

                $data = $event->data->object;

                if( !empty( $data->metadata->home_url ) ){
                    if( $data->metadata->home_url != home_url() )
                        die();
                }

                $subscription_id = isset( $data->metadata->subscription_id ) ? absint( $data->metadata->subscription_id ) : 0;

                if ( $subscription_id === 0 )
                    die();

                $member_subscription = pms_get_member_subscription( $subscription_id );

                pms_add_member_subscription_log( $member_subscription->id, 'stripe_webhook_setup_intent_failed', $this->parse_intent_last_error( $data ) );

                break;

            case 'charge.refunded':

                //get payment id from metadata
                $data = $event->data->object;

                if( !empty( $data->metadata->home_url ) ){
                    if( $data->metadata->home_url != home_url() )
                        die();
                }

                $payment_id = isset( $data->metadata->payment_id ) ? absint( $data->metadata->payment_id ) : 0;

                if( $payment_id === 0 )
                    die();

                $payment = pms_get_payment( $payment_id );

                if( $payment->status != 'completed' )
                    die();

                $payment->log_data( 'stripe_webhook_received', array( 'event_id' => $event_id, 'event_type' => 'charge.refunded' ) );

                $payment->log_data( 'stripe_charge_refunded', array( 'data' => $data->metadata ) );

                $payment->update( array( 'status' => 'refunded' ) );

                $member_subscription = pms_get_member_subscription( $payment->member_subscription_id );

                if( !empty( $member_subscription ) ){

                    if( in_array( $member_subscription->status, array( 'active', 'canceled' ) ) ){
                        $member_subscription->update( array( 'status' => 'expired' ) );

                        pms_add_member_subscription_log( $member_subscription->id, 'stripe_webhook_subscription_expired' );
                    }

                }

                break;

            default:
                break;
        }

        die();

    }

    private function webhooks_process_subscription( $payment, $data ){

        if( empty( $payment ) || empty( $payment->member_subscription_id ) )
            return;

        if( !empty( $data->metadata->request_location ) ){

            $subscription = pms_get_member_subscription( $payment->member_subscription_id );

            $this->payment_id = $payment->id;

            $is_recurring = !empty( $data->metadata->is_recurring ) && $data->metadata->is_recurring == 'true' ? true : false;

            $this->update_subscription( $subscription, sanitize_text_field( $data->metadata->request_location ), false, $is_recurring, $payment->subscription_id );

            // Save Customer to Subscription and User if it's not present
            $subscription_customer = pms_get_member_subscription_meta( $subscription->id, '_stripe_customer_id', true );
            $customer              = get_user_meta( $subscription->user_id, 'pms_stripe_customer_id', true );

            if( empty( $subscription_customer ) ){

                if( empty( $customer ) ){
                    $customer = sanitize_text_field( $data->customer );

                    update_user_meta( $subscription->user_id, 'pms_stripe_customer_id', $customer );
                }

                pms_update_member_subscription_meta( $subscription->id, '_stripe_customer_id', $customer );

            }

            $payment_method = !empty( $data->payment_method ) ? $data->payment_method : '';

            // Update subscription Payment Method
            if( !empty( $data->latest_charge ) ){

                // Set correct payment method for SEPA Direct Debit recurring transactions.
                // The initial charge can be made through iDEAL for example, but for subsequent charges, the generated SEPA Debit payment method needs to be used
                $alternative_payment_method = $this->get_alternative_payment_method( $data->latest_charge );

                if( !empty( $alternative_payment_method ) )
                    $payment_method = $alternative_payment_method;

            }


            if( !empty( $payment_method ) ){
                pms_update_member_subscription_meta( $subscription->id, '_stripe_card_id', sanitize_text_field( $payment_method ) );

                $this->save_payment_method_expiration_data( $subscription->id, $payment_method );
            }

        }

    }

    /**
     * This method checks the payment methods of a charge and if it's different than card,
     * it returns the payment method that can be used for future payments
     */
    private function get_alternative_payment_method( $latest_charge ){

        // We always expect one charge per payment intent
        if( empty( $latest_charge ) )
            return false;

        $charge = Charge::retrieve( $latest_charge );

        if( empty( $charge->payment_method_details ) )
            return false;

        $payment_method_details = $charge->payment_method_details;

        if( empty( $payment_method_details->type ) || $payment_method_details->type == 'card' )
            return false;

        // We always expect one charge per payment intent
        $payment_method_type = $payment_method_details->type;

        if( empty( $payment_method_details->$payment_method_type ) || empty( $payment_method_details->$payment_method_type->generated_sepa_debit ) )
            return false;

        return $payment_method_details->$payment_method_type->generated_sepa_debit;

    }

    // Nonces and other additions to the form
    /**
     * Display Stripe's publishable key field in the form
     *
     */
    public function field_publishable_key( $output, $pms_settings, $return = true ) {

        $api_credentials = pms_stripe_connect_get_api_credentials();

        if( !empty( $api_credentials['publishable_key'] ) )
            $output .= '<input type="hidden" id="stripe-pk" value="' . esc_attr( $api_credentials['publishable_key'] ) . '" />';

        if( $return )
            return $output;
        else
            echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    }

    /**
     * Add Publishable Key to the Update Payment Method form
     */
    public function update_payment_form_field_publishable_key(){

        $this->field_publishable_key( '', get_option( 'pms_payments_settings' ), false );

    }

    /**
     * Add Payment Intent nonce to form
     *
     * @param  string   $output
     * @param  array    $pms_settings
     * @return string
     */
    public function field_ajax_nonces( $output, $pms_settings ) {

        // Add Payment Intent Client Secret to form
        //$payment_intent = $this->create_initial_payment_intent();
        //$setup_intent   = $this->create_initial_setup_intent();

        // $payment_intent = '';
        // $setup_intent   = '';

        // if( !empty( $payment_intent ) )
        $output .= '<input type="hidden" name="pms_stripe_connect_payment_intent" value=""/>';

        //if( !empty( $setup_intent ) )
        $output .= '<input type="hidden" name="pms_stripe_connect_setup_intent" value=""/>';

        // process checkout nonce
        $output .= '<input type="hidden" id="pms-stripe-ajax-payment-intent-nonce" name="stripe_ajax_payment_intent_nonce" value="'. esc_attr( wp_create_nonce( 'pms_process_checkout' ) ) .'"/>';

        // update payment intent nonce
        $output .= '<input type="hidden" id="pms-stripe-ajax-update-payment-intent-nonce" name="stripe_ajax_update_payment_intent_nonce" value="'. esc_attr( wp_create_nonce( 'pms_stripe_connect_update_payment_intent' ) ) .'"/>';

        return $output;

    }

    /**
     * Add Update Payment Method nonce to form
     *
     * @param  string   $output
     * @param  array    $pms_settings
     * @return string
     */
    public function field_update_payment_method_nonce() {

        $setup_intent = $this->create_initial_setup_intent();

        if( !empty( $setup_intent ) )
            echo '<input type="hidden" name="pms_stripe_connect_setup_intent" value="'. esc_attr( $setup_intent ) .'"/>';

        echo '<input type="hidden" id="pms-stripe-ajax-update-payment-method-nonce" name="stripe_ajax_update_payment_method_nonce" value="'. esc_attr( wp_create_nonce( 'pms_update_payment_method' ) ) .'"/>';

    }

    // Extra fields
    /**
     * Register the Credit Card and Billing Details sections
     *
     * @param array  $sections
     * @param string $form_location
     *
     */
    public static function register_form_sections( $sections = array(), $form_location = '' ) {

        if( ! in_array( $form_location, array( 'register', 'new_subscription', 'upgrade_subscription', 'renew_subscription', 'retry_payment', 'change_subscription', 'update_payment_method_stripe_connect', 'update_payment_method_stripe_intents' ) ) )
            return $sections;

        // Add the credit card details if it does not exist
        if( empty( $sections['credit_card_information'] ) ) {

            $sections['credit_card_information'] = array(
                'name'    => 'credit_card_information',
                'element' => 'ul',
                'id'      => 'pms-credit-card-information',
                'class'   => 'pms-credit-card-information pms-section-credit-card-information'
            );

        }

        return $sections;

    }


    /**
     * Register the Credit Card and Billing Fields to the checkout forms
     *
     * @param array $fields
     *
     * @return array
     *
     */
    public static function register_form_fields( $fields = array(), $form_location = '' ) {

        if( ! in_array( $form_location, array( 'register', 'new_subscription', 'upgrade_subscription', 'renew_subscription', 'retry_payment', 'change_subscription', 'update_payment_method_stripe_connect', 'update_payment_method_stripe_intents' ) ) )
            return $fields;


        /**
         * Add the Credit Card fields
         *
         */
        $fields['pms_credit_card_heading'] = array(
            'section'         => 'credit_card_information',
            'type'            => 'heading',
            'default'         => '<h4>' . __( 'Payment Details', 'paid-member-subscriptions' ) . '</h4>',
            'element_wrapper' => 'li',
        );

        $fields['pms_credit_card_wrapper'] = array(
            'section' => 'credit_card_information',
            'type'    => 'stripe_placeholder',
            'id'      => 'pms-stripe-payment-elements'
        );

        return $fields;

    }

    private function add_application_fee( $args ){

        if( empty( $args ) || empty( $args['amount'] ) )
            return $args;

        $account_country      = pms_stripe_connect_get_account_country();
        $restricted_countries = array(
            'AG', 'AL', 'AM', 'AO', 'AR', 'AZ', 'BA', 'BB', 'BD', 'BF', 'BH', 'BJ', 'BN', 'BO', 'BR', 'BS', 'BT', 'BW', 'BZ', 'CI',
            'CL', 'CO', 'CR', 'CV', 'DJ', 'DM', 'DO', 'DZ', 'EC', 'EG', 'ET', 'FJ', 'FM', 'GA', 'GD', 'GE', 'GH', 'GM', 'GN', 'GQ',
            'GT', 'GY', 'HN', 'ID', 'IL', 'IN', 'IS', 'JM', 'JO', 'KE', 'KG', 'KH', 'KI', 'KR', 'KW', 'KZ', 'LA', 'LC', 'LK', 'LS',
            'MA', 'MC', 'MD', 'ME', 'MG', 'MH', 'MK', 'MN', 'MO', 'MR', 'MU', 'MW', 'MX', 'MY', 'MZ', 'NA', 'NE', 'NG', 'OM', 'PA',
            'PE', 'PG', 'PH', 'PK', 'PY', 'QA', 'RS', 'RW', 'SA', 'SB', 'SC', 'SL', 'SM', 'SN', 'SR', 'SV', 'TG', 'TH', 'TJ', 'TL',
            'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ', 'UY', 'UZ', 'VC', 'VN', 'WS', 'ZA', 'ZM' );

        if( in_array( $account_country, $restricted_countries ) )
            return $args;

        $serial_number        = pms_get_serial_number();
        $serial_number_status = pms_get_serial_number_status();

        $fee_percentage = ( empty( $serial_number ) || $serial_number_status != 'valid' ) ? 2 : 0;

        $args['application_fee_amount'] = floor( $args['amount'] * round( floatval( $fee_percentage ), 2 ) / 100 );

        return $args;

    }

    // Random Functionalities
    /**
     * Save current payment method fingerprint so it can't be used to access a trial for the
     * given subscription's subscription plan
     *
     * @param  int   $subscription_id   Subscription ID
     * @return void
     */
    public function save_trial_card( $subscription_id, $payment_method ){

        if( empty( $payment_method ) || empty( $subscription_id ) )
            return;

        $member_subscription = pms_get_member_subscription( $subscription_id );

        if( !empty( $member_subscription->subscription_plan_id ) ){

            $subscription_plan = pms_get_subscription_plan( $member_subscription->subscription_plan_id );

            if( !empty( $subscription_plan->trial_duration ) ){

                $plan_fingerprints = get_option( 'pms_used_trial_cards_' . $subscription_plan->id, false );
                $payment_method    = PaymentMethod::retrieve( $payment_method );

                if( !empty( $payment_method->card->fingerprint ) ){
                    if( $plan_fingerprints == false )
                        $plan_fingerprints = array( $payment_method->card->fingerprint );
                    else
                        $plan_fingerprints[] = $payment_method->card->fingerprint;
                }

                update_option( 'pms_used_trial_cards_' . $subscription_plan->id, $plan_fingerprints, false );

            }

        }

    }

    /**
     * Determines if trial is valid for the current request subscription plans and payment method
     *
     * Hook: pms_checkout_has_trial
     *
     * @param  boolean
     * @return boolean
     */
    public function disable_trial_if_duplicate_card( $has_trial ){

        if( $has_trial == false || apply_filters( 'pms_disable_trial_if_duplicate_card', false ) )
            return $has_trial;

        // Disable when payments are in test mode
        if( pms_is_payment_test_mode() )
            return $has_trial;

        // Skip if token is not for a payment method
        if( empty( $_POST['stripe_token'] ) || empty( $_POST['subscription_plans'] ) || strpos( sanitize_text_field( $_POST['stripe_token'] ), 'pm_' ) === false )
            return $has_trial;

        $plan = pms_get_subscription_plan( absint( $_POST['subscription_plans'] ) );

        if( empty( $plan->id ) )
            return $has_trial;

        if( empty( $this->secret_key ) )
            return $has_trial;

        // Set API key
        Stripe::setApiKey( $this->secret_key );

        $payment_method = PaymentMethod::retrieve( $this->stripe_token );

        if( empty( $payment_method->card->fingerprint ) )
            return $has_trial;

        $used_cards = get_option( 'pms_used_trial_cards_' . $plan->id, false );

        if( empty( $used_cards ) )
            return $has_trial;

        if( in_array( $payment_method->card->fingerprint, $used_cards ) )
            return false;

        return $has_trial;

    }

    public function log_error_data( $exception ) {

        if ( empty( $exception ) ) return;

        $payment = new PMS_Payment( $this->payment_id );

        if ( !method_exists( $payment, 'log_data' ) )
            return;

        $trace = $exception->getTrace();

        //If there's no error code in the exception, use a generic one
        $error_code = 'card_declined';

        $data = array();

        if ( !empty( $trace[0]['args'][0] ) ) {
            $error_obj = json_decode( $trace[0]['args'][0] );

            if( isset( $error_obj->error->payment_intent->id ) ){
                $intent_id = $error_obj->error->payment_intent->id;

                $payment->update( array( 'transaction_id' => $error_obj->error->payment_intent->id ) );
            }

            // generate data array
            if( isset( $error_obj->error ) ){
                $data['data'] = array(
                    'charge_id'         => !empty( $error_obj->error->charge ) ? $error_obj->error->charge : '',
                    'code'              => !empty( $error_obj->error->code ) ? $error_obj->error->code : '',
                    'decline_code'      => !empty( $error_obj->error->decline_code ) ? $error_obj->error->decline_code : '',
                    'doc_url'           => !empty( $error_obj->error->doc_url ) ? $error_obj->error->doc_url : '',
                    'payment_intent_id' => !empty( $error_obj->error->payment_intent->id ) ? $error_obj->error->payment_intent->id : '',
                );
            }

            if ( !empty( $error_obj->error->decline_code ) )
                $error_code = $error_obj->error->decline_code;
            else if ( !empty( $error_obj->error->code ) )
                $error_code = $error_obj->error->code;
        }

        $data['message'] = $exception->getMessage();
        $data['desc']    = 'stripe response';

        $payment->log_data( 'payment_failed', $data, $error_code );
    }

    public function set_account_country( $environment ){

        if( empty( $this->secret_key ) )
            return false;

        // set API key
        Stripe::setApiKey( $this->secret_key );

        try {

            $account = Account::retrieve();

        } catch ( Exception $e ) {

            return false;

        }

        if ( empty( $account ) || empty( $account->country ) )
            return false;

        update_option( 'pms_stripe_connect_account_country_' . $environment, $account->country );

        return $account;

    }

    // Apple Pay, Google Pay, Link
    public function domain_is_registered(){

        if( empty( $this->secret_key ) )
            return false;

        // set API key
        $stripe = new \Stripe\StripeClient( $this->secret_key );

        if( empty( $stripe->paymentMethodDomains ) )
            return false;

        // get domains
        try {

            $domains = $stripe->paymentMethodDomains->all();

        } catch ( Exception $e ) {

            return false;

        }

        if( empty( $domains ) )
            return false;

        $current_domain = false;
        $home_url       = home_url();

        // verify if domain exists
        foreach( $domains as $domain ) {

            if ( !empty( $home_url ) && $domain->domain_name === $home_url ){
                $current_domain = $domain;
                break;
            }

        }

        if( empty( $current_domain ) ){
            $current_domain = $this->register_domain();
        }

        // check if domain is validated with Apple Pay
        if( $current_domain->apple_pay->status != 'active' ){
            $current_domain = $stripe->paymentMethodDomains->validate( $current_domain->id );
        }

        if( $current_domain->enabled == true )
            return true;

        return false;

    }

    public function register_domain(){

        if( empty( $this->secret_key ) )
            return false;

        // set API key
        $stripe = new \Stripe\StripeClient( $this->secret_key );

        // Stripe expects a base url here without a path, so for multisite with subdirectories for example, we need to remove the directory
        $target_url = wp_parse_url( home_url() );
        $target_url = $target_url['scheme'] . '://' . $target_url['host'];

        try {

            $domain = $stripe->paymentMethodDomains->create( array(
                'domain_name' => $target_url,
            ) );

        } catch ( Exception $e ) {

            return false;

        }

        if( !empty( $domain->id ) )
            $stripe->paymentMethodDomains->validate( $domain->id );

        return $domain;

    }

    //authentication stuff
    protected function generate_auth_url( $intent, $payment ){
        $account_page = pms_get_page( 'account', true );

        //@TODO: add a notice in this case (use an option)
        if( empty( $account_page ) )
            return '';

        $url = add_query_arg( array(
            'pms-action'    => 'authenticate_stripe_payment',
            'pms-intent-id' => $intent->id
        ), $account_page );

        return $url;
    }

    // Profile Builder
    /**
     * Remove success message wrappers from profile builder register form and add
     * payment failed hook
     *
     * @return void
     */
    public function wppb_success_message_wrappers() {

        $payment_id = $this->is_failed_payment_request();

        if( $payment_id !== false ){
            $this->payment_id = $payment_id;

            add_filter( 'wppb_form_message_tpl_start',   '__return_empty_string' );
            add_filter( 'wppb_form_message_tpl_end',     '__return_empty_string' );
            add_filter( 'wppb_register_success_message', array( $this, 'wppb_handle_failed_payment' ) );
        }

    }

    /**
     * Display payment failed error message
     *
     * Hook: wppb_register_success_message
     *
     * @param  string   $content
     * @return function pms_in_stripe_error_message
     */
    public function wppb_handle_failed_payment( $content ){

        return pms_stripe_error_message( $content, 1, $this->payment_id );

    }

    // Customer
    /*
     * Returns the Stripe customer if it exists based on the user_id provided
     *
     * @param int $user_id
     *
     */
    public function get_customer( $user_id = 0 ) {

        if( $user_id == 0 )
            $user_id = $this->user_id;

        // Set API key
        Stripe::setApiKey( $this->secret_key );

        try {

            // Get saved Stripe ID
            $customer_stripe_id = get_user_meta( $user_id, 'pms_stripe_customer_id', true );

            // Return if the customer id is missing
            if( empty( $customer_stripe_id ) ){

                // Try to find customer by Email address
                $user = get_userdata( $user_id );

                $customers = Customer::all( [ 'email' => $user->user_email, 'limit' => 1 ] );

                if( empty( $customers ) )
                    return false;

                if( isset( $customers->data[0] ) && !empty( $customers->data[0]->id ) )
                    $customer_stripe_id = $customers->data[0]->id;
            }

            // Get customer
            $customer = Customer::retrieve( $customer_stripe_id );

            // If empty name on the Stripe Customer try to add it from the website
            if( apply_filters( 'pms_stripe_update_customer_name', true ) && empty( $customer->name ) ){

                $name = $this->get_user_name( $user_id );

                if( !empty( $name ) ){
                    Customer::update(
                        $customer_stripe_id,
                        array(
                            'name' => $name
                        )
                    );
                }

            }

            if( isset( $customer->deleted ) && $customer->deleted == true )
                return false;
            else
                return $customer;

        } catch( Exception $e ) {
            return false;
        }

    }

    protected function create_customer() {

        // Set API key
        Stripe::setApiKey( $this->secret_key );

        if( empty( $this->connected_account ) )
            return false;

        try {

            $customer = Customer::create( array(
                'email'       => !empty( $this->user_email ) ? $this->user_email : '',
                'description' => !empty( $this->user_id ) ? 'User ID: ' . $this->user_id : '',
                'name'        => !empty( $this->user_id ) ? $this->get_user_name( $this->user_id ) : '',
                'address'     => $this->get_billing_details(),
                'metadata'    => !empty( $this->user_id ) ? array( 'user_id' => $this->user_id ) : array(),
            ), array( 'stripe_account' => $this->connected_account ) );

            // Save Stripe customer ID
            if( !empty( $this->user_id ) )
                update_user_meta( $this->user_id, 'pms_stripe_customer_id', $customer->id );

            return $customer;

        } catch( Exception $e ) {

            $this->log_error_data( $e );

            return false;

        }

    }

    protected function update_customer_information( $customer ){

        // Add Customer information
        if( !empty( $_POST['user_id'] ) && !empty( $customer ) ){

            $user = get_user_by( 'ID', absint( $_POST['user_id'] ) );

            if( !is_wp_error( $user ) ){

                $customer_data = array(
                    'email'       => !empty( $user->user_email ) ? $user->user_email : '',
                    'description' => 'User ID: ' . $user->ID,
                    'name'        => $this->get_user_name( $user->ID ),
                    'address'     => $this->get_usermeta_billing_details( $user->ID ),
                    'metadata'    => array( 'user_id' => $user->ID ),
                );

                try {

                    Customer::update(
                        $customer,
                        $customer_data
                    );

                    return true;

                } catch( Exception $e ) {

                    return false;

                }

            }

        }

        return false;

    }

    protected function get_initial_intent_amount(){

        $plans = pms_get_subscription_plans();

        $amount = 100;

        if( !empty( $plans ) ){
            foreach( $plans as $plan ){
                if( !empty( $plan->price ) && $plan->price > 1 ){
                    $amount = $plan->price;
                    break;
                }
            }
        }

        return $amount;

    }

    // LEGACY CLASS ADDITIONS
    /**
     * Send software information to Stripe with each request
     */
    private function set_appinfo() {
        Stripe::setAppInfo(
          "Paid Member Subscriptions (WordPress)",
           PMS_VERSION,
           "https://www.cozmoslabs.com/",
           "pp_partner_Fk2RgE0VrGkLiR"
        );
    }

    public function get_user_name( $user_id ){
        $user = get_userdata( $user_id );

        if( empty( $user ) )
            return '';

        $name = !empty( $user->first_name ) ? $user->first_name . ' ' : '';
        $name .= !empty( $user->last_name ) ? $user->last_name : '';

        return $name;
    }

    /**
     * Checks if billing info is available in $_POST and returns an array with all the info
     * The array is ready to use with the Stripe API (see Customer -> Shipping field)
     */
    public function get_billing_details() {

        if( empty( $_POST ) )
            return array();

        $billing_details = array();

        $keys = array(
            'line1'       => 'pms_billing_address',
            'city'        => 'pms_billing_city',
            'postal_code' => 'pms_billing_zip',
            'country'     => 'pms_billing_country',
            'state'       => 'pms_billing_state'
        );

        foreach( $keys as $stripe_key => $pms_key ) {
            if( !empty( $_POST[$pms_key] ) )
                $billing_details[$stripe_key] = sanitize_text_field( $_POST[$pms_key] );
        }

        return $billing_details;

    }

    /**
     * Checks if billing info is available in USERMETA and returns an array with all the info
     * The array is ready to use with the Stripe API (see Customer -> Shipping field)
     */
    public function get_usermeta_billing_details( $user_id ) {

        if( empty( $user_id ) )
            return array();

        $billing_details = array();

        $keys = array(
            'line1'       => 'pms_billing_address',
            'city'        => 'pms_billing_city',
            'postal_code' => 'pms_billing_zip',
            'country'     => 'pms_billing_country',
            'state'       => 'pms_billing_state'
        );

        foreach( $keys as $stripe_key => $pms_key ) {

            $meta_value = get_user_meta( $user_id, $pms_key, true );

            if( !empty( $meta_value ) )
                $billing_details[$stripe_key] = $meta_value;
        }

        return $billing_details;

    }
    // END
}