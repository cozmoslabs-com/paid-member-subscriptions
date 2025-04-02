<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class PMS_Setup_Wizard {
    private $step              = '';
    private $steps             = array();
    public  $general_settings  = array();
    public  $payments_settings = array();
    public  $misc_settings     = array();
    public  $kses_args         = array(
        'strong' => array()
    );

    public function __construct(){
        if( apply_filters( 'pms_run_setup_wizard', true ) && current_user_can( 'manage_options' ) ){
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );
            add_filter( 'pms_output_dashboard_setup_wizard', array( $this, 'setup_wizard' ) );
            add_action( 'admin_init', array( $this, 'redirect_to_setup' ) );
            add_action( 'admin_init', array( $this, 'save_data' ) );
            add_action( 'wp_ajax_dismiss_newsletter_subscribe', array( $this, 'dismiss_newsletter_subscribe' ) );
        }
    }

    public function enqueue_scripts_and_styles(){
        if( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'pms-setup' ) {
            wp_enqueue_style( 'wp-jquery-ui-dialog' );
            wp_enqueue_style( 'pms-setup-wizard', PMS_PLUGIN_DIR_URL . 'assets/css/admin/style-setup-wizard.css', array(), PMS_VERSION );
            wp_enqueue_script( 'pms-wizard-js', PMS_PLUGIN_DIR_URL . 'assets/js/admin/setup-wizard.js', array( 'jquery', 'jquery-ui-core' ), PMS_VERSION );
        }
    }

    public function get_default_steps(){
        return array(
            'user-pages' => __( 'User Pages', 'paid-member-subscriptions' ),
            'general'    => __( 'Design & UI', 'paid-member-subscriptions' ),
            'payments'   => __( 'Payments', 'paid-member-subscriptions' ),
            'addons'     => __( 'Add-Ons', 'paid-member-subscriptions' ),
            'next'       => __( 'Ready!', 'paid-member-subscriptions' ),
        );
    }

    public function setup_wizard( $content ){

        if( empty( $_GET['page'] ) || $_GET['page'] != 'pms-dashboard-page' )
            return $content;

        if( empty( $_GET['subpage'] ) || $_GET['subpage'] != 'pms-setup' )
            return $content;

        $this->general_settings  = get_option( 'pms_general_settings', array() );
        $this->payments_settings = get_option( 'pms_payments_settings', array() );
        $this->misc_settings     = get_option( 'pms_misc_settings', array() );

        $default_steps = $this->get_default_steps();

        reset( $default_steps );

        $this->steps = apply_filters( 'pms_setup_wizard_steps', $default_steps );
        $this->step  = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : key( $default_steps );

        include_once 'views/view-page-setup-wizard.php';

    }

    public function save_data(){

        if( empty( $_POST['pms_setup_wizard_nonce'] ) )
            return;

        check_admin_referer( 'pms-setup-wizard-nonce', 'pms_setup_wizard_nonce' );

        $default_steps = $this->get_default_steps();

        reset( $default_steps );

        $this->steps = apply_filters( 'pms_setup_wizard_steps', $default_steps );
        $this->step  = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : key( $default_steps );

        //save data
        if( $this->step === 'user-pages' ){

            if( !empty( $_POST['pms_user_pages'] ) ){

                $pages = array(
                    'register' => array(
                        'title'   => 'Register',
                        'option'  => 'register_page',
                        'content' => '[pms-register]',
                    ),
                    'login' => array(
                        'title'   => 'Login',
                        'option'  => 'login_page',
                        'content' => '[pms-login]',
                    ),
                    'account' => array(
                        'title'   => 'Account',
                        'option'  => 'account_page',
                        'content' => '[pms-account]',
                    ),
                    'password-reset' => array(
                        'title'   => 'Password Reset',
                        'option'  => 'lost_password_page',
                        'content' => '[pms-recover-password]',
                    ),
                );

                $user_pages = array_map( 'sanitize_text_field', $_POST['pms_user_pages'] );

                foreach( $user_pages as $page_slug => $value ){
                    if( $value == 1 ){
                        $this->create_page( $pages[$page_slug]['option'], $pages[$page_slug]['title'], $pages[$page_slug]['content'] );
                    }
                }

                update_option( 'pms_general_settings', $this->general_settings );

            }

        } else if( $this->step === 'general' ){

            $settings = get_option( 'pms_general_settings', array() );

            if( isset( $_POST['pms_automatically_login'] ) )
                $settings['automatically_log_in'] = sanitize_text_field( $_POST['pms_automatically_login'] );
            else
                unset( $settings['automatically_log_in'] );

            if( isset( $_POST['pms_account_sharing'] ) )
                $settings['prevent_account_sharing'] = sanitize_text_field( $_POST['pms_account_sharing'] );
            else
                unset( $settings['prevent_account_sharing'] );

            if( isset( $_POST['pms_redirect_default'] ) )
                $settings['redirect_default_wp'] = sanitize_text_field( $_POST['pms_redirect_default'] );
            else
                unset( $settings['redirect_default_wp'] );

            if( isset( $_POST['pms_general_settings'] ) && !empty( $_POST['pms_general_settings']['forms_design'] ) )
                $settings['forms_design'] = sanitize_text_field( $_POST['pms_general_settings']['forms_design'] );
            else
                $settings['forms_design'] = 'default';

            if( !empty( $settings ) )
                update_option( 'pms_general_settings', $settings );

            $misc_settings = get_option( 'pms_misc_settings', array() );

            if( isset( $_POST['pms_hide_admin_bar'] ) )
                $misc_settings['hide-admin-bar'] = sanitize_text_field( $_POST['pms_hide_admin_bar'] );
            else
                unset( $misc_settings['hide-admin-bar'] );

            if( !empty( $misc_settings ) )
                update_option( 'pms_misc_settings', $misc_settings );

        } else if( $this->step === 'payments' ){

            $settings = get_option( 'pms_payments_settings', array() );

            if( isset( $_POST['pms_payments_currency'] ) )
                $settings['currency'] = sanitize_text_field( $_POST['pms_payments_currency'] );

            if( isset( $_POST['pms_payments_currency_position'] ) )
                $settings['currency_position'] = sanitize_text_field( $_POST['pms_payments_currency_position'] );

            if( isset( $_POST['pms_payments_price_format'] ) )
                $settings['price-display-format'] = sanitize_text_field( $_POST['pms_payments_price_format'] );

            // if( isset( $_POST['pms_payments_renewal'] ) )
            //     $settings['recurring'] = sanitize_text_field( $_POST['pms_payments_renewal'] );

            $settings['active_pay_gates'] = array();

            if( isset( $_POST['pms_gateway_offline'] ) ){
                $settings['active_pay_gates'][] = 'manual';
                $settings['default_payment_gateway'] = 'manual';
            }

            if( isset( $_POST['pms_gateway_paypal_connect'] ) ){
                $settings['active_pay_gates'][] = 'paypal_connect';
                $settings['default_payment_gateway'] = 'paypal_connect';
            }

            if( isset( $_POST['pms_gateway_stripe'] ) ){
                $settings['active_pay_gates'][] = 'stripe_connect';
                $settings['default_payment_gateway'] = 'stripe_connect';
            }

            if( !empty( $settings ) )
                update_option( 'pms_payments_settings', $settings );

        } else if ( $this->step == 'addons' ){

            $target_addons = array(
                'pms-add-on-invoices',
                'pms-add-on-multiple-subscriptions-per-user',
                'pms-add-on-global-content-restriction',
                'pms-add-on-pay-what-you-want',
            );

            foreach( $target_addons as $addon ){
                if( isset( $_POST[ $addon ] ) && $_POST[ $addon ] == 'yes' )
                    do_action( 'pms_add_ons_activate', $addon . '/index.php' );
                else
                    do_action( 'pms_add_ons_deactivate', $addon . '/index.php' );
            }

        }

        // step completion for setup
        $steps_completion = $this->get_completed_progress_steps();

        if( !empty( $this->step ) && !in_array( $this->step, array( 'addons' ) ) ){
            if( empty( $steps_completion ) ){

                $steps_completion = array(
                    $this->step => 1,
                );

            } else {

                $steps_completion[$this->step] = 1;

            }
        }

        update_option( 'pms_setup_wizard_steps', $steps_completion );

        //redirect to the next step at the end
        wp_safe_redirect( esc_url_raw( $this->get_next_step_link() ) );
        exit;
    }

    public static function get_completed_progress_steps(){
        return get_option( 'pms_setup_wizard_steps', array() );
    }

    private function get_next_step_link( $step = '' ){
        if( !$step )
            $step = $this->step;

        $keys = array_keys( $this->steps );

        if( end( $keys ) === $step )
            return admin_url();

        $step_index = array_search( $step, $keys, true );

        if( $step_index === false )
            return '';

        return esc_url_raw( add_query_arg( 'step', $keys[$step_index + 1] ) );
    }

    private function create_page( $option, $title, $content = '' ){
        if( empty( $this->general_settings ) )
            $this->general_settings = get_option( 'pms_general_settings', array() );

        //try to find an existing page with the shortcode
        if( empty( $this->general_settings[$option] ) || $this->general_settings[$option] == '-1' ) {

            if( !empty( $content ) ){
                global $wpdb;

                $shortcode = str_replace( array( '<!-- wp:shortcode -->', '<!-- /wp:shortcode -->' ), '', $content );
                $existing_page = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", '%' . $shortcode . '%' ) );

                if( !empty( $existing_page ) ) {
                    $this->general_settings[$option] = $existing_page;

                    return $existing_page;
                }
            }

            $page = array(
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => $title,
                'post_content' => $content
            );

            $page_id = wp_insert_post( $page );
            $this->general_settings[$option] = $page_id;
        }
    }

    public static function get_progress_steps(){

        $progress_steps = array(
            'user-pages'         => array(
                'label' => __( 'Create user pages for registration, login, account and password reset.', 'paid-member-subscriptions' ),
                'url'   => admin_url( 'admin.php?page=pms-dashboard-page&subpage=pms-setup' ),
            ),
            'general'            => array(
                'label' => __( 'Choose a design and optimize the login and registration flow for your users.', 'paid-member-subscriptions' ),
                'url'   => admin_url( 'admin.php?page=pms-dashboard-page&subpage=pms-setup&step=general' ),
            ),
            'payments'           => array(
                'label' => __( 'Setup how your currency is displayed and choose & configure a payment gateway.', 'paid-member-subscriptions' ),
                'url'   => admin_url( 'admin.php?page=pms-dashboard-page&subpage=pms-setup&step=payments' ),
            ),
            'subscription_plans' => array(
                'label' => __( 'Create a subscription plan and start registering new members.', 'paid-member-subscriptions' ),
                'url'   => admin_url( 'edit.php?post_type=pms-subscription' ),
            ),
            'restrict_content'   => array(
                'label'  => __( 'Restrict your content based on the newly created subscription plans.', 'paid-member-subscriptions' ),
                'url'    => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/content-restriction/?utm_source=wpbackend&utm_medium=pms-dashboard-page&utm_campaign=PMSPro',
                'target' => '_blank',
            ),
            'pricing_page' => array(
                    'label' => __( 'Create a Pricing Page to sell your plans.', 'paid-member-subscriptions' ),
                    'url'   => '',
                    'id'    => 'pms-popup2',
            ),
        );

        if ( ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) || ( is_plugin_active_for_network('woocommerce/woocommerce.php') ) ){
            $progress_steps['woocommerce'] = array(
                'label'  => __( 'Integrate with WooCommerce: discount for members, restrict products or sell subscriptions.', 'paid-member-subscriptions' ),
                'url'    => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/integration-with-other-plugins/woocommerce/?utm_source=wpbackend&utm_medium=pms-dashboard-page&utm_campaign=PMSPro',
                'target' => '_blank',
            );

        }

        return $progress_steps;

    }

    public static function output_progress_steps(){

        $steps            = self::get_progress_steps();
        $steps_completion = self::get_completed_progress_steps();


        // Membership Pages and General Settings Completion
        if( !isset( $steps_completion['user-pages'] ) && self::website_has_plugin_pages() ){
            $steps_completion['user-pages'] = 1;
            $steps_completion['general']    = 1;
        }

        // Payments Completion
        if( !isset( $steps_completion['payments'] ) && self::website_has_payments() )
            $steps_completion['payments'] = 1;

        // Subscription Plans Completion
        if( !isset( $steps_completion['subscription_plans'] ) && self::website_has_subscription_plans() )
            $steps_completion['subscription_plans'] = 1;

        // Restricted Content Completion
        if( !isset( $steps_completion['restrict_content'] ) && self::website_has_restricted_content() )
            $steps_completion['restrict_content'] = 1;

        // Pricing Page Completion
        if( !isset( $steps_completion['pricing_page'] ) && self::website_has_pricing_page() )
            $steps_completion['pricing_page'] = 1;

        // WooCommerce
        if( !isset( $steps_completion['woocommerce'] ) && self::website_has_restricted_purchase_products() )
            $steps_completion['woocommerce'] = 1;

        update_option( 'pms_setup_wizard_steps', $steps_completion );

        $current_step = is_array( $steps_completion ) ? count( $steps_completion ) : 0;
        $total_steps  = count( $steps );

        if( $current_step > $total_steps )
            $current_step = $total_steps;

        ob_start(); ?>

        <div class="pms-setup-progress">
            <h3><?php esc_html_e( 'Progress Review', 'paid-member-subscriptions' ); ?></h3>
            <p><?php printf( esc_html__( 'Follow these steps to start a membership site quickly. %1s out of %2s complete.', 'paid-member-subscriptions' ), esc_html( $current_step ), esc_html( $total_steps ) ); ?></p>

            <div class="pms-setup-progress__bar">
                <?php foreach( $steps as $slug => $step ) : ?>
                    <div class="item <?php echo isset( $steps_completion[$slug] ) && $steps_completion[$slug] == 1 ? 'completed' : ''; ?>"></div>
                <?php endforeach; ?>
            </div>

            <div class="pms-setup-progress__steps">
                <?php foreach( $steps as $slug => $step ) : ?>
                    <a class="pms-setup-progress__step <?php echo isset( $steps_completion[$slug] ) && $steps_completion[$slug] == 1 ? 'completed' : ''; ?>" href="<?php echo esc_url( $step['url'] ) ?>" target="<?php echo isset( $step['target'] ) ? esc_html( $step['target'] ) : '' ?>" id="<?php echo isset( $step['id'] ) ? esc_html( $step['id'] ) : '' ?>">
                         <?php echo esc_html( $step['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>


        <?php
        $output = ob_get_clean();

        echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    }

    public static function output_modal_progress_steps(){

        pms_output_modal_create_pricing_page();
    }

    public function check_value( $slug ){
        if( $slug == 'automatically_log_in' && !get_option( 'pms_already_installed' ) )
            return true;

        if( !empty( $this->general_settings[$slug] ) && $this->general_settings[$slug] == '1' )
            return true;
        else if( !empty( $this->misc_settings[$slug] ) && $this->misc_settings[$slug] == '1' )
            return true;

        return false;
    }

    public function check_gateway( $slug ){
        if( in_array( $slug, pms_get_active_payment_gateways() ) )
            return true;

        return false;
    }

    public function redirect_to_setup(){
        $run_setup = get_transient( 'pms_run_setup_wizard' );

        if( $run_setup == true ){
            delete_transient( 'pms_run_setup_wizard' );
            wp_safe_redirect( admin_url( 'admin.php?page=pms-dashboard-page&subpage=pms-setup' ) );
            die();
        }
    }

    public static function website_has_payments(){

        $payments = pms_get_payments( array( 'number' => '5' ) );

        if( !empty( count( $payments ) ) )
            return true;

        return false;

    }

    public static function website_has_plugin_pages(){

        $settings = get_option( 'pms_general_settings', array() );

        $pages = array( 'login_page', 'register_page', 'account_page', 'lost_password_page' );

        foreach( $pages as $page ){
            if( !empty( $settings[ $page ] ) )
                return true;
        }

        return false;

    }

    public static function website_has_subscription_plans(){

        $args = [
            'post_type'      => 'pms-subscription',
            'posts_per_page' => '1',
        ];

        $result = new WP_Query( $args );

        if( $result->have_posts() )
            return true;

        return false;

    }

    public static function website_has_pricing_page(){
        if( get_option('pms_create_pricing_page_complete') === 'pricing_page_exist'){
            return true;
        }

        return false;
    }

    public static function website_has_restricted_content(){

        $args = [
            'posts_per_page' => '1',
            'post_type'      => array( 'post', 'page' ),
            'meta_query'     => [
                [
                    'key'     => 'pms-content-restrict-subscription-plan',
                    'compare' => 'EXISTS'
                ]
            ],
        ];

        $result = new WP_Query( $args );

        if( $result->have_posts() )
            return true;

        // Logged in meta
        $args = [
            'posts_per_page' => '1',
            'post_type'      => array( 'post', 'page' ),
            'meta_query'     => [
                [
                    'key'     => 'pms-content-restrict-user-status',
                    'compare' => 'EXISTS'
                ]
            ],
        ];

        $result = new WP_Query( $args );

        if( $result->have_posts() )
            return true;

        return false;

    }

    public static function website_has_restricted_purchase_products(){

        // Purchase Restrictions
        $args = [
            'posts_per_page' => '1',
            'post_type'      => 'product',
            'meta_query'     => [
                [
                    'key'     => 'pms-purchase-restrict-subscription-plan',
                    'compare' => 'EXISTS'
                ]
            ],
        ];

        $result = new WP_Query( $args );

        if( $result->have_posts() )
            return true;

        // View Restrictions per Plan
        $args = [
            'posts_per_page' => '1',
            'post_type'      => 'product',
            'meta_query'     => [
                [
                    'key'     => 'pms-content-restrict-subscription-plan',
                    'compare' => 'EXISTS'
                ]
            ],
        ];

        $result = new WP_Query( $args );

        if( $result->have_posts() )
            return true;

        // View Restrictions logged in
        $args = [
            'posts_per_page' => '1',
            'post_type'      => 'product',
            'meta_query'     => [
                [
                    'key'     => 'pms-content-restrict-user-status',
                    'compare' => 'EXISTS'
                ]
            ],
        ];

        $result = new WP_Query( $args );

        if( $result->have_posts() )
            return true;

        // Discounts functionality
        $args = [
            'posts_per_page' => '1',
            'post_type'      => 'product',
            'meta_query'     => [
                [
                    'key'     => 'pms-woo-product-membership-discounts',
                    'compare' => 'EXISTS'
                ]
            ],
        ];

        $result = new WP_Query( $args );

        if( $result->have_posts() )
            return true;

        return false;

    }

    public function dismiss_newsletter_subscribe() {

        check_ajax_referer( 'dismiss_setup_wizard_newsletter_subscribe', 'pms_nonce' );

        $user_id = get_current_user_id();

        if( !empty( $user_id ) )
            update_user_meta( $user_id, 'pms_setup_wizard_newsletter', 1 );

        wp_die();

    }
}

new PMS_Setup_Wizard();
