<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Extends core PMS_Submenu_Page base class to create and add an Import Members page
 *
 */
Class PMS_Submenu_Page_Import extends PMS_Submenu_Page {


    /*
     * Method that initializes the class
     *
     */
    public function init() {

        // Hook the output method to the parent's class action for output instead of overwriting the
        // output method
        add_action( 'pms_output_content_submenu_page_' . $this->menu_slug, array( $this, 'output' ) );
        add_action( 'wp_ajax_pms_do_ajax_import',  array( $this, 'pms_do_ajax_import' ) );

    }

    /*
     * Process Ajax requests from pms-import-page
     *
     */
    public function pms_do_ajax_import(){

        if( !isset( $_POST['form'] ) || !isset( $_POST['csv'] ))
            die();

        parse_str( $_POST['form'], $form ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        $_REQUEST = $form = (array) pms_array_sanitize_text_field( $form );

        if( !isset( $_REQUEST['pms_ajax_import'] ) || !wp_verify_nonce( sanitize_text_field( $_REQUEST['pms_ajax_import'] ), 'pms_ajax_import' ) ) {
            echo json_encode( array( 'error' => true, 'message' => esc_html__( 'Something went wrong!', 'paid-member-subscriptions' ) ) );
            exit;
        }

        $csv = json_decode( stripslashes( sanitize_text_field( $_POST['csv'] ) ) );

        if( empty( $csv ) ){
            echo json_encode( array( 'error' => true, 'message' => 'Could not read file!' ) ); exit;
            die();
        }

        $csv_parse_error = false;

        $csv = array_map('str_getcsv', str_getcsv($csv, "\n"));

        $header_column_count = count( $csv[0] );
        
        array_walk($csv, function(&$a) use ($csv,$header_column_count,&$csv_parse_error) {

            if( count( $a ) != $header_column_count ){
                $csv_parse_error = true;
                $a = array();
            } else {
                $a = array_combine($csv[0], $a);
            }

        });

        if( $csv_parse_error ){
            echo json_encode( array( 'error' => true, 'message' => 'File error: mismatched columns. The header and rows of the file are not matched. Please verify your file and try again.' ) ); exit;
            die();
        }

        array_shift($csv); // remove column headers

        foreach( $csv as $membership ) {

            if( empty( $membership ) )
                continue;

            // check if subscription_id is present
            if( !empty( $membership[ 'subscription_id' ] ) ){
                
                $member = [ 'subscription_id' => absint( $membership[ 'subscription_id' ] ) ];
                $this->handle_subscription( $member, $membership );
                continue;

            }

            $user = empty( $membership[ 'subscription_user_id' ] ) ? false : get_userdata( absint( $membership[ 'subscription_user_id' ] ) );

            if ( $user !== false ) {

                // user id exists
                $member = pms_get_member( absint( $membership[ 'subscription_user_id' ] ) );
                $this->handle_subscription( $member, $membership );

            } else {

                // user id does not exist
                if( empty( $membership[ 'user_email' ] ) )
                    continue;

                $user = get_user_by( 'email', $membership[ 'user_email' ] );

                if ( $user !== false ) {
                    // user email exists
                    $member = pms_get_member( $user->ID );
                    $this->handle_subscription( $member, $membership, true );

                } else {
                    // user email does not exist

                    // generate username
                    $username = isset( $membership[ 'user_username' ] ) && $membership[ 'user_username' ] !== '' ? $membership[ 'user_username' ] : rawurldecode( sanitize_title_with_dashes( remove_accents( $membership[ 'user_email' ] ) ) );

                    $user_data = array(
                        'user_login' 			=> $username,
                        'user_email' 			=> $membership[ 'user_email' ],
                        'user_pass' 			=> '',
                        'role' 		         	=> apply_filters( 'pms_change_default_site_user_role', get_option('default_role') ),
                    );

                    if ( isset( $membership[ 'user_firstname' ] ) && $membership[ 'user_firstname' ] !== '' ) array_push( $user_data, $membership[ 'user_firstname' ] );
                    if ( isset( $membership[ 'user_lastname' ] ) && $membership[ 'user_lastname' ]  !== '' ) array_push( $user_data, $membership[ 'user_lastname' ]  );

                    // Register the user and grab the user_id
                    $user_id = wp_insert_user( $user_data );

                    if( !is_wp_error( $user_id ) ) {
                        $member = pms_get_member( $user_id );
                        $this->handle_subscription( $member, $membership, true );
                    }
                }
            }
        }

        echo json_encode( array( 'success' => true, 'message' => 'Successful import!' ) ); exit;
    }

    /*
     * Update existing subscriptions or create new ones as necessary
     *
     */
    private function handle_subscription( $member, $membership, $update_user_id = false ) {

        if( empty( $membership[ 'subscription_plan_id' ] ) )
            return;

        $subscription_plan = pms_get_subscription_plan( $membership[ 'subscription_plan_id' ] );

        if ( !$subscription_plan->id )
            return;

        $found = false;

        $subscription_data_keys = array(
            'user_id'               => 'subscription_user_id',
            'subscription_plan_id'  => 'subscription_plan_id',
            'start_date'            => 'subscription_start_date',
            'expiration_date'       => 'subscription_expiration_date',
            'status'                => 'subscription_status',
            'payment_profile_id'    => 'subscription_payment_profile_id',
            'payment_gateway'       => 'subscription_payment_gateway',
            'billing_amount'        => 'subscription_billing_amount',
            'billing_duration'      => 'subscription_billing_duration',
            'billing_duration_unit' => 'subscription_billing_duration_unit',
            'billing_cycles'        => 'subscription_billing_cycles',
            'billing_next_payment'  => 'subscription_billing_next_payment',
            'billing_last_payment'  => 'subscription_billing_last_payment',
            'trial_end'             => 'subscription_trial_end',
        );

        $subscription_data = array();

        foreach( $subscription_data_keys as $key => $import_key ){

            if( $key == 'user_id' && isset( $membership[$import_key] ) ){
                if( $update_user_id )
                    $subscription_data[ $key ] = $membership[ $import_key ];
            } else if( isset( $membership[$import_key] ) )
                $subscription_data[ $key ] = $membership[ $import_key ];
        }

        // Don't allow updates on the billing_amount column
        if( isset( $subscription_data['billing_amount'] ) )
            unset( $subscription_data['billing_amount'] );

        $membership_user_id = isset( $membership['subscription_user_id'] ) ? $membership['subscription_user_id'] : 0;

        // Update subscription 
        if( !empty( $member['subscription_id'] ) ){

            $subscription = pms_get_member_subscription( $member['subscription_id'] );

            if( empty( $subscription->id ) )
                return;

            $subscription->update( $subscription_data );

            pms_add_member_subscription_log( $subscription->id, 'subscription_import_updated', array( 'who' => get_current_user_id(), 'fields' => implode( ', ', array_keys( $subscription_data ) ) ) );

            // Update subscription meta
            foreach( $membership as $key => $value ) {
                if ( strpos( $key, "subscriptionmeta_" ) === 0 ) {
                    $key = str_replace( "subscriptionmeta_", "", $key );
                    pms_update_member_subscription_meta( $subscription->id, $key, $value );
                }

                if( !empty( $membership_user_id ) ){
                    if ( strpos( $key, "usermeta_" ) === 0 ) {
                        $key = str_replace( "usermeta_", "", $key );
                        update_user_meta( $membership_user_id, $key, $value );
                    }
                }
            }

            $found = true;

        } else if( !empty( $member->subscriptions ) ){

            foreach ( $member->subscriptions as $single_subscription ) {
                if ( $membership[ 'subscription_plan_id' ] === $single_subscription[ 'subscription_plan_id' ] ) {

                    $subscription = pms_get_member_subscription( $single_subscription[ 'id' ] );

                    $subscription->update( $subscription_data );

                    pms_add_member_subscription_log( $subscription->id, 'subscription_import_updated', array( 'who' => get_current_user_id(), 'fields' => implode( ', ', array_keys( $subscription_data ) ) ) );

                    // Update subscription meta
                    foreach( $membership as $key => $value ) {

                        if ( strpos( $key, "subscriptionmeta_" ) === 0 ) {
                            $key = str_replace( "subscriptionmeta_", "", $key );
                            pms_update_member_subscription_meta( $subscription->id, $key, $value );
                        }

                        if( !empty( $membership_user_id ) ){
                            if ( strpos( $key, "usermeta_" ) === 0 ) {
                                $key = str_replace( "usermeta_", "", $key );
                                update_user_meta( $membership_user_id, $key, $value );
                            }
                        }

                    }

                    $found = true;
                }
            }

        }

        // Create subscription
        if ( !$found ) {
            $new_subscription = new PMS_Member_Subscription();

            if( empty( $subscription_data['user_id'] ) )
                $subscription_data['user_id'] = $member->user_id;

            // Insert subscription
            $new_subscription->insert( $subscription_data );

            pms_add_member_subscription_log( $new_subscription->id, 'subscription_import_created', array( 'who' => get_current_user_id() ) );

            // Add subscription meta
            foreach( $membership as $key => $value ) {
                if ( strpos( $key, "subscriptionmeta_" ) === 0 ) {
                    $key = str_replace( "subscriptionmeta_", "", $key );
                    pms_add_member_subscription_meta( $new_subscription->id, $key, $value );
                }
            }
        }
    }

    /*
     * Method to output content in the custom page
     *
     */
    public function output() {

        // Set options
        $this->options = get_option( $this->settings_slug, array() );
        $active_tab = 'pms-import-page';
        include_once 'views/view-page-import.php';

    }

}

$pms_submenu_page_basic_info = new PMS_Submenu_Page_Import( 'paid-member-subscriptions', esc_html__( 'Import Data', 'paid-member-subscriptions' ), esc_html__( 'Import Data', 'paid-member-subscriptions' ), 'manage_options', 'pms-import-page', 9);
$pms_submenu_page_basic_info->init();
