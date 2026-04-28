<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Extends core PMS_Submenu_Page base class to create and add custom functionality
 * for the subscriptions section in the admin section
 *
 */
class PMS_Submenu_Page_Subscriptions extends PMS_Submenu_Page {

    public $list_table;

    public function init() {
        add_action( 'pms_output_content_submenu_page_' . $this->menu_slug, array( $this, 'output' ) );
        add_action( 'init', array( $this, 'process_data' ), 20 );
        add_action( 'pms_submenu_page_enqueue_admin_scripts_' . $this->menu_slug, array( $this, 'admin_scripts' ) );

        if( isset( $_GET['page'] ) && $_GET['page'] == 'pms-subscriptions-page' )
            add_action( 'current_screen', array( $this, 'load_table' ) );
    }

    /**
     * Instantiate the subscriptions list table
     *
     */
    public function load_table() {
        $this->list_table = new PMS_Subscriptions_List_Table();
    }

    /**
     * Output the subscriptions admin page template
     *
     */
    public function output() {
        include_once 'views/view-page-subscriptions.php';
    }

    /**
     * Process row and bulk actions for subscriptions
     *
     */
    public function process_data() {

        // These processes should be handled only by an admin
        if( ! pms_current_user_can_access_area( 'pms-subscriptions-page' ) )
            return;

        /**
         * Handle delete subscription
         *
         */
        if( !empty( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'pms_subscriptions_row_action_nonce' ) ) {

            if( empty( $_GET['subscription_id'] ) || empty( $_GET['pms-action'] ) || sanitize_text_field( $_GET['pms-action'] ) != 'delete_subscription' )
                return;

            $member_subscription = pms_get_member_subscription( (int)sanitize_text_field( $_GET['subscription_id'] ) );

            if( is_null( $member_subscription ) )
                return;

            $deleted = $member_subscription->remove();

            if( $deleted )
                $this->add_admin_notice( esc_html__( 'Member Subscription deleted successfully.', 'paid-member-subscriptions' ), 'updated' );

            return;

        }

        /**
         * Handle Bulk actions
         *
         */
        if( !empty( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'pms_subscriptions_bulk_action_nonce' ) && isset( $_REQUEST[ 'member_subscriptions' ] ) && !empty( $_REQUEST[ 'member_subscriptions' ] ) ) {

            $action = ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] != '-1' ? sanitize_text_field( $_REQUEST['action'] ) : ( isset( $_REQUEST['action2'] ) ? sanitize_text_field( $_REQUEST['action2'] ) : '' ) );

            if( $action !== 'pms_subscriptions_bulk_delete' && $action !== 'pms_subscriptions_bulk_cancel' )
                return;

            $deleted_subscriptions_count  = 0;
            $canceled_subscriptions_count = 0;
            $redirect_args                = array();

            // Handle bulk delete subscriptions
            if( $action == 'pms_subscriptions_bulk_delete' ){
                $subscription_ids = array_unique( array_filter( array_map( 'absint', $_REQUEST[ 'member_subscriptions' ] ) ) );

                foreach( $subscription_ids as $id ){

                    $member_subscription = pms_get_member_subscription( (int)$id );

                    if( !is_null( $member_subscription ) ){
                        $deleted = $member_subscription->remove();

                        if( $deleted )
                            $deleted_subscriptions_count++;

                    }
                }

                // Show success if any items were processed
                if( $deleted_subscriptions_count > 0 ) {
                    $redirect_args = array(
                        'message'       => 2,
                        'updated'       => 1,
                        'deleted_count' => $deleted_subscriptions_count,
                    );
                }

            }

            // Handle bulk cancel subscriptions
            if( $action == 'pms_subscriptions_bulk_cancel' ){
                $subscription_ids = array_unique( array_filter( array_map( 'absint', $_REQUEST[ 'member_subscriptions' ] ) ) );

                foreach( $subscription_ids as $id ){

                    $member_subscription = pms_get_member_subscription( (int)$id );

                    if( is_null( $member_subscription ) || $member_subscription->status === 'canceled' )
                        continue;

                    $confirm_remove_subscription = apply_filters( 'pms_confirm_cancel_subscription', true, $member_subscription->user_id, $member_subscription->subscription_plan_id );

                    // If all is good remove the subscription, if not send an error
                    if( ! $confirm_remove_subscription )
                        continue;

                    $subscription_data = array();
                    $subscription_data['status'] = 'canceled';

                    // If we have a billing payment date, set it as the expiration date and remove it
                    if( empty( $member_subscription->payment_profile_id ) && ! empty( $member_subscription->billing_next_payment ) ) {

                        $subscription_data['expiration_date']      = $member_subscription->billing_next_payment;
                        $subscription_data['billing_next_payment'] = '';

                    }

                    if( $member_subscription->update( $subscription_data ) ){
                        pms_add_member_subscription_log( $member_subscription->id, 'subscription_canceled_admin' );

                        pms_update_member_subscription_meta( $member_subscription->id, 'pms_retry_payment', 'inactive' );

                        /**
                         * Action for when the cancellation is successful
                         *
                         * @param array $member_data
                         * @param PMS_Member_Subscription $member_subscription
                         *
                         */
                        do_action( 'pms_cancel_member_subscription_successful', array(), $member_subscription );

                        $canceled_subscriptions_count++;

                    }
                }

                // Show success if any items were processed
                if( $canceled_subscriptions_count > 0 ) {
                    $redirect_args = array(
                        'message'        => 3,
                        'updated'        => 1,
                        'canceled_count' => $canceled_subscriptions_count,
                    );
                }
            }

            if( empty( $redirect_args ) )
                return;

            $redirect_url = $this->get_clean_bulk_action_redirect_url();
            $redirect_url = add_query_arg( $redirect_args, $redirect_url );

            wp_safe_redirect( esc_url_raw( $redirect_url ) );
            exit;

        }

    }

    /**
     * Build a clean redirect URL after bulk actions.
     *
     * Keeps only non-empty list state args and drops bulk/form noise.
     *
     * @return string
     */
    protected function get_clean_bulk_action_redirect_url() {

        $redirect_args = array(
            'page' => $this->menu_slug,
        );

        foreach( $_GET as $arg_name => $arg_value ) {
            if( !is_string( $arg_name ) || !is_scalar( $arg_value ) || !isset( $arg_value ) )
                continue;

            if( $arg_name === 'page' )
                continue;

            $is_allowed = in_array( $arg_name, array( 'pms-view', 'paged', 'orderby', 'order', 's' ), true )
                        || strpos( $arg_name, 'pms-filter-' ) === 0
                        || strpos( $arg_name, 'pms-datepicker-' ) === 0;

            if( !$is_allowed )
                continue;

            $arg_value = sanitize_text_field( wp_unslash( $arg_value ) );

            if( $arg_value === '' )
                continue;

            $redirect_args[ $arg_name ] = $arg_value;
        }

        return add_query_arg( $redirect_args, admin_url( 'admin.php' ) );

    }

    /**
     * Enqueue admin scripts
     *
     */
    public function admin_scripts() {

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'jquery-style', PMS_PLUGIN_DIR_URL . 'assets/css/admin/jquery-ui.min.css', array(), PMS_VERSION );

        $confirmation_message = array(
            'delete_confirmation' => __( 'Are you sure you want to delete these Subscriptions? \nThis action is irreversible.', 'paid-member-subscriptions' ),
            'no_selection'        => __( 'Please select at least one subscription.', 'paid-member-subscriptions' ),
        );

        // submenu-page-subscriptions-page.js is already enqueued by the parent class via the pms-subscriptions-page-js handle.
        wp_localize_script( $this->menu_slug . '-js', 'pms_subscriptions_delete_confirmation_message', $confirmation_message );

    }

    /**
     * Add Screen Options to Subscriptions page
     *
     */
    public function add_screen_options() {

        $args = array(
            'label'   => 'Subscriptions per page',
            'default' => 10,
            'option'  => 'pms_subscriptions_per_page'
        );

        add_screen_option( 'per_page', $args );

    }

    /**
     * Returns a custom message by the provided code.
     *
     * @param int $code
     *
     * @return string
     */
    protected function get_message_by_code( $code = 0 ) {

        $message = '';

        switch( absint( $code ) ) {
            case 2:
                $deleted_count = !empty( $_GET['deleted_count'] ) ? absint( $_GET['deleted_count'] ) : 0;
                $message = ( $deleted_count > 0 ? sprintf( _n( '%d Member Subscription successfully deleted.', '%d Member Subscriptions successfully deleted.', $deleted_count, 'paid-member-subscriptions' ), $deleted_count ) : '' );
                break;

            case 3:
                $canceled_count = !empty( $_GET['canceled_count'] ) ? absint( $_GET['canceled_count'] ) : 0;
                $message = ( $canceled_count > 0 ? sprintf( _n( '%d Member Subscription successfully canceled.', '%d Member Subscriptions successfully canceled.', $canceled_count, 'paid-member-subscriptions' ), $canceled_count ) : '' );
                break;

            default:
                break;
        }

        return $message;

    }
}

/**
 * Initialize the Subscriptions submenu page
 *
 */
function pms_init_subscriptions_page() {

    $pms_submenu_page_subscriptions = new PMS_Submenu_Page_Subscriptions(
        'paid-member-subscriptions',
        __( 'Subscriptions', 'paid-member-subscriptions' ),
        __( 'Subscriptions', 'paid-member-subscriptions' ),
        'manage_options',
        'pms-subscriptions-page',
        15,
        '',
        'pms_subscriptions_per_page'
    );

    $pms_submenu_page_subscriptions->init();
}
add_action( 'init', 'pms_init_subscriptions_page', 9 );
