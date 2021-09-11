<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

//Migrate the old single option for settings to the new structure
add_action( 'init', 'pms_migrate_old_settings_to_new' );
function pms_migrate_old_settings_to_new() {
    remove_filter( 'option_pms_settings', 'pms_recreate_old_settings', 99 );

    $old_settings = get_option( 'pms_settings' );

    add_filter( 'option_pms_settings', 'pms_recreate_old_settings', 99, 2);

    if ( get_option( 'pms_already_migrated_options' ) == 'yes' || empty( $old_settings ) ) return;

    $new_settings = array( 'general', 'payments', 'emails', 'woocommerce', 'invoices', 'recaptcha' );

    foreach( $new_settings as $setting ) {
        if ( !empty( $old_settings[$setting] ) )
            update_option( 'pms_' . $setting . '_settings', $old_settings[$setting] );
    }

    //Content Restriction
    $content_restriction_settings = array();

    $keys = array( 'content_restrict_type', 'content_restrict_redirect_url', 'content_restrict_template' );

    foreach( $keys as $key ) {
        if ( !empty( $old_settings[$key] ) )
            $content_restriction_settings[$key] = $old_settings[$key];
    }

    if ( !empty( $old_settings['messages']['logged_out'] ) )
        $content_restriction_settings['logged_out'] = $old_settings['messages']['logged_out'];

    if ( !empty( $old_settings['messages']['non_members'] ) )
        $content_restriction_settings['non_members'] = $old_settings['messages']['non_members'];

    if ( !empty( $old_settings['messages']['purchasing_restricted'] ) )
        $content_restriction_settings['purchasing_restricted'] = $old_settings['messages']['purchasing_restricted'];

    if ( !empty( $old_settings['general']['restricted_post_preview'] ) )
        $content_restriction_settings['restricted_post_preview'] = $old_settings['general']['restricted_post_preview'];

    update_option( 'pms_content_restriction_settings', $content_restriction_settings );

    update_option( 'pms_already_migrated_options', 'yes' );
}

//Recreate old settings option from the new one when requested
add_filter( 'option_pms_settings', 'pms_recreate_old_settings', 99, 2);
function pms_recreate_old_settings( $value, $option ) {
    $settings = array();

    $new_settings_slug = array( 'general', 'payments', 'emails', 'woocommerce', 'invoices', 'recaptcha' );

    foreach( $new_settings_slug as $slug ) {
        $new_setting = get_option( 'pms_' . $slug . '_settings' );

        if ( !empty( $new_setting ) )
            $settings[$slug] = $new_setting;
    }

    $content_restriction_settings = get_option( 'pms_content_restriction_settings' );

    $keys = array( 'content_restrict_type', 'content_restrict_redirect_url', 'content_restrict_template' );

    foreach( $keys as $key ) {
        if ( !empty( $content_restriction_settings[$key] ) )
            $settings[$key] = $content_restriction_settings[$key];
    }

    $keys = array( 'logged_out', 'non_members', 'purchasing_restricted' );

    foreach( $keys as $key ) {
        if ( !empty( $content_restriction_settings[$key] ) )
            $settings['messages'][$key] = $content_restriction_settings[$key];
    }

    if ( !empty( $content_restriction_settings['restricted_post_preview'] ) )
        $settings['general']['restricted_post_preview'] = $content_restriction_settings['restricted_post_preview'];

    return $settings;
}

//Handle Labels Edit migration from a plugin to a module with a misc setting
add_action( 'plugins_loaded', 'pms_handle_labels_edit_migration', 10 );
function pms_handle_labels_edit_migration(){

    //if it's triggered in the frontend we need this include
    if( !function_exists('is_plugin_active') )
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    $addon_slug = 'pms-add-on-labels-edit/index.php';
    if( is_plugin_active( $addon_slug ) ) {
        if( is_multisite() ){
            if( is_plugin_active_for_network($addon_slug) )
                deactivate_plugins($addon_slug, true);
            else
                deactivate_plugins($addon_slug, true, false);
        }
        else {
            deactivate_plugins($addon_slug, true);
        }


        $misc_settings = get_option('pms_misc_settings', array());
        $misc_settings['labels-edit'] = 'enabled';
        update_option('pms_misc_settings', $misc_settings);
    }

}