<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Return if LearnDash Plugin is not active
if ( ! is_plugin_active('sfwd-lms/sfwd_lms.php') ) return;

// Return if LearnDash meta-box is disabled
if ( apply_filters( 'pms_post_content_restriction_learndash_meta_box_disable', false ) ) return;


/**
 * Remove PMS Content Restriction meta-box from LearnDash: courses, lessons, and quizzes
 *
 */
function pms_content_restriction_remove_learndash_cpts( $post_types ) {
    $learndash_post_types = array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-quiz' );

    foreach ( $post_types as $key => $slug ) {
        if ( in_array( $slug, $learndash_post_types) )
            unset( $post_types[$key] );
    }

    return $post_types;
}
add_filter( 'pms_post_content_restriction_post_types', 'pms_content_restriction_remove_learndash_cpts' );


/**
 * Initializes the LearnDash meta-box
 *
 */
function pms_init_learndash_meta_box() {
    $post_types = array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-quiz' );

    foreach( $post_types as $post_type ) {
        add_meta_box( 'pms_post_content_restriction_learndash', __( 'Content Restriction', 'paid-member-subscriptions' ), 'pms_learndash_meta_box_content', $post_type, 'normal' );
    }

}
add_action( 'add_meta_boxes', 'pms_init_learndash_meta_box' );


/**
 * Output callback for the LearnDash meta-box
 *
 */
function pms_learndash_meta_box_content() {
    echo '<div class="pms-icon-wrapper">
              <img id="pms-icon" src="'. esc_url( PMS_PLUGIN_DIR_URL ) . 'assets/images/pms-logo.svg" alt="Paid Member Subscriptions PRO" title="Paid Member Subscriptions PRO">
              <span class="dashicons dashicons-plus"></span>
              <img id="learndash-logo" src="' . esc_url( PMS_PLUGIN_DIR_URL ) . 'assets/images/learn-dash-logo.png" alt="">
          </div>';

    echo '<h4>' . esc_html( __( 'Create member only content with just a few clicks.', 'paid-member-subscriptions' ) ) . '</h4>';

    echo '<p>' . esc_html( __( "Allow only members to have access to courses, lessons and quizzes with Paid Member Subscriptions PRO.", 'paid-member-subscriptions' ) ). '</p>';

    echo '<a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_content=content-restriction-learndash&utm_campaign=PMSFree#pricing" target="_blank" class="button-primary">' . esc_html( __( 'Upgrade to PRO', 'paid-member-subscriptions' ) ) . '</a>';
}