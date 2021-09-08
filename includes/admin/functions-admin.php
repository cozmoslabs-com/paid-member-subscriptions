<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Filter allowed screen options from the plugin
add_filter( 'set-screen-option', 'pms_admin_set_screen_option', 20, 3 );
function pms_admin_set_screen_option( $status, $option, $value ){

    $per_page_options = array(
        'pms_members_per_page',
        'pms_payments_per_page',
        'pms_users_per_page'
    );

    if( in_array( $option, $per_page_options ) )
        return $value;

    return $status;

}

// Specific option filter since WordPress 5.4.2
// Other filters are added through the PMS_Submenu_Page class, but since the bulk add members is not a submenu page, we add this here
add_filter( 'set_screen_option_pms_users_per_page', 'pms_admin_bulk_add_members_screen_option', 20, 3 );
function pms_admin_bulk_add_members_screen_option( $status, $option, $value ){

    if( $option == 'pms_users_per_page' )
        return $value;

    return $status;

}

add_filter( 'admin_init', 'pms_reset_cron_jobs' );
function pms_reset_cron_jobs(){

    if( !isset( $_GET['pms_reset_cron_jobs'] ) || $_GET['pms_reset_cron_jobs'] != 'true' || !isset( $_GET['_wpnonce'] ) )
        return;

    if( !wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'pms_reset_cron_jobs' ) )
        return;

    // Remove all cron jobs
    wp_clear_scheduled_hook( 'pms_cron_process_member_subscriptions_payments' );
    wp_clear_scheduled_hook( 'pms_check_subscription_status' );
    wp_clear_scheduled_hook( 'pms_cron_process_pending_payments' );
    wp_clear_scheduled_hook( 'pms_remove_activation_key' );

    // Process payments for custom member subscriptions
    if( !wp_next_scheduled( 'pms_cron_process_member_subscriptions_payments' ) )
        wp_schedule_event( time(), 'daily', 'pms_cron_process_member_subscriptions_payments' );

    // Schedule event for checking subscription status
    if( !wp_next_scheduled( 'pms_check_subscription_status' ) )
        wp_schedule_event( time(), 'daily', 'pms_check_subscription_status' );

    // Schedule event for setting old payments to failed
    if( !wp_next_scheduled( 'pms_cron_process_pending_payments' ) )
        wp_schedule_event( time(), 'daily', 'pms_cron_process_pending_payments' );

    //Schedule event for deleting expired activation keys used for password reset
    if( !wp_next_scheduled( 'pms_remove_activation_key' ) )
        wp_schedule_event( time(), 'daily', 'pms_remove_activation_key' );

    $url = remove_query_arg( array(
        'pms_reset_cron_jobs',
        '_wpnonce'
    ));

    wp_safe_redirect( add_query_arg( 'sucess_notice', '1', $url ) );
    exit;

}

add_action( 'admin_notices', 'pms_show_admin_notice_success_by_get' );
function pms_show_admin_notice_success_by_get(){

    if( isset( $_GET['page'] ) && $_GET['page'] == 'pms-settings-page' && isset( $_GET['sucess_notice'] ) && $_GET['sucess_notice'] == '1' )
        echo '<div class="updated"><p>' . esc_html__( 'Completed successfully.', 'paid-member-subscriptions' ) . '</p></div>';

}
