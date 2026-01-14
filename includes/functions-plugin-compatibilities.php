<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * This file has the sole purpose to help solve compatibility issues with other plugins/themes
 *
 */

/****************************************************
 * Theme Name: Eduma
 * Plugin URI: https://themeforest.net/item/education-wordpress-theme-education-wp/14058034
 ****************************************************/


 /**
  * Do not autologin if the registration happened on a PMS form
  *
  */
 if( function_exists( 'thim_register_extra_fields' ) ) {
     remove_action( 'user_register', 'thim_register_extra_fields', 1000 );
     add_action( 'user_register', 'pms_thim_register_extra_fields', 1001 );

     function pms_thim_register_extra_fields( $user_id ) {

         //if PMS form, skip
         if ( isset( $_POST['pmstkn'] ) )
             return;

         $user_data = array();
         $user_data['ID'] = $user_id;
         if (!empty($_POST['password'])) {
             $user_data['user_pass'] = $_POST['password']; //phpcs:ignore  WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
             add_filter('send_password_change_email', '__return_false');
         }
         $new_user_id = wp_update_user($user_data);

         // Login after registered
         if (!is_admin()) {
             wp_set_current_user($user_id);
             wp_set_auth_cookie($user_id);
             wp_new_user_notification($user_id, null, 'both');

             if (isset($_POST['level']) && $_POST['level'] && isset($_POST['token']) && $_POST['token'] && isset($_POST['gateway']) && $_POST['gateway']) {//phpcs:ignore  WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                 return;
             }

             if (isset($_REQUEST['level']) && $_REQUEST['level'] && isset($_REQUEST['review']) && $_REQUEST['review'] && isset($_REQUEST['token']) && $_REQUEST['token'] && isset($_REQUEST['PayerID']) && $_REQUEST['PayerID']) { //phpcs:ignore  WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                 return;
             }

             if ((isset($_POST['billing_email']) && !empty($_POST['billing_email'])) || (isset($_POST['bconfirmemail']) && !empty($_POST['bconfirmemail']))) {
                 return;
             } else {
                 if (!empty($_REQUEST['redirect_to'])) {
                     wp_redirect(wp_sanitize_redirect( $_REQUEST['redirect_to'] ));
                 } else {
                     $theme_options_data = get_theme_mods();
                     if (!empty($_REQUEST['option']) && $_REQUEST['option'] === 'moopenid') {
                         if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
                             $http = "https://";
                         } else {
                             $http = "http://";
                         }

                         $redirect_url = site_url() . '/';
                         if( isset( $_SERVER["HTTP_HOST"] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
                             $redirect_url = urldecode( html_entity_decode( esc_url( $http . sanitize_text_field( $_SERVER["HTTP_HOST"] ) . str_replace('?option=moopenid', '', esc_url_raw( $_SERVER['REQUEST_URI'] ) ) ) ) );

                             if ( html_entity_decode( esc_url( remove_query_arg('message', $redirect_url) ) ) == wp_login_url() || strpos( sanitize_text_field( $_SERVER['REQUEST_URI'] ), 'wp-login.php') !== false || strpos( sanitize_text_field( $_SERVER['REQUEST_URI'] ), 'wp-admin') !== false) {
                                 $redirect_url = site_url() . '/';
                             }
                         }

                         wp_redirect($redirect_url);

                         return;
                     }

                     if (!empty($theme_options_data['thim_register_redirect'])) {
                         wp_redirect($theme_options_data['thim_register_redirect']);
                     } else {
                         wp_redirect(home_url());
                     }
                 }
                 exit();
             }
         }
     }
 }

if( is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) ){
    add_action( 'pms_process_login_start', 'pms_learndash_compatibility_login_start' );
    function pms_learndash_compatibility_login_start (){
        remove_action( 'wp_login_failed', 'learndash_login_failed', 1 );
    }
    add_action( 'pms_process_login_end', 'pms_learndash_compatibility_login_end' );
    function pms_learndash_compatibility_login_end (){
        add_action( 'wp_login_failed', 'learndash_login_failed', 1, 1 );
    }
}

/****************************************************
 * Theme Name: Valenti
 * Plugin URI: https://mhthemes.com/wp-themes/valenti-wordpress-theme/https://themeforest.net/item/education-wordpress-theme-education-wp/14058034
 ****************************************************/

if( function_exists( 'valenti_widget_areas' ) ) {
    add_action('after_setup_theme','pms_compatibility_disable_valenti_sidebar_registration_on_pms_CPTs', 17);
    function pms_compatibility_disable_valenti_sidebar_registration_on_pms_CPTs() {
    
        if( !empty( $_GET['post'] ) && isset( $_GET['post'] ) ){
            $post = get_post( absint( $_GET['post'] ) );
    
            $post_type = $post->post_type;
    
        } else if( !empty( $_GET['post_type'] ) && isset( $_GET['post_type'] ) ){
            $post_type = sanitize_text_field( $_GET['post_type'] );
        }
    
        if( in_array( $post_type, array('pms-subscription','pms-discount-codes', 'pms-email-reminders') ) ){
            remove_action( 'widgets_init', 'valenti_widget_areas' );
        }
    
    }
}
