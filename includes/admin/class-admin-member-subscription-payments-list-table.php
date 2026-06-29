<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// WP_List_Table is not loaded automatically in the plugins section
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/*
 * Extent WP default list table for payments linked to a member subscription
 *
 */
Class PMS_Member_Subscription_Payments_List_Table extends PMS_Member_Payments_List_Table {

    /**
     * Payments per page
     *
     * @access public
     * @var int
     */
    public $items_per_page = 10;

    /**
     * The current member subscription ID
     *
     * @access private
     * @var int
     */
    private $member_subscription_id = 0;

    /**
     * The total number of items
     *
     * @access private
     * @var int
     */
    private $total_items = 0;

    /*
     * Constructor function
     *
     */
    public function __construct( $member_subscription_id = 0 ) {

        parent::__construct();

        $this->member_subscription_id = absint( $member_subscription_id );

        if( empty( $this->member_subscription_id ) && isset( $_GET['subscription_id'] ) )
            $this->member_subscription_id = absint( $_GET['subscription_id'] );

        $items_per_page = get_user_meta( get_current_user_id(), 'pms_payments_per_page', true );

        if( !empty( $items_per_page ) )
            $this->items_per_page = absint( $items_per_page );

    }

    /*
     * Overwrites the parent class.
     * Define the columns for the payments
     *
     * @return array
     *
     */
    public function get_columns() {

        $columns = array(
            'id'      => __( 'Payment ID', 'paid-member-subscriptions' ),
            'amount'  => __( 'Amount', 'paid-member-subscriptions' ),
            'date'    => __( 'Date', 'paid-member-subscriptions' ),
            'type'    => __( 'Type', 'paid-member-subscriptions' ),
            'status'  => __( 'Status', 'paid-member-subscriptions' ),
            'actions' => __( 'Actions', 'paid-member-subscriptions' )
        );

        return apply_filters( 'pms_member_subscription_payments_list_table_columns', $columns );

    }

    /*
     * Returns the table data
     *
     * @return array
     *
     */
    public function get_table_data() {

        $data = array();

        if( empty( $this->member_subscription_id ) )
            return $data;

        $paged = ( isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
        $paged = max( 1, $paged );

        $args = array(
            'member_subscription_id' => $this->member_subscription_id,
            'order'                  => 'DESC',
            'orderby'                => 'id',
            'number'                 => $this->items_per_page,
            'offset'                 => ( $paged - 1 ) * $this->items_per_page
        );

        $payments = pms_get_payments( $args );

        foreach( $payments as $payment ) {

            $payment_currency = !empty( $payment->currency ) ? $payment->currency : pms_get_active_currency();

            $data[] = apply_filters( 'pms_member_subscription_payments_list_table_entry_data', array(
                'id'      => $payment->id,
                'amount'  => pms_format_price( $payment->amount, $payment_currency ),
                'date'    => apply_filters( 'pms_match_date_format_to_wp_settings', ucfirst( date_i18n( 'F d, Y H:i:s', strtotime( $payment->date ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ), true, $payment->date ),
                'type'    => pms_get_payment_type_name( $payment->type ),
                'status'  => ucfirst( $payment->status ),
                'actions' => $payment->id
            ), $payment );

        }

        $this->total_items = pms_get_payments_count( array( 'member_subscription_id' => $this->member_subscription_id ) );

        return $data;

    }

    /*
     * Populates the items for the table
     *
     */
    public function prepare_items() {

        parent::prepare_items();

        $this->set_pagination_args( array(
            'total_items' => $this->total_items,
            'per_page'    => $this->items_per_page
        ) );

    }

    /*
     * Overwrite parent display tablenav to show only pagination controls
     *
     * @param string $which - which side of the table ( top or bottom )
     *
     */
    protected function display_tablenav( $which ) {

        if( $which == 'top' )
            return;

        echo '<div class="tablenav ' . esc_attr( $which ) . '">';

        $this->pagination( $which );

        echo '<br class="clear" />';
        echo '</div>';

    }

    /*
     * Return data that will be displayed in the actions column
     *
     * @param array $item   - data of the current row
     *
     * @return string
     *
     */
    public function column_actions( $item ) {

        $actions = '<a class="button-secondary" href="' . esc_url( add_query_arg( array( 'page' => 'pms-payments-page', 'pms-action' => 'edit_payment', 'payment_id' => $item['actions'] ), admin_url( 'admin.php' ) ) ) . '">' . __( 'View Details', 'paid-member-subscriptions' ) . '</a>';

        return apply_filters( 'pms_member_subscription_payments_list_table_actions', $actions, $item );

    }

}
