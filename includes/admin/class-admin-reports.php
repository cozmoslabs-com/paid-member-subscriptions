<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Extends core PMS_Submenu_Page base class to create and add custom functionality
 * for the reports section in the admin section
 *
 */
Class PMS_Submenu_Page_Reports extends PMS_Submenu_Page {

    /**
     * The start date to filter results
     *
     * @var string
     *
     */
    public $start_date;
    public $start_previous_date;


    /**
     * The end date to filter results
     *
     * @var string
     *
     */
    public $end_date;
    public $end_previous_date;

    /**
     * The total of days in a month
     *
     * @var string
     *
     */
    public $month_total_days = 0;


    /**
     * Array of payments retrieved from the database given the user filters
     *
     * @var array
     *
     */
    public $queried_payments = array();
    public $queried_payments_attempts = array();
    public $queried_previous_payments = array();
    public $queried_previous_attempts = array();


    /**
     * Array with the formatted results ready for chart.js usage
     *
     * @var array
     *
     */
    public $results = array();


    /**
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


    /**
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


    /**
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


    /**
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

            } else if( $date === 'this_week'){

                $this->start_date = new DateTime('this week monday');
                $this->month_total_days = $this->start_date->format( 't' );
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('this week sunday');
                $this->end_date = $this->end_date->format('Y-m-d');
                $this->end_date = $this->end_date . ' 23:59:59';

            } else if( $date === 'last_week' ){

                $this->start_date = new DateTime('last week monday');
                $this->month_total_days = $this->start_date->format( 't' );
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last week sunday');
                $this->end_date = $this->end_date->format('Y-m-d');
                $this->end_date = $this->end_date . ' 23:59:59';

            } else if( $date === '30days' ){

                $this->start_date = new DateTime('today - 30 days');
                $this->month_total_days = $this->start_date->format( 't' );
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('today');
                $this->end_date = $this->end_date->format('Y-m-d');
                $this->end_date = $this->end_date . ' 23:59:59';

            } else if( $date === 'this_month' ){

                $this->start_date = new DateTime('first day of this month');
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last day of this month');
                $this->end_date = $this->end_date->format('Y-m-d');
                $this->end_date = $this->end_date . ' 23:59:59';

            } else if( $date === 'last_month' ){

                $this->start_date = new DateTime('first day of last month');
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last day of last month');
                $this->end_date = $this->end_date->format('Y-m-d');
                $this->end_date = $this->end_date . ' 23:59:59';

            } else if ( $date === 'this_year' ){

                $this->start_date = new DateTime('first day of January this year');
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last day of December this year');
                $this->end_date = $this->end_date->format('Y-m-d');
                $this->end_date = $this->end_date . ' 23:59:59';

            } else if ( $date === 'last_year' ){

                $this->start_date = new DateTime('first day of January last year');
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last day of December last year');
                $this->end_date = $this->end_date->format('Y-m-d');
                $this->end_date = $this->end_date . ' 23:59:59';

            } else if( $date === 'custom_date' ){

                if( empty( $_GET['pms-filter-time-start-date'] ) || empty( $_GET['pms-filter-time-end-date'] ) ){
                    $this->start_date = '0000-00-00';
                    $this->end_date = '0000-00-00';
                }

            } else {

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

            } else {

                $this->start_previous_date = DateTime::createFromFormat('Y-m-d', $this->start_date);
                $this->start_previous_date->modify('-1 year');
                $this->start_previous_date = $this->start_previous_date->format('Y-m-d');

            }

            $this->end_previous_date = DateTime::createFromFormat('Y-m-d H:i:s', $this->end_date);
            $this->end_previous_date->modify('-1 year');
            $this->end_previous_date = $this->end_previous_date->format('Y-m-d') . ' 23:59:59';
        }

        $specific_subs = array();
        $payments = array();

        $args_attempts = array(
            'status' => array( 'completed', 'pending', 'failed' ),
            'date'   => array( $this->start_date, $this->end_date ),
            'order'  => 'ASC',
            'number' => '-1'
        );

        $args_previous_attempts = array(
            'status' => array( 'completed', 'pending', 'failed' ),
            'date'   => array( $this->start_previous_date, $this->end_previous_date ),
            'order'  => 'ASC',
            'number' => '-1'
        );

        if( isset( $_REQUEST['pms-filter-subscription-plans'] ) && !empty( $_GET['pms-filter-subscription-plans'] ) ){

            $specific_subs = array_map('absint', $_GET['pms-filter-subscription-plans'] );

            $args_attempts         ['subscription_plan_id'] = $specific_subs;
            $args_previous_attempts['subscription_plan_id'] = $specific_subs;

        }

        $args_attempts          = apply_filters( 'pms_reports_get_filtered_payments_args', $args_attempts );
        $args_previous_attempts = apply_filters( 'pms_reports_get_filtered_payments_args_previous_period', $args_previous_attempts );

        $this->queried_payments_attempts = pms_get_payments( $args_attempts );

        // we get only the completed payments from the same result set
        if( !empty( $this->queried_payments_attempts ) ){
            $payments = array_filter( $this->queried_payments_attempts, array( $this, 'filter_get_completed_payments' ) );
        }

        $this->queried_previous_attempts = pms_get_payments( $args_previous_attempts );

        if( !empty( $this->queried_previous_attempts ) ){
            $this->queried_previous_payments = array_filter( $this->queried_previous_attempts, array( $this, 'filter_get_completed_payments' ) );
        }

        return $payments;

    }


    /**
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


    /**
     * Method to output content in the custom page
     *
     */
    public function output() {
        $active_tab = 'pms-reports-page';
        include_once 'views/view-page-reports.php';

    }


    /**
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

    /**
     * Count any type of payment
     *
     */
    public function pms_counting_type_of_payment( $payment_type, $types_wanted, $count ) {
        if ( in_array( $payment_type, $types_wanted ) )
            $count++;

        return $count;
    }

    /**
     * Gather any type of payment
     *
     */
    public function pms_sum_type_of_payment( $payment_type, $types_wanted, $payment_amount, $total ) {
        if ( in_array( $payment_type, $types_wanted ) )
            $total += $payment_amount;

        return $total;
    }

    /**
     * Gather any type and status of payment
     *
     */
    public function pms_sum_type_and_status_of_payment( $payment_type, $types_wanted, $payment_status, $payment_wanted, $payment_amount, &$total ) {
        if ( in_array( $payment_type, $types_wanted ) && in_array( $payment_status, $payment_wanted ) )
            $total += $payment_amount;

        return $total;
    }

    /**
     * Counting any type and status of payment
     *
     */
    public function pms_count_type_and_status_of_payment( $payment_type, $types_wanted, $payment_status, $payment_wanted, &$count ) {
        if ( in_array( $payment_type, $types_wanted ) && in_array( $payment_status, $payment_wanted ) )
            $count++;

        return $count;
    }

    /**
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


    /**
     *
     * Counting payments with any status but type of 'subscription_renewal_payment'
     */
    public function pms_return_counting_renewal_payments( $queried ){
        $renewal_payments = ['subscription_renewal_payment'];
        $count_renewal_payments = 0;

        if( !empty( $queried ) ) {
            foreach( $queried as $payment ){
                $count_renewal_payments = $this->pms_counting_type_of_payment( $payment->type, $renewal_payments, $count_renewal_payments );
            }
        }

        return $count_renewal_payments;
    }

    /**
     *
     * Counting payments with any status but type of 'subscription_upgrade_payment'
     */
    public function pms_return_counting_upgrade_payments( $queried ){
        $upgrade_payments = ['subscription_upgrade_payment'];
        $count_upgrade_payments = 0;

        if( !empty( $queried ) ) {
            foreach( $queried as $payment ){
                $count_upgrade_payments = $this->pms_counting_type_of_payment( $payment->type, $upgrade_payments, $count_upgrade_payments );
            }
        }

        return $count_upgrade_payments;
    }

    /**
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

    /**
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

    /**
     * Get the Summary data
     *
     * @param $queried_payments
     * @return array
     */
    public function get_summary_data( $queried_payments ){

        $default_currency = pms_get_active_currency();
        $payments_count  = array();
        $payments_amount = array();

        $subscriptions_plans_result = array();

        $completed_payments = ['subscription_initial_payment', 'recurring_payment_profile_created', 'expresscheckout', 'subscription_renewal_payment', 'subscription_upgrade_payment', 'subscription_downgrade_payment'];
        $recurring_payments = ['subscription_recurring_payment', 'recurring_payment', 'subscr_payment'];
        $attempts_payments = ['subscription_retry_payment'];
        $status = ['completed'];
        $total_successful_retries = 0;
        $total_completed_payments = $total_recurring_payments = $total_recovered_payments = array();

        $discount_codes_result = $this->pms_prepare_discount_codes( array(), $queried_payments );
        $payment_gateways_result = $this->pms_prepare_payment_gateway( array() );

        $default_currency_totals = array(
            'payments_amount' => 0,
            'payments_count' => 0,
            'total_completed_payments' => 0,
            'total_recurring_payments' => 0,
            'payment_gateways_result' => $payment_gateways_result,
            'total_recovered_payments' => 0,
        );

        if( isset( $_GET['pms-filter-subscription-plans'] ) && !empty( $_GET['pms-filter-subscription-plans'] ) ){
            $specific_subs = array_map('absint', $_GET['pms-filter-subscription-plans'] );

            foreach ( $specific_subs as $sub_id ){
                if( !isset( $subscriptions_plans_result[$sub_id] ) ) {
                    $subscriptions_plans_result[$sub_id] = array( 'name' => '', 'earnings' => array(), 'count' => array(), 'default_currency_total' => 0 );
                }
            }
        }
        else{
            $specific_subs = pms_get_subscription_plans(false);

            foreach ( $specific_subs as $plan ){
                if( !isset( $subscriptions_plans_result[$plan->id] ) ) {
                    $subscriptions_plans_result[$plan->id] = array( 'name' => '', 'earnings' => array(), 'count' => array(), 'default_currency_total' => 0 );
                }

            }
        }

        if( !empty( $queried_payments ) ) {

            foreach( $queried_payments as $payment ) {
                $currency = !empty( $payment->currency ) ? $payment->currency : $default_currency;
                $base_currency_amount = get_metadata( 'payment', $payment->id, 'base_currency_amount', true );


                // Total Payment Amounts in Default Currency

                if ( $currency === $default_currency ) {
                    $default_currency_payment_amount = $payment->amount;
                }
                elseif ( !empty( $base_currency_amount ) ) {
                    $default_currency_payment_amount = $base_currency_amount;
                }
                else {
                    $default_currency_payment_amount = function_exists( 'pms_convert_currency' ) ? pms_convert_currency( $payment->amount, $currency, $default_currency,  date('Y-m-d', strtotime( $payment->date ) ) ) : $payment->amount;
                }

                $default_currency_totals['payments_amount'] += $default_currency_payment_amount;

                if ( in_array( $payment->type, $completed_payments ) )
                    $default_currency_totals['total_completed_payments'] += $default_currency_payment_amount;

                if ( in_array( $payment->type, $recurring_payments ) )
                    $default_currency_totals['total_recurring_payments'] += $default_currency_payment_amount;

                if ( !empty( $payment->payment_gateway ) )
                    $default_currency_totals['payment_gateways_result'][$payment->payment_gateway]['earnings'] += $default_currency_payment_amount;

                if ( in_array( $payment->type, $attempts_payments ) && in_array( $payment->status, $status ) )
                    $default_currency_totals['total_recovered_payments'] += $default_currency_payment_amount;

                $subscriptions_plans_result[intval( $payment->subscription_id )]['default_currency_total'] += $default_currency_payment_amount;


                // Total Earnings

                if ( isset( $payments_amount[$currency] ) )
                    $payments_amount[$currency] += $payment->amount;
                else $payments_amount[$currency] = $payment->amount;


                // Total Payments

                $default_currency_totals['payments_count']++;

                if ( isset( $payments_count[$currency] ) )
                    $payments_count[$currency]++;
                else $payments_count[$currency] = 1;


                // New Revenue

                $total_completed_payments[$currency] = isset( $total_completed_payments[$currency] ) ? $total_completed_payments[$currency] : 0;
                $total_completed_payments[$currency] = $this->pms_sum_type_of_payment( $payment->type, $completed_payments, $payment->amount,  $total_completed_payments[$currency] );


                // Recurring Revenue

                $total_recurring_payments[$currency] = isset( $total_recurring_payments[$currency] ) ? $total_recurring_payments[$currency] : 0;
                $total_recurring_payments[$currency] = $this->pms_sum_type_of_payment( $payment->type, $recurring_payments, $payment->amount, $total_recurring_payments[$currency] );


                // Payment Gateways Revenue

                if ( !empty( $payment->payment_gateway ) && !is_array( $payment_gateways_result[ $payment->payment_gateway ]['earnings'] ) && $payment_gateways_result[ $payment->payment_gateway ]['earnings'] == 0 )
                    $payment_gateways_result[ $payment->payment_gateway ]['earnings'] = array();

                if( isset( $payment_gateways_result[ $payment->payment_gateway ]['earnings'][ $currency ] ) ){
                    $payment_gateways_result[ $payment->payment_gateway ]['earnings'][ $currency ] += $payment->amount;
                }
                else {
                    $payment_gateways_result[ $payment->payment_gateway ]['earnings'][ $currency ] = $payment->amount;
                }
                // TODO: simplify the above: maybe update the pms_prepare_payment_gateway() function to return "earnings" as empty array instead of 0



                // Payment Retries

                $total_successful_retries            = $this->pms_count_type_and_status_of_payment( $payment->type, $attempts_payments, $payment->status, $status, $total_successful_retries );

                $total_recovered_payments[$currency] = isset( $total_recovered_payments[$currency] ) ? $total_recovered_payments[$currency] : 0;
                $total_recovered_payments[$currency] = $this->pms_sum_type_and_status_of_payment( $payment->type, $attempts_payments, $payment->status, $status, $payment->amount, $total_recovered_payments[$currency] );


                // Subscription Plans

                if ( isset( $subscriptions_plans_result[ intval( $payment->subscription_id ) ]['earnings'][$currency] ) )
                    $subscriptions_plans_result[ intval( $payment->subscription_id ) ]['earnings'][$currency] += $payment->amount;
                else $subscriptions_plans_result[ intval( $payment->subscription_id ) ]['earnings'][$currency] = $payment->amount;

                if ( isset( $subscriptions_plans_result[ intval( $payment->subscription_id ) ]['count'][$currency] ) )
                    $subscriptions_plans_result[ intval( $payment->subscription_id ) ]['count'][$currency]++;
                else $subscriptions_plans_result[ intval( $payment->subscription_id ) ]['count'][$currency] = 1;


                if( empty( $subscriptions_plans_result[ intval( $payment->subscription_id ) ]['name'] ) ){
                    $plan = pms_get_subscription_plan( $payment->subscription_id );
                    $subscriptions_plans_result[ intval( $payment->subscription_id ) ]['name'] = $plan->name;
                }


                // Discount Codes

                $discount_codes_result[ $payment->discount_code ]['count']++;
            }

            // Sort Subscription Plans after best performing earnings (calculated in default currency)
            usort($subscriptions_plans_result, function( $first_plan, $second_plan ) {
                return $second_plan['default_currency_total'] - $first_plan['default_currency_total'];
            });

            // Sort the discount codes after the most used
            usort($discount_codes_result, function( $first_plan, $second_plan ) {
                return $second_plan['count'] - $first_plan['count'];
            });
        }


        $summary_data = array(
            'payments_amount' => $payments_amount,
            'payments_count' => $payments_count,
            'total_completed_payments' => $total_completed_payments,
            'total_recurring_payments' => $total_recurring_payments,
            'subscriptions_plans_result' => $subscriptions_plans_result,
            'payment_gateways_result' => $payment_gateways_result,
            'total_successful_retries' => $total_successful_retries,
            'total_recovered_payments' => $total_recovered_payments,
            'discount_codes_result' => $discount_codes_result,
            'default_currency' => $default_currency,
            'default_currency_totals' => $default_currency_totals
        );

        return $summary_data;
    }


    /**
     * Output Summary data
     *
     * @param $summary_data
     * @param $title
     * @param $results_arrow
     * @param $previous
     * @return void
     */
    public function output_summary_area( $summary_data, $title, $results_arrow, $previous = false ){

        $nav_tabs = array(
            'general' => array(
                'id' => 'pms-general-link',
                'title' => esc_html__( 'General', 'paid-member-subscriptions' ),
                'section_class' => 'pms-general-section'
            ),
            'subscription_plans' => array(
                'id' => 'pms-subscription-plans-link',
                'title' => esc_html__( 'Subscription Plans', 'paid-member-subscriptions' ),
                'section_class' => 'pms-subscription-plans-section'
            ),
            'discount_codes' => array(
                'id' => 'pms-discount-codes-link',
                'title' => esc_html__( 'Discount Codes', 'paid-member-subscriptions' ),
                'section_class' => 'pms-discount-codes-section'
            )
        );


        $nav_tabs = apply_filters( 'pms_reports_summary_tabs', $nav_tabs );
        $class_section = 'present';

        if( $previous ){
            foreach ( $nav_tabs as $tab => $data ) {
                $nav_tabs[$tab]['id'] .= '-previous';
                $nav_tabs[$tab]['section_class'] .= '-previous';
            }

            $class_section = 'previous';

            $summary_data['total_retry_attempts'] = esc_html( $this->pms_return_counting_attempts( $this->queried_previous_attempts ) );
            $summary_data['total_renewal_payments'] = esc_html( $this->pms_return_counting_renewal_payments( $this->queried_previous_attempts ) );
            $summary_data['total_upgrade_payments'] = esc_html( $this->pms_return_counting_upgrade_payments( $this->queried_previous_attempts ) );
        }
        else {
            $summary_data['total_retry_attempts'] = esc_html( $this->pms_return_counting_attempts( $this->queried_payments_attempts ) );
            $summary_data['total_renewal_payments'] = esc_html( $this->pms_return_counting_renewal_payments( $this->queried_payments_attempts ) );
            $summary_data['total_upgrade_payments'] = esc_html( $this->pms_return_counting_upgrade_payments( $this->queried_payments_attempts ) );
        }

        $default_currency = $summary_data['default_currency'];
        $default_currency_totals = $summary_data['default_currency_totals'];


        ?>
        <div class="postbox cozmoslabs-form-subsection-wrapper <?php echo esc_html( $class_section ); ?>">
            <h4 class="cozmoslabs-subsection-title"><?php echo esc_html( $title ); ?></h4>

            <div class="inside">

                <div class="pms-summary-tabs">
                    <?php
                        foreach ( $nav_tabs as $tab ) {
                            echo '<a class="pms-summary-tab" id="'. esc_html( $tab['id'] ) .'">'. esc_html( $tab['title'] ) .'</a>';
                        }
                    ?>
                </div>

                <div class="pms-summary-section <?php echo esc_html( $nav_tabs['general']['section_class'] ); ?>">
                    <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                        <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total earnings for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'Total Earnings', 'paid-member-subscriptions' ); ?></label>

                        <div class="pms-total-container" id="total-earnings-amount">

                            <?php
                            if ( !empty( $default_currency_totals['payments_amount'] ) ) {

                                echo '<div class="pms-currency pms-currency-amount">';
                                    echo '<p style="margin: 0;">' . esc_html( pms_format_price( $default_currency_totals['payments_amount'], $default_currency ) ) . '</p>';

                                    echo '<div class="pms-currency-difference">';

                                        if( !empty( $results_arrow['default_currency_totals']['payments_amount']['present'] ) && !$previous ){ ?>

                                            <img class="pms-arrow" alt="pms-arrow" src="<?php echo esc_html( $results_arrow['default_currency_totals']['payments_amount']['present'] ); ?>">

                                            <span style="
                                                        <?php
                                            if( $results_arrow['default_currency_totals']['payments_amount']['difference'] > 0 )
                                                echo 'color: red';
                                            elseif( $results_arrow['default_currency_totals']['payments_amount']['difference'] < 0 )
                                                echo 'color: green';
                                            ?>">
                                                <?php echo '(' . esc_html( number_format( $results_arrow['default_currency_totals']['payments_amount']['percent'], 2 ) ) . '%)'; ?>
                                            </span>

                                        <?php }

                                    echo '</div>';

                                echo '</div>';

                            }
                            else echo '<p class="pms-currency-amount" style="margin: 0 0 5px 0;">' . esc_html( pms_format_price( 0, $default_currency ) ) . '</p>';
                            ?>

                        </div>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                        <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total number of payments for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'Total Payments', 'paid-member-subscriptions' ); ?></label>

                        <div class="pms-total-container" id="total-payments-count">

                            <?php

                            if ( !empty( $default_currency_totals['payments_count'] ) ) {

                                echo '<div class="pms-currency pms-currency-count">';

                                    echo '<p style="margin: 0;">'. esc_html( $default_currency_totals['payments_count'] ) .'</p>';

                                    echo '<div class="pms-currency-difference">';

                                        if( !empty( $results_arrow['default_currency_totals']['payments_count']['present'] ) && !$previous ){ ?>

                                            <img class="pms-arrow" alt="pms-arrow" src="<?php echo esc_html( $results_arrow['default_currency_totals']['payments_count']['present'] ); ?>">

                                            <span style="
                                            <?php
                                            if( $results_arrow['default_currency_totals']['payments_count']['difference'] > 0 )
                                                echo 'color: red';
                                            elseif( $results_arrow['default_currency_totals']['payments_count']['difference'] < 0 )
                                                echo 'color: green';
                                            ?>">
                                                <?php echo '(' . esc_html( number_format( $results_arrow['default_currency_totals']['payments_count']['percent'], 2 ) ) . '%)'; ?>
                                            </span>

                                        <?php }

                                    echo '</div>';

                                echo '</div>';

                            }
                            else echo '<p class="pms-currency-count" style="margin: 0 0 5px 0;">0</p>';

                            ?>

                        </div>

                    </div>

                    <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                        <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total earnings of completed payments for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'New Revenue', 'paid-member-subscriptions' ); ?></label>

                        <div class="pms-total-container" id="new-revenue-amount">

                            <?php if ( !empty( $default_currency_totals['total_completed_payments'] ) ) : ?>

                                <div class="pms-currency pms-currency-new-revenue">

                                    <p style="margin: 0;" title="<?php

                                    if( $default_currency_totals['payments_amount'] != 0)
                                        $completed_payments_percent = ( 100 * $default_currency_totals['total_completed_payments'] ) / $default_currency_totals['payments_amount'];
                                    else $completed_payments_percent = 0;

                                    echo '(' . esc_html( number_format( $completed_payments_percent, 2 ) ) . '%)';

                                    ?>">
                                        <?php echo esc_html( pms_format_price( $default_currency_totals['total_completed_payments'], $default_currency ) ); ?>
                                    </p>

                                    <div class="pms-currency-difference">

                                        <?php if( !empty( $results_arrow['default_currency_totals']['total_completed_payments']['present'] ) && !$previous ) : ?>

                                            <img class="pms-arrow" alt="pms-arrow" src="<?php echo esc_html( $results_arrow['default_currency_totals']['total_completed_payments']['present'] ); ?>">

                                            <span style="
                                            <?php
                                            if( $results_arrow['default_currency_totals']['total_completed_payments']['difference'] > 0 )
                                                echo 'color: red';
                                            elseif( $results_arrow['default_currency_totals']['total_completed_payments']['difference'] < 0 )
                                                echo 'color: green';
                                            ?>">
                                                <?php echo '(' . esc_html( number_format( $results_arrow['default_currency_totals']['total_completed_payments']['percent'], 2 ) ) . '%)'; ?>
                                            </span>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            <?php else : ?>

                                <p class="pms-currency-new-revenue" style="margin: 0 0 5px 0;" title="<?php echo '(' . esc_html( number_format( 0, 2 ) ) . '%)'; ?>">
                                    <?php echo esc_html( pms_format_price( 0, pms_get_active_currency() ) ); ?>
                                </p>

                            <?php endif; ?>

                        </div>

                    </div>

                    <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                        <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total earnings of recurring payments for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'Recurring Revenue', 'paid-member-subscriptions' ); ?></label>

                        <div class="pms-total-container" id="recurring-revenue-amount">

                            <?php if ( !empty( $default_currency_totals['total_recurring_payments'] ) ) : ?>

                                <div class="pms-currency pms-currency-recurring-revenue">

                                    <p style="margin: 0 0 5px 0;" title="<?php
                                    if( $default_currency_totals['payments_amount'] != 0)
                                        $recurring_payments_percent = ( 100 * $default_currency_totals['total_recurring_payments'] ) / $default_currency_totals['payments_amount'];
                                    else $recurring_payments_percent = 0;

                                    echo '(' . esc_html( number_format( $recurring_payments_percent, 2 ) ) . '%)';

                                    ?>">
                                        <?php echo esc_html( pms_format_price( $default_currency_totals['total_recurring_payments'], $default_currency ) ); ?>
                                    </p>

                                    <div class="pms-currency-difference">

                                        <?php if( !empty( $results_arrow['default_currency_totals']['total_recurring_payments']['present'] ) && !$previous ) : ?>

                                            <img class="pms-arrow" alt="pms-arrow" src="<?php echo esc_html( $results_arrow['default_currency_totals']['total_recurring_payments']['present'] ); ?>">

                                            <span style="
                                                <?php
                                            if( $results_arrow['default_currency_totals']['total_recurring_payments']['difference'] > 0 )
                                                echo 'color: red';
                                            elseif( $results_arrow['default_currency_totals']['total_recurring_payments']['difference'] < 0 )
                                                echo 'color: green';
                                            ?>">
                                                    <?php echo '(' . esc_html( number_format( $results_arrow['default_currency_totals']['total_recurring_payments']['percent'], 2 ) ) . '%)'; ?>
                                                </span>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            <?php else : ?>

                                <p class="pms-currency-recurring-revenue" style="margin: 0 0 5px 0;" title="<?php echo '(' . esc_html( number_format( 0, 2 ) ) . '%)'; ?>">
                                    <?php echo esc_html( pms_format_price( 0, pms_get_active_currency() ) ); ?>
                                </p>

                            <?php endif; ?>

                        </div>

                    </div>

                    <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                        <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'The plan with the most income for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'Best Performing Plan', 'paid-member-subscriptions' ); ?></label>
                        <?php if( !empty( $summary_data['subscriptions_plans_result'] ) && isset( $summary_data['subscriptions_plans_result'][0] ) ){
                            $best_performing_plan = $summary_data['subscriptions_plans_result'][0];
                            ?>

                            <div id="pms-best-performing">
                                <span><?php echo esc_html( $best_performing_plan['name'] ); ?></span>
                                <span><?php echo esc_html( '-' ); ?></span>
                                <span><?php echo esc_html( pms_format_price( $best_performing_plan['default_currency_total'], $default_currency ) ); ?></span>
                            </div>

                        <?php }
                        else{
                            ?>
                            <span><?php echo esc_html__( '-', 'paid-member-subscriptions'); ?></span>
                            <?php
                        }?>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                        <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total number of renewal payments for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'Renewal Payments', 'paid-member-subscriptions' ); ?></label>
                        <span><?php echo esc_html( $summary_data['total_renewal_payments'] ); ?></span>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                        <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total number of upgraded payments for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'Upgraded Payments', 'paid-member-subscriptions' ); ?></label>
                        <span><?php echo esc_html( $summary_data['total_upgrade_payments'] ); ?></span>
                    </div>

                    <div class="cozmoslabs-form-field-wrapper pms-gateway-revenue">
                        <label class="pms-form-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Payment Gateways Revenue', 'paid-member-subscriptions' ); ?></label>
                        <ul>

                            <?php
                            $nr_payment_gateways = 0;
                            if( !empty( $default_currency_totals['payment_gateways_result'] ) ){
                                foreach ( $default_currency_totals['payment_gateways_result'] as $payment_gateway ){

                                    if( $payment_gateway['earnings'] !== 0 ){
                                        $nr_payment_gateways++;
                                        ?>
                                        <li>
                                            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                                                <label class="pms-form-field-label cozmoslabs-form-field-label"><?php echo esc_html( $payment_gateway['name'] ); ?></label>

                                                <div class="payment-gateway-amount">

                                                    <p style="margin: 0 0 5px 0;" title="<?php
                                                    if( $default_currency_totals['payments_amount'] != 0)
                                                        $payment_gateway['percent'] = ( 100 * $payment_gateway['earnings'] ) / $default_currency_totals['payments_amount'];
                                                    else $payment_gateway['percent'] = 0;

                                                    echo '(' . esc_html( number_format( $payment_gateway['percent'], 2 ) ) . '%)';

                                                    ?>">
                                                        <?php echo esc_html( pms_format_price( $payment_gateway['earnings'], $default_currency ) ); ?>
                                                    </p>

                                                </div>


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
                                    <span><?php echo esc_html( $summary_data['total_retry_attempts'] ); ?></span>
                                </div>
                            </li>

                            <li>
                                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                                    <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total number of successful attempts payments for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'Successful Retries', 'paid-member-subscriptions' ); ?></label>
                                    <span><?php echo esc_html( $summary_data['total_successful_retries'] ); ?></span>
                                </div>
                            </li>

                            <li>
                                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                                    <label class="pms-form-field-label cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total number of recovered payments for the selected period', 'paid-member-subscriptions' ); ?>"><?php echo esc_html__( 'Recovered Revenue', 'paid-member-subscriptions' ); ?></label>

                                    <div id="gateway-recovered-amount">

                                        <?php if( !empty( $default_currency_totals['total_recovered_payments'] ) ) : ?>

                                            <p style="margin: 0 0 5px 0;"><?php echo esc_html( pms_format_price( $default_currency_totals['total_recovered_payments'], $default_currency ) ); ?></p>

                                        <?php else : ?>

                                            <p style="margin: 0 0 5px 0;"><?php echo esc_html( pms_format_price( 0, $default_currency ) ); ?></p>

                                        <?php endif; ?>


                                    </div>

                                </div>
                            </li>
                        </ul>
                    </div>
                <?php } ?>
                </div>

                <div class="pms-summary-section  <?php echo esc_html( $nav_tabs['subscription_plans']['section_class'] ); ?>" id="pms_subscription_plans_section">
                    <div class="pms-subscription-plans-header">
                        <label class="pms-form-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Subscription Plan', 'paid-member-subscriptions' ); ?></label>
                        <label class="pms-form-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Earnings', 'paid-member-subscriptions' ); ?></label>
                        <label class="pms-form-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Payments', 'paid-member-subscriptions' ); ?></label>
                    </div>
                    <?php
                    $nr_plans = 0;
                    foreach ( $summary_data['subscriptions_plans_result'] as $plan ){

                        if( !empty( $plan['earnings'] ) ){
                            $nr_plans++; ?>
                            <div class="cozmoslabs-form-field-wrapper pms-plans-section cozmoslabs-toggle-switch">
                                <label class="cozmoslabs-form-field-label" title="<?php echo esc_html__( 'Total earnings for the selected subscription plan', 'paid-member-subscriptions' ); ?>"><?php echo esc_html( $plan['name'] ); ?></label>

                                <div class="cozmoslabs-form-field-label pms-subscription-plan-amount" style="display: flex; flex-direction: column; gap: 5px;">
                                    <span><?php echo esc_html( pms_format_price( $plan['default_currency_total'], $default_currency ) ); ?></span>
                                </div>

                                <div class="cozmoslabs-form-field-label pms-subscription-plan-count" style="display: flex; flex-direction: column; gap: 5px;">

                                    <?php
                                    $total_count = 0;
                                    foreach ( $plan['count'] as $currency => $count ) {
                                        $total_count += $count;
                                    }
                                    ?>

                                    <span class="cozmoslabs-form-field-label pms-normal-font" ><?php echo esc_html( $total_count ); ?></span>

                                </div>
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

                <div class="pms-summary-section <?php echo esc_html( $nav_tabs['discount_codes']['section_class'] ); ?>" id="pms_discount_codes_section">
                    <div class="pms-discount-codes-header">
                        <label class="pms-form-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Discount Code', 'paid-member-subscriptions' ); ?></label>
                        <label class="pms-form-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Uses', 'paid-member-subscriptions' ); ?></label>
                    </div>
                    <?php
                    $nr_discounts = 0;
                    if( !empty( $summary_data['discount_codes_result'] ) ){
                        foreach ( $summary_data['discount_codes_result'] as $discount_code ){
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

                <?php do_action( 'pms_reports_summary_sections_bottom', $summary_data, $results_arrow, $previous ); ?>


            </div>
        </div>
        <?php
    }

    /**
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

        $results_arrow = array();
        $data_types = array( 'payments_amount', 'payments_count', 'total_completed_payments', 'total_recurring_payments' );


        // payment info per currency

        foreach ( $summary_data as $type => $data ) {

            if ( !in_array( $type, $data_types ) )
                continue;

            foreach ( $data as $currency => $amount ) {
                $previous_amount = !empty( $summary_previous_data[$type][$currency] ) ? $summary_previous_data[$type][$currency] : 0;
                $results_arrow[$currency][$type]['difference'] = $previous_amount - $amount;

                if( $previous_amount != 0 )
                    $results_arrow[$currency][$type]['percent'] = ( abs( $results_arrow[$currency][$type]['difference'] ) * 100 ) / $previous_amount;
                else $results_arrow[$currency][$type]['percent'] = 100;

                if( $amount > $previous_amount ){
                    $results_arrow[$currency][$type]['present'] = $arrows['up'];
                    $results_arrow[$currency][$type]['previous'] = $arrows['down'];
                }
                elseif( $amount < $previous_amount ){
                    $results_arrow[$currency][$type]['present'] = $arrows['down'];
                    $results_arrow[$currency][$type]['previous'] = $arrows['up'];
                }

            }

            // payment info for default currency totals

            $total_amount = $summary_data['default_currency_totals'][$type];
            $previous_total_amount = $summary_previous_data['default_currency_totals'][$type];

            $results_arrow['default_currency_totals'][$type]['difference'] = $previous_total_amount - $total_amount;

            if( $previous_total_amount != 0 )
                $results_arrow['default_currency_totals'][$type]['percent'] = ( abs( $results_arrow['default_currency_totals'][$type]['difference'] ) * 100 ) / $previous_total_amount;
            else $results_arrow['default_currency_totals'][$type]['percent'] = 100;

            if( $total_amount > $previous_total_amount ){
                $results_arrow['default_currency_totals'][$type]['present'] = $arrows['up'];
                $results_arrow['default_currency_totals'][$type]['previous'] = $arrows['down'];
            }
            elseif( $total_amount < $previous_total_amount ){
                $results_arrow['default_currency_totals'][$type]['present'] = $arrows['down'];
                $results_arrow['default_currency_totals'][$type]['previous'] = $arrows['up'];
            }

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

    /**
     * Output the javascript data as variables
     *
     */
    public function output_chart_js_data() {

        if( empty( $this->results ) )
            return;

        $results = $this->results;
        $default_currency = pms_get_active_currency();

        // Generate chart labels
        $chart_labels_js_array = $pms_chart_data = array();

        foreach( $results as $key => $details ) {

            $chart_labels_js_array[] = $key;
            $pms_chart_data[$default_currency]['earnings'][$key] = $details['earnings'];
            $pms_chart_data[$default_currency]['payments'][$key] = $details['payments'];

        }

        // Start ouput
        echo '<script type="text/javascript">';

        if ( isset( $_GET['pms-filter-time'] ) && ( $_GET['pms-filter-time'] === 'today' || $_GET['pms-filter-time'] === 'yesterday' ) )
            echo 'var pms_chart_labels = ' . wp_json_encode( $chart_labels_js_array ) . ';';

        echo 'var pms_chart_data = ' . wp_json_encode( $pms_chart_data ) . ';';
        echo 'var pms_default_currency = ' . wp_json_encode( $default_currency ) . ';';
        echo 'var pms_default_currency_symbol = "' . esc_html( html_entity_decode( pms_get_currency_symbol( $default_currency ) ) ) . '";';

        echo '</script>';

    }

    public function filter_get_completed_payments( $item ){

        if( $item->status == 'completed' )
            return true;

        return false;

    }

}

function pms_init_reports_page() {

    global $pms_submenu_page_reports;

    $pms_submenu_page_reports = new PMS_Submenu_Page_Reports( 'paid-member-subscriptions', __( 'Reports', 'paid-member-subscriptions' ), __( 'Reports', 'paid-member-subscriptions' ), 'manage_options', 'pms-reports-page', 20 );
    $pms_submenu_page_reports->init();

}
add_action( 'init', 'pms_init_reports_page', 9 );
