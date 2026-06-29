<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'is_plugin_active' ) )
    require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Return if LearnDash Plugin is not active
if ( ! is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) ) return;

/**
 * Content restriction for the LearnDash courses archive page
 *
 * - the `/courses/` archive is the `sfwd-courses` post type archive, not a real WP_Post, so the regular meta-box and post meta cannot be used
 * - the restriction is set in a box on the Courses Options screen and stored in the `pms_learndash_courses_archive_restriction` option
 * - enforcement runs at template time
 *
 */

define( 'PMS_LD_COURSES_ARCHIVE_OPTION', 'pms_learndash_courses_archive_restriction' );
define( 'PMS_LD_COURSES_OPTIONS_SCREEN', 'sfwd-courses_page_courses-options' );

/**
 * Whether this is a paid build
 *
 * - in the free build LearnDash content restriction is suppressed and upsold (see extend/learndash/functions.php)
 * - the archive box follows the same convention, showing the real UI and enforcing only in paid builds
 *
 * @return bool
 */
function pms_ld_courses_archive_is_paid_version() {
    return ( defined( 'PMS_PAID_PLUGIN_DIR' ) || ( defined( 'PAID_MEMBER_SUBSCRIPTIONS' ) && PAID_MEMBER_SUBSCRIPTIONS === 'Paid Member Subscriptions Dev' ) );
}

/**
 * Register the restriction box on the LearnDash Courses Options screen
 *
 * - the screen renders via do_meta_boxes(), so a standard meta-box mounts cleanly
 *
 * @param string $screen_id
 */
function pms_ld_courses_archive_register_meta_box( $screen_id ) {
    if ( $screen_id !== PMS_LD_COURSES_OPTIONS_SCREEN )
        return;

    $callback = pms_ld_courses_archive_is_paid_version() ? 'pms_ld_courses_archive_meta_box_content' : 'pms_ld_courses_archive_upsell_meta_box_content';

    // 'low' priority and late hook so the box renders after LearnDash's own sections
    add_meta_box( 'pms-learndash-courses-archive-content-restriction', __( 'Content Restriction', 'paid-member-subscriptions' ), $callback, $screen_id, 'normal', 'low' );
}
add_action( 'learndash_add_meta_boxes', 'pms_ld_courses_archive_register_meta_box', 20 );

/**
 * Output the real restriction UI
 *
 * - reuses the same field markup, meta-key vocabulary and JS as the post / taxonomy restriction UI, so the conditional fields behave identically
 *
 */
function pms_ld_courses_archive_meta_box_content() {
    $restriction = pms_ld_get_courses_archive_restriction();

    include PMS_PLUGIN_DIR_PATH . 'extend/learndash/views/view-courses-archive-content-restriction.php';
}

/**
 * Output the free build upsell box
 *
 * - consistent with the single course / lesson / quiz upsell box
 *
 */
function pms_ld_courses_archive_upsell_meta_box_content() {
    echo '<div class="pms-icon-wrapper">
              <img id="pms-icon" src="'. esc_url( PMS_PLUGIN_DIR_URL ) . 'assets/images/pms-logo.svg" alt="Paid Member Subscriptions PRO" title="Paid Member Subscriptions PRO">
              <span class="dashicons dashicons-plus"></span>
              <img id="learndash-logo" src="' . esc_url( PMS_PLUGIN_DIR_URL ) . 'assets/images/learn-dash-logo.png" alt="">
          </div>';

    echo '<h4>' . esc_html( __( 'Restrict the courses archive page with just a few clicks.', 'paid-member-subscriptions' ) ) . '</h4>';

    echo '<p>' . esc_html( __( 'Allow only members to access the courses listing page with Paid Member Subscriptions PRO.', 'paid-member-subscriptions' ) ) . '</p>';

    echo '<a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=content-restriction-learndash&utm_campaign=PMSFree#pricing" target="_blank" class="button-primary">' . esc_html( __( 'Upgrade to PRO', 'paid-member-subscriptions' ) ) . '</a>';
}

/**
 * Enqueue the shared restriction JS on the Courses Options screen
 *
 * - drives the conditional field show/hide and the "all plans" toggle
 *
 */
function pms_ld_courses_archive_enqueue_admin_scripts() {
    $screen = get_current_screen();

    if ( empty( $screen ) || $screen->id !== PMS_LD_COURSES_OPTIONS_SCREEN )
        return;

    wp_enqueue_script( 'pms-courses-archive-content-restriction-js', PMS_PLUGIN_DIR_URL . 'assets/js/admin/meta-box-post-content-restriction.js', array( 'jquery' ), PMS_VERSION );
}

/**
 * Register the option in the Courses Options settings group
 *
 * - lets it save through the screen's existing options.php form
 *
 */
function pms_ld_courses_archive_register_setting() {
    register_setting( 'courses-options', PMS_LD_COURSES_ARCHIVE_OPTION, array( 'sanitize_callback' => 'pms_ld_courses_archive_sanitize' ) );
}

/**
 * Assemble the restriction option from the submitted form fields
 *
 * - the Settings API value is ignored, the fields are read from $_POST directly
 * - bails on a failed nonce check, returning the stored value unchanged
 *
 * @param mixed $value
 * @return array
 */
function pms_ld_courses_archive_sanitize( $value ) {
    if ( empty( $_POST['pmstkn'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['pmstkn'] ), 'pms_meta_box_single_content_restriction_nonce' ) )
        return pms_ld_get_courses_archive_restriction();

    $restriction = array();

    if ( ! empty( $_POST['pms-content-restrict-type'] ) )
        $restriction['pms-content-restrict-type'] = sanitize_text_field( $_POST['pms-content-restrict-type'] );

    if ( isset( $_POST['pms-content-restrict-all-subscription-plans'] ) ) {

        $restriction['pms-content-restrict-all-subscription-plans'] = 'all';

        $active_plan_ids = array();
        foreach ( pms_get_subscription_plans() as $active_plan ) {
            $active_plan_ids[] = (int) $active_plan->id;
        }

        $restriction['pms-content-restrict-subscription-plan'] = $active_plan_ids;

    } elseif ( ! empty( $_POST['pms-content-restrict-subscription-plan'] ) ) {
        $restriction['pms-content-restrict-subscription-plan'] = array_map( 'intval', (array) $_POST['pms-content-restrict-subscription-plan'] );
    }

    if ( isset( $_POST['pms-content-restrict-user-status'] ) && $_POST['pms-content-restrict-user-status'] === 'loggedin' )
        $restriction['pms-content-restrict-user-status'] = 'loggedin';

    if ( isset( $_POST['pms-content-restrict-custom-redirect-url-enabled'] ) )
        $restriction['pms-content-restrict-custom-redirect-url-enabled'] = 'yes';

    $restriction['pms-content-restrict-custom-redirect-url']            = ! empty( $_POST['pms-content-restrict-custom-redirect-url'] ) ? sanitize_text_field( wp_unslash( $_POST['pms-content-restrict-custom-redirect-url'] ) ) : '';
    $restriction['pms-content-restrict-custom-non-member-redirect-url'] = ! empty( $_POST['pms-content-restrict-custom-non-member-redirect-url'] ) ? sanitize_text_field( wp_unslash( $_POST['pms-content-restrict-custom-non-member-redirect-url'] ) ) : '';

    if ( isset( $_POST['pms-content-restrict-messages-enabled'] ) )
        $restriction['pms-content-restrict-messages-enabled'] = 'yes';

    $restriction['pms-content-restrict-message-logged_out']  = ! empty( $_POST['pms-content-restrict-message-logged_out'] ) ? wp_kses_post( wp_unslash( $_POST['pms-content-restrict-message-logged_out'] ) ) : '';
    $restriction['pms-content-restrict-message-non_members'] = ! empty( $_POST['pms-content-restrict-message-non_members'] ) ? wp_kses_post( wp_unslash( $_POST['pms-content-restrict-message-non_members'] ) ) : '';

    return $restriction;
}

/**
 * Get the stored courses archive restriction settings
 *
 * @return array
 */
function pms_ld_get_courses_archive_restriction() {
    $restriction = get_option( PMS_LD_COURSES_ARCHIVE_OPTION, array() );

    return is_array( $restriction ) ? $restriction : array();
}

/**
 * Get the effective restriction type
 *
 * - falls back to the global Content Restriction default when unset or set to "default"
 *
 * @return string
 */
function pms_ld_get_courses_archive_restriction_type() {
    $restriction = pms_ld_get_courses_archive_restriction();
    $type        = ! empty( $restriction['pms-content-restrict-type'] ) ? $restriction['pms-content-restrict-type'] : '';

    if ( $type === 'default' || empty( $type ) ) {
        $settings = get_option( 'pms_content_restriction_settings', array() );
        $type     = ! empty( $settings['content_restrict_type'] ) ? $settings['content_restrict_type'] : 'message';
    }

    return $type;
}

/**
 * Whether the current user is restricted from the courses archive
 *
 * @return bool
 */
function pms_ld_is_courses_archive_restricted() {

    static $result = null;

    if ( $result !== null )
        return $result;

    $restriction = pms_ld_get_courses_archive_restriction();

    if ( empty( $restriction ) )
        return $result = false;

    if ( current_user_can( 'manage_options' ) || current_user_can( apply_filters( 'pms_content_restriction_capability', 'pms_bypass_content_restriction' ) ) )
        return $result = false;

    $user_status        = ! empty( $restriction['pms-content-restrict-user-status'] ) ? $restriction['pms-content-restrict-user-status'] : '';
    $subscription_plans = ! empty( $restriction['pms-content-restrict-subscription-plan'] ) ? (array) $restriction['pms-content-restrict-subscription-plan'] : array();
    $all_plans          = ! empty( $restriction['pms-content-restrict-all-subscription-plans'] ) ? $restriction['pms-content-restrict-all-subscription-plans'] : '';

    if ( ( ! empty( $subscription_plans ) || ! empty( $all_plans ) ) && is_user_logged_in() ) {

        if ( $all_plans === 'all' && pms_is_member() )
            return $result = false;
        elseif ( pms_is_member( get_current_user_id(), $subscription_plans ) )
            return $result = false;
        else
            return $result = true;

    } elseif ( ( ! empty( $user_status ) && $user_status === 'loggedin' ) || ! empty( $subscription_plans ) ) {

        if ( ! is_user_logged_in() )
            return $result = true;

    }

    return $result = false;
}

/**
 * Build the restriction message for the courses archive
 *
 * @return string
 */
function pms_ld_get_restricted_courses_archive_message() {

    global $user_ID, $wp_embed;

    $restriction  = pms_ld_get_courses_archive_restriction();
    $message_type = ! is_user_logged_in() ? 'logged_out' : 'non_members';
    $settings     = get_option( 'pms_content_restriction_settings' );

    if ( $message_type === 'logged_out' )
        $message = isset( $settings['logged_out'] ) ? $settings['logged_out'] : __( 'You do not have access to this content. You need to create an account.', 'paid-member-subscriptions' );
    else
        $message = isset( $settings['non_members'] ) ? $settings['non_members'] : __( 'You do not have access to this content. You need the proper subscription.', 'paid-member-subscriptions' );

    if ( ! empty( $restriction['pms-content-restrict-messages-enabled'] ) ) {
        $custom_message = ! empty( $restriction[ 'pms-content-restrict-message-' . $message_type ] ) ? $restriction[ 'pms-content-restrict-message-' . $message_type ] : '';

        if ( ! empty( $custom_message ) )
            $message = $custom_message;
    }

    $user_info = get_userdata( $user_ID );

    if ( class_exists( 'PMS_Merge_Tags' ) )
        $message = PMS_Merge_Tags::process_merge_tags( $message, $user_info, '' );

    $message = $wp_embed->autoembed( $message );

    add_filter( 'wp_kses_allowed_html', 'pms_wp_kses_allowed_html_iframe', 10, 2 );
    $message = wp_kses_post( $message );
    remove_filter( 'wp_kses_allowed_html', 'pms_wp_kses_allowed_html_iframe', 10, 2 );

    if ( function_exists( 'pms_restriction_message_wpautop' ) )
        $message = pms_restriction_message_wpautop( $message );

    return do_shortcode( apply_filters( 'pms_restricted_courses_archive_message', $message, $message_type ) );
}

/**
 * Enforce the redirect restriction type on the courses archive
 *
 */
function pms_ld_restrict_courses_archive_redirect() {

    if ( ! is_post_type_archive( 'sfwd-courses' ) )
        return;

    if ( isset( $_GET['pay_gate_listener'] ) )
        return;

    $restriction              = pms_ld_get_courses_archive_restriction();
    $restriction_type         = ! empty( $restriction['pms-content-restrict-type'] ) ? $restriction['pms-content-restrict-type'] : '';
    $settings                 = get_option( 'pms_content_restriction_settings', array() );
    $general_restriction_type = ( ! empty( $settings['content_restrict_type'] ) ? $settings['content_restrict_type'] : 'message' );
    $subscription_plans       = ! empty( $restriction['pms-content-restrict-subscription-plan'] ) ? (array) $restriction['pms-content-restrict-subscription-plan'] : array();
    $non_member_redirect_url  = '';
    $redirect_url             = '';

    if ( $restriction_type !== 'redirect' && $general_restriction_type !== 'redirect' )
        return;

    if ( ! in_array( $restriction_type, array( '', 'default', 'redirect' ), true ) )
        return;

    if ( ! pms_ld_is_courses_archive_restricted() )
        return;

    if ( $restriction_type === 'redirect' ) {

        $redirect_url_enabled    = ! empty( $restriction['pms-content-restrict-custom-redirect-url-enabled'] ) ? $restriction['pms-content-restrict-custom-redirect-url-enabled'] : '';
        $custom_redirect_url     = ! empty( $restriction['pms-content-restrict-custom-redirect-url'] ) ? $restriction['pms-content-restrict-custom-redirect-url'] : '';
        $custom_non_member_url   = ! empty( $restriction['pms-content-restrict-custom-non-member-redirect-url'] ) ? $restriction['pms-content-restrict-custom-non-member-redirect-url'] : '';

        $redirect_url            = ( ! empty( $redirect_url_enabled ) && ! empty( $custom_redirect_url ) ? $custom_redirect_url : '' );
        $non_member_redirect_url = ( ! empty( $redirect_url_enabled ) && ! empty( $custom_non_member_url ) ? $custom_non_member_url : '' );

    }

    if ( ! empty( $non_member_redirect_url ) ) {
        if ( is_user_logged_in() && ! pms_is_member( get_current_user_id(), $subscription_plans ) )
            $redirect_url = $non_member_redirect_url;
    }

    if ( empty( $redirect_url ) ) {

        $redirect_url            = ( ! empty( $settings['content_restrict_redirect_url'] ) ? $settings['content_restrict_redirect_url'] : '' );
        $non_member_redirect_url = ( ! empty( $settings['content_restrict_non_member_redirect_url'] ) ? $settings['content_restrict_non_member_redirect_url'] : '' );

        if ( ! empty( $non_member_redirect_url ) ) {
            if ( is_user_logged_in() && ! pms_is_member( get_current_user_id(), $subscription_plans ) )
                $redirect_url = $non_member_redirect_url;
        }

    }

    if ( empty( $redirect_url ) )
        return;

    $current_url = pms_get_current_page_url();

    if ( $current_url == $redirect_url )
        return;

    $add_redirect_to = apply_filters( 'pms_content_restriction_redirect_add_redirect_to_parameter', true, $current_url );

    $query_args = array();

    if ( $add_redirect_to )
        $query_args['redirect_to'] = $current_url;

    $redirect_url = add_query_arg( $query_args, pms_add_missing_http( $redirect_url ) );

    nocache_headers();
    wp_redirect( apply_filters( 'pms_restricted_courses_archive_redirect_url', $redirect_url ) );
    exit;
}

/**
 * Swap the template for the message and template restriction types
 *
 * - the message type renders through the shared restriction message helper, which handles both block and classic themes
 *
 * @param string $template
 * @return string
 */
function pms_ld_restrict_courses_archive_template( $template ) {

    if ( ! is_post_type_archive( 'sfwd-courses' ) )
        return $template;

    if ( ! pms_ld_is_courses_archive_restricted() )
        return $template;

    $restriction_type = pms_ld_get_courses_archive_restriction_type();

    if ( $restriction_type === 'message' )
        return pms_render_restricted_message_template( pms_ld_get_restricted_courses_archive_message(), 'pms//restricted-courses-archive' );

    if ( $restriction_type !== 'template' )
        return $template;

    $settings          = get_option( 'pms_content_restriction_settings', array() );
    $restrict_template = ( ! empty( $settings['content_restrict_template'] ) ? $settings['content_restrict_template'] : '' );

    if ( empty( $restrict_template ) )
        return $template;

    if ( did_action( 'elementor/loaded' ) && strpos( $restrict_template, 'elementor_template_' ) !== false ) {

        $elementor_template = pms_elementor_render_template( str_replace( 'elementor_template_', '', $restrict_template ) );

        if ( ! empty( $elementor_template ) )
            return $elementor_template;

        return $template;
    }

    $new_template = locate_template( array( $restrict_template ) );

    if ( ! empty( $new_template ) )
        return $new_template;

    return $template;
}


/** Hooks that run on paid versions only */
if ( pms_ld_courses_archive_is_paid_version() ) {

    add_action( 'admin_init', 'pms_ld_courses_archive_register_setting' );
    add_action( 'admin_enqueue_scripts', 'pms_ld_courses_archive_enqueue_admin_scripts' );

    add_action( 'template_redirect', 'pms_ld_restrict_courses_archive_redirect', 1 );
    add_filter( 'template_include', 'pms_ld_restrict_courses_archive_template', 999 );
}
