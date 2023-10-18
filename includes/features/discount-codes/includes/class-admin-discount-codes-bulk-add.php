<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Extends core PMS_Submenu_Page base class to create and add a basic information page
 *
 * The basic information page will contain a quick walk through the plugin features
 *
 */
Class PMS_IN_Custom_Post_Type_Discount_Codes_Bulk_Add extends PMS_Submenu_Page {


    /*
     * Method that initializes the class
     *
     */
    public function init() {

        // Hook the output method to the parent's class action for output instead of overwriting the
        // output method
        add_action( 'pms_output_content_submenu_page_' . $this->menu_slug, array( $this, 'output' ) );
        
        add_action( 'init', array( $this, 'bulk_import_discount_codes' ) );

    }

    /*
     * Method to output content in the custom page
     *
     */
    public function output() {

        include_once PMS_IN_DC_PLUGIN_DIR_PATH . 'views/view-page-bulk-add-discount-codes.php';

    }

    public function bulk_import_discount_codes() {

        if( !isset( $_REQUEST['pms_nonce'] ) || !isset( $_FILES['pms_bulk_add_discount_codes'] ) )
            return;

        if( ! wp_verify_nonce( sanitize_text_field( $_REQUEST['pms_nonce'] ), 'pms_bulk_add_discount_codes' ) )
            wp_die( esc_html__( 'Nonce verification failed', 'paid-member-subscriptions' ), esc_html__( 'Error', 'paid-member-subscriptions' ), array( 'response' => 403 ) );
        
        if( empty( $_FILES['pms_bulk_add_discount_codes']['name'] ) )
            wp_die( esc_html__( 'Cannot process file.', 'paid-member-subscriptions' ), esc_html__( 'Error', 'paid-member-subscriptions' ), array( 'response' => 403 ) );

        $filename = sanitize_text_field( $_FILES['pms_bulk_add_discount_codes']['name'] );

        if ( pathinfo($filename, PATHINFO_EXTENSION) != 'csv' ) 
            wp_die( esc_html__( 'Uploaded file must be a .csv file.', 'paid-member-subscriptions' ), esc_html__( 'Error', 'paid-member-subscriptions' ), array( 'response' => 403 ) );

        if( empty( $_FILES['pms_bulk_add_discount_codes']['tmp_name'] ) )
            wp_die( esc_html__( 'Cannot process file.', 'paid-member-subscriptions' ), esc_html__( 'Error', 'paid-member-subscriptions' ), array( 'response' => 403 ) );

        $data = $this->csv_to_array( $_FILES['pms_bulk_add_discount_codes']['tmp_name'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        if( !empty( $data ) ) {
            foreach( $data as $discount_code ) {
                if( !empty( $discount_code['code'] ) && pms_in_get_discount_by_code( $discount_code['code'] ) === false )
                    $this->create_discount_code( $discount_code );
            }
        }

        wp_redirect( admin_url( 'edit.php?post_type=pms-discount-codes' ) );
        exit;

    }

    private function create_discount_code( $data ){

        if( empty( $data ) )
            return;

        $discount_data = [
            'post_title'  => isset( $data['name'] ) ? $data['name'] : 'Discount Code',
            'post_status' => 'active',
            'post_type'   => 'pms-discount-codes',
            'meta_input'  => [],
        ];

        $meta_keys          = $this->get_discount_code_meta_keys();
        $checkbox_type_keys = $this->get_discount_code_checkbox_meta_keys();

        foreach( $meta_keys as $postmeta_key => $csv_key ){

            if( isset( $data[ $csv_key ] ) ){

                if( in_array( $postmeta_key, [ 'pms_discount_start_date', 'pms_discount_expiration_date' ] ) ){

                    if( $this->validate_date( $data[ $csv_key ] ) )
                        $discount_data['meta_input'][ $postmeta_key ] = $data[ $csv_key ];
                    else
                        $discount_data['meta_input'][ $postmeta_key ] = '';

                } elseif( in_array( $postmeta_key, $checkbox_type_keys ) )
                    $discount_data['meta_input'][ $postmeta_key ] = $data[ $csv_key ] == 1 ? 'checked' : '';
                else
                    $discount_data['meta_input'][ $postmeta_key ] = $data[ $csv_key ];
            }

        }

        wp_insert_post( $discount_data );

    }

    private function get_discount_code_meta_keys(){

        return apply_filters( 'pms_discount_codes_bulk_add_meta_keys', [
            'pms_discount_code'               => 'code',
            'pms_discount_type'               => 'type',
            'pms_discount_amount'             => 'amount',
            'pms_discount_subscriptions'      => 'subscription_plans',
            'pms_discount_max_uses'           => 'max_uses',
            'pms_discount_max_uses_per_user'  => 'max_uses_per_user',
            'pms_discount_start_date'         => 'start_date',
            'pms_discount_expiration_date'    => 'expiration_date',
            'pms_discount_status'             => 'status',
            'pms_discount_recurring_payments' => 'recurring_payments',
            'pms_discount_new_users_only'     => 'new_users_only'
        ]);

    }

    private function get_discount_code_checkbox_meta_keys(){

        return apply_filters( 'pms_discount_codes_bulk_add_meta_keys_checkboxes', [
            'pms_discount_recurring_payments',
            'pms_discount_new_users_only',
        ]);

    }

    private function csv_to_array( $filename = '', $delimiter = ',' ) {

        if( !file_exists( $filename ) || !is_readable( $filename ) )
            return FALSE;

        $header = NULL;
        $data   = array();

        if ( ( $handle = fopen( $filename, 'r' ) ) !== FALSE ) {
            while ( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== FALSE ) {
                if( !$header )
                    $header = $row;
                else {
                    
                    if( count( $header ) != count( $row ) )
                        continue;

                    $data[] = array_combine( $header, $row );
                }
            }

            fclose( $handle );
        }

        return $data;

    }

    private function validate_date( $date ){

        $dateTime = DateTime::createFromFormat( 'Y-m-d', $date );

        $errors = DateTime::getLastErrors();

        if ( !empty( $errors['warning_count'] ) )
            return false;

        return $dateTime !== false;

    }

}

$pms_submenu_page_basic_info = new PMS_IN_Custom_Post_Type_Discount_Codes_Bulk_Add( 'paid-member-subscriptions', esc_html__( 'Bulk Import Discount Codes', 'paid-member-subscriptions' ), esc_html__( 'Bulk Import Discount Codes', 'paid-member-subscriptions' ), 'manage_options', 'pms-discount-codes-bulk-add', 9);
$pms_submenu_page_basic_info->init();
