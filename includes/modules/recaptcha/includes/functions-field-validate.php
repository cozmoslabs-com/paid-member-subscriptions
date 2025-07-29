<?php

/**
 * Validates the field with Google
 *
 * @param string $form_location
 *
 * @return void
 *
 */
function pms_recaptcha_field_validate( $form_location = 'register' ) {

    $settings = pms_recaptcha_get_settings();

    // Exit if this form does not have reCaptcha
    if( empty( $settings['display_form'] ) || ! in_array( $form_location, $settings['display_form'] ) )
        return true;

    $post_data = $_POST;

    $secret_key = $settings['secret_key'];

    $score_threshold = isset( $settings['v3_score_threshold'] ) ? $settings['v3_score_threshold'] : 0.5;

    if ( isset( $settings['v3'] ) && $settings['v3'] === 'yes' ){

        if ( isset( $post_data['g-recaptcha-response'] ) ){
            $recaptcha_response_field = sanitize_textarea_field( $post_data['g-recaptcha-response'] );
        }
        else {
            $recaptcha_response_field = '';
        }

        // Discard empty solution submissions
        if ($recaptcha_response_field == null || strlen($recaptcha_response_field) == 0) {
            if( isset( $_POST['pms_recaptcha_init_error'] ) && wp_verify_nonce( sanitize_text_field( $_POST['pms_recaptcha_init_error'] ), 'pms_recaptcha_init_error' ) )
                return true;

            return false;
        }

        $secret_key = $settings['v3_secret_key'];

    } else {

        // Verify that the user has completed the reCaptcha
        if( !isset( $post_data['g-recaptcha-response'] ) || empty( $post_data['g-recaptcha-response'] ) ) {
            pms_errors()->add( 'recaptcha-' . $form_location, __( 'Please complete the reCaptcha.', 'paid-member-subscriptions' ) );
            return false;
        }
    }

    $already_validated = false;
    $saved             = get_option( 'pms_recaptcha_validations', array() );

    if( isset( $saved[ $post_data['g-recaptcha-response'] ] ) && $saved[ $post_data['g-recaptcha-response'] ] == true ){
        $already_validated = true;

        if( !wp_doing_ajax() ){
            unset( $saved[ $post_data['g-recaptcha-response'] ] );

            update_option( 'pms_recaptcha_validations', $saved, false );
        }

    }

    $has_error = false;

    // Connect to Google to check if the response is valid
    if( !$already_validated ){

        $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify',
            array(
                'timeout' => 15,
                'body' => array(
                    'secret'    => ( !empty( $secret_key ) ? $secret_key : '' ),
                    'response'  => $post_data['g-recaptcha-response'],
                    'remoteip'  => pms_get_user_ip_address()
                )
            )
        );

        if( wp_remote_retrieve_response_code( $response ) === 200 ) {

            $body = json_decode( wp_remote_retrieve_body( $response ), ARRAY_A );

            if( empty( $body['success'] ) && $body['success'] != true )
                $has_error = true;

            if ( array_key_exists( 'score', $body ) && ($body['score'] < $score_threshold) )
                $has_error = true;

        } else
            $has_error = true;

    }

    // Save valid results when they are being triggered from an ajax request
    if( wp_doing_ajax() && isset( $_POST['action'] ) && in_array( $_POST['action'], array( 'pms_validate_checkout', 'pms_process_checkout' ) ) ){

        $saved = get_option( 'pms_recaptcha_validations', array() );

        if( $has_error === false )
            $saved[ $post_data['g-recaptcha-response'] ] = true;

        update_option( 'pms_recaptcha_validations', $saved, false );

    }

    // Add errors if something went wrong
    if( $has_error ){
        $message = esc_html__( 'Could not validate reCAPTCHA. Please complete it again.', 'paid-member-subscriptions' );

        if ( isset( $settings['v3'] ) && $settings['v3'] === 'yes' ){
            $message = esc_html__( 'Could not validate reCAPTCHA. Please try again.', 'paid-member-subscriptions' );
        }

        pms_errors()->add( 'recaptcha-' . $form_location, $message );
    }

    return ! $has_error;

}


/*
 * Validates the reCaptcha on the different form fields form
 *
 */
function pms_recaptcha_field_validate_forms() {

    switch( current_filter() ) {

        case "pms_register_form_validation";
            $form_location = 'register';
            break;

        case "pms_recover_password_form_validation";
            $form_location = 'recover_password';
            break;

        default:
            return;

    }

    pms_recaptcha_field_validate( $form_location );

}
add_action( 'pms_register_form_validation', 'pms_recaptcha_field_validate_forms' );
add_action( 'pms_recover_password_form_validation', 'pms_recaptcha_field_validate_forms' );


/**
 * Validates the reCaptcha on login forms
 * Handles validations for both the default WP and PMS custom login forms
 *
 * @param WP_User|WP_Error $user
 *
 */
function pms_recaptcha_field_validate_form_login( $user ) {

    if( is_wp_error( $user ) )
        return $user;

    if( isset( $_POST['wp-submit'] ) && !isset( $_POST['wppb_login'] ) )
        $login_form_location = 'default_wp_login';

    if( isset( $_POST['pms_login'] ) && $_POST['pms_login'] == 1 )
        $login_form_location = 'login';

    if( isset( $login_form_location ) ) {

        $validated = pms_recaptcha_field_validate( $login_form_location );

        if( ! $validated ) {

            $user = new WP_Error( 'pms-recaptcha-' . $login_form_location, '<strong>' . esc_html__('ERROR:', 'paid-member-subscriptions') . '</strong>' . pms_errors()->get_error_message( 'recaptcha-' . $login_form_location ) );

        }

    }

    return $user;

}
add_filter( 'authenticate', 'pms_recaptcha_field_validate_form_login', 25 );


/**
 * Validates the reCaptcha on the default WP register form
 *
 * @param WP_Error $errors
 *
 */
function pms_recaptcha_field_validate_default_wp_register( $errors ) {

    if( empty( $_POST['wp-submit'] ) )
        return $errors;

    $validated = pms_recaptcha_field_validate( 'default_wp_register' );

    if( ! $validated ) {

        $errors->add( 'recaptcha-default_wp_register', '<strong>' . esc_html__('ERROR:', 'paid-member-subscriptions') . '</strong>' . pms_errors()->get_error_message( 'recaptcha-default_wp_register' ) );

    }

    return $errors;

}
add_filter( 'registration_errors', 'pms_recaptcha_field_validate_default_wp_register' );


/**
 * Validates the reCaptcha on the default WP lost password form
 *
 */
function pms_recaptcha_field_validate_default_wp_recover_password_form(){

    if( empty( $_REQUEST['user_login'] ) )
        return;

    $validated = pms_recaptcha_field_validate( 'default_wp_recover_password' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    if( ! $validated ) {

        wp_die( pms_errors()->get_error_message( 'recaptcha-default_wp_recover_password' ) . '<br />' . esc_html__( "Click the BACK button on your browser, and try again.", 'paid-member-subscriptions' ) ) ;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    }

}
add_action('lostpassword_post','pms_recaptcha_field_validate_default_wp_recover_password_form');
