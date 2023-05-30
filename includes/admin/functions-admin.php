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

function pms_compare_subscription_plan_objects($a, $b) {
    return strcmp( $a->name, $b->name );
}


// add filters to match WP Date Format if PMS -> Misc -> Others -> "WordPress Date Format" setting is Enabled
$misc_settings = get_option( 'pms_misc_settings', array() );
if ( isset( $misc_settings['match-wp-date-format'] ) ) {
    add_filter( 'pms_match_date_format_to_wp_settings', 'pms_match_date_format', 10, 2 );
    add_filter( 'post_date_column_time', 'pms_cpt_last_modified_date_fromat', 10, 4 );
}

/**
 * Function that changes the date format to match the one set in Wordpress --> Settings --> General
 *
 * @param $date - date or timestamp
 * @param $display_time - true/false for displaying the time along with the date
 *
 */
function pms_match_date_format( $date , $display_time ) {

    if ( $display_time )
        $wp_time_format = get_option( 'time_format' );
    else $wp_time_format = '';

    if ( !empty( $date )) {
        $wp_date_format = get_option( 'date_format' );
        $timestamp = ( strtotime( $date )) ? strtotime( $date ) : $date;
        $date = ucfirst( wp_date( $wp_date_format . ' ' .  $wp_time_format, $timestamp ));
    }

    return $date;

}

// Subscription Plans List
// change Last Modified date format to match the one set in Wordpress --> Settings --> General
function pms_cpt_last_modified_date_fromat( $published_time, $post, $column_name, $display_mode ) {

    if ( !isset( $_GET['post_type'] ) || $_GET['post_type'] != 'pms-subscription' )
        return $published_time;

    $post_date = get_the_modified_date( get_option( 'date_format' ), $post );
    $post_time = get_post_modified_time( get_option('time_format'), $post );

    return $post_date . ' at ' . $post_time;
}

// add filter for Misc -> Others -> Always show Subscriptions Expiration Date option
$misc_settings = get_option( 'pms_misc_settings', array() );
if ( isset( $misc_settings['force-subscriptions-expiration-date'] ) ) {
    add_filter( 'pms_view_add_new_edit_subscription_hide_expiration_date', '__return_false' );
}

/**
 * Generate the Form Designs Preview Showcase
 *
 */
function pms_display_form_designs_preview() {

    wp_enqueue_script( 'jquery-ui-dialog' );

    $form_designs_data = array(
        array(
            'id' => 'form-style-default',
            'name' => 'Default',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'/assets/images/pms-fd-style-default.jpg',
            ),
        ),
        array(
            'id' => 'form-style-1',
            'name' => 'Style 1',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'/assets/images/pms-fd-style1-slide1.jpg',
                'slide1' => PMS_PLUGIN_DIR_URL.'/assets/images/pms-fd-style1-slide2.jpg',
            ),
        ),
        array(
            'id' => 'form-style-2',
            'name' => 'Style 2',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'/assets/images/pms-fd-style2-slide1.jpg',
                'slide1' => PMS_PLUGIN_DIR_URL.'/assets/images/pms-fd-style2-slide2.jpg',
            ),
        ),
        array(
            'id' => 'form-style-3',
            'name' => 'Style 3',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'/assets/images/pms-fd-style3-slide1.jpg',
                'slide1' => PMS_PLUGIN_DIR_URL.'/assets/images/pms-fd-style3-slide2.jpg',
            ),
        )
    );

    $output = '<div id="pms-forms-design-browser">';

    foreach ( $form_designs_data as $form_design ) {

        if ( $form_design['id'] != 'form-style-default' )
            $preview_button = '<div class="pms-forms-design-preview" id="'. $form_design['id'] .'-info">Preview</div>';
        else $preview_button = '';

        $output .= '
                <div class="pms-forms-design" id="'. $form_design['id'] .'">
                   <div class="pms-forms-design-screenshot">
                      <img src="' . $form_design['images']['main'] . '" alt="Form Design">
                      '. $preview_button .'
                   </div>
                   <div class="pms-forms-design-details">
                      <div class="pms-forms-design-title" style="border:none;">
                         <h2>'. $form_design['name'] .'</h2>
                      </div>

                   </div>
                </div>
        ';

        $img_count = 0;
        $image_list = '';
        foreach ( $form_design['images'] as $image ) {
            $img_count++;
            $active_img = ( $img_count == 1 ) ? ' active' : '';
            $image_list .= '<img class="pms-forms-design-preview-image'. $active_img .'" src="'. $image .'">';
        }

        if ( $img_count > 1 ) {
            $previous_button = '<div class="pms-slideshow-button pms-forms-design-sildeshow-previous disabled" data-theme-id="'. $form_design['id'] .'" data-slideshow-direction="previous"> < </div>';
            $next_button = '<div class="pms-slideshow-button pms-forms-design-sildeshow-next" data-theme-id="'. $form_design['id'] .'" data-slideshow-direction="next"> > </div>';
            $justify_content = 'space-between';
        }
        else {
            $previous_button = $next_button = '';
            $justify_content = 'center';
        }

        $output .= '<div id="pms-modal-'. $form_design['id'] .'" class="pms-forms-design-modal" title="'. $form_design['name'] .'">
                        <div class="pms-forms-design-modal-slideshow" style="justify-content: '. $justify_content .'">
                            '. $previous_button .'
                            <div class="pms-forms-design-modal-images">
                                '. $image_list .'
                            </div>
                            '. $next_button .'
                        </div>
                    </div>';

    }

    $output .= '</div>';

    return $output;
}