<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Verifies whether the current post or the post with the provided id has any restrictions in place
 *
 * @param int $post_id
 *
 * @return bool
 *
 */
function pms_is_post_restricted( $post_id = null ) {

    global $post, $pms_show_content, $pms_is_post_restricted_arr;

    if( empty( $post_id ) ){

        if( !empty( $post->ID ) )
            $post_id = $post->ID;
        else
            return false;

    }

    if( is_array( $post_id ) )
        $post_id = null;

    /**
     * If we have a cached result, return it
     */
    if( isset( $pms_is_post_restricted_arr[$post_id] ) )
        return $pms_is_post_restricted_arr[$post_id];

    $post_obj = $post;

    if( ! is_null( $post_id ) )
        $post_obj = get_post( $post_id );

    /**
     * This filter was added in order to take advantage of the existing functions that hook to the_content
     * and check to see if the post is restricted or not.
     *
     * We don't need the returned value, just the value of the global $pms_show_content, which is modified
     * in the functions mentioned above
     *
     */
    $t = apply_filters( 'pms_post_restricted_check', '', $post_obj );

    /**
     * Cache the result for further usage
     */
    if( $pms_show_content === false )
        $pms_is_post_restricted_arr[$post_id] = true;
    elseif ( $pms_show_content === true )
        $pms_is_post_restricted_arr[$post_id] = false;
    else {
        $restricted_term = pms_get_post_restricted_term( $post_id );
        $pms_is_post_restricted_arr[$post_id] = ( ! empty( $restricted_term ) );
    }

    // Return
    return $pms_is_post_restricted_arr[$post_id];

}


/**
 * Verifies whether a post has direct content restriction rules set on itself
 *
 * @param int $post_id
 *
 * @return bool
 *
 */
function pms_post_has_direct_restrictions( $post_id = 0 ) {

    if ( empty( $post_id ) )
        return false;

    $user_status            = get_post_meta( $post_id, 'pms-content-restrict-user-status', true );
    $subscription_plans     = get_post_meta( $post_id, 'pms-content-restrict-subscription-plan' );
    $all_subscription_plans = get_post_meta( $post_id, 'pms-content-restrict-all-subscription-plans', true );

    return ( ! empty( $user_status ) || ! empty( $subscription_plans ) || ! empty( $all_subscription_plans ) );

}


/**
 * Verifies whether a post has direct subscription-plan-based restrictions set on itself
 *
 * @param int $post_id
 *
 * @return bool
 *
 */
function pms_post_has_direct_subscription_restrictions( $post_id = 0 ) {

    if ( empty( $post_id ) )
        return false;

    $subscription_plans     = get_post_meta( $post_id, 'pms-content-restrict-subscription-plan' );
    $all_subscription_plans = get_post_meta( $post_id, 'pms-content-restrict-all-subscription-plans', true );

    return ( ! empty( $subscription_plans ) || ! empty( $all_subscription_plans ) );

}


/**
 * Returns the restriction message added by the admin in the settings page or a default message if the first one is missing
 *
 * @param string $type      - whether the message is for logged out users or non-members
 * @param int    $post_id   - optional, the id of the current post
 *
 * @return string
 *
 */
function pms_get_restriction_content_message( $type = '', $post_id = 0 ) {

    $settings = get_option( 'pms_content_restriction_settings' );
    $message  = '';

    // Set the default message from the Settings page
    if( $type == 'logged_out' ){
        $message = isset( $settings['logged_out']) ? $settings['logged_out'] : __( 'You do not have access to this content. You need to create an account.', 'paid-member-subscriptions' );
    } elseif( $type == 'non_members' ){
        $message = isset( $settings['non_members']) ? $settings['non_members'] : __( 'You do not have access to this content. You need the proper subscription.', 'paid-member-subscriptions' );
    } else{
        $message = apply_filters('pms_get_restriction_content_message_default', $message, $type, $settings);
    }

    // Overwrite if there is a custom message set for the post
    $custom_message_enabled = get_post_meta( $post_id, 'pms-content-restrict-messages-enabled', true );

    if( ! empty( $post_id ) && ! empty( $custom_message_enabled ) ) {

        $custom_message = get_post_meta( $post_id, 'pms-content-restrict-message-' . $type, true );

        if( ! empty( $custom_message ) )
            $message = $custom_message;

    }

    /**
     * Autoembed unlinked URLs
     *
     */
    global $wp_embed;
    $message = $wp_embed->autoembed( $message );

    // Allow iframes in the body of the restriction message
    add_filter( 'wp_kses_allowed_html', 'pms_wp_kses_allowed_html_iframe', 10, 2 );

    $message = wp_kses_post( $message );

    // Remove our iframe allowed html filter, so that other parth of the execution
    // are not affected
    remove_filter( 'wp_kses_allowed_html', 'pms_wp_kses_allowed_html_iframe', 10, 2 );

    return apply_filters( 'pms_content_restriction_message', $message, $type, $post_id );

}


/**
 * Returns the restriction message with any tags processed
 *
 * @param string $type
 * @param int    $user_ID
 * @param int    $post_id - optional
 *
 * @return string
 *
 */
function pms_process_restriction_content_message( $type, $user_ID, $post_id = 0 ) {

    $message    = pms_get_restriction_content_message( $type, $post_id );
    $user_info  = get_userdata( $user_ID );

    if( class_exists( 'PMS_Merge_Tags' ) )
        $message = PMS_Merge_Tags::process_merge_tags( $message, $user_info, '' );

    return $message;
}


/**
 * Return the restriction message to be displayed to the user. If the current post is not restricted / it was not checked
 * to see if it is restricted an empty string is returned
 *
 * @param int $post_id
 *
 * @return string
 *
 */
function pms_get_restricted_post_message( $post_id = 0 ) {

    global $post, $user_ID, $pms_show_content;

    $post_obj = $post;

    if( ! empty( $post_id ) )
        $post_obj = get_post( $post_id );

    $target_post_id = 0;

    if( isset( $post_obj->ID ) )
        $target_post_id = $post_obj->ID;

    if ( ! pms_post_has_direct_restrictions( $target_post_id ) ) {
        $restricted_term = pms_get_post_restricted_term( $target_post_id );

        if ( ! empty( $restricted_term ) )
            return pms_get_restricted_term_message( $restricted_term );
    }

    if( ! is_user_logged_in() )
        $message_type = 'logged_out';
    else
        $message_type = 'non_members';

    /**
     * Filter the message type for which to get the restriction message
     *
     * @param string $message_type
     * @param int    $post_id
     * @param int    $user_ID
     *
     */
    $message_type = apply_filters( 'pms_get_restricted_post_message_type', $message_type, $target_post_id, $user_ID );

    $message = pms_process_restriction_content_message( $message_type, $user_ID, $target_post_id );

    /**
     * Filter the restriction message before returning it
     *
     * @param string $message   - the custom message set by the admin in the Messages tab of the Settings page. If no messages are set there a default is returned
     * @param string $content   - the content of the current $post_obj object
     * @param WP_Post $post_obj - the current post object
     * @param int $user_ID      - the current user id
     *
     */
    global $wp_filter;
    if ( ! isset( $wp_filter[ 'pms_restriction_message_' . $message_type ] ) ) { // we should prevent this from calling itself to not enter a infinite loop. The post preview was called on this and if it was a restrict shortcode in it it would crash
        $message = apply_filters( 'pms_restriction_message_' . $message_type, $message, !empty( $post_obj->post_content ) ? $post_obj->post_content : '', $post_obj, $user_ID );
    }

    return do_shortcode( $message );

}


/**
 * Verifies whether the current term or provided term has any restrictions in place
 *
 * @param WP_Term|int|null $term
 *
 * @return bool
 *
 */
function pms_is_term_restricted( $term = null ) {

    static $pms_is_term_restricted_arr = array();

    if ( empty( $term ) ) {
        $term = get_queried_object();
    }

    if ( is_numeric( $term ) ) {
        $term = get_term( (int) $term );
    }

    if ( empty( $term ) || empty( $term->term_id ) ) {
        return false;
    }

    if ( isset( $pms_is_term_restricted_arr[ $term->term_id ] ) ) {
        return $pms_is_term_restricted_arr[ $term->term_id ];
    }

    if ( current_user_can( 'manage_options' ) || current_user_can( apply_filters( 'pms_content_restriction_capability', 'pms_bypass_content_restriction' ) ) ) {
        $pms_is_term_restricted_arr[ $term->term_id ] = false;
        return false;
    }

    $user_status             = get_term_meta( $term->term_id, 'pms-content-restrict-user-status', true );
    $term_subscription_plans = get_term_meta( $term->term_id, 'pms-content-restrict-subscription-plan' );
    $all_subscription_plans  = get_term_meta( $term->term_id, 'pms-content-restrict-all-subscription-plans', true );

    if ( ( ! empty( $term_subscription_plans ) || ! empty( $all_subscription_plans ) ) && is_user_logged_in() ) {

        if ( $all_subscription_plans == 'all' && pms_is_member() ) {
            $pms_is_term_restricted_arr[ $term->term_id ] = false;
            return false;
        } elseif ( pms_is_member( get_current_user_id(), $term_subscription_plans ) ) {
            $pms_is_term_restricted_arr[ $term->term_id ] = false;
            return false;
        } else {
            $pms_is_term_restricted_arr[ $term->term_id ] = true;
            return true;
        }

    } elseif ( ( ! empty( $user_status ) && $user_status == 'loggedin' ) || ! empty( $term_subscription_plans ) ) {

        if ( ! is_user_logged_in() ) {
            $pms_is_term_restricted_arr[ $term->term_id ] = true;
            return true;
        }

    }

    $pms_is_term_restricted_arr[ $term->term_id ] = false;

    return false;

}


/**
 * Returns the restriction message for a taxonomy term archive
 *
 * @param WP_Term|int|null $term
 *
 * @return string
 *
 */
function pms_get_restricted_term_message( $term = null ) {

    global $user_ID, $wp_embed;

    if ( empty( $term ) ) {
        $term = get_queried_object();
    }

    if ( is_numeric( $term ) ) {
        $term = get_term( (int) $term );
    }

    if ( empty( $term ) || empty( $term->term_id ) ) {
        return '';
    }

    $message_type = ! is_user_logged_in() ? 'logged_out' : 'non_members';
    $settings     = get_option( 'pms_content_restriction_settings' );

    if ( $message_type == 'logged_out' ) {
        $message = isset( $settings['logged_out'] ) ? $settings['logged_out'] : __( 'You do not have access to this content. You need to create an account.', 'paid-member-subscriptions' );
    } else {
        $message = isset( $settings['non_members'] ) ? $settings['non_members'] : __( 'You do not have access to this content. You need the proper subscription.', 'paid-member-subscriptions' );
    }

    $custom_message_enabled = get_term_meta( $term->term_id, 'pms-content-restrict-messages-enabled', true );

    if ( ! empty( $custom_message_enabled ) ) {
        $custom_message = get_term_meta( $term->term_id, 'pms-content-restrict-message-' . $message_type, true );

        if ( ! empty( $custom_message ) ) {
            $message = $custom_message;
        }
    }

    $user_info = get_userdata( $user_ID );

    if ( class_exists( 'PMS_Merge_Tags' ) ) {
        $message = PMS_Merge_Tags::process_merge_tags( $message, $user_info, '' );
    }

    $message = $wp_embed->autoembed( $message );

    add_filter( 'wp_kses_allowed_html', 'pms_wp_kses_allowed_html_iframe', 10, 2 );

    $message = wp_kses_post( $message );

    remove_filter( 'wp_kses_allowed_html', 'pms_wp_kses_allowed_html_iframe', 10, 2 );

    if ( function_exists( 'pms_restriction_message_wpautop' ) ) {
        $message = pms_restriction_message_wpautop( $message );
    }

    return do_shortcode( apply_filters( 'pms_restricted_term_message', $message, $term, $message_type ) );

}


/**
 * Returns the first restricted taxonomy term attached to a post
 *
 * @param int    $post_id
 * @param string $restriction_type Optional. Filter term by effective restriction type.
 *
 * @return WP_Term|false
 *
 */
function pms_get_post_restricted_term( $post_id = 0, $restriction_type = '' ) {

    if ( empty( $post_id ) )
        return false;

    $taxonomies = get_object_taxonomies( get_post_type( $post_id ), 'names' );

    if ( empty( $taxonomies ) )
        return false;

    foreach ( $taxonomies as $taxonomy ) {

        $terms = get_the_terms( $post_id, $taxonomy );

        if ( empty( $terms ) || is_wp_error( $terms ) )
            continue;

        foreach ( $terms as $term ) {

            if ( ! pms_is_term_restricted( $term ) )
                continue;

            if ( ! empty( $restriction_type ) ) {
                $settings            = get_option( 'pms_content_restriction_settings', array() );
                $term_restrict_type  = get_term_meta( $term->term_id, 'pms-content-restrict-type', true );
                $effective_type      = ( $term_restrict_type == 'default' || empty( $term_restrict_type ) ? ( ! empty( $settings['content_restrict_type'] ) ? $settings['content_restrict_type'] : 'message' ) : $term_restrict_type );

                if ( $effective_type !== $restriction_type )
                    continue;
            }

            return $term;

        }
    }

    return false;

}


/**
 * Returns the effective restriction type for a taxonomy term
 *
 * @param WP_Term|int $term
 *
 * @return string
 *
 */
function pms_get_term_restriction_type( $term ) {

    if ( is_numeric( $term ) ) {
        $term = get_term( (int) $term );
    }

    if ( empty( $term ) || empty( $term->term_id ) )
        return '';

    $settings              = get_option( 'pms_content_restriction_settings', array() );
    $term_restriction_type = get_term_meta( $term->term_id, 'pms-content-restrict-type', true );

    if ( $term_restriction_type == 'default' || empty( $term_restriction_type ) ) {
        return ( ! empty( $settings['content_restrict_type'] ) ? $settings['content_restrict_type'] : 'message' );
    }

    return $term_restriction_type;

}


/**
 * Returns the effective restriction type source for a single post when taxonomy restrictions are also present
 *
 * @param int            $post_id
 * @param WP_Term|false  $restricted_term
 *
 * @return string post|taxonomy
 *
 */
function pms_get_post_taxonomy_restriction_type_source( $post_id, $restricted_term = false ) {

    $user_status           = get_post_meta( $post_id, 'pms-content-restrict-user-status', true );
    $post_restriction_type = get_post_meta( $post_id, 'pms-content-restrict-type', true );

    if ( ! empty( $user_status ) && $user_status == 'loggedin' )
        return 'post';

    if ( pms_post_has_direct_subscription_restrictions( $post_id ) && ! empty( $post_restriction_type ) && $post_restriction_type !== 'default' )
        return 'post';

    if ( ! empty( $restricted_term ) )
        return 'taxonomy';

    return 'post';

}


/**
 * Returns the effective restriction type for a single post when taxonomy restrictions are also present
 *
 * @param int            $post_id
 * @param WP_Term|false  $restricted_term
 *
 * @return string
 *
 */
function pms_get_post_taxonomy_restriction_type( $post_id, $restricted_term = false ) {

    $settings              = get_option( 'pms_content_restriction_settings', array() );
    $post_restriction_type = get_post_meta( $post_id, 'pms-content-restrict-type', true );
    $type_source           = pms_get_post_taxonomy_restriction_type_source( $post_id, $restricted_term );

    if ( $type_source === 'taxonomy' && ! empty( $restricted_term ) )
        return pms_get_term_restriction_type( $restricted_term );

    if ( $post_restriction_type == 'default' || empty( $post_restriction_type ) )
        return ( ! empty( $settings['content_restrict_type'] ) ? $settings['content_restrict_type'] : 'message' );

    return $post_restriction_type;

}


/**
 * Handle single posts restricted through attached taxonomy terms, even when themes/builders bypass the_content
 *
 */
function pms_handle_single_post_taxonomy_restriction() {

    if ( ! is_singular() )
        return;

    if ( isset( $_GET['pay_gate_listener'] ) )
        return;

    global $post;

    if ( empty( $post ) || empty( $post->ID ) )
        return;

    $restricted_term = pms_get_post_restricted_term( $post->ID );

    if ( empty( $restricted_term ) )
        return;

    $effective_restriction_type = pms_get_post_taxonomy_restriction_type( $post->ID, $restricted_term );
    $restriction_type_source    = pms_get_post_taxonomy_restriction_type_source( $post->ID, $restricted_term );

    if ( $restriction_type_source !== 'taxonomy' || $effective_restriction_type !== 'message' )
        return;

    if ( ! pms_is_post_restricted( $post->ID ) )
        return;

    $restricted_message = pms_get_restricted_term_message( $restricted_term );
    $GLOBALS['pms_taxonomy_restriction_message_injected'] = true;

    $post->post_content = $restricted_message;
    $post->post_excerpt = '';

    global $wp_query;

    if ( ! empty( $wp_query->posts ) && isset( $wp_query->posts[0] ) ) {
        $wp_query->posts[0]->post_content = $restricted_message;
        $wp_query->posts[0]->post_excerpt = '';
    }

}
add_action( 'template_redirect', 'pms_handle_single_post_taxonomy_restriction', 9 );

/**
 * Checks to see if the current single post is restricted through an attached taxonomy term and redirects the user
 *
 */
function pms_restricted_single_post_taxonomy_redirect() {

    if( ! is_singular() )
        return;

    if( isset( $_GET['pay_gate_listener'] ) )
        return;

    global $post;

    if( empty( $post ) || empty( $post->ID ) )
        return;

    $restricted_term = pms_get_post_restricted_term( $post->ID );

    if ( empty( $restricted_term ) )
        return;

    if ( pms_get_post_taxonomy_restriction_type_source( $post->ID, $restricted_term ) !== 'taxonomy' )
        return;

    if ( pms_get_post_taxonomy_restriction_type( $post->ID, $restricted_term ) !== 'redirect' )
        return;

    if( ! pms_is_post_restricted( $post->ID ) )
        return;

    $redirect_url               = '';
    $settings                   = get_option( 'pms_content_restriction_settings', array() );
    $term_subscription_plans    = get_term_meta( $restricted_term->term_id, 'pms-content-restrict-subscription-plan' );
    $term_redirect_url_enabled  = get_term_meta( $restricted_term->term_id, 'pms-content-restrict-custom-redirect-url-enabled', true );
    $term_redirect_url          = get_term_meta( $restricted_term->term_id, 'pms-content-restrict-custom-redirect-url', true );
    $term_non_member_redirect   = get_term_meta( $restricted_term->term_id, 'pms-content-restrict-custom-non-member-redirect-url', true );
    $non_member_redirect_url    = '';

    $redirect_url = ( ! empty( $term_redirect_url_enabled ) && ! empty( $term_redirect_url ) ? $term_redirect_url : '' );
    $non_member_redirect_url = ( ! empty( $term_redirect_url_enabled ) && ! empty( $term_non_member_redirect ) ? $term_non_member_redirect : '' );

    if ( ! empty( $non_member_redirect_url ) && is_user_logged_in() && ! pms_is_member( get_current_user_id(), $term_subscription_plans ) ) {
        $redirect_url = $non_member_redirect_url;
    }

    if( empty( $redirect_url ) ) {
        $redirect_url = ( ! empty( $settings['content_restrict_redirect_url'] ) ? $settings['content_restrict_redirect_url'] : '' );
        $non_member_redirect_url = ( ! empty( $settings['content_restrict_non_member_redirect_url'] ) ? $settings['content_restrict_non_member_redirect_url'] : '' );

        if ( ! empty( $non_member_redirect_url ) && is_user_logged_in() && ! pms_is_member( get_current_user_id(), $term_subscription_plans ) ) {
            $redirect_url = $non_member_redirect_url;
        }
    }

    if( empty( $redirect_url ) )
        return;

    $current_url = pms_get_current_page_url();

    if( $current_url == $redirect_url )
        return;

    $add_redirect_to = apply_filters( 'pms_content_restriction_redirect_add_redirect_to_parameter', true, $current_url );

    $query_args = array();

    if ( $add_redirect_to ) {
        $query_args['redirect_to'] = $current_url;
    }

    $redirect_url = add_query_arg( $query_args, pms_add_missing_http( $redirect_url ) );

    nocache_headers();
    wp_redirect( apply_filters( 'pms_restricted_single_post_taxonomy_redirect_url', $redirect_url, $restricted_term, $post ) );
    exit;

}
add_action( 'template_redirect', 'pms_restricted_single_post_taxonomy_redirect', 8 );


/**
 * Checks to see if the current post is restricted and if any redirect URLs are in place
 * the user is redirected to the URL with the highest priority
 *
 */
function pms_restricted_post_redirect() {

    if( ! is_singular() )
        return;

    // don't redirect if an IPN request is made (useful for restricted front pages)
    if( isset( $_GET['pay_gate_listener'] ) )
        return;

    global $post;

    if( empty( $post ) || empty( $post->ID ) )
        return;

    /**
     * Filter to change the $post_id of the current restricted post
     *
     * This is useful when wanting the redirect of the current post to actually
     * be from another post
     *
     */
    $post_id = apply_filters( 'pms_restricted_post_redirect_post_id', $post->ID );

    $redirect_url             = '';
    $post_restriction_type    = get_post_meta( $post_id, 'pms-content-restrict-type', true );
    $settings                 = get_option( 'pms_content_restriction_settings', array() );
    $general_restriction_type = ( ! empty( $settings['content_restrict_type'] ) ? $settings['content_restrict_type'] : 'message' );
    $post_subscription_plans  = get_post_meta( $post_id, 'pms-content-restrict-subscription-plan' );
    $non_member_redirect_url  = '';

    if( ! in_array( $post_restriction_type, array( '', 'default', 'redirect' ) ) )
        return;

    if( $post_restriction_type !== 'redirect' && $general_restriction_type !== 'redirect' )
        return;

    if( ! pms_is_post_restricted( $post_id ) )
        return;

    /**
     * Get the redirect URL from the post meta if enabled
     *
     */
    if( $post_restriction_type === 'redirect' ) {

        $post_redirect_url_enabled      = get_post_meta( $post_id, 'pms-content-restrict-custom-redirect-url-enabled', true );
        $post_redirect_url              = get_post_meta( $post_id, 'pms-content-restrict-custom-redirect-url', true );
        $post_non_member_redirect_url   = get_post_meta( $post_id, 'pms-content-restrict-custom-non-member-redirect-url', true );

        $redirect_url = ( ! empty( $post_redirect_url_enabled ) && ! empty( $post_redirect_url ) ? $post_redirect_url : '' );
        $non_member_redirect_url = ( ! empty( $post_redirect_url_enabled ) && ! empty( $post_non_member_redirect_url ) ? $post_non_member_redirect_url : '' );

    }
    if ( !empty( $non_member_redirect_url) ){
        if ( is_user_logged_in() && isset( $post_subscription_plans ) && !pms_is_member( get_current_user_id(), $post_subscription_plans ) ){
            $redirect_url = $non_member_redirect_url;
        }
    }

    /**
     * If the post doesn't have a custom redirect URL set, get the default from the Settings page
     *
     */
    if( empty( $redirect_url ) ) {

        $redirect_url = ( ! empty( $settings['content_restrict_redirect_url'] ) ? $settings['content_restrict_redirect_url'] : '' );
        $non_member_redirect_url = ( ! empty( $settings['content_restrict_non_member_redirect_url'] ) ? $settings['content_restrict_non_member_redirect_url'] : '' );
        if ( !empty( $non_member_redirect_url ) ){
            if ( is_user_logged_in() && isset( $post_subscription_plans ) && !pms_is_member( get_current_user_id(), $post_subscription_plans ) ){
                $redirect_url = $non_member_redirect_url;
            }
        }
    }

    if( empty( $redirect_url ) )
        return;

    /**
     * To avoid a redirect loop we break in case the redirect URL is the same as
     * the current page URl
     *
     */
    $current_url = pms_get_current_page_url();

    if( $current_url == $redirect_url )
        return;

    $add_redirect_to = apply_filters( 'pms_content_restriction_redirect_add_redirect_to_parameter', true, $current_url );

    $query_args = array();

    if ( $add_redirect_to ) {
        $query_args['redirect_to'] = $current_url;
    }

    // Pass the correct referer URL forward
    $redirect_url = add_query_arg( $query_args, pms_add_missing_http( $redirect_url ) );

    /**
     * Redirect
     *
     */
    nocache_headers();
    wp_redirect( apply_filters( 'pms_restricted_post_redirect_url', $redirect_url ) );
    exit;

}
add_action( 'template_redirect', 'pms_restricted_post_redirect' );

/**
 * Checks to see if the current taxonomy archive is restricted and if any redirect URLs are in place
 * the user is redirected to the URL with the highest priority
 *
 */
function pms_restricted_term_redirect() {

    if ( ! is_category() && ! is_tag() && ! is_tax() )
        return;

    if ( isset( $_GET['pay_gate_listener'] ) )
        return;

    $term = get_queried_object();

    if ( empty( $term ) || empty( $term->term_id ) )
        return;

    $redirect_url             = '';
    $term_restriction_type    = get_term_meta( $term->term_id, 'pms-content-restrict-type', true );
    $settings                 = get_option( 'pms_content_restriction_settings', array() );
    $general_restriction_type = ( ! empty( $settings['content_restrict_type'] ) ? $settings['content_restrict_type'] : 'message' );
    $term_subscription_plans  = get_term_meta( $term->term_id, 'pms-content-restrict-subscription-plan' );
    $non_member_redirect_url  = '';

    if ( $term_restriction_type !== 'redirect' && $general_restriction_type !== 'redirect' )
        return;

    if ( ! in_array( $term_restriction_type, array( '', 'default', 'redirect' ) ) )
        return;

    if ( ! pms_is_term_restricted( $term ) )
        return;

    if ( $term_restriction_type === 'redirect' ) {

        $term_redirect_url_enabled    = get_term_meta( $term->term_id, 'pms-content-restrict-custom-redirect-url-enabled', true );
        $term_redirect_url            = get_term_meta( $term->term_id, 'pms-content-restrict-custom-redirect-url', true );
        $term_non_member_redirect_url = get_term_meta( $term->term_id, 'pms-content-restrict-custom-non-member-redirect-url', true );

        $redirect_url = ( ! empty( $term_redirect_url_enabled ) && ! empty( $term_redirect_url ) ? $term_redirect_url : '' );
        $non_member_redirect_url = ( ! empty( $term_redirect_url_enabled ) && ! empty( $term_non_member_redirect_url ) ? $term_non_member_redirect_url : '' );

    }

    if ( ! empty( $non_member_redirect_url ) ) {
        if ( is_user_logged_in() && isset( $term_subscription_plans ) && ! pms_is_member( get_current_user_id(), $term_subscription_plans ) ) {
            $redirect_url = $non_member_redirect_url;
        }
    }

    if ( empty( $redirect_url ) ) {

        $redirect_url = ( ! empty( $settings['content_restrict_redirect_url'] ) ? $settings['content_restrict_redirect_url'] : '' );
        $non_member_redirect_url = ( ! empty( $settings['content_restrict_non_member_redirect_url'] ) ? $settings['content_restrict_non_member_redirect_url'] : '' );

        if ( ! empty( $non_member_redirect_url ) ) {
            if ( is_user_logged_in() && isset( $term_subscription_plans ) && ! pms_is_member( get_current_user_id(), $term_subscription_plans ) ) {
                $redirect_url = $non_member_redirect_url;
            }
        }

    }

    if ( empty( $redirect_url ) )
        return;

    $current_url = pms_get_current_page_url();

    if ( $current_url == $redirect_url )
        return;

    $add_redirect_to = apply_filters( 'pms_content_restriction_redirect_add_redirect_to_parameter', true, $current_url );

    $query_args = array();

    if ( $add_redirect_to ) {
        $query_args['redirect_to'] = $current_url;
    }

    $redirect_url = add_query_arg( $query_args, pms_add_missing_http( $redirect_url ) );

    nocache_headers();
    wp_redirect( apply_filters( 'pms_restricted_term_redirect_url', $redirect_url, $term ) );
    exit;

}
add_action( 'template_redirect', 'pms_restricted_term_redirect', 1 );

/* handle the Template restrict type case */
add_filter( 'template_include', 'pms_restrict_page_template', 999 );
function pms_restrict_page_template( $template ) {

    //don't do anything for archives
    if( !is_singular() )
        return $template;

    global $post;
    $post_restriction_type    = get_post_meta( $post->ID, 'pms-content-restrict-type', true );
    $settings                 = get_option( 'pms_content_restriction_settings', array() );

    if( $post_restriction_type == 'default' || empty( $post_restriction_type ) )
        $post_restriction_type = ( ! empty( $settings['content_restrict_type'] ) ? $settings['content_restrict_type'] : 'message' );

    //only continue if we have a template restriction type
    if( $post_restriction_type == 'template' ) {
        $restrict_template =  ( ! empty( $settings['content_restrict_template'] ) ? $settings['content_restrict_template'] : '' );

        if( !empty( $restrict_template ) ) {
            if ( pms_is_post_restricted( $post->ID ) ) {

                if ( did_action( 'elementor/loaded' ) ) {
                    if( strpos( $restrict_template, 'elementor_template_' ) !== false ) {
                        $elementor_template = pms_elementor_render_template( str_replace( 'elementor_template_', '', $restrict_template ) );

                        if ( ! empty( $elementor_template ) )
                            return $elementor_template;

                        return $template;
                    }
                }

                $new_template = locate_template( array( $restrict_template ) );

                if ( !empty( $new_template ) )
                    return $new_template;

            }
        }
    }

    return $template;
}

function pms_restrict_single_post_taxonomy_template( $template ) {

    if( !is_singular() )
        return $template;

    global $post;

    if ( empty( $post ) || empty( $post->ID ) )
        return $template;

    $restricted_term = pms_get_post_restricted_term( $post->ID );

    if ( empty( $restricted_term ) )
        return $template;

    if ( pms_get_post_taxonomy_restriction_type_source( $post->ID, $restricted_term ) !== 'taxonomy' )
        return $template;

    if ( pms_get_post_taxonomy_restriction_type( $post->ID, $restricted_term ) !== 'template' )
        return $template;

    $settings = get_option( 'pms_content_restriction_settings', array() );
    $restrict_template = ( ! empty( $settings['content_restrict_template'] ) ? $settings['content_restrict_template'] : '' );

    if ( empty( $restrict_template ) || ! pms_is_post_restricted( $post->ID ) )
        return $template;

    if ( did_action( 'elementor/loaded' ) ) {
        if( strpos( $restrict_template, 'elementor_template_' ) !== false ) {
            $elementor_template = pms_elementor_render_template( str_replace( 'elementor_template_', '', $restrict_template ) );

            if ( ! empty( $elementor_template ) )
                return $elementor_template;

            return $template;
        }
    }

    $new_template = locate_template( array( $restrict_template ) );

    if ( ! empty( $new_template ) )
        return $new_template;

    return $template;
}
add_filter( 'template_include', 'pms_restrict_single_post_taxonomy_template', 998 );

function pms_restrict_taxonomy_template( $template ) {

    if ( ! is_category() && ! is_tag() && ! is_tax() )
        return $template;

    $term = get_queried_object();

    if ( empty( $term ) || empty( $term->term_id ) )
        return $template;

    $term_restriction_type = get_term_meta( $term->term_id, 'pms-content-restrict-type', true );
    $settings              = get_option( 'pms_content_restriction_settings', array() );

    if ( $term_restriction_type == 'default' || empty( $term_restriction_type ) )
        $term_restriction_type = ( ! empty( $settings['content_restrict_type'] ) ? $settings['content_restrict_type'] : 'message' );

    if ( $term_restriction_type == 'template' ) {
        $restrict_template = ( ! empty( $settings['content_restrict_template'] ) ? $settings['content_restrict_template'] : '' );

        if ( ! empty( $restrict_template ) && pms_is_term_restricted( $term ) ) {

            if ( did_action( 'elementor/loaded' ) ) {
                if ( strpos( $restrict_template, 'elementor_template_' ) !== false ) {
                    $elementor_template = pms_elementor_render_template( str_replace( 'elementor_template_', '', $restrict_template ) );

                    if ( ! empty( $elementor_template ) )
                        return $elementor_template;

                    return $template;
                }
            }

            $new_template = locate_template( array( $restrict_template ) );

            if ( ! empty( $new_template ) )
                return $new_template;

        }
    }

    return $template;
}
add_filter( 'template_include', 'pms_restrict_taxonomy_template', 999 );

function pms_restrict_taxonomy_message() {

    if ( ! is_category() && ! is_tag() && ! is_tax() )
        return;

    $term = get_queried_object();

    if ( empty( $term ) || empty( $term->term_id ) )
        return;

    $term_restriction_type = get_term_meta( $term->term_id, 'pms-content-restrict-type', true );
    $settings              = get_option( 'pms_content_restriction_settings', array() );

    if ( $term_restriction_type == 'default' || empty( $term_restriction_type ) )
        $term_restriction_type = ( ! empty( $settings['content_restrict_type'] ) ? $settings['content_restrict_type'] : 'message' );

    if ( $term_restriction_type !== 'message' || ! pms_is_term_restricted( $term ) )
        return;

    global $wp_query;

    if ( isset( $wp_query->posts ) )
        $wp_query->posts = array();

    if ( isset( $wp_query->post_count ) )
        $wp_query->post_count = 0;

    if ( isset( $wp_query->found_posts ) )
        $wp_query->found_posts = 0;

    if ( isset( $wp_query->max_num_pages ) )
        $wp_query->max_num_pages = 0;
}
add_action( 'template_redirect', 'pms_restrict_taxonomy_message', 5 );

function pms_get_restricted_taxonomy_archive_description( $description ) {

    if ( ! is_category() && ! is_tag() && ! is_tax() )
        return $description;

    $term = get_queried_object();

    if ( empty( $term ) || ! pms_is_term_restricted( $term ) )
        return $description;

    return pms_get_restricted_term_message( $term );
}
add_filter( 'get_the_archive_description', 'pms_get_restricted_taxonomy_archive_description', 999 );

function pms_elementor_render_template( $template_id ) {
    if ( did_action( 'elementor/loaded' ) ) {

        $content = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id, true );  

        if ( !empty( $content ) ) {
            get_header();
            echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            get_footer();
            exit; // Prevent WordPress from loading the default template
        }

    }

    return '';
}


/* if the Static Posts Page has a restriction on it hijack the query */
add_action( 'template_redirect', 'pms_content_restriction_posts_page_handle_query', 1 );
function pms_content_restriction_posts_page_handle_query(){
    if( is_home() ){
        $posts_page_id = get_option( 'page_for_posts' );
        if( $posts_page_id ) {
            if (pms_is_post_restricted($posts_page_id)) {
                pms_content_restriction_force_page($posts_page_id);
            }
        }
    }
}


/* if the Static Posts Page has a restriction on it hijack the template back to the Page Template */
add_filter( 'template_include', 'pms_content_restriction_posts_page_template', 100 );
function pms_content_restriction_posts_page_template( $template ){
    if( is_home() ){
        $posts_page_id = get_option( 'page_for_posts' );
        if( $posts_page_id ) {
            if (pms_is_post_restricted($posts_page_id)) {
                $template = get_page_template();
            }
        }
    }
    return $template;
}

/* Change the query to a single post */
function pms_content_restriction_force_page( $posts_page_id ){
    if( $posts_page_id ) {
        global $wp_query, $post;
        $post = get_post($posts_page_id);
        $wp_query->posts = array($post);
        $wp_query->post_count = 1;
        $wp_query->is_singular = true;
        $wp_query->is_singule = true;
        $wp_query->is_archive = false;
    }
}

if( function_exists( 'bp_is_current_component' ) )
    add_filter( 'pms_restricted_post_redirect_post_id', 'pms_restrict_buddypress_pages' );

function pms_restrict_buddypress_pages( $post_id ){

    if( bp_is_current_component('activity') ){

        $bp_pages = get_option('bp-pages');
        $post_id  = $bp_pages['activity'];

    }

    if( bp_is_current_component('members') ){

        $bp_pages = get_option('bp-pages');
        $post_id  = $bp_pages['members'];

    }

    return $post_id;

}


/**
 * Exclude Restricted Posts From Query
 */

$settings = get_option( 'pms_content_restriction_settings' );

if( !isset( $settings['pms_includeRestrictedPosts'] ) || $settings['pms_includeRestrictedPosts'] != 'yes' ){

    /* Exclude restricted posts and pages from the main queries like blog, archive and taxonomy pages. */
    add_action( 'pre_get_posts', 'pmsc_exclude_post_from_query', 40 );
    function pmsc_exclude_post_from_query( $query ) {

        if( !function_exists( 'pms_is_post_restricted' ) || is_admin() || is_singular() )
            return;
        
        if( isset( $query->query_vars['wc_query'] ) && $query->query_vars['wc_query'] == 'product_query' )
            return;

        // Skip Ultimate Member queries
        if( isset( $query->query_vars['um_action'] ) || isset( $query->query_vars['um_user'] ) )
            return;

        if( $query->is_main_query() || ( $query->is_search() && isset( $query->query_vars ) && isset( $query->query_vars['s'] ) ) ) {

            remove_action( 'pre_get_posts', 'pmsc_exclude_post_from_query', 40 );

            $args = $query->query_vars;

            $args['suppress_filters'] = true;
            $args['fields']           = 'ids';

            // setup paged arguments
            // double these params so we take care of results from the second page as well
            if( !empty( $query->query_vars['paged'] ) ){

                $args['paged'] = round( $args['paged'] / 2 );

                if( !empty( $args['posts_per_page'] ) )
                    $args['posts_per_page'] = $args['posts_per_page'] * 2;
                else
                    $args['posts_per_page'] = get_option( 'posts_per_page' ) * 2;

            }

            if( is_search() )
                $args['post_type'] = 'any';

            $restricted_posts = get_posts( $args );

            $previous_restricted_ids = $query->get( 'post__not_in' );
            if ( ! is_array( $previous_restricted_ids ) ) {
                $previous_restricted_ids = array();
            }

            $restricted_ids = array_filter( $restricted_posts, 'pms_is_post_restricted' );

            $query->set( 'post__not_in', array_merge( $previous_restricted_ids, $restricted_ids ) );
        }
    }

    //Remove restricted forums from the main bbPress query
    add_filter( 'bbp_after_has_forums_parse_args', 'pmsc_exclude_restricted_forums_from_main_query' );
    function pmsc_exclude_restricted_forums_from_main_query( $vars ) {
        if( !function_exists( 'pms_is_post_restricted' ) ) return;

        $query = new WP_Query( $vars );

        $forum_ids = array();

        foreach ( $query->posts as $forum ) {
            $forum_ids[] = $forum->ID;
        }

        $previous_restricted_forums = $query->get( 'post__not_in' );
        if ( ! is_array( $previous_restricted_forums ) ) {
            $previous_restricted_forums = array();
        }

        $restricted_forums = array_filter( $forum_ids, 'pms_is_post_restricted' );
        $updated_restricted_forums = array_merge( $previous_restricted_forums, $restricted_forums );

        $vars['post__not_in'] = $updated_restricted_forums;

        return $vars;
    }

    //Alters the output of the query that displays the Category Archive (including Shop) pages, filtering the restricted products from the output
    add_action( 'pre_get_posts', 'pms_exclude_restricted_products_from_woocoommerce_category_queries', 11 );
    function pms_exclude_restricted_products_from_woocoommerce_category_queries( $query ) {
        if( is_admin() || !function_exists('pms_is_post_restricted') )
            return;

        $settings = get_option( 'pms_woocommerce_settings' );

        if( !isset( $settings['exclude_products_from_queries'] ) || $settings['exclude_products_from_queries'] != 1 )
            return;

        if( isset( $query->query_vars['wc_query'] ) && $query->query_vars['wc_query'] == 'product_query' ){

            remove_action( 'pre_get_posts', 'pms_exclude_restricted_products_from_woocoommerce_category_queries', 11 );

            $args = $query->query_vars;

            $args['suppress_filters'] = true;
            $args['posts_per_page']   = -1;

            $previous_restricted_ids = $query->get( 'post__not_in' );
            if ( ! is_array( $previous_restricted_ids ) ) {
                $previous_restricted_ids = array();
            }

            $transient_key = 'pms_content_restriction_products_query_' . md5( serialize( $args ) );
            if ( false === ( $products = get_transient( $transient_key ) ) ) {
                $products = wc_get_products( $args );
                set_transient( $transient_key, $products, 2 * HOUR_IN_SECONDS );
            }

            $product_ids = array();

            if( !empty( $products ) ){
                foreach ($products as $product) {
                    $product_ids[] = $product->get_id();
                }
    
                $restricted_ids = array_filter( $product_ids, 'pms_is_post_restricted' );
    
                $updated_restricted_ids = array_merge( $previous_restricted_ids, $restricted_ids );
                $query->set( 'post__not_in', $updated_restricted_ids );
            }

        }
    }

    //Alters the output of the [products] shortcode from WooCommerce, filtering restricted products from the output
    add_filter( 'woocommerce_shortcode_products_query', 'pmsc_exclude_restricted_products_from_woocoommerce_products_shortcode_queries' );
    function pmsc_exclude_restricted_products_from_woocoommerce_products_shortcode_queries( $query_args ) {
        if( !is_admin() && function_exists('pms_is_post_restricted') ) {
            $settings = get_option( 'pms_woocommerce_settings' );

            if( !isset( $settings['exclude_products_from_queries'] ) || $settings['exclude_products_from_queries'] != 1 )
                return $query_args;
    
            $posts_per_page = $query_args['posts_per_page'];

            $query_args['suppress_filters'] = true;
            $query_args['posts_per_page'] = '-1';

            $products = wc_get_products( $query_args );

            $query_args['posts_per_page'] = $posts_per_page;

            $product_ids = array();

            foreach ($products as $product) {
                $product_ids[] = $product->get_id();
            }

            $previous_restricted_ids = isset( $query_args['post__not_in'] ) ? $query_args['post__not_in'] : array();

            if ( ! is_array( $previous_restricted_ids ) ) {
                $previous_restricted_ids = array();
            }

            $restricted_ids = array_filter( $product_ids, 'pms_is_post_restricted' );
            $updated_restricted_ids = array_merge( $previous_restricted_ids, $restricted_ids );

            $query_args['post__not_in'] = $updated_restricted_ids;
        }

        return $query_args;
    }

    // Exclude restricted posts from Elementor
    add_filter( 'elementor/query/query_args', 'pmsc_exclude_posts_from_elementor' );
    function pmsc_exclude_posts_from_elementor( $query_args ) {

        // check if the form is being displayed in the Elementor editor
        $is_elementor_edit_mode = false;
        if( class_exists ( '\Elementor\Plugin' ) ){
            $is_elementor_edit_mode = \Elementor\Plugin::$instance->editor->is_edit_mode();
        }

        if ( !$is_elementor_edit_mode && !is_admin() && function_exists( 'pms_is_post_restricted' ) ) {
            $args = $query_args;
            $args['suppress_filters'] = true;
            $args['fields']           = 'ids';

            if( !empty( $query_args['paged'] ) ){

                $args['paged'] = round( $args['paged'] / 2 );

                if( !empty( $args['posts_per_page'] ) )
                    $args['posts_per_page'] = $args['posts_per_page'] * 2;
                else
                    $args['posts_per_page'] = get_option( 'posts_per_page' ) * 2;

            }

            if( is_search() )
                $args['post_type'] = 'any';

            $restricted_posts = get_posts( $args );

            $restricted_ids = array_filter( $restricted_posts, 'pms_is_post_restricted' );

            if ( ! empty( $restricted_ids ) ) {
                if ( isset( $query_args['post__not_in'] ) && is_array( $query_args['post__not_in'] ) ) {
                    $query_args['post__not_in'] = array_merge( $query_args['post__not_in'], $restricted_ids );
                } else {
                    $query_args['post__not_in'] = $restricted_ids;
                }
            }
        }
        return $query_args;
    }

}
