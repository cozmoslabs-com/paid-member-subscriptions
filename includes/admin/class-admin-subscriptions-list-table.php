<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically in the plugins section
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/**
 * Extends WP default list table for our custom subscriptions section
 *
 */
Class PMS_Subscriptions_List_Table extends WP_List_Table {

    /**
     * Subscriptions per page
     *
     * @var int
     */
    public $items_per_page;

    /**
     * Subscriptions table data
     *
     * @var array
     */
    public $data = array();

    /**
     * Subscriptions views count
     *
     * @var array
     */
    public $views_count = array();

    /**
     * The total number of Subscriptions
     *
     * @var int
     */
    private $total_items = 0;

    public function __construct() {

        $screen = get_current_screen();

        parent::__construct( array(
            'singular' => 'subscription',
            'plural'   => 'subscriptions',
            'ajax'     => false,
            'screen'   => $screen->id,
        ) );

        // TODO: there is a typo in this filter (extra space before _columns)
        // TODO: if corrected it will throw an error (Labels Edit related, needs investigating)
        // TODO: - also present in: includes/admin/class-admin-members-list-table.php & includes/admin/class-admin-payments-list-table.php
//        add_filter( 'manage_' . $screen->id . ' _columns', array( $this, 'manage_columns' ) );

        // Register custom bulk actions
        add_filter( 'bulk_actions-' . $screen->id, array( $this, 'register_bulk_actions' ) );

        // Set items per page
        $items_per_page = get_user_meta( get_current_user_id(), 'pms_subscriptions_per_page', true );

        if( empty( $items_per_page ) )
            $items_per_page = 10;

        $this->items_per_page = $items_per_page;

        // Set table data
        $this->set_table_data();

    }

//    public function manage_columns() {
//
//        $this->get_column_info();
//
//        return $this->_column_headers[0];
//
//    }

    /**
     * Register custom bulk actions
     *
     * @param array $actions actions
     *
     * @return array
     *
     */
    function register_bulk_actions( $actions ) {

        if( isset( $_GET['page'] ) && $_GET['page'] == 'pms-subscriptions-page' && empty( $_GET['subpage'] ) ) {
            $actions['pms_subscriptions_bulk_delete'] = esc_html__( 'Delete Subscriptions', 'paid-member-subscriptions');
            $actions['pms_subscriptions_bulk_cancel'] = esc_html__( 'Cancel Subscriptions', 'paid-member-subscriptions');
        }

        return apply_filters( 'pms_subscriptions_list_table_register_bulk_actions', $actions );

    }

    /**
     * Define table columns
     *
     * @return array
     *
     */
    public function get_columns() {
        $columns = array(
            'cb'                => '<input type="checkbox" />',
            'subscription_id'   => esc_html__( 'ID', 'paid-member-subscriptions' ),
            'username'          => esc_html__( 'Username', 'paid-member-subscriptions' ),
            'subscription_plan' => esc_html__( 'Subscription Plan', 'paid-member-subscriptions' ),
            'start_date'        => esc_html__( 'Start Date', 'paid-member-subscriptions' ),
            'next_payment_date' => esc_html__( 'Next Payment Date', 'paid-member-subscriptions' ),
            'amount'            => esc_html__( 'Amount', 'paid-member-subscriptions' ),
            'payment_gateway'   => esc_html__( 'Payment Gateway', 'paid-member-subscriptions' ),
            'type'              => esc_html__( 'Type', 'paid-member-subscriptions' ),
            'status'            => esc_html__( 'Status', 'paid-member-subscriptions' ),
        );

        return apply_filters( 'pms_subscriptions_list_table_columns', $columns );
    }

    /**
     * Overwrites the parent class
     * Define which columns are sortable
     *
     * @return array
     *
     */
    public function get_sortable_columns() {

        return array(
            'subscription_id' => array( 'subscription_id', false ),
            'username'        => array( 'username', false ),
            'subscription_plan' => array( 'subscription_plan', false ),
            'start_date'      => array( 'start_date', false ),
            'next_payment_date' => array( 'next_payment_date', false )
        );

    }

    /**
     * Get the possible views for the Subscriptions list table
     *
     * @return mixed|null
     *
     */
    protected function get_views_map() {

        $views_map = array(
                'all'          => esc_html__( 'All', 'paid-member-subscriptions' ),
                'active'       => esc_html__( 'Active', 'paid-member-subscriptions' ),
                'canceled'     => esc_html__( 'Canceled', 'paid-member-subscriptions' ),
                'expired'      => esc_html__( 'Expired', 'paid-member-subscriptions' ),
                'paused'       => esc_html__( 'Paused', 'paid-member-subscriptions' ),
                'pending'      => esc_html__( 'Pending', 'paid-member-subscriptions' ),
                'pending_gift' => esc_html__( 'Pending Gift', 'paid-member-subscriptions' ),
                'abandoned'    => esc_html__( 'Abandoned', 'paid-member-subscriptions' ),
        );

        return apply_filters( 'pms_subscriptions_list_table_views_map', $views_map );

    }

    /**
     * Build status views for the Subscriptions list table
     *
     * @return array
     *
     */
    protected function get_views() {

        $views = array();

        foreach( $this->get_views_map() as $view_slug => $view_label ) {

            if( $view_slug != 'all' && isset( $this->views_count[ $view_slug ] ) && empty( $this->views_count[ $view_slug ] ) )
                continue;

            if( $view_slug == 'all' ) {
                $views[ $view_slug ] = '<a href="' . esc_url( remove_query_arg( array( 'pms-view', 'paged' ) ) ) . '" ' . ( !isset( $_GET['pms-view'] ) ? 'class="current"' : '' ) . '>' . $view_label . ' <span class="count">(' . ( isset( $this->views_count[ $view_slug ] ) ? $this->views_count[ $view_slug ] : '' ) . ')</span></a>';
            }
            else {
                $views[ $view_slug ] = '<a href="' . esc_url( add_query_arg( array( 'pms-view' => $view_slug, 'paged' => 1 ) ) ) . '" ' . ( isset( $_GET['pms-view'] ) && $_GET['pms-view'] == $view_slug ? 'class="current"' : '' ) . '>' . $view_label . ' <span class="count">(' . ( isset( $this->views_count[ $view_slug ] ) ? $this->views_count[ $view_slug ] : '' ) . ')</span></a>';
            }

        }

        return apply_filters( 'pms_subscriptions_list_table_get_views', $views );

    }

    /**
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
        wp_nonce_field( 'pms_subscriptions_bulk_action_nonce', '_wpnonce', false );

        $this->extra_tablenav( $which );
        if ( $which == 'bottom' )
            $this->pagination( $which );

        echo '<br class="clear" />';
        echo '</div>';

    }

    /**
     * Get the Subscriptions query string
     *
     * @param array $args Query args
     * @param string $query_type Query type: rows, views_count
     *
     * @return string
     *
     */
    protected function get_subscriptions_query_string( $args = array(), $query_type = 'rows' ) {

        global $wpdb;

        $defaults = array(
            'order'                       => 'DESC',
            'orderby'                     => 'id',
            'number'                      => 1000,
            'offset'                      => '',
            'status'                      => '',
            'subscription_plan_id'        => '',
            'payment_gateway'             => '',
            'currency'                    => '',
            'group'                       => '',
            'search'                      => '',
            'start_date_after'            => '',
            'start_date_before'           => '',
            'expiration_date_after'       => '',
            'expiration_date_before'      => '',
            'billing_next_payment_after'  => '',
            'billing_next_payment_before' => '',
            'include_abandoned'           => false,
        );

        $args = apply_filters( 'pms_get_subscriptions_list_table_query_args', wp_parse_args( $args, $defaults ), $args, $defaults );

        // Start query string
        if( $query_type == 'views_count' )
            $query_string = 'SELECT subscriptions.status, COUNT(DISTINCT subscriptions.id) as count ';
        else
            $query_string = 'SELECT DISTINCT subscriptions.* ';

        $query_from   = "FROM {$wpdb->prefix}pms_member_subscriptions subscriptions ";
        $query_where  = "WHERE 1=1 ";
        $query_order_by = '';
        $query_order    = '';
        $query_limit    = '';
        $query_offset   = '';
        $query_group    = '';

        $query_from .= "LEFT JOIN {$wpdb->users} users ON users.ID = subscriptions.user_id ";
        $query_from .= "LEFT JOIN {$wpdb->posts} plans ON plans.ID = subscriptions.subscription_plan_id ";
        $query_from .= "LEFT JOIN {$wpdb->prefix}pms_member_subscriptionmeta gift_payment_meta ON gift_payment_meta.member_subscription_id = subscriptions.id AND gift_payment_meta.meta_key = 'gift_payment_id' ";
        $query_from .= "LEFT JOIN {$wpdb->prefix}pms_payments gift_payments ON gift_payments.id = gift_payment_meta.meta_value ";
        $query_from .= "LEFT JOIN {$wpdb->prefix}pms_member_subscriptionmeta currency_meta ON currency_meta.member_subscription_id = subscriptions.id AND currency_meta.meta_key = 'currency' ";
        $query_from .= "LEFT JOIN {$wpdb->prefix}pms_member_subscriptionmeta group_owner_meta ON group_owner_meta.member_subscription_id = subscriptions.id AND group_owner_meta.meta_key = 'pms_group_subscription_owner' ";
        $query_from .= "LEFT JOIN {$wpdb->prefix}pms_member_subscriptionmeta payment_type_meta ON payment_type_meta.member_subscription_id = subscriptions.id AND payment_type_meta.meta_key = 'pms_payment_type' ";

        // Keep filtering semantics aligned with PMS_Member_Subscription::is_auto_renewing().
        $auto_renewing_sql = "( subscriptions.status NOT IN ('expired', 'canceled', 'abandoned') AND COALESCE(payment_type_meta.meta_value, '') != 'one_time_payment' AND ( ( subscriptions.payment_profile_id IS NOT NULL AND subscriptions.payment_profile_id != '' ) OR ( subscriptions.billing_duration > 0 AND subscriptions.billing_duration_unit IS NOT NULL AND subscriptions.billing_duration_unit != '' ) ) )";
        $next_billing_date_sql = "COALESCE(NULLIF(subscriptions.billing_next_payment, '0000-00-00 00:00:00'), NULLIF(subscriptions.expiration_date, '0000-00-00 00:00:00'))";

        // Filter by status
        if( !empty( $args['status'] ) ) {

            if( is_array( $args['status'] ) ){
                $status = array_map( 'sanitize_text_field', $args['status'] );
                $status_placeholders = implode( ',', array_fill( 0, count( $status ), '%s' ) );
                $query_where .= $wpdb->prepare( " AND subscriptions.status IN ({$status_placeholders})", $status );
            }
            else{
                $status = sanitize_text_field( $args['status'] );
                $query_where .= $wpdb->prepare( ' AND subscriptions.status LIKE %s', $status );
            }

        }

        // Exclude Abandoned and Pending Gift statuses unless requested
        if( isset( $args['include_abandoned'] ) && $args['include_abandoned'] === false ) {
            $query_where .= " AND subscriptions.status NOT LIKE 'abandoned'";
            $query_where .= " AND subscriptions.status NOT LIKE 'pending_gift'";
        }

        // Filter by start date after
        if( !empty( $args['start_date_after'] ) ) {
            $start_date_after = sanitize_text_field( $args['start_date_after'] );
            $query_where .= $wpdb->prepare( ' AND subscriptions.start_date > %s', $start_date_after );
        }

        // Filter by start date before
        if( !empty( $args['start_date_before'] ) ) {
            $start_date_before = sanitize_text_field( $args['start_date_before'] );
            $query_where .= $wpdb->prepare( ' AND subscriptions.start_date < %s', $start_date_before );
        }

        // Filter by expiration date after
        if( !empty( $args['expiration_date_after'] ) ) {
            $expiration_date_after = sanitize_text_field( $args['expiration_date_after'] );
            $query_where .= " AND subscriptions.expiration_date IS NOT NULL AND subscriptions.expiration_date != '0000-00-00 00:00:00'";
            $query_where .= $wpdb->prepare( ' AND subscriptions.expiration_date > %s', $expiration_date_after );
            $query_where .= " AND NOT {$auto_renewing_sql}";
        }

        // Filter by expiration date before
        if( !empty( $args['expiration_date_before'] ) ) {
            $expiration_date_before = sanitize_text_field( $args['expiration_date_before'] );
            $query_where .= " AND subscriptions.expiration_date IS NOT NULL AND subscriptions.expiration_date != '0000-00-00 00:00:00'";
            $query_where .= $wpdb->prepare( ' AND subscriptions.expiration_date < %s', $expiration_date_before );
            $query_where .= " AND NOT {$auto_renewing_sql}";
        }

        // Filter by billing next payment date after
        if( !empty( $args['billing_next_payment_after'] ) ) {
            $billing_next_payment_after = sanitize_text_field( $args['billing_next_payment_after'] );
            $query_where .= " AND {$next_billing_date_sql} IS NOT NULL";
            $query_where .= $wpdb->prepare( " AND {$next_billing_date_sql} > %s", $billing_next_payment_after );
            $query_where .= " AND {$auto_renewing_sql}";
        }

        // Filter by billing next payment date before
        if( !empty( $args['billing_next_payment_before'] ) ) {
            $billing_next_payment_before = sanitize_text_field( $args['billing_next_payment_before'] );
            $query_where .= " AND {$next_billing_date_sql} IS NOT NULL";
            $query_where .= $wpdb->prepare( " AND {$next_billing_date_sql} < %s", $billing_next_payment_before );
            $query_where .= " AND {$auto_renewing_sql}";
        }

        // Filter by subscription plan id
        if( !empty( $args['subscription_plan_id'] ) )
            $query_where .= $wpdb->prepare( ' AND subscriptions.subscription_plan_id = %d', (int)$args['subscription_plan_id'] );

        // Filter by payment gateway
        if( !empty( $args['payment_gateway'] ) ) {

            $payment_gateway = sanitize_text_field( $args['payment_gateway'] );
            $query_where .= $wpdb->prepare( " AND CASE WHEN subscriptions.payment_gateway = 'gift_subscription' THEN gift_payments.payment_gateway ELSE subscriptions.payment_gateway END LIKE %s", $payment_gateway );

        }

        // Filter by currency
        if( !empty( $args['currency'] ) ) {

            $selected_currency = strtoupper( sanitize_text_field( $args['currency'] ) );
            $active_currency   = pms_get_active_currency();
            $query_where .= $wpdb->prepare( " AND UPPER(COALESCE(NULLIF(currency_meta.meta_value, ''), %s)) = %s", $active_currency, $selected_currency );

        }

        // Filter by group owner id
        if( !empty( $args['group'] ) ) {
            $selected_group = (int)$args['group'];
            $query_where .= $wpdb->prepare( ' AND ( subscriptions.id = %d OR group_owner_meta.meta_value = %s )', $selected_group, (string)$selected_group );
        }

        // Search query
        if( !empty( $args['search'] ) ) {

            $search_term  = trim( sanitize_text_field( $args['search'] ) );
            $search_like  = '%' . $wpdb->esc_like( $search_term ) . '%';
            $search_where = array();

            if( is_numeric( $search_term ) )
                $search_where[] = $wpdb->prepare( 'subscriptions.id = %d', (int)$search_term );

            $search_where[] = $wpdb->prepare( 'users.user_login LIKE %s', $search_like );
            $search_where[] = $wpdb->prepare( 'plans.post_title LIKE %s', $search_like );

            $query_where .= ' AND ( ' . implode( ' OR ', $search_where ) . ' )';

        }

        if( $query_type == 'rows' ) {

            // Query order by
            if( !empty( $args['orderby'] ) && $args['orderby'] == 'id' ) {
                $query_order_by = ' ORDER BY subscriptions.id ';
            }
            elseif( !empty( $args['orderby'] ) && $args['orderby'] == 'start_date' ) {
                $query_order_by = ' ORDER BY subscriptions.start_date ';
            }
            elseif( !empty( $args['orderby'] ) && $args['orderby'] == 'username' ) {
                $query_order_by = " ORDER BY CASE WHEN users.user_login IS NULL OR users.user_login = '' THEN 1 ELSE 0 END ASC, users.user_login ";
            }
            elseif( !empty( $args['orderby'] ) && $args['orderby'] == 'subscription_plan' ) {
                $query_order_by = " ORDER BY CASE WHEN plans.post_title IS NULL OR plans.post_title = '' THEN 1 ELSE 0 END ASC, plans.post_title ";
            }
            elseif( !empty( $args['orderby'] ) && $args['orderby'] == 'next_payment_date' ) {
                $query_order_by = " ORDER BY CASE WHEN COALESCE(NULLIF(subscriptions.billing_next_payment, '0000-00-00 00:00:00'), NULLIF(subscriptions.expiration_date, '0000-00-00 00:00:00')) IS NULL THEN 1 ELSE 0 END ASC, COALESCE(NULLIF(subscriptions.billing_next_payment, '0000-00-00 00:00:00'), NULLIF(subscriptions.expiration_date, '0000-00-00 00:00:00')) ";
            }
            else {
                $query_order_by = ' ORDER BY subscriptions.id ';
            }

            // Query order
            $query_order = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
            $query_order .= ' ';

            // Query limit
            if( !empty( $args['number'] ) )
                $query_limit = 'LIMIT ' . (int)trim( $args['number'] ) . ' ';

            // Query offset
            if( !empty( $args['offset'] ) )
                $query_offset = 'OFFSET ' . (int)trim( $args['offset'] ) . ' ';

        }

        if( $query_type == 'views_count' )
            $query_group = ' GROUP BY subscriptions.status ';

        $query_string .= $query_from . $query_where . $query_group . $query_order_by . $query_order . $query_limit . $query_offset;

        return $query_string;

    }

    /**
     * Get Subscriptions for the list table
     *
     * @param array $args Query args
     *
     * @return array
     *
     */
    protected function get_subscriptions( $args = array() ) {

        global $wpdb;

        $query_string = $this->get_subscriptions_query_string( $args );
        $data_array   = $wpdb->get_results( $query_string, ARRAY_A );
        $subscriptions = array();

        foreach( $data_array as $key => $data )
            $subscriptions[$key] = new PMS_Member_Subscription( $data );

        return $subscriptions;

    }

    /**
     * Get Subscriptions views count
     *
     * @param array $args Query args
     *
     * @return array
     *
     */
    protected function get_subscriptions_views_count( $args = array() ) {

        global $wpdb;

        $query_string = $this->get_subscriptions_query_string( $args, 'views_count' );
        $results = $wpdb->get_results( $query_string, ARRAY_A );
        $views_count = array();

        foreach( array_keys( $this->get_views_map() ) as $view_slug )
            $views_count[ $view_slug ] = 0;

        foreach( $results as $result ) {

            if( !isset( $result['count'] ) )
                continue;

            $status_count = (int)$result['count'];

            if( !empty( $result['status'] ) && !in_array( $result['status'], array( 'abandoned', 'pending_gift' ) ) )
                $views_count['all'] += $status_count;

            if( !empty( $result['status'] ) && isset( $views_count[ $result['status'] ] ) )
                $views_count[ $result['status'] ] = $status_count;
        }

        return apply_filters( 'pms_get_subscriptions_views_count', $views_count, $args, $results );

    }

    /**
     * Set the table data and pagination totals
     *
     */
    public function set_table_data() {

        $data = array();
        $args = array();

        $selected_view            = ( isset( $_GET['pms-view'] ) ? sanitize_text_field( $_GET['pms-view'] ) : '' );
        $paged                    = ( isset( $_GET['paged'] ) ? (int)$_GET['paged'] : 1 );
        $search_query             = ( isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '' );
        $selected_payment_gateway = !empty( $_GET['pms-filter-payment-gateway'] ) ? sanitize_text_field( $_GET['pms-filter-payment-gateway'] ) : '';
        $mc_addon_active          = apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-multiple-currencies/index.php' );
        $selected_currency        = ( $mc_addon_active && !empty( $_GET['pms-filter-currency'] ) ? strtoupper( sanitize_text_field( $_GET['pms-filter-currency'] ) ) : '' );
        $gm_addon_active          = apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-group-memberships/index.php' );
        $selected_group           = ( $gm_addon_active && !empty( $_GET['pms-filter-group'] ) ? sanitize_text_field( $_GET['pms-filter-group'] ) : '' );

        /**
         * Set subscriptions arguments
         *
         */
        $args['status']            = $selected_view;
        $args['include_abandoned'] = ( $selected_view == 'abandoned' || $selected_view == 'pending_gift' );
        $args['orderby']           = 'id';
        $args['order']             = 'DESC';
        $args['number']            = $this->items_per_page;
        $args['offset']            = ( $paged - 1 ) * $this->items_per_page;
        $args['search']            = $search_query;
        $args['payment_gateway']   = $selected_payment_gateway;
        $args['currency']          = $selected_currency;
        $args['group']             = $selected_group;

        /**
         * Set sorting args
         *
         */
        if( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {

            $selected_orderby = sanitize_text_field( $_REQUEST['orderby'] );

            if( $selected_orderby == 'subscription_id' )
                $args['orderby'] = 'id';
            elseif( $selected_orderby == 'start_date' )
                $args['orderby'] = 'start_date';
            elseif( $selected_orderby == 'username' )
                $args['orderby'] = 'username';
            elseif( $selected_orderby == 'subscription_plan' )
                $args['orderby'] = 'subscription_plan';
            elseif( $selected_orderby == 'next_payment_date' )
                $args['orderby'] = 'next_payment_date';

            // Sort order
            $order = strtolower( sanitize_text_field( $_REQUEST['order'] ) );

            if( $order == 'asc' )
                $args['order'] = 'ASC';
            elseif( $order == 'desc' )
                $args['order'] = 'DESC';

        }

        // Filter Subscriptions by Subscription Plan
        if( ! empty( $_GET['pms-filter-subscription-plan'] ) )
            $args['subscription_plan_id'] = (int)$_GET['pms-filter-subscription-plan'];

        // Filter Subscriptions by Start Date
        if( !empty( $_GET['pms-filter-start-date'] ) ) {

            $start_date_filter = sanitize_text_field( $_GET['pms-filter-start-date'] );

            if( $start_date_filter != 'custom' ) {
                if( $start_date_filter == 'last_week' ) {
                    $args['start_date_after']  = date( 'Y-m-d 00:00:00', strtotime( 'monday last week' ) );
                    $args['start_date_before'] = date( 'Y-m-d 23:59:59', strtotime( 'sunday last week' ) );
                }
                elseif( $start_date_filter == 'last_month' ) {
                    $args['start_date_after']  = date( 'Y-m-d 00:00:00', strtotime( 'first day of last month' ) );
                    $args['start_date_before'] = date( 'Y-m-d 23:59:59', strtotime( 'last day of last month' ) );
                }
                elseif( $start_date_filter == 'last_year' ) {
                    $args['start_date_after']  = date( 'Y-m-d 00:00:00', strtotime( 'first day of last year' ) );
                    $args['start_date_before'] = date( 'Y-m-d 23:59:59', strtotime( 'last day of December last year' ) );
                }

            } else {

                if( !empty( $_GET['pms-datepicker-start-date-beginning'] ) )
                    $args['start_date_after'] = date( 'Y-m-d 00:00:00', strtotime( sanitize_text_field( $_GET['pms-datepicker-start-date-beginning'] ) ) );

                if( !empty( $_GET['pms-datepicker-start-date-end'] ) )
                    $args['start_date_before'] = date( 'Y-m-d 23:59:59', strtotime( sanitize_text_field( $_GET['pms-datepicker-start-date-end'] ) ) );

            }
        }

        // Filter Subscriptions by Expiration Date
        if( !empty( $_GET['pms-filter-expiration-date'] ) ) {

            $expiration_date_filter = sanitize_text_field( $_GET['pms-filter-expiration-date'] );

            if( $expiration_date_filter != 'custom' ) {

                if( $expiration_date_filter == 'today' ) {
                    $args['expiration_date_after']  = date( 'Y-m-d 00:00:00', strtotime( 'today' ) );
                    $args['expiration_date_before'] = date( 'Y-m-d 23:59:59', strtotime( 'today' ) );
                }
                elseif( $expiration_date_filter == 'tomorrow' ) {
                    $args['expiration_date_after']  = date( 'Y-m-d 00:00:00', strtotime( 'tomorrow' ) );
                    $args['expiration_date_before'] = date( 'Y-m-d 23:59:59', strtotime( 'tomorrow' ) );
                }
                elseif( $expiration_date_filter == 'this_week' ) {
                    $args['expiration_date_after']  = date( 'Y-m-d 00:00:00', strtotime( 'monday this week' ) );
                    $args['expiration_date_before'] = date( 'Y-m-d 23:59:59', strtotime( 'sunday this week' ) );
                }
                elseif( $expiration_date_filter == 'this_month' ) {
                    $args['expiration_date_after']  = date( 'Y-m-d 00:00:00', strtotime( 'first day of this month' ) );
                    $args['expiration_date_before'] = date( 'Y-m-d 23:59:59', strtotime( 'last day of this month' ) );
                }

            } else {

                if( !empty( $_GET['pms-datepicker-expiration-date-beginning'] ) )
                    $args['expiration_date_after'] = date( 'Y-m-d 00:00:00', strtotime( sanitize_text_field( $_GET['pms-datepicker-expiration-date-beginning'] ) ) );

                if( !empty( $_GET['pms-datepicker-expiration-date-end'] ) )
                    $args['expiration_date_before'] = date( 'Y-m-d 23:59:59', strtotime( sanitize_text_field( $_GET['pms-datepicker-expiration-date-end'] ) ) );

            }
        }

        // Filter Subscriptions by Next Billing Date
        if( !empty( $_GET['pms-filter-next-billing-date'] ) ) {

            $next_billing_date_filter = sanitize_text_field( $_GET['pms-filter-next-billing-date'] );

            if( $next_billing_date_filter != 'custom' ) {

                if( $next_billing_date_filter == 'tomorrow' ) {
                    $args['billing_next_payment_after']  = date( 'Y-m-d 00:00:00', strtotime( 'tomorrow' ) );
                    $args['billing_next_payment_before'] = date( 'Y-m-d 23:59:59', strtotime( 'tomorrow' ) );
                }
                elseif( $next_billing_date_filter == 'this_week' ) {
                    $args['billing_next_payment_after']  = date( 'Y-m-d 00:00:00', strtotime( 'monday this week' ) );
                    $args['billing_next_payment_before'] = date( 'Y-m-d 23:59:59', strtotime( 'sunday this week' ) );
                }
                elseif( $next_billing_date_filter == 'this_month' ) {
                    $args['billing_next_payment_after']  = date( 'Y-m-d 00:00:00', strtotime( 'first day of this month' ) );
                    $args['billing_next_payment_before'] = date( 'Y-m-d 23:59:59', strtotime( 'last day of this month' ) );
                }
                elseif( $next_billing_date_filter == 'this_year' ) {
                    $args['billing_next_payment_after']  = date( 'Y-m-d 00:00:00', strtotime( 'first day of January' ) );
                    $args['billing_next_payment_before'] = date( 'Y-m-d 23:59:59', strtotime( 'last day of December' ) );
                }

            } else {

                if( !empty( $_GET['pms-datepicker-next-billing-date-beginning'] ) )
                    $args['billing_next_payment_after'] = date( 'Y-m-d 00:00:00', strtotime( sanitize_text_field( $_GET['pms-datepicker-next-billing-date-beginning'] ) ) );

                if( !empty( $_GET['pms-datepicker-next-billing-date-end'] ) )
                    $args['billing_next_payment_before'] = date( 'Y-m-d 23:59:59', strtotime( sanitize_text_field( $_GET['pms-datepicker-next-billing-date-end'] ) ) );

            }
        }

        $subscriptions = $this->get_subscriptions( $args );
        $legacy_paypal_amounts = $this->get_legacy_paypal_amounts( $subscriptions );

        /**
         * Get payment gateways data
         *
         */
        $payment_gateways = pms_get_payment_gateways();

        /**
         * Set views count for each view ( a.k.a. subscription status )
         *
         */
        $views_count_args = $args;

        unset( $views_count_args['number'] );
        unset( $views_count_args['offset'] );
        unset( $views_count_args['status'] );

        $views_count_args['include_abandoned'] = true;

        $this->views_count = $this->get_subscriptions_views_count( $views_count_args );

        /**
         * Set all items
         *
         */
        $this->total_items = $this->views_count[ ( ! empty( $selected_view ) ? $selected_view : 'all' ) ];

        /**
         * Set data array
         *
         */
        foreach( $subscriptions as $subscription ) {

            // Get Subscription Plan
            $subscription_plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

            // Get user
            $user = get_user_by( 'id', $subscription->user_id );

            // Get payment gateway
            $payment_gateway_name = $this->get_subscription_payment_gateway_name( $subscription, $payment_gateways );

            // Get amount
            $amount = $this->get_subscription_amount( $subscription, $legacy_paypal_amounts );

            // Get next payment date
            $next_payment_date = '';

            if( !empty( $subscription->billing_next_payment ) )
                $next_payment_date = pms_sanitize_date( $subscription->billing_next_payment );
            elseif( !empty( $subscription->expiration_date ) )
                $next_payment_date = pms_sanitize_date( $subscription->expiration_date );

            // Get subscription type
            $subscription_type = esc_html__( 'One Time', 'paid-member-subscriptions' );

            if( $subscription->is_auto_renewing() )
                $subscription_type = esc_html__( 'Auto Renewing', 'paid-member-subscriptions' );
            elseif( empty( $subscription->expiration_date ) )
                $subscription_type = esc_html__( 'Unlimited', 'paid-member-subscriptions' );

            // Get subscription type markers
            $type_markers = $this->get_subscription_type_markers( $subscription, $subscription_plan );

            $data[] = apply_filters( 'pms_subscriptions_list_table_entry_data', array(
                'subscription_id'   => $subscription->id,
                'user_id'           => $subscription->user_id,
                'username'          => ( $user ? $user->data->user_login : '' ),
                'subscription_plan' => !empty( $subscription_plan->name ) ? $subscription_plan->name : sprintf( esc_html__( 'Not Found (ID: %s)', 'paid-member-subscriptions' ), $subscription->subscription_plan_id ),
                'start_date'        => apply_filters( 'pms_match_date_format_to_wp_settings', pms_sanitize_date( $subscription->start_date ), false ),
                'next_payment_date' => !empty( $next_payment_date ) ? strtotime( $next_payment_date ) : '',
                'amount'            => $amount,
                'payment_gateway'   => $payment_gateway_name,
                'type'              => $subscription_type,
                'type_markers'      => $type_markers,
                'status'            => $subscription->status,
            ), $subscription );
        }

        $this->data = $data;

    }

    /**
     * Prepare table headers, pagination and items
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
     * Get the payment gateway slug for a Subscription
     *
     * @param PMS_Member_Subscription $subscription Subscription object
     *
     * @return string
     *
     */
    protected function get_subscription_payment_gateway_slug( $subscription ) {
        $payment_gateway_slug = !empty( $subscription->payment_gateway ) ? $subscription->payment_gateway : '';

        if( $payment_gateway_slug == 'gift_subscription' ) {
            $gift_payment_id = pms_get_member_subscription_meta( $subscription->id, 'gift_payment_id', true );

            if( !empty( $gift_payment_id ) ) {
                $gift_payment = pms_get_payment( (int)$gift_payment_id );

                if( !empty( $gift_payment->payment_gateway ) )
                    $payment_gateway_slug = $gift_payment->payment_gateway;
            }
        }

        return $payment_gateway_slug;
    }

    /**
     * Get the payment gateway display name for a Subscription
     *
     * @param PMS_Member_Subscription $subscription     Subscription object
     * @param array                   $payment_gateways Registered gateways
     *
     * @return string
     *
     */
    protected function get_subscription_payment_gateway_name( $subscription, $payment_gateways ) {
        $payment_gateway_name = '';
        $payment_gateway_slug = $this->get_subscription_payment_gateway_slug( $subscription );

        if( !empty( $payment_gateway_slug ) && !empty( $payment_gateways[ $payment_gateway_slug ]['display_name_admin'] ) )
            $payment_gateway_name = $payment_gateways[ $payment_gateway_slug ]['display_name_admin'];

        return $payment_gateway_name;
    }


    /**
     * Get the list of currencies used by Subscriptions
     *
     * @return array
     *
     */
    public function get_used_subscription_currencies() {

        global $wpdb;

        $currencies = array();

        $used_currencies = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT CASE WHEN meta.meta_value IS NULL OR meta.meta_value = '' THEN %s ELSE meta.meta_value END AS currency FROM {$wpdb->prefix}pms_member_subscriptions subscriptions LEFT JOIN {$wpdb->prefix}pms_member_subscriptionmeta meta ON subscriptions.id = meta.member_subscription_id AND meta.meta_key = %s", pms_get_active_currency(), 'currency' ) );

        foreach( $used_currencies as $currency )
            $currencies[] = strtoupper( sanitize_text_field( $currency ) );

        $currencies = array_unique( $currencies );
        sort( $currencies );

        return $currencies;

    }


    /**
     * Get all active group names
     * - used in the group filter dropdown
     *
     * @return array
     *
     */
    public function get_active_group_names() {

        global $wpdb;

        $groups = $wpdb->get_results( $wpdb->prepare( "SELECT subscription_meta.meta_id, subscription_meta.member_subscription_id, subscription_meta.meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta as subscription_meta INNER JOIN {$wpdb->prefix}pms_member_subscriptions as subscriptions ON subscription_meta.member_subscription_id = subscriptions.id WHERE subscription_meta.meta_key = %s AND subscription_meta.meta_value != '' AND subscriptions.status != 'abandoned'", 'pms_group_name' ), 'ARRAY_A' );

        return !empty( $groups ) ? $groups : array();

    }


    /**
     * Get formatted amount for a Subscription
     *
     * @param PMS_Member_Subscription $subscription Subscription object
     * @param array                   $legacy_paypal_amounts Legacy PayPal amounts map
     *
     * @return string
     *
     */
    protected function get_subscription_amount( $subscription, $legacy_paypal_amounts = array() ) {
        $amount = ( isset( $subscription->billing_amount ) ? $subscription->billing_amount : '' );

        if( ( $subscription->payment_gateway === 'paypal_standard' || $subscription->payment_gateway === 'paypal_express' ) && isset( $legacy_paypal_amounts[ (int)$subscription->id ] ) )
            $amount = $legacy_paypal_amounts[ (int)$subscription->id ];

        if( $amount !== '' ) {

            if( $amount == 0 ) {
                $amount = esc_html__( 'Free', 'paid-member-subscriptions' );
            }
            else {
                $subscription_currency = pms_get_member_subscription_meta( $subscription->id, 'currency', true );
                $currency = !empty( $subscription_currency ) ? $subscription_currency : pms_get_active_currency();
                $amount = pms_format_price( $amount, $currency );
            }

        }

        return apply_filters( 'pms_subscriptions_list_table_amount', $amount, $subscription );
    }

    /**
     * Get legacy PayPal amounts for Subscriptions
     *
     * @param array $subscriptions Subscriptions list
     *
     * @return array
     *
     */
    protected function get_legacy_paypal_amounts( $subscriptions = array() ) {

        global $wpdb;

        $subscription_ids = array();

        foreach( $subscriptions as $subscription ) {

            if( $subscription->payment_gateway !== 'paypal_standard' && $subscription->payment_gateway !== 'paypal_express' )
                continue;

            $subscription_ids[] = (int)$subscription->id;

        }

        if( empty( $subscription_ids ) )
            return array();

        $subscription_ids = array_unique( array_map( 'absint', $subscription_ids ) );
        $subscription_ids = implode( ',', $subscription_ids );

        $payments = $wpdb->get_results(
            "SELECT latest_payments.member_subscription_id, payments.amount 
             FROM {$wpdb->prefix}pms_payments as payments
             INNER JOIN (
                SELECT payment_meta.meta_value as member_subscription_id, MAX(payments.id) as latest_payment_id
                FROM {$wpdb->prefix}pms_payments as payments
                INNER JOIN {$wpdb->prefix}pms_paymentmeta as payment_meta ON payment_meta.payment_id = payments.id
                WHERE payment_meta.meta_key = 'subscription_id' AND payment_meta.meta_value IN ({$subscription_ids})
                GROUP BY payment_meta.meta_value
             ) as latest_payments ON latest_payments.latest_payment_id = payments.id",
            ARRAY_A
        );

        if( empty( $payments ) )
            return array();

        $amounts = array();

        foreach( $payments as $payment ) {

            if( !isset( $payment['member_subscription_id'] ) || !isset( $payment['amount'] ) )
                continue;

            $amounts[ (int)$payment['member_subscription_id'] ] = $payment['amount'];

        }

        return $amounts;

    }

    /**
     * Build the Subscription type markers list
     *
     * @param PMS_Member_Subscription $subscription      Subscription object
     * @param object                  $subscription_plan Subscription plan object
     *
     * @return array
     *
     */
    protected function get_subscription_type_markers( $subscription, $subscription_plan ) {
        $type_markers = array();

        $group_marker = $this->get_group_type_marker( $subscription );

        if( !empty( $group_marker ) )
            $type_markers[] = $group_marker;

        $is_gift_subscription = pms_get_member_subscription_meta( $subscription->id, 'is_gift', true );

        if( $is_gift_subscription == '1' || ( !empty( $subscription->status ) && $subscription->status == 'pending_gift' ) )
            $type_markers[] = esc_html__( 'Gift Subscription', 'paid-member-subscriptions' );

        if( $subscription->is_trial_period() )
            $type_markers[] = esc_html__( 'Trial Active', 'paid-member-subscriptions' );

        if( $subscription->has_installments() ) {

            $billing_processed_cycles = pms_get_member_subscription_billing_processed_cycles( $subscription->id );
            $billing_cycles           = !empty( $subscription->billing_cycles ) ? (int)$subscription->billing_cycles : 0;

            if( $billing_cycles > 0 && $billing_processed_cycles !== false )
                $type_markers[] = sprintf( esc_html__( 'Limited Cycles (%1$s/%2$s)', 'paid-member-subscriptions' ), $billing_processed_cycles, $billing_cycles );
            else
                $type_markers[] = esc_html__( 'Limited Cycles', 'paid-member-subscriptions' );

        }

        return apply_filters( 'pms_subscriptions_list_table_type_markers', $type_markers, $subscription, $subscription_plan );
    }

    /**
     * Get the group marker output for a group-related subscription
     *
     * @param PMS_Member_Subscription $subscription Subscription object
     *
     * @return string|false
     *
     */
    protected function get_group_type_marker( $subscription ) {

        if( !apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-group-memberships/index.php' ) )
            return false;

        $owner_subscription_id = ( function_exists( 'pms_in_gm_is_group_owner' ) && pms_in_gm_is_group_owner( $subscription->id ) ) ? $subscription->id : pms_get_member_subscription_meta( $subscription->id, 'pms_group_subscription_owner', true );

        if( empty( $owner_subscription_id ) )
            return false;

        $group_name = function_exists( 'pms_in_gm_get_group_name' ) ? pms_in_gm_get_group_name( $subscription->id ) : '';

        if( empty( $group_name ) )
            $group_name = esc_html__( 'Undefined', 'paid-member-subscriptions' );

        $group_url   = add_query_arg( array( 'subpage' => 'group_details', 'group_owner' => $owner_subscription_id ), admin_url( 'admin.php?page=pms-members-page' ) );
        $group_title = esc_html__( 'Edit group', 'paid-member-subscriptions' );

        return esc_html__( 'Group:', 'paid-member-subscriptions' ) . ' <a href="' . esc_url( $group_url ) . '" title="' . esc_attr( $group_title ) . '">' . esc_html( $group_name ) . '</a>';

    }


    /**
     * Render default column values
     *
     * @param array  $item        Row item
     * @param string $column_name Column key
     *
     * @return string
     *
     */
    public function column_default( $item, $column_name ) {

        return !empty( $item[ $column_name ] ) ? $item[ $column_name ] : '-';

    }

    /**
     * Handles the checkbox column output
     *
     * @param array $item The current item
     */
    function column_cb( $item ) {

        if( isset( $item[ 'subscription_id' ] ) && !empty( $item[ 'subscription_id' ] ) ) {
            $subscription_id = $item[ 'subscription_id' ];

            ?>
            <label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $subscription_id ); ?>"></label>
            <input type="checkbox" name="member_subscriptions[]" id="cb-select-<?php echo esc_attr( $subscription_id ); ?>" value="<?php echo esc_attr( $subscription_id ); ?>" />
            <?php
        }

    }

    /**
     * Render Subscription ID column
     *
     * @param array $item Row item
     *
     * @return string
     *
     */
    public function column_subscription_id( $item ) {
        return !empty( $item['subscription_id'] ) ? $item['subscription_id'] : '-';

    }

    /**
     * Render Username column with row actions
     *
     * @param array $item Row item
     *
     * @return string
     *
     */
    public function column_username( $item ) {

        $actions = array();

        $actions['edit'] = '<a href="' . esc_url( add_query_arg( array( 'page' => 'pms-members-page', 'subpage' => 'edit_subscription', 'subscription_id' => $item['subscription_id'] ), admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Edit Subscription', 'paid-member-subscriptions' ) . '</a>';
        $actions['delete'] = '<a class="pms-subscriptions-row-delete-action" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'pms-subscriptions-page', 'pms-action' => 'delete_subscription', 'subscription_id' => $item['subscription_id'] ), admin_url( 'admin.php' ) ), 'pms_subscriptions_row_action_nonce' ) ) . '">' . esc_html__( 'Delete', 'paid-member-subscriptions' ) . '</a>';

        /**
         * Filter the actions for a subscription
         *
         * @param array $actions
         * @param array $item
         *
         */
        $actions = apply_filters( 'pms_subscriptions_list_table_entry_actions', $actions, $item );

        if( !empty( $item['username'] ) )
            $username = $item['username'];
        else
            $username = sprintf( esc_html__( 'User no longer exists (ID: %s)', 'paid-member-subscriptions' ), $item['user_id'] );

        return '<a href="' . esc_url( add_query_arg( array( 'subpage' => 'edit_member', 'member_id' => $item['user_id'] ), admin_url( 'admin.php?page=pms-members-page' ) ) ) . '">' . esc_html( $username ) . '</a>' . $this->row_actions( $actions );

    }

    /**
     * Render Next Payment date column
     *
     * @param array $item Row item
     *
     * @return string
     *
     */
    public function column_next_payment_date( $item ) {

        if( !empty( $item['next_payment_date'] ) )
            $next_payment_date = apply_filters( 'pms_match_date_format_to_wp_settings', pms_sanitize_date( date( 'Y-m-d', $item['next_payment_date'] ) ), false );
        else $next_payment_date = '-';

        return $next_payment_date;
    }

    /**
     * Render Status column
     *
     * @param array $item Row item
     *
     * @return string
     *
     */
    public function column_status( $item ) {

        $status_slug = !empty( $item['status'] ) ? $item['status'] : '';

        if( empty( $status_slug ) )
            return '-';

        $statuses = pms_get_member_subscription_statuses();

        if( !empty( $statuses[ $status_slug ] ) )
            return '<span class="pms-status-dot '. $status_slug .'"></span>' . $statuses[ $status_slug ];

        return '<span class="pms-status-dot '. $status_slug .'"></span>' . $status_slug;

    }

    /**
     * Render Type column with extra markers
     *
     * @param array $item Row item
     *
     * @return string
     *
     */
    public function column_type( $item ) {

        if( empty( $item['type'] ) )
            return '-';

        $output = '<p class="pms-subscriptions__type">' . esc_html( $item['type'] ) . '</p>';

        if( !empty( $item['type_markers'] ) && is_array( $item['type_markers'] ) ) {

            foreach( $item['type_markers'] as $marker )
                $output .= '<p class="cozmoslabs-description pms-subscriptions__type-marker">' . $marker . '</p>';

        }

        return $output;

    }
}
