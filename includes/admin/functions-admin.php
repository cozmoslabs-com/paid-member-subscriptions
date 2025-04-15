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

    if( ! ( current_user_can( 'manage_options' ) || current_user_can( 'pms_edit_capability' ) ) )
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

    wp_safe_redirect( esc_url( add_query_arg( 'sucess_notice', '1', $url ) ) );
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
                <label>
                    <input type="radio" id="wppb-fd-option-' . $form_design['id'] . '" value="' . $form_design['id'] . '" name="" disabled ' . ( $form_design['id'] == 'form-style-default' ? 'checked' : '' ) .'>
                    ' . $form_design['name'] . '</label>
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

    if ( !defined( 'PMS_PAID_PLUGIN_DIR' ) )
        return '';

    $status          = pms_get_serial_number_status();
    $license         = pms_get_serial_number();

    if( !empty( $license ) ){
        // process license so it doesn't get displayed in back-end
        $license_length = strlen( $license );
        $license        = substr_replace( $license, '***************', 7, $license_length - 14 );
    }

    $license_details = get_option( 'pms_license_details', false );
    ?>

    <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-register-version">
        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Register Version ', 'paid-member-subscriptions' ) ?></h4>

        <form method="post" action="<?php echo !is_multisite() ? 'options.php' : 'edit.php'; ?>">
            <?php settings_fields( 'pms_serial_number' ); ?>
            <div class="cozmoslabs-form-field-wrapper cozmoslabs-form-field-serial-number">
                <label class="cozmoslabs-form-field-label" for="pms_serial_number"><?php esc_html_e( 'License key', 'paid-member-subscriptions' ); ?></label>
                <div class="cozmoslabs-serial-wrap__holder">
                    <input id="pms_serial_number" name="pms_serial_number" type="text" class="regular-text" value="<?php echo esc_attr( $license ); ?>" />
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
        $page_name = ' Add-Ons';

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

    $upgrade_button = '<a class="cozmoslabs-banner-link cozmoslabs-upgrade-link" href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=upgrade-link&utm_campaign=PMSFree#pricing" target="_blank">
                         <img src="'. esc_url(PMS_PLUGIN_DIR_URL) . 'assets/images/upgrade-link-icon.svg" alt="">
                         Upgrade to PRO
                       </a>';

    $upgrade_button_basic = '<a class="cozmoslabs-banner-link cozmoslabs-upgrade-link" href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=upgrade-link&utm_campaign=PMSBasic#pricing" target="_blank">
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

    // Add free version upgrade button
    if ( !defined( 'PMS_PAID_PLUGIN_DIR' ) )
        $output .= $upgrade_button;

    // Add Basic version upgrade button (not to account, to plugin purchase page)
    if( defined( 'PAID_MEMBER_SUBSCRIPTIONS' ) && PAID_MEMBER_SUBSCRIPTIONS == 'Paid Member Subscriptions Basic' ){
        $output .= $upgrade_button_basic;
    }

    $output .= '    </div>
                </div>';

    echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}


/**
 * Include Script for repositioning the Publish Box/Button in Admin Dashboard --> PMS CPTs & Custom Pages
 *
 */
function pms_enqueue_reposition_submit_box_script( $hook ) {
    $enqueue_script = false;

    if ( $hook === 'post.php' && isset( $_GET['post'] ) ) {
        $pms_cpts = array( 'pms-subscription', 'pms-content-dripping', 'pms-email-reminders', 'pms-discount-codes' );
        $post_type = get_post_type( (int)$_GET['post'] );

        if ( in_array( $post_type, $pms_cpts ) )
            $enqueue_script = true;
    }
    else {
        $parent_menu_slug = sanitize_title( __( 'Paid Member Subscriptions', 'paid-member-subscriptions' ) );
        $pms_custom_pages = array( $parent_menu_slug . '_page_pms-members-page', $parent_menu_slug . '_page_pms-settings-page' );

        if ( in_array( $hook, $pms_custom_pages ) )
            $enqueue_script = true;
    }

    if ( $enqueue_script )
        wp_enqueue_script( 'pms-submit-meta-box-position-script', PMS_PLUGIN_DIR_URL . 'assets/js/admin/submit-meta-box-position.js', array( 'jquery' ), PMS_VERSION, true );

}
add_action( 'admin_enqueue_scripts', 'pms_enqueue_reposition_submit_box_script', 20 );


/**
 * Add the "Add New Subscription" button on members edit list table only when abandoned subscriptions are present
 *
 */
function pms_extend_edit_add_new_subscription( $which, $member, $existing_subscriptions ){

    if( $which == 'bottom' && !function_exists('pms_in_msu_member_subscription_list_table_add_new_button') ){

        $subscriptions = pms_get_member_subscriptions( array( 'user_id' => $member->user_id, 'include_abandoned' => true ) );
        $only_abandoned_subscriptions = true;
        foreach ( $subscriptions as $subscription ){
            if( $subscription->status !== "abandoned" ){
                $only_abandoned_subscriptions = false;
                break;
            }
        }
        if( $only_abandoned_subscriptions ){
            echo '<a href="' . esc_url( add_query_arg( array( 'page' => 'pms-members-page', 'subpage' => 'add_subscription', 'member_id' => $member->user_id ), admin_url( 'admin.php' ) ) ) . '" class="button-primary">' . esc_html__( 'Add New Subscription', 'paid-member-subscriptions' ) . '</a>';
        }
    }

}
add_action( 'pms_member_subscription_list_table_extra_tablenav', 'pms_extend_edit_add_new_subscription', 10, 3 );


/**
 * Function that displays the modal for the create pricing page
 *
 */
function pms_output_modal_create_pricing_page(){
    global $pagenow;

    ?>
    <div class="overlay"></div>

    <div id="" class="pms-modal <?php if( $pagenow === 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] === 'pms-dashboard-page' ) { echo 'pms-modal-dashboard'; } ?>">
        <div class="pms-modal__holder">
            <h2 class="cozmoslabs-page-title"><?php esc_html_e( 'Create Pricing Page', 'paid-member-subscriptions' ); ?></h2>
            <a class="pms-button-close" id="pms-button-close" href="#">&times;</a>
            <div class="pms-content">
                <?php
                if( empty( pms_get_page( 'register' ) ) || pms_get_page( 'register' ) == false ){

                    $pms_url_settings_page = esc_url( add_query_arg( array( 'page' => 'pms-settings-page' ), admin_url( 'admin.php' ) ) . '#cozmoslabs-subsection-membership-pages' );
                    $pms_url = '<a href="' . $pms_url_settings_page . '">' . __( 'PMS → Settings → Membership Pages → Registration', 'paid-member-subscriptions' ) . '</a>';
                    $pms_register_page_set_error = sprintf( __('%sAlert:%s It appears that the register page is not configured. To address this, please navigate to %s and choose the page containing the %s shortcode.', 'paid-member-subscriptions'),
                        '<strong>', '</strong>', $pms_url, '<strong>[pms-register]</strong>');
                    echo '<div class="pms-error-box">';
                    echo '<p class="pms-error-message">' . wp_kses_post( $pms_register_page_set_error ) . '</p>';
                    echo '</div>';
                    return;
                }

                $subscriptions = pms_get_subscription_plans();

                if( empty( $subscriptions ) || $subscriptions === null || count( $subscriptions ) === 0){

                    $pms_url_settings_page = esc_url( admin_url( 'post-new.php?post_type=pms-subscription' ) );
                    $pms_url = '<a href="' . $pms_url_settings_page . '">' . __( 'PMS → Subscription Plans → Add New', 'paid-member-subscriptions' ) . '</a>';
                    $pms_register_page_set_error = sprintf( __('%sAlert:%s It seems that you do not have any subscriptions plans set. To resolve this, please navigate to %s and create a new subscription plan.', 'paid-member-subscriptions'),
                        '<strong>', '</strong>', $pms_url);
                    echo '<div class="pms-error-box">';
                    echo '<p class="pms-error-message">' . wp_kses_post( $pms_register_page_set_error ) . '</p>';
                    echo '</div>';
                    return;
                }
                ?>
                <p><?php esc_html_e( 'Select the subscription plan(s) you want to use to generate a pricing page. You can choose a maximum of 3 plans.', 'paid-member-subscriptions' ); ?></p>
                <form action="<?php echo  esc_url( admin_url( 'admin-post.php') ); ?>" method="post" class="pms-form">
                    <table class="pms-select-container" style="margin-bottom: 20px">
                        <tr>
                            <th>
                                <label for="pms-silver-subscription-plan"><?php esc_html_e( 'First plan:', 'paid-member-subscriptions' ); ?></label>
                            </th>
                            <td>
                                <select id="pms-silver-subscription-plan" name="pms-silver-subscription-plan"  class="pms-chosen-modal" >
                                    <option value=""><?php esc_html_e( 'Select a plan...', 'paid-member-subscriptions' ); ?></option>
                                    <?php
                                    foreach ( $subscriptions as $subscription ){
                                        echo '<option value="' . esc_attr( $subscription->id ) . '">' . esc_html( $subscription->name ) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="pms-gold-subscription-plan"><?php esc_html_e( 'Second plan:', 'paid-member-subscriptions' ); ?></label>
                            </th>
                            <td>
                                <select id="pms-gold-subscription-plan" name="pms-gold-subscription-plan"  class="pms-chosen-modal" >
                                    <option value=""><?php esc_html_e( 'Select a plan...', 'paid-member-subscriptions' ); ?></option>
                                    <?php
                                    $subscriptions = pms_get_subscription_plans();
                                    foreach ( $subscriptions as $subscription ){
                                        echo '<option value="' . esc_attr( $subscription->id ) . '">' . esc_html( $subscription->name ) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="pms-platinum-subscription-plan"><?php esc_html_e( 'Third plan:', 'paid-member-subscriptions' ); ?></label>
                            </th>
                            <td>
                                <select id="pms-platinum-subscription-plan" name="pms-platinum-subscription-plan"  class="pms-chosen-modal" >
                                    <option value=""><?php esc_html_e( 'Select a plan...', 'paid-member-subscriptions' ); ?></option>
                                    <?php
                                    $subscriptions = pms_get_subscription_plans();
                                    foreach ( $subscriptions as $subscription ){
                                        echo '<option value="' . esc_attr( $subscription->id ) . '">' . esc_html( $subscription->name ) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <p class="cozmoslabs-description" style="margin-bottom: 5px;"><?php esc_html_e( 'Choose a style that better suits your pricing page.', 'paid-member-subscriptions' ); ?></p>

                    <div class="cozmoslabs-form-field-wrapper">

                        <?php
                            echo pms_render_pricing_tables_design_selector(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        ?>

                    </div>

                    <div style="margin-top: 10px;">
                        <input type="hidden" name="action" value="pms_create_pricing_table_page">
                        <input type="hidden" name="pms_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pms_create_pricing_table_page' ) ); ?>">
                        <input type="submit" class="button button-primary" value="Submit">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php
}


/**
 * Function that displays the modal for the create pricing page
 *
 */
function pms_output_modal_style_pricing_page(){
    global $pagenow;
    $post_id = isset( $_GET['post'] ) ? sanitize_text_field( $_GET['post'] ) : '';

    ?>
    <div class="overlay"></div>

    <div id="" class="pms-modal">
        <div class="pms-modal__holder">
            <h2 class="cozmoslabs-page-title"><?php esc_html_e( 'Choose a style for Pricing Page', 'paid-member-subscriptions' ); ?></h2>
            <a class="pms-button-close" id="pms-button-close" href="#">&times;</a>
            <div class="pms-content">
                <form action="<?php echo  esc_url( admin_url( 'admin-post.php') ); ?>" method="post" class="pms-form">

                    <p class="cozmoslabs-description" style="margin-bottom: 5px;"><?php esc_html_e( 'Choose a style that better suits your pricing page.', 'paid-member-subscriptions' ); ?></p>

                    <div class="cozmoslabs-form-field-wrapper">

                        <?php
                        echo pms_render_pricing_tables_design_selector(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        ?>

                    </div>

                    <div style="margin-top: 10px;">
                        <input type="hidden" name="action" value="pms_update_pricing_table_style">
                        <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
                        <input type="hidden" name="pms_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pms_update_pricing_table_style' ) ); ?>">
                        <input type="submit" class="button button-primary" value="Submit">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php
}


/**
 * Upsell notice in PMS Free Version
 *
 *  - the Subscription Type option is displayed in Subscription Plan settings
 *  - the Regular option is pre-selected
 *  - when the Group option is selected the notice is displayed
 *
 */
function pms_group_memberships_addon_upsell( $subscription_plan_id ) {
    $message = '';

    if ( !defined( 'PMS_PAID_PLUGIN_DIR' ) || ( defined( 'PMS_PAID_PLUGIN_DIR' ) && PAID_MEMBER_SUBSCRIPTIONS === 'Paid Member Subscriptions Basic' ) ) {
        // Upsell message
        $message = sprintf( esc_html__( 'Group Memberships are available only with a %1$sPro%2$s or %1$sAgency%2$s license. %3$sBuy now%4$s', 'paid-member-subscriptions' ), '<strong>', '</strong>', '<a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=subscription-type&utm_campaign=PMSFree#pricing" target="_blank">', '</a>' );
    }
    elseif ( !class_exists( 'PMS_IN_Admin_Group_Memberships' ) ) {
        // Activate Add-On message
        $message = sprintf( esc_html__( 'Please %3$sactivate%4$s the %1$sGroup Memberships%2$s Add-On to enable this functionality.', 'paid-member-subscriptions' ), '<strong>', '</strong>', '<a href="'.admin_url( 'admin.php?page=pms-addons-page' ).'">', '</a>' );;
    }

    // return if the user has a paid PMS version and the add-on is active
    if ( empty( $message ) )
        return;

    $output = '<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">
                   
                   <label for="pms-plan-type" class="pms-meta-box-field-label cozmoslabs-form-field-label">
                       '. esc_html__( 'Subscription Type', 'paid-member-subscriptions' ) .'
                   </label>
                   
                   <select id="pms-plan-type" name="pms_plan_type">
                       <option value="regular" selected="selected">'. esc_html__( 'Regular', 'paid-member-subscriptions' ) .'</option>
                       <option value="group">'. esc_html__( 'Group', 'paid-member-subscriptions' ) .'</option>
                   </select>
                   
                   <p class="cozmoslabs-description cozmoslabs-description-align-right">
                       '. esc_html__( 'Please select the type for this subscription plan.', 'paid-member-subscriptions' ) .'
                   </p>
                   
                   <p class="cozmoslabs-description cozmoslabs-description-space-left cozmoslabs-description-upsell" id="pms-group-memberships-addon-notice" style="max-width: 600px; margin-left: 230px; display: none;">
                       '. $message .'
                   </p>
                   
               </div>';

    echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'pms_view_meta_box_subscription_details_top', 'pms_group_memberships_addon_upsell' );


/**
 * Handle "Active Payment Gateways" settings relocation notification from Payments settings page
 * - hide this notification after the "Click here" button is clicked to navigate to the "Active Payment Gateways" section in its new location
 *
 * @return void
 */
function pms_payment_gateways_section_relocation_notice() {

    if( empty( $_GET['_wpnonce'] ) || !wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'pms_payment_gateways_relocation_dismiss' ) )
        return;

    if ( isset( $_GET['pms_payments_gateways_notice_clicked'] ) && $_GET['pms_payments_gateways_notice_clicked'] == true )
        add_option( 'pms_payments_gateways_notice_clicked', true );

}
add_filter( 'admin_init', 'pms_payment_gateways_section_relocation_notice' );

add_action( 'wp_ajax_pms_cleanup_postmeta', 'pms_cleanup_postmeta' );
function pms_cleanup_postmeta() {
    check_ajax_referer( 'pms_cleanup_postmeta', 'nonce' );

    global $wpdb;

    $query         = '';
    $step          = isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 1;
    $batch_size    = 500;
    $rows_affected = 0;

    if ( $step === 1 ) {
        // First query - cleanup discount code meta
        $query = $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} 
                                 WHERE post_id IN (
                                     SELECT ID FROM {$wpdb->posts}
                                     WHERE post_type != %s
                                 )
                                 AND meta_key IN ( %s, %s )
                                 LIMIT %d",
                                'pms-discount-codes',
                                'pms_discount_recurring_payments',
                                'pms_discount_new_users_only',
                                $batch_size );

        $rows_affected = $wpdb->query( $query );

        if ( $rows_affected === 0 ) {
            // Move to next step when no more rows to delete
            wp_send_json_success( array( 'step' => 2 ) );
        }

    } else if ( $step === 2 ) {
        // Second query - cleanup subscription plan meta  
        $query = $wpdb->prepare( "DELETE FROM {$wpdb->postmeta}
                                 WHERE post_id IN (
                                     SELECT ID FROM {$wpdb->posts}
                                     WHERE post_type != %s
                                 )
                                 AND meta_key IN ( %s, %s, %s, %s )
                                 LIMIT %d",
                                'pms-subscription',
                                'pms_subscription_plan_tax_exempt',
                                'pms_subscription_plan_pay_what_you_want', 
                                'pms_subscription_plan_allow_renew',
                                'pms_subscription_plan_fixed_membership',
                                $batch_size );

        $rows_affected = $wpdb->query( $query );

        if ( $rows_affected === 0 ) {
            // Save completion flag as non-autoloaded option
            add_option( 'pms_postmeta_cleanup_completed', true, '', 'no' );
            
            // All done
            wp_send_json_success( array( 
                'step' => 'done',
                'hide_button' => true 
            ) );
        }
    }

    // Continue with same step
    wp_send_json_success( array( 
        'step' => $step,
        'rows_affected' => $rows_affected
    ) );
}