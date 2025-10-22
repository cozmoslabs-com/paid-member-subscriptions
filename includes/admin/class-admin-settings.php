<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Extends core PMS_Submenu_Page base class to create and add custom functionality
 * for the settings page in the admin section
 *
 * The settings page will contain several tabs where the user will be able to customize e-mails,
 * user messages and also set up payment gateways
 *
 */
Class PMS_Submenu_Page_Settings extends PMS_Submenu_Page {

    public $active_tab = 'general';

    /*
     * Method that initializes the class
     *
     */
    public function init() {

        // Hook the output method to the parent's class action for output instead of overwriting the
        // output method
        add_action( 'pms_output_content_submenu_page_' . $this->menu_slug, array( $this, 'output' ) );

        if ( isset( $_GET['tab'] ) ){
            $tab = sanitize_text_field( $_GET['tab'] );

            if ( !in_array( $tab, array_keys( $this->get_tabs() ) ) ){
                $tab = 'general';
            }

            $this->active_tab = $tab;
        }

        add_action( 'pms_submenu_page_enqueue_admin_scripts_' . $this->menu_slug, array( $this, 'admin_scripts' ) );

        $this->setup_functions();

    }


    /*
     * Method to output content in the custom page
     *
     */
    public function output() {

        // Set options
        $this->options = get_option( 'pms_' . $this->active_tab . '_settings', array() );

        ?>

        <div class="wrap pms-wrap cozmoslabs-wrap">

            <h1></h1>
            <!-- WordPress Notices are added after the h1 tag -->

            <div class="cozmoslabs-page-header">
                <div class="cozmoslabs-section-title">

                    <h3 class="cozmoslabs-page-title"><?php esc_html_e( 'Settings', 'paid-member-subscriptions' ); ?></h3>
                    <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/settings/?utm_source=pms-settings&utm_medium=client-site&utm_campaign=pms-settings-docs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>

                </div>

                <div class="pms-payments-status-wrap pms-payments-status-wrap--<?php echo ( pms_is_payment_test_mode() ? 'test' : 'live' ); ?>">
                    <div><?php echo ( pms_is_payment_test_mode() ? esc_html__( 'Test Payments are enabled', 'paid-member-subscriptions' ) : esc_html__( 'Live Payments are enabled', 'paid-member-subscriptions' ) ); ?></div>
                    <div class="pms-payments-status pms-payments-status--<?php echo ( pms_is_payment_test_mode() ? 'test' : 'live' ); ?>"></div>
                </div>
            </div>

            <div class="cozmoslabs-nav-tab-wrapper">
                <?php
                foreach( $this->get_tabs() as $tab_slug => $tab_name )
                    echo '<a href="' . esc_url( admin_url( add_query_arg( array( 'page' => 'pms-settings-page', 'tab' => $tab_slug ), 'admin.php' ) ) ) . '" class="nav-tab ' . ( $this->active_tab == $tab_slug ? 'nav-tab-active' : '' ) . '">' . esc_html( $tab_name ) . '</a>';
                ?>
            </div>

            <?php settings_errors(); ?>


            <?php
            // insert Register Version Form to PMS Settings - General Tab
            if ( !is_multisite() && isset( $_GET['page'] ) && $_GET['page'] === 'pms-settings-page' && ( !isset( $_GET['tab'] ) || $_GET['tab'] === 'general' ) )
                pms_add_register_version_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>

            <form method="post" enctype="multipart/form-data" encoding="multipart/form-data" action="options.php">
                <?php

                    settings_fields( 'pms_' . $this->active_tab . '_settings' );

                    echo '<div class="cozmoslabs-settings-container">';

                        ob_start();

                        if ( file_exists( PMS_PLUGIN_DIR_PATH . 'includes/admin/views/view-page-settings-' . $this->active_tab . '.php' ) )
                            include_once 'views/view-page-settings-' . $this->active_tab . '.php';

                        $output = ob_get_clean();

                        echo apply_filters( 'pms_settings_tab_content', $output, $this->active_tab, $this->options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

                        $styling = '';

                        if( $this->active_tab === 'general' ){
                            $styling = 'display: none;';
                        }

                        echo '<div class="submit cozmoslabs-submit"'. ( !empty( $styling ) ? ' style="' . esc_attr( $styling ) . '"' : '' ) .'>';
                            echo '<h3 class="cozmoslabs-subsection-title">'. esc_html__( 'Update Settings', 'paid-member-subscriptions' ) .'</h3>';
                            echo '<div class="cozmoslabs-publish-button-group">';
                                submit_button( esc_html__( 'Save Settings', 'paid-member-subscriptions' ), 'primary cozmoslabs-save-settings-button' );
                            echo '</div>';
                        echo '</div>';

                    echo '</div>';

                ?>
            </form>

        </div>

        <?php
    }

    /*
     * Callback overwrite for sanitizing settings
     *
     */
    public function sanitize_settings( $options ) {

        // Sanitize all option values
        $options = pms_array_strip_script_tags( $options );

        $previous_options = array();

        if ( isset( $_REQUEST['option_page'] ) ) {

            $option_page = sanitize_text_field( $_REQUEST['option_page'] );

            $previous_options = get_option( $option_page, array() );

            // If no active payment gateways are checked, add paypal_standard as default
            if( $option_page == 'pms_payments_settings' && !isset( $options['active_pay_gates'] ) )
                $options['active_pay_gates'] = array( 'stripe_connect' );

            if ( $option_page == 'pms_general_settings' ) {

                if (isset($options['register_success_page']))
                    $options['register_success_page'] = (int)$options['register_success_page'];

                if (isset($options['login_page']))
                    $options['login_page'] = (int)$options['login_page'];

                if (isset($options['register_page']))
                    $options['register_page'] = (int)$options['register_page'];

                if (isset($options['account_page']))
                    $options['account_page'] = (int)$options['account_page'];

                if (isset($options['lost_password_page']))
                    $options['lost_password_page'] = (int)$options['lost_password_page'];

                if (isset($options['edit_profile_shortcode']))
                    $options['edit_profile_shortcode'] = $options['edit_profile_shortcode'];
            }

            if( $option_page == 'pms_content_restriction_settings' && isset( $options['restricted_post_preview']['trim_content_length'] ) )
                $options['restricted_post_preview']['trim_content_length'] = (int)$options['restricted_post_preview']['trim_content_length'];

            // Sanitize admin emails field
            if( $option_page == 'pms_emails_settings' && ! empty( $options['admin_emails'] ) ) {

                $admin_emails = array_map( 'trim', explode( ',', $options['admin_emails'] ) );

                foreach( $admin_emails as $key => $admin_email ) {

                    if( ! is_email( $admin_email ) )
                        unset( $admin_emails[$key] );

                }

                $options['admin_emails'] = implode( ', ', $admin_emails );

            }

            if ( $option_page == 'pms_payments_settings' ) {
                $old_settings = get_option( 'pms_payments_settings' );

                if ( empty( $options['gateways']['paypal'] ) && !empty( $old_settings['gateways']['paypal'] ) )
                    $options['gateways']['paypal'] = $old_settings['gateways']['paypal'];

                if ( empty( $options['gateways']['stripe'] ) && !empty( $old_settings['gateways']['stripe'] ) )
                    $options['gateways']['stripe'] = $old_settings['gateways']['stripe'];
            }

            if ( $option_page == 'pms_misc_settings' ) {

                if (isset($options['gdpr']['gdpr_checkbox']))
                    $options['gdpr']['gdpr_checkbox'] = sanitize_text_field($options['gdpr']['gdpr_checkbox']);

                if (isset($options['gdpr']['gdpr_checkbox_text'])){
                    $options['gdpr']['gdpr_checkbox_text'] = wp_kses_post( $options['gdpr']['gdpr_checkbox_text'] );

                    if( function_exists('icl_register_string') )
                        icl_register_string('plugin paid-member-subscriptions', 'gdpr_checkbox_text' , $options['gdpr']['gdpr_checkbox_text'] );
                }

                if (isset($options['gdpr']['gdpr_delete']))
                    $options['gdpr']['gdpr_delete'] = sanitize_text_field($options['gdpr']['gdpr_delete']);

                if ( isset( $options['payments']['payment_renew_button_delay'] ) && filter_var($options['payments']['payment_renew_button_delay'], FILTER_VALIDATE_INT) === false ) {
                    unset( $options['payments']['payment_renew_button_delay'] );
                }

                if ( isset( $options['payments']['redirect_after_manual_payment'] ) && filter_var($options['payments']['redirect_after_manual_payment'], FILTER_VALIDATE_URL) === false ) {
                    unset( $options['payments']['redirect_after_manual_payment'] );
                }

                if ( !isset( $options['recaptcha']['v3_score_threshold'] ) || $options['recaptcha']['v3_score_threshold'] < 0 || $options['recaptcha']['v3_score_threshold'] > 1 ) {
                    $options['recaptcha']['v3_score_threshold'] = 0.5;
                }

            }
        }

        /**
         * Filter to sanitize plugin settings
         *
         * @param array $options
         *
         */
        $options = apply_filters( 'pms_sanitize_settings', $options, $previous_options );

        return $options;
    }

    /*
     * Returns the tabs we want for this page
     *
     */
    private function get_tabs() {

        $tabs = array(
            'general'              => esc_html__( 'General', 'paid-member-subscriptions' ),
            'payments'             => esc_html__( 'Payments', 'paid-member-subscriptions' ),
            'content_restriction'  => esc_html__( 'Content Restriction', 'paid-member-subscriptions' ),
            'emails'               => esc_html__( 'E-Mails', 'paid-member-subscriptions' ),
            'misc'                 => esc_html__( 'Misc', 'paid-member-subscriptions' )
        );

        return apply_filters( $this->menu_slug . '_tabs', $tabs );

    }

    public function register_settings() {

        foreach ( $this->get_tabs() as $slug => $name )
            register_setting( 'pms_' . $slug . '_settings', 'pms_' . $slug . '_settings', array( $this, 'sanitize_settings' ) );

        do_action( 'pms_register_tab_settings' );
    }

    public function admin_scripts() {

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'jquery-style', PMS_PLUGIN_DIR_URL . 'assets/css/admin/jquery-ui.min.css', array(), PMS_VERSION );

        global $wp_scripts;

        // Try to detect if chosen has already been loaded
        $found_chosen = false;

        foreach( $wp_scripts as $wp_script ) {
            if( !empty( $wp_script['src'] ) && strpos($wp_script['src'], 'chosen') !== false )
                $found_chosen = true;
        }

        if( !$found_chosen ) {
            wp_enqueue_script( 'pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.jquery.min.js', array( 'jquery' ), PMS_VERSION );
            wp_enqueue_style( 'pms-chosen', PMS_PLUGIN_DIR_URL . 'assets/libs/chosen/chosen.css', array(), PMS_VERSION );
        }

    }

    private function setup_functions() {

        $advanced_settings_dir  = plugin_dir_path( __FILE__ );
        $misc_settings          = get_option( 'pms_misc_settings', array() );
        $advanced_settings_keys = array( 'payment_renew_button_delay' , 'redirect_after_manual_payment', 'upgrade_downgrade_sign_up_fee', 'disable-dashboard-redirect', 'payment_retry_max_retry_amount', 'payment_retry_retry_interval', 'disable-cancel-button','disable-abandon-button', 'disable-renew-button', 'disable-change-button', 'functions-password-strength' );

        if( !empty( $misc_settings ) ){
            foreach ( $misc_settings as $misc_key => $misc_value ) {

                if ( is_array( $misc_value )) {
                    foreach ( $misc_value as $key => $value ) {
                        if ( !empty( $value ) && in_array( $key, $advanced_settings_keys )) {
                            $path = 'advanced-settings/' . $key . '.php';
                            if ( file_exists( $advanced_settings_dir . $path ) )
                                include_once $path;
                        }
                    }
                }
                else {
                    if ( !empty( $misc_value ) && in_array( $misc_key, $advanced_settings_keys )) {
                        $path = 'advanced-settings/' . $misc_key . '.php';
                        if ( file_exists( $advanced_settings_dir . $path ) )
                            include_once $path;
                    }
                }
    
            }
        }

    }

}

function pms_init_settings_page() {

    $pms_submenu_page_settings = new PMS_Submenu_Page_Settings( 'paid-member-subscriptions', esc_html__( 'Settings', 'paid-member-subscriptions' ), esc_html__( 'Settings', 'paid-member-subscriptions' ), 'manage_options', 'pms-settings-page', 30, 'pms_settings' );
    $pms_submenu_page_settings->init();

}
add_action( 'init', 'pms_init_settings_page', 9 );