<?php

function pms_wppb_get_form( $form_name = '' ){

    if( file_exists( WPPB_PLUGIN_DIR . '/front-end/class-formbuilder.php' ) )
        include_once( WPPB_PLUGIN_DIR . '/front-end/class-formbuilder.php' );

    if( !class_exists( 'Profile_Builder_Form_Creator' ) )
        return false;

    $args = array(
        'form_type'               => 'register',
        'form_name'               => $form_name,
        'form_fields'             => '',
        'role'                    => get_option( 'default_role' ),
        'pms_custom_ajax_request' => true,
    );

    $form = new Profile_Builder_Form_Creator( $args );

    return $form;

}

function pms_wppb_get_autologin_url( $redirect_url, $payment_id = 0 ){
    
    if( is_user_logged_in() || !function_exists( 'wppb_get_admin_approval_option_value' ) ) {
        return $redirect_url;
    }

    $wppb_general_settings = get_option( 'wppb_general_settings' );

    if ( isset( $wppb_general_settings['emailConfirmation'] ) && ( $wppb_general_settings['emailConfirmation'] == 'yes' ) ) {
        return $redirect_url;
    }

    $payment = pms_get_payment( $payment_id );

    if( empty( $payment->user_id ) )
        return $redirect_url;

    $user = get_userdata( $payment->user_id );

    if( !$user )
        return $redirect_url;

    $nonce = wp_create_nonce( 'autologin-'. $user->ID .'-'. (int)( time() / 60 ) );

    if ( wppb_get_admin_approval_option_value() === 'yes' ) {
        if( !empty( $wppb_general_settings['adminApprovalOnUserRole'] ) ) {
            foreach ($user->roles as $role) {
                if ( in_array( $role, $wppb_general_settings['adminApprovalOnUserRole'] ) ) {
                    return $redirect_url;
                }
            }
        }
        else {
            return $redirect_url;
        }
    }

    $redirect_url = add_query_arg( array( 'autologin' => 'true', 'uid' => $user->ID, '_wpnonce' => $nonce ), $redirect_url );

    return $redirect_url;

}