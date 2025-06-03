<?php

class Cozmoslabs_Plugin_Optin_PMS {

    public static $user_name           = '';
    public static $api_url             = 'https://www.cozmoslabs.com/wp-json/cozmos-api/';
    public static $base_url            = 'https://usagetracker.cozmoslabs.com/update';
    public static $plugin_optin_status = '';
    public static $plugin_optin_email  = '';

    public static $plugin_option_key       = 'cozmos_pms_plugin_optin';
    public static $plugin_option_email_key = 'cozmos_pms_plugin_optin_email';

    public function __construct(){

        if( apply_filters( 'pms_enable_plugin_optin', true ) === false )
            return;
        
        if ( !wp_next_scheduled( 'cozmos_pms_plugin_optin_sync' ) )
            wp_schedule_event( time(), 'weekly', 'cozmos_pms_plugin_optin_sync' );

        add_action( 'cozmos_pms_plugin_optin_sync', array( 'Cozmoslabs_Plugin_Optin_PMS', 'sync_data' ) );

        self::$plugin_optin_status = get_option( self::$plugin_option_key, false );
        self::$plugin_optin_email  = get_option( self::$plugin_option_email_key, false );
        
        add_action( 'admin_init', array( $this, 'redirect_to_plugin_optin_page' ) );
        add_action( 'admin_menu', array( $this, 'add_submenu_page_optin' ) );
        add_action( 'admin_init', array( $this, 'process_optin_actions' ) );
        add_action( 'activate_plugin', array( $this, 'process_paid_plugin_activation' ) );
        add_action( 'deactivated_plugin', array( $this, 'process_paid_plugin_deactivation' ) );

        add_filter( 'pms_sanitize_settings', array( $this, 'process_plugin_optin_advanced_setting' ), 20, 2 );

    }

    public function redirect_to_plugin_optin_page(){

        if( ( isset( $_GET['page'] ) && sanitize_text_field( $_GET['page'] ) == 'pms-optin-page' ) || ( isset( $_GET['page'] ) && isset( $_GET['subpage'] ) && sanitize_text_field( $_GET['page'] ) == 'pms-dashboard-page' && sanitize_text_field( $_GET['subpage'] ) == 'pms-setup' ) )
            return;

        if( self::$plugin_optin_status !== false )
            return;

        // Show this only when admin tries to access a plugin page
        $target_slugs   = array( 'pms' );
        $is_plugin_page = false;

        if( !empty( $target_slugs ) ){
            foreach ( $target_slugs as $slug ){

                if( ! empty( $_GET['page'] ) && false !== strpos( sanitize_text_field( $_GET['page'] ), $slug ) )
                    $is_plugin_page = true;

                if( ! empty( $_GET['post_type'] ) && false !== strpos( sanitize_text_field( $_GET['post_type'] ), $slug ) )
                    $is_plugin_page = true;

                if( ! empty( $_GET['post'] ) && false !== strpos( get_post_type( (int)$_GET['post'] ), $slug ) )
                    $is_plugin_page = true;

            }
        }

        if( $is_plugin_page == true ){
            wp_safe_redirect( admin_url( 'admin.php?page=pms-optin-page' ) );
            exit();
        }
        
        return;

    }

    public function add_submenu_page_optin() {
        add_submenu_page( 'PMSHidden', 'Paid Member Subscriptions Plugin Optin', 'PMSHidden', 'manage_options', 'pms-optin-page', array(
            $this,
            'optin_page_content'
        ) );
	}

    public function optin_page_content(){
        require_once PMS_PLUGIN_DIR_PATH . 'includes/admin/plugin-optin/view-admin-plugin-optin.php';
    }

    public function process_optin_actions(){

        if( !isset( $_GET['page'] ) || $_GET['page'] != 'pms-optin-page' || !isset( $_GET['_wpnonce'] ) )
            return;

        if( wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'cozmos_pms_enable_plugin_optin' ) ){

            $args = array(
                'method' => 'POST',
                'body'   => array(
                    'email'   => get_option( 'admin_email' ),
                    'name'    => self::get_user_name(),
                    'version' => pms_get_product_version(),
                    'product' => 'pms',
                ),
            );

            // Check if the other plugin might be active as well
            $args = $this->add_other_plugin_version_information( $args );

            $request = wp_remote_post( self::$api_url . 'pluginOptinSubscribe/', $args );

            update_option( self::$plugin_option_key, 'yes' );
            update_option( self::$plugin_option_email_key, get_option( 'admin_email' ) );
            
            $settings = get_option( 'pms_misc_settings', array() );

            if( empty( $settings ) )
                $settings = array( 'plugin-optin' => 'yes' );
            else
                $settings['plugin-optin'] = 'yes';

            update_option( 'pms_misc_settings', $settings );

            wp_safe_redirect( admin_url( 'admin.php?page=pms-dashboard-page' ) );
            exit;

        }

        if( wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'cozmos_pms_disable_plugin_optin' ) ){

            update_option( self::$plugin_option_key, 'no' );

            $settings = get_option( 'pms_misc_settings', array() );

            if( empty( $settings ) )
                $settings = array( 'plugin-optin' => 'no' );
            else
                $settings['plugin-optin'] = 'no';

            update_option( 'pms_misc_settings', $settings );

            wp_safe_redirect( admin_url( 'admin.php?page=pms-dashboard-page' ) );
            exit;

        }

    }

    // Update tags when a paid version is activated
    public function process_paid_plugin_activation( $plugin ){

        if( self::$plugin_optin_status !== 'yes' || self::$plugin_optin_email === false )
            return;

        $target_plugins = [ 'paid-member-subscriptions-agency/index.php', 'paid-member-subscriptions-pro/index.php', 'paid-member-subscriptions-unlimited/index.php', 'paid-member-subscriptions-basic/index.php' ];

        if( !in_array( $plugin, $target_plugins ) )
            return;

        $version = explode( '/', $plugin );
        $version = str_replace( 'paid-member-subscriptions-', '', $version[0] );

        // Update user version tag
        $args = array(
            'method' => 'POST',
            'body'   => array(
                'email'   => self::$plugin_optin_email,
                'version' => $version,
                'product' => 'pms',
            )
        );

        // Check if the other plugin might be active as well
        $args = $this->add_other_plugin_version_information( $args );

        $request = wp_remote_post( self::$api_url . 'pluginOptinUpdateVersion/', $args );

    }

    // Update tags when a paid version is deactivated
    public function process_paid_plugin_deactivation( $plugin ){

        if( self::$plugin_optin_status !== 'yes' || self::$plugin_optin_email === false )
            return;

        $target_plugins = [ 'paid-member-subscriptions-agency/index.php', 'paid-member-subscriptions-pro/index.php', 'paid-member-subscriptions-unlimited/index.php', 'paid-member-subscriptions-basic/index.php' ];

        if( !in_array( $plugin, $target_plugins ) )
            return;

        // Update user version tag
        $args = array(
            'method' => 'POST',
            'body'   => [
                'email'   => self::$plugin_optin_email,
                'version' => 'free',
                'product' => 'pms',
            ],
        );

        $request = wp_remote_post( self::$api_url . 'pluginOptinUpdateVersion/', $args );

    }

    // Advanced settings
    public function process_plugin_optin_advanced_setting( $settings, $previous_settings ){

        if( !empty( $previous_settings ) ){

            if( ( !isset( $settings['plugin-optin'] ) && ( !isset( $_GET['subpage'] ) || $_GET['subpage'] != 'pms-setup' ) ) || ( isset( $settings['plugin-optin'] ) && $settings['plugin-optin'] == 'no' ) ){

                update_option( self::$plugin_option_key, 'no' );
    
                if( self::$plugin_optin_email === false )
                    return $settings;
    
                $args = array(
                    'method' => 'POST',
                    'body'   => [
                        'email'   => self::$plugin_optin_email,
                        'product' => 'pms',
                    ],
                );
    
                $request = wp_remote_post( self::$api_url . 'pluginOptinArchiveSubscriber/', $args );
    
            } else if ( isset( $settings['plugin-optin'] ) && $settings['plugin-optin'] == 'yes' && ( !isset( $previous_settings['plugin-optin'] ) || $settings['plugin-optin'] != $previous_settings['plugin-optin'] ) ) {

                $existing_option = get_option( self::$plugin_option_key, false );
    
                if( $existing_option == $settings['plugin-optin'] )
                    return $settings;
                
                update_option( self::$plugin_option_key, 'yes' );
                update_option( self::$plugin_option_email_key, get_option( 'admin_email' ) );
    
                if( self::$plugin_optin_email === false )
                    return $settings;
    
                $args = array(
                    'method' => 'POST',
                    'body'   => [
                        'email'   => self::$plugin_optin_email,
                        'name'    => self::get_user_name(),
                        'product' => 'pms',
                        'version' => pms_get_product_version(),
                    ],
                );
    
                // Check if the other plugin might be active as well
                $args = $this->add_other_plugin_version_information( $args );
    
                $request = wp_remote_post( self::$api_url . 'pluginOptinSubscribe/', $args );
    
            }

        }

        return $settings;

    }

    public function add_other_plugin_version_information( $args ){

        $target_found = false;

        // paid versions
        $target_plugins = [ 'profile-builder-agency/index.php', 'profile-builder-pro/index.php', 'profile-builder-unlimited/index.php', 'profile-builder-hobbyist/index.php' ];

        foreach( $target_plugins as $plugin ){
            if( is_plugin_active( $plugin ) || is_plugin_active_for_network( $plugin ) ){
                $target_found = $plugin;
                break;
            }
        }

        // verify free version separately
        if( $target_found === false ){

            if( is_plugin_active( 'profile-builder/index.php' ) || is_plugin_active_for_network( 'profile-builder/index.php' ) )
                $target_found = 'profile-builder-free';

        }

        if( $target_found !== false ){

            $target_found = explode( '/', $target_found );
            $target_found = str_replace( 'profile-builder-', '', $target_found[0] );

            $args['body']['other_product_data'] = array(
                'product' => 'wppb',
                'version' => $target_found,
            );

        }

        return $args;

    }

    // Determine current user name
    public static function get_user_name(){

        if( !empty( self::$user_name ) )
            return self::$user_name;

        $user = wp_get_current_user();

        $name = $user->display_name;

        $first_name = get_user_meta( $user->ID, 'first_name', true );
        $last_name  = get_user_meta( $user->ID, 'last_name', true );

        if( !empty( $first_name ) && !empty( $last_name ) )
            $name = $first_name . ' ' . $last_name;

        self::$user_name = $name;

        return self::$user_name;

    }

    public static function sync_data(){

        if( self::$plugin_optin_status != 'yes' )
            return;

        $args = array(
            'method' => 'POST',
            'body'   => array(
                'home_url'       => home_url(),
                'product'        => 'pms',
                'email'          => self::$plugin_optin_email,
                'name'           => self::get_user_name(),
                'version'        => pms_get_product_version(),
                'license'        => pms_get_serial_number(),
                'active_plugins' => json_encode( get_option( 'active_plugins', array() ) ),
                'wp_version'     => get_bloginfo('version'),
                'wp_locale'      => get_locale(),
                'plugin_version' => defined( 'PMS_VERSION' ) ? PMS_VERSION : '',
                'php_version'    => defined( 'PHP_VERSION' ) ? PHP_VERSION : '',
            ),
        );

        // Only send the major version for WordPress and PHP
        // e.g. 1.x
        $target_keys = array( 'wp_version', 'php_version' );

        foreach( $target_keys as $key ){
            $version_number = explode( '.', $args['body'][$key] );

            if( isset( $version_number[0] ) && isset( $version_number[1] ) )
                $args['body'][$key] = $version_number[0] . '.' . $version_number[1];
        }

        $args = apply_filters( 'cozmoslabs_plugin_optin_pms_metadata', $args );

        $request = wp_remote_post( self::$base_url, $args );

    }

}

if( !class_exists( 'Cozmoslabs_Plugin_Optin_Metadata_Builder' ) ) {
    class Cozmoslabs_Plugin_Optin_Metadata_Builder {

        public $option_prefix = '';
        public $blacklisted_option_slugs = [];
        public $blacklisted_option_patterns = [];
        public $blacklisted_option_names = [];
        protected $metadata;

        public function __construct(){

            $this->metadata = [
                'settings' => [],
                'addons'   => [],
                'custom'   => [],
                'cpt'      => [],
            ];

            add_filter( 'cozmoslabs_plugin_optin_'. $this->option_prefix .'metadata', array( $this, 'build_metadata' ) );

        }

        public function build_metadata( $args ){
            // Get all options that start with the prefix
            $options = $this->get_option_keys();

            if( !empty( $options ) ){

                foreach( $options as $option ){

                    // exclude exact option names
                    if( in_array( $option['option_name'], $this->blacklisted_option_slugs ) ){
                        continue;
                    }

                    // exclude patterns
                    if( !empty( $this->blacklisted_option_patterns ) ){
                        $found_pattern = false;

                        foreach( $this->blacklisted_option_patterns as $pattern ){
                            if( strpos( $option['option_name'], $pattern ) !== false ){
                                $found_pattern = true;
                                break;
                            }
                        }

                        if( $found_pattern )
                            continue;
                    }

                    $option_value = get_option( $option['option_name'], false );

                    if( !empty( $option_value ) ){

                        if( is_array( $option_value ) ){
                            foreach( $option_value as $key => $value ){
                                if( !is_array( $value ) ){
                                    if( in_array( $key, $this->blacklisted_option_names ) )
                                    unset( $option_value[ $key ] );
                                } else {
                                    if( in_array( $key, $this->blacklisted_option_names ) )
                                        unset( $option_value[ $key ] );

                                    foreach( $value as $key_deep => $value_deep ){
                                        if( in_array( $key_deep, $this->blacklisted_option_names ) )
                                            unset( $option_value[ $key ][ $key_deep ] );
                                    }
                                }
                            }
                        }

                        // cleanup options like array( array( 'abc' ) ) to be array( 'abc' ) 
                        if( is_array( $option_value ) && count( $option_value ) == 1 && isset( $option_value[0] ) )
                            $option_value = $option_value[0];
                        
                        $this->metadata['settings'][ $option['option_name'] ] = $option_value;
                    }

                }

            }

            // Ability to add custom data
            $this->metadata = apply_filters( 'cozmoslabs_plugin_optin_'. $this->option_prefix .'metadata_builder_metadata', $this->metadata );

            $args['body']['metadata'] = $this->metadata;

            return $args;
        }

        private function get_option_keys(){

            global $wpdb;

            if( empty( $this->option_prefix ) )
                return [];
            
            $result = $wpdb->get_results( $wpdb->prepare( "SELECT option_name FROM {$wpdb->prefix}options WHERE option_name LIKE %s", $this->option_prefix . '%' ), 'ARRAY_A' );

            if( !empty( $result ) )
                return $result;
        
            return [];

        }

    }
}

class Cozmoslabs_Plugin_Optin_Metadata_Builder_PMS extends Cozmoslabs_Plugin_Optin_Metadata_Builder {

    public function __construct(){

        $this->option_prefix = 'pms_';

        parent::__construct();

        $this->blacklisted_option_slugs = [
            'pms_add_ons_settings',
            'pms_already_installed',
            'pms_inv_invoice_number',
            'pms_serial_number',
            'pms_edd_sl_initial_activation',
            'pmsle_backup',
            'pms_license_details',
            'pms_old_add_ons_status',
            'pms_pages_created',
            'pms_repackage_initial_upgrade',
            'pms_review_request_status',
            'pms_version',
            'pms_stripe_connect_test_publishable_key',
            'pms_stripe_connect_test_secret_key',
            'pms_stripe_connect_live_publishable_key',
            'pms_stripe_connect_live_secret_key',
            'pms_payments_gateways_notice_clicked',
            'pms_paypal_connect_test_access_token',
            'pms_paypal_connect_live_access_token',
            'pms_paypal_connect_test_webhook_id',
            'pms_paypal_connect_live_webhook_id',
            'pms_paypal_migration_existing_paypal_gateways',
            'pms_paypal_migration_existing_paypal_gateways_paypal_standard',
            'pms_paypal_migration_existing_paypal_gateways_paypal_express',
            'pms_paypal_connect_test_client_id',
            'pms_paypal_connect_test_client_secret',
            'pms_paypal_connect_test_payer_id',
            'pms_paypal_connect_live_client_id',
            'pms_paypal_connect_live_client_secret',
            'pms_paypal_connect_live_payer_id',
            'pms_recaptcha_validations',
            'pms_gm_first_activation',
            'pms_currency_exchange_data',
            'pms_currency_exchange_request_date',
            'pms_payments_home_url',
            'pms_inv_version',
            'pms_inv_first_activation',
            'pms_inv_invoice_number',
            'pms_inv_reset_invoice_number_years',
            'pms_emails_settings',
            'pms_files_restriction_addon_already_activated',
            'pmsle',
            'pms_ipn_logger',
            'pms_stripe_first_activation',
            'pms_msfp_migration',
            'pms_used_deprecated_addons',
        ];

        $this->blacklisted_option_names = [
            'logged_out',
            'non_members',
            'purchasing_restricted',
            'notes',
            'company_details',
            'product_discounted_message',
            'exchange_api_key',
            'alpha_vantage_api_key',
            'pms-paypal-unsupported-currencies',
            'site_key',
            'secret_key',
            'v3_site_key',
            'v3_secret_key',
        ];

        $this->blacklisted_option_patterns = [
            'pms_ipn_logger',
            'pms_used_trial',
            'pms_used_trial_cards',
        ];

        add_action( 'cozmoslabs_plugin_optin_'. $this->option_prefix .'metadata_builder_metadata', array( $this, 'build_custom_plugin_metadata' ) );

    }

    public function build_custom_plugin_metadata(){

        // Add-ons data
        $this->metadata['addons'] = $this->generate_addon_settings();

        // Content restriction data
        $this->metadata['custom']['content_restriction'] = $this->generate_content_restriction_data();

        // Custom post types data
        $this->metadata['cpt'] = $this->generate_cpt_data();

        return $this->metadata;

    }

    public function generate_addon_settings(){
        $add_on_option_slugs = [
            'pms_add_ons_settings',
        ];

        $add_ons = [];

        foreach( $add_on_option_slugs as $option_slug ){
            $option = get_option( $option_slug, false );

            if( !empty( $option ) ){
                foreach( $option as $slug => $value ){
                    
                    if( ( is_bool( $value ) && $value == true ) || $value == 'show' ){
                        $slug = str_replace( [ 'pms-add-on-', '/index.php'], '', $slug );

                        $add_ons[ $slug ] = true;
                    }
                }
            }
        }

        return $add_ons;
    }

    public function generate_content_restriction_data(){
        global $wpdb;

        $restriction_data = [
            'post_restrictions'        => 0,
            'elementor_restrictions'   => 0,
            'divi_restrictions'        => 0,
            'blocks_restrictions'      => 0,
        ];

        // Count post/page/cpt restrictions
        $restriction_data['post_restrictions'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT a.post_id) 
            FROM {$wpdb->postmeta} a
            INNER JOIN {$wpdb->posts} b ON a.post_id = b.ID 
            WHERE b.post_type != 'revision'
            AND ( ( a.meta_key = 'pms-content-restrict-user-status' AND a.meta_value = 'loggedin' )
            OR ( a.meta_key = 'pms-content-restrict-subscription-plan' AND a.meta_value IS NOT NULL ) ) LIMIT 100"
        );

        // Count Elementor widget restrictions if Elementor is active
        if( did_action( 'elementor/loaded' ) ) {
            $elementor_posts = $wpdb->get_results(
                "SELECT a.post_id, a.meta_value 
                FROM {$wpdb->postmeta} a
                INNER JOIN {$wpdb->posts} b ON a.post_id = b.ID
                WHERE b.post_type != 'revision'
                AND a.meta_key = '_elementor_data' 
                AND ( a.meta_value LIKE '%\"pms_restriction_loggedin_users\":\"yes\"%'
                OR a.meta_value LIKE '%\"pms_restriction_subscription_plans\":[\"%') LIMIT 100"
            );

            if( !empty( $elementor_posts ) )
                $restriction_data['elementor_restrictions'] = count($elementor_posts);
        }

        // Check if Divi is active
        if( defined( 'ET_BUILDER_VERSION' ) ) {
            $divi_posts = $wpdb->get_results(
                "SELECT ID 
                FROM {$wpdb->posts}
                WHERE post_type != 'revision'
                AND post_content LIKE '%pms_display_to=\"logged_in\"%'
                OR post_content LIKE '%pms_subscription_plans=\"%' LIMIT 100"
            );

            if( !empty( $divi_posts ) )
                $restriction_data['divi_restrictions'] = count($divi_posts);
        }

        // Check if Gutenberg is available
        if( version_compare( get_bloginfo( 'version' ), '5.0', '>=' ) ) {
            $gutenberg_posts = $wpdb->get_results(
                "SELECT ID 
                FROM {$wpdb->posts}
                WHERE post_type != 'revision'
                AND ( post_content LIKE '%\"pmsContentRestriction\":{\"loggedIn\":%'
                OR post_content LIKE '%\"pmsContentRestriction\":{\"subscriptionPlans\":[\"%' ) LIMIT 100"
            );

            if( !empty( $gutenberg_posts ) )
                $restriction_data['blocks_restrictions'] = count($gutenberg_posts);
        }

        return $restriction_data;
    }

    public function generate_cpt_data(){

        $cpt_data = [];

        // Define post types and their meta key prefixes
        $post_types = array(
            'pms-subscription'    => array('key' => 'pms_subscription_plan', 'data_key' => 'subscription_plans'),
            'pms-discount-codes'  => array('key' => 'pms_discount', 'data_key' => 'discount_codes'),
            'pms-email-reminders' => array('key' => 'pms_email_reminder', 'data_key' => 'email_reminders')
        );
        
        global $wpdb;
        
        foreach( $post_types as $post_type => $meta_info ) {
            $limit = '';
            
            // Add limit of 50 for discount codes
            if( $post_type == 'pms-discount-codes' ) {
                $limit = ' LIMIT 50';
            }

            $posts = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT ID 
                    FROM {$wpdb->posts} 
                    WHERE post_type = %s 
                    ORDER BY post_date ASC" . $limit,
                    $post_type
                )
            );
            
            if( !empty( $posts ) ){
                foreach( $posts as $post_id ){
                    $post_meta = $wpdb->get_results( 
                        $wpdb->prepare( 
                            "SELECT meta_key, meta_value 
                            FROM {$wpdb->postmeta} 
                            WHERE post_id = %d 
                            AND meta_key LIKE %s",
                            $post_id,
                            $meta_info['key'] . '%'
                        )
                    );
                    
                    $data = array();
                    
                    if( !empty( $post_meta ) ){
                        foreach( $post_meta as $meta ){
                            if( strpos( $meta->meta_key, 'description' ) === false ){

                                $meta_value = maybe_unserialize( $meta->meta_value );

                                if( is_array( $meta_value ) )
                                    $meta_value = json_encode( $meta_value );

                                $data[$meta->meta_key] = $meta_value;
                            }
                        }
                    }
                    
                    $cpt_data[$meta_info['data_key']][] = $data;
                }
            }
        }

        return $cpt_data;

    }

}

new Cozmoslabs_Plugin_Optin_PMS();
new Cozmoslabs_Plugin_Optin_Metadata_Builder_PMS();