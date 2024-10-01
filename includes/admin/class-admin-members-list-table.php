<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically in the plugins section
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/*
 * Extent WP default list table for our custom members section
 *
 */
Class PMS_Members_List_Table extends WP_List_Table {

    /**
     * Members per page
     *
     * @access public
     * @var int
     */
    public $items_per_page;

    /**
     * Members table data
     *
     * @access public
     * @var array
     */
    public $data;

    /**
     * Members table views count
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


    /*
     * Constructor function
     *
     */
    public function __construct() {

        $screen = get_current_screen();

        parent::__construct( array(
            'singular'  => 'member',
            'plural'    => 'members',
            'ajax'      => false,
            'screen'    => $screen->id,
        ));

        add_filter( 'manage_' . $screen->id . ' _columns' , array( $this , 'manage_columns' ) );

        // Register custom bulk actions
        add_filter( 'bulk_actions-' . $screen->id, array( $this, 'register_bulk_actions' ) );

        // Set items per page
        $items_per_page = get_user_meta( get_current_user_id(), 'pms_members_per_page', true );

        if( empty( $items_per_page ) )
            $items_per_page = 10;

        $this->items_per_page = $items_per_page;

        // Set data
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

        if( !empty( $_GET['page'] == 'pms-members-page' ) && empty( $_GET['subpage'] ) )
            $actions['pms-delete-subscriptions'] = esc_html__( 'Delete Subscriptions', 'paid-member-subscriptions');

        return $actions;
    }

    /*
     * Overwrites the parent class.
     * Define the columns for the members
     *
     * @return array
     *
     */
    public function get_columns() {

        $columns = array(
            'cb'                => '<input type="checkbox" />',
            'user_id'           => esc_html__( 'User ID', 'paid-member-subscriptions' ),
            'username'          => esc_html__( 'Username', 'paid-member-subscriptions' ),
            'name'              => esc_html__( 'Name', 'paid-member-subscriptions' ),
            'email'             => esc_html__( 'E-mail', 'paid-member-subscriptions' ),
            'subscriptions'     => esc_html__( 'Subscribed to', 'paid-member-subscriptions' )
        );

        if( isset( $_GET['pms-view'] ) && $_GET['pms-view'] == 'abandoned' )
            $columns['subscriptions'] = esc_html__( 'Abandoned subscriptions', 'paid-member-subscriptions' );

        return apply_filters( 'pms_members_list_table_columns', $columns );

    }


    /**
     * Handles the checkbox column output.
     *
     * @since 4.3.0
     *
     * @param $item The current item.
     */
    function column_cb( $item ) {
        if( isset( $item[ 'subscriptions' ] ) && !empty( $item[ 'subscriptions' ][ 0 ] ) ){
            $subscription = $item[ 'subscriptions' ][ 0 ];
            if( isset( $subscription->id ) ){
                ?>
                <label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $subscription->id ); ?>"></label>
                <input type="checkbox" name="member_subscriptions[]" id="cb-select-<?php echo esc_attr( $subscription->id ); ?>" value="<?php echo esc_attr( $subscription->id ); ?>" />
                <?php
            }
        }
    }


    /*
     * Overwrites the parent class.
     * Define which columns to hide
     *
     * @return array
     *
     */
    public function get_hidden_columns() {

        return array();

    }


    /*
     * Overwrites the parent class.
     * Define which columns are sortable
     *
     * @return array
     *
     */
    public function get_sortable_columns() {

        return array(
            'user_id'           => array( 'user_id', false ),
            'username'          => array( 'username', false )
        );

    }


    /*
     * Returns the possible views for the members list table
     *
     */
    protected function get_views() {

        return apply_filters( 'pms_members_list_table_get_views', array(
            'all'       => '<a href="' . esc_url( remove_query_arg( array( 'pms-view', 'paged' ) ) ) . '" ' . ( !isset( $_GET['pms-view'] ) ? 'class="current"' : '' ) . '>All <span class="count">(' . ( isset( $this->views_count['all'] ) ? $this->views_count['all'] : '' ) . ')</span></a>',
            'active'    => '<a href="' . esc_url( add_query_arg( array( 'pms-view' => 'active', 'paged' => 1 ) ) ) . '" ' . ( isset( $_GET['pms-view'] ) && $_GET['pms-view'] == 'active' ? 'class="current"' : '' ) . '>Active <span class="count">(' . ( isset( $this->views_count['active'] ) ? $this->views_count['active'] : '' ) . ')</span></a>',
            'canceled'  => '<a href="' . esc_url( add_query_arg( array( 'pms-view' => 'canceled', 'paged' => 1 ) ) ) . '" ' . ( isset( $_GET['pms-view'] ) && $_GET['pms-view'] == 'canceled' ? 'class="current"' : '' ) . '>Canceled <span class="count">(' . ( isset( $this->views_count['canceled'] ) ? $this->views_count['canceled'] : '' ) . ')</span></a>',
            'expired'   => '<a href="' . esc_url( add_query_arg( array( 'pms-view' => 'expired', 'paged' => 1 ) ) ) . '" ' . ( isset( $_GET['pms-view'] ) && $_GET['pms-view'] == 'expired' ? 'class="current"' : '' ) . '>Expired <span class="count">(' . ( isset( $this->views_count['expired'] ) ? $this->views_count['expired'] : '' ) . ')</span></a>',
            'pending'   => '<a href="' . esc_url( add_query_arg( array( 'pms-view' => 'pending', 'paged' => 1 ) ) ) . '" ' . ( isset( $_GET['pms-view'] ) && $_GET['pms-view'] == 'pending' ? 'class="current"' : '' ) . '>Pending <span class="count">(' . ( isset( $this->views_count['pending'] ) ? $this->views_count['pending'] : '' ) . ')</span></a>',
            'abandoned' => '<a href="' . esc_url( add_query_arg( array( 'pms-view' => 'abandoned', 'paged' => 1 ) ) ) . '" ' . ( isset( $_GET['pms-view'] ) && $_GET['pms-view'] == 'abandoned' ? 'class="current"' : '' ) . '>Abandoned <span class="count">(' . ( isset( $this->views_count['abandoned'] ) ? $this->views_count['abandoned'] : '' ) . ')</span></a>',
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
            wp_nonce_field( 'pms_bulk_delete_subscription_nonce', '_wpnonce', false );

            $this->extra_tablenav( $which );
            if ( $which == 'bottom' )
                $this->pagination( $which );

            echo '<br class="clear" />';
        echo '</div>';

    }


    /*
     * Method to add extra actions before and after the table
     * Replaces parent method
     *
     * @param string @which     - which side of the table ( top or bottom )
     *
     */
    public function extra_tablenav( $which ) {

        if( $which == 'bottom' || ( isset( $_GET['pms-view'] ) && $_GET['pms-view'] == 'abandoned' ) )
            return;


    }


    /*
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
         * Set member arguments
         *
         */
        $args['number'] = $this->items_per_page;
        $args['offset'] = ( $paged - 1 ) * $this->items_per_page;
        $args['member_subscription_status'] = $selected_view;

        // Search query
        if ( ! empty($_REQUEST['s']) ) {
            $args['search'] = sanitize_text_field( $_REQUEST['s'] );
        }

        // Order by query
        if( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {

            if( $_REQUEST['orderby'] == 'user_id' )
                $args['orderby'] = 'ID';

            if( $_REQUEST['orderby'] == 'username' )
                $args['orderby'] = 'user_login';

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

        if( !empty( $_GET[ 'pms-filter-payment-gateway' ] ) ){
            $args[ 'payment_gateway' ] = sanitize_text_field( $_GET[ 'pms-filter-payment-gateway' ] );
        }

        if( !empty( $_GET[ 'pms-filter-start-date' ] ) ){

            if( $_GET[ 'pms-filter-start-date' ] != 'custom' ){

                $args[ 'start_date_end' ] = date( 'Y-m-d 23:59:59', strtotime( 'today' ) );

                if( $_GET[ 'pms-filter-start-date' ] == 'last_week' ){
                    $args[ 'start_date_beginning' ] = date( 'Y-m-d 23:59:59', strtotime( 'today - 1 week' ) );
                }
                else if( $_GET[ 'pms-filter-start-date' ] == 'last_month' ){
                    $args[ 'start_date_beginning' ] = date( 'Y-m-d 23:59:59', strtotime( 'today - 1 month' ) );
                }
                else{
                    $args[ 'start_date_beginning' ] = date( 'Y-m-d 00:00:00', strtotime( 'first day of last year' ) );
                    $args[ 'start_date_end' ] = date( 'Y-m-d 23:59:59', strtotime( 'last day of December last year' ) );
                }
            }
            else{
                if( !empty( $_GET[ 'pms-datepicker-start-date-beginning' ] ) ){
                    $args[ 'start_date_beginning' ] = date( 'Y-m-d 00:00:00', strtotime( sanitize_text_field( $_GET[ 'pms-datepicker-start-date-beginning' ] ) ) );
                }
                if( !empty( $_GET[ 'pms-datepicker-start-date-end' ] ) ){
                    $args[ 'start_date_end' ] = date( 'Y-m-d 23:59:59', strtotime( sanitize_text_field( $_GET[ 'pms-datepicker-start-date-end' ] ) ) );
                }
            }
        }

        if( !empty( $_GET['pms-filter-expiration-date'] ) ){

            if( $_GET[ 'pms-filter-expiration-date' ] != 'custom' ){

                if( $_GET[ 'pms-filter-expiration-date' ] == 'today' ){
                    $args[ 'expiration_date_beginning' ] = date( 'Y-m-d H:i:s', strtotime( 'today' ) );
                    $args[ 'expiration_date_end' ] = date( 'Y-m-d 23:59:59', strtotime('today' ) );
                }
                else if( $_GET[ 'pms-filter-expiration-date' ] == 'tomorrow' ){
                    $args[ 'expiration_date_beginning' ] = date( 'Y-m-d H:i:s', strtotime( 'tomorrow' ) );
                    $args[ 'expiration_date_end' ] = date( 'Y-m-d 23:59:59', strtotime( 'tomorrow' ) );
                }
                else if( $_GET[ 'pms-filter-expiration-date' ] == 'this_week' ){
                    $args[ 'expiration_date_beginning' ] = date( 'Y-m-d H:i:s', strtotime( 'Monday this week' ) );
                    $args[ 'expiration_date_end' ] = date( 'Y-m-d 23:59:59', strtotime( 'this Sunday' ) );
                }
                else{
                    $args[ 'expiration_date_beginning' ] = date( 'Y-m-d 00:00:00', strtotime( 'first day of this month' ) );
                    $args[ 'expiration_date_end' ] = date( 'Y-m-d 23:59:59', strtotime( 'last day of this month' ) );
                }
            }
            else{
                if( !empty( $_GET[ 'pms-datepicker-expiration-date-beginning' ] ) ){
                    $args[ 'expiration_date_beginning' ] = date( 'Y-m-d 00:00:00', strtotime( sanitize_text_field( $_GET[ 'pms-datepicker-expiration-date-beginning' ] ) ) );
                }
                if( !empty( $_GET[ 'pms-datepicker-expiration-date-end' ] ) ){
                    $args[ 'expiration_date_end' ] = date( 'Y-m-d 23:59:59', strtotime( sanitize_text_field( $_GET[ 'pms-datepicker-expiration-date-end' ] ) ) );
                }
            }
        }


        // Get the members
        $members = pms_get_members( $args );

        // Set views count array to 0, we use this to display the count
        // next to the views links (all, active, expired, etc)
        $views = $this->get_views();

        foreach( $views as $view_slug => $view_link) {

            $args['member_subscription_status'] = ( $view_slug != 'all' ? $view_slug : '' );

            $this->views_count[$view_slug] = pms_get_members( $args, true );

        }

        // Get the current view to filter results
        $selected_view = ( isset( $_GET['pms-view'] ) ? sanitize_text_field( $_GET['pms-view'] ) : '' );

        foreach( $members as $member ) {

            $member_subscriptions = pms_get_member_subscriptions( array( 'user_id' => $member->user_id, 'include_abandoned' => true ) );

            $user_meta = get_user_meta( absint( $member->user_id ) );
            $member_name = $user_meta['first_name'][0] . ' ' . $user_meta['last_name'][0];

            $data[] = apply_filters( 'pms_members_list_table_entry_data', array(
                'user_id'           => $member->user_id,
                'username'          => '<strong><a href="' . esc_url( add_query_arg( array( 'subpage' => 'edit_member', 'member_id' => $member->user_id ), admin_url( 'admin.php?page=pms-members-page' ) ) ) . '">' . esc_attr( $member->username ) . '</a></strong>',
                'name'              => $member_name,
                'email'             => $member->email,
                'subscriptions'     => $member_subscriptions
            ), $member );

        }

        /**
         * Set all items
         *
         */
        if( empty( $this->views_count ) )
            $this->total_items = count( $members );
        else
            $this->total_items = $this->views_count[ ( ! empty( $selected_view ) ? $selected_view : 'all' ) ];

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


    /*
     * Return data that will be displayed in each column
     *
     * @param array $item           - data for the current row
     * @param string $column_name   - name of the current column
     *
     * @return string
     *
     */
    public function column_default( $item, $column_name ) {

        return !empty( $item[ $column_name ] ) ? $item[ $column_name ] : '';

    }


    /*
     * Return data that will be displayed in the username column
     *
     * @param array $item   - row data
     *
     * @return string
     *
     */
    public function column_username( $item ) {

        $actions = array();

        // Add an edit user action for each member
        $actions['edit'] = '<a href="' . esc_url( add_query_arg( array( 'subpage' => 'edit_member', 'member_id' => $item['user_id'] ), admin_url( 'admin.php?page=pms-members-page' ) ) ) . '">' . esc_html__( 'Edit Member', 'paid-member-subscriptions' ) . '</a>';

        // Return value saved for username and also the row actions
        return $item['username'] . $this->row_actions( apply_filters( 'pms_members_list_username_actions', $actions, $item ) );

    }


    /**
     * Return data that will be displayed in the subscriptions column
     *
     * @param array $item   - row data
     *
     * @return string
     *
     */
    public function column_subscriptions( $item ) {

        $output = '';

        $abandon_count = 0;
        $other_count   = 0;
        $user_id       = 0;

        foreach( $item['subscriptions'] as $member_subscription ) {

            if( ( !isset( $_GET['pms-view'] ) || $_GET['pms-view'] != 'abandoned' ) && $member_subscription->status == 'abandoned' ){
                $abandon_count++;
                continue;
            } else if( isset( $_GET['pms-view'] ) && $_GET['pms-view'] == 'abandoned' && $member_subscription->status != 'abandoned' )
                continue;

            //$member_subscription = $member_subscription->to_array();

            $subscription_plan = pms_get_subscription_plan( $member_subscription->subscription_plan_id );
            $other_count++;

            $output .= '<span class="pms-member-list-subscription pms-has-bubble">';

                $output .= '<a href="' . esc_url( add_query_arg( array( 'subpage' => 'edit_subscription', 'subscription_id' => $member_subscription->id ) ) ) . '">';
                    $output .= apply_filters( 'pms_list_table_' . $this->_args['plural'] . '_show_status_dot', '<span class="pms-status-dot ' . esc_attr( $member_subscription->status ) . '"></span>' );

                    $output .= ( !empty( $subscription_plan->id ) ? $subscription_plan->name : sprintf( esc_html__( 'Subscription Plan Not Found - ID: %s', 'paid-member-subscriptions' ), $member_subscription->subscription_plan_id ) );
                $output .= '</a>';

                $output .= '<div class="pms-bubble">';

                    $statuses = pms_get_member_subscription_statuses();

                    $output .= '<div><span class="alignleft">' . esc_html__( 'Start date', 'paid-member-subscriptions' ) . '</span><span class="alignright">' . date( get_option( 'date_format' ), strtotime( pms_sanitize_date( $member_subscription->start_date ) ) ) . '</span></div>';
                    $output .= '<div><span class="alignleft">' . esc_html__( 'Expiration date', 'paid-member-subscriptions' ) . '</span><span class="alignright">' . ( ! empty( $member_subscription->expiration_date ) ? date( get_option( 'date_format' ), strtotime( pms_sanitize_date( $member_subscription->expiration_date ) ) ) : ( !empty( $member_subscription->billing_next_payment ) ? date( get_option( 'date_format' ), strtotime( pms_sanitize_date( $member_subscription->billing_next_payment ) ) ) : __( 'Unlimited', 'paid-member-subscriptions' ) ) ) . '</span></div>';
                    $output .= '<div><span class="alignleft">' . esc_html__( 'Status', 'paid-member-subscriptions' ) . '</span><span class="alignright">' . ( isset( $statuses[ $member_subscription->status ] ) ? $statuses[ $member_subscription->status ] : '' ) . '</span></div>';

                    if( pms_payment_gateways_support( pms_get_active_payment_gateways(), 'recurring_payments' ) )
                        $output .= '<div><span class="alignleft">' . esc_html__( 'Auto-renewing', 'paid-member-subscriptions' ) . '</span><span class="alignright">' . ( $member_subscription->is_auto_renewing() ? esc_html__( 'Yes', 'paid-member-subscriptions' ) : esc_html__( 'No', 'paid-member-subscriptions' ) ) . '</span></div>';

                    if( pms_payment_gateways_support( pms_get_active_payment_gateways(), 'subscription_free_trial' ) && !empty( $member_subscription->trial_end ) && strtotime( $member_subscription->trial_end ) > time() )
                        $output .= '<div><span class="alignleft">' . esc_html__( 'Active Trial', 'paid-member-subscriptions' ) . '</span> <span class="alignright">'. esc_html__( 'Yes', 'paid-member-subscriptions' ) .'</span></div>';

                $output .= '</div>';

            $output .= '</span>';


        }

        if( !empty( $abandon_count ) ){
            foreach( $item['subscriptions'] as $member_subscription ){
                $user_id = $member_subscription->user_id;
                break;
            }
            $sign = '';

            if( $other_count > 0 )
                $sign = '+';

            $output .= '<a href="' . esc_url( add_query_arg( array( 'subpage' => 'edit_member', 'member_id' => $user_id ) ) ) . '" title="'.esc_html__( 'View Abandoned Subscriptions', 'paid-member-subscriptions' ).'" class="pms-abandon-count">';
                $output .= sprintf( _n( '%1$s %2$s abandoned subscription', '%1$s %2$s abandoned subscriptions', $abandon_count, 'paid-member-subscriptions' ), $sign, $abandon_count );
            $output .= '</a>';
        }

        return $output;

    }


    /*
     * Display if no items are found
     *
     */
    public function no_items() {

        echo esc_html__( 'No members found', 'paid-member-subscriptions' );

    }

}
