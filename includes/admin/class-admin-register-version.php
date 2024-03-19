<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PMS_Register_Version {
    public function __construct(){

        if( !is_multisite() )
            return;

        add_action( 'network_admin_menu', array( $this, 'pms_multisite_register_your_version_page' ), 20 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function pms_register_your_version_submenu_page(){
        if ( pms_is_paid_version_active() )
            add_submenu_page( 'paid-member-subscriptions', __( 'Register Your Version', 'paid-member-subscriptions' ), __( 'Register Version', 'paid-member-subscriptions' ), 'manage_options', 'pms-register-page', array( $this, 'pms_register_your_version_content' ) );
    }

    public function pms_multisite_register_your_version_page(){
        if ( pms_is_paid_version_active() )
            add_menu_page( __( 'Paid Member Subscriptions Register', 'paid-member-subscriptions' ), __( 'Paid Member Subscriptions Register', 'paid-member-subscriptions' ), 'manage_options', 'pms-register-page', array( $this, 'pms_register_your_version_content' ), PMS_PLUGIN_DIR_URL . 'assets/images/pms-wp-menu-icon.svg' );

    }

    public function register_settings(){
        register_setting( 'pms_serial_number', 'pms_serial_number' );
    }

    /**
     * Function that adds content to the "Register Version" submenu page
     *
     * @return string
     */
    public function pms_register_your_version_content() {
        ?>
        <div class="wrap pms-wrap">
            <?php
            $this->pms_serial_form();
            ?>
        </div>
        <?php
    }
    
    /**
     * Function that creates the "Register Version" form
     *
     * @return void
     */
    private function pms_serial_form(){
        $status  = pms_get_serial_number_status();
        $license = pms_get_serial_number();
        ?>
        <div id="pms-register-version-page" class="wrap cozmoslabs-wrap">

            <h1></h1>
            <!-- WordPress Notices are added after the h1 tag -->

            <div class="cozmoslabs-page-header">
                <div class="cozmoslabs-section-title">
                    <h2 class="cozmoslabs-page-title"><?php esc_html_e( "Register your version of Paid Member Subscriptions", 'paid-member-subscriptions' ); ?></h2>
                    <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/basic-information-and-installation?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs#Add_Serial_Number" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
                </div>
            </div>

            <?php pms_add_register_version_form(); ?>
        </div>
        <?php
    }
}

new PMS_Register_Version();