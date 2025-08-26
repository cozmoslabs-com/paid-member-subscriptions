<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically in the plugins section
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/*
 * Extent WP default list table for our custom payments section
 *
 */
Class PMS_Payments_List_Table extends WP_List_Table {

    /**
     * Payments per page
     *
     * @access public
     * @var int
     */
    public $items_per_page;

    /**
     * Payments table data
     *
     * @access public
     * @var array
     */
    public $data;

    /**
     * Payments table views count
     *
     * @access public
     * @var array
     */
    public $views_count = array();

    /**
     * The total number of items
     *
     * @access private
     * @var int
     *
     */
    private $total_items;

    /**
     * Constructor function
     *
     */
    public function __construct() {

        $screen = get_current_screen();

        parent::__construct( array(
            'singular'  => 'payment',
            'plural'    => 'payments',
            'ajax'      => false,
            'screen'    => $screen->id,
        ));

        add_filter( 'manage_' . $screen->id . ' _columns' , array( $this , 'manage_columns' ) );

        // Register custom bulk actions
        add_filter( 'bulk_actions-' . $screen->id, array( $this, 'register_bulk_actions' ) );

        //Set items per page
        $items_per_page = get_user_meta( get_current_user_id(), 'pms_payments_per_page', true );

        if( empty( $items_per_page ) )
            $items_per_page = 10;

        $this->items_per_page = $items_per_page;

        //Set table data
        $this->set_table_data();

    }

    public function manage_columns() {

        $this->get_column_info();

        return $this->_column_headers[0];

    }

    /*
     * Register custom bulk actions
     *
     * @param array $actions actions.
     *
     * @return array
     *
     */
    function register_bulk_actions( $actions ) {

        if( !empty( $_GET['page'] == 'pms-payments-page' ) && empty( $_GET['subpage'] ) )
            $actions['pms_bulk_delete_payments'] = esc_html__( 'Delete Payments', 'paid-member-subscriptions' );

        return apply_filters( 'pms_payments_list_table_register_bulk_actions', $actions );

    }

    /**
     * Overwrites the parent class.
     * Define the columns for the payments
     *
     * @return array
     *
     */
    public function get_columns() {

        $columns = array(
            'cb'             => '<input type="checkbox" />',
            'id'             => esc_html__( 'ID', 'paid-member-subscriptions' ),
            'username'       => esc_html__( 'User', 'paid-member-subscriptions' ),
            'subscriptions'  => esc_html__( 'Subscription', 'paid-member-subscriptions' ),
            'amount'         => esc_html__( 'Amount', 'paid-member-subscriptions' ),
            'date'           => esc_html__( 'Date / Time', 'paid-member-subscriptions' ),
            'type'           => esc_html__( 'Type', 'paid-member-subscriptions' ),
            'transaction_id' => esc_html__( 'Transaction ID', 'paid-member-subscriptions' ),
            'status'         => esc_html__( 'Status', 'paid-member-subscriptions' ),
        );

        return apply_filters( 'pms_payments_list_table_columns', $columns );

    }


    /**
     * Overwrites the parent class.
     * Define which columns are sortable
     *
     * @return array
     *
     */
    public function get_sortable_columns() {

        return array(
            'id'             => array( 'id', false ),
            'date'           => array( 'date', false ),
            'subscriptions'  => array( 'subscription_plan_id', false ),
            'type'           => array( 'type', false ),
            'status'         => array( 'status', false ),
        );

    }


    /**
     * Returns the possible views for the members list table
     *
     */
    protected function get_views() {

        return apply_filters( 'pms_payments_list_table_get_views', array(
            'all'       => '<a href="' . esc_url( remove_query_arg( array( 'pms-view', 'paged' ) ) ) . '" ' . ( !isset( $_GET['pms-view'] ) ? 'class="current"' : '' ) . '>All <span class="count">(' . ( isset( $this->views_count['all'] ) ? $this->views_count['all'] : '' ) . ')</span></a>',
            'completed' => '<a href="' . esc_url( add_query_arg( array( 'pms-view' => 'completed', 'paged' => 1 ) ) ) . '" ' . ( isset( $_GET['pms-view'] ) && $_GET['pms-view'] == 'completed' ? 'class="current"' : '' ) . '>Completed <span class="count">(' . ( isset( $this->views_count['completed'] ) ? $this->views_count['completed'] : '' ) . ')</span></a>',
            'pending'   => '<a href="' . esc_url( add_query_arg( array( 'pms-view' => 'pending', 'paged' => 1 ) ) ) . '" ' . ( isset( $_GET['pms-view'] ) && $_GET['pms-view'] == 'pending' ? 'class="current"' : '' ) . '>Pending <span class="count">(' . ( isset( $this->views_count['pending'] ) ? $this->views_count['pending'] : '' ) . ')</span></a>',
            'failed'    => '<a href="' . esc_url( add_query_arg( array( 'pms-view' => 'failed', 'paged' => 1 ) ) ) . '" ' . ( isset( $_GET['pms-view'] ) && $_GET['pms-view'] == 'failed' ? 'class="current"' : '' ) . '>Failed <span class="count">(' . ( isset( $this->views_count['failed'] ) ? $this->views_count['failed'] : '' ) . ')</span></a>',
            'refunded'  => '<a href="' . esc_url( add_query_arg( array( 'pms-view' => 'refunded', 'paged' => 1 ) ) ) . '" ' . ( isset( $_GET['pms-view'] ) && $_GET['pms-view'] == 'refunded' ? 'class="current"' : '' ) . '>Refunded <span class="count">(' . ( isset( $this->views_count['refunded'] ) ? $this->views_count['refunded'] : '' ) . ')</span></a>'
        ));

    }

    /*
     * Overwrite parent display tablenav to avoid WP's default nonce for bulk actions
     *
     * @param string @which     - which side of the table ( top or bottom )
     *
     */
    protected function display_tablenav( $which ) {

        echo '<div class="tablenav ' . esc_attr( $which ) . '">';

        if ( !empty( $_GET['pms-view'] ) )
            echo '<input type="hidden" id="pms-view" name="pms-view" value="'. esc_attr( sanitize_text_field( $_GET['pms-view'] )) .'">';

        $this->bulk_actions( $which );
        wp_nonce_field( 'pms_payment_nonce' );

        $this->extra_tablenav( $which );
        if ( $which == 'bottom' )
            $this->pagination( $which );

        echo '<br class="clear" />';
        echo '</div>';

    }

    /**
     * Sets the table data
     *
     * @return array
     *
     */
    public function set_table_data() {

        $data = array();
        $args = array();

        $selected_view = ( isset( $_GET['pms-view'] ) ? sanitize_text_field( $_GET['pms-view'] ) : '' );
        $paged         = ( isset( $_GET['paged'] )    ? (int)$_GET['paged'] : 1 );

        /**
         * Set payments arguments
         *
         */
        $args['number'] = $this->items_per_page;
        $args['offset'] = ( $paged - 1 ) * $this->items_per_page;
        $args['status'] = $selected_view;

        // Search query
        if ( !empty($_REQUEST['s']) ) {
            $args['search'] = sanitize_text_field( $_REQUEST['s'] );
        }

        // Order by query
        if( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {

            $orderby = sanitize_text_field( $_REQUEST['orderby'] );

            $orderby_possibilities = array( 'id', 'status', 'date', 'subscription_plan_id', 'type' );

            if( in_array( $orderby, $orderby_possibilities ) )
                $args['orderby'] = $orderby;

            $order = strtolower( sanitize_text_field( $_REQUEST['order'] ) );

            if( $order == 'asc' )
                $args['order'] = 'ASC';
            elseif( $order == 'desc' )
                $args['order'] = 'DESC';

        }

        // Set subscription plan if it exists
        if( ! empty( $_GET['pms-filter-subscription-plan'] ) ) {
            $args['subscription_plan_id'] = (int)$_GET['pms-filter-subscription-plan'];
        }

        if( ! empty( $_GET['pms-filter-payment-type'] ) ) {
            $args['type'] = sanitize_text_field( $_GET['pms-filter-payment-type'] );
        }

        if( !empty( $_GET['pms-filter-payment-gateway'] ) ){
            $args['payment_gateway'] = sanitize_text_field( $_GET['pms-filter-payment-gateway'] );
        }

        if( !empty( $_GET['pms-filter-date'] ) ){
            if( $_GET['pms-filter-date'] != 'custom' ){
                
                if( $_GET['pms-filter-date'] == 'today' ) {
                    $args['date'][0] = date( 'Y-m-d 00:00:00', strtotime( 'today' ) );
                    $args['date'][1] = date( 'Y-m-d 23:59:59', strtotime( 'today' ) );
                
                } else if( $_GET['pms-filter-date'] == 'this_month' ) {
                    $args['date'][0] = date( 'Y-m-d 00:00:00', strtotime( 'first day of this month' ) );
                    $args['date'][1] = date( 'Y-m-d 23:59:59', strtotime( 'last day of this month' ) );
                
                } else if( $_GET['pms-filter-date'] == 'this_year' ) {
                    $args['date'][0] = date( 'Y-m-d 00:00:00', strtotime( 'first day of January' ) );
                    $args['date'][1] = date( 'Y-m-d 23:59:59', strtotime( 'last day of December' ) );
                
                } else if( $_GET['pms-filter-date'] == 'last_week' ) {
                    $args['date'][0] = date( 'Y-m-d 23:59:59', strtotime( 'today - 1 week' ) );
                    $args['date'][1] = date( 'Y-m-d 23:59:59', strtotime( 'today' ) );
                
                } else if( $_GET['pms-filter-date'] == 'last_month' ) {

                    $args['date'][0] = date( 'Y-m-d 23:59:59', strtotime( 'today - 1 month' ) );
                    $args['date'][1] = date( 'Y-m-d 23:59:59', strtotime( 'today' ) );
                
                } else if( $_GET['pms-filter-date'] == 'last_year' ) {
                    $args['date'][0] = date( 'Y-m-d 00:00:00', strtotime( 'first day of last year' ) );
                    $args['date'][1] = date( 'Y-m-d 23:59:59', strtotime( 'last day of December last year' ) );
                }

            } else {
                if( !empty( $_GET[ 'pms-datepicker-date-start' ] ) ){
                    $args['date'][0] = date( 'Y-m-d 00:00:00', strtotime( sanitize_text_field( $_GET[ 'pms-datepicker-date-start' ] ) ) );

                    if( empty( $_GET[ 'pms-datepicker-date-end' ] ) ){
                        $args['date'][1] = date( 'Y-m-d 23:59:59', strtotime( sanitize_text_field( $_GET[ 'pms-datepicker-date-start' ] ) ) );
                    }

                }
                
                if( !empty( $_GET[ 'pms-datepicker-date-end' ] ) )
                    $args['date'][1] = date( 'Y-m-d 23:59:59', strtotime( sanitize_text_field( $_GET[ 'pms-datepicker-date-end' ] ) ) );
                
            }
        }

        /**
         * Get payments
         *
         */
        $payments = pms_get_payments( $args );

        /**
         * Get payment gateways data
         *
         */
        $payment_gateways = pms_get_payment_gateways();


        /**
         * Set views count for each view ( a.k.a payment status )
         *
         */
        $views = $this->get_views();

        foreach( $views as $view_slug => $view_link) {

            $args['status'] = ( $view_slug != 'all' ? $view_slug : '' );

            $this->views_count[$view_slug] = pms_get_payments_count( $args );

        }


        /**
         * Set data array
         *
         */
        foreach( $payments as $payment ) {

            if( !empty($selected_view) && $payment->status != $selected_view )
                continue;

            // Get user data
            $user = get_user_by( 'id', $payment->user_id );

            if( $user )
                $username = $user->data->user_login;
            else
                $username = esc_html__( 'User no longer exists', 'paid-member-subscriptions' );

            // Get payment gateway data
            if( ! empty( $payment_gateways[$payment->payment_gateway]['display_name_admin'] ) )
                $payment_gateway_name = $payment_gateways[$payment->payment_gateway]['display_name_admin'];
            else
                $payment_gateway_name = '';


            $data[] = apply_filters( 'pms_payments_list_table_entry_data', array(
                'id'              => $payment->id,
                'username'        => $username,
                'subscription'    => $payment->subscription_id,
                'amount'          => $payment->amount,
                'date'            => apply_filters('pms_match_date_format_to_wp_settings', ucfirst( date_i18n( 'F d, Y H:i:s', strtotime( $payment->date ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ), true, $payment->date),
                'type'            => pms_get_payment_type_name( $payment->type ),
                'payment_gateway' => $payment_gateway_name,
                'transaction_id'  => $payment->transaction_id,
                'status'          => $payment->status,
                'discount_code'   => $payment->discount_code
            ), $payment );
        }


        /**
         * Set all items
         *
         */
        $this->total_items = $this->views_count[ ( !empty( $selected_view ) ? $selected_view : 'all' ) ];


        /**
         * Set table data
         *
         */
        $this->data = $data;

    }


    /**
     * Populates the items for the table
     *
     * @param array $item           - data for the current row
     *
     * @return string
     *
     */
    public function prepare_items() {

        $this->_column_headers = $this->get_column_info();

        $this->set_pagination_args( array(
            'total_items' => $this->total_items,
            'per_page'    => $this->items_per_page
        ));

        $this->items = $this->data;

    }


    /**
     * Return data that will be displayed in each column
     *
     * @param array $item           - data for the current row
     * @param string $column_name   - name of the current column
     *
     * @return string
     *
     */
    public function column_default( $item, $column_name ) {

        return !empty( $item[ $column_name ] ) ? $item[ $column_name ] : '-';

    }


    /**
     * Handles the checkbox column output.
     *
     * @since 4.3.0
     *
     * @param $item The current item.
     */
    function column_cb( $item ) {
        if( isset( $item[ 'id' ] ) && !empty( $item[ 'id' ] ) ){
            $payment_id = $item[ 'id' ];
            ?>
            <label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $payment_id ); ?>"></label>
            <input type="checkbox" name="payments[]" id="cb-select-<?php echo esc_attr( $payment_id ); ?>" value="<?php echo esc_attr( $payment_id ); ?>" />
            <?php
        }
    }


    /**
     * Return data that will be displayed in the username column
     *
     * @param array $item   - data of the current row
     *
     * @return string
     *
     */
    public function column_username( $item ) {

        $payment = pms_get_payment( $item['id'] );

        // Add row actions
        $actions = array();

        // Edit payment row action
        $actions['edit'] = '<a href="' . esc_url( add_query_arg( array( 'pms-action' => 'edit_payment', 'payment_id' => $item['id'] ), admin_url( 'admin.php?page=pms-payments-page' ) ) ) . '">' . esc_html__( 'Edit Payment', 'paid-member-subscriptions' ) . '</a>';

        if ( ( current_user_can( 'manage_options' ) || current_user_can( 'pms_edit_capability' ) ) && $item['status'] === 'completed' && !empty( $payment->payment_gateway ) && pms_payment_gateways_support( array( $payment->payment_gateway ), 'refunds' ) )
            $actions['refund'] = '<a href="#" class="pms-refund-payment" data-payment-id="'. $item['id'] .'" >' . esc_html__( 'Refund', 'paid-member-subscriptions' ) . '</a>';

        // Delete row action
        $actions['delete'] = '<a onclick="return confirm( \'' . esc_html__( "Are you sure you want to delete this Payment?", "paid-member-subscriptions" ) . ' \' )" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'delete_payment', 'payment_id' => $item['id'] ) ), 'pms_payment_nonce' ) ) . '">' . esc_html__( 'Delete', 'paid-member-subscriptions' ) . '</a>';

        /**
         * Filter the actions for a payment
         *
         * @param array $actions
         * @param array $item
         *
         */
        $actions = apply_filters( 'pms_payments_list_table_entry_actions', $actions, $item );

        $output  = '<a href="' . esc_url( add_query_arg( array( 'page' => 'pms-members-page', 'pms-action' => 'edit_member', 'member_id' => $payment->user_id, 'subpage' => 'edit_member' ), admin_url( 'admin.php' ) ) ) . '" title="' . __( 'Edit Member', 'paid-member-subscriptions' ) . '">' . $item['username'] . '</a>';
        $output .= $this->row_actions( $actions );

        return $output;

    }


    /**
     * Return data that will be displayed in the subscriptions column
     *
     * @param array $item   - data of the current row
     *
     * @return string
     *
     */
    public function column_subscriptions( $item ) {

        $subscription_plan = pms_get_subscription_plan( $item['subscription'] );
        $payment           = pms_get_payment( $item['id'] );

        $url = add_query_arg( array( 'page' => 'pms-members-page', 'pms-action' => 'edit_member', 'member_id' => $payment->user_id, 'subpage' => 'edit_member' ), admin_url( 'admin.php' ) );

        if( !empty( $payment->member_subscription_id ) )
            $url = add_query_arg( array( 'page' => 'pms-members-page', 'pms-action' => 'edit_member', 'subpage' => 'edit_subscription', 'subscription_id' => $payment->member_subscription_id ), admin_url( 'admin.php' ) );

        $output = '<a href="' . esc_url( $url ) . '" class="pms-payment-list-subscription" title="' . ( !empty( $payment->member_subscription_id ) ? esc_html__( 'Edit Subscription', 'paid-member-subscriptions' ) : esc_html__( 'Edit Member', 'paid-member-subscriptions' ) ) . '">' . esc_html( $subscription_plan->name ) . '</a>';

        return $output;

    }


    /**
     * Return data that will be displayed in the status column
     *
     * @param array $item   - data of the current row
     *
     * @return string
     *
     */
    public function column_status( $item ) {

        $payment_statuses = pms_get_payment_statuses();

        $output = apply_filters( 'pms_list_table_' . $this->_args['plural'] . '_show_status_dot', '<span class="pms-status-dot ' . esc_attr( $item['status'] ) . '" data-payment-id="'. $item['id'] .'"></span>' );

        $output .= ( isset( $payment_statuses[ $item['status'] ] ) ? esc_html( $payment_statuses[ $item['status'] ] ) : $item['status'] );

        if( $item['status'] == 'failed' ){
            $output .= ' | <a href="' . esc_url( add_query_arg( array( 'pms-action' => 'edit_payment', 'payment_id' => $item['id'] ) ) ) . '">' . esc_html__( 'View Logs', 'paid-member-subscriptions' ) . '</a>';
        }

        return $output;

    }


    /**
     * Return data that will be displayed in the amount column
     *
     * @param array $item   - data of the current row
     *
     * @return string
     *
     */
    public function column_amount( $item ) {

        $payment = pms_get_payment( $item['id'] );
        $currency = !empty( $payment->currency ) ? $payment->currency : pms_get_active_currency();

        // Check if discount code was used for this payment
        if ( !empty($item['discount_code']) ) {
            $output = '<span class="pms-has-bubble">';

            $output .= pms_format_price( $item['amount'], $currency ) . '<span class="pms-discount-dot"> % </span>';

            $output .= '<div class="pms-bubble">';
                $output .= '<div><span class="alignleft">' . esc_html__('Discount code', 'paid-member-subscriptions') . '</span><span class="alignright">' . esc_html( $item['discount_code'] ) . '</span></div>';
            $output .= '</div>';

            $output .= '</span>';
        } else
            $output = pms_format_price( $item['amount'], $currency );

        return apply_filters( 'pms_payments_list_table_column_amount', $output, $item );

    }


    /**
     * Return data that will be displayed in the type column
     *
     * @param array $item   - data of the current row
     *
     * @return string
     *
     */
    public function column_type( $item ) {

        $output = $item['type'];

        if( ! empty( $item['payment_gateway'] ) )
            $output .= ' (' . esc_html( $item['payment_gateway'] ) . ')';

        return $output;

    }

    /**
     * Return data that will be displayed in the transaction_id column
     *
     * @param array $item   - data of the current row
     *
     * @return string
     *
     */
    public function column_transaction_id( $item ) {

        if( empty( $item['transaction_id'] ) )
            return '-';

        if( !empty( $item['payment_gateway'] ) ){

            $test_mode = pms_is_payment_test_mode();

            if( in_array( $item['payment_gateway'], array( 'PayPal', 'PayPal Standard', 'PayPal Express', 'PayPal Express Checkout' ) ) ){

                if( $test_mode ){
                    $url = 'https://www.sandbox.paypal.com/activity/payment/' . $item['transaction_id'];
                } else {
                    $url = 'https://www.paypal.com/activity/payment/' . $item['transaction_id'];
                }

            } else if( in_array( $item['payment_gateway'], array( 'stripe', 'Stripe' ) ) ){

                if( $test_mode ){
                    $url = 'https://dashboard.stripe.com/test/payments/' . $item['transaction_id'];
                } else {
                    $url = 'https://dashboard.stripe.com/payments/' . $item['transaction_id'];
                }

            }

        } 

        if( !empty( $url ) ){
            return '<a href="'. $url .'" target="_blank">'. $item['transaction_id'] . '</a>';
        } else {
            return $item['transaction_id']; 
        }

    }


    /**
     * Display if no items are found
     *
     */
    public function no_items() {

        echo esc_html__( 'No payments found', 'paid-member-subscriptions' );

    }

    /**
     * Returns the payment types that are used in the database
     *
     * @return array
     *
     */
    public function get_used_payment_types() {

        $payment_types = array();

        if ( false === ( $payment_types = get_transient( 'pms_existing_payment_types' ) ) ) {

            $payment_types = pms_get_payment_types();

            global $wpdb;

            $existing_payment_types = $wpdb->get_results( "SELECT DISTINCT type FROM {$wpdb->prefix}pms_payments", 'ARRAY_N' );

            $existing_types = array();
            foreach( $existing_payment_types as $type ) {
                $existing_types[] = $type[0];
            }
    
            // Keep only payment types that exist in the database
            $payment_types = array_intersect_key( $payment_types, array_flip( $existing_types ) );

            set_transient( 'pms_existing_payment_types', $payment_types, HOUR_IN_SECONDS );

        }

        return $payment_types;

    }

}
