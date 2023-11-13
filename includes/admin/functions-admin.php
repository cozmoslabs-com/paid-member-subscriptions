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
            'name' => 'Default Style',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'assets/images/pms-fd-style-default.jpg',
            ),
        ),
        array(
            'id' => 'form-style-1',
            'name' => 'Sublime',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'assets/images/pms-fd-style1-slide1.jpg',
                'slide1' => PMS_PLUGIN_DIR_URL.'assets/images/pms-fd-style1-slide2.jpg',
            ),
        ),
        array(
            'id' => 'form-style-2',
            'name' => 'Greenery',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'assets/images/pms-fd-style2-slide1.jpg',
                'slide1' => PMS_PLUGIN_DIR_URL.'assets/images/pms-fd-style2-slide2.jpg',
            ),
        ),
        array(
            'id' => 'form-style-3',
            'name' => 'Slim',
            'images' => array(
                'main' => PMS_PLUGIN_DIR_URL.'assets/images/pms-fd-style3-slide1.jpg',
                'slide1' => PMS_PLUGIN_DIR_URL.'assets/images/pms-fd-style3-slide2.jpg',
            ),
        )
    );

    $output = '<div id="pms-forms-design-browser">';

    foreach ( $form_designs_data as $form_design ) {

        if ( $form_design['id'] != 'form-style-default' )
            $preview_button = '<div class="pms-forms-design-preview button-secondary" id="'. $form_design['id'] .'-info">Preview</div>';
        else $preview_button = '';

        $output .= '
                <div class="pms-forms-design" id="'. $form_design['id'] .'">
                    <label>' . $form_design['name'] . '</label>
                   <div class="pms-forms-design-screenshot">
                        <img src="' . $form_design['images']['main'] . '" alt="Form Design">
                        '. $preview_button .'
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


/**
 * Register Version Form
 *
 */
function pms_add_register_version_form() {

    if ( !defined( 'PMS_PAID_PLUGIN_DIR' ) && PAID_MEMBER_SUBSCRIPTIONS !== 'Paid Member Subscriptions Dev' )
        return '';

    $status          = pms_get_serial_number_status();
    $license         = pms_get_serial_number();
    $license_details = get_option( 'pms_license_details', false );
    ?>
    
    <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-register-version">
        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Register Version ', 'paid-member-subscriptions' ) ?></h4>

        <form method="post" action="<?php echo !is_multisite() ? 'options.php' : 'edit.php'; ?>">
            <?php settings_fields( 'pms_serial_number' ); ?>
            <div class="cozmoslabs-form-field-wrapper cozmoslabs-form-field-serial-number">
                <label class="cozmoslabs-form-field-label" for="pms_serial_number"><?php esc_html_e( 'License key', 'paid-member-subscriptions' ); ?></label>
                <div class="cozmoslabs-serial-wrap__holder">
                    <input id="pms_serial_number" name="pms_serial_number" type="password" class="regular-text" value="<?php echo esc_attr( $license ); ?>" />
                    <?php wp_nonce_field( 'pms_license_nonce', 'pms_license_nonce' ); ?>
                    <?php if( $status !== false && $status == 'valid' ) {
                        $button_name =  'pms_edd_license_deactivate';
                        $button_value = __('Deactivate License', 'paid-member-subscriptions' );
                        if( empty( $details['invalid'] ) )
                            echo '<span title="'. esc_html__( 'Active on this site', 'paid-member-subscriptions' ) .'" class="pms-active-license dashicons dashicons-yes"></span>';
                        else
                            echo '<span title="'. esc_html__( 'Your license is invalid', 'paid-member-subscriptions' ) .'" class="pms-invalid-license dashicons dashicons-warning"></span>';
                    } else {
                        $button_name =  'pms_edd_license_activate';
                        $button_value = __('Activate License', 'paid-member-subscriptions');
                    }
                    ?>
                    <input type="submit" class="button-secondary" name="<?php echo esc_attr( $button_name ); ?>" value="<?php echo esc_attr( $button_value ); ?>"/>
                </div>

                <?php if( $status != 'expired' && ( !empty( $license_details ) && !empty( $license_details->expires ) && $license_details->expires !== 'lifetime' ) && ( ( !isset( $license_details->subscription_status ) || $license_details->subscription_status != 'active' ) && strtotime( $license_details->expires ) < strtotime( '+14 days' ) ) ) : ?>
                    <div class="cozmoslabs-description-container yellow">
                        <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'Your %s license is about to expire on %s', 'paid-member-subscriptions' ), '<strong>' . PAID_MEMBER_SUBSCRIPTIONS . '</strong>', '<strong>' . date_i18n( get_option( 'date_format' ), strtotime( $license_details->expires ) ) . '</strong>' ) ); ?>
                        <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'Please %sRenew Your Licence%s to continue receiving access to product downloads, automatic updates and support.', 'paid-member-subscriptions' ), "<a href='https://www.cozmoslabs.com/account/?utm_source=wpbackend&utm_medium=pms-settings-page&utm_campaign=PMS-Renewal' target='_blank'>", "</a>" ) ); ?></p>
                    </div>
                <?php elseif( $status == 'expired' ) : ?>
                    <div class="cozmoslabs-description-container red">
                        <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'Your %s license has expired.', 'paid-member-subscriptions' ), '<strong>' . PAID_MEMBER_SUBSCRIPTIONS . '</strong>' ) ); ?>
                        <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'Please %1$sRenew Your Licence%2$s to continue receiving access to product downloads, automatic updates and support.', 'paid-member-subscriptions' ), '<a href="https://www.cozmoslabs.com/account/?utm_source=wpbackend&utm_medium=pms-settings-page&utm_campaign=PMSFree" target="_blank">', '</a>' ) ); ?></p>
                    </div>
                <?php elseif( $status == 'no_activations_left' ) : ?>
                    <div class="cozmoslabs-description-container red">
                        <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'Your %s license has reached its activation limit.', 'paid-member-subscriptions' ), '<strong>' . PAID_MEMBER_SUBSCRIPTIONS . '</strong>' ) ); ?>
                        <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( '%sUpgrade now%s for unlimited activations and extra features like invoices, taxes, global content restriction, email reminders and more.', 'paid-member-subscriptions' ), '<a href="https://www.cozmoslabs.com/account/?utm_source=wpbackend&utm_medium=pms-settings-page&utm_campaign=PMS" target="_blank">', '</a>' ) ); ?>
                    </div>
                <?php elseif( empty( $license ) || $status != 'valid' ) : ?>
                    <div class="cozmoslabs-description-container">
                        <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'Enter your license key. Your license key can be found in your %sCozmoslabs account%s. ', 'paid-member-subscriptions' ), '<a href="https://www.cozmoslabs.com/account/?utm_source=wpbackend&utm_medium=pms-settings-page&utm_campaign=PMSFree" target="_blank">', '</a>' ) ); ?></p>
                        <p class="cozmoslabs-description"><?php echo wp_kses_post( sprintf( __( 'You can use this core version of Paid Member Subscription for free. For priority support and advanced functionality, a license key is required. %sClick here%s to buy one.', 'paid-member-subscriptions' ), '<a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=pms-settings-page&utm_campaign=PMSFree#pricing" target="_blank">', '</a>' ) ); ?></p>
                    </div>
                <?php endif; ?>

            </div>
        </form>
    </div>

    <?php
}


/**
 * Insert the PMS Admin area Header Banner
 *
 */
function pms_insert_page_banner() {

    if ( isset( $_GET['post_type'] ) )
        $post_type = sanitize_text_field( $_GET['post_type'] );
    elseif ( isset( $_GET['post'] ) )
        $post_type = get_post_type( (int)$_GET['post'] );
    elseif ( isset( $_GET['page'] ) )
        $post_type = sanitize_text_field( $_GET['page'] );
    else $post_type = '';

    $page_name = '';
    if ( $post_type == 'pms-addons-page' )
        $page_name = ' Addons';

    if ( !empty( $post_type ) && strpos( $post_type, 'pms' ) === 0 && ( !isset( $_GET['subpage'] ) || $_GET['subpage'] != 'pms-setup' ) )
        pms_output_page_banner( $page_name );

}
add_action( 'in_admin_header', 'pms_insert_page_banner' );


/**
 * Output the PMS Admin area Header Banner content
 *
 */
function pms_output_page_banner( $page_name ) {

    $page_title = '';
    if ( !empty( $page_name ) )
        $page_title = ' ' . $page_name;

    $upgrade_button = '<a class="cozmoslabs-banner-link cozmoslabs-upgrade-link" href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=general-settings-link&utm_campaign=PMSFree#pricing" target="_blank">
                         <img src="'. esc_url(PMS_PLUGIN_DIR_URL) . 'assets/images/upgrade-link-icon.svg" alt="">
                         Upgrade to PRO
                       </a>';

    $support_url = 'https://www.cozmoslabs.com/support?utm_source=wpbackend&utm_medium=pms-support&utm_campaign=PMSSupport';

    if ( !defined( 'PMS_PAID_PLUGIN_DIR' ) )
        $support_url = 'https://wordpress.org/support/plugin/paid-member-subscriptions/';

    $output = '<div class="cozmoslabs-banner">
                   <div class="cozmoslabs-banner-title">
                       <img src="'. esc_url(PMS_PLUGIN_DIR_URL) . 'assets/images/pms-logo.svg" alt="">
                       <h4>Paid Member Subscriptions'. $page_title .'</h4>
                   </div>
                   <div class="cozmoslabs-banner-buttons">
                       <a class="cozmoslabs-banner-link cozmoslabs-support-link" href="'. $support_url .'" target="_blank">
                           <img src="'. esc_url(PMS_PLUGIN_DIR_URL) . 'assets/images/support-link-icon.svg" alt="">
                           Support
                       </a>
               
                       <a class="cozmoslabs-banner-link cozmoslabs-documentation-link" href="https://www.cozmoslabs.com/docs/paid-member-subscriptions?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs" target="_blank">
                           <img src="'. esc_url(PMS_PLUGIN_DIR_URL) . 'assets/images/docs-link-icon.svg" alt="">
                           Documentation
                       </a>';

    if ( !defined( 'PMS_PAID_PLUGIN_DIR' ) )
        $output .= $upgrade_button;

    $output .= '    </div>
                </div>';

    echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}