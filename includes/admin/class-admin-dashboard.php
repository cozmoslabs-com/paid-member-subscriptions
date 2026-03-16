<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

Class PMS_Submenu_Page_Dashboard extends PMS_Submenu_Page {

    /*
     * The start date to filter results
     *
     * @var string
     *
     */
    public $start_date;


    /*
     * The end date to filter results
     *
     * @var string
     *
     */
    public $end_date;


    /*
     * Array of payments retrieved from the database given the user filters
     *
     * @var array
     *
     */
    public $queried_payments = array();


    /*
     * Array with the formatted results ready for chart.js usage
     *
     * @var array
     *
     */
    public $results = array();


    /*
     * Method that initializes the class
     *
     */
    public function init() {

        // Hook the output method to the parent's class action for output instead of overwriting the
        // output method
        add_action( 'pms_output_content_submenu_page_' . $this->menu_slug, array( $this, 'output' ) );

        add_action( 'wp_ajax_get_dashboard_stats', array( $this, 'get_dashboard_stats' ) );
        
        // Generic AJAX router for dashboard issue actions
        add_action( 'wp_ajax_pms_dashboard_issue_action', array( $this, 'handle_dashboard_issue_action' ) );
        
        // Clear cache when payment settings are updated
        add_action( 'update_option_pms_payments_settings', array( __CLASS__, 'clear_dashboard_issues_cache' ) );

        // Clear cache when license status changes (activation, deactivation, etc.)
        add_action( 'pms_license_status_changed', array( __CLASS__, 'clear_dashboard_issues_cache' ) );
        add_action( 'wp_ajax_pms_dismiss_setup_widget', array( $this, 'pms_dismiss_setup_widget_handler' ) );

    }

    /**
     * Get the dashboard stats
     * 
     * @return array Array of dashboard stats
     */
    public function get_dashboard_stats(){

        check_admin_referer( 'pms_dashboard_get_stats' );

        if( !current_user_can( 'manage_options' ) )
            die();

        if( empty( $_POST['interval'] ) )
            return;

        $interval = sanitize_text_field( $_POST['interval'] );
        $return = array(
            'success' => true,
            'data'    => array(),
        );
        
        // generate filter data
        $args = array();

        if( $interval == '30days' ){
            
        } else if( $interval == 'this_month' ){

            $args['interval'][] = date( 'Y-m-01', time() );
            $args['interval'][] = date( 'Y-m-d', time() );

        } else if( $interval == 'last_month' ){

            $args['interval'][] = date( 'Y-m-01', strtotime( '-1 month' ) );
            $args['interval'][] = date( 'Y-m-t', strtotime( '-1 month' ) );

        } else if( $interval == 'this_year' ){

            $args['interval'][] = date( 'Y-01-01', time() );
            $args['interval'][] = date( 'Y-m-d', time() );

        } else if( $interval == 'last_year' ){

            $args['interval'][] = date( 'Y-01-01', strtotime( '-1 year' ) );
            $args['interval'][] = date( 'Y-12-31', strtotime( '-1 year' ) );

        }

        $return['data'] = $this->get_stats( $args );

        echo json_encode( $return );
        die();

    }

    /**
     * Generic AJAX handler for dashboard issue actions
     * Routes to specific methods based on action parameter
     * 
     * @return void
     */
    public function handle_dashboard_issue_action() {
        // Verify nonce and capability
        if ( !isset( $_POST['_ajax_nonce'] ) || !isset( $_POST['action_name'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing required parameters.', 'paid-member-subscriptions' ) ) );
        }
        
        $action_name = sanitize_text_field( wp_unslash( $_POST['action_name'] ) );
        $nonce_action = 'pms_dashboard_issue_' . $action_name;
        
        if ( !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ), $nonce_action ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'paid-member-subscriptions' ) ) );
        }
        
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'paid-member-subscriptions' ) ) );
        }
        
        // Route to specific handler method
        $method_name = 'handle_issue_' . $action_name;
        
        if ( method_exists( $this, $method_name ) ) {
            $this->$method_name();
        } else {
            // Allow other code to handle this action
            do_action( 'pms_dashboard_issue_action_' . $action_name );
            
            // If action didn't output anything, send default success
            if ( !defined( 'DOING_AJAX_RESPONSE' ) ) {
                wp_send_json_success( array( 'message' => __( 'Action completed.', 'paid-member-subscriptions' ) ) );
            }
        }
    }

    /**
     * Handle wp_cron_disabled issue confirmation
     * 
     * @return void
     */
    public function handle_issue_wp_cron_disabled() {
        update_option( 'pms_dashboard_issue_wp_cron_on_server', true );
        
        self::clear_dashboard_issues_cache();
        
        wp_send_json_success( array(
            'message'       => __( 'Confirmation saved successfully.', 'paid-member-subscriptions' ),
            'reload_issues' => true,
        ) );
    }

    /**
     * Handle website_previously_initialized issue dismissal
     * 
     * @return void
     */
    public function handle_issue_website_previously_initialized_dismiss() {
        update_option( 'pms_dashboard_issue_website_previously_initialized_dismissed', true );
        
        self::clear_dashboard_issues_cache();
        
        wp_send_json_success( array(
            'message'       => __( 'Notification dismissed successfully.', 'paid-member-subscriptions' ),
            'reload_issues' => true,
        ) );
    }

    /**
     * Handle dev_environment issue dismissal
     * 
     * @return void
     */
    public function handle_issue_dev_environment_dismiss() {
        update_option( 'pms_dashboard_issue_dev_environment_dismissed', true );
        
        self::clear_dashboard_issues_cache();
        
        wp_send_json_success( array(
            'message'       => __( 'Notification dismissed successfully.', 'paid-member-subscriptions' ),
            'reload_issues' => true,
        ) );
    }

    /*
     * Method to output content in the custom page
     *
     */
    public function output() {

        if( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'pms-setup' )
            do_action( 'pms_output_dashboard_setup_wizard' );
        else
            include_once 'views/view-page-dashboard.php';

    }

    public function pms_dismiss_setup_widget_handler() {

        check_ajax_referer( 'pms_dismiss_nonce', 'nonce' );

        update_option( 'pms_dismiss_setup_progress', 'yes' );

        wp_send_json_success();
    }


    /**
     * Get the active payment gateways
     * 
     * @return string The active payment gateways
     */
    public function get_active_payment_gateways(){

        $payment_gateways = pms_get_payment_gateways();
        $active_gateways  = pms_get_active_payment_gateways();
        
        if( !empty( $active_gateways ) ) {
            $display_gateways = '';
            
            foreach( $active_gateways as $gateway ) {

                if( empty( $display_gateways ) )
                    $display_gateways = isset( $payment_gateways[ $gateway ]['display_name_admin'] ) ? $payment_gateways[ $gateway ]['display_name_admin'] : '';
                else 
                    $display_gateways .= ', ' . ( isset( $payment_gateways[ $gateway ]['display_name_admin'] ) ? $payment_gateways[ $gateway ]['display_name_admin'] : '' );

            }

            return esc_html( $display_gateways );
        }

        return esc_html_e( 'None', 'paid-member-subscriptions' );

    }

    /**
     * Get the dashboard stats
     * 
     * @param array $args The arguments for the stats
     * 
     * @return array The dashboard stats
     */
    public static function get_stats( $args = array() ){

        // All time
        $data = array(
            'all_active_members'     => self::get_active_members(),
            'new_subscriptions'      => 0,
            'earnings'               => 0,
            'all_time_earnings'      => pms_format_price( self::get_all_time_earnings() ),
            'new_paid_subscriptions' => 0,
            'payments_count'         => 0,
        );

        // Payments Related Data
        $payments_args = array( 'status' => 'completed', 'number' => -1 );
        
        // default is 30 days, args is filled only for AJAX requests right now
        if( empty( $args['interval'] ) ){

            $payments_args['date'] = array( date('Y-m-d', strtotime( '-30 days' ) ), date('Y-m-d', time() ) );

        } else if( is_array( $args['interval'] ) ) {

            $payments_args['date'] = $args['interval'];

        }

        $payments = pms_get_payments( $payments_args );

        if( !empty( $payments ) ){
            
            // payments count 
            $data['payments_count'] = count( $payments );

            foreach( $payments as $payment ) {
                if( !empty( $payment->amount ) )
                    $data['earnings'] += apply_filters( 'pms_dashboard_selected_period_earnings_amount', $payment->amount, $payment );
            }

            $data['earnings'] = pms_format_price( $data['earnings'] );
        }

        // Subscriptions Related Data
        $subscriptions_args = array( 'status' => 'active' );

        // default is 30 days, args is filled only for AJAX requests right now
        if( empty( $args['interval'] ) ){

            $subscriptions_args['start_date_after'] = date('Y-m-d', strtotime( '-30 days' ) );

        } else if( is_array( $args['interval'] ) ) {

            $subscriptions_args['start_date_after'] = $args['interval'][0];
            $subscriptions_args['start_date_before'] = $args['interval'][1];

        }

        $subscriptions = pms_get_member_subscriptions( $subscriptions_args );

        if( !empty( $subscriptions ) ){

            foreach( $subscriptions as $subscription ){

                $data['new_subscriptions'] = $data['new_subscriptions'] + 1;

                $plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

                if( !empty( $plan->price ) )
                    $data['new_paid_subscriptions'] = $data['new_paid_subscriptions'] + 1;

            }

        }

        return $data;
    }

    /**
     * Get the dashboard stats labels
     * 
     * @return array The dashboard stats labels
     */
    public function get_stats_labels(){
        return array(
            'all_time_earnings'      => __( 'All Time Earnings', 'paid-member-subscriptions' ),
            'new_subscriptions'      => __( 'New Members', 'paid-member-subscriptions' ),
            'earnings'               => __( 'Earnings', 'paid-member-subscriptions' ),
            'all_active_members'     => __( 'Active Subscriptions', 'paid-member-subscriptions' ),
            'new_paid_subscriptions' => __( 'New Paid Subscriptions', 'paid-member-subscriptions' ),
            'payments_count'         => __( 'Payments', 'paid-member-subscriptions' ),
        );
    }

    /**
     * Get the active members
     * 
     * @return int The active members
     */
    public static function get_active_members(){

        global $wpdb;

        $result = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) AS total FROM {$wpdb->prefix}pms_member_subscriptions WHERE status = %s", 'active' ) );

        if( !empty( $result ) )
            return (int)$result;
    
        return 0;

    }

    /**
     * Get all time earnings
     * 
     * @return int The all time earnings
     */
    public static function get_all_time_earnings(){
        $payments = pms_get_payments( array( 'status' => 'completed', 'number' => -1 ) );
        $total = 0;

        foreach( $payments as $payment ){
            if( !empty( $payment->amount ) )
                $total += apply_filters( 'pms_dashboard_total_earnings_amount', $payment->amount, $payment );
        }

        return (int)$total;
    }

    /**
     * Get all dashboard issues
     * 
     * Collects issues from various sources using the pms_dashboard_issues filter.
     * Each issue should have a unique key and contain raw data.
     * Uses transient caching with 1-hour expiration for performance.
     * 
     * @return array Array of issues with unique keys
     */
    public static function get_dashboard_issues(){
        
        $bypass_cache = apply_filters( 'pms_bypass_dashboard_issues_cache', false );
        
        
        if ( !$bypass_cache ) {
            $cached_issues = get_transient( 'pms_dashboard_issues_cache' );
            
            if ( $cached_issues !== false ) {
                return $cached_issues;
            }
        }
        
        $issues = array();

        /**
         * Filter to allow adding dashboard issues
         * 
         * @param array $issues Array of issues (empty by default)
         * 
         * @return array Modified issues array with format:
         *               [
         *                   'issue_key' => [
         *                       'count' => 5,
         *                       'amount' => '$100.00',
         *                       // ... other raw data
         *                   ]
         *               ]
         */
        $issues = apply_filters( 'pms_dashboard_issues', $issues );

        // Store in cache for 1 hour (3600 seconds)
        set_transient( 'pms_dashboard_issues_cache', $issues, HOUR_IN_SECONDS );

        return $issues;
    }

    /**
     * Clear the dashboard issues cache
     * 
     * Deletes the cached issues transient and fires an action hook
     * to allow other code to respond to cache invalidation.
     * 
     * @return void
     */
    public static function clear_dashboard_issues_cache(){
        delete_transient( 'pms_dashboard_issues_cache' );
        
        /**
         * Action fired after dashboard issues cache is cleared
         * 
         * Allows other code to respond to cache invalidation.
         */
        do_action( 'pms_dashboard_issues_cache_cleared' );
    }

    /**
     * Interpret dashboard issues into display format
     * 
     * Takes raw issues and converts them into formatted arrays ready for display.
     * Uses a general filter that receives the issue key and data.
     * 
     * @param array $issues Raw issues array from get_dashboard_issues()
     * 
     * @return array Array of interpreted issues with format:
     *               [
     *                   [
     *                       'title' => 'Issue Title',
     *                       'description' => 'Issue description',
     *                       'severity' => 'critical|warning|info',
     *                       'actions' => [
     *                           [
     *                               'text'     => 'Button Text',
     *                               'url'      => 'https://example.com',
     *                               'type'     => 'primary|secondary', // Optional, defaults to 'secondary'
     *                               'target'   => '_self|_blank',      // Optional, defaults to '_self'
     *                               'behavior' => 'url'                // Required, can be 'url', 'ajax', or 'dialog'
     *                           ]
     *                       ]
     *                   ]
     *               ]
     */
    public static function interpret_dashboard_issues( $issues ){
        $interpreted = array();

        if( empty( $issues ) ) {
            return $interpreted;
        }

        foreach( $issues as $issue_key => $issue_data ) {
            /**
             * Filter to interpret individual dashboard issues
             * 
             * @param array|null $interpreted_issue Interpreted issue array (null by default)
             * @param string     $issue_key         The unique key identifying the issue type
             * @param array      $issue_data        Raw issue data
             * 
             * @return array|null Formatted issue array with:
             *                    - title (string): Issue title
             *                    - description (string): Issue description
             *                    - severity (string): 'critical', 'warning', or 'info'
             *                    - actions (array): Array of action button definitions, each with 'text', 'url', 'type', 'target', 'behavior'
             */
            $interpreted_issue = apply_filters( 'pms_interpret_dashboard_issue', null, $issue_key, $issue_data );

            if( !empty( $interpreted_issue ) && is_array( $interpreted_issue ) ) {
                $interpreted[] = $interpreted_issue;
            }
        }

        return $interpreted;
    }

    /**
     * Get the dashboard health status
     * 
     * Determines if there are any critical issues that need attention.
     * 
     * @return string 'healthy' if no critical issues, 'needs_attention' if there are critical issues
     */
    public static function get_dashboard_health_status(){
        $issues = self::get_dashboard_issues();

        if( empty( $issues ) ) {
            return 'healthy';
        }

        // Interpret issues to check their severity
        $interpreted_issues = self::interpret_dashboard_issues( $issues );

        // Check if any critical issues exist
        foreach( $interpreted_issues as $issue ) {
            if( isset( $issue['severity'] ) && $issue['severity'] === 'critical' ) {
                return 'needs_attention';
            }
        }

        return 'healthy';
    }

    /**
     * Get the plan name
     * 
     * @param int $subscription_plan_id The subscription plan ID
     * 
     * @return string The plan name
     */
    public function get_plan_name( $subscription_plan_id ){
        $plan = pms_get_subscription_plan( $subscription_plan_id );

        if( !empty( $plan->name ) )
            return $plan->name;

        return 'unknown';
    }

}

function pms_init_dashboard_page() {

    global $pms_submenu_page_dashboard;

    if( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'pms-setup' )
        $page_title = __( 'Setup Wizard', 'paid-member-subscriptions' );
    else
        $page_title = __( 'Dashboard', 'paid-member-subscriptions' );
    
    $pms_submenu_page_dashboard = new PMS_Submenu_Page_Dashboard( 'paid-member-subscriptions', $page_title, $page_title, 'manage_options', 'pms-dashboard-page', 5 );
    $pms_submenu_page_dashboard->init();

}
add_action( 'init', 'pms_init_dashboard_page', 9 );