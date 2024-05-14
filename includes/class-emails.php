<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Emails Class contains the necessary functions for sending emails to users
 *
 */
Class PMS_Emails {

    /**
     * Initializer for the class where we hook in the actions to send emails
     *
     */
    static function init() {

        add_action( 'pms_register_form_after_create_user', array( 'PMS_Emails', 'send_registration_email' ) );

        add_action( 'pms_member_subscription_insert', array( 'PMS_Emails', 'send_emails' ), 10, 2 );
        add_action( 'pms_member_subscription_update', array( 'PMS_Emails', 'send_emails' ), 10, 3 );

        add_action( 'pms_member_subscription_update', array( 'PMS_Emails', 'send_renewal_email' ), 11, 3 );

        add_action( 'pms_payment_update', array( 'PMS_Emails', 'send_payment_failed_email' ), 10, 3 );

        $settings = get_option('pms_emails_settings', array() );
        if( !empty( $settings['reset_password_is_enabled'] ) ){
            add_filter('pms_recover_password_message_title_sent_to_user1', array('PMS_Emails', 'send_reset_password_email_subject'), 10, 2);
            add_filter('pms_recover_password_message_content_sent_to_user1', array('PMS_Emails', 'send_reset_password_email_content'), 10, 4);
        }

        add_filter( 'pms_email_content_user',  array( 'PMS_Emails', 'maybe_add_html_tags' ), 20 );
        add_filter( 'pms_email_content_admin', array( 'PMS_Emails', 'maybe_add_html_tags' ), 20 );

    }


    /**
     * Sends emails to users / admins when a subscription is added or updated
     *
     * @param int   $subscription_id        - the ID of the subscription being added/updated
     * @param array $subscription_data      - the data array added to the subscription
     * @param array $old_subscription_data  - the array of values representing the subscription before the update
     *
     */
    static function send_emails( $subscription_id = 0, $subscription_data = array(), $old_subscription_data = array() ) {

        if( empty( $subscription_id ) )
            return;

        if( empty( $subscription_data['status'] ) )
            return;

        // Send emails only if the status or the subscription plan changes
        if( ! empty( $old_subscription_data['status'] ) && ( $old_subscription_data['status'] == $subscription_data['status'] ) ) {
            if ( empty($subscription_data['subscription_plan_id']) || ( !empty($subscription_data['subscription_plan_id']) && $old_subscription_data['subscription_plan_id'] == $subscription_data['subscription_plan_id']))
                return;
        }

        $subscription = pms_get_member_subscription( (int)$subscription_id );

        if( is_null( $subscription ) )
            return;

        // Set the current action
        switch ( $subscription->status ) {
            case 'active':
                $action = 'activate';
                break;

            case 'abandoned':
            case 'canceled':
                $action = 'cancel';
                break;

            case 'expired':
                $action = 'expired';
                break;

            default:
                $action = '';
                break;
        }

        // Action must be set
        if( empty( $action ) )
            return;

        $settings = get_option( 'pms_emails_settings', array() );

        // Check that the status is a supported email action and that the email is active
        if( ! in_array( $action, PMS_Emails::get_email_actions() ) )
            return;

        // Dont send activate email if status changes from expired and renew mail is active
        if( isset( $settings['renew_is_enabled'] ) && $action == 'activate' ){

            if( isset( $subscription_data['status'] ) && isset( $old_subscription_data['status'] ) && $subscription_data['status'] == 'active' && $old_subscription_data['status'] == 'expired' ){
                // if the plan changes, this is not a renewal email
                if( isset( $subscription_data['subscription_plan_id'] ) && isset( $old_subscription_data['subscription_plan_id'] ) && $subscription_data['subscription_plan_id'] == $old_subscription_data['subscription_plan_id'] )
                    return;
            }

        }

        // Grab the latest payment done for this subscription
        $payments = pms_get_payments( array( 'user_id' => $subscription->user_id, 'subscription_plan_id' => $subscription->subscription_plan_id, 'number' => 1 ) );

        if( isset( $payments[0] ) && !empty( $payments[0]->id ) )
            $payment_id = $payments[0]->id;
        else
            $payment_id = 0;

        /**
         * Send the email to the user
         *
         */
        if ( isset( $settings[ $action . '_is_enabled' ] ) ){

            PMS_Emails::mail( 'user', $action, $subscription->user_id, $subscription->id, $payment_id );

        }

        /**
         * Send the email to the admins
         *
         */

        if( empty( $settings['admin_emails_on'] ) || !isset( $settings[ $action . '_admin_is_enabled' ] ) )
            return;

        PMS_Emails::mail( 'admin', $action, $subscription->user_id, $subscription->id, $payment_id );

    }

    static function send_renewal_email( $subscription_id = 0, $subscription_data = array(), $old_subscription_data = array() ) {

        if( is_admin() && !wp_doing_ajax() ){

            if( $old_subscription_data['payment_gateway'] != 'manual' )
                return;

        }

        // Don't send anything if current status is not active
        if( empty( $subscription_id ) || empty( $subscription_data['status'] ) || $subscription_data['status'] != 'active' )
            return;

        // Only send email if status changes from expired to active
        if( $old_subscription_data['status'] != $subscription_data['status'] && $old_subscription_data['status'] != 'expired' )
            return;

        // Don't do anything if subscription is upgraded
        if( !empty( $subscription_data['subscription_plan_id'] ) && $subscription_data['subscription_plan_id'] != $old_subscription_data['subscription_plan_id'] )
            return;

        // Don't do anything if expiration date does not change
        if( empty( $subscription_data['expiration_date'] ) || $subscription_data['expiration_date'] == $old_subscription_data['expiration_date'] ){
            if( empty( $subscription_data['billing_next_payment'] ) )
                return;
        }

        $subscription = pms_get_member_subscription( (int)$subscription_id );
        $settings     = get_option( 'pms_emails_settings', array() );

        // Grab the latest payment done for this subscription
        $payments = pms_get_payments( array( 'user_id' => $subscription->user_id, 'subscription_plan_id' => $subscription->subscription_plan_id, 'number' => 1 ) );

        if( isset( $payments[0] ) && !empty( $payments[0]->id ) )
            $payment_id = $payments[0]->id;
        else
            $payment_id = 0;

        $action = 'renew';

        if ( isset( $settings[ $action . '_is_enabled' ] ) )
            PMS_Emails::mail( 'user', $action, $subscription->user_id, $subscription->id, $payment_id );

        /**
         * Send the email to the admins
         *
         */

        if( empty( $settings['admin_emails_on'] ) || !isset( $settings[ $action . '_admin_is_enabled' ] ) )
            return;

        PMS_Emails::mail( 'admin', $action, $subscription->user_id, $subscription->id, $payment_id );

    }

    /**
     * Sends the user registration mail
     *
     * @param array $user_data
     *
     */
    static function send_registration_email( $user_data = array() ) {

        $settings             = get_option( 'pms_emails_settings', array() );
        $subscription_plan_id = ( isset( $user_data['subscriptions'][0] ) ? $user_data['subscriptions'][0] : 0 );

        if ( isset( $settings[ 'register_is_enabled' ] ) )
            PMS_Emails::mail( 'user', 'register', $user_data['user_id'], $subscription_plan_id );

        if ( isset( $settings[ 'register_admin_is_enabled' ] ) )
            PMS_Emails::mail( 'admin', 'register', $user_data['user_id'], $subscription_plan_id );

    }

    /**
     * Sends the payment failed email
     *
     * @return [type] [description]
     */
    static function send_payment_failed_email( $payment_id, $new_payment_data, $old_payment_data ) {

        if( empty( $payment_id ) || empty( $new_payment_data['status'] ) )
            return;

        if( !empty( $new_payment_data['status'] ) && !empty( $old_payment_data['status'] ) && $new_payment_data['status'] != $old_payment_data['status'] && $new_payment_data['status'] == 'failed' ){

            $settings = get_option( 'pms_emails_settings', array() );

            // Get subscription id if available on the payment
            $subscription_id = pms_get_payment_meta( $payment_id, 'subscription_id', true );

            if( empty( $subscription_id ) )
                $subscription_id = 0;

            if ( isset( $settings[ 'payment_failed_is_enabled' ] ) )
                PMS_Emails::mail( 'user', 'payment_failed', $old_payment_data['user_id'], $subscription_id, $payment_id );

        }

    }

    /**
     * Returns the reset password email title
     */
    static function send_reset_password_email_subject( $content, $user_login ){

        $settings = get_option('pms_emails_settings', array() );
        $user_data = get_user_by('login', $user_login );

        if( empty( $settings['reset_password_is_enabled'] ) ){

            $email_default_subjects = PMS_Emails::get_default_email_subjects( 'user' );
            $email_content = PMS_Merge_Tags::process_merge_tags( $email_default_subjects['reset_password'], $user_data );
        }
        else{
            $email_content = PMS_Merge_Tags::process_merge_tags( $settings['reset_password_sub_subject'], $user_data );
        }

        return $email_content;

    }

    /**
     * Returns the reset password email content
     */
    static function send_reset_password_email_content( $content, $user_id, $user_login, $user_email ){

        $settings = get_option('pms_emails_settings', array() );
        $user_data = get_userdata( $user_id );
        $data = array( 'password_reset_key' => get_password_reset_key( $user_data ) );

        if( empty( $settings['reset_password_is_enabled'] ) ){

            $email_default_content = PMS_Emails::get_default_email_content( 'user' );
            $email_content = PMS_Merge_Tags::process_merge_tags( $email_default_content['reset_password'], $user_data, 0, 0, 'reset_password', $data );
        }
        else{
            $email_content = PMS_Merge_Tags::process_merge_tags( $settings['reset_password_sub'], $user_data, 0, 0, 'reset_password', $data );
        }

        $email_content = wpautop($email_content);
        return $email_content;
    }



    /**
     * Function that calls wp_mail after we decide what to send
     *
     * @param string $send_to              - the recepient of the email, possible values: user, admin
     * @param string $action               - the action for which the email is sent
     * @param int    $user_id
     * @param int    $subscription_plan_id
     * @param string $start_date
     * @param string $expiration_date
     *
     */
    static function mail( $send_to = '', $action = '', $user_id = 0, $subscription_id = 0, $payment_id = 0 ) {

        if( empty( $send_to ) )
            return false;

        if( empty( $action ) )
            return false;

        if( apply_filters( 'pms_mail_stop_emails', false ) )
            return false;

        $settings  = get_option( 'pms_emails_settings', array() );
        $user_info = get_userdata( $user_id );

        if( empty( $user_info ) )
            return false;

        /**
         * Set the email address which will receive the email
         *
         */
        if( $send_to == 'user' ) {

            $email_to = $user_info->user_email;

        }

        if( $send_to == 'admin' ) {

            $admin_emails = ( ! empty( $settings['admin_emails'] ) ? $settings['admin_emails'] : '' );
            $admin_emails = array_map( 'trim', explode( ',', $admin_emails ) );

            // Make sure emails are valid
            foreach( $admin_emails as $key => $email_address ) {

                if( ! is_email( $email_address ) )
                    unset( $admin_emails[$key] );

            }

            if ( !empty( $admin_emails ))
                $email_to = $admin_emails;
            else
                $email_to = get_option( 'admin_email' );

        }


        /**
         * Set the subject and message content of the email
         *
         */
        $email_default_subjects = PMS_Emails::get_default_email_subjects( $send_to );
        $email_default_content  = PMS_Emails::get_default_email_content( $send_to );

        // Email settings for the user are saved in the db without a sufix
        $settings_sufix = ( $send_to == 'admin' ? '_admin' : '' );

        // Set email subject
        if( ! empty( $settings[$action . '_sub_subject' . $settings_sufix] ) )
            $email_subject = $settings[$action . '_sub_subject' . $settings_sufix];
        else
            $email_subject = $email_default_subjects[$action];

        // Set email message
        if( ! empty( $settings[$action . '_sub' . $settings_sufix] ) )
            $email_content = $settings[$action . '_sub' . $settings_sufix];
        else
            $email_content = $email_default_content[$action];

        // for the register email, the subscription doesn't exist yet, the provided $subscription_id is a plan id actually
        // set it in the extra user_info array and make it available for tags to use (e.g. subscription name)
        if( $action == 'register' || $action == 'pending_manual_payment' ){
            $user_info->subscription_plan_id = $subscription_id;
            $subscription_id = 0;
        }

        $email_subject = PMS_Merge_Tags::process_merge_tags( $email_subject, $user_info, $subscription_id, $payment_id, $action );
        $email_content = PMS_Merge_Tags::process_merge_tags( $email_content, $user_info, $subscription_id, $payment_id, $action );

        if( apply_filters( 'pms_emails_auto_format', true, $action ) )
            $email_content = wpautop( $email_content );

        $email_content = do_shortcode( $email_content );

        /**
         * Filter the subject and the content before sending the mail
         *
         */
        $email_subject = apply_filters( 'pms_email_subject_' . $send_to, $email_subject, $action, $user_info, $subscription_id, $payment_id );
        $email_content = apply_filters( 'pms_email_content_' . $send_to, $email_content, $action, $user_info, $subscription_id, $payment_id );

        // Add filter to enable html encoding
        add_filter( 'wp_mail_content_type', array( 'PMS_Emails', 'pms_email_content_type' ) );

        // Temporary change the from name and from email
        add_filter( 'wp_mail_from_name', array( 'PMS_Emails', 'pms_email_website_name' ), 20, 1 );
        add_filter( 'wp_mail_from', array( 'PMS_Emails', 'pms_email_website_email' ), 20, 1 );

        // Send email
        $mail_sent = wp_mail( $email_to, $email_subject, $email_content );

        // Reset html encoding
        remove_filter( 'wp_mail_content_type', array( 'PMS_Emails', 'pms_email_content_type' ) );

        // Reset the from name and email
        remove_filter( 'wp_mail_from_name', array( 'PMS_Emails', 'pms_email_website_name' ), 20 );
        remove_filter( 'wp_mail_from', array( 'PMS_Emails', 'pms_email_website_email' ), 20 );

        return $mail_sent;

    }


    /**
     * Function that returns the possible email actions
     *
     * @return array
     *
     */
    static function get_email_actions() {

        $email_actions = array( 'register', 'activate', 'cancel', 'expired', 'pending_manual_payment' );

        return apply_filters( 'pms_email_actions', $email_actions );

    }


    /**
     * Function that returns the general email option defaults
     *
     * @return mixed
     *
     */
    static function get_email_general_options() {

        $email_options = array(
            'email-from-name'  => get_bloginfo('name'),
            'email-from-email' => get_bloginfo('admin_email'),
        );

        return apply_filters( 'pms_email_general_options_defaults', $email_options );
    }

    /**
     * The headers fot the emails in the settings page
     *
     * @return array
     *
     */
    static function get_email_headings() {

        $email_headings = array(
            'register'               => __( 'Register Email', 'paid-member-subscriptions' ),
            'activate'               => __( 'Activate Subscription Email', 'paid-member-subscriptions' ),
            'cancel'                 => __( 'Cancel and Abandon Subscription Email', 'paid-member-subscriptions' ),
            'expired'                => __( 'Expired Subscription Email', 'paid-member-subscriptions' ),
            'payment_failed'         => __( 'Failed Payment Email', 'paid-member-subscriptions' ),
            'pending_manual_payment' => __( 'Pending Manual Payment Email', 'paid-member-subscriptions' ),
            'renew'                  => __( 'Renew Subscription Email', 'paid-member-subscriptions' ),
            'reset_password'         => __('Reset Password Email', 'paid-member-subscriptions')
        );

        return apply_filters( 'pms_email_headings', $email_headings );

    }


    /**
     * The function that returns the default email subjects
     *
     * @param string $send_to
     *
     * @return array
     *
     */
    static function get_default_email_subjects( $send_to = '' ) {

        // Emails sent to the user
        if( empty( $send_to ) || $send_to == 'user' ) {

            $email_subjects = array(
                'register'               => __( 'You have a new account', 'paid-member-subscriptions' ),
                'activate'               => __( 'Your Subscription is now active', 'paid-member-subscriptions' ),
                'cancel'                 => __( 'Your Subscription has been canceled', 'paid-member-subscriptions' ),
                'expired'                => __( 'Your Subscription has expired', 'paid-member-subscriptions' ),
                'payment_failed'         => __( 'Your latest payment has failed', 'paid-member-subscriptions' ),
                'pending_manual_payment' => __( 'Pending manual payment', 'paid-member-subscriptions' ),
                'renew'                  => __( 'Your Subscription was renewed', 'paid-member-subscriptions' ),
                'reset_password'         => __( 'Password Reset from {{site_url}}', 'paid-member-subscriptions' ),
            );

        }

        // Emails sent to the admin
        if( $send_to == 'admin' ) {

            $email_subjects = array(
                'register'               => __( 'A New User has registered to your website', 'paid-member-subscriptions' ),
                'activate'               => __( 'A Member Subscription is now active', 'paid-member-subscriptions' ),
                'cancel'                 => __( 'A Member Subscription has been canceled', 'paid-member-subscriptions' ),
                'expired'                => __( 'A Member Subscription has expired', 'paid-member-subscriptions' ),
                'renew'                  => __( 'A Member Subscription was renewed', 'paid-member-subscriptions' ),
                'pending_manual_payment' => __( 'Pending manual payment', 'paid-member-subscriptions' ),
            );

        }

        return apply_filters( 'pms_default_email_subjects', $email_subjects, $send_to );

    }


    /**
     * The function that returns the default email contents
     *
     * @param string $send_to
     *
     * @return array
     *
     */
    static function get_default_email_content( $send_to = '' ) {

        // Emails sent to the user
        if( empty( $send_to ) || $send_to == 'user' ) {

            $email_content = array(
                'register'               => __( 'Congratulations {{display_name}}! You have successfully created an account!', 'paid-member-subscriptions' ),
                'activate'               => __( 'Congratulations {{display_name}}! The "{{subscription_name}}" plan has been successfully activated.', 'paid-member-subscriptions' ),
                'cancel'                 => __( 'Hello {{display_name}}, The "{{subscription_name}}" plan has been canceled.', 'paid-member-subscriptions' ),
                'expired'                => __( 'Hello {{display_name}}, The "{{subscription_name}}" plan has expired.', 'paid-member-subscriptions' ),
                'payment_failed'         => __( 'Your latest payment for the "{{subscription_name}}" plan has failed. You can go to the <a href="{{account_page_url}}">account page</a> and login in order to try again.<br><br>{{automatic_retry_message}}', 'paid-member-subscriptions' ),
                'pending_manual_payment' => __( 'Hello {{display_name}}!<br>We received your order for "{{subscription_name}}" plan.<br>You can make the payment using the following bank details:', 'paid-member-subscriptions' ),
                'renew'                  => __( 'Hello {{display_name}}, The "{{subscription_name}}" plan has been renewed.', 'paid-member-subscriptions' ),
                'reset_password'         => __('Someone has just requested a password reset for the following account: {{site_name}} <br> If this was a mistake, just ignore this email and nothing will happen. <br> To reset your password, visit the following link: {{reset_link}}', 'paid-member-subscriptions'),
            );

        }

        // Emails sent to the admin
        if( $send_to == 'admin' ) {

            $email_content = array(
                'register'               => __( '{{display_name}} has just created an account!', 'paid-member-subscriptions' ),
                'activate'               => __( 'The "{{subscription_name}}" plan has been successfully activated for user {{display_name}}.', 'paid-member-subscriptions' ),
                'cancel'                 => __( 'The "{{subscription_name}}" plan has been canceled for user {{display_name}}.', 'paid-member-subscriptions' ),
                'expired'                => __( 'The "{{subscription_name}}" plan has expired for user {{display_name}}.', 'paid-member-subscriptions' ),
                'renew'                  => __( 'The "{{subscription_name}}" plan was renewed for user {{display_name}}.', 'paid-member-subscriptions' ),
                'pending_manual_payment' => __( '{{display_name}} has just placed an order for "{{subscription_name}}" plan.<br><strong>Manual Payment</strong> option was used and the status is <strong>Pending</strong>.', 'paid-member-subscriptions' ),
            );

        }

        return apply_filters( 'pms_default_email_content', $email_content, $send_to );

    }

    /**
     * Filters the From name
     *
     * @param string $site_name
     *
     * @return string
     *
     */
    static function pms_email_website_name( $site_name = '' ) {

        $pms_settings = get_option( 'pms_emails_settings' );

        if ( !empty( $pms_settings['email-from-name'] ) ) {

            $site_name = $pms_settings['email-from-name'];

        } else {

            $site_name = get_bloginfo('name');

        }

        return $site_name;
    }


    /**
     * Filters the From email address
     *
     * @param string $site_name
     *
     * @return string
     *
     */
    static function pms_email_website_email( $sender_email = '' ) {

        $pms_settings = get_option( 'pms_emails_settings' );

        if ( ! empty( $pms_settings['email-from-email'] ) ) {

            if( is_email( $pms_settings['email-from-email'] ) )
                $sender_email = $pms_settings['email-from-email'];

        } else {

            $sender_email = get_bloginfo( 'admin_email' );

        }

        return $sender_email;
    }


    /**
     * Callback to be applied to change the content type of the sent emails
     *
     * @return string
     *
     */
    static function pms_email_content_type() {

        return 'text/html';

    }

    /**
     * Add HTML tags around message content
     */
    static function maybe_add_html_tags( $content ){

        if( $content !== wp_strip_all_tags( $content ) ){
            if( strpos( html_entity_decode( $content ), '<html' ) === false && strpos( html_entity_decode( $content ), '<body' ) === false )
                $content = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>'. $content . '</body></html>';
        }

        return $content;

    }

}

PMS_Emails::init();
