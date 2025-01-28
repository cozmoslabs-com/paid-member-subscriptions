<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * This class handles all the form submissions in the front-end part of the website
 *
 */
Class PMS_Form_Handler {

    /**
     * Hook data processing methods on init
     *
     */
    public static function init() {

        add_action( 'init', array( __CLASS__, 'register_form' ) );
        add_action( 'init', array( __CLASS__, 'new_subscription_form') );
        add_action( 'init', array( __CLASS__, 'upgrade_subscription' ) );
        add_action( 'init', array( __CLASS__, 'change_subscription' ) );
        add_action( 'init', array( __CLASS__, 'renew_subscription' ) );
        add_action( 'init', array( __CLASS__, 'cancel_subscription') );
        add_action( 'init', array( __CLASS__, 'abandon_subscription') );
        add_action( 'init', array( __CLASS__, 'update_payment_method') );
        add_action( 'init', array( __CLASS__, 'retry_payment_subscription' ) );
        add_action( 'init', array( __CLASS__, 'recover_password_form') );
        add_action( 'init', array( __CLASS__, 'edit_profile' ) );
        add_action( 'init', array( __CLASS__, 'login_form' ) );

        add_filter( 'login_redirect', array( __CLASS__, 'validate_login_form' ), 10 ,3 );
        add_action( 'pms_register_form_after_create_user', array( __CLASS__, 'automatically_log_in') );

    }


    /**
     * Registration Form logic
     *
     */
    public static function register_form() {

        // Check nonce
        if ( !isset( $_POST['pmstkn'] ) || !wp_verify_nonce( sanitize_text_field( $_POST['pmstkn'] ), 'pms_register_form_nonce') )
            return;

        // Validate data sent from the registration form
        if( !self::validate_register_form() ){
            
            // return errors for AJAX requests
            if( wp_doing_ajax() )
                self::return_generated_errors_for_ajax();
            else
                return;

        }

        // Check if we need to register the user without him selecting a subscription (becoming a member) - thins happens when "subscription_plans" param in register form is = "none"
        if ( isset( $_POST['pmstkn2'] ) && ( wp_verify_nonce( sanitize_text_field( $_POST['pmstkn2'] ), 'pms_register_user_no_subscription_nonce' ) ) ) {

            // Register the user
            self::register_user( self::get_request_member_data(), true );

        } else {

            // Proceed to checkout
            self::process_checkout();

        }

    }

    public static function validate_register_form() {

        /**
         * Username
         *
         */
        if (!isset($_POST['user_login']))
            pms_errors()->add('user_login', __('Please enter a username.', 'paid-member-subscriptions'));

        if (isset($_POST['user_login'])) {

            $user_login = trim( sanitize_user($_POST['user_login']));

            if (empty($user_login))
                pms_errors()->add('user_login', __('Please enter a username.', 'paid-member-subscriptions'));
            else {

                $user = get_user_by('login', $user_login);

                if ($user){

                    if( ( $login_page = pms_get_page( 'login', true ) ) !== false )
                        pms_errors()->add('user_login', sprintf( __('This username is already taken. Please choose another one or login %shere%s.', 'paid-member-subscriptions'), '<a href="'.$login_page.'">', '</a>' ) );
                    else
                        pms_errors()->add('user_login', __('This username is already taken. Please choose another one.', 'paid-member-subscriptions'));

                }

            }

        }


        /**
         * E-mail
         *
         */
        if (!isset($_POST['user_email']))
            pms_errors()->add('user_email', __('Please enter an e-mail address.', 'paid-member-subscriptions'));

        if (isset($_POST['user_email'])) {

            $user_email = trim( sanitize_email( $_POST['user_email']) );

            if (empty($user_email))
                pms_errors()->add('user_email', __('Please enter an e-mail address.', 'paid-member-subscriptions'));
            else {

                if (!is_email($user_email))
                    pms_errors()->add('user_email', __('The e-mail address doesn\'t seem to be valid.', 'paid-member-subscriptions'));
                else {

                    $user = get_user_by('email', $user_email);

                    if ($user){

                        if( ( $login_page = pms_get_page( 'login', true ) ) !== false )
                            pms_errors()->add('user_email', sprintf( __( 'This e-mail is already registered. Please choose another one or login %shere%s.', 'paid-member-subscriptions' ), '<a href="'.$login_page.'">', '</a>' ) );
                        else
                            pms_errors()->add('user_email', __('This e-mail is already registered. Please choose another one.', 'paid-member-subscriptions'));

                    }

                }

            }

        }


        /**
         * Password
         *
         */
        if (!isset($_POST['pass1']) || empty($_POST['pass1']))
            pms_errors()->add('pass1', __('Please enter a password.', 'paid-member-subscriptions'));

        if (!isset($_POST['pass2']) || empty($_POST['pass2']))
            pms_errors()->add('pass2', __('Please repeat the password.', 'paid-member-subscriptions'));

        if (isset($_POST['pass1']) && isset($_POST['pass2'])) {

            $pass1 = trim($_POST['pass1']);// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $pass2 = trim($_POST['pass2']);// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

            if ($pass1 != $pass2)
                pms_errors()->add('pass2', __('The passwords did not match.', 'paid-member-subscriptions'));

        }


        /**
         * GDPR
         *
         */
        $gdpr_settings = pms_get_gdpr_settings();
        if( !empty( $gdpr_settings ) ) {
            if (!empty($gdpr_settings['gdpr_checkbox']) && $gdpr_settings['gdpr_checkbox'] === 'enabled') {
                if (!isset($_POST['user_consent']))
                    pms_errors()->add('user_consent', __('This field is required.', 'paid-member-subscriptions'));
            }
        }


        /**
         * Extra validations
         *
         */
        do_action( 'pms_register_form_validation' );


        // Stop if there are errors
        if( count( pms_errors()->get_error_messages() ) > 0 )
            return false;
        else
            return true;

    }

    /**
     * Inserts a new user in the database given an array of user information
     *
     * @param array $user_data  - user information to be added to the user
     *
     * @return int
     *
     */
    public static function register_user( $user_data = array(), $redirect = false ) {

        if( empty( $user_data ) )
            return 0;

        /**
         * Modify user data on register just before inserting the user
         *
         * @param array $user_data
         *
         */
        $user_data = apply_filters( 'pms_register_form_user_data', $user_data );

        /**
         * Do something before creating the user
         *
         * @param array $user_data
         *
         */
        do_action( 'pms_register_form_before_create_user', $user_data );


        // Register the user and grab the user_id
        $user_id = wp_insert_user( $user_data );

        if( is_wp_error( $user_id ) )
            $user_data['user_id'] = 0;
        else
            $user_data['user_id'] = $user_id;

        /**
         * Do something after creating the user
         *
         * @param array $user_data
         *
         */
        do_action( 'pms_register_form_after_create_user', $user_data );

        if( $redirect === true ){
            wp_redirect( self::get_redirect_url() );
            exit;
        }

        if( is_wp_error( $user_id ) )
            return false;
        else
            return $user_id;

    }


    /*
     * Method that validates the subscription plans sent
     *
     */
    public static function validate_subscription_plans( $post_data = array() ) {

        // If there are no active subscriptions return false
        $active_subscriptions = pms_get_subscription_plans();
        if ( empty($active_subscriptions) ) {
            pms_errors()->add( 'subscription_plans', __( 'The selected subscription plan does not exist or is inactive.', 'paid-member-subscriptions' ) );
            return false;
        }

        // Set post data
        if( empty( $post_data ) )
            $post_data = $_POST;

        // Check to see if any subscription plans where selected
        if( empty( $post_data['subscription_plans'] ) ) {
            pms_errors()->add( 'subscription_plans', apply_filters( 'pms_error_subscription_plan_missing', __( 'Please select a subscription plan.', 'paid-member-subscriptions' ) ) );
            return false;
        }


        $subscription_plan = pms_get_subscription_plan( absint( trim( $post_data['subscription_plans'] ) ) );

        // Check to see if the subscription plan exists and is active
        if( !$subscription_plan->is_valid() || !$subscription_plan->is_active() ){
            pms_errors()->add( 'subscription_plans', __( 'The selected subscription plan does not exist or is inactive.', 'paid-member-subscriptions' ) );
            return false;
        }

        return true;

    }


    public static function validate_subscription_plans_member_eligibility( $user_data = array() ) {

        if( empty( $user_data ) )
            $user_data = self::get_request_member_data();

        $form_location = self::get_request_form_location();

        /**
         * Handle member eligibility on new_subscription forms
         *
         */
        if( $form_location == 'new_subscription' ) {

            // Get member object
            $member = pms_get_member( $user_data['user_id'] );

            // Add subscription plans array to use later, instead of querying the db again
            $subscription_plan_cache = array();

            foreach( $user_data['subscriptions'] as $new_subscription_id ) {

                // Get subscription plan
                $subscription_plan = pms_get_subscription_plan( $new_subscription_id );
                $subscription_plan_cache[] = $subscription_plan;

                // Check to see if the subscription plan exists,
                // If it doesn't go to the next one
                if( !$subscription_plan->is_valid() ) {
                    pms_errors()->add( 'subscription_plans', __( 'Something went wrong.', 'paid-member-subscriptions' ) );
                    continue;
                }

                if( !empty( $member->subscriptions ) ) {
                    foreach( $member->subscriptions as $member_subscription ) {

                        // Get subscription plan group for the current subscription plan
                        // We need to check if the plan we wish to add to the member isn't already added, or
                        // that another plan from the branch is already attached
                        $subscription_plans_group = pms_get_subscription_plans_group( $new_subscription_id );

                        foreach( $subscription_plans_group as $group_subscription_plan ) {
                            if( $group_subscription_plan->id == $member_subscription['subscription_plan_id'] )
                                pms_errors()->add( 'subscription_plans', sprintf( __( 'You are not eligible to subscribe to: %s', 'paid-member-subscriptions' ), $subscription_plan->name ) );
                        }

                    }
                }

            }

        }

        if( count( pms_errors()->get_error_messages() ) > 0 )
            return false;
        else
            return true;

    }


    /**
     * Makes payment gateways related validations such as:
     *
     * 1. If no payment gateway is selected on checkout we check to see if the subscription plan is a free one or not
     * 2. Verifies that the payment gateway was configured correctly
     * 3. Verifies if the selected subscription plan's settings match the payment gateway's supports features
     * 4. Verifies the custom fields of the selected payment gateway
     *
     * @param string $form_location
     *
     * @return bool
     *
     */
    public static function validate_payment_gateway( $form_location = 'register' ) {

        // Get payment gateway
        $pay_gate = ( ! empty( $_POST['pay_gate'] ) ? sanitize_text_field( $_POST['pay_gate'] ) : '' );

        if( ! empty( $pay_gate ) )
            $payment_gateway = pms_get_payment_gateway( $pay_gate );

        // Get subscription plan
        if( !empty( $_POST['subscription_plans'] ) ) {
            $subscription_plan = pms_get_subscription_plan(absint( $_POST['subscription_plans'] ));
        } else {
            pms_errors()->add( 'subscription_plan', __( 'There was no subscription plan selected.', 'paid-member-subscriptions' ) );
            return false;
        }

        /**
         * Handle the case where no payment gateway was selected
         * We need to check if the selected subscription plan is free in this case
         *
         */
        if( empty( $pay_gate ) ) {

            /**
             * If we are on a register form or a new subscription form we need to check if the subscription
             * plan is has a price in place and also if it has a sign-up fee. Sign-up fees are available only
             * for register and new subscriptions
             *
             */
            if( in_array( $form_location, array( 'register', 'new_subscription', 'register_email_confirmation' ) ) ) {

                if( ! empty( $subscription_plan->price ) || ! empty( $subscription_plan->sign_up_fee ) ) {

                    pms_errors()->add( 'payment_gateway', __( 'There is no payment gateway available to complete the checkout process.', 'paid-member-subscriptions' ) );
                    return false;

                }

            /**
             * If we are on other forms, such as renew and upgrade we need to check only for the subscription
             * plan's price
             *
             */
            } else {

                if( apply_filters( 'pms_validate_payment_gateway_no_gateway_logged_in', false, $subscription_plan, $form_location ) === false ){

                    if( ! empty( $subscription_plan->price ) ) {

                        pms_errors()->add( 'payment_gateway', __( 'There is no payment gateway available to complete the checkout process.', 'paid-member-subscriptions' ) );
                        return false;

                    }

                }

            }

            return true;

        }

        // Check if gateway is configured correctly
        $payment_gateway->validate_credentials();

        // Validate if the payment gateway supports trials for the selected subscription plan
        if( pms_payment_gateways_support( pms_get_active_payment_gateways(), 'subscription_free_trial' ) ) {

            if( ! empty( $subscription_plan->trial_duration ) ) {

                if( ! $payment_gateway->supports( 'subscription_free_trial' ) )
                    pms_errors()->add( 'form_general', __( 'Something went wrong. The selected payment gateway does not support free trials.', 'paid-member-subscriptions' ) );

            }

        }

        // Validate if the payment gateway supports sign-up fees for the selected subscription plan
        if( pms_payment_gateways_support( pms_get_active_payment_gateways(), 'subscription_sign_up_fee' ) ) {

            if( ! empty( $subscription_plan->sign_up_fee ) ) {

                if( ! $payment_gateway->supports( 'subscription_sign_up_fee' ) )
                    pms_errors()->add( 'form_general', __( 'Something went wrong. The selected payment gateway does not support sign-up fees.', 'paid-member-subscriptions' ) );

            }

        }

        // Validate if the payment gateway supports recurring for the selected subscription plan
        if( self::checkout_is_recurring() ) {

            if( ! $payment_gateway->supports( 'recurring_payments' ) )
                pms_errors()->add( 'form_general', __( 'Something went wrong. The selected payment gateway does not support recurring payments.', 'paid-member-subscriptions' ) );

        }

        // Validate fields for the payment gateway
        if( method_exists( $payment_gateway , 'validate_fields' ) )
            $payment_gateway->validate_fields();

        if( count( pms_errors()->get_error_messages() ) > 0 )
            return false;
        else
            return true;

    }


    /*
     * New Subscription Form logic
     */
    public static function new_subscription_form() {

        // Verify nonce
        if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_new_subscription_form_nonce' ) )
            return;

        // Validate data sent from the new subscription form
        if( !self::validate_new_subscription_form() ){

            // return errors for AJAX requests
            if( wp_doing_ajax() )
                self::return_generated_errors_for_ajax();
            else
                return;

        }

        // Hook useful for saving extra information
        do_action( 'pms_new_subscription_form_extra', get_current_user_id() );

        // Proceed to checkout
        self::process_checkout();

    }

    /**
     * New Subscription Form validation
     */
    public static function validate_new_subscription_form() {

        // Just in case, do not let logged out users get here
        if( !is_user_logged_in() )
            return false;

        // First of all validate the subscription plans
        if( !self::validate_subscription_plans() )
            return false;

        // Get user id
        $member = pms_get_member( pms_get_current_user_id() );

        if( $member->get_subscriptions_count() >= 1 ) {
            pms_errors()->add( 'subscription_plans', __( 'You are already a member.', 'paid-member-subscriptions' ) );
            return;
        }

        // Extra validations
        do_action( 'pms_new_subscription_form_validation' );

        // Stop if there are errors
        if ( count( pms_errors()->get_error_codes() ) > 0 )
            return false;
        else
            return true;

    }


    /*
     * Upgrade Subscription Plan form logic
     */
    public static function upgrade_subscription() {

        // Verify nonce
        if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_upgrade_subscription' ) )
            return;

        // Upgrade subscription
        if( isset( $_POST['pms_upgrade_subscription'] ) ) {

            // Validate data sent from the upgrade subscription form
            if( !self::validate_upgrade_subscription_form() ){

                // return errors for AJAX requests
                if( wp_doing_ajax() )
                    self::return_generated_errors_for_ajax();
                else
                    return;

            }

            // Log attempt
            if( isset( $_GET['subscription_id'] ) )
                pms_add_member_subscription_log( absint( $_GET['subscription_id'] ), 'subscription_upgrade_attempt', array( 'new_plan' => isset( $_POST['subscription_plans'] ) ? absint( $_POST['subscription_plans'] ) : '' ) );

            // Hook useful for saving extra information
            do_action( 'pms_upgrade_subscription_form_extra', get_current_user_id() );

            // Proceed to checkout
            self::process_checkout();

        }

        // Redirect to current page and remove all query arguments
        if( isset( $_POST['pms_redirect_back'] ) ) {
            wp_redirect( esc_url_raw( remove_query_arg( array( 'pms-action', 'subscription_plan', 'subscription_id', 'pmstkn' ), pms_get_current_page_url() ) ) );
            exit;
        }

    }

    /*
     * Upgrade Subscription Plan form validation
     */
    public static function validate_upgrade_subscription_form() {

        // Just in case, do not let logged out users get here
        if( !is_user_logged_in() )
            return false;

        if( !self::validate_subscription_plans($_POST) )
            return false;

        // Extra validations
        do_action('pms_upgrade_subscription_form_validation' );

        // Stop if there are errors
        if ( count( pms_errors()->get_error_codes() ) > 0 )
            return false;
        else
            return true;

    }

    /**
     * Change subscription plan form logic
     */
    public static function change_subscription() {

        // Verify nonce
        if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_change_subscription' ) )
            return;

        // Change subscription
        if( isset( $_POST['pms_change_subscription'] ) ) {

            // Validate data sent from the upgrade subscription form
            if( !self::validate_change_subscription_form() ){

                // return errors for AJAX requests
                if( wp_doing_ajax() )
                    self::return_generated_errors_for_ajax();
                else
                    return;

            }

            // Log Attempt?
            if ( isset( $_GET['subscription_id'] ) && isset( $_POST['subscription_plans'] ) ){

                $subscription = pms_get_member_subscription( absint( $_GET['subscription_id'] ) );
                $context      = pms_get_change_subscription_plan_context( $subscription->subscription_plan_id, absint( $_POST['subscription_plans'] ) );

                pms_add_member_subscription_log( absint( $_GET['subscription_id'] ), 'subscription_'. $context .'_attempt', array( 'new_plan' => isset( $_POST['subscription_plans'] ) ? absint( $_POST['subscription_plans'] ) : '' ) );

            }

            // Hook useful for saving extra information
            do_action( 'pms_change_subscription_form_extra', get_current_user_id() );

            // Proceed to checkout
            self::process_checkout();

        }

        // Redirect to current page and remove all query arguments
        if( isset( $_POST['pms_redirect_back'] ) ) {
            wp_redirect( esc_url_raw( remove_query_arg( array( 'pms-action', 'subscription_plan', 'subscription_id', 'pmstkn' ), pms_get_current_page_url() ) ) );
            exit;
        }

    }

    /**
     * Validate Change subscription plan form
     */
    public static function validate_change_subscription_form() {

        // Just in case, do not let logged out users get here
        if( !is_user_logged_in() )
            return false;

        if( !self::validate_subscription_plans( $_POST ) )
            return false;

        // Extra validations
        do_action( 'pms_change_subscription_form_validation' );

        // Stop if there are errors
        if ( count( pms_errors()->get_error_codes() ) > 0 )
            return false;
        else
            return true;

    }

    /*
     * Renew Form logic
     */
    public static function renew_subscription() {

        // Verify nonce
        if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_renew_subscription' ) )
            return;

        // Renew subscription
        if( isset( $_POST['pms_renew_subscription'] ) ) {

            // Validate data sent from the renew subscription form
            if( !self::validate_renew_subscription_form() ){

                // return errors for AJAX requests
                if( wp_doing_ajax() )
                    self::return_generated_errors_for_ajax();
                else
                    return;

            }

            // Hook useful for saving extra information
            do_action( 'pms_renew_subscription_form_extra', get_current_user_id() );

            // Proceed to checkout
            self::process_checkout();

        }

        // Redirect to current page and remove all query arguments
        if( isset( $_POST['pms_redirect_back'] ) ) {
            wp_redirect( esc_url_raw( remove_query_arg( array( 'pms-action', 'subscription_plan', 'subscription_id', 'pmstkn' ), pms_get_current_page_url() ) ) );
            exit;
        }

    }

    /*
     * Renew Subscription Plan form validation
     */
    public static function validate_renew_subscription_form() {

        // Just in case, do not let logged out users get here
        if( !is_user_logged_in() )
            return false;

        if( !self::validate_subscription_plans($_POST) )
            return false;

        // Extra validations
        do_action( 'pms_renew_subscription_form_validation' );

        // Stop if there are errors
        if ( count( pms_errors()->get_error_codes() ) > 0 )
            return false;
        else
            return true;

    }

    /*
     * Handles manual user subscription cancellation from account shortcode
     */
    public static function cancel_subscription() {

        // Verify nonce
        if( ! isset( $_POST['pmstkn'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['pmstkn'] ), 'pms_cancel_subscription' ) )
            return;

        // Just in case, do not let logged out users get here
        if( ! is_user_logged_in() )
            return;

        if( empty( $_POST['subscription_id'] ) )
            return;

        // Get member subscription
        $member_subscription = pms_get_member_subscription( absint( $_POST['subscription_id'] ) );

        if( is_null( $member_subscription ) )
            return;

        // Remove subscription if confirm button was pressed
        if( isset( $_POST['pms_confirm_cancel_subscription'] ) ) {

            // Extra validations
            do_action('pms_cancel_subscription_form_validation' );

            // Stop if there are errors
            if ( count( pms_errors()->get_error_codes() ) > 0 )
                return;

            $member_data          = self::get_request_member_data();
            $subscription_plan_id = (int)$member_subscription->subscription_plan_id;

            // Optional checks to confirm cancellation, besides the user driven one
            $confirm_remove_subscription = apply_filters( 'pms_confirm_cancel_subscription', true, $member_data['user_id'], $subscription_plan_id );

            // If all is good remove the subscription, if not send an error
            if( true == $confirm_remove_subscription ) {

                $subscription_data = array();
                $subscription_data['status'] = 'canceled';

                // If we have a billing payment date, set it as the expiration date and remove it
                if( empty( $member_subscription->payment_profile_id ) && ! empty( $member_subscription->billing_next_payment ) ) {

                    $subscription_data['expiration_date']      = $member_subscription->billing_next_payment;
                    $subscription_data['billing_next_payment'] = '';

                }

                // Update the subscription
                if( $member_subscription->update( $subscription_data ) ) {

                    pms_success()->add( 'subscription_plans', apply_filters( 'pms_cancel_subscription_success', __( 'Your subscription has been successfully canceled.', 'paid-member-subscriptions' ), $member_data, $member_subscription ) );

                    pms_add_member_subscription_log( $member_subscription->id, 'subscription_canceled' );

                    pms_update_member_subscription_meta( $member_subscription->id, 'pms_retry_payment', 'inactive' );

                    /**
                     * Action for when the cancellation is successful
                     *
                     * @param array $member_data
                     * @param PMS_Member_Subscription $member_subscription
                     *
                     */
                    do_action( 'pms_cancel_member_subscription_successful', $member_data, $member_subscription );

                }

            } else {

                pms_errors()->add( 'subscription_plans', apply_filters( 'pms_cancel_subscription_error', __( 'Something went wrong. We could not cancel your subscription.', 'paid-member-subscriptions' ), $member_data, $member_subscription ) );

                /**
                 * Action for when the cancellation is unsuccessful
                 *
                 * @param array $member_data
                 * @param PMS_Member_Subscription $member_subscription
                 *
                 */
                do_action( 'pms_cancel_member_subscription_unsuccessful', $member_data, $member_subscription );

            }

        }

        // Redirect to current page and remove all query arguments
        if( isset( $_REQUEST['pms_redirect_back'] ) ) {
            wp_redirect( esc_url_raw( remove_query_arg( array( 'pms-action', 'subscription_plan', 'subscription_id', 'pmstkn' ), pms_get_current_page_url() ) ) );
            exit;
        }

    }


    /*
     * Handles manual user subscription abandon from account shortcode
     */
    public static function abandon_subscription() {

        // Verify nonce
        if( ! isset( $_REQUEST['pmstkn'] ) || ! wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_abandon_subscription' ) )
            return;

        // Just in case, do not let logged out users get here
        if( ! is_user_logged_in() )
            return;

        if( empty( $_POST['subscription_id'] ) )
            return;

        // Get member subscription
        $member_subscription = pms_get_member_subscription( absint( $_POST['subscription_id'] ) );

        if( is_null( $member_subscription ) )
            return;

        // Remove subscription if confirm button was pressed
        if( isset( $_REQUEST['pms_confirm_abandon_subscription'] ) ) {

            // Extra validations
            do_action( 'pms_abandon_subscription_form_validation' );

            // Stop if there are errors
            if ( count( pms_errors()->get_error_codes() ) > 0 )
                return;

            $member_data          = self::get_request_member_data();
            $subscription_plan_id = $member_subscription->subscription_plan_id;

            /**
             * Optional checks to confirm cancellation, besides the user driven one
             * It's the same as the cancel subscription one because we need to also cancel subscriptions from the payment gateways
             * and payment gateways already hook to this filter
             *
             */
            $confirm_remove_subscription = apply_filters( 'pms_confirm_cancel_subscription', true, $member_data['user_id'], $subscription_plan_id );

            // If all is good remove the subscription, if not send an error
            if( true == $confirm_remove_subscription ) {

                $subscription_data = array();
                $subscription_data['status'] = 'abandoned';

                // If we have a billing payment date, set it as the expiration date and remove it
                if( empty( $member_subscription->payment_profile_id ) && ! empty( $member_subscription->billing_next_payment ) ) {

                    $subscription_data['expiration_date']      = date( 'Y-m-d H:i:s' );
                    $subscription_data['billing_next_payment'] = '';

                }

                if( $member_subscription->update( $subscription_data ) ) {
                    pms_success()->add( 'subscription_plans', apply_filters( 'pms_abandon_subscription_success', __( 'Your subscription has been successfully removed.', 'paid-member-subscriptions' ) ) );

                    pms_add_member_subscription_log( $member_subscription->id, 'subscription_abandoned' );

                    pms_update_member_subscription_meta( $member_subscription->id, 'pms_retry_payment', 'inactive' );
                }


                do_action( 'pms_abandon_member_subscription_successful', $member_data, $member_subscription );

            } else {

                pms_errors()->add( 'subscription_plans', apply_filters( 'pms_abandon_subscription_error', __( 'Something went wrong. We could not remove your subscription.', 'paid-member-subscriptions' ), $member_data, $member ) );

                do_action( 'pms_abandon_member_subscription_unsuccessful', $member_data, $member_subscription );

            }

        }

        // Redirect to current page and remove all query arguments
        if( isset( $_REQUEST['pms_redirect_back'] ) ) {
            wp_redirect( esc_url_raw( remove_query_arg( array( 'pms-action', 'subscription_plan', 'subscription_id', 'pmstkn' ), pms_get_current_page_url() ) ) );
            exit;
        }

    }

    /*
     * Handles manual user subscription abandon from account shortcode
     */
    public static function update_payment_method() {

        // Verify nonce
        if( ! isset( $_REQUEST['pmstkn'] ) || ! wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_update_payment_method' ) )
            return;

        // Just in case, do not let logged out users get here
        if( ! is_user_logged_in() )
            return;

        if( empty( $_POST['subscription_id'] ) )
            return;

        // Get member and the member's subscription
        $member              = pms_get_member( get_current_user_id() );
        $member_subscription = pms_get_member_subscription( absint( $_POST['subscription_id'] ) );

        if( is_null( $member_subscription ) )
            return;

        if( ! in_array( $member_subscription->id, $member->get_subscription_ids() ) )
            return;

        if( !$member_subscription->is_auto_renewing() || !pms_payment_gateways_support( array( $member_subscription->payment_gateway ), 'update_payment_method' ) )
            return $content;

        do_action( 'pms_update_payment_method_' . $member_subscription->payment_gateway, $member_subscription );

        // Redirect to current page and remove all query arguments
        if( isset( $_REQUEST['pms_redirect_back'] ) ) {
            wp_redirect( esc_url_raw( remove_query_arg( array( 'pms-action', 'subscription_plan', 'subscription_id', 'pmstkn' ), pms_get_current_page_url() ) ) );
            exit;
        }

    }


    /*
     * Retry Payment form logic
     */
    public static function retry_payment_subscription() {

        // Verify nonce
        if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_retry_payment_subscription' ) )
            return;

        // Retry payment for subscription
        if( isset( $_POST['pms_confirm_retry_payment_subscription'] ) ) {

            // Validate data sent from the retry subscription form
            if( !self::validate_retry_payment_form() )
                return;

            // Proceed to checkout
            self::process_checkout();

        }

        // Redirect to current page and remove all query arguments
        if( isset( $_POST['pms_redirect_back'] ) ) {
            wp_redirect( esc_url_raw( pms_get_current_page_url( true ) ));
            exit;
        }

    }

    /*
     * Retry Payment form validation
     */
    public static function validate_retry_payment_form(){

        // Just in case, do not let logged out users get here
        if( !is_user_logged_in() )
            return;

        if( !self::validate_subscription_plans($_POST) )
            return;

        // Extra validations
        do_action('pms_retry_payment_subscription_form_validation' );

        // Stop if there are errors
        if ( count( pms_errors()->get_error_codes() ) > 0 )
            return false;
        else
            return true;

    }


    /*
     * Handles login form validation and redirection
     */
    public static function validate_login_form( $redirect_to, $request, $user ) {

        if( isset( $_POST['pms_login_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['pms_login_nonce'] ), 'pms_login_form_nonce' ) && isset( $_POST['pms_login'] ) && $_POST['pms_login'] == 1 && !empty( $_POST['pms_redirect'] ) ) {

            if( is_wp_error($user) ) {

                $redirect_to   = esc_url_raw( $_POST['pms_redirect'] );

                $error_code    = $user->get_error_code();
                $error_message = $user->get_error_message( $error_code );

                if( $error_code == 'incorrect_password' ){
                    $error_message = '<strong>' . __( 'ERROR:', 'paid-member-subscriptions' ) . '</strong> ';

                    if( isset( $_POST['log'] ) && is_email( $_POST['log'] ) )
                        $error_message .= __( 'The email and password combination is wrong.', 'paid-member-subscriptions' );
                    else
                        $error_message .= __( 'The username and password combination is wrong.', 'paid-member-subscriptions' );
                }

                // If there's no error message then neither the user name or password was entered
                if( empty( $error_message ) )
                    $error_message = '<strong>' . __( 'ERROR:', 'paid-member-subscriptions' ) . '</strong> ' . __( 'Both fields are empty.', 'paid-member-subscriptions' );

                if( isset($error_message) && !empty($error_message) )
                    $redirect_to = add_query_arg( array( 'login_error' => urlencode(base64_encode($error_message)) ) , $redirect_to );

            } else {

                $redirect_to = remove_query_arg( array('login_error'), $redirect_to );

            }

            wp_safe_redirect( $redirect_to );

        }

        return $redirect_to;
    }


    /**
     * Returns an array with the member data from the request,
     * user_id and subscriptions are required to be present
     *
     * @param int $user_id
     *
     * @return array
     *
     */
    public static function get_request_member_data( $user_id = 0 ) {

        $member_id          = ( ! empty( $user_id ) ? $user_id : pms_get_current_user_id() );
        $subscription_plans = ( ! empty( $_POST['subscription_plans'] ) ? array( trim( absint( $_POST['subscription_plans'] ) ) ) : array() );

        /**
         * Member data array base structure
         *
         */
        $member_data = array(
            'user_id'         => $member_id,
            'user_login'      => '',
            'user_email'      => '',
            'first_name'      => '',
            'last_name'       => '',
            'user_pass'       => '',
            'role'            => '',
            'subscriptions'   => $subscription_plans
        );

        /**
         * User is not logged in
         *
         */
        if( empty( $member_id ) ) {

            $member_data['user_login'] = ( isset( $_POST['user_login'] ) ? sanitize_user($_POST['user_login']) : '' );
            $member_data['user_email'] = ( isset( $_POST['user_email'] ) ? sanitize_email( $_POST['user_email'] ) : '' );
            $member_data['first_name'] = ( isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '' );
            $member_data['last_name']  = ( isset( $_POST['last_name'] )  ? sanitize_text_field( $_POST['last_name'] ) : '' );
            $member_data['user_pass']  = ( isset( $_POST['pass1'] ) ? $_POST['pass1'] : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $member_data['role']       = apply_filters( 'pms_change_default_site_user_role', get_option('default_role') );

        /**
         * User is loggedin
         *
         */
        } else {

            $user_data = get_userdata( $member_id );

            if( !empty( $user_data ) && !is_wp_error( $user_data ) ) {
                $member_data['user_email'] = $user_data->user_email;
                $member_data['user_login'] = $user_data->user_login;
                $member_data['first_name'] = $user_data->first_name;
                $member_data['last_name']  = $user_data->last_name;
            }

        }

        /**
         * Filter member data just before returning it
         *
         */
        return apply_filters( 'pms_get_request_member_data', $member_data );

    }


    /**
     * Returns a slug of the form location from which the user made
     * the request
     *
     * @return string
     *
     */
    public static function get_request_form_location( $nonce_name = 'pmstkn' ) {

        $location = '';

        if( !isset( $_REQUEST[ $nonce_name ] ) )

            $location = '';

        else {

            $nonce = sanitize_text_field( $_REQUEST[ $nonce_name ] );

            // Register form
            if( wp_verify_nonce( $nonce, 'pms_register_form_nonce') )
                $location = 'register';

            // Profile Builder Email Confirmation form
            if( wp_verify_nonce( $nonce, 'pms_register_form_email_confirmation_nonce') )
                $location = 'register_email_confirmation';

            // Cancel subscription
            if( wp_verify_nonce( $nonce, 'pms_edit_profile_form_nonce' ) )
                $location = 'edit_profile';

            // Add new subscription
            if( wp_verify_nonce( $nonce, 'pms_new_subscription_form_nonce' ) )
                $location = 'new_subscription';

            // Change subscription
            if( wp_verify_nonce( $nonce, 'pms_change_subscription' ) ){
                $location = 'change_subscription';

                if( isset( $_REQUEST['form_action'] ) ){

                    if( wp_verify_nonce( sanitize_text_field( $_REQUEST['form_action'] ), 'pms_upgrade_subscription' ) )
                        $location = 'upgrade_subscription';

                    if( wp_verify_nonce( sanitize_text_field( $_REQUEST['form_action'] ), 'pms_downgrade_subscription' ) )
                        $location = 'downgrade_subscription';

                }
            }

            // Upgrade subscription
            if( wp_verify_nonce( $nonce, 'pms_upgrade_subscription' ) )
                $location = 'upgrade_subscription';

            // Renew subscription
            if( wp_verify_nonce( $nonce, 'pms_renew_subscription' ) )
                $location = 'renew_subscription';

            // Cancel subscription
            if( wp_verify_nonce( $nonce, 'pms_cancel_subscription' ) )
                $location = 'cancel_subscription';

            // Retry subscription payment
            if( wp_verify_nonce( $nonce, 'pms_retry_payment_subscription' ) )
                $location = 'retry_payment';



            /**
             * For the Discount Codes request made from the PB form where there is no PMS nonce
             *
             * @since 2.0.5
             */
            if( function_exists( 'wp_doing_ajax') && wp_doing_ajax() && isset( $_REQUEST[$nonce_name] ) && $_REQUEST[$nonce_name] === 'pb_form' )
                $location = 'register';

        }

        return apply_filters( 'pms_request_form_location', $location, $_REQUEST );

    }


    /**
     * Automatically log in users after successful registration
     *
     * @param $user_data array
     */
    public static function automatically_log_in( $user_data ) {

        $settings = get_option( 'pms_payments_settings' );

        if ( pms_is_autologin_active() && !empty( $user_data['user_login'] ) && !empty( $user_data['user_pass'] ) ){

            if ( !empty( $settings['gateways']['paypal']['reference_transactions'] ) && !empty( $_POST['pay_gate'] ) && ($_POST['pay_gate'] === 'paypal_express') ){
                //set a transient so we log the user in after payment confirmation
                set_transient( 'pms-rt-autologin', $user_data['user_id'], 60 * 5 );

                return;
            }


            $credentials = array(
                            'user_login'    => $user_data['user_login'],
                            'user_password' => $user_data['user_pass'],
                            'remember'      => true
            );

            wp_signon( $credentials );
        }
    }


    /**
     * Returns the URL where the user should be redirected back to
     * after registering or completing a purchase
     *
     * @return string
     *
     */
    public static function get_redirect_url() {

        $url      = '';
        $location = self::get_request_form_location();

        switch( $location ) {

            case 'register':

                $url = pms_get_register_success_url();

                // Add success message
                if( empty($url) ) {
                    $url = pms_get_current_page_url( true );
                    $url = add_query_arg( array( 'pmsscscd' => base64_encode('subscription_plans'), 'pmsscsmsg' => base64_encode( apply_filters( 'pms_register_subscription_success_message', __( 'Congratulations, you have successfully created an account.', 'paid-member-subscriptions' ) ) ) ), $url );
                }

                break;

            case 'change_subscription':
            case 'upgrade_subscription':
            case 'downgrade_subscription':
            case 'renew_subscription':
            case 'retry_payment':

                $url = pms_get_current_page_url( true );

                // Add success message
                $url = add_query_arg( array( 'pms_gateway_payment_action' => base64_encode( $location ) ), $url );
                break;

            case 'new_subscription':

                $url = pms_get_register_success_url();

                // Add success message
                $url = add_query_arg( array( 'pms_gateway_payment_action' => base64_encode( $location ) ), $url );
                break;
        }

        return apply_filters( 'pms_get_redirect_url', $url, $location );

    }

    /*
     * Handles data sent from the login form
     *
     */
    public static function login_form() {

        if( !isset( $_REQUEST['pms_login'] ) || !isset( $_POST['pms_login_nonce'] ) || !wp_verify_nonce( sanitize_text_field( $_POST['pms_login_nonce'] ), 'pms_login_form_nonce' ) )
            return;

        do_action( 'login_init' );
        do_action( 'login_form_login' );
        do_action( 'pms_process_login_start' );

        $secure_cookie = '';
        // If the user wants ssl but the session is not ssl, force a secure cookie.
        if ( !empty($_POST['log']) && !force_ssl_admin() ) {
            $user_name = sanitize_user($_POST['log']);
            $user = get_user_by( 'login', $user_name );

            if ( ! $user && strpos( $user_name, '@' ) ) {
                $user = get_user_by( 'email', $user_name );
            }

            if ( $user ) {
                if ( get_user_option('use_ssl', $user->ID) ) {
                    $secure_cookie = true;
                    force_ssl_admin(true);
                }
            }
        }

        if ( isset( $_REQUEST['redirect_to'] ) ) {
            $redirect_to = wp_sanitize_redirect( $_REQUEST['redirect_to'] );
        }

        $user = wp_signon( array(), $secure_cookie );

        if ( empty( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
            if ( headers_sent() ) {
                /* translators: 1: Browser cookie documentation URL, 2: Support forums URL */
                $user = new WP_Error( 'test_cookie', sprintf( __( '<strong>ERROR</strong>: Cookies are blocked due to unexpected output. For help, please see <a href="%1$s">this documentation</a> or try the <a href="%2$s">support forums</a>.', 'paid-member-subscriptions' ),
                    'https://codex.wordpress.org/Cookies', 'https://wordpress.org/support/' ) );
            }
        }

        $requested_redirect_to = isset( $_REQUEST['redirect_to'] ) ? wp_sanitize_redirect( $_REQUEST['redirect_to'] ) : '';
        /**
         * Filters the login redirect URL.
         */
        $redirect_to = apply_filters( 'login_redirect', $redirect_to, $requested_redirect_to, $user );

        do_action( 'pms_process_login_end' );

        if ( !is_wp_error($user) ) {
            if ( $redirect_to == 'wp-admin/' || $redirect_to == admin_url() ) {
                // If the user doesn't belong to a blog, send them to user admin. If the user can't edit posts, send them to their profile.
                if ( is_multisite() && !get_active_blog_for_user($user->ID) && !is_super_admin( $user->ID ) )
                    $redirect_to = user_admin_url();
                elseif ( is_multisite() && !$user->has_cap('read') )
                    $redirect_to = get_dashboard_url( $user->ID );
                elseif ( !$user->has_cap('edit_posts') )
                    $redirect_to = $user->has_cap( 'read' ) ? admin_url( 'profile.php' ) : home_url();

                wp_redirect( $redirect_to );
                exit();
            }
            wp_safe_redirect($redirect_to);
            exit();
        } else {
            wp_safe_redirect($redirect_to);
            exit();
        }

    }


    /*
     * Handles data sent from the recover password form
     *
     */
    public static function recover_password_form() {

        /*
         * Username or Email
         */
        if( isset( $_POST['pms_username_email'] ) ) {

            //Check recover password form nonce;
            if( !isset( $_POST['pmstkn'] ) || ( !wp_verify_nonce( sanitize_text_field( $_POST['pmstkn'] ), 'pms_recover_password_form_nonce') ) )
                return;

            if( is_email( $_POST['pms_username_email'] ) )
                $username_email = sanitize_email( $_POST['pms_username_email'] );
            else
                $username_email = sanitize_text_field( $_POST['pms_username_email'] );

            if( empty( $username_email ) )
                pms_errors()->add( 'pms_username_email', __( 'Please enter a username or email address.', 'paid-member-subscriptions' ) );
            else {

                $user = '';
                // verify if it's a username and a valid one
                if ( !is_email($username_email) ) {
                    if ( username_exists($username_email) ) {
                        $user = get_user_by('login',$username_email);
                    }
                        else pms_errors()->add('pms_username_email',__( 'The entered username doesn\'t exist. Please try again.', 'paid-member-subscriptions'));
                }

                //verify if it's a valid email
                if ( is_email( $username_email ) ){
                    if ( email_exists($username_email) ) {
                        $user = get_user_by('email', $username_email);
                    }
                    else pms_errors()->add('pms_username_email',__( 'The entered email wasn\'t found in our database. Please try again.', 'paid-member-subscriptions'));
                }

            }

            // Extra validation
            do_action( 'pms_recover_password_form_validation' );

            //If entered username or email is valid (no errors), email the password reset confirmation link
            if ( count( pms_errors()->get_error_codes() ) == 0 ) {

                if (is_object($user)) {  //user data is set
                    $requestedUserID = $user->ID;
                    $requestedUserLogin = $user->user_login;
                    $requestedUserEmail = $user->user_email;

                    //search if there is already an activation key present, if not create one
                    $key = get_password_reset_key( $user );

                    $reset_url = add_query_arg( array( 'loginName' => urlencode( $requestedUserLogin ), 'key' => $key ), pms_get_current_page_url() );
                    $reset_url = apply_filters( 'pms_recover_password_landing_page_url', $reset_url, $requestedUserID );

                    //Confirmation link email content
                    $recoveruserMailMessage1 = sprintf(__('Someone has just requested a password reset for the following account: <b>%1$s</b><br/><br/>If this was a mistake, just ignore this email and nothing will happen.<br/>To reset your password, visit the following link: %2$s', 'paid-member-subscriptions'), $username_email, '<a href="' . esc_url( $reset_url ) . '">' . esc_url( $reset_url ) . '</a>');
                    $recoveruserMailMessage1 = apply_filters('pms_recover_password_message_content_sent_to_user1', $recoveruserMailMessage1, $requestedUserID, $requestedUserLogin, $requestedUserEmail);

                    //Confirmation link email title
                    $recoveruserMailMessageTitle1 = sprintf(__('Password Reset from "%s"', 'paid-member-subscriptions'), $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES));
                    $recoveruserMailMessageTitle1 = apply_filters('pms_recover_password_message_title_sent_to_user1', $recoveruserMailMessageTitle1, $requestedUserLogin);


                    //we add this filter to enable html encoding
                    add_filter('wp_mail_content_type', array( __CLASS__, 'email_content_type' ) );

                    // Temporary change the from name and from email
                    add_filter( 'wp_mail_from_name', array( 'PMS_Emails', 'pms_email_website_name' ), 20, 1 );
                    add_filter( 'wp_mail_from', array( 'PMS_Emails', 'pms_email_website_email' ), 20, 1 );


                    //send mail to the user notifying him of the reset request
                    if (trim($recoveruserMailMessageTitle1) != '') {
                        $sent = wp_mail($requestedUserEmail, $recoveruserMailMessageTitle1, $recoveruserMailMessage1);
                        if ($sent === false)
                            pms_errors()->add('pms_username_email',__( 'There was an error while trying to send the activation link.', 'paid-member-subscriptions'));
                    }

                    // Reset the from name and email
                    remove_filter( 'wp_mail_from_name', array( 'PMS_Emails', 'pms_email_website_name' ), 20 );
                    remove_filter( 'wp_mail_from', array( 'PMS_Emails', 'pms_email_website_email' ), 20 );

                    if( $sent === true )
                        do_action( 'pms_password_reset_email_sent', $user, $key );

                }
            }

        } // isset($_POST[pms_username_email])


        // If the user clicked the email confirmation link, make the verifications and change password
        if ( !empty($_GET['loginName']) && !empty($_GET['key']) ) {

            //Check new password form nonce;
            if( !isset( $_POST['pmstkn'] ) || ( !wp_verify_nonce( sanitize_text_field( $_POST['pmstkn'] ), 'pms_new_password_form_nonce') ) )
                return;

            //check if the new password form was submitted
            if ( !empty($_POST['pms_new_password']) && !empty($_POST['pms_repeat_password']) ) {

                $new_pass    = trim($_POST['pms_new_password']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $repeat_pass = trim($_POST['pms_repeat_password']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

                if ($new_pass != $repeat_pass )
                    pms_errors()->add('pms_repeat_password',__( 'The entered passwords don\'t match! Please try again.', 'paid-member-subscriptions'));

                do_action( 'pms_recover_password_form_change_password_validation' );

                $login = sanitize_user( $_GET['loginName'] );
                $key   = sanitize_text_field( $_GET['key'] );

                $user = check_password_reset_key( $key, $login );

                if ( ( count( pms_errors()->get_error_codes() ) == 0 ) && !is_wp_error($user) ) {
                    // update the new password
                    wp_set_password( $new_pass, $user->ID );
                    //delete the user activation key
                    update_user_meta($user->ID, 'user_activation_key', '' );

                    do_action( 'pms_password_reset', $user->ID, $new_pass );
                }

            }

        }

    }


    /*
     * Handles data received from the edit profile form
     *
     */
    public static function edit_profile() {

        // Verify nonce
        if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['pmstkn'] ), 'pms_edit_profile_form_nonce' ) )
            return;

        // Just in case, do not let logged out users get here
        if( !is_user_logged_in() )
            return;

        $user = get_userdata( pms_get_current_user_id() );

        /*
         * E-mail
         */
        if( !isset( $_POST['user_email'] ) )
            pms_errors()->add( 'user_email', __( 'Please enter an e-mail address.', 'paid-member-subscriptions' ) );

        if( isset( $_POST['user_email'] ) ) {

            $user_email = sanitize_email( $_POST['user_email'] );

            if( empty( $user_email ) )
                pms_errors()->add( 'user_email', __( 'Please enter an e-mail address.', 'paid-member-subscriptions' ) );
            else {

                if( !is_email( $user_email ) )
                    pms_errors()->add( 'user_email', __( 'The e-mail address doesn\'t seem to be valid.', 'paid-member-subscriptions' ) );
                elseif( $user->user_email != $user_email ) {

                    $check_user = get_user_by( 'email', $user_email );

                    if( $check_user )
                        pms_errors()->add( 'user_email', __( 'This e-mail is already registered. Please choose another one.', 'paid-member-subscriptions' ) );

                }

            }

        }

        /*
         * First name and last name
         */
        $user_first_name = ( isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '' );
        $user_last_name  = ( isset( $_POST['last_name'] )  ? sanitize_text_field( $_POST['last_name'] ) : '' );


        /*
         * Password
         */
        if( ( isset( $_POST['pass1'] ) && !empty( $_POST['pass1'] ) ) && ( isset( $_POST['pass2'] ) && !empty( $_POST['pass2'] ) ) ) {

            $pass1 = trim($_POST['pass1']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $pass2 = trim($_POST['pass2']); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

            // Check for HTML in the fields
            if( strip_tags( $pass1 ) != $pass1 )
                pms_errors()->add( 'pass1', __( 'Some of the characters entered were not valid.', 'paid-member-subscriptions' ) );

            if( strip_tags( $pass2 ) != $pass2 )
                pms_errors()->add( 'pass1', __( 'Some of the characters entered were not valid.', 'paid-member-subscriptions' ) );

            if( (strip_tags( $pass1 ) == $pass1) && (strip_tags( $pass2 ) == $pass2) ) {

                if( $pass1 != $pass2 )
                    pms_errors()->add( 'pass2', __( 'The passwords did not match.', 'paid-member-subscriptions' ) );

            }

        }

        // Extra validation
        do_action( 'pms_edit_profile_form_validation' );


        // Stop if there are errors
        if( count( pms_errors()->get_error_codes() ) > 0 )
            return;

        /*
         * Update user information
         */
        $user_data = array(
            'ID'          => $user->ID,
            'first_name'  => $user_first_name,
            'last_name'   => $user_last_name
        );

        if( isset($user_email) )
            $user_data['user_email'] = $user_email;

        if( isset( $pass1 ) )
            $user_data['user_pass'] = $pass1;

        $user_id = wp_update_user( $user_data );

        if( !is_wp_error($user_id) ) {

            // Hook useful for saving extra user information (e.g. custom user fields)
            do_action( 'pms_edit_profile_form_update_user', $user_data['ID'] );

            pms_success()->add('edit_profile', __('Profile updated successfully', 'paid-member-subscriptions'));
        }
        else
            pms_errors()->add( 'edit_profile', __( 'Something went wrong. We could not update your profile.', 'paid-member-subscriptions' ) );

    }


    /**
     * Verifies at checkout time if the subscription plan should become a recurring
     * subscription
     *
     * @return bool
     *
     */
    public static function checkout_is_recurring() {

        if( empty( $_POST['subscription_plans'] ) )
            return false;

        $subscription_plan = pms_get_subscription_plan( absint( $_POST['subscription_plans'] ) );

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
        if( $subscription_plan->recurring == 1 ) {

            // User checked the auto-renew checkbox
            if( ! empty( $_POST['pms_recurring'] ) )
                return true;

            // User did not check the auto-renew checkbox
            else
                return false;

        }

        // Subscription plan has option: settings default
        if( empty( $subscription_plan->recurring ) ) {

            $settings           = get_option( 'pms_payments_settings', array() );
            $settings_recurring = empty( $settings['recurring'] ) ? 0 : (int)$settings['recurring'];

            if( empty( $settings_recurring ) )
                return false;

            // Settings has option: always recurring
            if( $settings_recurring == 2 )
                return true;

            // Settings has option: never recurring
            if( $settings_recurring == 3 )
                return false;

            // Settings has option: customer opts in
            if( $settings_recurring == 1 ) {

                // User checked the auto-renew checkbox
                if( ! empty( $_POST['pms_recurring'] ) )
                    return true;

                // User did not check the auto-renew checkbox
                else
                    return false;

            }

        }

    }


    /**
     * Determines if the subscription plan selected can have a trial period
     * in the current checkout
     *
     */
    public static function checkout_has_trial() {

        $form_location   = self::get_request_form_location();
        $payment_gateway = self::checkout_get_payment_gateway();

        if( is_null( $payment_gateway ) )
            return false;

        // Set has trial
        $has_trial = false;

        if( in_array( $form_location, array( 'register', 'new_subscription', 'retry_payment', 'upgrade_subscription', 'register_email_confirmation', 'change_subscription' ) ) ) {

            if( $payment_gateway->supports( 'subscription_free_trial' ) ) {

                if( !empty( $_POST['subscription_plans'] ) ) {
                    // Get subscription plan
                    $subscription_plan = pms_get_subscription_plan( absint( $_POST['subscription_plans'] ) );

                    if ( $subscription_plan->has_trial() ) {
                        $has_trial = true;
                    }
                }

            }

        }

        return $has_trial;

    }


    /**
     * Returns the payment gateway object from the user's selection on the checkout page
     *
     * @param array $payment_gatway_data
     *
     * @return PMS_Payment_Gateway_{{gateway}}
     *
     */
    public static function checkout_get_payment_gateway( $payment_gateway_data = array() ) {

        if( ! empty( $_POST['pay_gate'] ) ) {

            $pay_gate        = sanitize_text_field( $_POST['pay_gate'] );
            $payment_gateway = pms_get_payment_gateway( $pay_gate, $payment_gateway_data );

        } else {

            $payment_gateway = null;

        }

        return $payment_gateway;

    }

    // NOTE: Should be refactored in a new class
    /**
     * Checkout process
     *
     * - validates the data from the forms
     * - registers the user if the request is coming from the register form
     * - prepares the subscription and payment data
     * - sends the payment to be processed by the payment gateway and activates the subscription
     *   if everything is okay
     *
     */
    public static function process_checkout( $user_data = array() ) {

        if( empty( $user_data ) )
            $user_data = self::get_request_member_data();

        $form_location = self::get_request_form_location();

        // Verify the validity of the subscription plans
        self::validate_subscription_plans();

        // Verify if the member has eligibility to subscribe to the selected subscription plans
        self::validate_subscription_plans_member_eligibility( $user_data );

        // Verify the validity of the payment gateway and what it supports
        self::validate_payment_gateway( $form_location );


        /**
         * Allow extra validations before the processing of the checkout
         *
         */
        do_action( 'pms_process_checkout_validations' );


        /**
         * Stop if there are errors
         *
         */
        if ( count( pms_errors()->get_error_codes() ) > 0 ){

            // return errors for AJAX requests
            if( wp_doing_ajax() )
                self::return_generated_errors_for_ajax();
            else
                return;
            
        }

        /**
         * If we're on the register form register the user
         *
         */
        if( $form_location == 'register' && empty( $user_data['user_id'] ) ) {

            $user_id = self::register_user( $user_data );

            if( $user_id === false ){
                pms_errors()->add( 'user_registration', __( 'Something went wrong while registering the user. Contact the website administrator.', 'paid-member-subscriptions' ) );
                return;
            } else
                $user_data['user_id'] = $user_id;

        }

        /**
         * Set-up some data for future use
         *
         */
        $payments_settings = get_option( 'pms_payments_settings' );
        $subscription_plan = pms_get_subscription_plan( $user_data['subscriptions'][0] );

        // Get payment gateway
        $pay_gate        = ( ! empty( $_POST['pay_gate'] ) ? sanitize_text_field( $_POST['pay_gate'] ) : '' );
        $payment_gateway = self::checkout_get_payment_gateway();

        /**
         * If the payment gateway is null then no payment is necessary
         * because the subscription plan is free
         *
         */
        if( is_null( $payment_gateway ) )
            $needs_payment = false;
        else
            $needs_payment = true;


        // For backwards compatibility cache the subscription plan
        // in the user_data array
        $user_data['subscription'] = $subscription_plan;

        // Set recurring value
        $is_recurring         = self::checkout_is_recurring();
        $has_trial            = self::checkout_has_trial();
        $gateway_supports_psp = !is_null( $payment_gateway ) && $payment_gateway->supports( 'plugin_scheduled_payments' ) ? true : false;

        // Check if user already used the trial
        if( !empty( $user_data['user_email'] ) ){

            $used_trial = get_option( 'pms_used_trial_' . $subscription_plan->id, false );

            if( !empty( $used_trial ) && in_array( $user_data['user_email'], $used_trial ) )
                $has_trial = false;

        }

        // Filter checkout trial status
        $has_trial = apply_filters( 'pms_checkout_has_trial', $has_trial, $user_data, $subscription_plan, $form_location, $pay_gate, $is_recurring );

        // Cache the checkout details
        $checkout_data = array(
            'is_recurring'  => $is_recurring,
            'has_trial'     => $has_trial,
            'form_location' => $form_location
        );


        /**
         * Subscription data
         *
         */
        $subscription_data = self::get_subscription_data( $user_data['user_id'], $subscription_plan, $form_location, $gateway_supports_psp, $pay_gate, $is_recurring, $has_trial );

        /**
         * Filter the subscription data after its been set
         *
         * @param array $subscription_data
         *
         */
        $subscription_data = apply_filters( 'pms_process_checkout_subscription_data', $subscription_data, $checkout_data );


        /**
         * Insert the subscription into the db
         *
         */
        if( in_array( $form_location, array( 'register', 'new_subscription', 'register_email_confirmation' ) ) ) {

            /**
             * We can't assume that this won't get executed multiple times. ( on PB registration if we had the field multiple times this executed as many times as the number of fields
             * and resulted in the user having the same subscription multiple times )
             * After a discussion we decided to make sure that the user can't have the same subscription multiple times amd we should prevent this
             * There is a possible feature request to allow the same subscription multiple times but for now we leave it like this
             */
            $subscription_already_exist = false;
            $current_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $user_data['user_id'] ) );
            if( !empty( $current_subscriptions ) ){
                foreach( $current_subscriptions as $current_subscription ) {
                    if( $current_subscription->subscription_plan_id == $subscription_data['subscription_plan_id'] ){
                        $subscription = $current_subscription;
                        $subscription_already_exist = true;
                        break;
                    }
                }
            }

            if( !$subscription_already_exist ) {
                $subscription = new PMS_Member_Subscription();
                $subscription->insert($subscription_data);

                pms_add_member_subscription_log( $subscription->id, 'subscription_added' );

                /**
                 * Action that fires after inserting the $subscription_data inside the db
                 *
                 * @param object $subscription
                 * @param array $checkout_data
                 */
                do_action( 'pms_after_inserting_subscription_data_inside_db', $subscription, $checkout_data);
            }

        /**
         * Grab the existing member subscription
         *
         */
        } else {

            if( isset( $_POST['pms_current_subscription'] ) ){

                $subscription = pms_get_member_subscription( absint( $_POST['pms_current_subscription'] ) );

            } else {

                $current_subscriptions    = pms_get_member_subscriptions( array( 'user_id' => $user_data['user_id'] ) );
                $subscription_plans_group = pms_get_subscription_plans_group( $subscription_plan->id );

                foreach( $subscription_plans_group as $subscription_plan_sibling ) {
                    foreach( $current_subscriptions as $current_subscription ) {
                        if( $subscription_plan_sibling->id == $current_subscription->subscription_plan_id ) {
                            break 2;
                        }
                    }
                }

                $subscription = $current_subscription;

            }

        }

        /**
         * Calculate amount to be paid on the initial payment
         *
         */
        if( $has_trial ) {

            if( ! is_null( $payment_gateway ) && $payment_gateway->supports( 'subscription_sign_up_fee' ) && in_array( $form_location, apply_filters( 'pms_checkout_signup_fee_form_locations', array( 'register', 'new_subscription', 'retry_payment', 'register_email_confirmation', 'change_subscription', 'wppb_register' ), $form_location, $subscription ) ) )
                $amount = $subscription_plan->sign_up_fee;
            else
                $amount = 0;

        } else {

            if( ! is_null( $payment_gateway ) && $payment_gateway->supports( 'subscription_sign_up_fee' ) && in_array( $form_location, apply_filters( 'pms_checkout_signup_fee_form_locations', array( 'register', 'new_subscription', 'retry_payment', 'register_email_confirmation', 'change_subscription', 'wppb_register' ), $form_location, $subscription ) ) )
                $amount =  $subscription_plan->price + $subscription_plan->sign_up_fee;
            else
                $amount =  $subscription_plan->price;

        }

        // Handle the case in which a non-recurring Fixed Period Membership has a free trial that ends on the expiration date and no sign up fee
        if( $has_trial && !$is_recurring && $subscription_plan->is_fixed_period_membership() && ( $subscription_plan->get_trial_expiration_date() == $subscription_plan->get_expiration_date() ) && !$subscription_plan->has_sign_up_fee() )
            $needs_payment = false;


        /**
         * With the payment response we will activate the member's subscription data
         *
         */
        if( $needs_payment )
            $payment_response = false;
        else
            $payment_response = true;


        /**
         * Filter amount that the user has to pay
         *
         */
        $amount = apply_filters( 'pms_checkout_payment_amount', $amount, $subscription );

        /**
         * If we have an amount to charge or we have a trial that will need a payment in the future,
         * go further to the payment gateway
         *
         */
        if( ! empty( $amount ) || ( empty( $amount ) && $has_trial ) ) {

            /**
             * Prepare payment gateway data, this is actually the "old" payment_data
             *
             */
            $payment_gateway_data = array(
                'user_data'         => $user_data,
                'subscription_data' => $subscription_data,
                'sign_up_amount'    => null,
                'redirect_url'      => self::get_redirect_url(),
                'form_location'     => $form_location,
                'recurring'         => $is_recurring
            );

            /**
             * Payment data
             *
             */
            $payment_data = array(
                'user_id'              => $user_data['user_id'],
                'subscription_plan_id' => $subscription_plan->id,
                'date'                 => date( 'Y-m-d H:i:s' ),
                'amount'               => $amount,
                'payment_gateway'      => $pay_gate,
                'currency'             => pms_get_active_currency(),
                'status'               => 'pending'
            );

            // Payment type
            if( in_array( $form_location, array( 'register', 'new_subscription', 'retry_payment', 'register_email_confirmation' ) ) )
                $payment_data['type'] = 'subscription_initial_payment';

            elseif( $form_location == 'renew_subscription' )
                $payment_data['type'] = 'subscription_renewal_payment';

            elseif( $form_location == 'upgrade_subscription' )
                $payment_data['type'] = 'subscription_upgrade_payment';

            elseif( $form_location == 'downgrade_subscription' )
                $payment_data['type'] = 'subscription_downgrade_payment';

            elseif( $form_location == 'change_subscription' )
                $payment_data['type'] = 'subscription_initial_payment';

            // Payment for a non-recurring Fixed Period Membership has a free trial that ends on the expiration date and no sign up fee
            if( $has_trial && !$is_recurring && $subscription_plan->is_fixed_period_membership() && ( $subscription_plan->get_trial_expiration_date() == $subscription_plan->get_expiration_date() ) && !$subscription_plan->has_sign_up_fee() && ( $payment_data['payment_gateway'] == 'paypal_standard' || $payment_data['payment_gateway'] == 'paypal_express' ) ){
                $payment_data['type']   = $payment_data['payment_gateway'] . '_trial_payment';
                $payment_data['status'] = 'completed';
            }

            /**
             * Filter the subscription data after its been set
             *
             * @NOTE: This is not used when the payment happens through the Stripe (Payment Intents) gateway
             * since the gateway is called directly through AJAX to build the data
             *
             * @param array $payment_data
             *
             */
            $payment_data = apply_filters( 'pms_process_checkout_payment_data', $payment_data, $checkout_data );


            /**
             * Insert the payment into the db
             *
             * Allow for empty amounts to be introduced for PayPal subscriptions, as it is the amount considered for the trial period
             *
             */
            if( ! empty( $payment_data['amount'] ) || ( in_array( $pay_gate, array( 'paypal_standard', 'paypal_express' ) ) && $has_trial ) ) {

                $payment = new PMS_Payment();
                $payment->insert( $payment_data );

                $payment_gateway_data['payment_id'] = $payment->id;

                // Save subscription id as payment meta
                pms_add_payment_meta( $payment->id, 'subscription_id', $subscription->id, true );

            }

            $payment_gateway_data = array_merge( $payment_gateway_data, $payment_data );


            /**
             * Filter the payment data just before sending it to the payment gateway
             *
             * @param array $payment_gateway_data
             * @param array $payments_settings
             *
             */
            $payment_gateway_data = apply_filters( 'pms_register_payment_data', $payment_gateway_data, $payments_settings );


            // Log retry payment attempt
            if( $form_location == 'retry_payment' )
                pms_add_member_subscription_log( $subscription->id, 'subscription_retry_attempt' );

            /**
             * Action that fires just before sending the user to the payment processor
             *
             * @param array $payment_gateway_data
             *
             */
            do_action( 'pms_register_payment', $payment_gateway_data );

            if( ! empty( $payment_gateway_data['amount'] ) || ! empty( $payment_gateway_data['sign_up_amount'] ) || ( empty( $payment_gateway_data['amount'] ) && $has_trial ) ) {

                // Get payment gateway
                $payment_gateway = self::checkout_get_payment_gateway( $payment_gateway_data );


                /**
                 * If the payment gateway supports user payment agreements, as in a user can choose to Agree
                 * that the payment gateway can make further payments without any input from the user,
                 * then process a one time payment and activate the subscription
                 *
                 */
                if( ! is_null( $payment_gateway ) && $payment_gateway->supports( 'plugin_scheduled_payments' ) ) {

                    /**
                     * Save needed payment gateway subscription data
                     *
                     */
                    $register_automatic_billing_info_response = $payment_gateway->register_automatic_billing_info( $subscription->id );

                    /**
                     * If we need to make a payment, we make it
                     *
                     */
                    if( $register_automatic_billing_info_response && ! empty( $amount ) && $payment_gateway_data['sign_up_amount'] != '0' ) {

                        $payment_response = $payment_gateway->process_payment( $payment->id, $subscription->id );

                        $subscription_data['billing_last_payment'] = date( 'Y-m-d H:i:s' );

                    /**
                     * If there is no payment to be made consider the payment as being made,
                     * for example when we have a free trial subscription
                     *
                     */
                     } else {
                         if( $register_automatic_billing_info_response )
                            $payment_response = true;
                     }

                /**
                 * If the payment gateway doesn't support such agreements we direct the data to the payment
                 * gateway to handle it
                 *
                 */
                } else {

                    /**
                     * If we get here, the request will be sent to the process_sign_up() method of the payment gateway
                     *
                     * $payment_response will remain set to false so the method needs to take care of what happens next:
                     *      - send the user to the gateway for payment and capture the return flow
                     *      - attempt to charge the user and handle success scenario
                     *
                     * In case of an error, don't intrerrupt the flow (basically, do not redirect) and the plugin will redirect to the
                     * error page (see else @ line 1864 below)
                     */
                    if( $needs_payment )
                        pms_to_gateway( $pay_gate, $payment_gateway_data );

                }

            } else
                $payment_response = true;

        }

        // If all good handle subscriptions
        if( $payment_response ) {

            $subscription_data['status'] = 'active';

            // Handle each subscription by the form location
            switch( $form_location ) {

                case 'register':
                // new subscription
                case 'new_subscription':
                // register form E-mail Confirmation compatibility
                case 'register_email_confirmation':
                // retry payment
                case 'retry_payment':

                    $subscription->update( $subscription_data );

                    if( isset( $has_trial ) && $has_trial == true && ( ( isset( $register_automatic_billing_info_response ) && $register_automatic_billing_info_response == true ) || ( !$is_recurring && $subscription_plan->is_fixed_period_membership() && strtotime( $subscription_plan->get_expiration_date() ) <= strtotime( $subscription_plan->get_trial_expiration_date() ) ) ) ){
                        pms_add_member_subscription_log( $subscription->id, 'subscription_trial_started', array( 'until' => $subscription_data['trial_end'] ) );

                        // Save email when trial is used
                        $user       = get_userdata( $user_data['user_id'] );
                        $used_trial = get_option( 'pms_used_trial_' . $subscription_plan->id, false );

                        if( $used_trial == false )
                            $used_trial = array( $user->user_email );
                        else
                            $used_trial[] = $user->user_email;

                        update_option( 'pms_used_trial_' . $subscription_plan->id, $used_trial, false );
                    }

                    pms_add_member_subscription_log( $subscription->id, 'subscription_activated', array( 'until' => $subscription_data['expiration_date'] ) );

                    break;

                // upgrading the subscription
                case 'upgrade_subscription':
                // downgrade the subscription
                case 'downgrade_subscription':
                // changing the subscription
                case 'change_subscription':

                    // Payment can be not set when the new subscription plan is free
                    if( !isset( $payment ) )
                        $payment = '';

                    do_action( 'pms_psp_before_'. $form_location, $subscription, $payment, $subscription_data );

                    $context = 'change';

                    if( $form_location == 'upgrade_subscription' )
                        $context = 'upgrade';
                    elseif( $form_location == 'downgrade_subscription' )
                        $context = 'downgrade';

                    pms_add_member_subscription_log( $subscription->id, 'subscription_'. $context .'_success', array( 'old_plan' => $subscription->subscription_plan_id, 'new_plan' => $subscription_data['subscription_plan_id'] ) );

                    // If current checkout is not recurring, we need to clear the recurring schedule data
                    if( !$is_recurring ){
                        $subscription_data['billing_next_payment']  = '';
                        $subscription_data['billing_duration']      = '';
                        $subscription_data['billing_duration_unit'] = '';
                    }

                    $subscription->update( $subscription_data );

                    do_action( 'pms_psp_after_'. $form_location, $subscription, $payment );

                    pms_delete_member_subscription_meta( $subscription->id, 'pms_retry_payment' );

                    break;

                case 'renew_subscription':

                    if( strtotime( $subscription->expiration_date ) < time() || ( !$subscription_plan->is_fixed_period_membership() && $subscription_plan->duration === 0 ) || ( $subscription_plan->is_fixed_period_membership() && !$subscription_plan->fixed_period_renewal_allowed() ) )
                        $expiration_date = $subscription_plan->get_expiration_date();
                    else {
                        if( $subscription_plan->is_fixed_period_membership() ){
                            $expiration_date = date( 'Y-m-d 23:59:59', strtotime( $subscription->expiration_date . '+ 1 year' ) );
                        }
                        else{
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

        /**
         * Redirect to the error page if the payment was not successful
         *
         */
        } else {

            /**
             * Action that fires when a checkout error occurs and the plugin will redirect 
             * away to the error page
             *
             * @param object $subscription
             * @param object $payment
             */
            do_action( 'pms_checkout_error_before_redirect', $subscription, isset( $payment ) ? $payment : null, $form_location );

            if( wp_doing_ajax() ){

                if( isset( $payment ) )
                    self::return_failed_payment_redirect_for_ajax( $payment->id, $form_location );
                // TODO: have a clean return here with the other message without a payment_id

            } else {

                if( isset( $_POST['pmstkn'] ) ) {

                    if( isset( $payment ) && isset( $payment->id ) )
                        $redirect_url = add_query_arg( array( 'pms_payment_error' => '1', 'pms_is_register' => ( in_array( $form_location, array( 'register', 'register_email_confirmation' ) ) ) ? '1' : '0', 'pms_payment_id' => $payment->id ), pms_get_current_page_url( true ) );
                    else
                        $redirect_url = add_query_arg( array( 'pms_payment_error' => '1', 'pms_is_register' => ( in_array( $form_location, array( 'register', 'register_email_confirmation' ) ) ) ? '1' : '0' ), pms_get_current_page_url( true ) );

                    wp_redirect( $redirect_url );
                    exit;

                }

            }

        }

        /**
         * Action that fires after the checkout process is finished (only for PSP subscriptions)
         *
         * @param object $subscription
         * @param string $form_location
         */
        do_action( 'pms_after_checkout_is_processed', $subscription, $form_location );

        /**
         * Redirect right at the end if everything worked like it should have
         *
         */
        if( isset( $_POST['pmstkn'] ) ) {

            if( isset( $payment ) && isset( $payment->id ) )
                $success_redirect_link = add_query_arg( array( 'pmsscscd' => base64_encode( 'subscription_plans' ), 'pms_gateway_payment_action' => base64_encode( $form_location ), 'pms_gateway_payment_id' => base64_encode( $payment->id ) ), self::get_redirect_url() );

            else
                $success_redirect_link = add_query_arg( array( 'pmsscscd' => base64_encode( 'subscription_plans' ), 'pms_gateway_payment_action' => base64_encode( $form_location ) ), self::get_redirect_url() );

            wp_redirect( $success_redirect_link );
            exit;

        }

    }

    public static function get_subscription_data( $user_id, $subscription_plan, $form_location, $psp_supported, $pay_gate = null, $is_recurring = null, $has_trial = null ){

        if( empty( $pay_gate ) )
            $pay_gate = ! empty( $_POST['pay_gate'] ) ? sanitize_text_field( $_POST['pay_gate'] ) : '';

        if( is_null( $is_recurring ) )
            $is_recurring = self::checkout_is_recurring();

        if( empty( $has_trial ) ){
            $has_trial = self::checkout_has_trial();

            $user = get_userdata( $user_id );

            if( !empty( $user->user_email ) ){

                $used_trial = get_option( 'pms_used_trial_' . $subscription_plan->id, false );

                if( !empty( $used_trial ) && in_array( $user->user_email, $used_trial ) )
                    $has_trial = false;

            }

            $has_trial = apply_filters( 'pms_checkout_has_trial', $has_trial, array( 'user_id' => $user_id ), $subscription_plan, $form_location, $pay_gate, $is_recurring );

        }

        // Base data
        $subscription_data = array(
            'user_id'              => $user_id,
            'subscription_plan_id' => $subscription_plan->id,
            'expiration_date'      => $subscription_plan->get_expiration_date(),
            'status'               => 'pending',
            'payment_gateway'      => $pay_gate,
            // billing_amount is used for payments only for psp supported gateways, but if the price is modified (pwyw, dc) it will be updated here for all gateways
            'billing_amount'       => $subscription_plan->price,
        );

        // Add start date for new subscriptions
        if( in_array( $form_location, array( 'register', 'new_subscription', 'register_email_confirmation' ) ) )
            $subscription_data['start_date'] = date('Y-m-d H:i:s');

        // Add trial data
        if( $has_trial ) {

            // Add trial end
            $subscription_data['trial_end'] = $subscription_plan->get_trial_expiration_date();

            // Can be unlimited which comes as an empty string
            $expiration_date = $subscription_plan->get_expiration_date( true );

            if( empty( $expiration_date ) )
                $expiration_date = time();

            // Extend expiration date to accomodate the trial period
            if( $subscription_plan->is_fixed_period_membership() ) {

                $subscription_data['expiration_date'] = date( 'Y-m-d H:i:s', $expiration_date );
                $days_difference = ( strtotime( $subscription_plan->get_expiration_date() ) - strtotime( $subscription_plan->get_trial_expiration_date() ) ) / 86400;

            } else {

                if( $pay_gate == 'paypal_express' )
                    $subscription_data['expiration_date'] = $subscription_data['trial_end'];
                else
                    $subscription_data['expiration_date'] = date( 'Y-m-d H:i:s', strtotime( "+" . $subscription_plan->trial_duration . ' ' . $subscription_plan->trial_duration_unit, $expiration_date ) );

            }

        } else
            $subscription_data['trial_end'] = '';

        // Add custom payment schedule data to the subscription
        if( $psp_supported ) {

            $subscription_data['payment_profile_id'] = '';

            if( $is_recurring && !$subscription_plan->is_fixed_period_membership() ) {
                $subscription_data['expiration_date']       = '';
                $subscription_data['billing_duration']      = $subscription_plan->duration;
                $subscription_data['billing_duration_unit'] = $subscription_plan->duration_unit;
                $subscription_data['billing_next_payment']  = ( ! empty( $subscription_plan->duration ) ? $subscription_plan->get_expiration_date() : '' );
            }
            elseif( $is_recurring && $subscription_plan->is_fixed_period_membership() ){
                $subscription_data['expiration_date']       = '';
                if( isset( $days_difference ) && $days_difference > 0 ){
                    $subscription_data['billing_duration']      = $days_difference;
                    $subscription_data['billing_duration_unit'] = 'day';
                    $subscription_data['billing_next_payment']  = $subscription_plan->get_trial_expiration_date();
                } else{
                    $subscription_data['billing_duration']      = '1';
                    $subscription_data['billing_duration_unit'] = 'year';
                    $subscription_data['billing_next_payment']  = $subscription_plan->get_expiration_date();
                }
            } else {
                $subscription_data['billing_duration']      = '';
                $subscription_data['billing_duration_unit'] = '';
                $subscription_data['billing_next_payment']  = '';
            }

            if( ( !$subscription_plan->is_fixed_period_membership() && $has_trial ) || ( $has_trial && strtotime( $subscription_plan->get_expiration_date() ) > strtotime( $subscription_plan->get_trial_expiration_date() ) ) ){
                $trial_expiration_date = $subscription_plan->get_trial_expiration_date();
                $subscription_data['billing_next_payment'] = !empty( $trial_expiration_date ) ? $trial_expiration_date : $subscription_data['billing_next_payment'];
            }

        }

        return $subscription_data;
    }

    public static function return_failed_payment_redirect_for_ajax( $payment_id, $form_location ){
        
        $redirect_page = '';

        if( in_array( $form_location, array( 'register', 'new_subscription', 'register_email_confirmation' ) ) )
            $redirect_page = pms_get_page( 'register', true );
        else
            $redirect_page = pms_get_page( 'account', true );

        if( empty( $redirect_page ) && !empty( $_POST['current_page'] ) )
            $redirect_page = esc_url_raw( $_POST['current_page'] );

        // TODO: Log this case / add a notice / by adding an option or something we can make the user aware of this 'error'
        if( empty( $redirect_page ) )
            die();

        $data                 = array();
        $data['error']        = true;
        $data['redirect_url'] = add_query_arg(
            array(
                'pms_payment_error' => '1',
                'pms_is_register'   => in_array( $form_location, array( 'register', 'register_email_confirmation' ) ) ? '1' : '0',
                'pms_payment_id'    => $payment_id ),
            $redirect_page
        );

        echo json_encode( $data );
        die();

    }

    // TODO: this should be removed and replaced with PMS_Emails::pms_email_content_type()
    /**
     * Used to enable HTML in the password recovery email
     * @return string
     */
    public static function email_content_type() {
        return 'text/html';
    }

    public static function return_generated_errors_for_ajax(){

        $errors = pms_get_generated_errors();

        echo json_encode( array(
            'success' => false,
            'data'    => $errors,
        ) );
        die();

    }

}

PMS_Form_Handler::init();
