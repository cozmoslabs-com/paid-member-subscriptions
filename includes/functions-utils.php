<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Verifies whether the current page communicates through HTTPS
 *
 * @return bool
 *
 */
function pms_is_https() {

    $is_secure = false;

    if ( isset( $_SERVER['HTTPS'] ) && 'on' === strtolower( sanitize_text_field( $_SERVER['HTTPS'] ) ) ) {

        $is_secure = true;

    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' || ! empty( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on' ) {

        $is_secure = true;

    }

    return $is_secure;

}


/**
 * Function that returns only the date part of a date-time format
 *
 * @param string $date
 *
 * @return string
 *
 */
function pms_sanitize_date( $date ) {

    if( !isset( $date ) )
        return;

    $date_time = explode( ' ', $date );

    return $date_time[0];

}


/**
 * Returns the url of the current page
 *
 * @param bool $strip_query_args - whether to eliminate query arguments from the url or not
 *
 * @return string
 *
 */
function pms_get_current_page_url( $strip_query_args = false ) {
    $home_url = pms_get_absolute_home();

    $parsed_url = parse_url( $home_url, PHP_URL_PATH );

    if( !empty( $parsed_url ) )
        $home_path = trim( $parsed_url, '/' );
    else
        $home_path = $parsed_url;

    if( $home_path === null || $home_path === false )
        $home_path = '';

    $home_path_regex = sprintf( '|^%s|i', preg_quote( $home_path, '|' ) );

    if( isset( $_SERVER['REQUEST_URI'] ) )
        $request_uri = preg_replace( $home_path_regex, '', ltrim( esc_url_raw( $_SERVER['REQUEST_URI'] ), '/' ) );
    else
        $request_uri = '';

    $page_url    = trim( $home_url, '/') . '/' . ltrim( $request_uri, '/' );

    // Remove query arguments
    if( $strip_query_args ) {
        $page_url_parts = explode( '?', $page_url );

        $page_url = $page_url_parts[0];

        // Keep query args "p" and "page_id" for non-beautified permalinks
        if( isset( $page_url_parts[1] ) ) {
            $page_url_query_args = explode( '&', $page_url_parts[1] );

            if( !empty( $page_url_query_args ) ) {
                foreach( $page_url_query_args as $key => $query_arg ) {

                    if( strpos( $query_arg, 'p=' ) === 0 ) {
                        $query_arg_parts = explode( '=', $query_arg );
                        $query_arg       = $query_arg_parts[0];
                        $query_arg_val   = $query_arg_parts[1];

                        $page_url = add_query_arg( array( $query_arg => $query_arg_val ), $page_url );
                    }

                    if( strpos( $query_arg, 'page_id=' ) === 0 ) {
                        $query_arg_parts = explode( '=', $query_arg );
                        $query_arg       = $query_arg_parts[0];
                        $query_arg_val   = $query_arg_parts[1];

                        $page_url = add_query_arg( array( $query_arg => $query_arg_val ), $page_url );
                    }

                }
            }
        }

    }

    /**
     * Filter the page url just before returning
     *
     * @param string $page_url
     *
     */
    $page_url = apply_filters( 'pms_get_current_page_url', $page_url );

    return $page_url;

}

function pms_get_absolute_home(){
    global $wpdb;

    $url = ( ! is_multisite() && defined( 'WP_HOME' )
            ? WP_HOME
            : ( is_multisite() && ! is_main_site()
                ? ( preg_match( '/^(https)/', get_option( 'home' ) ) === 1 ? 'https://'
                    : 'http://' ) . $wpdb->get_var( "	SELECT CONCAT(b.domain, b.path)
								FROM {$wpdb->blogs} b
								WHERE blog_id = {$wpdb->blogid}
								LIMIT 1" )

                : $wpdb->get_var( "	SELECT option_value
								FROM {$wpdb->options}
								WHERE option_name = 'home'
								LIMIT 1" ) )
        );

    if ( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' )
        $url = str_replace( 'http://', 'https://', $url );
    else
        $url = str_replace( 'https://', 'http://', $url );

	if (empty($url)) {
		$url = get_option("siteurl");
	}

    return $url;
}

/**
 * Checks if there is a need to add the http:// prefix to a link and adds it. Returns the correct link.
 *
 * @param string $link
 *
 * @return string
 *
 */
function pms_add_missing_http( $link = '' ) {

    $http = '';

    if ( preg_match( '#^(?:[a-z\d]+(?:-+[a-z\d]+)*\.)+[a-z]+(?::\d+)?(?:/|$)#i', $link ) ) { //if missing http(s)

        $http = 'http';

        if ( isset( $_SERVER['HTTPS'] ) && 'on' == strtolower( sanitize_text_field( $_SERVER['HTTPS'] ) ) )
            $http .= "s";

        $http .= "://";
    }

    return $http . $link;

}

/**
 * Sanitizes the values of an array recursivelly
 *
 * @param array $array
 *
 * @return array
 *
 */
function pms_array_sanitize_text_field( $array = array() ) {

    if( empty( $array ) || ! is_array( $array ) )
        return array();

    foreach( $array as $key => $value ) {

        if( is_array( $value ) )
            $array[$key] = pms_array_sanitize_text_field( $value );

        else
            $array[$key] = sanitize_text_field( $value );

    }

    return $array;

}


/**
 * Removes the script tags from the values of an array recursivelly
 *
 * @param array $array
 *
 * @return array
 *
 */
function pms_array_strip_script_tags( $array = array() ) {

    if( empty( $array ) || ! is_array( $array ) )
        return array();

    foreach( $array as $key => $value ) {

        if( is_array( $value ) )
            $array[$key] = pms_array_strip_script_tags( $value );
        else {
            if( empty( $value ) )
                $array[$key] = $value;
            else
                $array[$key] = preg_replace( '@<(script)[^>]*?>.*?</\\1>@si', '', $value );
        }

    }

    return $array;

}


/**
 * Callback for the "wp_kses_allowed_html" filter to add iframes to the allowed tags
 *
 * @param array  $tags
 * @param strint $context
 *
 * @return array
 *
 */
function pms_wp_kses_allowed_html_iframe( $tags = array(), $context = '' ) {

    if ( 'post' === $context ) {

        $tags['iframe'] = array(
            'src'             => true,
            'height'          => true,
            'width'           => true,
            'frameborder'     => true,
            'allowfullscreen' => true,
        );

    }

    return $tags;

}


/**
 * Copy of WordPress's default _deprecated_function() function, which is marked as private
 *
 */
function _pms_deprecated_function( $function, $version, $replacement = null ) {

    /**
     * Filters whether to trigger an error for deprecated functions.
     *
     * @param bool $trigger Whether to trigger the error for deprecated functions. Default true.
     *
     */
    if ( WP_DEBUG && apply_filters( 'pms_deprecated_function_trigger_error', true ) ) {
        if ( function_exists( '__' ) ) {
            if ( ! is_null( $replacement ) ) {
                /* translators: 1: PHP function name, 2: version number, 3: alternative function name */
                trigger_error( wp_kses_post( sprintf( __('%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.', 'paid-member-subscriptions'), $function, $version, $replacement ) ) );
            } else {
                /* translators: 1: PHP function name, 2: version number */
                trigger_error( wp_kses_post( sprintf( __('%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.', 'paid-member-subscriptions' ), $function, $version ) ) );
            }
        } else {
            if ( ! is_null( $replacement ) ) {
                trigger_error( wp_kses_post( sprintf( __('%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.', 'paid-member-subscriptions' ), $function, $version, $replacement ) ) );
            } else {
                trigger_error( wp_kses_post( sprintf( __( '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.', 'paid-member-subscriptions' ), $function, $version ) ) );
            }
        }
    }
}

/**
 * Checks the status of the automatically login option
 *
 * @since 1.7.8
 * @return boolean True if auto login activated or false if not
 */
function pms_is_autologin_active() {
    $settings = get_option( 'pms_general_settings' );

    if ( !empty( $settings['automatically_log_in'] ) && $settings['automatically_log_in'] == '1' )
        return true;

    return false;
}

/**
 * Retrieves the serial number if available
 *
 * @since 1.7.8
 * @return string|bool
 */
function pms_get_serial_number() {

    if( is_multisite() ){
        $serial_number = get_site_option( 'pms_serial_number' );

        // fallback to regular option if this is empty
        if( empty( $serial_number ) )
            $serial_number = get_option( 'pms_serial_number' );

    } else
        $serial_number = get_option( 'pms_serial_number' );

    return $serial_number === false ? false : $serial_number;

}

/**
 * Retrieves the status of the serial number.
 *
 * @since 1.7.8
 * @return string Serial number status
 */
function pms_get_serial_number_status() {

    if( is_multisite() )
        return get_site_option( 'pms_license_status' );
    else
        return get_option( 'pms_license_status' );

}

/**
 * Retrives the current Paid Member Subscriptions version
 *
 * @since 1.7.8
 * @return string  Free, basic, pro, unlimited, agency
 */
function pms_get_product_version() {

    $version = 'free';

    $active_plugins         = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
    $active_network_plugins = get_site_option('active_sitewide_plugins');

    if ( in_array( 'paid-member-subscriptions-pro/index.php', $active_plugins ) || isset( $active_network_plugins['paid-member-subscriptions-pro/index.php'] ) )
        $version = 'pro';
    elseif( in_array( 'paid-member-subscriptions-unlimited/index.php', $active_plugins ) || isset( $active_network_plugins['paid-member-subscriptions-unlimited/index.php'] ) )
        $version = 'unlimited';
    elseif( in_array( 'paid-member-subscriptions-agency/index.php', $active_plugins ) || isset( $active_network_plugins['paid-member-subscriptions-agency/index.php'] ) )
        $version = 'agency';
    elseif( in_array( 'paid-member-subscriptions-basic/index.php', $active_plugins ) || isset( $active_network_plugins['paid-member-subscriptions-basic/index.php'] ) )
        $version = 'basic';
    elseif( in_array( 'paid-member-subscriptions-dev/index.php', $active_plugins ) || isset( $active_network_plugins['paid-member-subscriptions-dev/index.php'] ) )
        $version = 'dev';

    return $version;

}

/*
 * To be used in admin screens
 */
function pms_get_current_post_type() {
    global $post, $typenow, $current_screen, $pagenow;

    if ( $post && $post->post_type )
        return $post->post_type;
    elseif ( $typenow )
        return $typenow;
    elseif ( $current_screen && $current_screen->post_type )
        return $current_screen->post_type;
    elseif ( isset( $_GET['post_type'] ) )
        return sanitize_key( $_GET['post_type'] );
    elseif ( isset( $_GET['post'] ) )
        return get_post_type( absint( $_GET['post'] ) );
    elseif( is_admin() && $pagenow == 'post-new.php' )
        return 'post';

    return null;
}

function pms_get_billing_states(){

    $pms_states = array();

    $files = @glob( PMS_PLUGIN_DIR_PATH . 'i18n/states/[A-Z][A-Z].php', GLOB_NOSORT );

    foreach( $files as $file )
        include( $file );

    return apply_filters( 'pms_get_billing_states', $pms_states );

}

/**
 * Retrieve GDPR settings
 * If current settings are empty, it retrieves settings from the older option
 *
 * @since 2.0.5
 * @return array
 */
function pms_get_gdpr_settings(){

    $settings = get_option( 'pms_misc_settings', array() );

    if( empty( $settings['gdpr'] ) ){
        $old_gdpr_settings = get_option( 'pms_gdpr_settings', array() );

        if( !empty( $old_gdpr_settings ) ){
            $settings['gdpr'] = $old_gdpr_settings;

            update_option( 'pms_misc_settings', $settings );
        }

    }

    return isset( $settings['gdpr'] ) ? $settings['gdpr'] : array();

}

/**
 * Simple query to count users
 */
function pms_count_users(){

    global $wpdb;

    return $wpdb->get_var(
    	"SELECT COUNT(*) FROM $wpdb->users"
    );

}

/**
 * WPML translation support
 */
function pms_icl_t( $context, $name, $value ){

	if( function_exists( 'icl_t' ) )
		return icl_t( $context, $name, $value );
	else
		return $value;

}

/**
 * Verifies if a paid version of the plugin is active
 */
function pms_is_paid_version_active(){

    $slugs = array(
        '/paid-member-subscriptions-basic/index.php',
        '/paid-member-subscriptions-agency/index.php',
        '/paid-member-subscriptions-pro/index.php',
        '/paid-member-subscriptions-unlimited/index.php',
    );

    $active = false;

    foreach( $slugs as $slug ){
        if( file_exists( WP_PLUGIN_DIR . $slug ) ){
            $active = true;
            break;
        }
    }

    return $active;

}

/**
 * Figure out if we should load front-end scripts or not on the current page request
 */
function pms_should_load_scripts(){

    // Don't load scripts on the WooCommerce Checkout and Cart pages
    if( ( function_exists( 'is_checkout' ) && is_checkout() ) || ( function_exists( 'is_cart' ) && is_cart() ) )
        return false;

    if( is_admin() )
        return true;

    $settings = get_option( 'pms_misc_settings', false );

    if( empty( $settings ) )
        return true;

    if( !isset( $settings['scripts-on-specific-pages-enabled'] ) || $settings['scripts-on-specific-pages-enabled'] != '1' )
        return true;

    // Load scripts on the Membership pages selected under general settings
    $general_settings = get_option( 'pms_general_settings', false );

    if( !empty( $general_settings ) ){

        $pages = array();

        if( isset( $general_settings['login_page'] ) && $general_settings['login_page'] != '-1' )
            $pages[] = $general_settings['login_page'];
        if( isset( $general_settings['register_page'] ) && $general_settings['register_page'] != '-1' )
            $pages[] = $general_settings['register_page'];
        if( isset( $general_settings['account_page'] ) && $general_settings['account_page'] != '-1' )
            $pages[] = $general_settings['account_page'];
        if( isset( $general_settings['lost_password_page'] ) && $general_settings['lost_password_page'] != '-1' )
            $pages[] = $general_settings['lost_password_page'];

        if( in_array( get_the_ID(), $pages ) )
            return true;

    }

    if( !isset( $settings['scripts-on-specific-pages'] ) || empty( $settings['scripts-on-specific-pages'] ) )
        return true;

    if( in_array( get_the_ID(), $settings['scripts-on-specific-pages'] ) )
        return true;

    return false;

}

/**
 * Return an array of generated errors
 */
function pms_get_generated_errors(){

    $generated_errors = array();
    $error_obj        = pms_errors();

    if( !empty( $error_obj->errors ) ){
        foreach( $error_obj->errors as $key => $error ){

            if( !empty( $error[0] ) )
                $generated_errors[] = array(
                    'target'  => $key,
                    'message' => $error[0]
                );

        }
    }

    return $generated_errors;

}

// This adds a wrapper around the account details fields from the form and also makes sure to keep
// the billing and credit card information sections separate
add_action( 'pms_register_form_before_fields', 'pms_forms_output_account_details_wrapper_before', 60 );
function pms_forms_output_account_details_wrapper_before( $atts ){

    echo '<div class="pms-account-section-wrapper">';

    if( $atts['plans_position'] == 'top' || ( function_exists( 'pms_get_active_form_design' ) && in_array( pms_get_active_form_design(), array( 'form-style-1', 'form-style-2', 'form-style-3' ) ) ) ){
        echo '<h3 class="pms-account-details-title">'. esc_html__('Account Details', 'paid-member-subscriptions') .'</h3>';
    }

}

add_action( 'pms_register_form_after_fields', 'pms_forms_output_account_details_wrapper_after', 60 );
function pms_forms_output_account_details_wrapper_after(){

    echo '</div>';

}

function pms_get_home_url(){

    $target_url = wp_parse_url( home_url() );
    $target_url = $target_url['scheme'] . '://' . $target_url['host'];

    return $target_url;

}