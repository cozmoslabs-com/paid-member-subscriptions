<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Extends core PMS_Submenu_Page base class to create and add custom functionality
 * for the add-ons page in the admin section
 *
 * The Add-ons page will contain a listing of all the available add-ons for PMS,
 * allowing the user to purchase, install or activate a certain add-on.
 *
 */
Class PMS_Submenu_Page_Addons extends PMS_Submenu_Page {

    /*
     * Method that initializes the class
     *
     * */
    public function init() {

        // Hook the output method to the parent's class action for output instead of overwriting the
        // output method
        add_action( 'pms_output_content_submenu_page_' . $this->menu_slug, array( $this, 'output' ) );

        add_action( 'wp_ajax_pms_add_on_activate', array( $this, 'add_on_activate' ) );
        add_action( 'wp_ajax_pms_add_on_deactivate', array( $this, 'add_on_deactivate' ) );

    }

    /*
     * Method to output the content in the Add-ons page
     *
     * */
    public function output(){

        include_once 'views/view-page-addons.php';

    }

    /*
    * Function that returns the array of add-ons from cozmoslabs.com if it finds the file
    * If something goes wrong it returns false
    *
    * @since v.2.1.0
    */
    static function add_ons_get_remote_content() {
        $slug = 'pms_add_ons_remote_content';

        if ( false === ( $pms_add_ons = get_transient( $slug ) ) ) {
            global $wp_version;

            $args = array(
                'user-agent'  => 'PMS/WordPress/' . $wp_version . '; ' . pms_get_current_page_url(),
            );

            $response = wp_remote_get( 'https://www.cozmoslabs.com/wp-content/plugins/cozmoslabs-products-add-ons/paid-member-subscriptions-add-ons.json', $args );

            if( is_wp_error( $response ) ) {
                return false;
            } else {
                $json_file_contents = $response['body'];
                $pms_add_ons        = json_decode( $json_file_contents, true );
            }

            if( !is_object( $pms_add_ons ) && !is_array( $pms_add_ons ) )
                return false;

            set_transient( $slug, $pms_add_ons, 60 * MINUTE_IN_SECONDS );
        }

        return $pms_add_ons;
    }


    /**
     * Function that is triggered through Ajax to activate an add-on
     *
     */
    function add_on_activate(){

        check_ajax_referer( 'pms-activate-addon', 'nonce' );

        if( current_user_can( 'manage_options' ) && isset( $_POST['pms_add_on_to_activate'] ) ){

            // Setup variables from POST
            $pms_add_on_to_activate = sanitize_text_field( $_POST['pms_add_on_to_activate'] );
            $response               = isset( $_POST['pms_add_on_index'] ) ? (int)$_POST['pms_add_on_index'] : '';

            if( !empty( $pms_add_on_to_activate ) && !is_plugin_active( $pms_add_on_to_activate )) {
                activate_plugin( $pms_add_on_to_activate );
            }

            if( !empty( $response ) || $response == 0 )
                echo esc_html( $response );
        }

        wp_die();
    }

    /**
     * Function that is triggered through Ajax to deactivate an add-on
     *
     */
    function add_on_deactivate() {

        check_ajax_referer( 'pms-activate-addon', 'nonce' );

        if( current_user_can( 'manage_options' ) && isset( $_POST['pms_add_on_to_deactivate'] ) ) {

            // Setup variables from POST
            $pms_add_on_to_deactivate = sanitize_text_field( $_POST['pms_add_on_to_deactivate'] );
            $response                 = isset( $_POST['pms_add_on_index'] ) ? (int)$_POST['pms_add_on_index'] : '';

            if( !empty( $pms_add_on_to_deactivate ))
                deactivate_plugins( $pms_add_on_to_deactivate );

            if( !empty( $response ) || $response == 0 )
                echo esc_html( $response );
        }

        wp_die();

    }

}

$pms_submenu_page_addons = new PMS_Submenu_Page_Addons( 'paid-member-subscriptions', __( 'Add-ons', 'paid-member-subscriptions' ), __( 'Add-ons', 'paid-member-subscriptions' ), 'manage_options', 'pms-addons-page', 30, '' );
$pms_submenu_page_addons->init();
