<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PMS_Register_Version{

    public function __construct(){

        if( !is_multisite() ){
            add_action( 'admin_menu', array( $this, 'pms_register_your_version_submenu_page' ), 30 );
        }
        else{
            add_action( 'network_admin_menu', array( $this, 'pms_multisite_register_your_version_page' ), 20 );
        }

        add_action( 'admin_init', array( $this, 'pms_serial_register_settings' ) );
    }

    public function pms_register_your_version_submenu_page(){

        add_submenu_page( 'paid-member-subscriptions', __( 'Register Your Version', 'paid-member-subscriptions' ), __( 'Register Version', 'paid-member-subscriptions' ), 'manage_options', 'paid-member-subscriptions-register', array( $this, 'pms_register_your_version_content' ) );

    }

    public function pms_multisite_register_your_version_page(){

        add_menu_page( __( 'Paid Member Subscriptions Register', 'paid-member-subscriptions' ), __( 'Paid Member Subscriptions Register', 'paid-member-subscriptions' ), 'manage_options', 'paid-member-subscriptions-register', array( $this, 'pms_register_your_version_content' ), PMS_PLUGIN_DIR_URL . 'assets/images/pms-menu-icon.png' );
        
    }

    public function pms_serial_register_settings() {

        register_setting( 'pms_serial_number', 'pms_serial_number', array( $this, 'pms_register_serial_number' ) );

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
        ?>
        <div id="pms-register-version-page" class="wrap">
            <h2><?php esc_html_e( "Register your version of Paid Member Subscriptions", 'paid-member-subscriptions' ); ?></h2>

            <div class="pms-serial-wrap">
                <form method="post" action="<?php echo esc_url( get_admin_url( 1, 'options.php' ) ) ?>">

                    <?php
                    $pms_serial_status      = pms_get_serial_number_status();
                    $pms_serial_number      = pms_get_serial_number();
                    ?>

                    <?php settings_fields( 'pms_serial_number' ); ?>

                    <label for="pms_serial_number"><?php esc_html_e( 'Serial number', 'paid-member-subscriptions' ); ?></label>
                    <div class="pms-register-version-serial-number-wrapper <?php $this->pms_register_version_output_styling_class( $pms_serial_status ); ?>">
                        <input type="<?php echo ( ( !empty( $pms_serial_status ) && $pms_serial_status == 'notFound' ) || !$pms_serial_number ? 'text' : 'password' ); ?>" name="pms_serial_number" class="<?php $this->pms_register_version_output_styling_class( $pms_serial_status ); ?>" id="pms_serial_number" value="<?php echo ( !empty( $pms_serial_number ) ? esc_attr( pms_get_serial_number() ) : '' ); ?>">
                        <span class="status-dot"></span>

                    </div>

                    <?php submit_button( esc_html__( 'Save Changes', 'paid-member-subscriptions' ) ); ?>

                    <div class="pms-serial-wrap__status <?php $this->pms_register_version_output_styling_class( $pms_serial_status ); ?>">

                        <?php
                        $this->pms_register_version_output_serial_number_status_message();
                        ?>
                    </div>
                </form>

                <p>
                    <?php esc_html_e( 'The serial number is used to access the premium plugin versions, any updates made to them and support.', 'paid-member-subscriptions' ); ?>
                </p>

            </div>

        </div>

        <?php
    }


    public function pms_register_serial_number( $serial_number ) {

        $this->pms_register_version_check_serial_number( trim( $serial_number ), 'pms', true );

        return $serial_number;
    }


    //the function to check the validity of the serial number and save a variable in the DB; purely visual
    public static function pms_register_version_check_serial_number( $serial, $add_on_slug, $resetCron = false ){

        $remote_url = 'http://updatemetadata.cozmoslabs.com/checkserial/?serialNumberSent='.$serial;

        $remote_response = wp_remote_get( $remote_url );

        $response = PMS_Register_Version::pms_register_version_update_serial_status( $remote_response, $add_on_slug );

        if( $resetCron === true )
            PMS_Register_Version::pms_register_version_clear_cron_hooks();

        return $response;
    }


    public static function pms_register_version_clear_cron_hooks() {

        $versions = array( 'basic', 'pro', 'unlimited' );

        foreach( $versions as $version )
            wp_clear_scheduled_hook( 'check_plugin_updates-paid-member-subscriptions-' . $version . '-update' );

    }


    /* function to update the serial number status */
    public static function pms_register_version_update_serial_status( $response, $add_on_slug ) {
        if ( $add_on_slug != 'pms' ) {
            $serial_status = 'pms_add_on_'.$add_on_slug.'_serial_status';
            $serial_number = 'pms_add_on_'. $add_on_slug .'_serial_number';
        } else {
            $serial_status = 'pms_serial_number_status';
            $serial_number = 'pms_serial_number';
        }

        if ( is_wp_error($response) ) {

            update_option( $serial_status, 'serverDown' ); //server down

            return 'serverDown';
        } else {
            $response_body = trim($response['body']);

            if (($response_body != 'notFound') && ($response_body != 'found') && ($response_body != 'expired') && (strpos( $response['body'], 'aboutToExpire' ) === false)) {

                update_option( $serial_status, 'serverDown' ); //unknown response parameter
                //update_option( $serial_number, '' ); //reset the entered serial, since the user will need to try again later

                return 'serverDown';
            } else {

                update_option( $serial_status, $response_body ); //either found, notFound, expired or aboutToExpire

                return $response_body;
            }
        }
    }


    private function pms_register_version_output_serial_number_status_message() {
        $status = pms_get_serial_number_status();

        if ( empty( $status ) || !pms_get_serial_number() )
            return printf( wp_kses_post( __( 'Need a licence ? <a href="%s">Click here</a> to purchase one.', 'paid-member-subscriptions' ) ), esc_url( 'https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMS&utm_content=register-version-page-no-serial-number-message' ) );
        else if ( $status == 'found' )
            return esc_html_e( 'Your serial number has been successfully validated.', 'paid-member-subscriptions' );
        else if ( $status == 'expired' )
            return printf( wp_kses_post( __( 'Your serial number has expired. <a href="%s">Click here</a> to renew.', 'paid-member-subscriptions' ) ), esc_url( 'https://www.cozmoslabs.com/account/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMS&utm_content=register-version-page-expired-serial-number-message' ) );
        else if ( strpos( $status, 'aboutToExpire' ) !== false ) {
            $parts = explode( '#', $status );

            if ( !empty( $parts[1] ) )
                return printf( wp_kses_post( __( 'Your licence is valid but will expire on %s. <a href="%s">Click here</a> to renew.', 'paid-member-subscriptions') ), esc_html( $parts[1] ), esc_url( 'https://www.cozmoslabs.com/account/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMS&utm_content=register-version-page-expired-serial-number-message' ) );
            else
                return printf( wp_kses_post( __( 'Your licence is valid but it will expire soon. <a href="%s">Click here</a> to renew.', 'paid-member-subscriptions') ), esc_url( 'https://www.cozmoslabs.com/account/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMS&utm_content=register-version-page-expired-serial-number-message' ) );
        }
        else if ( $status == 'notFound' )
            return printf( wp_kses_post( __( 'The serial number you entered is invalid. Need a licence ? <a href="%s">Click here</a> to purchase one.', 'paid-member-subscriptions' ) ), esc_url( 'https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMS&utm_content=register-version-page-no-serial-number-message' ) );
        else if ( $status == 'serverDown' )
            return esc_html_e( 'Couldn\'t contact our server. Please try again later.', 'paid-member-subscriptions' );
    }


    private function pms_register_version_output_styling_class( $status ) {
        if ( !pms_get_serial_number() ) {}
        else if ( !empty( $status ) && ( $status == 'found' || strpos( $status, 'aboutToExpire' ) !== false ) )
            echo esc_attr( 'pms-found' );
        else
            echo esc_attr( 'pms-error' );
    }
}

new PMS_Register_Version();