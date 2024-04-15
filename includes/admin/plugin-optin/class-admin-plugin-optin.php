<?php

class Cozmoslabs_Plugin_Optin_PMS {

    public static $user_name           = '';
    public static $base_url            = 'https://www.cozmoslabs.com/wp-json/cozmos-api/';
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

            $request = wp_remote_post( self::$base_url . 'pluginOptinSubscribe/', $args );

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

        $request = wp_remote_post( self::$base_url . 'pluginOptinUpdateVersion/', $args );

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

        $request = wp_remote_post( self::$base_url . 'pluginOptinUpdateVersion/', $args );

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
    
                $request = wp_remote_post( self::$base_url . 'pluginOptinArchiveSubscriber/', $args );
    
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
    
                $request = wp_remote_post( self::$base_url . 'pluginOptinSubscribe/', $args );
    
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

        $plugin_optin_status = get_option( self::$plugin_option_key, false );

        if( $plugin_optin_status != 'yes' )
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
            ),
        );

        $args = self::add_request_metadata( $args );

        $request = wp_remote_post( self::$base_url . 'pluginOptinSync/', $args );

    }

    public static function add_request_metadata( $args ){
        
        $settings          = get_option( 'pms_general_settings', false );
        $payments_settings = get_option( 'pms_payments_settings', false );
        $tax_settings      = get_option( 'pms_tax_settings', 'not_found' );

	    $enabled = 'no';

        if( !empty( $settings ) ) {

            if( !empty( $settings['forms_design'] ) )
                $args['body']['form_design'] = $settings['forms_design'];
            else
                $args['body']['form_design'] = '';

            if( isset( $payments_settings['currency'] ) )
                $args['body']['currency'] = $payments_settings['currency'];

            if( isset( $payments_settings['active_pay_gates'] ) )
                $args['body']['active_pay_gates'] = json_encode( $payments_settings['active_pay_gates'] );

            if( isset( $payments_settings['retry-payments'] ) && $settings['retry-payments'] == '1' )
                $args['body']['retry_payments'] = 1;
            else
                $args['body']['retry_payments'] = 0;

            $invoice_number = get_option( 'pms_inv_invoice_number', '1' );

            if( (int)$invoice_number > 1 ){
                $args['body']['invoices'] = 1;
            } else {
                $args['body']['invoices'] = 0;
            }

            if( isset( $payments_settings['enable_tax'] ) && $settings['enable_tax'] == '1' )
                $args['body']['taxes'] = 1;
            else
                $args['body']['taxes'] = 0;

            $args['body']['addons'] = json_encode( get_option( 'pms_add_ons_settings', array() ) );

            $pricing_tables_option = get_option( 'pms_create_pricing_page_complete', false );

            if( $pricing_tables_option == 'pricing_page_exist' ){
                $args['body']['pricing_tables'] = 1;
            } else {
                $args['body']['pricing_tables'] = 0;
            }

        }

        return $args;

    }

}

new Cozmoslabs_Plugin_Optin_PMS();