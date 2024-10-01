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
Class PMS_Members_Add_New_Bulk_List_Table extends WP_List_Table {

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

    /*
     * Constructor function
     *
     */
    public function __construct() {

        add_filter( 'pms_filter_users_table_data', array( $this, 'pms_filter_users' ));

        parent::__construct( array(
            'singular'  => 'member-add-new-bulk',
            'plural'    => 'members-add-new-bulk',
            'ajax'      => false
        ));

        // Set items per page
        $items_per_page = get_user_meta( get_current_user_id(), 'pms_users_per_page', true );

        if( empty( $items_per_page ) ) {
            $screen         = get_current_screen();
            $per_page       = $screen->get_option( 'per_page' );
            $items_per_page = $per_page['default'];
        }

        $this->items_per_page = $items_per_page;

        // Set data
        $this->set_table_data();

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
            'cb'       => '<input type="checkbox" />',
            'user_id'  => esc_html__( 'User ID', 'paid-member-subscriptions' ),
            'username' => esc_html__( 'Username', 'paid-member-subscriptions' ),
            'email'    => esc_html__( 'E-mail', 'paid-member-subscriptions' ),
            'role'     => esc_html__( 'Role', 'paid-member-subscriptions' )
        );

        return $columns;

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

        return array();

    }


    /*
     * Method to add extra actions before and after the table
     * Replaces parent method
     *
     * @param string @which     - which side of the table ( top or bottom )
     *
     */
    public function extra_tablenav( $which ) {

        if( $which == 'bottom' ) {

            $subscription_plans = pms_get_subscription_plans();

            echo '<div class="cozmoslabs-form-field-wrapper">';
                echo '<label class="cozmoslabs-form-field-label" for="pms_add_member_bulk_subscription_plan">'. esc_html__( 'Subscription Plan', 'paid-member-subscriptions' ) .'</label>';
                echo '<select id="pms_add_member_bulk_subscription_plan" name="subscription_plan_id">';

                    echo '<option value="-1">' . esc_html__( 'Select Subscription Plan...', 'paid-member-subscriptions' ) . '</option>';

                    if( !empty( $subscription_plans ) ) {
                        foreach( $subscription_plans as $subscription_plan )
                            echo '<option value="' . esc_attr( $subscription_plan->id ) . '">' . esc_html( $subscription_plan->name ) . '</option>';
                    }

                echo '</select>';
            echo '</div>';


            echo '<div class="cozmoslabs-form-field-wrapper">';
                echo '<label class="cozmoslabs-form-field-label" for="pms_add_member_bulk_subscription_status">'. esc_html__( 'Status', 'paid-member-subscriptions' ) .'</label>';
                echo '<select id="pms_add_member_bulk_subscription_status" name="subscription_status" title="'. esc_html__( 'Select Subscription Status', 'paid-member-subscriptions' ) .'">';

                    echo '<option value="" disabled>'. esc_html__( 'Select Subscription Status...', 'paid-member-subscriptions' ) .'</option>';
                    echo '<option value="active">' . esc_html__( 'Active', 'paid-member-subscriptions' ) . '</option>';
                    echo '<option value="pending">' . esc_html__( 'Pending', 'paid-member-subscriptions' ) . '</option>';
                    echo '<option value="expired">' . esc_html__( 'Expired', 'paid-member-subscriptions' ) . '</option>';

                echo '</select>';
            echo '</div>';

            echo '<div class="cozmoslabs-form-field-wrapper">';
                echo '<label class="cozmoslabs-form-field-label" for="pms_add_member_bulk_subscription_expiration_date">'. esc_html__( 'Expiration Date', 'paid-member-subscriptions' ) .'</label>';
                echo '<input id="pms_add_member_bulk_subscription_expiration_date" type="text" name="subscription_expiration_date" class="datepicker pms-subscription-field" value="" />';

            echo '</div>';

            submit_button( esc_html__( 'Assign', 'paid-member-subscriptions' ), 'primary', 'pms_add_member_bulk_assign', false );

        }

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

        // If it's a search query send search parameter through $args
        if ( !empty($_REQUEST['s']) ) {
            $args = array(
                'order'                => 'ASC',
                'orderby'              => 'ID',
                'offset'               => '',
                'limit'                => '',
                'search'               => sanitize_text_field( $_REQUEST['s'] )
            );
        }

        // Get users
        $users = pms_get_users_non_members( $args );

        // Set views count array to 0, we use this to display the count
        // next to the views links
        $views = $this->get_views();
        if( !empty( $views ) ) {
            foreach( $views as $view_slug => $view_link) {
                $this->views_count[$view_slug] = 0;
            }

            // Get the current view to filter results
            $selected_view = ( isset( $_GET['pms-view'] ) ? sanitize_text_field( $_GET['pms-view'] ) : '' );
        }


        foreach( $users as $user ) {

            $usr = get_userdata( $user['id'] );

            $checkbox = '<label class="screen-reader-text" for="user_' . esc_attr( $usr->data->ID ) . '">' . sprintf( esc_html__( 'Select %s', 'paid-member-subscriptions' ), esc_html( $usr->data->user_login ) ) . '</label>'
                . "<input type='checkbox' name='users[]' id='user_". esc_attr( $usr->data->ID ) ."' value='". esc_attr( $usr->data->ID ) ."' />";

            if( !empty( $usr->roles ) ){
                $role = array_values( $usr->roles );

                if( !empty( $role[0] ) )
                    $role = $role[0];
            }

            $data[] = array(
                'cb'                => $checkbox,
                'user_id'           => $usr->data->ID,
                'username'          => $usr->data->user_login,
                'email'             => $usr->data->user_email,
                'role'              => ( ! empty( $role ) ? $role : '' )
            );
        }

        $data = apply_filters( 'pms_filter_users_table_data', $data );

        $this->data = $data;

    }



    /*
     * Populates the items for the table
     *
     * @param array $item           - data for the current row
     *
     * @return string
     *
     */
    public function prepare_items() {

        $columns        = $this->get_columns();
        $hidden_columns = $this->get_hidden_columns();
        $sortable       = $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden_columns, $sortable );

        $data = $this->data;
        usort( $data, array( $this, 'sort_data' ) );

        $paged = ( isset( $_GET['paged'] ) ? (int)$_GET['paged'] : 1 );

        $this->set_pagination_args( array(
            'total_items' => count( $data ),
            'per_page'    => $this->items_per_page
        ) );

        $data = array_slice( $data, $this->items_per_page * ( $paged-1 ), $this->items_per_page );

        $this->items = $data;

    }


    /*
     * Sorts the data by the variables in GET
     *
     */
    public function sort_data( $a, $b ) {

        // Set defaults
        $orderby = 'username';
        $order   = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = sanitize_text_field( $_GET['orderby'] );
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = sanitize_text_field( $_GET['order'] );
        }

        $result = strnatcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;

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

        return $item[ $column_name ];

    }


    /*
     * Return data that will be displayed in the cb ( checkbox ) column
     *
     * @param array $item   - row data
     *
     * @return string
     *
     */
    public function column_cb( $item ) {

        return $item['cb'];

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
        $actions['view_user'] = '<a href="' . esc_url( add_query_arg( array( 'user_id' => $item['user_id']), admin_url('user-edit.php') ) ) . '">' . esc_html__( 'Edit User', 'paid-member-subscriptions' ) . '</a>';

        // Return value saved for username and also the row actions
        return esc_html( $item['username'] ) . $this->row_actions( $actions );

    }


    /*
     * Return data that will be displayed in the subscriptions column
     *
     * @param array $item   - row data
     *
     * @return string
     *
     */
    public function column_subscriptions( $item ) {

        $output = '';

        foreach( $item['subscriptions'] as $member_subscription ) {

            $subscription_plan = pms_get_subscription_plan( $member_subscription['subscription_plan_id'] );

            $output .= '<span class="pms-member-list-subscription pms-has-bubble">';

                $output .= apply_filters( 'pms_list_table_' . $this->_args['plural'] . '_show_status_dot', '<span class="pms-status-dot ' . esc_attr( $member_subscription['status'] ) . '"></span>' );

                $output .= ( !empty( $subscription_plan->id ) ? esc_html( $subscription_plan->name ) : sprintf( esc_html__( 'Subscription Plan Not Found - ID: %s', 'paid-member-subscriptions' ), $member_subscription['subscription_plan_id'] ) );

                $output .= '<div class="pms-bubble">';

                    $statuses = pms_get_member_subscription_statuses();

                    $output .= '<div><span class="alignleft">' . 'Start date' . '</span><span class="alignright">' . pms_sanitize_date( $member_subscription['start_date'] ) . '</span></div>';
                    $output .= '<div><span class="alignleft">' . 'Expiration date' . '</span><span class="alignright">' . pms_sanitize_date( $member_subscription['expiration_date'] ) . '</span></div>';
                    $output .= '<div><span class="alignleft">' . 'Status' . '</span><span class="alignright">' .( isset( $statuses[ $member_subscription['status'] ] ) ? esc_html( $statuses[ $member_subscription['status'] ] ) : '' ) . '</span></div>';

                $output .= '</div>';

            $output .= '</span>';

        }

        return $output;

    }


    /*
     * Return filtered users list if any filtering options are present
     *
     */
    public function pms_filter_users( $data ) {

        if ( !empty( $_POST['pms-filter-user-role'] )) {

            foreach ( $data as $key => $args ) {
                if ( $args['role'] != sanitize_text_field( $_POST['pms-filter-user-role'] ))
                    unset( $data[$key] );
            }

        }

        return $data;
    }


    /*
     * Display if no items are found
     *
     */
    public function no_items() {

        echo esc_html__( 'No users found', 'paid-member-subscriptions' );

    }

}
