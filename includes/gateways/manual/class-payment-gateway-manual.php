<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

Class PMS_Payment_Gateway_Manual extends PMS_Payment_Gateway {

    /*
     * The payment gateway slug
     *
     * @access public
     * @var string
     *
     */
    public $payment_gateway = 'manual';


    /**
     * The features supported by the payment gateway
     *
     * @access public
     * @var array
     *
     */
    public $supports;


    public function init() {

        $this->supports = apply_filters( 'pms_gateway_manual_supports', array(
            'subscription_sign_up_fee',
            'subscription_free_trial',
            'recurring_payments',
        ) );

        // Add custom user messages for this gateway
        add_filter( 'pms_message_gateway_payment_action', array( $this, 'success_messages' ), 10, 4 );

        // Automatically activate the member's subscription when completing the payment
        if( !$this->check_filter_from_class_exists( 'pms_payment_update', 'PMS_Payment_Gateway_Manual', 'activate_member_subscription' ) )
            add_action( 'pms_payment_update', array( $this, 'activate_member_subscription' ), 10, 3 );

        // Send email notification for pending manual payment
        if( !$this->check_filter_from_class_exists( 'pms_payment_update', 'PMS_Payment_Gateway_Manual', 'send_pending_manual_payment_email' ) )
            add_action( 'pms_payment_update', array( $this, 'send_pending_manual_payment_email' ), 10, 3 );

        // Remove the Retry payment action for this gateway
        if( !$this->check_filter_from_class_exists( 'pms_output_subscription_plan_pending_retry_payment', 'PMS_Payment_Gateway_Manual', 'remove_retry_payment' ) )
            add_action( 'pms_output_subscription_plan_pending_retry_payment', array( $this, 'remove_retry_payment' ), 10, 3 );

        if( !$this->check_filter_from_class_exists( 'pms_output_subscription_plan_action_renewal', 'PMS_Payment_Gateway_Manual', 'remove_renewal_action' ) )
            add_action( 'pms_output_subscription_plan_action_renewal', array( $this, 'remove_renewal_action' ), 10, 4 );

        // Change payment type in case of payment generated after a Free Trial
        add_filter( 'pms_cron_process_member_subscriptions_payment_data', array( $this, 'change_free_trial_payment_type' ), 20, 2 );

        // Add a new action if the payment is manual so it can be completed from the table
        add_filter( 'pms_payments_list_table_entry_actions', array( $this, 'add_mark_as_completed_action' ), 20, 2 );

    }

    /*
     * Process payment
     *
     */
    public function process_sign_up() {

        $subscription = pms_get_current_subscription_from_tier( $this->user_id, $this->subscription_data['subscription_plan_id'] );

        if( empty( $subscription ) && isset( $_POST['pms_current_subscription'] ) )
            $subscription = pms_get_member_subscription( absint( $_POST['pms_current_subscription'] ) );
        
        // Activate subscription if plan has a free trial
        if( !empty( $this->subscription_data['trial_end'] ) ){

            $subscription->update(
                array(
                    'status'               => 'active',
                    'subscription_plan_id' => $this->subscription_data['subscription_plan_id'],
                    'billing_next_payment' => $this->subscription_data['trial_end'],
                    'billing_amount'       => $this->subscription_data['billing_amount'],
                    'trial_end'            => $this->subscription_data['trial_end'],
                )
            );

            pms_add_member_subscription_log( $subscription->id, 'subscription_trial_started', array( 'until' => $this->subscription_data['trial_end'] ) );

            pms_add_member_subscription_log( $subscription->id, 'subscription_activated' );

        }

        if( $this->recurring ){

            $billing_next_payment = !empty( $this->subscription_data['trial_end'] ) ?  $this->subscription_data['trial_end'] : $this->subscription_data['expiration_date'];

            $subscription_data = array(
                'billing_next_payment'  => $billing_next_payment,
                'billing_duration'      => $this->subscription_plan->is_fixed_period_membership() ? '1' : $this->subscription_plan->duration,
                'billing_duration_unit' => $this->subscription_plan->is_fixed_period_membership() ? 'year' : $this->subscription_plan->duration_unit,
                'billing_amount'        => $this->subscription_data['billing_amount'],
            );

            $subscription->update( $subscription_data );

        }

        // Get payment
        $payment = pms_get_payment( $this->payment_id );

        do_action( 'pms_manual_gateway_signup_processed', $this->subscription_data, $this->payment_id );

        // Success Redirect
        if( isset( $_POST['pmstkn'] ) ) {

            $redirect_url = add_query_arg( array( 'pms_gateway_payment_id' => base64_encode( $this->payment_id ), 'pmsscscd' => base64_encode( 'subscription_plans' ), 'pms_gateway_payment_action' => base64_encode( $this->form_location ) ), $this->redirect_url );

            wp_redirect( $redirect_url );
            exit;

        }

    }

    // Adds a pending payment for the user after a free trial
    public function process_payment( $payment_id = 0, $subscription_id = 0 ) {
        $subscription = pms_get_member_subscription( $subscription_id );

        $data = array(
            'status'               => 'pending',
            'billing_next_payment' => '',
            'trial_end'            => ''
        );

        $subscription->update( $data );

        pms_add_member_subscription_log( $subscription->id, 'subscription_trial_end' );

        return false;
    }


    /*
     * Change the default success message for the different payment actions
     *
     * @param string $message
     * @param string $payment_status
     * @param string $payment_action
     * @param obj $payment
     *
     * @return string
     *
     */
    public function success_messages( $message, $payment_status, $payment_action, $payment ) {

        if( $payment->payment_gateway !== $this->payment_gateway )
            return $message;

        // We're interested in changing only the success messages for paid subscriptions
        // which will all have the "pending" status
        if( $payment_status != 'pending' )
            return $message;

        switch( $payment_action ) {

            case 'upgrade_subscription':
                $message = __( 'Thank you for upgrading. The changes will take effect after the payment is received.', 'paid-member-subscriptions' );
                break;

            case 'downgrade_subscription':
                $message = __( 'Thank you for downgrading. The changes will take effect after the payment is received.', 'paid-member-subscriptions' );
                break;

            case 'change_subscription':
                $message = __( 'Thank you for choosing another plan. The changes will take effect after the payment is received.', 'paid-member-subscriptions' );
                break;

            case 'renew_subscription':
                $message = __( 'Thank you for renewing. The changes will take effect after the payment is received.', 'paid-member-subscriptions' );
                break;

            case 'new_subscription':
                $message = __( 'Thank you for subscribing. The subscription will be activated after the payment is received.', 'paid-member-subscriptions' );
                break;

            case 'retry_payment':
                $message = __( 'The subscription will be activated after the payment is received.', 'paid-member-subscriptions' );
                break;

            default:
                break;

        }

        return $message;

    }


    /**
     * Activates the member's account when the payment is marked as complete.
     * If the subscription is already active, add the extra time to the subscription expiration date
     *
     * @param int   $payment_id
     * @param array $data         - an array with modifications made when saving the payment in the back-end
     * @param array $old_data     - the array of values representing the payment before the update
     *
     * @return void
     *
     */
    public function activate_member_subscription( $payment_id, $data, $old_data ) {

        if( empty( $data['status'] ) || $data['status'] != 'completed' || empty( $old_data['status'] ) || $old_data['status'] == $data['status'] )
            return;

        $payment = pms_get_payment( $payment_id );

        if( $payment->payment_gateway !== $this->payment_gateway )
            return;

        // Get plan
        $subscription_plan = pms_get_subscription_plan( $payment->subscription_id );

        // The subscription plan ID from the payment matches an existing subscription for this user
        $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $payment->user_id, 'subscription_plan_id' => $payment->subscription_id, 'number' => 1 ) );

        if( !empty( $member_subscriptions ) ){

            $member_subscription = $member_subscriptions[0];

            if( $member_subscription->status == 'active' )
                return;

            if( ! empty( $member_subscription ) ) {

                $subscription_plan = pms_get_subscription_plan( $payment->subscription_id );

                if ( $member_subscription->status == 'active' ){
                    if( $subscription_plan->is_fixed_period_membership() ){

                        if( $subscription_plan->fixed_period_renewal_allowed() )
                            $member_subscription->update( array( 'expiration_date' => date( 'Y-m-d H:i:s', strtotime( pms_sanitize_date($member_subscription->expiration_date) . '+ 1 year' ) ) ) );
                        else
                            $member_subscription->update( array( 'expiration_date' => date( 'Y-m-d H:i:s', strtotime( pms_sanitize_date($member_subscription->expiration_date) ) ) ) );

                    } else {
                        $member_subscription->update( array( 'expiration_date' => date( 'Y-m-d H:i:s', strtotime( pms_sanitize_date($member_subscription->expiration_date) . '+' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit ) ) ) );
                    }
                }
                else if ( $member_subscription->status == 'expired' ){
                    if( $subscription_plan->is_fixed_period_membership() ){

                        if( $subscription_plan->fixed_period_renewal_allowed() )
                            $member_subscription->update( array( 'status' => 'active', 'expiration_date' => date( 'Y-m-d H:i:s', strtotime( pms_sanitize_date($member_subscription->expiration_date) . '+ 1 year' ) ) ) );
                        else
                            $member_subscription->update( array( 'status' => 'active', 'expiration_date' => date( 'Y-m-d H:i:s', strtotime( pms_sanitize_date($member_subscription->expiration_date) ) ) ) );

                    } else {
                        $member_subscription->update( array( 'status' => 'active', 'expiration_date' => date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d H:i:s' ) . '+' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit ) ) ) );
                    }
                }
                else if ( $member_subscription->status == 'canceled' ) {
                    if ( strtotime( $member_subscription->expiration_date ) > strtotime( 'now' ) ){
                        if( $subscription_plan->is_fixed_period_membership() ){

                            if( $subscription_plan->fixed_period_renewal_allowed() )
                                $timestamp = strtotime( pms_sanitize_date($member_subscription->expiration_date) . '+ 1 year' );
                            else
                                $timestamp = strtotime( pms_sanitize_date($member_subscription->expiration_date) );

                        } else {
                            $timestamp = strtotime( pms_sanitize_date($member_subscription->expiration_date) . '+' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit );
                        }
                    }
                    else {
                        if( $subscription_plan->is_fixed_period_membership() ){

                            if( $subscription_plan->fixed_period_renewal_allowed() )
                                $timestamp = strtotime( pms_sanitize_date($member_subscription->expiration_date) . '+ 1 year' );
                            else
                                $timestamp = strtotime( pms_sanitize_date($member_subscription->expiration_date) );

                        } else {
                            $timestamp = strtotime( date( 'Y-m-d H:i:s' ) . '+' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit );
                        }
                    }

                    $member_subscription->update( array( 'status' => 'active', 'expiration_date' => date( 'Y-m-d H:i:s', $timestamp ) ) );

                } else
                    $member_subscription->update( array( 'status' => 'active' ) );

                pms_add_member_subscription_log( $member_subscription->id, 'admin_subscription_activated_payments' );

                return;

            }

        }

        // The plan from the payment is not the current user plan
        $old_subscription = pms_get_member_subscription( $payment->member_subscription_id );

        if( !empty( $old_subscription ) && !empty( $old_subscription->id ) ) {

            $old_plan_id = $old_subscription->subscription_plan_id;

            $subscription_plan = pms_get_subscription_plan( $payment->subscription_id );

            $subscription_data = array(
                'user_id'              => $payment->user_id,
                'subscription_plan_id' => $subscription_plan->id,
                'start_date'           => date('Y-m-d H:i:s'),
                'expiration_date'      => $subscription_plan->get_expiration_date(),
                'status'               => 'active',
                'payment_gateway'      => $this->payment_gateway,
            );
            
            // reset custom schedule
            if( $old_subscription->payment_gateway != 'manual' ){
                $subscription_data['billing_amount']        = '';
                $subscription_data['billing_duration']      = '';
                $subscription_data['billing_duration_unit'] = '';
                $subscription_data['billing_next_payment']  = '';
            }

            $old_subscription->update( $subscription_data );

            $context = pms_get_change_subscription_plan_context( $old_plan_id, $subscription_plan->id );

            pms_add_member_subscription_log( $old_subscription->id, 'subscription_'. $context .'_success', array( 'old_plan' => $old_plan_id, 'new_plan' => $subscription_plan->id ) );

            /**
             * This is triggered after a subscription was Upgraded, Downgraded or Changed
             */
            do_action( 'pms_manual_subscription_change_plan', $old_subscription, $subscription_plan, $payment_id, $context );

        }

    }

    public function remove_retry_payment( $output, $subscription_plan, $subscription ) {
        if ( !empty( $subscription['payment_gateway'] ) && $subscription['payment_gateway'] == 'manual' )
            return;

        return $output;
    }

    /**
     * For manual gateway payments, do not let users request a Renewal more than once.
     *
     * @param  string    $output
     * @param  object    $subscription_plan
     * @param  array     $subscription
     * @param  int       $user_id
     * @return string|void
     */
    public function remove_renewal_action( $output, $subscription_plan, $subscription, $user_id ) {

        $payments = pms_get_payments( array( 'user_id' => $user_id, 'subscription_plan_id' => $subscription_plan->id ) );

        if( !empty( $payments ) ){
            foreach( $payments as $payment ) {
                if ( $payment->payment_gateway == 'manual' && $payment->status == 'pending' && $payment->type == 'subscription_renewal_payment' )
                    return;
            }
        }

        return $output;

    }

    public function change_free_trial_payment_type( $payment_data, $subscription ){

        if( $subscription->payment_gateway == 'manual' )
            $payment_data['type'] = 'subscription_initial_payment';

        return $payment_data;
    }

    public function add_mark_as_completed_action( $actions, $item ){

        if( empty( $item['id'] ) )
            return $actions;

        $payment = pms_get_payment( $item['id'] );

        if( empty( $payment->id ) )
            return $actions;

        if( $payment->payment_gateway != 'manual' || $payment->status != 'pending' )
            return $actions;

        $delete_action = $actions['delete'];
        unset( $actions['delete'] );

        $actions['complete_payment'] = '<a href="' . wp_nonce_url( add_query_arg( array( 'pms-action' => 'complete_payment', 'payment_id' => $item['id'] ) ), 'pms_payment_nonce' ) . '">' . __( 'Complete Payment', 'paid-member-subscriptions' ) . '</a>';

        $actions['delete'] = $delete_action;

        return $actions;
    }

    public function send_pending_manual_payment_email( $payment_id, $data, $payment_data ) {

        if ( empty( $payment_id ) || !isset( $data['amount'] ) || !isset( $payment_data['payment_gateway'] ) || $payment_data['payment_gateway'] !== 'manual' )
            return;

        // avoid sending email multiple times
        $mail_sent = get_user_meta( $payment_data['user_id'], 'pending_manual_payment_'. $payment_id .'_email_sent', true );

        if ( empty( $mail_sent ) ) {
            $email_settings = get_option( 'pms_emails_settings', array() );

            if ( isset( $email_settings['pending_manual_payment_is_enabled'] ) && $email_settings['pending_manual_payment_is_enabled'] == 'yes' )
                PMS_Emails::mail( 'user', 'pending_manual_payment', $payment_data['user_id'], $payment_data['subscription_id'], $payment_id );

            if ( isset( $email_settings['pending_manual_payment_admin_is_enabled'] ) && $email_settings['pending_manual_payment_admin_is_enabled'] == 'yes' )
                PMS_Emails::mail( 'admin', 'pending_manual_payment', $payment_data['user_id'], $payment_data['subscription_id'], $payment_id );

            update_user_meta( $payment_data['user_id'], 'pending_manual_payment_'. $payment_id .'_email_sent', true );
        }

    }

    public function check_filter_from_class_exists( $hook, $className, $methodName ){

        global $wp_filter;
    
        if( !isset( $wp_filter[$hook] ) )
            return false;
    
        foreach( $wp_filter[$hook] as $priority => $realhook ){
    
            foreach( $realhook as $hook_k => $hook_v ){
    
                if( is_array( $hook_v['function'] ) ){
    
                    if( isset( $hook_v['function'][0], $hook_v['function'][1] ) && $hook_v['function'][0] == $className && $hook_v['function'][1] == $methodName ) {
    
                        return true;
    
                    }
                }
    
            }
    
        }
    
        return false;

    }

}
