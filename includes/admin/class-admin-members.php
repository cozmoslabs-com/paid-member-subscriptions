<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Extends core PMS_Submenu_Page base class to create and add custom functionality
 * for the members section in the admin section
 *
 */
Class PMS_Submenu_Page_Members extends PMS_Submenu_Page {

    /**
     * Request data
     *
     * @access public
     * @var array
     */
    public $request_data = array();

    public $list_table;


    /*
     * Method that initializes the class
     *
     */
    public function init() {

        $this->request_data = $_REQUEST;

        // Enqueue admin scripts
        add_action( 'pms_submenu_page_enqueue_admin_scripts_' . $this->menu_slug, array( $this, 'admin_scripts' ) );

        // Hook the validation of the data on the init.
        add_action( 'init', array( $this, 'process_data' ), 20 );

        // Hook the output method to the parent's class action for output instead of overwriting the
        // output method
        add_action( 'pms_output_content_submenu_page_' . $this->menu_slug, array( $this, 'output' ) );

        // Add ajax hooks
        add_action( 'wp_ajax_populate_expiration_date',            array( $this, 'ajax_populate_expiration_date' ) );
        add_action( 'wp_ajax_populate_member_subscription_fields', array( $this, 'ajax_populate_member_subscription_fields' ) );
        add_action( 'wp_ajax_check_username',                      array( $this, 'ajax_check_username' ) );
        add_action( 'wp_ajax_add_log_entry',                       array( $this, 'ajax_add_log_entry' ) );

        if( isset( $_GET['page'] ) && $_GET['page'] == 'pms-members-page' )
            add_action( 'current_screen', array( $this, 'load_table' ) );

    }

    public function load_table() {
        $this->list_table = new PMS_Members_List_Table();
    }

    /*
     * Method to enqueue admin scripts
     *
     */
    public function admin_scripts() {

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'jquery-style', PMS_PLUGIN_DIR_URL . 'assets/css/admin/jquery-ui.min.css', array(), PMS_VERSION );

        global $wp_scripts;

        // Try to detect if chosen has already been loaded
        $found_chosen = false;

        foreach( $wp_scripts as $wp_script ) {
            if( !empty( $wp_script['src'] ) && strpos($wp_script['src'], 'chosen') !== false )
                $found_chosen = true;
        }

        if( !$found_chosen ) {
            wp_enqueue_script( 'pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.jquery.min.js', array( 'jquery' ), PMS_VERSION );
            wp_enqueue_style( 'pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.css', array(), PMS_VERSION );
        }

        // Register script to display confirmation message in case of bulk delete
        wp_register_script( 'pms-members-bulk-actions-script', PMS_PLUGIN_DIR_URL . 'assets/js/admin/submenu-page-members-page.js', array('jquery'), PMS_VERSION );
        $confirmation_message = array(
            'message'   => __( 'Are you sure you want to delete these Subscriptions? \nThis action is irreversible.', 'paid-member-subscriptions' )
        );
        wp_localize_script( 'pms-members-bulk-actions-script', 'pms_confirmation_message', $confirmation_message );
        wp_enqueue_script( 'pms-members-bulk-actions-script' );
    }


    /**
     * Returns a custom message by the provided code
     *
     * @param int $code
     *
     * @return string
     *
     */
    protected function get_message_by_code( $code = 0 ) {

        $messages = array(
            1 => __( 'Something went wrong. Could not process your request.', 'paid-member-subscriptions' ),
            2 => __( 'Member Subscription added successfully.', 'paid-member-subscriptions' ),
            3 => __( 'Member Subscription updated successfully.', 'paid-member-subscriptions' ),
            4 => __( 'Member Subscription deleted successfully.', 'paid-member-subscriptions' )
        );

        return ( ! empty( $messages[$code] ) ? $messages[$code] : '' );

    }


    /*
     * Method that processes data on members admin pages
     *
     */
    public function process_data() {

        // These processes should be handled only by an admin
        if( ! ( current_user_can( 'manage_options' ) || current_user_can( 'pms_edit_capability' ) ) )
            return;

        /**
         * Handle add new subscription
         *
         */
        if( ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), 'pms_add_subscription_nonce' ) ) {

            if( ! $this->validate_subscription_data( $_POST ) )
                return;

            $member_subscription = new PMS_Member_Subscription();
            $subscription_id     = $member_subscription->insert( $_POST );

            pms_add_member_subscription_log( $subscription_id, 'admin_subscription_added_members' );

            // If the subscription was added redirect to the subscription's edit screen
            if( $subscription_id ) {

                wp_redirect( esc_url_raw( add_query_arg( array( 'page' => $this->menu_slug, 'subpage' => 'edit_subscription', 'subscription_id' => (int)$subscription_id, 'message' => '2', 'updated' => '1' ), admin_url( 'admin.php' ) ) ) );
                exit;

            }

        }


        /**
         * Handle edit subscription
         *
         */
        if( ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), 'pms_edit_subscription_nonce' ) ) {

            if( ! $this->validate_subscription_data( $_POST ) )
                return;

            if( ! $this->validate_subscription_data_edit_subscription() )
                return;

            if( !isset( $_GET['subscription_id'] ) )
                return;

            $member_subscription = pms_get_member_subscription( (int)sanitize_text_field( $_GET['subscription_id'] ) );

            /* when changing a users subscription plan from the back-end change the billing amount as well */
            if( apply_filters( 'pms_update_billing_amount_from_backend_on_sub_change', true ) ) {
                if ( isset( $_POST['subscription_plan_id'] ) && $member_subscription->subscription_plan_id != $_POST['subscription_plan_id'] ) {

                    $new_subscription_plan = pms_get_subscription_plan( (int)sanitize_text_field( $_POST['subscription_plan_id'] ) );

                    if ( isset( $new_subscription_plan->price ) )
                        $_POST['billing_amount'] = $new_subscription_plan->price;
                }

            }

            // When an admin adds a subscription from the back-end, he can't add the time part so set it to 23:59:59 (full access for the expiration day)
            if( isset( $_POST['expiration_date'] ) )
                $_POST['expiration_date'] = sanitize_text_field( $_POST['expiration_date'] ) . ' 23:59:59';

            // When an admin edits a subscription from the back-end, he cannot edit the time part so we should make sure this is kept unless the admin
            // explicitly changes the start date
            if( isset( $_POST['start_date'] ) ){

                // separate time from existing subscription start date
                $start_date = explode( ' ', $member_subscription->start_date );

                if( isset( $start_date[0] ) && $start_date[0] == $_POST['start_date'] ){
                    $_POST['start_date'] = $member_subscription->start_date;
                }
            }


            // When an admin cancels a PSP subscription, the expiration date needs to be set
            if( isset( $_POST['status'] ) && in_array( $_POST['status'], array( 'expired', 'canceled' ) ) && $_POST['status'] != $member_subscription->status ){
                if( !empty( $member_subscription->billing_next_payment ) )
                    $_POST['expiration_date'] = $member_subscription->billing_next_payment;
            }

            // When a subscription is edited by the admin, disable payment retry
            pms_update_member_subscription_meta( $member_subscription->id, 'pms_retry_payment', 'inactive' );

            $updated = $member_subscription->update( $_POST );

            if( $updated ) {

                wp_redirect( esc_url_raw( add_query_arg( array( 'message' => '3', 'updated' => '1' ), pms_get_current_page_url() ) ) );
                exit;

            }

        }


        /**
         * Handle delete subscription
         *
         */
        if( ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'pms_delete_subscription_nonce' ) ) {

            if( empty( $_GET['subscription_id'] ) )
                return;

            $member_subscription = pms_get_member_subscription( (int)sanitize_text_field( $_GET['subscription_id'] ) );

            if( is_null( $member_subscription ) )
                return;

            $member_id = $member_subscription->user_id;
            $deleted   = $member_subscription->remove();

            if( $deleted ) {

                $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $member_id ) );

                if( ! empty( $member_subscriptions ) )
                    wp_redirect( esc_url_raw( add_query_arg( array( 'page' => $this->menu_slug, 'subpage' => 'edit_member', 'member_id' => $member_id, 'message' => '4', 'updated' => '1' ), admin_url( 'admin.php' ) ) ) );
                else
                    wp_redirect( esc_url_raw( add_query_arg( array( 'page' => $this->menu_slug, 'message' => '4', 'updated' => '1' ), admin_url( 'admin.php' ) ) ) );

                exit;

            }

        }

        /*
         * Handle bulk delete subscriptions
         *
         */
        if( ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'pms_bulk_delete_subscription_nonce' ) ) {

            $action = ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] != '-1' ? sanitize_text_field( $_REQUEST['action'] ) : ( isset( $_REQUEST['action2'] ) ? sanitize_text_field( $_REQUEST['action2'] ) : '' ) );

            if( isset( $_REQUEST[ 'member_subscriptions' ] ) && !empty( $_REQUEST[ 'member_subscriptions' ] ) && $action == 'pms-delete-subscriptions' ){

                $deleted_subscriptions_count = 0;
                $subscription_ids            = array_map( 'absint', $_REQUEST[ 'member_subscriptions' ] );

                foreach( $subscription_ids as $id ){

                    $member_subscription = pms_get_member_subscription( (int)$id );

                    if( !is_null( $member_subscription ) ){
                        $deleted = $member_subscription->remove();

                        if( $deleted ){
                            $deleted_subscriptions_count++;
                        }
                    }
                }

                if( $deleted_subscriptions_count != 0 )
                    $this->add_admin_notice( sprintf( _n( '%d Member Subscription successfully deleted.', '%d Member Subscriptions successfully deleted.', $deleted_subscriptions_count, 'paid-member-subscriptions' ), $deleted_subscriptions_count ), 'updated' );

            }

            // Handle bulk cancel subscriptions
            if( isset( $_REQUEST[ 'member_subscriptions' ] ) && !empty( $_REQUEST[ 'member_subscriptions' ] ) && $action == 'pms-cancel-subscriptions' ){

                $canceled_subscriptions_count = 0;
                $subscription_ids            = array_map( 'absint', $_REQUEST[ 'member_subscriptions' ] );

                foreach( $subscription_ids as $id ){

                    $member_subscription = pms_get_member_subscription( (int)$id );

                    if( !is_null( $member_subscription ) ){

                        $confirm_remove_subscription = apply_filters( 'pms_confirm_cancel_subscription', true, $member_subscription->user_id, $member_subscription->subscription_plan_id );

                        // If all is good remove the subscription, if not send an error
                        if( true == $confirm_remove_subscription ) {

                            $subscription_data = array();
                            $subscription_data['status'] = 'canceled';
            
                            // If we have a billing payment date, set it as the expiration date and remove it
                            if( empty( $member_subscription->payment_profile_id ) && ! empty( $member_subscription->billing_next_payment ) ) {
            
                                $subscription_data['expiration_date']      = $member_subscription->billing_next_payment;
                                $subscription_data['billing_next_payment'] = '';
            
                            }
                            
                            if( $member_subscription->update( $subscription_data ) ){
                                pms_add_member_subscription_log( $member_subscription->id, 'subscription_canceled_admin' );

                                pms_update_member_subscription_meta( $member_subscription->id, 'pms_retry_payment', 'inactive' );
            
                                /**
                                 * Action for when the cancellation is successful
                                 *
                                 * @param array $member_data
                                 * @param PMS_Member_Subscription $member_subscription
                                 *
                                 */
                                do_action( 'pms_cancel_member_subscription_successful', array(), $member_subscription );
    
                                $canceled_subscriptions_count++;
                                
                            }

                        }

                    }
                }

                if( $canceled_subscriptions_count != 0 )
                    $this->add_admin_notice( sprintf( _n( '%d Member Subscription successfully canceled.', '%d Member Subscriptions successfully canceled.', $canceled_subscriptions_count, 'paid-member-subscriptions' ), $canceled_subscriptions_count ), 'updated' );

            }

        }


        /**
         * Bulk add subscription plan to users
         *
         */
        if( ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), 'pms_add_new_members_bulk_nonce' ) ) {

            if( ! $this->validate_subscription_data_bulk_add( $this->request_data ) )
                return;

            if( !empty( $this->request_data['subscription_plan_id'] ) && trim( $this->request_data['subscription_plan_id'] ) != -1 && !empty( $this->request_data['users'] ) ) {

                // Get subscription plan object
                $subscription_plan = pms_get_subscription_plan( absint( $this->request_data['subscription_plan_id'] ) );

                $added_members_count = 0;

                // Loop through every user id from the request
                foreach( $this->request_data['users'] as $user_id ) {

                    $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $user_id ) );

                    if( ! empty( $member_subscriptions ) && apply_filters( 'pms_add_new_members_bulk_existing_subscriptions_check', true ) ){

                        continue;

                    } else if( !empty( $member_subscriptions ) ){

                        // check that the user is not already subscribed to the given plan
                        $already_subscribed = false;

                        foreach( $member_subscriptions as $subscription ){
                            if( $subscription->subscription_plan_id === $subscription_plan->id ){
                                $already_subscribed = true;
                                break;
                            }
                        }

                        if( $already_subscribed )
                            continue;

                    }

                    $data = array(
                        'user_id'              => $user_id,
                        'subscription_plan_id' => $subscription_plan->id,
                        'start_date'           => date( 'Y-m-d H:i:s' ),
                        'expiration_date'      => $subscription_plan->get_expiration_date(),
                        'expiration_date'      => !isset( $this->request_data['subscription_status'] ) || ( isset( $this->request_data['subscription_status'] ) && $this->request_data['subscription_status'] == 'active' ) ? $subscription_plan->get_expiration_date() : date( 'Y-m-d H:i:s' ),
                        'status'               => !isset( $this->request_data['subscription_status'] ) ? 'active' : sanitize_text_field( $this->request_data['subscription_status'] ),
                    );

                    if( isset( $this->request_data[ 'subscription_expiration_date' ] ) && !empty( $this->request_data[ 'subscription_expiration_date' ] ) ){

                        $data[ 'expiration_date' ] = date( 'Y-m-d 23:59:59', strtotime( sanitize_text_field( $this->request_data[ 'subscription_expiration_date' ] ) ) );

                        if( isset( $subscription_plan ) && isset( $subscription_plan->duration ) && isset( $subscription_plan->duration_unit ) ){

                            if( $subscription_plan->is_fixed_period_membership() )
                                $time = '- 1 year';
                            else
                                $time = '-' . $subscription_plan->duration . ' ' . $subscription_plan->duration_unit;
                            $data[ 'start_date' ] = date( 'Y-m-d H:i:s', strtotime( $data[ 'expiration_date' ] . $time ) );

                        }

                    }

                    $member_subscription = new PMS_Member_Subscription();
                    $inserted            = $member_subscription->insert( $data );

                    pms_add_member_subscription_log( $inserted, 'admin_subscription_added_bulk' );

                    if( $inserted )
                        $added_members_count++;

                }

                if( $added_members_count != 0 )
                    $this->add_admin_notice( sprintf( __( '%d members successfully added.', 'paid-member-subscriptions' ), $added_members_count ), 'updated' );

            }

        }

    }


    /**
     * Method that validates general subscription data
     *
     * @return bool
     *
     */
    protected function validate_subscription_data() {

        /*
         * User validations
         */
        $request_data = $_REQUEST;

        // Check to see if the username field is empty
        if( empty( $request_data['user_id'] ) )
            $this->add_admin_notice( __( 'Please select a user.', 'paid-member-subscriptions' ), 'error' );

        else {

            // Check to see if the username exists
            $user = get_user_by( 'id', absint( $request_data['user_id'] ) );

            if( !$user )
                $this->add_admin_notice( __( 'It seems this user does not exist.', 'paid-member-subscriptions' ), 'error' );

        }

        /**
         * Check to see if a subscription plan was selected
         */
        if( empty( $request_data['subscription_plan_id'] ) )
            $this->add_admin_notice( __( 'Please select a subscription plan.', 'paid-member-subscriptions' ), 'error' );


        /**
         * Check if user is already subscribed to a plan from this tier
         */
        if( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), 'pms_add_subscription_nonce' ) && isset( $request_data['user_id'] ) && isset( $request_data['subscription_plan_id'] ) ){

            $subscription = pms_get_current_subscription_from_tier( absint( $request_data['user_id'] ), absint( $request_data['subscription_plan_id'] ) );

            if( !empty( $subscription ) )
                $this->add_admin_notice( __( 'This user is already subscribed to the selected plan or another plan from this tier. Select another one.', 'paid-member-subscriptions' ), 'error' );

        }

        /**
         * Check to see if a start date was selected
         */
        if( empty( $request_data['start_date'] ) )
            $this->add_admin_notice( __( 'Please add a start date for the subscription.', 'paid-member-subscriptions' ), 'error' );


        /**
         * If expiration date is provided it should be bigger than the start date and also the trial date
         */
        if( ! empty( $request_data['expiration_date'] ) ) {

            if( ! empty( $request_data['start_date'] ) && ( strtotime( $request_data['expiration_date'] ) < strtotime( $request_data['start_date'] ) ) )
                $this->add_admin_notice( __( 'The expiration date needs to be greater than the start date.', 'paid-member-subscriptions' ), 'error' );

            if( ! empty( $request_data['trial_end'] ) && ( strtotime( $request_data['expiration_date'] ) < strtotime( $request_data['trial_end'] ) ) )
                $this->add_admin_notice( __( 'The expiration date needs to be greater than the trial end date.', 'paid-member-subscriptions' ), 'error' );

        }


        /**
         * Other validations from outside
         *
         * Returned array for the filter must contain arrays in the form of 'notice_type' => 'Notice message',
         * thus the returned array should be array( array('notice_type' => 'Notice message 1' ), array( 'notice_type' => 'Notice message 2' ) )
         *
         */
        $this->add_admin_notices( apply_filters( 'pms_submenu_page_members_validate_subscription_data', array(), $request_data ) );


        if( $this->has_admin_notice( 'error' ) )
            return false;
        else
            return true;

    }


    /**
     * Method that validates subscription data on edit subscription
     *
     * @return bool
     *
     */
    protected function validate_subscription_data_edit_subscription() {

        // Check to see if we have a subscription id for the subscription we want to edit
        if( empty( $_GET['subscription_id'] ) )
            $this->add_admin_notice( __( 'Something went wrong. Could not complete your request.', 'paid-member-subscriptions' ), 'error' );


        if( ! empty( $_GET['subscription_id'] ) ) {

            $member_subscription = pms_get_member_subscription( (int)$_GET['subscription_id'] );

            // Check to see if there's a subscription with the provided id
            if( is_null( $member_subscription ) )
                $this->add_admin_notice( __( 'Something went wrong. Could not complete your request.', 'paid-member-subscriptions' ), 'error' );

            // Check to see if the subscription's attached user_id is the same with the provided user_id
            elseif( isset( $_POST['user_id'] ) && $member_subscription->user_id != (int)$_POST['user_id'] )
                $this->add_admin_notice( __( 'Something went wrong. Could not complete your request.', 'paid-member-subscriptions' ), 'error' );

            // Make sure that the user doesn't have another active subscription from the same tier
            elseif ( isset( $_POST['status'] ) && $_POST['status'] != 'abandoned' ){

                $existing_subscription = pms_get_current_subscription_from_tier( (int)sanitize_text_field( $_POST['user_id'] ), $member_subscription->subscription_plan_id );

                if( !empty( $existing_subscription ) && $existing_subscription->status != 'abandoned' && $existing_subscription->id != $member_subscription->id )
                    $this->add_admin_notice( __( 'The user already has a non-abandoned subscription with this plan.', 'paid-member-subscriptions' ), 'error'  );

            }
        }


        if( $this->has_admin_notice( 'error' ) )
            return false;
        else
            return true;

    }


    /**
     * Method that validates subscription data on bulk add
     *
     * @param array $request_data
     *
     * @return bool
     *
     */
    public function validate_subscription_data_bulk_add( $request_data ){

        if( isset( $request_data[ 'subscription_status' ] ) && isset( $request_data[ 'subscription_expiration_date' ] ) ){

            if( $request_data[ 'subscription_status' ] == 'pending' || $request_data[ 'subscription_status' ] == 'expired' ){

                if( strtotime( date('Y-m-d 23:59:59', strtotime( 'today' ) ) ) < strtotime( $request_data[ 'subscription_expiration_date' ] ) ){
                    $this->add_admin_notice( __( 'The expiration date needs to be less than the current date.', 'paid-member-subscriptions' ), 'error' );
                }
            }
        }

        if( $this->has_admin_notice( 'error' ) )
            return false;
        else
            return true;
    }


    /*
     * Method to output content in the custom page
     *
     */
    public function output() {

        ob_start();

        // Display the add new member form
        if( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'add_subscription' ) {

            // Check to see if member is already subscribed
            if( ! empty( $_GET['member_id'] ) ) {

                $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => (int)$_GET['member_id'] ) );

                if( empty( $member_subscriptions ) )
                    include_once 'views/view-page-members-add-new-edit-subscription.php';
                else
                    include_once 'views/view-page-members-edit.php';

            } else {

                include_once 'views/view-page-members-add-new-edit-subscription.php';

            }

        }

        // Display the add new bulk table
        elseif( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'add_new_members_bulk' )

            include_once 'views/view-page-members-add-new-bulk.php';

        // Display the edit member form
        elseif( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'edit_member' && isset( $_REQUEST['member_id'] ) && !empty( $_REQUEST['member_id'] ) )

            include_once 'views/view-page-members-edit.php';

        elseif( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'edit_subscription' && isset( $_REQUEST['subscription_id'] ) && !empty( $_REQUEST['subscription_id'] ) )

            include_once 'views/view-page-members-add-new-edit-subscription.php';

        // Display a list with all the members
        else
            include_once 'views/view-page-members-list-table.php';

        $subpage_content = ob_get_contents();

        ob_clean();

        echo apply_filters( 'pms_submenu_page_members_output', $subpage_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }


    /**
     * Method that returns the formatted expiration date of a subscription plan
     *
     */
    public function ajax_populate_expiration_date() {

        if( !isset( $_POST['subscription_plan_id'] ) )
            die();

        $subscription_plan_id = (int)sanitize_text_field( $_POST['subscription_plan_id'] );

        if( ! empty( $subscription_plan_id ) ) {

            $subscription_plan = pms_get_subscription_plan( $subscription_plan_id );

            echo esc_html( pms_sanitize_date( $subscription_plan->get_expiration_date() ) );

        } else
            echo '';

        wp_die();

    }


    /**
     *
     *
     */
    public function ajax_populate_member_subscription_fields() {

        if( empty( $_POST['action'] ) || $_POST['action'] != 'populate_member_subscription_fields' ) {
            echo 0;
            wp_die();
        }

        if( empty( $_POST['subscription_plan_id'] ) ) {
            echo 0;
            wp_die();
        }

        $subscription_plan_id = (int)$_POST['subscription_plan_id'];
        $subscription_plan    = pms_get_subscription_plan( $subscription_plan_id );

        $subscription_data = array(
            'billing_duration'      => $subscription_plan->duration,
            'billing_duration_unit' => $subscription_plan->duration_unit,
            'start_date'            => date( 'Y-m-d' ),
            'expiration_date'       => pms_sanitize_date( $subscription_plan->get_expiration_date() ),
            'trial_end'             => pms_sanitize_date( $subscription_plan->get_trial_expiration_date() )
        );

        echo json_encode( $subscription_data );

        wp_die();

    }

    public function ajax_check_username() {

        if( empty( $_POST['username'] ) ){
            echo 0;
            die();
        }

        $username = sanitize_text_field( $_POST['username'] );

        if( is_email( $username ) )
            $type = 'email';
        else
            $type = 'login';

        $user = get_user_by( $type, $username );

        if( !empty( $user->ID ) ){
            echo esc_html( $user->ID );
            wp_die();
        }

        echo 0;
        wp_die();

    }

	/*
     * Method that adds Screen Options to Members page
     *
     */
	public function add_screen_options() {

        if( isset( $_REQUEST['subpage'] ) && $_REQUEST['subpage'] == 'add_new_members_bulk' ) {

            $args = array(
                'label'   => 'Users per page',
                'default' => 10,
                'option'  => 'pms_users_per_page'
            );

        } else {

            $args = array(
                'label'   => 'Members per page',
                'default' => 10,
                'option'  => 'pms_members_per_page'
            );

        }

		add_screen_option( 'per_page', $args );

	}

    // Generates HTML for a subscription log row
    public function get_logs_row( $log ) {

        ob_start();
        ?>

        <div class="pms-subscription-logs__row">

            <div class="pms-subscription-logs__date">
                <?php echo esc_html( ucfirst( date_i18n( 'F d, Y H:i:s', strtotime( $log['date'] ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ) ) ?>
            </div>

            <div class="pms-subscription-logs__message">
                <?php echo wp_kses_post( $this->get_log_message( $log ) ); ?>
            </div>

        </div>

        <?php

        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    // Given a log entry, returns a human readable message
    private function get_log_message( $log ) {

        $kses_args = array(
            'strong' => array()
        );

        switch ( $log['type'] ) {
            case 'subscription_canceled':
                $message = __( 'Subscription canceled by user.', 'paid-member-subscriptions' );
                break;
            case 'subscription_canceled_admin':
                $message = __( 'Subscription canceled by admin.', 'paid-member-subscriptions' );
                break;
            case 'subscription_added':
                $message = __( 'Subscription initiated by user.', 'paid-member-subscriptions' );
                break;
            case 'subscription_abandoned':
                $message = __( 'Subscription abandoned by user.', 'paid-member-subscriptions' );
                break;
            case 'subscription_activated':

                if( !empty( $log['data']['until'] ) )
                    $message = sprintf( __( 'Subscription activated until <strong>%s</strong>.', 'paid-member-subscriptions' ), date_i18n( get_option('date_format'), strtotime( $log['data']['until'] ) ) );
                else
                    $message = __( 'Subscription activated successfully.', 'paid-member-subscriptions' );

                break;
            case 'subscription_expired':
                $message = __( 'Subscription expired.', 'paid-member-subscriptions' );
                break;
            case 'subscription_renewed_manually':

                if( !empty( $log['data']['until'] ) )
                    $message = sprintf( __( 'Subscription renewed until <strong>%s</strong>.', 'paid-member-subscriptions' ), date_i18n( get_option('date_format'), strtotime( $log['data']['until'] ) ) );
                else
                    $message = __( 'Subscription renewed by user.', 'paid-member-subscriptions' );

                break;
            case 'subscription_renewed_automatically':
                $message = __( 'Subscription renewed automatically.', 'paid-member-subscriptions' );
                break;
            case 'subscription_renewal_failed':
                $message = __( 'Tried to renew subscription automatically but failed. Subscription status set to <strong>expired</strong>.', 'paid-member-subscriptions' );
                break;
            case 'subscription_renewal_failed_retry_enabled':
	            if( !empty( $log['data']['days'] ) ) {
		            $message = sprintf( __( 'Tried to renew subscription automatically but failed. Subscription status set to <strong>expired</strong>. Payment will be retried in %s days.', 'paid-member-subscriptions' ), $log['data']['days'] );
	            } else {
		            $subscription_id = isset( $_GET['subscription_id'] ) ? (int)$_GET['subscription_id'] : 0;

		            $message = sprintf( __( 'Tried to renew subscription automatically but failed. Subscription status set to <strong>expired</strong>. Payment will be retried in %s days.', 'paid-member-subscriptions' ), apply_filters( 'pms_retry_payment_interval', 3, $subscription_id ) );
	            }
                break;
            case 'subscription_renewal_failed_retry_disabled':
                $message = __( 'Subscription could not be renewed. Payment retry was disabled.', 'paid-member-subscriptions' );
                break;
            case 'subscription_upgrade_attempt':
                $message = sprintf( __( 'User attempted to upgrade his subscription plan to <strong>%s</strong>.', 'paid-member-subscriptions' ), $this->parse_admin_changed_value( $log['data']['new_plan'], 'Subscription Plan' ) );
                break;
            case 'subscription_downgrade_attempt':
                $message = sprintf( __( 'User attempted to downgrade his subscription plan to <strong>%s</strong>.', 'paid-member-subscriptions' ), $this->parse_admin_changed_value( $log['data']['new_plan'], 'Subscription Plan' ) );
                break;
            case 'subscription_change_attempt':
                $message = sprintf( __( 'User attempted to change his subscription plan to <strong>%s</strong>.', 'paid-member-subscriptions' ), $this->parse_admin_changed_value( $log['data']['new_plan'], 'Subscription Plan' ) );
                break;
            case 'subscription_retry_attempt':
                $message = __( 'User attempted to retry the payment for his subscription.', 'paid-member-subscriptions' );
                break;
            case 'subscription_renew_attempt':
                $message = __( 'User attempted to renew his subscription.', 'paid-member-subscriptions' );
                break;
            case 'subscription_upgrade_success':
                $message = sprintf( __( 'Subscription successfully upgraded from <strong>%s</strong> to <strong>%s</strong>.', 'paid-member-subscriptions' ), $this->parse_admin_changed_value( $log['data']['old_plan'], 'Subscription Plan' ), $this->parse_admin_changed_value( $log['data']['new_plan'], 'Subscription Plan' ) );
                break;
            case 'subscription_downgrade_success':
                $message = sprintf( __( 'Subscription successfully downgraded from <strong>%s</strong> to <strong>%s</strong>.', 'paid-member-subscriptions' ), $this->parse_admin_changed_value( $log['data']['old_plan'], 'Subscription Plan' ), $this->parse_admin_changed_value( $log['data']['new_plan'], 'Subscription Plan' ) );
                break;
            case 'subscription_change_success':
                $message = sprintf( __( 'Subscription successfully changed from <strong>%s</strong> to <strong>%s</strong>.', 'paid-member-subscriptions' ), $this->parse_admin_changed_value( $log['data']['old_plan'], 'Subscription Plan' ), $this->parse_admin_changed_value( $log['data']['new_plan'], 'Subscription Plan' ) );
                break;
            case 'subscription_trial_started':
                $message = sprintf( __( 'Subscription trial started until <strong>%s</strong>.', 'paid-member-subscriptions' ), date_i18n( get_option('date_format'), strtotime( $log['data']['until'] ) ) );
                break;
            case 'subscription_trial_end':
                $message = __( 'Subscription trial ended.', 'paid-member-subscriptions' );
                break;
            case 'subscription_payment_method_updated':
                $message = __( 'Payment method for the subscription updated by user.', 'paid-member-subscriptions' );
                break;
            case 'admin_subscription_edit':
                $admin_name = ucwords( $this->get_display_name( !empty( $log['data']['who'] ) ? $log['data']['who'] : '' ) );

                if( $log['data']['field'] == 'subscription_plan_id' )
                    $log['data']['field'] = 'subscription_plan';

                $field = ucwords( str_replace( '_', ' ', $log['data']['field'] ) );

                if( !empty( $log['data']['old'] ) || !empty( $log['data']['new'] ) )
                    $message = sprintf( __( '%s changed <strong>%s</strong> from <strong>%s</strong> to <strong>%s</strong>.', 'paid-member-subscriptions' ), $admin_name, $field, !empty( $log['data']['old'] ) ? $this->parse_admin_changed_value( $log['data']['old'], $field ) : '-', !empty( $log['data']['new'] ) ? $this->parse_admin_changed_value( $log['data']['new'], $field ) : '-' );
                else
                    $message = sprintf( __( '%s changed <strong>%s</strong>.', 'paid-member-subscriptions' ), $admin_name, $field );

                break;
            case 'admin_subscription_activated_payments':
                $message = __( 'Subscription activated successfully (by admin, manual offline)', 'paid-member-subscriptions' );
                break;
            case 'admin_subscription_added_members':
                $message = sprintf( __( 'Subscription initiated by %s.', 'paid-member-subscriptions' ), 'admin' );
                break;
            case 'admin_subscription_added_bulk':
                $message = sprintf( __( 'Subscription initiated by %s (bulk).', 'paid-member-subscriptions' ), 'admin' );
                break;
            case 'admin_subscription_added_payments':
                $message = sprintf( __( 'Subscription initiated by %s (payment).', 'paid-member-subscriptions' ), 'admin' );
                break;
            case 'admin_note':
                $message = sprintf( '[%s] %s', ucwords( $this->get_display_name( !empty( $log['data']['who'] ) ? $log['data']['who'] : '' ) ), $log['data']['note'] );
                break;
            case 'admin_subscription_renewed_manually':
                $message = __( 'The subscription was renewed by the admin using the manual/offline gateway.', 'paid-member-subscriptions' );
                break;
            case 'subscription_canceled_user_deletion':
                $message = sprintf( __( 'Subscription canceled because user was deleted by <strong>%s</strong>.', 'paid-member-subscriptions' ), ucwords( $this->get_display_name( !empty( $log['data']['who'] ) ? $log['data']['who'] : '' ) ) );
                break;
            case 'gateway_subscription_canceled':
                $message = __( 'Subscription canceled by gateway.', 'paid-member-subscriptions' );
                break;
            case 'changed_payment_gateway':
                $message = sprintf( __( 'Payment gateway was changed from <strong>%s</strong> to <strong>%s</strong>.', 'paid-member-subscriptions' ), $log['data']['old'], $log['data']['new'] );
                break;
            case 'woocommerce_product_subscription_activate':
                $message = sprintf( __( 'Subscription <strong>activated</strong> successfully by WooCommerce [Order #%s] until <strong>%s</strong>', 'paid-member-subscriptions' ), $log['data']['order_id'], $log['data']['expiration_date'] );
                break;
            case 'woocommerce_product_subscription_canceled':
                $message = sprintf( __( 'Subscription <strong>expired</strong> because WooCommerce <strong>Subscription #%s</strong> was canceled. ', 'paid-member-subscriptions' ), $log['data']['woo_subscription_id'] );
                break;
            case 'woocommerce_product_subscription_status_update':
                $message = sprintf( __( '<strong>Status</strong> changed from <strong>%s</strong> to <strong>%s</strong> by WooCommerce [Order #%s].', 'paid-member-subscriptions' ), $log['data']['old_status'], $log['data']['new_status'], $log['data']['order_id'] );
                break;
            case 'woocommerce_product_subscription_status_set':
                $message = sprintf( __( '<strong>Status</strong> set to <strong>%s</strong> by WooCommerce [Order #%s].', 'paid-member-subscriptions' ), $log['data']['status'], $log['data']['order_id'] );
                break;
            case 'woocommerce_product_subscription_expiration_update':
                $message = sprintf( __( '<strong>Expiration date</strong> updated to <strong>%s</strong> by WooCommerce [Order #%s].', 'paid-member-subscriptions' ), $log['data']['new_expire_date'], $log['data']['order_id'] );
                break;
            case 'woocommerce_product_subscription_replacement':
                $message = sprintf( __( 'Your <strong>Subscription Plan</strong> has been %s to <strong>%s</strong>  by WooCommerce [Order #%s].', 'paid-member-subscriptions' ), $log['data']['type'], $log['data']['new_name'], $log['data']['order_id'] );
                break;
            case 'woocommerce_product_subscription_expiration_renewal':
                $message = sprintf( __( '<strong>Expiration date</strong> updated to <strong>%s</strong> by WooCommerce recurring payment process [Order #%s].', 'paid-member-subscriptions' ), $log['data']['new_expire_date'], $log['data']['order_id'] );
                break;
            case 'woocommerce_product_subscription_next_payment_update':
                $message = sprintf( __( 'Next <strong>Scheduled Payment date</strong> updated to <strong>%s</strong> by the recurring payment process', 'paid-member-subscriptions' ), $log['data']['new_payment_date'] );
                break;
            case 'woocommerce_new_product_subscription':
                $message = sprintf( __( 'Subscription initiated by WooCommerce [Order #%s]', 'paid-member-subscriptions' ), $log['data']['order_id'] );
                break;
            case 'subscription_trial_period_already_used':
                $message = __( 'The <strong>Trial Period</strong> for this Subscription has already been used.', 'paid-member-subscriptions' );
                break;
            case 'subscription_import_created':
                $admin_name = ucwords( $this->get_display_name( !empty( $log['data']['who'] ) ? $log['data']['who'] : '' ) );

                $message = sprintf( __( 'Subscription created through an import done by %s.', 'paid-member-subscriptions' ), '<strong>' . $admin_name . '</strong>' );

                break;
            case 'subscription_import_updated':
                $admin_name = ucwords( $this->get_display_name( !empty( $log['data']['who'] ) ? $log['data']['who'] : '' ) );
                $fields     = !empty( $log['data']['fields'] ) ? $log['data']['fields'] : '';

                $message = sprintf( __( 'Subscription updated through an import done by %s. The following keys were updated: %s', 'paid-member-subscriptions' ), '<strong>' . $admin_name . '</strong>', $fields );

                break;
            case 'paypal_payment_token_created':
                $message = __( 'Payment Token successfully setup with PayPal for this subscription.', 'paid-member-subscriptions' );
                break;
            case 'paypal_payment_token_creation_failed':
                $message = __( 'Payment Token creation failed with PayPal for this subscription.', 'paid-member-subscriptions' );
                break;
            case 'paypal_webhook_subscription_expired':
                $message = __( 'Subscription expired because the payment was refunded in the PayPal Dashboard.', 'paid-member-subscriptions' );
                break;
            case 'paypal_webhook_payment_token_deleted':
                $message = __( 'Subscription was canceled because the payment token was deleted.', 'paid-member-subscriptions' );
                break;
            case 'paypal_webhook_payment_token_created':
                $message = __( 'Payment token created and assigned to the subscription.', 'paid-member-subscriptions' );
                break;
            default:
                $message = __( 'Something went wrong.', 'paid-member-subscriptions' );
                break;
        }

        return apply_filters( 'pms_subscription_logs_system_error_messages', wp_kses( $message, $kses_args ), $log );

    }

    // Parse admin changed values
    private function parse_admin_changed_value( $value, $field = '' ){

        if( empty( $value ) )
            return '';

        if( $field == 'Subscription Plan' ){
            $plan = pms_get_subscription_plan( (int)$value );

            if( !empty( $plan->name ) )
                return $plan->name;

        } else if ( $field == 'Expiration Date' || $field == 'Start Date' || $field == 'Billing Next Payment' || $field == 'Trial End' )
            return date_i18n( get_option('date_format'), strtotime( $value ) );

        return $value;

    }

    // Given a user returns a name for display in our messages
    private function get_display_name( $user_id ){
        if( empty( $user_id ) )
            return 'Admin';

        $user = get_userdata( $user_id );

        if ( !$user )
            return 'Admin';
        else
            return $user->display_name;
    }

    // Ajax callback for adding a log entry
    public function ajax_add_log_entry(){

        check_ajax_referer( 'pms_add_log_entry', 'nonce' );

        if( empty( $_POST['subscription_id'] ) || empty( $_POST['log'] ) )
            die();

        if( pms_add_member_subscription_log( (int)$_POST['subscription_id'], 'admin_note', array( 'note' => sanitize_text_field( $_POST['log'] ), 'who' => get_current_user_id() ) ) ){
            $subscription_logs = pms_get_member_subscription_meta( (int)$_POST['subscription_id'], 'logs', true );

            if( !empty( $subscription_logs ) ){
                ob_start();

                foreach( array_reverse( $subscription_logs ) as $log ) echo wp_kses_post( $this->get_logs_row( $log ) );

                $output = ob_get_contents();
                ob_end_clean();

                echo json_encode( array( 'status' => 'success', 'data' => $output ) );
                die();
            }
        }

        die();

    }

}

function pms_init_members_page() {

    $pms_submenu_page_members = new PMS_Submenu_Page_Members( 'paid-member-subscriptions', __( 'Members', 'paid-member-subscriptions' ), __( 'Members', 'paid-member-subscriptions' ), 'manage_options', 'pms-members-page', 10, '', 'pms_members_per_page' );
    $pms_submenu_page_members->init();    

}
add_action( 'init', 'pms_init_members_page', 9 );
