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


    /*
     * The end date to filter results
     *
     * @var string
     *
     */
    public $end_date;

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
     * Return an array of payments payments depending on the user's input filters
     *
     * @return array
     *
     */
    private function get_filtered_payments() {

        if( isset( $_REQUEST['pms-filter-time'] ) && $_REQUEST['pms-filter-time'] == 'custom_date' && !empty( $_REQUEST['pms-filter-time-start-date'] ) && !empty( $_REQUEST['pms-filter-time-end-date'] ) ){

            $this->start_date = sanitize_text_field( $_REQUEST['pms-filter-time-start-date'] );
            $this->end_date   = sanitize_text_field( $_REQUEST['pms-filter-time-end-date'] ) . ' 23:59:59';

        } else {

            if( empty( $_REQUEST['pms-filter-time'] ) || $_REQUEST['pms-filter-time'] == 'today' )
                $date = date("Y-m-d");
            else
                $date = sanitize_text_field( $_REQUEST['pms-filter-time'] );

            if( $date === 'today' || $date === 'yesterday'){

                $date = new DateTime( $date );
                $date = $date->format('Y-m-d');
                $this->start_date = $date . ' 00:00:00';
                $this->end_date   = $date . ' 23:59:59';

            }
            else if( $date === 'this_week'){

                $this->start_date = new DateTime('this week monday');
                $this->month_total_days = $this->start_date->format( 't' );
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('this week sunday');
                $this->end_date = $this->end_date->format('Y-m-d');
            }
            else if( $date === 'last_week' ){

                $this->start_date = new DateTime('last week monday');
                $this->month_total_days = $this->start_date->format( 't' );
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last week sunday');
                $this->end_date = $this->end_date->format('Y-m-d');

            }
            else if( $date === '30days' ){

                $this->start_date = new DateTime('today - 30 days');
                $this->month_total_days = $this->start_date->format( 't' );
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('today');
                $this->end_date = $this->end_date->format('Y-m-d');
            }
            else if( $date === 'this_month' ){

                $this->start_date = new DateTime('first day of this month');
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last day of this month');
                $this->end_date = $this->end_date->format('Y-m-d');
            }
            else if( $date === 'last_month' ){

                $this->start_date = new DateTime('first day of last month');
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last day of last month');
                $this->end_date = $this->end_date->format('Y-m-d');
            }
            else if ( $date === 'this_year' ){

                $this->start_date = new DateTime('first day of January this year');
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last day of December this year');
                $this->end_date = $this->end_date->format('Y-m-d');
            }
            else if ( $date === 'last_year' ){

                $this->start_date = new DateTime('first day of January last year');
                $this->start_date = $this->start_date->format('Y-m-d');

                $this->end_date = new DateTime('last day of December last year');
                $this->end_date = $this->end_date->format('Y-m-d');
            }
            else if( $date === 'custom_date' ){

                if( empty( $_GET['pms-filter-time-start-date'] ) || empty( $_GET['pms-filter-time-end-date'] ) )
                {
                    $this->start_date = '0000-00-00';
                    $this->end_date = '0000-00-00';
                }
            }
            else{

                $date = new DateTime( $date );
                $date = $date->format('Y-m-d');
                $this->start_date = $date . ' 00:00:00';
                $this->end_date   = $date . ' 23:59:59';
            }

        }

        $specific_subs = array();

        if( isset( $_REQUEST['pms-filter-subscription-plans'] ) && !empty( $_GET['pms-filter-subscription-plans'] ) ){
            $specific_subs = array_map('absint', $_GET['pms-filter-subscription-plans'] );
            $args = apply_filters( 'pms_reports_get_filtered_payments_args', array( 'status' => 'completed', 'date' => array( $this->start_date, $this->end_date ), 'order' => 'ASC', 'number' => '-1', 'subscription_plan_id' => $specific_subs ) );
        }
        else
        {
            $args = apply_filters( 'pms_reports_get_filtered_payments_args', array( 'status' => 'completed', 'date' => array( $this->start_date, $this->end_date ), 'order' => 'ASC', 'number' => '-1' ) );
        }

        $payments = pms_get_payments( $args );

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
            $first_hour = new DateTime( $this->start_date );
            $first_hour = $first_hour->format('G');

            $last_hour = new DateTime( $this->end_date );
            $last_hour = $last_hour->format('G');

            for( $i = $first_hour; $i <= $last_hour; $i++ ) {
                if( !isset( $results[$i] ) )
                    $results[$i] = array( 'earnings' => 0, 'payments' => 0 );
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
                    $results[ $payment_date->format('G') ]['earnings'] += $payment->amount;
                    $results[ $payment_date->format('G') ]['payments'] += 1;
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
            <div class="pms-container-date-range">
                    <label class="cozmoslabs-form-field-label" for="pms-reports-filter-month"><?php esc_html_e( 'Interval', 'paid-member-subscriptions' ) ?></label>
                <?php

                echo '<select name="pms-filter-time" id="pms-reports-filter-month">';

                      echo '<option value="today"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( 'today', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('Today', 'paid-member-subscriptions') . '</option>';
                      echo '<option value="yesterday"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( 'yesterday', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('Yesterday', 'paid-member-subscriptions') . '</option>';
                      echo '<option value="this_week"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( 'this_week', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('This Week', 'paid-member-subscriptions') . '</option>';
                      echo '<option value="last_week"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( 'last_week', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('Last Week', 'paid-member-subscriptions') . '</option>';
                      echo '<option value="30days"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( '30days', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('Last 30 days', 'paid-member-subscriptions') . '</option>';
                      echo '<option value="this_month"' . ( !empty( $_GET['pms-filter-time'] ) ? selected( 'this_month', sanitize_text_field( $_GET['pms-filter-time'] ), false ) : '' ) . '>' . esc_html__('This Month', 'paid-member-subscriptions') . '</option>';
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

        <div class="cozmoslabs-form-field-wrapper" id="pms-container-specific-subs" style="margin-top: 0 !important; margin-bottom: 20px;">
            <label class="cozmoslabs-form-field-label" for="specific-subscriptions"><?php esc_html_e( 'Select Subscription Plans', 'paid-member-subscriptions' ) ?></label>

            <select id="specific-subscriptions" class="pms-chosen" name="pms-filter-subscription-plans[]" multiple style="width:200px" data-placeholder="<?php echo esc_attr__( 'All', 'paid-member-subscriptions' ); ?>">
                <?php
                $subscription_plans = pms_get_subscription_plans();
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
     * Outputs a summary with the payments and earnings for the selected period
     *
     */
    public function output_reports_table() {

        $payments_count  = count( $this->queried_payments );
        $payments_amount = 0;

        if( !empty( $this->queried_payments ) ) {
            foreach( $this->queried_payments as $payment )
                $payments_amount += $payment->amount;
        }

        echo '<div class="postbox cozmoslabs-form-subsection-wrapper">';
        echo '<h4 class="cozmoslabs-subsection-title">' . esc_html__( 'Summary', 'paid-member-subscriptions' ) . '</h4>';
            echo '<div class="inside">';

                echo '<div class="cozmoslabs-form-field-wrapper">';
                    echo '<label class="pms-form-field-label cozmoslabs-form-field-label" for="pms-reports-total-earnings" title="' . esc_html__( 'Total earnings for the selected period', 'paid-member-subscriptions' ) . '">' . esc_html__( 'Total Earnings', 'paid-member-subscriptions' ) . '</label>';
                    echo '<span>' . esc_html( pms_format_price( $payments_amount, pms_get_active_currency() ) ) . '</span>';
                echo '</div>';

                echo '<div class="cozmoslabs-form-field-wrapper">';
                    echo '<label class="pms-form-field-label cozmoslabs-form-field-label" for="pms-reports-total-payments" title="' . esc_html__( 'Total number of payments for the selected period', 'paid-member-subscriptions' ) . '">' . esc_html__( 'Total Payments', 'paid-member-subscriptions' ) . '</label>';
                    echo '<span>' . esc_html( $payments_count ) . '</span>';
                echo '</div>';

            echo '</div>';
        echo '</div>';

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
