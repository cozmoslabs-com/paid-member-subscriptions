<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Returns the list of PMS areas available for granular role permissions
 *
 * - keys are area slugs, values are translated UI labels
 * - excludes pms-settings-page from the first implementation phase
 *
 */
function pms_role_permissions_get_pages() {

    $pages = array(
        'pms-dashboard-page'     => 'Dashboard',
        'pms-basic-info-page'    => 'Basic Information',
        'pms-members-page'       => 'Members',
        'pms-subscriptions-page' => 'Subscriptions',
        'pms-subscription'       => 'Subscription Plans',
        'pms-discount-codes'     => 'Discount Codes',
        'pms-payments-page'      => 'Payments',
        'pms-reports-page'       => 'Reports',
        'pms-import-page'        => 'Import Data',
        'pms-export-page'        => 'Export Data',
        'pms-email-reminders'    => 'Email Reminders',
        'pms-content-dripping'   => 'Content Drip Sets',
        'pms-labels-edit'        => 'Labels Edit',
    );

    return apply_filters( 'pms_role_permission_pages', $pages );

}


/**
 * Returns the granular PMS capability mapped to a given area slug
 *
 * - area-to-capability mapping is centralized here so saved settings stay readable as area slugs
 * - unknown areas return an empty string
 *
 */
function pms_role_permissions_get_area_capability( $area_slug ) {

    $capability_map = array(
        'pms-dashboard-page'     => 'pms_view_dashboard',
        'pms-basic-info-page'    => 'pms_view_basic_info',
        'pms-members-page'       => 'pms_manage_members',
        'pms-subscriptions-page' => 'pms_manage_subscriptions',
        'pms-subscription'       => 'pms_manage_subscription_plans',
        'pms-discount-codes'     => 'pms_manage_discount_codes',
        'pms-payments-page'      => 'pms_manage_payments',
        'pms-reports-page'       => 'pms_manage_reports',
        'pms-import-page'        => 'pms_manage_import',
        'pms-export-page'        => 'pms_manage_export',
        'pms-email-reminders'    => 'pms_manage_email_reminders',
        'pms-content-dripping'   => 'pms_manage_content_dripping',
        'pms-labels-edit'        => 'pms_manage_labels',
    );

    $capability = isset( $capability_map[ $area_slug ] ) ? $capability_map[ $area_slug ] : '';

    return apply_filters( 'pms_role_permission_area_capability', $capability, $area_slug );

}


/**
 * Returns the saved role-permission settings array
 *
 * - shape is array( role_slug => array( area_slug => '1' ) )
 * - results are cached per request through a static variable
 *
 */
function pms_role_permissions_get_settings() {

    static $settings = null;

    if ( $settings !== null )
        return $settings;

    $misc_settings = get_option( 'pms_misc_settings', array() );

    if ( ! empty( $misc_settings['role_permissions'] ) && is_array( $misc_settings['role_permissions'] ) )
        $settings = $misc_settings['role_permissions'];
    else
        $settings = array();

    return $settings;

}


/**
 * Returns true if the given role slug is explicitly configured in PMS role-permission settings
 *
 */
function pms_role_permissions_is_role_managed( $role_slug ) {

    if ( empty( $role_slug ) )
        return false;

    $settings = pms_role_permissions_get_settings();

    return isset( $settings[ $role_slug ] );

}


/**
 * Returns true if the current user has at least one unmanaged role
 *
 * - used to determine whether legacy pms_edit_capability fallback still applies
 *
 */
function pms_role_permissions_current_user_has_unmanaged_role() {

    $user = wp_get_current_user();

    if ( empty( $user ) || empty( $user->roles ) )
        return false;

    foreach ( $user->roles as $role_slug ) {
        if ( ! pms_role_permissions_is_role_managed( $role_slug ) )
            return true;
    }

    return false;

}


/**
 * Returns true if any managed role of the current user grants the requested area
 *
 * - granular permissions are additive across managed roles
 *
 */
function pms_role_permissions_current_user_has_granular_area_permission( $area_slug ) {

    if ( empty( $area_slug ) )
        return false;

    $user = wp_get_current_user();
    $settings = pms_role_permissions_get_settings();

    if ( empty( $user ) || empty( $user->roles ) )
        return false;

    foreach ( $user->roles as $role_slug ) {

        if ( ! pms_role_permissions_is_role_managed( $role_slug ) )
            continue;

        if ( ! empty( $settings[ $role_slug ] ) && is_array( $settings[ $role_slug ] ) && ! empty( $settings[ $role_slug ][ $area_slug ] ) )
            return true;

    }

    return false;

}


/**
 * Returns true if the current user qualifies for the legacy pms_edit_capability fallback
 *
 * - administrators pass through manage_options instead
 * - legacy fallback remains valid only through unmanaged roles
 *
 */
function pms_role_permissions_current_user_has_legacy_access() {

    if ( ! is_user_logged_in() )
        return false;

    if ( current_user_can( 'manage_options' ) )
        return false;

    if ( ! current_user_can( 'pms_edit_capability' ) )
        return false;

    // Legacy access remains valid only for roles not explicitly managed by this feature.
    return pms_role_permissions_current_user_has_unmanaged_role();

}


/**
 * Returns true if the current user can access the requested PMS area
 *
 * - administrators pass through manage_options
 * - legacy fallback remains valid through unmanaged roles
 * - managed roles are evaluated through granular area settings
 *
 */
function pms_role_permissions_current_user_can_access_area( $area_slug ) {

    if ( current_user_can( 'manage_options' ) )
        $allowed = true;
    elseif ( pms_role_permissions_current_user_has_legacy_access() )
        $allowed = true;
    elseif ( pms_role_permissions_current_user_has_granular_area_permission( $area_slug ) )
        $allowed = true;
    else
        $allowed = false;

    return (bool) apply_filters( 'pms_user_can_access_pms_area', $allowed, $area_slug, get_current_user_id() );

}


/**
 * Grants PMS granular capabilities dynamically through user_has_cap
 *
 * - keeps legacy pms_edit_capability external and unchanged
 * - grants only the PMS granular caps mapped to areas the user can access
 *
 */
function pms_role_permissions_filter_user_has_cap( $allcaps, $caps, $args, $user ) {

    if ( empty( $caps ) || empty( $user ) || ! ( $user instanceof WP_User ) )
        return $allcaps;

    if ( (int) $user->ID !== (int) get_current_user_id() )
        return $allcaps;

    $managed_capabilities = array();

    // Build a capability-to-area lookup so user_has_cap can grant only PMS-owned caps.
    foreach ( array_keys( pms_role_permissions_get_pages() ) as $area_slug ) {

        $capability = pms_role_permissions_get_area_capability( $area_slug );

        if ( ! empty( $capability ) )
            $managed_capabilities[ $capability ] = $area_slug;

    }

    if ( empty( $managed_capabilities ) )
        return $allcaps;

    foreach ( $caps as $required_capability ) {

        if ( ! isset( $managed_capabilities[ $required_capability ] ) )
            continue;

        if ( ! empty( $allcaps[ $required_capability ] ) )
            continue;

        if ( pms_role_permissions_current_user_can_access_area( $managed_capabilities[ $required_capability ] ) )
            $allcaps[ $required_capability ] = true;

    }

    return $allcaps;

}
add_filter( 'user_has_cap', 'pms_role_permissions_filter_user_has_cap', 10, 4 );


/**
 * Filters the export capability through the PMS role-permissions helper
 *
 * - export remains delegated only through the Export area or legacy fallback
 *
 */
function pms_role_permissions_filter_export_capability( $can_export ) {

    if ( pms_role_permissions_current_user_can_access_area( 'pms-export-page' ) )
        return true;

    return $can_export;

}
add_filter( 'pms_export_capability', 'pms_role_permissions_filter_export_capability' );


/**
 * Filters the capability required for PMS submenu pages
 *
 * - parent menu becomes visible if the user can access at least one PMS area
 * - known submenu areas switch from manage_options to their granular PMS capability
 *
 */
function pms_role_permissions_filter_submenu_page_capability( $capability, $menu_slug ) {

    if ( $menu_slug === 'paid-member-subscriptions' ) {

        if ( current_user_can( 'manage_options' ) )
            return 'manage_options';

        if ( pms_role_permissions_current_user_has_legacy_access() )
            return 'pms_edit_capability';

        // Return the first granular cap that can expose the PMS parent menu.
        foreach ( array_keys( pms_role_permissions_get_pages() ) as $area_slug ) {

            if ( pms_role_permissions_current_user_has_granular_area_permission( $area_slug ) )
                return pms_role_permissions_get_area_capability( $area_slug );

        }

        return $capability;

    }

    if ( $menu_slug === 'pms-discount-codes-bulk-add' ) {

        if ( current_user_can( 'manage_options' ) )
            return 'manage_options';

        if ( pms_role_permissions_current_user_has_legacy_access() )
            return 'pms_edit_capability';

        return pms_role_permissions_get_area_capability( 'pms-discount-codes' );

    }

    $area_capability = pms_role_permissions_get_area_capability( $menu_slug );

    if ( ! empty( $area_capability ) )
        return $area_capability;

    return $capability;

}
add_filter( 'pms_submenu_page_capability', 'pms_role_permissions_filter_submenu_page_capability', 10, 2 );


/**
 * Filters the capability required for PMS custom post types
 *
 * - maps PMS CPT UI access onto stable PMS granular capabilities
 *
 */
function pms_role_permissions_filter_custom_post_type_capability( $capability, $post_type ) {

    $area_capability = pms_role_permissions_get_area_capability( $post_type );

    if ( ! empty( $area_capability ) )
        return $area_capability;

    return $capability;

}
add_filter( 'pms_custom_post_type_capability', 'pms_role_permissions_filter_custom_post_type_capability', 10, 2 );


/**
 * Maps PMS CPT registration arguments onto PMS-specific primitive capabilities
 *
 * - replaces generic post-cap reliance with the PMS area capability
 *
 */
function pms_role_permissions_filter_cpt_register_args( $args, $post_type ) {

    $area_capability = pms_role_permissions_get_area_capability( $post_type );

    if ( empty( $area_capability ) )
        return $args;

    $args['map_meta_cap'] = true;

    $existing_capabilities = isset( $args['capabilities'] ) && is_array( $args['capabilities'] ) ? $args['capabilities'] : array();

    $args['capabilities'] = array_merge( $existing_capabilities, array(
        'edit_posts'             => $area_capability,
        'edit_others_posts'      => $area_capability,
        'publish_posts'          => $area_capability,
        'read_private_posts'     => $area_capability,
        'delete_posts'           => $area_capability,
        'delete_private_posts'   => $area_capability,
        'delete_published_posts' => $area_capability,
        'delete_others_posts'    => $area_capability,
        'edit_private_posts'     => $area_capability,
        'edit_published_posts'   => $area_capability,
        'create_posts'           => $area_capability,
    ) );

    return $args;

}


/**
 * Adapts the single-argument pms_register_post_type_<slug> filters to the CPT cap mapper
 *
 */
function pms_role_permissions_register_pms_subscription( $args ) {
    return pms_role_permissions_filter_cpt_register_args( $args, 'pms-subscription' );
}
add_filter( 'pms_register_post_type_pms-subscription', 'pms_role_permissions_register_pms_subscription' );

function pms_role_permissions_register_pms_discount_codes( $args ) {
    return pms_role_permissions_filter_cpt_register_args( $args, 'pms-discount-codes' );
}
add_filter( 'pms_register_post_type_pms-discount-codes', 'pms_role_permissions_register_pms_discount_codes' );

function pms_role_permissions_register_pms_email_reminders( $args ) {
    return pms_role_permissions_filter_cpt_register_args( $args, 'pms-email-reminders' );
}
add_filter( 'pms_register_post_type_pms-email-reminders', 'pms_role_permissions_register_pms_email_reminders' );

function pms_role_permissions_register_pms_content_dripping( $args ) {
    return pms_role_permissions_filter_cpt_register_args( $args, 'pms-content-dripping' );
}
add_filter( 'pms_register_post_type_pms-content-dripping', 'pms_role_permissions_register_pms_content_dripping' );


/**
 * Sanitizes the role-permissions settings structure
 *
 * - drops invalid roles, invalid area slugs, and empty role entries
 *
 */
function pms_role_permissions_sanitize_settings( $settings ) {

    if ( empty( $settings ) || ! is_array( $settings ) )
        return array();

    $valid_roles = array_keys( pms_get_user_role_names() );
    $valid_areas = array_keys( pms_role_permissions_get_pages() );
    $clean       = array();

    foreach ( $settings as $role_slug => $areas ) {

        if ( ! in_array( $role_slug, $valid_roles, true ) )
            continue;

        if ( empty( $areas ) || ! is_array( $areas ) )
            continue;

        $clean_areas = array();

        foreach ( $areas as $area_slug => $flag ) {

            if ( ! in_array( $area_slug, $valid_areas, true ) )
                continue;

            if ( empty( $flag ) )
                continue;

            $clean_areas[ $area_slug ] = '1';

        }

        // Import and Export stay subordinate to Reports in the saved settings.
        if ( empty( $clean_areas['pms-reports-page'] ) ) {
            unset( $clean_areas['pms-import-page'] );
            unset( $clean_areas['pms-export-page'] );
        }

        if ( ! empty( $clean_areas ) )
            $clean[ $role_slug ] = $clean_areas;

    }

    return $clean;

}
