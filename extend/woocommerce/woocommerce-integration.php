<?php
/**
 * PMS - WooCommerce integration
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Add tab for WooCommerce integration under PMS Settings page
 *
 * @param array $pms_tabs The PMS Settings tabs
 * @return mixed
 */
function pms_woo_add_woocommerce_tab( $pms_tabs ) {

    $pms_tabs['woocommerce'] = __( 'WooCommerce Integration', 'paid-member-subscriptions' );

    return $pms_tabs;

}
add_filter('pms-settings-page_tabs', 'pms_woo_add_woocommerce_tab' );


/**
 * Add content for WooCommerce integration tab
 *
 * @param array $options The PMS settings options
 */
function pms_woo_add_woocommerce_tab_content ( $output, $active_tab, $options ) {

    if ( $active_tab == 'woocommerce' ) {
        ob_start();

        include_once 'views/view-settings-tab-woocommerce-integration.php';

        $output = ob_get_clean();
    }

    return $output;
}
add_action( 'pms_settings_tab_content', 'pms_woo_add_woocommerce_tab_content', 20, 3 );

/**
 * Sanitize PMS WooCommerce integration settings
 *
 * @param array $options The PMS settings options
 * @return mixed
 */
function pms_woo_sanitize_settings( $options ){

    if( !isset( $_REQUEST['option_page' ] ) )
        return $options;
        
    $option_page = sanitize_text_field( $_REQUEST['option_page'] );

    if ( $option_page != 'pms_woocommerce_settings' ) return $options;

    if ( isset( $options['cumulative_discounts'] ) )
        $options['cumulative_discounts'] = (int) $options['cumulative_discounts'];

    if ( isset( $options['exclude_on_sale'] ) )
        $options['exclude_on_sale'] = (int) $options['exclude_on_sale'];

    if ( isset( $options['product_discounted_message'] ) )
        $options['product_discounted_message'] =  wp_kses_post( $options['product_discounted_message'] );

    return $options;
}
add_filter( 'pms_sanitize_settings', 'pms_woo_sanitize_settings' );


/**
 * Get WooCommerce version number
 *
 * @return null|string
 */
function pms_get_woo_version(){

    return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;

}

/**
 * Returns true if the installed WooCommerce $version is greater or equal to $version
 *
 * @param string $version the version to compare
 *
 * @return boolean true if the installed version of WooCommerce is >= $version
 * */
function pms_is_woo_version_gte( $version ) {

    $woo_version = pms_get_woo_version();
    return $woo_version && version_compare( $woo_version, $version, '>=' );

}

/**
 *  Enqueue admin scripts required for product membership discounts
 *
 */
function pms_woo_enqueue_admin_scripts_membership_discounts(){

    global $wp_scripts, $post_type;

    // make sure to load scripts only on Product and Subscription Plan post types
    if ( ( $post_type == 'product' ) || ( $post_type == 'pms-subscription' ) ) {

        // Try to detect if chosen has already been loaded; We use it for Product Discounts metabox - multiple select, under Subscription Plan
        $found_chosen = false;

        foreach ($wp_scripts as $wp_script) {
            if (!empty($wp_script['src']) && strpos($wp_script['src'], 'chosen') !== false)
                $found_chosen = true;
        }

        if (!$found_chosen) {
            wp_enqueue_script('pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.jquery.min.js', array('jquery'), PMS_VERSION);
            wp_enqueue_style('pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.css', array(), PMS_VERSION);
        }

        // If the file exists, enqueue it
        if (file_exists(PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/assets/js/admin/meta-box-membership-discounts.js')) {

            wp_enqueue_script('pms-meta-box-product-membership-discounts-js', PMS_PLUGIN_DIR_URL . 'extend/woocommerce/assets/js/admin/meta-box-membership-discounts.js', array('jquery'), PMS_VERSION);
            wp_localize_script('pms-meta-box-product-membership-discounts-js', 'pms_woo_admin_vars', array(
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'strings' => array(
                    'Active'                                         => __('Active', 'paid-member-subscriptions'),
                    'Inactive'                                       => __('Inactive', 'paid-member-subscriptions'),
                    'No discounts yet'                               => __('There are no discounts yet. Click below to add one.', 'paid-member-subscriptions'),
                    'Products'                                       => __('Products', 'paid-member-subscriptions'),
                    'Product Categories'                             => __('Product Categories', 'paid-member-subscriptions'),
                    'Select...'                                      => __('Select... or leave blank to apply to all', 'paid-member-subscriptions'),
                    'Percent'                                        => __('Percent', 'paid-member-subscriptions'),
                    'Fixed'                                          => __('Fixed', 'paid-member-subscriptions'),
                    'Choose'                                         => __('Choose...', 'paid-member-subscriptions'),
                    'Remove this discount'                           => __('Remove this discount', 'paid-member-subscriptions'),
                    'Are you sure you want to remove this discount?' => __('Are you sure you want to remove this discount?', 'paid-member-subscriptions')
                )
            ));

        }

        // Back-end css
        if (file_exists(PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/assets/css/back-end.css'))
            wp_enqueue_style('pms-meta-box-membership-discounts-style', PMS_PLUGIN_DIR_URL . 'extend/woocommerce/assets/css/back-end.css', array(), PMS_VERSION);

    }

}


/**
 * Include required files for Restricting Product Purchase/Viewing and Membership Discounts
 *
 */
function pms_woo_include_files(){

    // Restrict product purchase or viewing based on subscription plan or logged in status
    if (file_exists(PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/functions-content-restriction.php'))
        include_once PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/functions-content-restriction.php';

    // Check if WooCommerce version is greater than 3.0, as we don't support membership discounts for older versions
    if ( pms_is_woo_version_gte('3.0') ) {

        // Load scripts for product membership discounts
        add_action('admin_enqueue_scripts', 'pms_woo_enqueue_admin_scripts_membership_discounts');

        // Add meta-box for adding membership discounts per individual products
        if (file_exists(PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/includes/admin/meta-boxes/class-meta-box-product-membership-discounts.php'))
            include_once PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/includes/admin/meta-boxes/class-meta-box-product-membership-discounts.php';

        // Add meta-box for adding product discounts per subscription plan
        if (file_exists(PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/includes/admin/meta-boxes/class-meta-box-subscription-plan-product-discounts.php'))
            include_once PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/includes/admin/meta-boxes/class-meta-box-subscription-plan-product-discounts.php';

        // Modify prices viewed by an active member based on existing membership discounts (set per subscription plan and per product)
        if (file_exists(PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/includes/class-pms-woo-subscription-discounts.php'))
            include_once PMS_PLUGIN_DIR_PATH . 'extend/woocommerce/includes/class-pms-woo-subscription-discounts.php';
    }


}
add_action('plugins_loaded', 'pms_woo_include_files');


/**
 * If the pms-account shortcode is added on the same page as the woocommerce my account shortcode,
 * disable our URL rewrites for tabs
 *
 * @since 1.8.5
 * @return bool
 */
function pms_woo_disable_tab_url_rewrite( $disable ) {

    $account_page = pms_get_page( 'account' );

    if ( $account_page == false )
        return $disable;

    if ( $account_page == get_option( 'woocommerce_myaccount_page_id' ) )
        return true;

    return $disable;

}
add_filter( 'pms_account_rewrite_tab_urls', 'pms_woo_disable_tab_url_rewrite' );


/**
 * Used for updating WooCommerce Billing details when PMS Billing details get updated
 */
function pms_save_pms_billing_as_woo( $user_data ){

    $user_id = $user_data;
    if( is_array( $user_data ) )
        $user_id = $user_data['user_id'];

    $billing_fields = array(
        'pms_billing_first_name' => 'billing_first_name',
        'pms_billing_last_name'  => 'billing_last_name',
        'pms_billing_email'      => 'billing_email',
        'pms_billing_company'    => 'billing_company',
        'pms_billing_address'    =>  array('billing_address','billing_address_1'),
        'pms_billing_city'       => 'billing_city',
        'pms_billing_zip'        => 'billing_postcode',
        'pms_billing_country'    => 'billing_country',
        'pms_billing_state'      => 'billing_state',
    );

    foreach( $billing_fields as $pms_key => $woo_key ){

        if( isset( $_POST[ $pms_key ] ) ) {

            if ( is_array( $woo_key ) ){

                foreach ( $woo_key as $key )
                    update_user_meta($user_id, $key, sanitize_text_field($_POST[$pms_key]));

            }
            else update_user_meta($user_id, $woo_key, sanitize_text_field($_POST[$pms_key]));

        }

    }

}

/**
 * Used for updating PMS Billing details when WooCommerce Billing details get updated
 */
function pms_save_woo_billing_as_pms( $user_id, $woo_form ) {

    if ( $woo_form != 'billing' )
        return;

    $billing_fields = array(
        'billing_first_name' => 'pms_billing_first_name',
        'billing_last_name'  => 'pms_billing_last_name',
        'billing_email'      => 'pms_billing_email',
        'billing_company'    => 'pms_billing_company',
        'billing_address_1'  => array('pms_billing_address','billing_address'),
        'billing_city'       => 'pms_billing_city',
        'billing_zip'        => 'pms_billing_postcode',
        'billing_country'    => 'pms_billing_country',
        'billing_state'      => 'pms_billing_state',
    );

    foreach( $billing_fields as $woo_key => $pms_key ){

        if( isset( $_POST[ $woo_key ] ) ) {

            if ( is_array( $pms_key ) ){

                foreach ( $pms_key as $key )
                    update_user_meta($user_id, $key, sanitize_text_field($_POST[$woo_key]));

            }
            else update_user_meta($user_id, $pms_key, sanitize_text_field($_POST[$woo_key]));

        }

    }

}

$settings = get_option( 'pms_woocommerce_settings', array() );
if ( isset( $settings['sync_woo_pms_billing_details'] ) && $settings['sync_woo_pms_billing_details'] == 'yes' ) {
    add_action( 'pms_edit_profile_form_update_user', 'pms_save_pms_billing_as_woo' );
    add_action( 'pms_register_form_after_create_user', 'pms_save_pms_billing_as_woo' );
    add_action( 'pms_renew_subscription_form_extra', 'pms_save_pms_billing_as_woo' );
    add_action( 'pms_change_subscription_form_extra', 'pms_save_pms_billing_as_woo' );
    add_action( 'pms_upgrade_subscription_form_extra', 'pms_save_pms_billing_as_woo' );
    add_action( 'pms_new_subscription_form_extra', 'pms_save_pms_billing_as_woo' );
    add_action( 'woocommerce_customer_save_address', 'pms_save_woo_billing_as_pms', 10, 2 );
}