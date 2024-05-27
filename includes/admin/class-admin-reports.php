<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Extends core PMS_Submenu_Page base class to create and add custom functionality
 * for the reports section in the admin section
 *
 */
Class PMS_Submenu_Page_Reports extends PMS_Submenu_Page {

    /*
     * The start date to filter results
     *
     * @var string
     *
     */
    public $start_date;
    public $start_previous_date;


    /*
     * The end date to filter results
     *
     * @var string
     *
     */
    public $end_date;
    public $end_previous_date;

    /*
     * The total of days in a month
     *
     * @var string
     *
     */
    public $month_total_days = 0;


    /*
     * Array of payments retrieved from the database given the user filters
     *
     * @var array
     *
     */
    public $queried_payments = array();
    public $queried_payments_attempts = array();
    public $queried_previous_payments = array();
    public $queried_previous_attempts = array();


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

        // Enqueue admin scripts
        add_action( 'pms_submenu_page_enqueue_admin_scripts_before_' . $this->menu_slug, array( $this, 'admin_scripts' ) );

        // Hook the output method to the parent's class action for output instead of overwriting the
        // output method
        add_action( 'pms_output_content_submenu_page_' . $this->menu_slug, array( $this, 'output' ) );

        // Process different actions within the page
        add_action( 'init', array( $this, 'process_data' ) );

        // Output filters
        add_action( 'pms_reports_filters', array( $this, 'output_filters' ) );

        // Period reports table
        add_action( 'pms_reports_page_bottom', array( $this, 'output_reports_table' ) );

        add_action( 'admin_print_footer_scripts', array( $this, 'output_chart_js_data' ) );

    }


    /*
     * Method to enqueue admin scripts
     *
     */
    public function admin_scripts() {

        wp_enqueue_script( 'pms-chart-js', PMS_PLUGIN_DIR_URL . 'assets/js/admin/libs/chart/chart.min.js' );

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

    }


    /*
     * Method that processes data on reports admin pages
     *
     */
    public function process_data() {

        // Get current actions
        $action = !empty( $_REQUEST['pms-action'] ) ? sanitize_text_field( $_REQUEST['pms-action'] ) : '';

        // Get default results if no filters are applied by the user
        if( empty($action) && !empty( $_REQUEST['page'] ) && $_REQUEST['page'] == 'pms-reports-page' ) {

            $this->queried_payments = $this->get_filtered_payments();

            $results = $this->prepare_payments_for_output( $this->queried_payments );

        } else {

            // Verify correct nonce
            if( !isset( $_REQUEST['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), 'pms_reports_nonce' ) )
                return;

            // Filtering results
            if( $action == 'filter_results' ) {

                $this->queried_payments = $this->get_filtered_payments();

                $results = $this->prepare_payments_for_output( $this->queried_payments );

            }

        }

        if( !empty( $results ) )
            $this->results = $results;

    }


    /*
     * Return an array of payments, payments depending on the user's input filters
     *
     * @return array
     *
     */
    private function get_filtered_payments() {

        if( isset( $_REQUEST['pms-filter-time'] ) && $_REQUEST['pms-filter-time'] == 'custom_date' && !empty( $_REQUEST['pms-filter-time-start-date'] ) && !empty( $_REQUEST['pms-filter-time-end-date'] ) ){

            $this->start_date = sanitize_text_field( $_REQUEST['pms-filter-time-start-date'] );
            $this->end_date   = sanitize_text_field( $_REQUEST['pms-filter-time-end-date'] ) . ' 23:59:59';

            $this->start_previous_date = DateTime::createFromFormat('Y-m-d', $this->start_date);
            $this->start_previous_date->modify('-1 year');
            $this->start_previous_date = $this->start_previous_date->format('Y-m-d');

            $this->end_previous_date = DateTime::createFromFormat('Y-m-d H:i:s', $this->end_date);
            $this->end_previous_date->modify('-1 year');
            $this->end_previous_date = $this->end_previous_date->format('Y-m-d') . ' 23:59:59';

        } else {

            if( empty( $_REQUEST['pms-filter-time'] ) || $_REQUEST['pms-filter-time'] == 'this_month' )
                $date = date("Y-m-d");
            else
                $date = sanitize_text_field( $_REQUEST['pms-filter-time'] );

            if( $date === 'today' || $date === 'yesterday'){

                $data = new DateTime( $date );
                $data = $data->format('Y-m-d');
                $this->start_date = $data . ' 00:00:00';
                $this->end_date   = $data . ' 23:59:59';

            }
            else if( $date === 'this_week'){

                $this->start_date = new DateTime('this week monday');
                $this->month_total_days = $this->start_date->format( 't' );
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('this week sunday');
                $this->end_date = $this->end_date->format('Y-m-d');
                $this->end_date = $this->end_date . ' 23:59:59';
            }
            else if( $date === 'last_week' ){

                $this->start_date = new DateTime('last week monday');
                $this->month_total_days = $this->start_date->format( 't' );
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last week sunday');
                $this->end_date = $this->end_date->format('Y-m-d');
                $this->end_date = $this->end_date . ' 23:59:59';

            }
            else if( $date === '30days' ){

                $this->start_date = new DateTime('today - 30 days');
                $this->month_total_days = $this->start_date->format( 't' );
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('today');
                $this->end_date = $this->end_date->format('Y-m-d');
                $this->end_date = $this->end_date . ' 23:59:59';
            }
            else if( $date === 'this_month' ){

                $this->start_date = new DateTime('first day of this month');
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last day of this month');
                $this->end_date = $this->end_date->format('Y-m-d');
                $this->end_date = $this->end_date . ' 23:59:59';
            }
            else if( $date === 'last_month' ){

                $this->start_date = new DateTime('first day of last month');
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last day of last month');
                $this->end_date = $this->end_date->format('Y-m-d');
                $this->end_date = $this->end_date . ' 23:59:59';
            }
            else if ( $date === 'this_year' ){

                $this->start_date = new DateTime('first day of January this year');
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last day of December this year');
                $this->end_date = $this->end_date->format('Y-m-d');
                $this->end_date = $this->end_date . ' 23:59:59';
            }
            else if ( $date === 'last_year' ){

                $this->start_date = new DateTime('first day of January last year');
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last day of December last year');
                $this->end_date = $this->end_date->format('Y-m-d');
                $this->end_date = $this->end_date . ' 23:59:59';
            }
            else if( $date === 'custom_date' ){

                if( empty( $_GET['pms-filter-time-start-date'] ) || empty( $_GET['pms-filter-time-end-date'] ) )
                {
                    $this->start_date = '0000-00-00';
                    $this->end_date = '0000-00-00';
                }
            }
            else{

                $this->start_date = new DateTime('first day of this month');
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last day of this month');
                $this->end_date = $this->end_date->format('Y-m-d');
                $this->end_date = $this->end_date . ' 23:59:59';
            }

            if( $date === 'today' || $date === 'yesterday'){
                $this->start_previous_date = DateTime::createFromFormat('Y-m-d H:i:s', $this->start_date);
                $this->start_previous_date->modify('-1 year');
                $this->start_previous_date = $this->start_previous_date->format('Y-m-d');
            }
            else{
                $this->start_previous_date = DateTime::createFromFormat('Y-m-d', $this->start_date);
                $this->start_previous_date->modify('-1 year');
                $this->start_previous_date = $this->start_previous_date->format('Y-m-d');
            }

            $this->end_previous_date = DateTime::createFromFormat('Y-m-d H:i:s', $this->end_date);
            $this->end_previous_date->modify('-1 year');
            $this->end_previous_date = $this->end_previous_date->format('Y-m-d') . ' 23:59:59';
        }

        $specific_subs = array();

        if( isset( $_REQUEST['pms-filter-subscription-plans'] ) && !empty( $_GET['pms-filter-subscription-plans'] ) ){
            $specific_subs = array_map('absint', $_GET['pms-filter-subscription-plans'] );
            $args = apply_filters( 'pms_reports_get_filtered_payments_args', array( 'status' => 'completed', 'date' => array( $this->start_date, $this->end_date ), 'order' => 'ASC', 'number' => '-1', 'subscription_plan_id' => $specific_subs ) );
            $args_attempts = array(
                    'status' => array( 'completed', 'pending', 'failed' ),
                    'date' => array( $this->start_date, $this->end_date ),
                    'order' => 'ASC',
                    'number' => '-1',
                    'subscription_plan_id' => $specific_subs );
            $args_previous_period = array(
                    'status' => 'completed',
                    'date' => array( $this->start_previous_date, $this->end_previous_date ),
                    'order' => 'ASC',
                    'number' => '-1',
                    'subscription_plan_id' => $specific_subs );
            $args_previous_attempts = array(
                'status' => array( 'completed', 'pending', 'failed' ),
                'date' => array( $this->start_previous_date, $this->end_previous_date ),
                'order' => 'ASC',
                'number' => '-1',
                'subscription_plan_id' => $specific_subs );
        }
        else
        {
            $args = apply_filters( 'pms_reports_get_filtered_payments_args', array( 'status' => 'completed', 'date' => array( $this->start_date, $this->end_date ), 'order' => 'ASC', 'number' => '-1' ) );
            $args_attempts = array(
                    'status' => array( 'completed', 'pending', 'failed' ),
                    'date' => array( $this->start_date, $this->end_date ),
                    'order' => 'ASC',
                    'number' => '-1' );
            $args_previous_period = array(
                    'status' => 'completed',
                    'date' => array( $this->start_previous_date, $this->end_previous_date ),
                    'order' => 'ASC',
                    'number' => '-1');
            $args_previous_attempts = array(
                'status' => array( 'completed', 'pending', 'failed' ),
                'date' => array( $this->start_previous_date, $this->end_previous_date ),
                'order' => 'ASC',
                'number' => '-1' );
        }

        $payments = pms_get_payments( $args );
        $this->queried_payments_attempts = pms_get_payments( $args_attempts );
        $this->queried_previous_payments = pms_get_payments( $args_previous_period );
        $this->queried_previous_attempts = pms_get_payments( $args_previous_attempts );

        return $payments;

    }


    /*
     * Get filtered results by date
     *
     * @param $start_date - has format Y-m-d
     * @param $end_date   - has format Y-m-d
     *
     * @return array
     *
     */
    private function prepare_payments_for_output( $payments = array() ) {

        $results = array();

        if( empty( $_REQUEST['pms-filter-time'] ) )
            $date = date("Y-m-d");
        else
            $date = sanitize_text_field( $_REQUEST['pms-filter-time'] );

        if( $date === 'today' || $date === 'yesterday' ){

            $first_hour = new DateTime( $this->start_date );
            $first_hour = $first_hour->format('G');

            $last_hour = new DateTime( $this->end_date );
            $last_hour = $last_hour->format('G');

            for( $i = $first_hour; $i <= $last_hour; $i++ ) {
                if( !isset( $results[$i] ) )
                    $results[$i] = array( 'earnings' => 0, 'payments' => 0 );
            }
        }
        else if( $date === 'this_week' || $date === 'last_week' || $date === '30days' || $date === 'this_month' || $date === 'last_month' ){

            $first_day = new DateTime( $this->start_date );
            $first_month = $first_day->format('n');
            $first_day = $first_day->format('j');

            $last_day  = new DateTime( $this->end_date );
            $last_month = $last_day->format('n');
            $last_day  = $last_day->format('j');

            if( $first_day >= $last_day || ( $first_day < $last_day && $first_month < $last_month ) ){
                for( $i = $first_day; $i <= $this->month_total_days; $i++ ) {
                    if( !isset( $results[$i] ) )
                        $results[$i] = array( 'earnings' => 0, 'payments' => 0 );
                }

                for( $i = 1; $i <= $last_day; $i++ ) {
                    if( !isset( $results[$i] ) )
                        $results[$i] = array( 'earnings' => 0, 'payments' => 0 );
                }
            }
            else{
                for( $i = $first_day; $i <= $last_day; $i++ ) {
                    if( !isset( $results[$i] ) )
                        $results[$i] = array( 'earnings' => 0, 'payments' => 0 );
                }
            }
        }
        else if( $date === 'this_year' || $date === 'last_year' ){

            $first_month = 1;
            $last_month = 12;

            for( $i = $first_month; $i <= $last_month; $i++ ) {
                if( !isset( $results[$i] ) )
                    $results[$i] = array( 'earnings' => 0, 'payments' => 0 );
            }
        }
        else if( $date === 'custom_date' ){

            $first = new DateTime( $this->start_date );

            $first_year = $first->format('Y');
            $first_month = $first->format('n');
            $first_day = $first->format('j');

            $last = new DateTime( $this->end_date );

            $last_year = $last->format('Y');
            $last_month = $last->format('n');
            $last_day = $last->format('j');

            $gap_between_years = $last_year - $first_year;
            $number_year = 1;

            if( $gap_between_years > 0 )
            {
                for( $i = $first_year; $i <= $last_year && $number_year <= $gap_between_years + 1; $i++ ){
                    if( $number_year === 1 ){
                        for( $j = $first_month; $j <= 12; $j++ ){
                            if( !isset( $results[$j] ) )
                                $results[$j] = array( 'earnings' => 0, 'payments' => 0 );
                        }
                    }
                    else{
                        $end_month = 12 * $number_year;
                        $start_month = $end_month - 11;

                        if( $i == $last_year ){
                            $end_month = $last_month + 12 * ( $number_year - 1 );
                        }

                        for( $j = $start_month; $j <= $end_month; $j++ ){
                            if( !isset( $results[$j] ) )
                                $results[$j] = array( 'earnings' => 0, 'payments' => 0 );
                        }
                    }
                    $number_year++;
                }
            }
            else{
                $gap_between_months = $last_month - $first_month;

                if( $gap_between_months > 0 ){

                        for( $i = $first_month; $i <= $last_month; $i++ ) {
                            if( !isset( $results[$i] ) )
                                $results[$i] = array( 'earnings' => 0, 'payments' => 0 );
                        }
                }
                else{
                    if( $first_day === $last_day ){
                        for( $i = 0; $i <= 23; $i++ ) {
                            if( !isset( $results[$i] ) )
                                $results[$i] = array( 'earnings' => 0, 'payments' => 0 );
                        }
                    }
                    else{
                        for( $i = $first_day; $i <= $last_day; $i++ ) {
                            if( !isset( $results[$i] ) )
                                $results[$i] = array( 'earnings' => 0, 'payments' => 0 );
                        }
                    }
                }
            }

        }
        else
        {
            $first_day = new DateTime( $this->start_date );
            $first_month = $first_day->format('n');
            $first_day = $first_day->format('j');

            $last_day  = new DateTime( $this->end_date );
            $last_month = $last_day->format('n');
            $last_day  = $last_day->format('j');

            if( $first_day >= $last_day || ( $first_day < $last_day && $first_month < $last_month ) ){
                for( $i = $first_day; $i <= $this->month_total_days; $i++ ) {
                    if( !isset( $results[$i] ) )
                        $results[$i] = array( 'earnings' => 0, 'payments' => 0 );
                }

                for( $i = 1; $i <= $last_day; $i++ ) {
                    if( !isset( $results[$i] ) )
                        $results[$i] = array( 'earnings' => 0, 'payments' => 0 );
                }
            }
            else{
                for( $i = $first_day; $i <= $last_day; $i++ ) {
                    if( !isset( $results[$i] ) )
                        $results[$i] = array( 'earnings' => 0, 'payments' => 0 );
                }
            }
        }

        if( !empty( $payments ) ) {
            foreach( $payments as $payment ) {
                $payment_date = new DateTime( $payment->date );

                if( $date === 'today' || $date === 'yesterday' ){
                    $results[ $payment_date->format('G') ]['earnings'] += $payment->amount;
                    $results[ $payment_date->format('G') ]['payments'] += 1;
                }
                else if( $date === 'this_week' || $date === 'last_week' || $date === '30days' || $date === 'this_month' || $date === 'last_month' ){
                        $results[ $payment_date->format('j') ]['earnings'] += $payment->amount;
                        $results[ $payment_date->format('j') ]['payments'] += 1;
                }
                else if( $date === 'this_year' || $date === 'last_year'){
                        $results[ $payment_date->format('n') ]['earnings'] += $payment->amount;
                        $results[ $payment_date->format('n') ]['payments'] += 1;
                }
                else if( $date === 'custom_date' ){

                    $first = new DateTime( $this->start_date );

                    $first_year = $first->format('Y');
                    $first_month = $first->format('n');
                    $first_day = $first->format('j');

                    $last = new DateTime( $this->end_date );

                    $last_year = $last->format('Y');
                    $last_month = $last->format('n');
                    $last_day = $last->format('j');

                    $gap_between_years = $last_year - $first_year;

                    if( $gap_between_years > 0 ){

                        foreach ( $results as $key => $data ){

                            if( $key > 12 ){

                                $current_year = $first_year + (int)floor( ( $key - 1 ) / 12 );
                                $current_month = (int)( $key - 1 ) % 12 + 1;

                                if ( $payment_date->format('Y') == $current_year &&  $payment_date->format('n') == $current_month )
                                {
                                    $results[ $key ]['earnings'] += $payment->amount;
                                    $results[ $key ]['payments'] += 1;
                                }
                            }
                        }
                    }
                    else{
                        $gap_between_months = $last_month - $first_month;

                        if( $gap_between_months > 0 ){
                                $results[ $payment_date->format('n') ]['earnings'] += $payment->amount;
                                $results[ $payment_date->format('n') ]['payments'] += 1;
                        }
                        else{
                            if( $first_day === $last_day ){
                                $results[ $payment_date->format('G') ]['earnings'] += $payment->amount;
                                $results[ $payment_date->format('G') ]['payments'] += 1;
                            }
                            else{
                                $results[ $payment_date->format('j') ]['earnings'] += $payment->amount;
                                $results[ $payment_date->format('j') ]['payments'] += 1;
                            }
                        }
                    }
                }
                else{
                    $results[ $payment_date->format('j') ]['earnings'] += $payment->amount;
                    $results[ $payment_date->format('j') ]['payments'] += 1;
                }
            }
        }

        return apply_filters( 'pms_reports_get_filtered_results', $results, $this->start_date, $this->end_date );

    }


    /*
     * Method to output content in the custom page
     *
     */
    public function output() {
        $active_tab = 'pms-reports-page';
        include_once 'views/view-page-reports.php';

    }


    /*
     * Outputs the input filter's the admin has at his disposal
     *
     */
    public function output_filters() {
        ?>

        <div class="cozmoslabs-form-field-wrapper" id="pms-container-select-date">
            <div class="pms-container-date-range cozmoslabs-form-field-wrapper" id="pms-container-date">
                    <label class="cozmoslabs-form-field-label" for="pms-reports-filter-month"><?php esc_html_e( 'Interval', 'paid-member-subscriptions' ) ?></label>
                <?php

                echo '<select name="pms-filter-time" id="pms-reports-filter-month">';

                      echo '<option value="this_month"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( 'this_month', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('This Month', 'paid-member-subscriptions') . '</option>';
                      echo '<option value="today"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( 'today', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('Today', 'paid-member-subscriptions') . '</option>';
                      echo '<option value="yesterday"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( 'yesterday', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('Yesterday', 'paid-member-subscriptions') . '</option>';
                      echo '<option value="this_week"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( 'this_week', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('This Week', 'paid-member-subscriptions') . '</option>';
                      echo '<option value="last_week"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( 'last_week', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('Last Week', 'paid-member-subscriptions') . '</option>';
                      echo '<option value="30days"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( '30days', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('Last 30 days', 'paid-member-subscriptions') . '</option>';
                      echo '<option value="last_month"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( 'last_month', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('Last Month', 'paid-member-subscriptions') . '</option>';
                      echo '<option value="this_year"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( 'this_year', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('This Year', 'paid-member-subscriptions') . '</option>';
                      echo '<option value="last_year"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( 'last_year', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('Last Year', 'paid-member-subscriptions') . '</option>';
                      echo '<option value="custom_date"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( 'custom_date', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('Custom Range', 'paid-member-subscriptions') . '</option>';

                echo '</select>';

                ?>
            </div>
            <div class="pms-custom-date-range-options" id="pms-custom-date-range-options" style="<?php echo !empty( $_GET['pms-filter-time'] ) && $_GET['pms-filter-time'] === 'custom_date' ? '' : 'display:none' ?>">
                <label for="pms-reports-start-date" id="pms-reports-start-date-label" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Start Date','paid-member-subscriptions' ); ?></label>

                <input type="text" id="pms-reports-start-date" name="pms-filter-time-start-date" class="pms_datepicker" value="<?php echo esc_attr( isset( $_GET['pms-filter-time-start-date'] ) ? sanitize_text_field( $_GET['pms-filter-time-start-date'] ) : '' ); ?>">


                <label for="pms-reports-expiration-date" id="pms-reports-expiration-date-label" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'End Date','paid-member-subscriptions' ); ?></label>

                <input type="text" id="pms-reports-expiration-date" name="pms-filter-time-end-date" class="pms_datepicker" value="<?php echo esc_attr( isset( $_GET['pms-filter-time-end-date'] ) ? sanitize_text_field( $_GET['pms-filter-time-end-date'] ) : '' ); ?>">

            </div>
        </div>

        <div class="cozmoslabs-form-field-wrapper" id="pms-container-specific-subs">
            <label class="cozmoslabs-form-field-label" for="specific-subscriptions"><?php esc_html_e( 'Select Subscription Plans', 'paid-member-subscriptions' ) ?></label>

            <select id="specific-subscriptions" class="pms-chosen" name="pms-filter-subscription-plans[]" multiple style="/*width:200px*/" data-placeholder="<?php echo esc_attr__( 'All', 'paid-member-subscriptions' ); ?>">
                <?php
                $subscription_plans = pms_get_subscription_plans(false);
                $specific_subs = array();

                if( isset( $_GET['pms-filter-subscription-plans'] ) && !empty( $_GET['pms-filter-subscription-plans'] ) ){
                    $specific_subs = array_map('absint', $_GET['pms-filter-subscription-plans'] );
                }

                foreach ( $subscription_plans as $subscription ){
                    echo '<option value="' . esc_attr( $subscription->id ) . '"' . ( !empty( $specific_subs ) && in_array( $subscription->id, $specific_subs ) ? ' selected' : '') . '>' . esc_html( $subscription->name ) . '</option>';
                }
                ?>
            </select>

            <p class="cozmoslabs-description cozmoslabs-description-space-left">
                <?php esc_html_e( 'Select only the Subscriptions Plans you want to see the statistics for.', 'paid-member-subscriptions' ); ?>
            </p>
        </div>
        <?php

    }

    /*
         *
         * Count any type of payment
         */
    public function pms_counting_type_of_payment( $payment_type, $types_wanted, &$count )
    {
        if ( in_array( $payment_type, $types_wanted ) )
            $count++;

        return $count;
    }

    /*
         *
         * Gather any type of payment
         */
    public function pms_sum_type_of_payment( $payment_type, $types_wanted, $payment_amount, &$total )
    {
        if ( in_array( $payment_type, $types_wanted ) )
            $total += $payment_amount;

        return $total;
    }

    /*
         *
         * Gather any type and status of payment
         */
    public function pms_sum_type_and_status_of_payment( $payment_type, $types_wanted, $payment_status, $payment_wanted, $payment_amount, &$total )
    {
        if ( in_array( $payment_type, $types_wanted ) && in_array( $payment_status, $payment_wanted ) )
            $total += $payment_amount;

        return $total;
    }

    /*
         *
         * Counting any type and status of payment
         */
    public function pms_count_type_and_status_of_payment( $payment_type, $types_wanted, $payment_status, $payment_wanted, &$count )
    {
        if ( in_array( $payment_type, $types_wanted ) && in_array( $payment_status, $payment_wanted ) )
            $count++;

        return $count;
    }

    /*
         *
         * Counting payments with any status but type of 'subscription_retry_payment'
         */
    public function pms_return_counting_attempts( $queried ){
        $attempts_payments = ['subscription_retry_payment'];
        $count_attempts_payments = 0;

        if( !empty( $queried ) ) {
            foreach( $queried as $payment ){
                $count_attempts_payments = $this->pms_counting_type_of_payment( $payment->type, $attempts_payments, $count_attempts_payments );
            }
        }

        return $count_attempts_payments;
    }

    /*
     * Returns the discount code array prepared
     *
     */

    public function pms_prepare_discount_codes( $discount_codes_result, $queried_payments ){
        if( !empty( $queried_payments ) ) {
            foreach( $queried_payments as $payment ){
                if( !isset( $discount_codes_result[ $payment->discount_code ] ) )
                    $discount_codes_result[ $payment->discount_code ] = array( 'name' => $payment->discount_code, 'count' => 0 );
            }
        }

        return $discount_codes_result;
    }

    /*
     * Returns the payment gateway array prepared
     *
     */

    public function pms_prepare_payment_gateway( $payment_gateways_result ){
        $all_gateways = pms_get_payment_gateways();

        if( !empty( $all_gateways ) ){
            foreach ( $all_gateways as $gateway_key => $gateway_data ){
                if( !isset( $payment_gateways_result[$gateway_key] ) ){
                    $payment_gateways_result[ $gateway_key ] = array( 'name' => $gateway_data['display_name_admin'], 'earnings' => 0, 'percent' => 0 );
                }
            }
        }

        return $payment_gateways_result;
    }

    public function get_summary_data( $queried_payments ){

        $payments_count  = count( $queried_payments );
        $payments_amount = 0;

        $subscriptions_plans_result = array();
        $discount_codes_result = array();
        $payment_gateways_result = array();
        $specific_subs = array();

        $completed_payments = ['subscription_initial_payment', 'recurring_payment_profile_created', 'expresscheckout', 'subscription_renewal_payment', 'subscription_upgrade_payment', 'subscription_downgrade_payment'];
        $reccuring_payments = ['subscription_recurring_payment', 'recurring_payment', 'subscr_payment'];
        $attempts_payments = ['subscription_retry_payment'];
        $status = ['completed'];
        $total_completed_payments = $total_reccuring_payments = $total_recovered_payments = $total_successful_retries = 0;

        $discount_codes_result = $this->pms_prepare_discount_codes( $discount_codes_result, $queried_payments );
        $payment_gateways_result = $this->pms_prepare_payment_gateway( $payment_gateways_result );

        if( isset( $_GET['pms-filter-subscription-plans'] ) && !empty( $_GET['pms-filter-subscription-plans'] ) ){
            $specific_subs = array_map('absint', $_GET['pms-filter-subscription-plans'] );

            foreach ( $specific_subs as $sub_id ){
                if( !isset( $subscriptions_plans_result[$sub_id] ) )
                    $subscriptions_plans_result[$sub_id] = array( 'name' => '', 'earnings' => 0, 'count' => 0 );
            }
        }
        else{
            $specific_subs = pms_get_subscription_plans(false);

            foreach ( $specific_subs as $plan ){
                if( !isset( $subscriptions_plans_result[$plan->id] ) )
                    $subscriptions_plans_result[$plan->id] = array( 'name' => '', 'earnings' => 0, 'count' => 0 );
            }
        }

        if( !empty( $queried_payments ) ) {

            foreach( $queried_payments as $payment )
            {
                $payments_amount += $payment->amount;

                $subscriptions_plans_result[ intval( $payment->subscription_id ) ]['earnings'] += $payment->amount;
                $subscriptions_plans_result[ intval( $payment->subscription_id ) ]['count'] ++;

                if( empty( $subscriptions_plans_result[ intval( $payment->subscription_id ) ]['name'] ) ){
                    $plan = pms_get_subscription_plan( $payment->subscription_id );
                    $subscriptions_plans_result[ intval( $payment->subscription_id ) ]['name'] = $plan->name;
                }

                $discount_codes_result[ $payment->discount_code ]['count']++;
                if( isset( $payment_gateways_result[ $payment->payment_gateway ]['earnings'] ) ){
                    $payment_gateways_result[ $payment->payment_gateway ]['earnings'] += $payment->amount;
                }

                $total_completed_payments = $this->pms_sum_type_of_payment( $payment->type, $completed_payments, $payment->amount, $total_completed_payments );
                $total_reccuring_payments = $this->pms_sum_type_of_payment( $payment->type, $reccuring_payments, $payment->amount, $total_reccuring_payments );
                $total_recovered_payments = $this->pms_sum_type_and_status_of_payment( $payment->type, $attempts_payments, $payment->status, $status, $payment->amount, $total_recovered_payments );
                $total_successful_retries = $this->pms_count_type_and_status_of_payment( $payment->type, $attempts_payments, $payment->status, $status, $total_successful_retries );
            }

            //Sorting the plans after best performing earnings
            usort($subscriptions_plans_result, function( $first_plan, $second_plan ) {
                return $second_plan['earnings'] - $first_plan['earnings'];
            });

            //Sorting the discount codes after the most used
            usort($discount_codes_result, function( $first_plan, $second_plan ) {
                return $second_plan['count'] - $first_plan['count'];
            });
        }

        $summary_data = array(
            'payments_amount' => $payments_amount,
            'payments_count' => $payments_count,
            'total_completed_payments' => $total_completed_payments,
            'total_reccuring_payments' => $total_reccuring_payments,
            'subscriptions_plans_result' => $subscriptions_plans_result,
            'payment_gateways_result' => $payment_gateways_result,
            'total_successful_retries' => $total_successful_retries,
            'total_recovered_payments' => $total_recovered_payments,
            'discount_codes_result' => $discount_codes_result
        );

        return $summary_data;
    }


    public function output_summary_area( $summary_data, $title, $results_arrow, $previous = false ){

        $payments_amount = $summary_data['payments_amount'];
        $payments_count = $summary_data['payments_count'];
        $total_completed_payments = $summary_data['total_completed_payments'];
        $total_reccuring_payments = $summary_data['total_reccuring_payments'];
        $subscriptions_plans_result = $summary_data['subscriptions_plans_result'];
        $payment_gateways_result = $summary_data['payment_gateways_result'];
        $total_successful_retries = $summary_data['total_successful_retries'];
        $total_recovered_payments = $summary_data['total_recovered_payments'];
        $discount_codes_result = $summary_data['discount_codes_result'];

        $id_general = 'pms-general-link';
        $id_subscription_plans = 'pms-subscription-plans-link';
        $id_discount_codes = 'pms-discount-codes-link';

        $class_general_section = 'pms-general-section';
        $class_subscription_plans_section = 'pms-subscription-plans-section';
        $class_discount_codes_section = 'pms-discount-codes-section';
        $class_section = 'present';

        if( $previous ){
            $id_general .= '-previous';
            $id_subscription_plans .= '-previous';
            $id_discount_codes .= '-previous';
            $class_general_section .= '-previous';
            $class_subscription_plans_section .= '-previous';
            $class_discount_codes_section .= '-previous';
            $class_section = 'previous';
        }

        ?>
        <div class="postbox cozmoslabs-form-subsection-wrapper <?php echo esc_html( $class_section ); ?>">
            <h4 class="cozmoslabs-subsection-title"><?php echo esc_html( $title ); ?></h4>

            <div class="inside">
                <a id="<?php echo esc_html( $id_general ); ?>"><?php echo esc_html__( 'General', 'paid-member-subscriptions' ); ?></a>
                <a id="<?php echo esc_html( $id_subscription_plans ); ?>"><?php echo esc_html__( 'Subscription Plans', 'paid-member-subscriptions' ); ?></a>
                <a id="<?php echo esc_html( $id_discount_codes ); ?>"><?php echo esc_html__( 'Discount Codes', 'paid-member-subscriptions' ); ?></a>

                <div class="<?php echo esc_html( $class_general_section ); ?>">
                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total earnings for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'Total Earnings', 'paid-member-subscriptions' ); ?></label>
                        <span><?php echo esc_html( pms_format_price( $payments_amount, pms_get_active_currency() ) ); ?></span>
                        <?php if( !empty( $results_arrow['payments_amount']['present'] ) && !$previous ){ ?>
                        <span class="pms-arrow"><img class="pms-arrow" src="<?php echo esc_html( $results_arrow['payments_amount']['present'] ); ?>"></span>
                        <span style="<?php
                            if( $results_arrow['payments_amount']['difference'] > 0 )
                                echo 'color: red';
                            elseif( $results_arrow['payments_amount']['difference'] < 0 )
                                echo 'color: green';
                                ?>"><?php echo '(' . esc_html( number_format( $results_arrow['payments_amount']['percent'], 2 ) ) . '%)'; ?></span>
                        <?php } ?>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total number of payments for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'Total Payments', 'paid-member-subscriptions' ); ?></label>
                        <span><?php echo esc_html( $payments_count ); ?></span>
                        <?php if( !empty( $results_arrow['payments_count']['present'] ) && !$previous ){ ?>
                            <span class="pms-arrow"><img class="pms-arrow" src="<?php echo esc_html( $results_arrow['payments_count']['present'] ); ?>"></span>
                            <span style="<?php
                            if( $results_arrow['payments_count']['difference'] > 0 )
                                echo 'color: red';
                            elseif( $results_arrow['payments_count']['difference'] < 0 )
                                echo 'color: green';
                            ?>"><?php echo '(' . esc_html( number_format( $results_arrow['payments_count']['percent'], 2 ) ) . '%)'; ?></span>
                        <?php } ?>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total earnings of completed payments for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'New Revenue', 'paid-member-subscriptions' ); ?></label>
                        <span title="<?php
                        if( $payments_amount != 0) {
                            $completed_payments_percent = ( 100 * $total_completed_payments ) / $payments_amount;
                            echo '(' . esc_html( number_format( $completed_payments_percent, 2 ) ) . '%)';
                        }
                        else{
                            $completed_payments_percent = 0;
                            echo '(' . esc_html( number_format( $completed_payments_percent, 2 ) ) . '%)';
                        }
                        ?>"><?php echo esc_html( pms_format_price( $total_completed_payments, pms_get_active_currency() ) ); ?></span>
                        <?php if( !empty( $results_arrow['total_completed_payments']['present'] ) && !$previous ){ ?>
                            <span class="pms-arrow"><img class="pms-arrow" src="<?php echo esc_html( $results_arrow['total_completed_payments']['present'] ); ?>"></span>
                            <span style="<?php
                            if( $results_arrow['total_completed_payments']['difference'] > 0 )
                                echo 'color: red';
                            elseif( $results_arrow['total_completed_payments']['difference'] < 0 )
                                echo 'color: green';
                            ?>"><?php echo '(' . esc_html( number_format( $results_arrow['total_completed_payments']['percent'], 2 ) ) . '%)'; ?></span>
                        <?php } ?>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total earnings of reccuring payments for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'Recurring Revenue', 'paid-member-subscriptions' ); ?></label>
                        <span title="<?php
                        if( $payments_amount != 0) {
                            $reccuring_payments_percent = ( 100 * $total_reccuring_payments ) / $payments_amount;
                            echo '(' . esc_html( number_format( $reccuring_payments_percent, 2 ) ) . '%)';
                        }
                        else{
                            $reccuring_payments_percent = 0;
                            echo '(' . esc_html( number_format( $reccuring_payments_percent, 2 ) ) . '%)';
                        }
                        ?>"><?php echo esc_html( pms_format_price( $total_reccuring_payments, pms_get_active_currency() ) ); ?></span>
                        <?php if( !empty( $results_arrow['total_reccuring_payments']['present'] ) && !$previous ){ ?>
                            <span class="pms-arrow"><img class="pms-arrow" src="<?php echo esc_html( $results_arrow['total_reccuring_payments']['present'] ); ?>"></span>
                            <span style="<?php
                            if( $results_arrow['total_reccuring_payments']['difference'] > 0 )
                                echo 'color: red';
                            elseif( $results_arrow['total_reccuring_payments']['difference'] < 0 )
                                echo 'color: green';
                            ?>"><?php echo '(' . esc_html( number_format( $results_arrow['total_reccuring_payments']['percent'], 2 ) ) . '%)'; ?></span>
                        <?php } ?>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'The plan with the most income for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'Best Performing Plan', 'paid-member-subscriptions' ); ?></label>
                        <?php if( !empty( $subscriptions_plans_result ) && isset( $subscriptions_plans_result[0] ) ){ ?>
                            <span><?php echo esc_html( $subscriptions_plans_result[0]['name'] ); ?></span>
                            <span><?php echo esc_html( '-' ); ?></span>
                            <span><?php echo esc_html( pms_format_price( $subscriptions_plans_result[0]['earnings'], pms_get_active_currency() ) ); ?></span>
                        <?php }
                        else{
                            ?>
                            <span><?php echo esc_html__( '-', 'paid-member-subscriptions'); ?></span>
                            <?php
                        }?>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper pms-gateway-revenue">
                        <label class="pms-form-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Payment Gateways Revenue', 'paid-member-subscriptions' ); ?></label>
                        <ul>
                            <?php
                            $nr_payment_gateways = 0;
                            if( !empty( $payment_gateways_result ) ){
                                foreach ( $payment_gateways_result as $payment_gateway ){
                                    if( $payment_gateway['earnings'] > 0){
                                        $nr_payment_gateways++;
                                        ?>
                                        <li>
                                            <div class="cozmoslabs-form-field-wrapper">
                                                <label class="pms-form-field-label cozmoslabs-form-field-label"><?php echo esc_html( $payment_gateway['name'] ); ?></label>
                                                <span title="<?php
                                                if( $payments_amount != 0) {
                                                    $payment_gateway['percent'] = ( 100 * $payment_gateway['earnings'] ) / $payments_amount;
                                                    echo '(' . esc_html( number_format( $payment_gateway['percent'], 2 ) ) . '%)';
                                                }
                                                else{
                                                    $payment_gateway['percent'] = 0;
                                                    echo '(' . esc_html( number_format( $payment_gateway['percent'], 2 ) ) . '%)';
                                                }?>"><?php echo esc_html( pms_format_price( $payment_gateway['earnings'], pms_get_active_currency() ) ); ?></span>
                                            </div>
                                        </li>
                                        <?php
                                    }
                                }

                                if( $nr_payment_gateways === 0 ){
                                    ?>
                                    <div class="cozmoslabs-form-field-wrapper">
                                        <label class="pms-form-field-label"><?php echo esc_html__( 'There are no payments for the selected period.', 'paid-member-subscriptions'); ?></label>
                                    </div>
                                    <?php
                                }
                            }
                            else{
                                ?>
                                <div class="cozmoslabs-form-field-wrapper">
                                    <label class="pms-form-field-label"><?php echo esc_html__( 'There aren\'t any gateways activated.', 'paid-member-subscriptions'); ?></label>
                                </div>
                                <?php
                            }
                            ?>
                        </ul>
                    </div>

                <?php if( pms_is_payment_retry_enabled() ){ ?>
                    <div class="cozmoslabs-form-field-wrapper pms-payment-retries">
                        <label class="pms-form-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Payment Retries', 'paid-member-subscriptions' ); ?></label>
                        <ul>
                            <li>
                                <div class="cozmoslabs-form-field-wrapper">
                                    <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total number of attempts payments for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'Attempts Retries', 'paid-member-subscriptions' ); ?></label>
                                    <span><?php if( $previous ){
                                            echo esc_html( $this->pms_return_counting_attempts( $this->queried_previous_attempts ) );
                                        }
                                        else{
                                            echo esc_html( $this->pms_return_counting_attempts( $this->queried_payments_attempts ) );
                                        }
                                    ?></span>
                                </div>
                            </li>

                            <li>
                                <div class="cozmoslabs-form-field-wrapper">
                                    <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total number of successful attempts payments for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'Successful Retries', 'paid-member-subscriptions' ); ?></label>
                                    <span><?php echo esc_html( $total_successful_retries ); ?></span>
                                </div>
                            </li>

                            <li>
                                <div class="cozmoslabs-form-field-wrapper">
                                    <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total number of recovered payments for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'Recovered Revenue', 'paid-member-subscriptions' ); ?></label>
                                    <span><?php echo esc_html( pms_format_price( $total_recovered_payments, pms_get_active_currency() ) ); ?></span>
                                </div>
                            </li>
                        </ul>
                    </div>
                <?php } ?>
                </div>

                <div class="<?php echo esc_html( $class_subscription_plans_section ); ?>" id="pms_subscription_plans_section">
                    <div class="pms-subscription-plans-header">
                        <label class="pms-form-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Subscription Plan', 'paid-member-subscriptions' ); ?></label>
                        <label class="pms-form-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Earnings', 'paid-member-subscriptions' ); ?></label>
                        <label class="pms-form-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Payments', 'paid-member-subscriptions' ); ?></label>
                    </div>
                    <?php
                    $nr_plans = 0;
                    foreach ( $subscriptions_plans_result as $plan ){
                        if( $plan['earnings'] > 0 ){
                            $nr_plans++; ?>
                            <div class="cozmoslabs-form-field-wrapper pms-plans-section">
                                <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total earnings for the selected subscription plan', 'paid-member-subscriptions' ); ?>"><?php echo esc_html( $plan['name'] ); ?></label>
                                <label class="pms-form-field-label cozmoslabs-form-field-label pms-normal-font" ><?php echo esc_html( pms_format_price( $plan['earnings'], pms_get_active_currency() ) ); ?></label>
                                <label class="pms-form-field-label cozmoslabs-form-field-label pms-normal-font" ><?php echo esc_html( $plan['count'] ); ?></label>
                            </div>
                        <?php   }
                    }
                    if( $nr_plans === 0 ){
                        ?>
                        <div class="cozmoslabs-form-field-wrapper">
                            <label class="pms-form-field-label"><?php echo esc_html__( 'No Data.', 'paid-member-subscriptions'); ?></label>
                        </div>
                        <?php
                    }
                    ?>
                </div>

                <div class="<?php echo esc_html( $class_discount_codes_section ); ?>" id="pms_discount_codes_section">
                    <div class="pms-discount-codes-header">
                        <label class="pms-form-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Discount Code', 'paid-member-subscriptions' ); ?></label>
                        <label class="pms-form-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Uses', 'paid-member-subscriptions' ); ?></label>
                    </div>
                    <?php
                    $nr_discounts = 0;
                    if( !empty( $discount_codes_result ) ){
                        foreach ( $discount_codes_result as $discount_code ){
                            if( !empty( $discount_code['name'] ) ){
                                $nr_discounts++;
                                ?>
                                <div class="cozmoslabs-form-field-wrapper">
                                    <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total earnings for the type of discount', 'paid-member-subscriptions' ); ?>"><?php echo esc_html( $discount_code['name'] ); ?></label>
                                    <span><?php echo esc_html( $discount_code['count'] ); ?></span>
                                </div>
                                <?php
                            }
                        }
                        if( $nr_discounts === 0 ){
                            ?>
                            <div class="cozmoslabs-form-field-wrapper">
                                <label class="pms-form-field-label"><?php echo esc_html__( 'No discount codes were used in the selected period.', 'paid-member-subscriptions'); ?></label>
                            </div>
                            <?php
                        }
                    }
                    else{
                        ?>
                        <div class="cozmoslabs-form-field-wrapper">
                            <label class="pms-form-field-label"><?php echo esc_html__( 'No discount codes were used in the selected period.', 'paid-member-subscriptions'); ?></label>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /*
     * Outputs a summary with the payments and earnings for the selected period
     *
     */
    public function output_reports_table() {

        $summary_data = $this->get_summary_data( $this->queried_payments );
        $summary_previous_data = $this->get_summary_data( $this->queried_previous_payments );

        $arrows = array(
                'up' => PMS_PLUGIN_DIR_URL.'assets/images/pms-caret-up.svg',
                'down' => PMS_PLUGIN_DIR_URL.'assets/images/pms-caret-down.svg'
        );

        $results_arrow = array(
                'payments_amount' => array( 'present' => '', 'previous' => '', 'difference' => 0, 'percent' => 0 ),
                'payments_count' => array( 'present' => '', 'previous' => '', 'difference' => 0, 'percent' => 0 ),
                'total_completed_payments' => array( 'present' => '', 'previous' => '', 'difference' => 0, 'percent' => 0 ),
                'total_reccuring_payments' => array( 'present' => '', 'previous' => '', 'difference' => 0, 'percent' => 0 )
        );

        $results_arrow['payments_amount']['difference'] = $summary_previous_data['payments_amount'] - $summary_data['payments_amount'];
        if( $summary_previous_data['payments_amount'] != 0 ){
            $results_arrow['payments_amount']['percent'] = ( abs( $results_arrow['payments_amount']['difference'] ) * 100 ) / $summary_previous_data['payments_amount'];
        }
        else{
            $results_arrow['payments_amount']['percent'] = 100;
        }

        $results_arrow['payments_count']['difference'] = $summary_previous_data['payments_count'] - $summary_data['payments_count'];
        if( $summary_previous_data['payments_count'] != 0 ){
            $results_arrow['payments_count']['percent'] = ( abs( $results_arrow['payments_count']['difference'] ) * 100 ) / $summary_previous_data['payments_count'];
        }
        else{
            $results_arrow['payments_count']['percent'] = 100;
        }

        $results_arrow['total_completed_payments']['difference'] = $summary_previous_data['total_completed_payments'] - $summary_data['total_completed_payments'];
        if( $summary_previous_data['total_completed_payments'] != 0 ){
            $results_arrow['total_completed_payments']['percent'] = ( abs( $results_arrow['total_completed_payments']['difference'] ) * 100 ) / $summary_previous_data['total_completed_payments'];
        }
        else{
            $results_arrow['total_completed_payments']['percent'] = 100;
        }

        $results_arrow['total_reccuring_payments']['difference'] = $summary_previous_data['total_reccuring_payments'] - $summary_data['total_reccuring_payments'];
        if( $summary_previous_data['total_reccuring_payments'] != 0 ){
            $results_arrow['total_reccuring_payments']['percent'] = ( abs( $results_arrow['total_reccuring_payments']['difference'] ) * 100 ) / $summary_previous_data['total_reccuring_payments'];
        }
        else{
            $results_arrow['total_reccuring_payments']['percent'] = 100;
        }

        if( $summary_data['payments_amount'] > $summary_previous_data['payments_amount'] ){
            $results_arrow['payments_amount']['present'] = $arrows['up'];
            $results_arrow['payments_amount']['previous'] = $arrows['down'];
        }
        else if( $summary_data['payments_amount'] < $summary_previous_data['payments_amount'] ){
            $results_arrow['payments_amount']['present'] = $arrows['down'];
            $results_arrow['payments_amount']['previous'] = $arrows['up'];
        }

        if( $summary_data['payments_count'] > $summary_previous_data['payments_count'] ){
            $results_arrow['payments_count']['present'] = $arrows['up'];
            $results_arrow['payments_count']['previous'] = $arrows['down'];
        }
        else if( $summary_data['payments_count'] < $summary_previous_data['payments_count'] ){
            $results_arrow['payments_count']['present'] = $arrows['down'];
            $results_arrow['payments_count']['previous'] = $arrows['up'];
        }

        if( $summary_data['total_completed_payments'] > $summary_previous_data['total_completed_payments'] ){
            $results_arrow['total_completed_payments']['present'] = $arrows['up'];
            $results_arrow['total_completed_payments']['previous'] = $arrows['down'];
        }
        else if( $summary_data['total_completed_payments'] < $summary_previous_data['total_completed_payments'] ){
            $results_arrow['total_completed_payments']['present'] = $arrows['down'];
            $results_arrow['total_completed_payments']['previous'] = $arrows['up'];
        }

        if( $summary_data['total_reccuring_payments'] > $summary_previous_data['total_reccuring_payments'] ){
            $results_arrow['total_reccuring_payments']['present'] = $arrows['up'];
            $results_arrow['total_reccuring_payments']['previous'] = $arrows['down'];
        }
        else if( $summary_data['total_reccuring_payments'] < $summary_previous_data['total_reccuring_payments'] ){
            $results_arrow['total_reccuring_payments']['present'] = $arrows['down'];
            $results_arrow['total_reccuring_payments']['previous'] = $arrows['up'];
        }
        ?>
        <div class="pms-reports-summary-section">
        <?php
            $this->output_summary_area( $summary_data, esc_html__( 'Summary', 'paid-member-subscriptions' ), $results_arrow, false );
            $this->output_summary_area( $summary_previous_data, esc_html__( 'Summary - Previous Year', 'paid-member-subscriptions' ), $results_arrow, true );
        ?>
        </div>
        <?php

    }

    /*
     * Output the javascript data as variables
     *
     */
    public function output_chart_js_data() {

        if( empty( $this->results ) )
            return;

        $results = $this->results;

        // Generate chart labels
        $chart_labels_js_array = $data_set_earnings_js_array = $data_set_payments_js_array = array();

        foreach( $results as $key => $details ) {

            $chart_labels_js_array[] = $key;
            $data_set_earnings_js_array[] = $details['earnings'];
            $data_set_payments_js_array[] = $details['payments'];

        }

        // Start ouput
        echo '<script type="text/javascript">';

            echo 'var pms_currency = "' . esc_html( html_entity_decode( pms_get_currency_symbol( pms_get_active_currency() ) ) ) . '";';

            echo 'var pms_chart_labels = ' . wp_json_encode( $chart_labels_js_array ) . ';';
            echo 'var pms_chart_earnings = ' . wp_json_encode( $data_set_earnings_js_array ) . ';';
            echo 'var pms_chart_payments = ' . wp_json_encode( $data_set_payments_js_array ) . ';';

        echo '</script>';

    }

}

global $pms_submenu_page_reports;

$pms_submenu_page_reports = new PMS_Submenu_Page_Reports( 'paid-member-subscriptions', __( 'Reports', 'paid-member-subscriptions' ), __( 'Reports', 'paid-member-subscriptions' ), 'manage_options', 'pms-reports-page', 20 );
$pms_submenu_page_reports->init();
