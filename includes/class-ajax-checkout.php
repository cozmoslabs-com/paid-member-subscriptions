<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/**
 * This is used to process an AJAX Checkout
 */
Class PMS_AJAX_Checkout_Handler {

    /**
     * Class instance
     */
    private static $instance = null;

    /**
     * The gateway slugs that support this functionality
     */
    private $supported_gateways = [];

    public function __construct() {

        $this->supported_gateways = [
            'stripe_connect',
            'paypal_connect',
        ];

        // only load if the active gateways include one of the supported gateways
        $payments_settings = get_option( 'pms_payments_settings', false );

        if( empty( $payments_settings ) || empty( $payments_settings['active_pay_gates'] ) )
            return;

        $enabled = false;

        foreach( $this->supported_gateways as $gateway ){
            if( in_array( $gateway, $payments_settings['active_pay_gates'] ) ){
                $enabled = true;
                break;
            }
        }

        if( $enabled === false )
            return;

        // Process AJAX checkout
        add_action( 'wp_ajax_pms_process_checkout', array( $this, 'process_ajax_checkout' ) );
        add_action( 'wp_ajax_nopriv_pms_process_checkout', array( $this, 'process_ajax_checkout' ) );

        // Process Payment
        add_action( 'wp_ajax_pms_process_payment', array( $this, 'process_payment' ) );
        add_action( 'wp_ajax_nopriv_pms_process_payment', array( $this, 'process_payment' ) );

        // Grab a fresh process payment nonce
        add_action( 'wp_ajax_pms_update_nonce', array( $this, 'refresh_nonce' ) );
        add_action( 'wp_ajax_nopriv_pms_update_nonce', array( $this, 'refresh_nonce' ) );

        // Set form location for AJAX requests
        add_filter( 'pms_request_form_location', array( $this, 'set_request_form_location_for_ajax' ), 20, 2 );

        /**
         * When the form is submitted through an AJAX request to the website it will trigger the
         * normal flow of the plugin: validation -> register user -> process checkout
         *
         * The checkout will error out since we don't have the required payment data and we just want it
         * to register the user, payment, subscription at this point, then it will reach this action
         *
         * We hook the action in order to return some data to the front-end js in order to complete the
         * processing of this payment
         */
        add_action( 'pms_checkout_error_before_redirect', array( $this, 'handle_checkout_error_redirect' ), 20, 2 );

        // Add process checkout nonce to form
        add_filter( 'pms_get_output_payment_gateways', array( $this, 'add_process_checkout_nonce_to_form' ), 10, 2 );

        // When the WPPB form uses PMS we reorder the fields in the ajax request so that the Subscription Plans field is last
        // This need to happen because on the save hook of that field, PMS does the necessary processing to create the payment 
        // and subscription. A request which is then intercepted by the redirect failure action
        add_filter( 'wppb_change_form_fields', array( $this, 'wppb_reorder_fields_when_doing_ajax_requests' ), 20, 2 );

        // Validate checkout through AJAX
        // This is using the same `process_ajax_checkout` function as the `pms_process_checkout` action but we catch it later and return the errors before further processing occurs
        add_action( 'wp_ajax_pms_validate_checkout', array( $this, 'process_ajax_checkout' ) );
        add_action( 'wp_ajax_nopriv_pms_validate_checkout', array( $this, 'process_ajax_checkout' ) );

        //add_action( 'pms_register_form_extra', array( $this, 'validate_ajax_checkout' ), 99 );
        add_action( 'pms_wppb_email_confirmation_form_extra', array( $this, 'validate_ajax_checkout' ), 99 );
        add_action( 'pms_process_checkout_validations', array( $this, 'validate_ajax_checkout' ), 99 );

    }

    /**
     * Get the class instance
     */
    public static function get_instance() {

        if ( null === self::$instance )
            self::$instance = new self();

        return self::$instance;

    }

    public function process_ajax_checkout(){

        if( !check_ajax_referer( 'pms_process_checkout', 'pms_nonce' ) )
            die();

        // this is simply added so the AJAX request to the website triggers the regular
        // form processing of the plugin

        // Process WPPB form manually based on the request data
        if( isset( $_REQUEST['form_type'] ) && $_REQUEST['form_type'] == 'wppb' ){

            $only_validate = isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'pms_validate_checkout' ? true : false;

            $this->process_wppb_checkout( $only_validate );

        }

    }

    public function process_payment(){

        // We use the same nonce as the process request for this one
        if( !check_ajax_referer( 'pms_process_payment', 'pms_nonce' ) )
            die();

        $payment_gateway = !empty( $_POST['pay_gate'] ) ? sanitize_text_field( $_POST['pay_gate'] ) : '';

        if( empty( $payment_gateway ) )
            die();

        // Make sure that payment gateway is enabled
        $active_gateways = pms_get_active_payment_gateways();

        if( !in_array( $payment_gateway, $active_gateways ) )
            die();

        $payment_id      = !empty( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;
        $subscription_id = !empty( $_POST['subscription_id'] ) ? absint( $_POST['subscription_id'] ) : 0;

        // Initialize gateway
        $gateway = pms_get_payment_gateway( $payment_gateway );

        $gateway->process_payment( $payment_id, $subscription_id );
        die();

    }

    public function refresh_nonce(){

        echo json_encode( wp_create_nonce( 'pms_process_payment' ) );
        die();

    }

    public function set_request_form_location_for_ajax( $location, $request ){

        if( !wp_doing_ajax() )
            return $location;

        if( !isset( $request['form_type'] ) )
            return $location;

        if( in_array( $request['form_type'], array( 'pms', 'wppb', 'pms_register' ) ) && isset( $request['action'] ) && $request['action'] == 'pms_process_payment' && empty( $location ) )
            $location = 'register';

        // set form location for wppb register AJAX request
        if( $request['form_type'] == 'wppb' && isset( $request['action'] ) && $request['action'] == 'pms_process_checkout' )
            $location = 'register';

        return $location;

    }

    /**
     * Handle Checkout Error redirect after an AJAX request was done in order to continue processing
     *
     * @param  object    $subscription   PMS_Member_Subscription object
     * @param  object    $payment        PMS_Payment object, can be empty
     * @return JSON
     */
    public function handle_checkout_error_redirect( $subscription, $payment ){

        if( !wp_doing_ajax() || !( $subscription instanceof PMS_Member_Subscription ) )
            return;

        // Only Stripe Connect is using the checkout error redirect hook to continue processing
        if( !isset( $_POST['pay_gate'] ) || $_POST['pay_gate'] != 'stripe_connect' )
            return;

        do_action( 'pms_process_checkout_handle_error_redirect', $subscription, $payment );

        $data = array(
            'success' => false,
        );

        echo json_encode( $data );
        die();

    }

    /**
     * Add process checkout nonce to form
     *
     * @param  string   $output
     * @param  array    $pms_settings
     * @return string
     */
    public function add_process_checkout_nonce_to_form( $output, $pms_settings ) {

        // process checkout nonce
        $output .= '<input type="hidden" id="pms-process-checkout-nonce" name="pms_process_checkout_nonce" value="'. esc_attr( wp_create_nonce( 'pms_process_checkout' ) ) .'"/>';

        return $output;

    }

    /**
     * This function verifies if we're trying to process a checkout validation request through AJAX 
     * and sends the processing back to front-end if there are no errors
     */
    public function validate_ajax_checkout(){

        if( !wp_doing_ajax() )
            return;

        if( !isset( $_REQUEST['pms_nonce'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['pms_nonce'] ), 'pms_process_checkout' ) )
            return;

        if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'pms_validate_checkout' ){

            // If the form is a WPPB form, we need to let the process continue to the WPPB handler
            if( isset( $_REQUEST['form_type'] ) && $_REQUEST['form_type'] == 'wppb' )
                return;

            // Return generated errors
            if ( count( pms_errors()->get_error_codes() ) > 0 ){
                PMS_Form_Handler::return_generated_errors_for_ajax();
            }

            do_action( 'pms_ajax_checkout_validated' );

            $data = array(
                'success' => true,
            );

            echo json_encode( $data );
            die();

        }

        return;

    }

    public function wppb_reorder_fields_when_doing_ajax_requests( $fields, $form_args ){

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

    public function process_wppb_checkout( $only_validate = false ){

        if( defined( 'WPPB_PLUGIN_DIR' ) )
            include_once( WPPB_PLUGIN_DIR . '/front-end/class-formbuilder.php' );
        else
            return false;

        $form = pms_wppb_get_form( isset( $_REQUEST['form_name'] ) ? sanitize_text_field( $_REQUEST['form_name'] ) : '' );

        do_action( 'pms_before_processing_wppb_checkout', $form, $only_validate );

        $field_check_errors = $form->wppb_test_required_form_values( $_REQUEST );

        if( empty( $field_check_errors ) ){

            if( $only_validate == true ){

                do_action( 'pms_ajax_checkout_validated' );

                $data = array(
                    'success' => true,
                );

                echo json_encode( $data );
                die();

            } else {

                do_action( 'wppb_before_saving_form_values', $_REQUEST, $form->args );

                // Process is started here, it gets completed by the PMS handler that gets triggered when the Subscription Plans field is saved
                $form->wppb_save_form_values( $_REQUEST) ;

                do_action( 'wppb_after_saving_form_values', $_REQUEST, $form->args );

            }

        } else {

            $data = array(
                'success'     => false,
                'wppb_errors' => $field_check_errors,
            );

            echo json_encode( $data );
            die();

        }

    }

    /**
     * Similar to PMS_Form_Handler::get_redirect_url(), but with a naked else at the end to cover all the
     * logged out form locations
     *
     * @param  string        $form_location
     * @return string        Success redirect URL
     */
    public static function get_success_redirect_url( $form_location, $payment_id = 0 ){

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
                    'pms_gateway_payment_id'     => !empty( $payment_id ) ? base64_encode( $payment_id ) : '',
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
                    'pms_gateway_payment_id'     => !empty( $payment_id ) ? base64_encode( $payment_id ) : '',
                ),
            $redirect_url );

        // Register success page or current page URL
        } else {

            $redirect_url = pms_get_register_success_url();

            // Add a success message if we should stay on the same page
            if( isset( $_POST['current_page'] ) && ( empty( $redirect_url ) || $redirect_url == $_POST['current_page'] ) ){

                $redirect_url = esc_url_raw( $_POST['current_page'] );

                $payment = pms_get_payment( $payment_id );
                $user    = get_userdata( $payment->user_id );

                // WPPB Form
                if( isset( $_REQUEST['form_type'] ) && $_REQUEST['form_type'] == 'wppb' ){

                    // On the WPPB Form also take into account Form and Custom Redirects
                    $form = pms_wppb_get_form( isset( $_REQUEST['form_name'] ) ? sanitize_text_field( $_REQUEST['form_name'] ) : '' );

                    if( ! current_user_can( 'manage_options' ) && $form->args['form_type'] != 'edit_profile' && isset( $_POST['custom_field_user_role'] ) ) {
                        $user_role = sanitize_text_field( $_POST['custom_field_user_role'] );
                    } elseif( ! current_user_can( 'manage_options' ) && $form->args['form_type'] != 'edit_profile' && isset( $form->args['role'] ) ) {
                        $user_role = $form->args['role'];
                    } else {
                        $user_role = get_option( 'default_role' );
                    }

                    $wppb_redirect_url = false;

                    if( $form->args['redirect_activated'] == '-' ) {
                        $wppb_redirect_url = wppb_get_redirect_url( $form->args['redirect_priority'], 'after_registration', $form->args['redirect_url'], $user, $user_role );
                    } elseif( $form->args['redirect_activated'] == 'Yes' ) {
                        $wppb_redirect_url = $form->args['redirect_url'];
                    }

                    if( empty( $wppb_redirect_url ) ){
                        $message = apply_filters( 'wppb_register_success_message', sprintf( __( 'The account %1s has been successfully created!', 'paid-member-subscriptions' ), $user->user_login ), $user->user_login );

                        if ( function_exists( 'wppb_get_admin_approval_option_value' ) && wppb_get_admin_approval_option_value() === 'yes' ) {

                            $wppb_general_settings = get_option( 'wppb_general_settings' );

                            if( empty( $wppb_general_settings['adminApprovalOnUserRole'] ) || ( !empty( $wppb_general_settings['adminApprovalOnUserRole'] ) && in_array( $user_role, $wppb_general_settings['adminApprovalOnUserRole'] ) ) )
                                $message = apply_filters( 'wppb_register_success_message', sprintf( __( 'Before you can access your account %1s, an administrator has to approve it. You will be notified via email.', 'paid-member-subscriptions' ), $user->user_login ), $user->user_login );
                        }

                        $redirect_url = add_query_arg( 'pms_wppb_custom_success_message', true, $redirect_url );
                    } else {
                        $redirect_url = $wppb_redirect_url;
                    }

                    // Automatic login
                    if( !empty( $redirect_url ) ){
                        if( !empty( $form->args['login_after_register'] ) && strtolower( $form->args['login_after_register'] ) == 'yes' ){
                            $redirect_url = pms_wppb_get_autologin_url( $redirect_url, $payment_id );
                        }
                    }

                    if( empty( $message ) )
                        $message = apply_filters( 'wppb_register_success_message', sprintf( __( 'The account %1s has been successfully created!', 'paid-member-subscriptions' ), $user->user_login ), $user->user_login );

                } else {
                    $message = apply_filters( 'pms_register_subscription_success_message', __( 'Congratulations, you have successfully created an account.', 'paid-member-subscriptions' ) );

                    if ( function_exists( 'wppb_get_admin_approval_option_value' ) && wppb_get_admin_approval_option_value() === 'yes' ) {

                        $wppb_general_settings = get_option( 'wppb_general_settings' );

                        if( !empty( $wppb_general_settings['adminApprovalOnUserRole'] ) ){
                            $user_has_admin_approval_role = false;

                            foreach ( $user->roles as $role ) {
                                if ( in_array( $role, $wppb_general_settings['adminApprovalOnUserRole'] ) ) {
                                    $user_has_admin_approval_role = true;
                                    break;
                                }
                            }

                            if( $user_has_admin_approval_role )
                                $message .= '<br><br>' . sprintf( __( 'Before you can access your account %1s, an administrator has to approve it. You will be notified via email.', 'paid-member-subscriptions' ), '<strong>' . $user->user_login . '</strong>' );
                        }

                    }
                }

                $redirect_url = add_query_arg( array( 'pmsscscd' => base64_encode( 'subscription_plans' ), 'pmsscsmsg' => urlencode( base64_encode( $message ) ) ), $redirect_url );

            // Redirecting to a new page
            } else {

                // Take into account autologin from the WPPB form
                if( isset( $_REQUEST['form_type'] ) && $_REQUEST['form_type'] == 'wppb' ){

                    $form = pms_wppb_get_form( isset( $_REQUEST['form_name'] ) ? sanitize_text_field( $_REQUEST['form_name'] ) : '' );

                    if( !empty( $form->args['redirect_url'] ) )
                        $redirect_url = $form->args['redirect_url'];

                    if( !empty( $form->args['login_after_register'] ) && strtolower( $form->args['login_after_register'] ) == 'yes' ){
                        $redirect_url = pms_wppb_get_autologin_url( $redirect_url, $payment_id );
                    }

                }

            }

            $redirect_url = add_query_arg(
                array(
                    'pmsscscd'                   => base64_encode( 'subscription_plans' ),
                    'pms_gateway_payment_action' => base64_encode( $form_location ),
                    'pms_gateway_payment_id'     => !empty( $payment_id ) ? base64_encode( $payment_id ) : '',
                ),
            $redirect_url );

        }

        // Same filter as PMS_Form_Handler::process_checkout()
        return apply_filters( 'pms_get_redirect_url', $redirect_url, $form_location );

    }

    /**
     * Get the payment error redirect URL
     *  
     * @param  int    $payment_id The payment ID
     * @return string The redirect URL
     */
    public static function get_payment_error_redirect_url( $payment_id = 0 ){

        $account_page = pms_get_page( 'account', true );

        $pms_is_register = is_user_logged_in() ? 0 : 1;

        $redirect_url = !empty( $_POST['current_page'] ) ? esc_url_raw( $_POST['current_page'] ) : $account_page;

        // Take into account autologin from the WPPB form
        if( isset( $_REQUEST['form_type'] ) && $_REQUEST['form_type'] == 'wppb' ) {

            $form = pms_wppb_get_form( isset( $_REQUEST['form_name'] ) ? sanitize_text_field( $_REQUEST['form_name'] ) : '' );

            if( !empty( $form->args['login_after_register'] ) && strtolower( $form->args['login_after_register'] ) == 'yes' ) {
                $redirect_url = pms_wppb_get_autologin_url( $redirect_url, $payment_id );
            }

        }

        $redirect_url = apply_filters( 'pms_ajax_payment_error_redirect_url', $redirect_url, $payment_id, $pms_is_register );

        return add_query_arg( array( 'pms_payment_error' => '1', 'pms_is_register' => $pms_is_register, 'pms_payment_id' => $payment_id ), $redirect_url );

    }

}

// Initialize the class
PMS_AJAX_Checkout_Handler::get_instance();