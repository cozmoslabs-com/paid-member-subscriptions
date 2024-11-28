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

        // Process different actions within the page
        //add_action( 'init', array( $this, 'process_data' ) );

    }

    /*
     * Method that processes data on reports admin pages
     *
     */
    public function process_data() {

        // // Get current actions
        // $action = !empty( $_REQUEST['pms-action'] ) ? sanitize_text_field( $_REQUEST['pms-action'] ) : '';

        // // Get default results if no filters are applied by the user
        // if( empty($action) && !empty( $_REQUEST['page'] ) && $_REQUEST['page'] == 'pms-reports-page' ) {

        //     $this->queried_payments = $this->get_filtered_payments();

        //     $results = $this->prepare_payments_for_output( $this->queried_payments );

        // } else {

        //     // Verify correct nonce
        //     if( !isset( $_REQUEST['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), 'pms_reports_nonce' ) )
        //         return;

        //     // Filtering results
        //     if( $action == 'filter_results' ) {

        //         $this->queried_payments = $this->get_filtered_payments();

        //         $results = $this->prepare_payments_for_output( $this->queried_payments );

        //     }

        // }

        // if( !empty( $results ) )
        //     $this->results = $results;

    }

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
                    $data['earnings'] = $data['earnings'] + $payment->amount;
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

    public static function get_active_members(){

        global $wpdb;

        $result = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) AS total FROM {$wpdb->prefix}pms_member_subscriptions WHERE status = %s", 'active' ) );

        if( !empty( $result ) )
            return (int)$result;
    
        return 0;

    }

    public static function get_all_time_earnings(){

        global $wpdb;

        $result = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) AS total FROM {$wpdb->prefix}pms_payments WHERE status = %s", 'completed' ) );
    
        if( !empty( $result ) )
            return (int)$result;
    
        return 0;

    }

    public function get_plan_name( $subscription_plan_id ){
        $plan = pms_get_subscription_plan( $subscription_plan_id );

        if( !empty( $plan->name ) )
            return $plan->name;

        return 'unknown';
    }

}

global $pms_submenu_page_dashboard;

if( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'pms-setup' )
    $page_title = __( 'Setup Wizard', 'paid-member-subscriptions' );
else
    $page_title = __( 'Dashboard', 'paid-member-subscriptions' );

$pms_submenu_page_dashboard = new PMS_Submenu_Page_Dashboard( 'paid-member-subscriptions', $page_title, $page_title, 'manage_options', 'pms-dashboard-page', 5 );
$pms_submenu_page_dashboard->init();
