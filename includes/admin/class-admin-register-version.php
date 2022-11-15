<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PMS_Register_Version {

    public function __construct(){

        if( !is_multisite() )
            add_action( 'admin_menu', array( $this, 'pms_register_your_version_submenu_page' ), 30 );
        else
            add_action( 'network_admin_menu', array( $this, 'pms_multisite_register_your_version_page' ), 20 );

        add_action( 'admin_init', array( $this, 'register_settings' ) );

    }

    public function pms_register_your_version_submenu_page(){

        if ( pms_are_paid_versions_active() )
            add_submenu_page( 'paid-member-subscriptions', __( 'Register Your Version', 'paid-member-subscriptions' ), __( 'Register Version', 'paid-member-subscriptions' ), 'manage_options', 'pms-register-page', array( $this, 'pms_register_your_version_content' ) );

    }

    public function pms_multisite_register_your_version_page(){

        if ( pms_are_paid_versions_active() )
            add_menu_page( __( 'Paid Member Subscriptions Register', 'paid-member-subscriptions' ), __( 'Paid Member Subscriptions Register', 'paid-member-subscriptions' ), 'manage_options', 'pms-register-page', array( $this, 'pms_register_your_version_content' ), PMS_PLUGIN_DIR_URL . 'assets/images/pms-menu-icon.png' );
        
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
        <div id="pms-register-version-page" class="wrap">
            <h2><?php esc_html_e( "Register your version of Paid Member Subscriptions", 'paid-member-subscriptions' ); ?></h2>

            <div class="pms-serial-wrap">
                <form method="post" action="<?php echo !is_multisite() ? 'options.php' : 'edit.php'; ?>">
                    <?php settings_fields( 'pms_serial_number' ); ?>

                    <label for="pms_serial_number"><?php esc_html_e( 'License key', 'paid-member-subscriptions' ); ?></label>

                    <div class="pms-serial-wrap__holder">
                        <input id="pms_serial_number" name="pms_serial_number" type="password" class="regular-text" value="<?php echo esc_attr( $license ); ?>" />
                        <?php wp_nonce_field( 'pms_license_nonce', 'pms_license_nonce' ); ?>

                        <?php if( $status !== false && $status == 'valid' ) {
                            $button_name =  'pms_edd_license_deactivate';
                            $button_value = __('Deactivate License', 'paid-member-subscriptions' );

                            if( empty( $details['invalid'] ) )
                                echo '<span title="'. esc_html__( 'Active on this site', 'paid-member-subscriptions' ) .'" class="pms-active-license dashicons dashicons-yes"></span>';
                            else
                                echo '<span title="'. esc_html__( 'Your license is invalid', 'paid-member-subscriptions' ) .'" class="pms-invalid-license dashicons dashicons-warning"></span>';

                        } else {
                            $button_name =  'pms_edd_license_activate';
                            $button_value = __('Activate License', 'paid-member-subscriptions');
                        }
                        ?>
                        <input type="submit" class="button-secondary" name="<?php echo esc_attr( $button_name ); ?>" value="<?php echo esc_attr( $button_value ); ?>"/>
                    </div>
                </form>

                <p>
                    <?php esc_html_e( 'The serial number is used to access the premium plugin versions, any updates made to them and support.', 'paid-member-subscriptions' ); ?>
                </p>
            </div>
        </div>

        <?php
    }

}

new PMS_Register_Version();