<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

Class PMS_Payment_Gateway_PayPal_Connect extends PMS_Payment_Gateway {

    /**
     * The discount code being used on checkout
     *
     * @access protected
     * @var string
     *
     */
    protected $discount = false;

    /**
     * The PayPal API client secret 
     *
     * @access protected
     * @var string
     *
     */
    protected $client_secret;

    /**
     * The PayPal API client ID
     *
     * @access protected
     * @var string
     *
     */
    protected $client_id;

    /**
     * The PayPal Merchant ID
     *
     * @access protected
     * @var string
     *
     */
    protected $merchant_id;

    /**
     * Environment
     *
     * @access protected
     * @var string
     *
     */
    protected $environment;

    /**
     * The gateway slug
     *
     * @access public
     * @var string
     *
     */
    public $gateway_slug = 'paypal_connect';

    /**
     * The Partner ID
     *
     * @access protected
     * @var string
     *
     */
    protected $partner_id;

    /**
     * Instance
     *
     * @access private
     * @var object
     *
     */
    private static $instance = null;

    /**
     * The PayPal API endpoint
     *
     * @access protected
     * @var string
     *
     */
    protected $endpoint;

    /** 
     * Initialisation
     *
     */
    public function __construct() {

        parent::__construct();

        $this->supports = array(
            'plugin_scheduled_payments',
            'recurring_payments',
            'subscription_sign_up_fee',
            'subscription_free_trial',
            'change_subscription_payment_method_admin',
            'update_payment_method',
        );

        $this->endpoint    = $this->test_mode ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

        $this->environment = $this->test_mode ? 'test' : 'live';

        $this->partner_id  = pms_ppcp_get_platform_partner_id();

        // don't add any hooks if the gateway is not active
        if( !in_array( $this->gateway_slug, pms_get_active_payment_gateways() ) )
            return;

        // Set API secret key
        $api_credentials     = pms_ppcp_get_api_credentials();
        $this->client_id     = ( !empty( $api_credentials['client_id'] ) ? $api_credentials['client_id'] : '' );
        $this->client_secret = ( !empty( $api_credentials['client_secret'] ) ? $api_credentials['client_secret'] : '' );
        $this->merchant_id   = ( !empty( $api_credentials['payer_id'] ) ? $api_credentials['payer_id'] : '' );


        // Set discount
        if( !empty( $_POST['discount_code'] ) && function_exists( 'pms_in_get_discount_by_code' ) )
            $this->discount = pms_in_get_discount_by_code( sanitize_text_field( $_POST['discount_code'] ) );

        if( empty( $this->payment_id ) && isset( $_POST['payment_id'] ) )
            $this->payment_id = (int)$_POST['payment_id'];

        if( empty( $this->form_location ) ){
            if( isset( $_POST['form_location'] ) )
                $this->form_location = sanitize_text_field( $_POST['form_location'] );
            else {
                $target              = isset( $_REQUEST['pmstkn_original'] ) ? 'pmstkn_original' : 'pmstkn';
                $this->form_location = PMS_Form_Handler::get_request_form_location( $target );
            }
        }

        // Add the needed sections for the checkout forms
        add_filter( 'pms_extra_form_sections', array( __CLASS__, 'register_form_sections' ), 60, 2 );

        // Add the needed form fields for the checkout forms
        add_filter( 'pms_extra_form_fields',   array( __CLASS__, 'register_form_fields' ), 60, 2 );

        // Add Form Fields placeholder
        add_action( 'pms_output_form_field_paypal_connect_placeholder', array( $this, 'output_form_field_paypal_connect_placeholder')  );

        // When checkout is validated via AJAX, we need to also send to front-end either an Order or Setup Intent ID
        add_action( 'pms_ajax_checkout_validated', array( $this, 'handle_order_creation_when_checkout_is_validated' ) );

        // Add a custom action so the payment gateway can add it's own output to the account subscription details table for the Payment Method column
        add_action( 'pms_account_subscription_details_table_payment_method_content', array( $this, 'output_account_subscription_details_table_payment_method_content' ) );

        // Add Update Payment Method ajax request nonce to form
        add_action( 'pms_update_payment_method_form_bottom', array( $this, 'update_payment_method_form_content' ), 20 );

        // Process PayPal Update Payment Method request
        add_action( 'pms_update_payment_method_paypal_connect', array( $this, 'update_customer_payment_method' ) );

        add_action( 'pms_after_checkout_is_processed', array( $this, 'remove_old_payment_method_details_from_subscription' ), 20, 2 );

    }
    
    public static function get_instance() {

        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;

    }

    /**
     * If a vault setup token is provided, create the payment token and save it for future transactions
     *
     * @param int $member_subscription_id
     *
     * @return bool
     */
    public function register_automatic_billing_info( $member_subscription_id = 0 ) {

        if( empty( $member_subscription_id ) )
            return false;

        // If a vault setup token is provided, create the payment token and save it for future transactions
        if( !empty( $_POST['paypal_vault_setup_token'] ) && !empty( $member_subscription_id ) ){

            $setup_token_id = sanitize_text_field( $_POST['paypal_vault_setup_token'] );

            // Create payment token from setup token
            $payment_token = $this->create_payment_token( $setup_token_id );

            if ( empty( $payment_token ) )
                return false;

            $subscription = pms_get_member_subscription( $member_subscription_id );

            // Store payment token data in subscription meta
            if ( !empty( $payment_token['id'] ) )
                pms_update_member_subscription_meta( $member_subscription_id, '_paypal_vault_id', $payment_token['id'] );

            if ( !empty( $payment_token['customer']['id'] ) ){
                pms_update_member_subscription_meta( $member_subscription_id, '_paypal_customer_id', sanitize_text_field( $payment_token['customer']['id'] ) );

                update_user_meta( $subscription->user_id, 'pms_paypal_customer_id', sanitize_text_field( $payment_token['customer']['id'] ) );
            }

            // Store PayPal Email address to be able to tell the user which account is used for the payment
            if( !empty( $payment_token['payment_source']['paypal']['email_address'] ) )
                pms_update_member_subscription_meta( $member_subscription_id, '_paypal_payee_email', sanitize_text_field( $payment_token['payment_source']['paypal']['email_address'] ) );
    
            // Log the successful token creation
            pms_add_member_subscription_log( $member_subscription_id, 'paypal_payment_token_created' );

        }

        // This function always returns true due to the logic that's written inside PMS_Form_Handler::process_checkout()
        // Basically, it expects to first use this function to register the billing info and then continue with the process_payment() function if necessary
        // Since we need to do the capture inside process_payment() when this is not a free trial checkout, this function needs to pass all the time
        return true;

    }

    public function process_payment( $payment_id = 0, $subscription_id = 0 ) {

        if( $payment_id != 0 )
            $this->payment_id = $payment_id;

        $payment = pms_get_payment( $this->payment_id );

        if( isset( $payment->status ) && $payment->status == 'completed' ){

            // If the payment is completed because the webhook was received already we don't want to touch it
            // But the payment can also be completed when a 100% discount code is used and in that scenario we need to continue
            if( empty( $_REQUEST['discount_code'] ) || ( !empty( $_REQUEST['discount_code'] ) && $payment->amount != 0 ) ){
                $data = array(
                    'success'      => true,
                    'redirect_url' => PMS_AJAX_Checkout_Handler::get_success_redirect_url( $this->form_location, $payment->id ),
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

        // If an order ID is provided, start order processing
        if( !empty( $_REQUEST['paypal_order_id'] ) ){
            $paypal_order_id = sanitize_text_field( $_REQUEST['paypal_order_id'] );

            if( !empty( $payment ) ){

                // Save order_id as payment meta
                pms_add_payment_meta( $payment->id, 'paypal_order_id', $paypal_order_id );

                // Save checkout data from $_POST to the payment
                // This is used for Webhooks if they need to update the subscription
                $target_keys = array(
                    'subscription_plans',
                    'pms_default_recurring',
                    'discount_code',
                    'pms_billing_address',
                    'pms_billing_city',
                    'pms_billing_zip',
                    'pms_billing_country',
                    'pms_billing_state',
                    'pms_vat_number',
                    'form_type',
                    'pms_current_subscription',
                );

                if( !empty( $_POST['subscription_plans'] ) )
                    $target_keys[] = sprintf( 'subscription_price_%s', absint( $_POST['subscription_plans'] ) );

                $checkout_data = array();

                foreach( $_POST as $key => $value ){
                    if( in_array( $key, $target_keys ) )
                        $checkout_data[$key] = $value;
                }

                $checkout_data['form_location'] = $this->form_location;
                $checkout_data['is_recurring']  = $is_recurring;
                $checkout_data['has_trial']     = false;

                pms_add_payment_meta( $payment->id, 'pms_checkout_data', $checkout_data );

                // Set Payment ID as custom ID for the order
                $this->update_order( $paypal_order_id, [ 'custom_id' => $payment->id, 'invoice_id' => $this->get_invoice_id_for_paypal( $payment->id ) ] );
                
                // Capture the order
                $order = $this->capture_order( $paypal_order_id );

                // Order status can be Completed when the capture isn't, we need to check the capture
                $order_capture = $this->get_captured_payment( $order );

                if( $order_capture !== false && $order_capture['status'] == 'COMPLETED' ){

                    $payment->update( array(
                        'status'         => 'completed',
                        'transaction_id' => $this->get_transaction_id( $order ) )
                    );

                    // Save payment method
                    if( !empty( $order['payment_source']['paypal'] ) && !empty( $order['payment_source']['paypal']['attributes'] ) ){
                        $attributes = $order['payment_source']['paypal']['attributes'];

                        if( !empty( $attributes['vault'] ) ){
                            $vault = $attributes['vault'];

                            // Vault is not always present, in that case we save it later through a webhook
                            if( !empty( $vault['status'] ) && $vault['status'] == 'VAULTED' ){

                                if( !empty( $vault['id'] ) )
                                    pms_update_member_subscription_meta( $subscription_id, '_paypal_vault_id', sanitize_text_field( $vault['id'] ) );
                            
                                if( !empty( $vault['customer'] ) && !empty( $vault['customer']['id'] ) ){
                                    pms_update_member_subscription_meta( $subscription_id, '_paypal_customer_id', sanitize_text_field( $vault['customer']['id'] ) );

                                    update_user_meta( $subscription->user_id, 'pms_paypal_customer_id', sanitize_text_field( $vault['customer']['id'] ) );
                                }

                            }

                        }
                    }

                    // Store PayPal Email address to be able to tell the user which account is used for the payment
                    if( !empty( $order['payment_source']['paypal']['email_address'] ) )
                        pms_update_member_subscription_meta( $subscription_id, '_paypal_payee_email', sanitize_text_field( $order['payment_source']['paypal']['email_address'] ) );

                    // Update subscription
                    // @TODO: Remove this call, let the main form handler update the subscripiton, but you need to do some extra stuff that's inside this function; function needs to remain for webhooks
                    //$this->update_subscription( $subscription, $this->form_location, false, $is_recurring );

                    // $data = array(
                    //     'success'      => true,
                    //     'redirect_url' => PMS_AJAX_Checkout_Handler::get_success_redirect_url( $this->form_location, $payment->id ),
                    // );

                    return true;


                } else {

                    // $data = array(
                    //     'success'      => false,
                    //     'redirect_url' => PMS_AJAX_Checkout_Handler::get_payment_error_redirect_url( $payment->id ),
                    // );

                    return false;

                }

            }
        }

        // PSP RECURRING
        // Get the customer and card id from the database
        if( ! empty( $subscription_id ) ) {
            $customer_id = pms_get_member_subscription_meta( $subscription_id, '_paypal_customer_id', true );
            $vault_id    = pms_get_member_subscription_meta( $subscription_id, '_paypal_vault_id', true );
        }

        if( empty( $customer_id ) || empty( $vault_id ) )
            return false;

        //if form location is empty, the request is from plugin scheduled payments
        if ( empty( $this->form_location ) )
            $this->form_location = 'psp';

        if( !empty( $payment->amount ) ) {

            $payment->log_data( 'paypal_psp_order_created' );
            
            // Create order
            $order = $this->create_order( $this->subscription_plan, $payment, $vault_id );

            // Order status can be Completed when the capture isn't, we need to check the capture
            $order_capture = $this->get_captured_payment( $order );

            if( $order_capture !== false && $order_capture['status'] == 'COMPLETED' ){

                $payment->log_data( 'paypal_psp_order_completed' );
                $payment->update( array( 'status' => 'completed', 'transaction_id' => $this->get_transaction_id( $order ) ) );

                return true;

            } else {

                $payment->log_data( 'paypal_psp_order_failed', array( 'order' => $order ) );
                $payment->update( array( 'status' => 'failed', 'transaction_id' => $this->get_transaction_id( $order ) ) );

                return false;

            }

        }

        // payment has failed
        return false;

    }

    /**
     * Update a subscription
     *
     * @param object $subscription The subscription object
     * @param string $form_location The form location
     * @param bool $has_trial Whether the subscription has a trial
     * @param bool $is_recurring Whether the subscription is recurring
     * @param array $checkout_data The checkout data
     *
     * @return bool True on success, false on failure
     */
    public function update_subscription( $subscription, $form_location, $has_trial = false, $is_recurring = false, $checkout_data = array() ){

        if( empty( $subscription ) || empty( $form_location ) )
            return false;

        if( !in_array( $form_location, array( 'register', 'new_subscription', 'retry_payment', 'register_email_confirmation' ) ) ){

            $subscription_plan_id = !empty( $_POST['subscription_plans'] ) ? absint( $_POST['subscription_plans'] ) : false;

            if( empty( $subscription_plan_id ) && !empty( $checkout_data['subscription_plans'] ) )
                $subscription_plan_id = $checkout_data['subscription_plans'];
            elseif( empty( $subscription_plan_id ) )
                $subscription_plan_id = $subscription->subscription_plan_id;

            $subscription_plan = pms_get_subscription_plan( $subscription_plan_id );

            $subscription_data = PMS_Form_Handler::get_subscription_data( $subscription->user_id, $subscription_plan, $form_location, true, $this->gateway_slug, $is_recurring, $has_trial );

            $checkout_data['is_recurring']  = $is_recurring;
            $checkout_data['has_trial']     = $has_trial;
            $checkout_data['form_location'] = $form_location;

            $subscription_data = apply_filters( 'pms_process_checkout_subscription_data', $subscription_data, $checkout_data );

            $subscription_data['status'] = 'active';

            // Billing amount needs to be recalculated with all the modifiers that can apply
            $subscription_data['billing_amount'] = pms_calculate_payment_amount( $subscription_plan, $checkout_data, true );

        } else {

            $subscription_data = array(
                'status'         => 'active',
            );

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

                pms_add_member_subscription_log( $subscription->id, 'subscription_renewed_manually', array( 'until' => $expiration_date ) );

                pms_delete_member_subscription_meta( $subscription->id, 'pms_retry_payment' );

                break;

            default:
                break;

        }

        // Clear the member subscriptions payment method details
        // PayPal is saving it's own information and these need to be cleared so they don't display in the member subscription details
        $targets = array( 'pms_payment_method_number', 'pms_payment_method_type', 'pms_payment_method_expiration_month', 'pms_payment_method_expiration_year' );

        foreach( $targets as $target ){
            pms_delete_member_subscription_meta( $subscription->id, $target );
        }

        return true;

    }

    function remove_old_payment_method_details_from_subscription( $subscription, $form_location ){

        if( empty( $subscription ) || $subscription->payment_gateway != $this->gateway_slug )
            return;
            
        // Clear the member subscriptions payment method details
        // PayPal is saving it's own information and these need to be cleared so they don't display in the member subscription details
        $targets = array( 'pms_payment_method_number', 'pms_payment_method_type', 'pms_payment_method_expiration_month', 'pms_payment_method_expiration_year' );

        foreach( $targets as $target ){
            pms_delete_member_subscription_meta( $subscription->id, $target );
        }

    }

    /**
     * Process PayPal webhooks
     */
    public function process_webhooks() {

        if( !isset( $_GET['pay_gate_listener'] ) || $_GET['pay_gate_listener'] != 'paypal_connect' )
            return;

        if( function_exists( 'sleep' ) )
            sleep(3);

        if( !$this->verify_webhook() ){
            die();
        }

        // Get the input
        $input = @file_get_contents( "php://input" );
        $event = json_decode( $input );

        if ( empty( $event ) ){
            die();
        }
        
        // add an option that we later use to tell the admin that webhooks are configured
        update_option( 'pms_paypal_connect_webhook_connection', strtotime( 'now' ) );

        if( empty( $event->event_type ) )
            die();

        $event_type = sanitize_text_field( $event->event_type );

        switch( $event_type ) {
            case 'PAYMENT.CAPTURE.COMPLETED':

                $resource = $event->resource;

                if( empty( $resource ) )
                    die();

                $payment_id = isset( $resource->custom_id ) ? absint( $resource->custom_id ) : 0;

                $payment = pms_get_payment( $payment_id );

                if( empty( $payment ) )
                    die();

                $payment->log_data( 'paypal_webhook_received', array( 'event_id' => $event->id, 'event_type' => $event_type, 'data' => $this->parse_webhook_response( $event ) ) );

                if( $payment->status != 'completed' ){

                    $payment->update( array( 'status' => 'completed', 'transaction_id' => $resource->id ) );

                    // If initial payment was not completed, we update the subscription as well
                    if( !empty( $payment->member_subscription_id ) ){
                        $subscription  = pms_get_member_subscription( $payment->member_subscription_id );
                        $checkout_data = pms_get_payment_meta( $payment->id, 'pms_checkout_data', true );
    
                        $this->update_subscription( $subscription, $checkout_data['form_location'], $checkout_data['has_trial'], $checkout_data['is_recurring'], $checkout_data );
                    }
                }
                
                break;
            case 'PAYMENT.CAPTURE.REFUNDED': // Refunded means that the merchant has refunded the payment to the customer
            case 'PAYMENT.CAPTURE.REVERSED': // Reversed means that the payment was reversed due to a dispute or fraud

                $resource = $event->resource;

                if( empty( $resource ) )
                    die();

                $payment_id = isset( $resource->custom_id ) ? absint( $resource->custom_id ) : 0;

                $payment = pms_get_payment( $payment_id );

                if( empty( $payment ) )
                    die();

                $payment->log_data( 'paypal_webhook_received', array( 'event_id' => $event->id, 'event_type' => $event_type, 'data' => $this->parse_webhook_response( $event ) ) );

                if( $payment->status != 'refunded' ){

                    $payment->log_data( 'paypal_transaction_refunded' );

                    $payment->update( array( 'status' => 'refunded' ) );
                    
                    $pms_settings = get_option( 'pms_misc_settings', array() );

                    // Maybe update subscription
                    if( !isset( $pms_settings['gateway-refund-behavior'] ) || $pms_settings['gateway-refund-behavior'] != 1 ){

                        if( !empty( $payment->member_subscription_id ) ){
                            $subscription = pms_get_member_subscription( $payment->member_subscription_id );

                            if( !empty( $subscription ) ){
                                pms_add_member_subscription_log( $subscription->id, 'paypal_webhook_subscription_expired' );

                                $subscription_data = array(
                                    'status'                => 'expired',
                                    'billing_next_payment'  => '',
                                    'billing_duration'      => 0,
                                    'billing_duration_unit' => '',
                                );

                                $subscription->update( $subscription_data );
                            }
                        }

                    }
                }
                
                break;
            case 'PAYMENT.CAPTURE.DENIED':

                $resource = $event->resource;

                if( empty( $resource ) )
                    die();

                $payment_id = isset( $resource->custom_id ) ? absint( $resource->custom_id ) : 0;

                $payment = pms_get_payment( $payment_id );

                if( empty( $payment ) )
                    die();

                $payment->log_data( 'paypal_webhook_received', array( 'event_id' => $event->id, 'event_type' => $event_type, 'data' => $this->parse_webhook_response( $event ) ) );

                if( $payment->status != 'failed' ){
                    $payment->update( array( 'status' => 'failed' ) );
                }
                
                break;
            case 'VAULT.PAYMENT-TOKEN.DELETED':

                $resource = $event->resource;

                if( empty( $resource ) )
                    die();

                $vault_id = isset( $resource->id ) ? sanitize_text_field( $resource->id ) : '';

                if( empty( $vault_id ) )
                    die();

                $subscription = $this->get_subscription_by_vault_id( $vault_id );

                if( empty( $subscription->id ) )
                    die();

                pms_add_member_subscription_log( $subscription->id, 'paypal_webhook_payment_token_deleted' );

                $subscription->update( [ 'status' => 'canceled', 'billing_duration' => 0, 'billing_duration_unit' => '', 'billing_next_payment' => '' ] );
                
                break;
            case 'VAULT.PAYMENT-TOKEN.CREATED':

                $resource = $event->resource;

                if( empty( $resource ) )
                    die();

                $vault_id = isset( $resource->id ) ? sanitize_text_field( $resource->id ) : '';

                if( empty( $vault_id ) )
                    die();

                $order_id = isset( $resource->metadata ) && isset( $resource->metadata->order_id ) ? sanitize_text_field( $resource->metadata->order_id ) : '';

                if( empty( $order_id ) )
                    die();

                $payment = $this->get_payment_from_order_id( $order_id );

                if( empty( $payment ) )
                    die();

                $payment->log_data( 'paypal_webhook_received', array( 'event_id' => $event->id, 'event_type' => $event_type, 'data' => $this->parse_webhook_response( $event ) ) );

                // Save the vault id and customer id to the subscription
                if( !empty( $payment->member_subscription_id ) ){
                    $subscription = pms_get_member_subscription( $payment->member_subscription_id );

                    if( !empty( $subscription ) ){

                        if( !empty( $vault_id ) )
                            pms_update_member_subscription_meta( $subscription->id, '_paypal_vault_id', sanitize_text_field( $vault_id ) );
                    
                        if( !empty( $resource->customer ) && !empty( $resource->customer->id ) ){
                            pms_update_member_subscription_meta( $subscription->id, '_paypal_customer_id', sanitize_text_field( $resource->customer->id ) );

                            update_user_meta( $subscription->user_id, 'pms_paypal_customer_id', sanitize_text_field( $resource->customer->id ) );
                        }

                        pms_add_member_subscription_log( $subscription->id, 'paypal_webhook_payment_token_created' );

                    }
                }

                break;
            default:
                break;
        }

        die();

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

        if( ! in_array( $form_location, array( 'register', 'new_subscription', 'upgrade_subscription', 'renew_subscription', 'retry_payment', 'change_subscription', 'update_payment_method_paypal_connect' ) ) )
            return $sections;

        // Add an extra section to the form to hold the PayPal Connect placeholder
        if( empty( $sections['paypal_connect'] ) ) {

            $sections['paypal_connect'] = array(
                'name'    => 'paypal_connect',
                'element' => 'ul',
                'id'      => 'pms-paypal-connect',
                'class'   => 'pms-paygate-extra-fields pms-paygate-extra-fields-paypal_connect'
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

        if( ! in_array( $form_location, array( 'register', 'new_subscription', 'upgrade_subscription', 'renew_subscription', 'retry_payment', 'change_subscription', 'update_payment_method_paypal_connect' ) ) )
            return $fields;

        /**
         * Add PayPal Connect placeholder
         *
         */
        $fields['pms_paypal_connect_wrapper'] = array(
            'section' => 'paypal_connect',
            'type'    => 'paypal_connect_placeholder',
            'id'      => 'pms-paygate-extra-fields-paypal_connect__placeholder',
        );

        return $fields;

    }

    public function output_form_field_paypal_connect_placeholder( $field = array() ) {

        if( $field['type'] != 'paypal_connect_placeholder' )
            return;

        $id = isset( $field['id'] ) ? $field['id'] : '';

        if( !empty( $this->client_id ) && !empty( $this->client_secret ) ){
            $output = '<div class="pms-spinner__holder"><div class="pms-spinner"></div></div>';
            $output .= '<div id="'. esc_attr( $id ) .'"></div>';
        } else {
            $output = '<div id="'. esc_attr( $id ) .'">Before you can accept payments, you need to connect your PayPal account by going to Dashboard -> Paid Member Subscriptions -> Settings -> Payments -> <a href="'.esc_url( admin_url( 'admin.php?page=pms-settings-page&tab=payments&nav_sub_tab=payments_gateways' ) ).'">Gateways</a>.</div>';
        }

        echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    }

    public function handle_order_creation_when_checkout_is_validated(){

        if( !isset( $_POST['pay_gate'] ) || $_POST['pay_gate'] != $this->gateway_slug )
            return;

        if( !isset( $_POST['subscription_plans'] ) || !isset( $_POST['paypal_action_nonce'] ) )
            return;

        $subscription_plan = pms_get_subscription_plan( absint( $_POST['subscription_plans'] ) );

        if( empty( $subscription_plan ) || empty( $subscription_plan->id ) )
            return;

        if( wp_verify_nonce( sanitize_text_field( $_POST['paypal_action_nonce'] ), 'pms_ppcp_create_order' ) ){

            $data = array(
                'success'  => true,
                'order_id' => $this->create_order( $subscription_plan )
            );

            echo json_encode( $data );

        } else if( wp_verify_nonce( sanitize_text_field( $_POST['paypal_action_nonce'] ), 'pms_ppcp_create_setup_token' ) ){

            $data = array(
                'success'        => true,
                'setup_token_id' => $this->create_setup_token( $subscription_plan )
            );

            echo json_encode( $data );

        }

        die();

    }

    public function update_payment_method_form_content( $member_subscription ){

        if( empty( $member_subscription->payment_gateway ) || $member_subscription->payment_gateway != $this->gateway_slug )
            return;

        $paypal_email = pms_get_member_subscription_meta( $member_subscription->id, '_paypal_payee_email', true );

        if( !empty( $paypal_email ) )
            echo '<p>'. sprintf( esc_html__( 'The email address of the PayPal account currently used for billing is: %s', 'paid-member-subscriptions' ), '<strong>'. esc_html( $paypal_email ) .'</strong>' ) .'</p>';

        echo '<p>'. esc_html__( 'Click the button below to update your payment method.', 'paid-member-subscriptions' ) .'</p>';

        echo '<input type="hidden" id="pms-paypal-update-payment-method-nonce" name="paypal_update_payment_method_nonce" value="'. esc_attr( wp_create_nonce( 'pms_update_payment_method' ) ) .'"/>';
        echo '<input type="hidden" id="pms-process-checkout-nonce" name="pms_process_checkout_nonce" value="'. esc_attr( wp_create_nonce( 'pms_process_checkout' ) ) .'"/>';

    }

    /**
     * This function is used to update the payment method for a member subscription.
     * It is called through AJAX using the pms_process_checkout action hook.
     * 
     * @param object $member_subscription The member subscription object
     * 
     * @return void
     */
    public function update_customer_payment_method( $member_subscription ){

        if( !isset( $_REQUEST['pmstkn'] ) || ! wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_update_payment_method' ) )
            return;

        if( empty( $member_subscription ) || empty( $_REQUEST['paypal_vault_setup_token'] ) )
            return;

        $setup_token_id = isset( $_POST['paypal_vault_setup_token'] ) ? sanitize_text_field( $_POST['paypal_vault_setup_token'] ) : '';

        // Create payment token from setup token
        $payment_token = $this->create_payment_token( $setup_token_id );

        if ( empty( $payment_token ) )
            return;

        // Store payment token data in subscription meta
        if( !empty( $payment_token['id'] ) )
            pms_update_member_subscription_meta( $member_subscription->id, '_paypal_vault_id', $payment_token['id'] );

        if( !empty( $payment_token['customer']['id'] ) )
            pms_update_member_subscription_meta( $member_subscription->id, '_paypal_customer_id', sanitize_text_field( $payment_token['customer']['id'] ) );

        // Store PayPal Email address to be able to tell the user which account is used for the payment
        if( !empty( $payment_token['payment_source']['paypal']['email_address'] ) )
            pms_update_member_subscription_meta( $member_subscription->id, '_paypal_payee_email', sanitize_text_field( $payment_token['payment_source']['paypal']['email_address'] ) );

        pms_add_member_subscription_log( $member_subscription->id, 'subscription_payment_method_updated' );

        do_action( 'pms_payment_method_updated', $member_subscription );

        $redirect_url = PMS_AJAX_Checkout_Handler::get_success_redirect_url( 'update_payment_method' );
        $redirect_url = remove_query_arg( array( 'pms-action', 'subscription_id', 'pmstkn' ), $redirect_url );

        $redirect_url = add_query_arg( array(
            'pmsscscd'  => base64_encode('update_payment_method'),
            'pmsscsmsg' => base64_encode( __( 'Payment method updated successfully.', 'paid-member-subscriptions' ) ),
        ), $redirect_url );

        if( wp_doing_ajax() ){

            echo json_encode( array(
                'success' => true,
                'redirect_url' => $redirect_url
            ) );
            die();

        } else {

            wp_redirect( esc_url_raw( $redirect_url ) );
            exit;

        }

    }

    // // Random Functionalities
    // Profile Builder
    /**
     * Remove success message wrappers from profile builder register form and add
     * payment failed hook
     *
     * @return void
     */
    // public function wppb_success_message_wrappers() {

    //     $payment_id = $this->is_failed_payment_request();

    //     if( $payment_id !== false ){
    //         $this->payment_id = $payment_id;

    //         add_filter( 'wppb_form_message_tpl_start',   '__return_empty_string' );
    //         add_filter( 'wppb_form_message_tpl_end',     '__return_empty_string' );
    //         add_filter( 'wppb_register_success_message', array( $this, 'wppb_handle_failed_payment' ) );
    //     }

    // }

    // /**
    //  * Display payment failed error message
    //  *
    //  * Hook: wppb_register_success_message
    //  *
    //  * @param  string   $content
    //  * @return function pms_in_stripe_error_message
    //  */
    // public function wppb_handle_failed_payment( $content ){

    //     return pms_stripe_error_message( $content, 1, $this->payment_id );

    // }

    // PPCP ADDITIONS 
    /**
     * Onboard a PayPal merchant
     * 
     * @param string $shared_id The shared ID
     * @param string $auth_code The authorization code
     * @param string $seller_nonce The seller nonce
     */
    public function onboard_paypal_merchant( $shared_id, $auth_code, $seller_nonce ){
 
        // Aquire onboarding access token
        $request_url = $this->endpoint . '/v1/oauth2/token';
    
        $auth_header = base64_encode("$shared_id:");
    
        $body = [
            'grant_type'    => 'authorization_code',
            'code'          => $auth_code,
            'code_verifier' => $seller_nonce
        ];
    
        $headers = [
            'Authorization' => 'Basic ' . $auth_header,
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ];
    
        $response = wp_remote_post($request_url, [
            'headers' => $headers,
            'body'    => http_build_query($body),
            'timeout' => 10,
        ]);
    
        // Check if the request was successful
        if (is_wp_error($response)) {
            die( json_encode( array(
                'success' => false,
                'message' => 'Something went wrong. Please try again.'
            ) ) );
        }
    
        // Decode the JSON response
        $response_body    = wp_remote_retrieve_body($response);
        $decoded_response = json_decode($response_body, true);

    	$access_token = !empty( $decoded_response['access_token'] ) ? sanitize_text_field( $decoded_response['access_token'] ) : '';

        if ( empty( $access_token ) ) {
            die( json_encode( array(
                'success' => false,
                'message' => 'Something went wrong. Please try again.'
            ) ) );
        }

        // Generate request to get REST API credentials
        $request_url = $this->endpoint . '/v1/customer/partners/' . $this->partner_id . '/merchant-integrations/credentials';

        $args = array(
            'headers' => array(
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Authorization' => 'Bearer ' . $access_token
            ),
        );

        $response = wp_remote_get( $request_url, $args );

        if ( is_wp_error( $response ) ) {
            die( json_encode( array(
                'success' => false,
                'message' => 'Something went wrong. Please try again.'
            ) ) );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if( !empty( $data['client_id'] ) ){
            update_option( 'pms_paypal_connect_'. $this->environment .'_client_id', sanitize_text_field( $data['client_id'] ) );
            $this->client_id = sanitize_text_field( $data['client_id'] );
        }

        if( !empty( $data['client_secret'] ) ){
            update_option( 'pms_paypal_connect_'. $this->environment .'_client_secret', sanitize_text_field( $data['client_secret'] ) );
            $this->client_secret = sanitize_text_field( $data['client_secret'] );
        }
        
        if( !empty( $data['payer_id'] ) )
            update_option( 'pms_paypal_connect_'. $this->environment .'_payer_id', sanitize_text_field( $data['payer_id'] ) );

        // Get Access Token
        $this->refresh_access_token();

        $this->register_webhooks();

        // Set PayPal as active gateway
        $payments_settings = get_option( 'pms_payments_settings', array() );

        // Remove other PayPal gateways from the active gateways array
        if( !empty( $payments_settings['active_pay_gates'] ) && is_array( $payments_settings['active_pay_gates'] ) ){
            foreach( $payments_settings['active_pay_gates'] as $key => $gateway ){

                if( in_array( $gateway, array( 'paypal_express', 'paypal_standard' ) ) )
                    unset( $payments_settings['active_pay_gates'][$key] );

            }
        }

        if( !isset( $payments_settings['active_pay_gates'] ) ){
            $payments_settings['active_pay_gates']   = array();
            $payments_settings['active_pay_gates'][] = 'paypal_connect';
        } else if( !in_array( 'paypal_connect', $payments_settings['active_pay_gates'] ) ){
            $payments_settings['active_pay_gates'][] = 'paypal_connect';
        }

        update_option( 'pms_payments_settings', $payments_settings );

        die( json_encode( array(
            'success' => true,
            'message' => 'PayPal successfully connected!'
        ) ) );

    }

    /**
     * Disconnect a PayPal merchant
     *
     * @param $paypal_credentials
     */
    public function disconnect_paypal_merchant( $paypal_credentials ) {

        $environment = pms_is_payment_test_mode() ? 'test' : 'live';

        $credentials_data = array(
            'pms_paypal_connect_' . $environment . '_client_id',
            'pms_paypal_connect_' . $environment . '_client_secret',
            'pms_paypal_connect_' . $environment . '_payer_id'
        );

        // Remove credentials
        foreach ( $credentials_data as $data ) {
            delete_option( $data );
        }

        die( json_encode( array(
            'success' => true,
            'message' => 'PayPal successfully disconnected!'
        ) ) );

    }
    
    /**
     * Refresh the PayPal access token using client credentials
     * 
     * @return string|bool The access token on success, false on failure
     */
    private function refresh_access_token() {

        if( empty( $this->client_id ) || empty( $this->client_secret ) )
            return false;

        $request_url = $this->endpoint . '/v1/oauth2/token';
        
        // Create authorization header
        $auth_header = base64_encode( $this->client_id . ':' . $this->client_secret );
        
        $args = array(
            'method'  => 'POST',
            'headers' => array(
                'Authorization' => 'Basic ' . $auth_header,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body'    => 'grant_type=client_credentials',
            'timeout' => 10,
        );

        $response = wp_remote_post( $request_url, $args );

        // We generate the access token here but even if it fails, we don't stop execution because we can generate it later
        if( !is_wp_error( $response ) ) {

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );

            if( !empty( $data['access_token'] ) ) {

                $token = [
                    'access_token' => $data['access_token'],
                    'expires_in'   => time() + $data['expires_in']
                ];

                // Store the access token and its expiration
                update_option( 'pms_paypal_connect_'. $this->environment .'_access_token', $token );

                return $data['access_token'];

            }

        }

        return false;

    }

    /**
     * Get the PayPal access token, refreshing it if expired
     * 
     * @return string|bool The access token on success, false on failure
     */
    private function get_access_token() {

        $token_data = get_option( 'pms_paypal_connect_'. $this->environment .'_access_token' );

        // If we have a valid token that's not expired, return it
        if( !empty( $token_data ) && is_array( $token_data ) ) {
            if( !empty( $token_data['access_token'] ) && !empty( $token_data['expires_in'] ) && time() < $token_data['expires_in'] ) {
                return $token_data['access_token'];
            }
        }

        // Token is either missing, invalid format, or expired - get a new one
        return $this->refresh_access_token();

    }

    /**
     * Get the merchant integration status from PayPal
     * 
     * @return array|bool Merchant status data on success, false on failure
     */
    public function get_merchant_status() {

        $access_token = $this->get_access_token();
        
        if( empty( $access_token ) )
            return false;
        
        if( empty( $this->merchant_id ) )
            return false;

        $request_url = $this->endpoint . '/v1/customer/partners/' . $this->partner_id . '/merchant-integrations/' . $this->merchant_id;

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json'
            )
        );

        $response = wp_remote_get( $request_url, $args );

        if( is_wp_error( $response ) )
            return false;

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if( empty( $data ) )
            return false;

        return $data;

    }

    /**
     * Register webhooks with PayPal
     * 
     * @return bool|array False on failure, webhook data on success
     */
    private function register_webhooks(){

        $access_token = $this->get_access_token();

        if ( empty( $access_token ) )
            return false;

        $request_url = $this->endpoint . '/v1/notifications/webhooks';

        // Generate webhook URL
        $webhook_url = add_query_arg( array(
            'pay_gate_listener' => 'paypal_connect'
        ), trailingslashit( pms_get_home_url() ) );

        // Prepare webhook data
        $webhook_data = array(
            'url'         => $webhook_url,
            'event_types' => array(
                array(
                    'name' => 'PAYMENT.CAPTURE.COMPLETED'
                ),
                array(
                    'name' => 'PAYMENT.CAPTURE.DENIED'
                ),
                array(
                    'name' => 'PAYMENT.CAPTURE.PENDING'
                ),
                array(
                    'name' => 'PAYMENT.CAPTURE.REFUNDED'
                ),
                array(
                    'name' => 'PAYMENT.CAPTURE.REVERSED'
                ),
                array(
                    'name' => 'VAULT.PAYMENT-TOKEN.CREATED'
                ),
                array(
                    'name' => 'VAULT.PAYMENT-TOKEN.DELETED'
                ),
            )
        );

        $args = array(
            'method'  => 'POST',
            'headers' => array(
                'Authorization'                 => 'Bearer ' . $access_token,
                'Content-Type'                  => 'application/json',
                'PayPal-Partner-Attribution-Id' => pms_ppcp_get_platform_bn_code(),
            ),
            'body'    => json_encode( $webhook_data ),
            'timeout' => 10,
        );

        $response = wp_remote_post( $request_url, $args );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( !empty( $data['id'] ) ) {

            // Store webhook ID for future reference
            update_option( 'pms_paypal_connect_' . $this->environment . '_webhook_id', sanitize_text_field( $data['id'] ) );

            return $data;
        }

        return false;

    }

    /**
     * Create a PayPal order
     * 
     * @param PMS_Subscription_Plan $subscription_plan
     * @param PMS_Payment $payment The payment object
     * @param string $vault_id The PayPal vault ID
     *
     * @return string|bool The order ID on success, false on failure
     */
    private function create_order( $subscription_plan, $payment = null, $vault_id = '' ) {

        if( empty( $subscription_plan ) || empty( $subscription_plan->id ) )
            return false;

        $request_url = $this->endpoint . '/v2/checkout/orders';

        if( empty( $subscription_plan->id ) )
            return false;

        $account_url = pms_get_page( 'account' );

        $order_breakdown = pms_calculate_payment_amount( $subscription_plan, array(), false, true );
        $order_currency  = apply_filters( 'pms_ppcp_create_order_currency', pms_get_active_currency(), $subscription_plan );

        // If payment is provided, this is a PSP request and we need to use the saved Billing Amount from the subscription
        if( !empty( $payment ) && !empty( $payment->member_subscription_id ) ){

            $subscription = pms_get_member_subscription( $payment->member_subscription_id );

            if( !empty( $subscription ) && !empty( $subscription->billing_amount ) ){
                $order_amount = $subscription->billing_amount;

                $purchase_units = array(
                    'amount' => array(
                        'currency_code' => $order_currency,
                        'value'         => (string)$order_amount,
                    )
                );

            }
            
        } else {

            $order_amount = $order_breakdown['total'];

            $purchase_units = array(
                'amount' => array(
                    'currency_code' => $order_currency,
                    'value'         => (string)$order_amount,
                    'breakdown' => array(
                        'item_total' => array(
                            'currency_code' => $order_currency,
                            'value'         => (string)round( $order_breakdown['subtotal'], 2 ),
                        )
                    )
                )
            );

            if( !empty( $order_breakdown['discount'] ) ){
                $purchase_units['amount']['breakdown']['discount'] = array(
                    'currency_code' => $order_currency,
                    'value'         => (string)round( $order_breakdown['discount'], 2 ),
                );
            }

            if( !empty( $order_breakdown['tax'] ) ){
                $purchase_units['amount']['breakdown']['tax_total'] = array(
                    'currency_code' => $order_currency,
                    'value'         => (string)round( $order_breakdown['tax'], 2 ),
                );
            }

            $item = array(
                'name' => $subscription_plan->name,
                'quantity' => 1,
                'unit_amount' => array(
                    'currency_code' => $order_currency,
                    'value'         => (string)round( $order_breakdown['subtotal'], 2 ),
                ),
            );

            if( !empty( $order_breakdown['tax'] ) ){
                $item['tax'] = array(
                    'currency_code' => $order_currency,
                    'value'         => (string)round( $order_breakdown['tax'], 2 ),
                );
            }

            $purchase_units['items'] = array( $item );

        }

        // Prepare the order data
        $order_data = array(
            'intent'         => 'CAPTURE',
            'purchase_units' => array( $purchase_units ),
            'payment_source' => array(
                'paypal' => array(
                    'attributes' => array(
                        'vault' => array(
                            'permit_multiple_payment_tokens' => 'false',
                            'store_in_vault'                 => 'ON_SUCCESS',
                            'usage_type'                     => 'MERCHANT',
                            'customer_type'                  => 'CONSUMER',
                        )
                    ),
                    'experience_context' => array(
                        'user_action'         => 'PAY_NOW',
                        'shipping_preference' => 'NO_SHIPPING',
                        'return_url'          => !empty( $account_url ) ? get_permalink( $account_url ) : home_url(),
                        'cancel_url'          => !empty( $account_url ) ? get_permalink( $account_url ) : home_url(),
                    )
                )
            ),
        );

        if( !empty( $items ) ){
            $order_data['items'] = $items;
        }

        if( !empty( $vault_id ) ) {
            $order_data['payment_source']['paypal'] = array(
                'vault_id' => $vault_id
            );

            if( !empty( $payment ) && !empty( $payment->id ) ) {
                $order_data['purchase_units'][0]['custom_id'] = $payment->id;
                $order_data['purchase_units'][0]['invoice_id'] = $this->get_invoice_id_for_paypal( $payment->id );
            }
        }

        $args = array(
            'method'  => 'POST',
            'headers' => $this->get_request_headers(),
            'body'    => json_encode( $order_data ),
            'timeout' => 10,
        );

        $response = wp_remote_post( $request_url, $args );

        if( is_wp_error( $response ) ) {
            if( !empty( $payment ) ){
                $payment->log_data( 'paypal_order_creation_failed', array( 'data' => $response->get_error_message() ) );
            }

            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        
        if( !in_array( $status_code, array( 200, 201 ) ) ) {

            $error_response = $this->parse_error_response( wp_remote_retrieve_body( $response ) );

            if( !empty( $payment ) ){
                $payment->log_data( 'paypal_order_creation_failed', array( 'status' => $status_code, 'data' => $error_response ) );
            }

            error_log( 'PMS PayPal Error: Order creation failed. Message: ' . $error_response['error_message'] );

            return false;

        } else {

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );

            if( empty( $data['id'] ) )
                return false;
    
            if( !empty( $vault_id ) )
                return $data;
    
            return $data['id'];

        }

        return false;

    }

    /**
     * Create a PayPal setup token
     * 
     * @param PMS_Subscription_Plan $subscription_plan
     * @return array|bool Setup token data on success, false on failure
     */
    public function create_setup_token( $subscription_plan = 0 ){

        $request_url = $this->endpoint . '/v3/vault/setup-tokens';

        $account_url = pms_get_page( 'account' );

        $description = __( 'Payment Method Update Request', 'paid-member-subscriptions' );

        if( !empty( $subscription_plan ) && !empty( $subscription_plan->name ) )
            $description = sprintf( __( 'Future payment authorization for: %s', 'paid-member-subscriptions' ), $subscription_plan->name );

        // Prepare the setup token data
        $setup_data = array(
            'payment_source' => array(
                'paypal' => array(
                    'description'                    => $description,
                    'permit_multiple_payment_tokens' => false,
                    'usage_type'                     => 'MERCHANT',
                    'customer_type'                  => 'CONSUMER',
                    'experience_context'             => array(
                        'shipping_preference' => 'NO_SHIPPING',
                        'return_url'          => pms_get_current_page_url(),
                        'cancel_url'          => !empty( $account_url ) ? get_permalink( $account_url ) : home_url(),
                    )
                )
            )
        );

        $args = array(
            'method'  => 'POST',
            'headers' => $this->get_request_headers(),
            'body'    => json_encode( $setup_data ),
            'timeout' => 10,
        );

        $response = wp_remote_post( $request_url, $args );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        
        if( !in_array( $status_code, array( 200, 201 ) ) )
            return false;
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( !empty( $data['id'] ) ) {
            return $data['id'];
        }

        return false;

    }

    /**
     * Capture a PayPal order payment
     * 
     * @param string $order_id The PayPal order ID to capture
     * @return array|bool The capture response data on success, false on failure
     */
    private function capture_order( $order_id ) {
        
        if( empty( $order_id ) )
            return false;

        $request_url = $this->endpoint . '/v2/checkout/orders/' . $order_id . '/capture';

        $args = array(
            'method'  => 'POST',
            'headers' => $this->get_request_headers(),
            'timeout' => 10,
        );

        $response = wp_remote_post( $request_url, $args );

        if( !empty( $this->payment_id ) ){
            $payment = pms_get_payment( $this->payment_id );
        }

        if( is_wp_error( $response ) ) {

            if( !empty( $payment ) ){
                $payment->log_data( 'paypal_order_capture_failed', array( 'data' => $response->get_error_message() ) );
                $payment->update( array( 'status' => 'failed' ) );
            }

            return false;

        }

        $status_code = wp_remote_retrieve_response_code( $response );

        if( !in_array( $status_code, array( 200, 201 ) ) ) {

            if( !empty( $payment ) ){
                $payment->log_data( 'paypal_order_capture_failed', array( 'status' => $status_code, 'data' => $this->parse_error_response( wp_remote_retrieve_body( $response ) ) ) );
                $payment->update( array( 'status' => 'failed' ) );
            }
            
            return false;

        } else {

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );

            // Order status can be Completed when the capture isn't, we need to check the capture
            $order_capture = $this->get_captured_payment( $data );

            if( $order_capture === false || $order_capture['status'] !== 'COMPLETED' ){

                if( !empty( $payment ) ){
                    $status         = $order_capture['status'];
                    $status_details = !empty( $order_capture['status_details'] ) && !empty( $order_capture['status_details']['reason'] ) ? $order_capture['status_details']['reason'] : 'not provided';

                    if( $status_details == 'PENDING_REVIEW' ){
                        $payment->log_data( 'paypal_order_capture_pending_payment', array( 'data' => array( 'status' => $status, 'status_details' => $status_details ) ) );
                    } else {
                        $payment->log_data( 'paypal_order_capture_failed', array( 'desc' => sprintf( 'Response status is %s. Reason: %s', $status, $status_details ) ) );
                    }

                }

                return false;
            } else {

                // Log successful capture
                if( !empty( $payment ) )
                    $payment->log_data( 'paypal_order_capture_completed', array( 'order_id' => $order_id ) );

                return $data;

            }

        }

        return false;

    }

    /**
     * Update a PayPal order
     * Supported operations: update custom_id, update invoice_id
     * 
     * @param string $order_id The PayPal order ID
     * @param array $data The data to update
     * @return bool True on success, false on failure
     */
    private function update_order( $order_id, $data ) {

        if( empty( $order_id ) )
            return false;

        $request_url = $this->endpoint . '/v2/checkout/orders/' . $order_id;

        // Prepare the patch operations
        $patch_operations = array();

        // Add custom_id update operation if provided
        if( !empty( $data['custom_id'] ) ) {
            $patch_operations[] = array(
                'op'    => 'add',
                'path'  => "/purchase_units/@reference_id=='default'/custom_id",
                'value' => $data['custom_id']
            );
        }

        // Add custom_id update operation if provided
        if( !empty( $data['invoice_id'] ) ) {
            $patch_operations[] = array(
                'op'    => 'add',
                'path'  => "/purchase_units/@reference_id=='default'/invoice_id",
                'value' => $data['invoice_id']
            );
        }

        // If no operations to perform, return
        if( empty( $patch_operations ) )
            return false;

        $args = array(
            'method'  => 'PATCH',
            'headers' => $this->get_request_headers(),
            'body'    => json_encode( $patch_operations ),
        );

        $response = wp_remote_request( $request_url, $args );

        if( is_wp_error( $response ) ) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code( $response );

        // PayPal returns 204 No Content on successful PATCH
        if( $response_code !== 204 ) {
            return false;
        }

        return true;

    }

    /**
     * Verify PayPal webhook signature
     * 
     * @return bool True if signature is valid, false otherwise
     */
    private function verify_webhook() {

        // Get webhook ID
        $webhook_id = get_option( 'pms_paypal_connect_'. $this->environment .'_webhook_id' );

        if( empty( $webhook_id ) )
            return false;

        // Get required headers
        $transmission_id = isset( $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ) ? sanitize_text_field( $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'] ) : '';
        $timestamp       = isset( $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] ) ? sanitize_text_field( $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'] ) : '';
        $signature       = isset( $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ) ? sanitize_text_field( $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ) : '';
        $cert_url        = isset( $_SERVER['HTTP_PAYPAL_CERT_URL'] ) ? sanitize_text_field( $_SERVER['HTTP_PAYPAL_CERT_URL'] ) : '';

        if( empty( $transmission_id ) || empty( $timestamp ) || empty( $signature ) || empty( $cert_url ) )
            return false;

        // Get raw request body
        $raw_body = file_get_contents( 'php://input' );
        
        if( empty( $raw_body ) )
            return false;

        // Calculate CRC32 of raw body
        $crc32 = hash( 'crc32b', $raw_body );
        $crc32 = hexdec( $crc32 );

        // Create verification message
        $message = "{$transmission_id}|{$timestamp}|{$webhook_id}|{$crc32}";

        // Get certificate from cache or download it
        $cert = $this->get_cached_cert( $cert_url );
        
        if( empty( $cert ) )
            return false;

        // Verify signature
        $signature_decoded = base64_decode( $signature );
        $verify            = openssl_verify( $message, $signature_decoded, $cert, OPENSSL_ALGO_SHA256 );

        return ( $verify === 1 );

    }

    /**
     * Get cached certificate or download and cache it
     * 
     * @param string $cert_url The certificate URL
     * @return string|bool The certificate content or false on failure
     */
    private function get_cached_cert( $cert_url ) {

        $cache_key = md5( $cert_url );
        $cache_dir = PMS_PLUGIN_DIR_PATH . '/includes/gateways/paypal_connect/cache/';
        $cache_file = $cache_dir . $cache_key;

        // Check if cached cert exists and is less than 24 hours old
        if( file_exists( $cache_file ) && ( time() - filemtime( $cache_file ) < ( 2 * DAY_IN_SECONDS ) ) ) {
            return file_get_contents( $cache_file );
        }

        // Download certificate
        $response = wp_remote_get( $cert_url );
        
        if( is_wp_error( $response ) )
            return false;

        $cert = wp_remote_retrieve_body( $response );
        
        if( empty( $cert ) )
            return false;

        // Create cache directory if it doesn't exist
        if( !file_exists( $cache_dir ) ) {
            if( !wp_mkdir_p( $cache_dir ) )
                return $cert; // Return cert without caching if directory creation fails
        }

        // Cache the certificate
        file_put_contents( $cache_file, $cert );

        return $cert;

    }

    /**
     * Create a PayPal payment token from a setup token
     * 
     * @param string $setup_token_id The setup token ID
     * @return array|bool Payment token data on success, false on failure
     */
    private function create_payment_token( $setup_token_id ) {

        if( empty( $setup_token_id ) )
            return false;

        $request_url = $this->endpoint . '/v3/vault/payment-tokens';

        // Prepare the payment token data
        $token_data = array(
            'payment_source' => array(
                'token' => array(
                    'id'   => $setup_token_id,
                    'type' => 'SETUP_TOKEN'
                )
            )
        );

        $args = array(
            'method'  => 'POST',
            'headers' => $this->get_request_headers(),
            'body'    => json_encode( $token_data ),
            'timeout' => 10,
        );

        $response = wp_remote_post( $request_url, $args );

        if( is_wp_error( $response ) ) {

            if( !empty( $this->subscription_data['id'] ) )
                pms_add_member_subscription_log( $this->subscription_data['id'], 'paypal_payment_token_creation_failed' );

            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        
        if( !in_array( $status_code, array( 200, 201 ) ) ) {

            if( !empty( $this->subscription_data['id'] ) )
                pms_add_member_subscription_log( $this->subscription_data['id'], 'paypal_payment_token_creation_failed', array( 'status' => $status_code, 'data' => $this->parse_error_response( wp_remote_retrieve_body( $response ) ) ) );

            return false;

        } else {

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );
    
            if( empty( $data['id'] ) )
                return false;
    
            return $data;

        }

        return false;

    }

    /**
     * Get common request headers for PayPal API calls
     * 
     * @return array|bool The headers array or false on failure
     */
    private function get_request_headers() {

        // Get access token
        $access_token = $this->get_access_token();
        
        if( empty( $access_token ) )
            return false;

        // Generate a unique request ID
        $request_id = wp_generate_password( 32, false );

        return array(
            'Authorization'                 => 'Bearer ' . $access_token,
            'Content-Type'                  => 'application/json',
            'PayPal-Request-Id'             => $request_id,
            'PayPal-Partner-Attribution-Id' => pms_ppcp_get_platform_bn_code(),
        );

    }

    /**
     * Get the transaction ID from the captured order
     * 
     * @param array $order The PayPal order
     * @return string The transaction ID
     */
    private function get_transaction_id( $order ) {

        if( empty( $order ) || empty( $order['purchase_units'] ) )
            return '';

        if( !empty( $order['purchase_units'][0]['payments']['captures'][0]['id'] ) )
            return $order['purchase_units'][0]['payments']['captures'][0]['id'];

    }

    /**
     * Parse a PayPal API error response and extract relevant details
     * 
     * @param array $error The error response from PayPal
     * @return array The parsed error details
     */
    private function parse_error_response( $error ) {

        $error = json_decode( $error, true );

        $parsed_error = array(
            'error_code'    => '',
            'error_message' => '',
            'error_field'   => '',
            'info_url'      => ''
        );

        // Extract error details
        if( !empty( $error['details'] ) && is_array( $error['details'] ) ) {
            // We'll take the first error detail since that's usually the most relevant
            $detail = reset( $error['details'] );
            
            if( !empty( $detail['issue'] ) )
                $parsed_error['error_code'] = $detail['issue'];
            
            if( !empty( $detail['description'] ) )
                $parsed_error['error_message'] = $detail['description'];
            
            if( !empty( $detail['field'] ) )
                $parsed_error['error_field'] = $detail['field'];
        }

        // Extract information link
        if( !empty( $error['links'] ) && is_array( $error['links'] ) ) {
            foreach( $error['links'] as $link ) {
                if( !empty( $link['rel'] ) && $link['rel'] === 'information_link' && !empty( $link['href'] ) ) {
                    $parsed_error['info_url'] = $link['href'];
                    break;
                }
            }
        }

        return $parsed_error;
    }

    /**
     * Output the PayPal payment method content for the account subscription details table
     * 
     * @param object $subscription The subscription object
     */
    public function output_account_subscription_details_table_payment_method_content( $subscription ) {

        if( empty( $subscription->id ) || empty( $subscription->payment_gateway ) || $subscription->payment_gateway != $this->gateway_slug )
            return;

        ?>

        <div class="pms-account-subscription-details-table__payment-method__paypal">
            <div class="pms-account-subscription-details-table__payment-method__wrap">
                <span class="pms-account-subscription-details-table__payment-method__brand-paypal">
                <?php
                    $assets_src = esc_url( PMS_PLUGIN_DIR_PATH ) . 'assets/images/';
                    
                    echo file_get_contents( $assets_src . 'PayPal-payment-icon.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                </span>
            </div>
        </div>

        <?php
    }

    /**
     * List all webhooks registered with PayPal
     * 
     * @return array|bool Array of webhooks on success, false on failure
     */
    public function list_webhooks() {

        $access_token = $this->get_access_token();
        
        if( empty( $access_token ) )
            return false;

        $request_url = $this->endpoint . '/v1/notifications/webhooks';

        $args = array(
            'method'  => 'GET',
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json'
            )
        );

        $response = wp_remote_get( $request_url, $args );

        if( is_wp_error( $response ) )
            return false;

        $status_code = wp_remote_retrieve_response_code( $response );
        
        if( $status_code !== 200 )
            return false;

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if( empty( $data ) || empty( $data['webhooks'] ) )
            return false;

        return $data['webhooks'];

    }

    /**
     * Parse a PayPal webhook response
     *
     * @param object $event The webhook event
     *
     * @return object The parsed webhook event
     */
    public function parse_webhook_response( $event ) {

        if( isset( $event->links) )
            unset( $event->links );
        
        if( isset( $event->resource->links ) )
            unset( $event->resource->links );

        if( isset( $event->resource ) ){
            $resource = $event->resource;

            unset( $event->resource );

            foreach ( $resource as $key => $value ) {

                if( isset( $event->$key ) )
                    $key = 'resource_' . $key;

                $event->$key = $value;

            }
        }

        return $event;

    }

    /**
     * Get a client token for a specific customer
     *
     * @param string $customer_id The PayPal customer ID
     * @return string|bool The client token on success, false on failure
     */
    public function generate_client_token( $customer_id ) {

        if( empty( $this->client_id ) || empty( $this->client_secret ) || empty( $customer_id ) )
            return false;

        $request_url = $this->endpoint . '/v1/oauth2/token';

        // Create authorization header
        $auth_header = base64_encode( $this->client_id . ':' . $this->client_secret );

        $args = array(
            'method'  => 'POST',
            'headers' => array(
                'Authorization'         => 'Basic ' . $auth_header,
                'Content-Type'          => 'application/x-www-form-urlencoded',
            ),
            'body'    => array(
                'grant_type'         => 'client_credentials',
                'response_type'      => 'id_token',
                'target_customer_id' => $customer_id
            ),
            'timeout' => 10,
        );

        $response = wp_remote_post( $request_url, $args );

        if( is_wp_error( $response ) ) {
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );

        if( !in_array( $status_code, array( 200, 201 ) ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if( !empty( $data['id_token'] ) ) {
            return $data['id_token'];
        }

        return false;

    }

    /**
     * Get a subscription from a PayPal vault ID
     *
     * @param string $vault_id The PayPal vault ID
     *
     * @return object|bool The subscription object on success, false on failure
     */
    private function get_subscription_by_vault_id( $vault_id ) {

        if( empty( $vault_id ) )
            return false;

        global $wpdb;
        
        $result = $wpdb->get_var( $wpdb->prepare( "SELECT a.member_subscription_id FROM {$wpdb->prefix}pms_member_subscriptionmeta a INNER JOIN {$wpdb->prefix}pms_member_subscriptions b ON a.member_subscription_id = b.id WHERE a.meta_key = %s AND a.meta_value = %s", '_paypal_vault_id', $vault_id ) );
    
        if( !empty( $result ) ){
            $subscription = pms_get_member_subscription( absint( $result ) );

            if( !empty( $subscription ) )
                return $subscription;
        }
    
        return false;

    }

    /**
     * Get a payment from a PayPal order ID
     *
     * @param string $order_id The PayPal order ID
     *
     * @return object|bool The payment object on success, false on failure
     */
    private function get_payment_from_order_id( $order_id ){

        if( empty( $order_id ) )
            return false;

        global $wpdb;

        $result = $wpdb->get_var( $wpdb->prepare( "SELECT a.payment_id FROM {$wpdb->prefix}pms_paymentmeta a INNER JOIN {$wpdb->prefix}pms_payments b ON a.payment_id = b.id WHERE a.meta_key = %s AND a.meta_value = %s", 'paypal_order_id', $order_id ) );

        if( !empty( $result ) ){
            $payment = pms_get_payment( absint( $result ) );

            if( !empty( $payment ) )
                return $payment;
        }

        return false;

    }


    /**
     * Get the captured payment from a PayPal order
     *
     * @param array $order The PayPal order
     *
     * @return array|bool The captured payment on success, false on failure
     */
    private function get_captured_payment( $order ){

        if( empty( $order['purchase_units'] ) || empty( $order['purchase_units'][0] ) || empty( $order['purchase_units'][0]['payments'] ) )
            return false;

        $payments = $order['purchase_units'][0]['payments'];

        if( empty( $payments['captures'] ) || empty( $payments['captures'][0] ) )
            return false;

        return $payments['captures'][0];

    }

    public function get_invoice_id_for_paypal( $payment_id ){

        $unique_string = $this->generate_site_unique_string();

        return 'PMS-' . $unique_string . '-' . $payment_id;

    }

    public function generate_site_unique_string( $length = 8 ) {

        $hash = md5( home_url() );

        return substr($hash, 0, $length);
        
    }

}