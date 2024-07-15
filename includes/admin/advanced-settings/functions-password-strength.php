<?php

//function to output the password strength checker on frontend forms
function pms_password_strength_checker_html(){
    if( pms_password_strength_should_load_assets() ){
        $pms_misc_settings = get_option('pms_misc_settings');

        if( !empty( $pms_misc_settings['minimum_password_strength'] ) ){
            $password_strength = '<span id="pms-pass-strength-result">'.__('Strength indicator', 'paid-member-subscriptions' ).'</span>
        <input type="hidden" value="" name="pms_password_strength" id="pms_password_strength"/>';
            return $password_strength;
        }
        return '';
    }
    return '';
}

function pms_password_strength_should_load_assets(){

    global $post;

    if( empty( $post->ID ) || empty( $post->post_content ) )
        return false;

    $post_content         = $post->post_content;
    $current_post_id      = $post->ID;
    $pms_general_settings = get_option('pms_general_settings');
    $page_ids             = array();

    if( !empty( $pms_general_settings ) ){

        if( isset( $pms_general_settings ) && $pms_general_settings['register_page'] != -1 )
            $page_ids[] = $pms_general_settings['register_page'];
        
        if( isset( $pms_general_settings ) && $pms_general_settings['account_page'] != -1 )
            $page_ids[] = $pms_general_settings['account_page'];
        
        if( isset( $pms_general_settings ) && $pms_general_settings['lost_password_page'] != -1 )
            $page_ids[] = $pms_general_settings['lost_password_page'];
        
        if( has_shortcode( $post_content, 'pms-register') )
            $page_ids[] = $current_post_id;
        
    }

    if( !empty( $current_post_id )  && in_array( $current_post_id, $page_ids ) )
        return true;
    else
        return false;

}

//function to check password length check
function pms_check_password_length( $password ){
    $pms_misc_settings = get_option('pms_misc_settings');

    if( !empty( $pms_misc_settings['minimum_password_length'] ) ){
        if( strlen( $password ) < $pms_misc_settings['minimum_password_length'] ){
            return true;
        }
        else
            return false;
    }
    return false;
}

//function to check password strength
function pms_check_password_strength(){
    $pms_misc_settings = get_option('pms_misc_settings');

    if( isset( $_POST['pms_password_strength'] ) && !empty( $pms_misc_settings['minimum_password_strength'] ) ){
        $pms_password_strength = sanitize_text_field( $_POST['pms_password_strength'] );
        $password_strength_array = array( 'short' => 0, 'bad' => 1, 'good' => 2, 'strong' => 3 );
        $password_strength_text = array( 'short' => __( 'Very Weak', 'paid-member-subscriptions' ), 'bad' => __( 'Weak', 'paid-member-subscriptions' ), 'good' => __( 'Medium', 'paid-member-subscriptions' ), 'strong' => __( 'Strong', 'paid-member-subscriptions' ) );
        if( $password_strength_array[$pms_password_strength] < $password_strength_array[$pms_misc_settings['minimum_password_strength']] ){
            return $password_strength_text[$pms_misc_settings['minimum_password_strength']];
        }
        else
            return false;
    }
    return false;
}

/* function to output password length requirements text */
function pms_password_length_text(){
    if( pms_password_strength_should_load_assets() ){
        $pms_misc_settings = get_option('pms_misc_settings');

        if( !empty( $pms_misc_settings['minimum_password_length'] ) ){
            return '<p>' . sprintf(__('Minimum length of %d characters.', 'paid-member-subscriptions'), $pms_misc_settings['minimum_password_length']) . '</p>';
        }
        return '';
    }
    return '';
}

/* function to output password strength requirements text */
function pms_password_strength_description() {
    if( pms_password_strength_should_load_assets() ){
        $pms_misc_settings = get_option('pms_misc_settings');

        if( ! empty( $pms_misc_settings['minimum_password_strength'] ) ) {
            $password_strength_text = array( 'short' => __( 'Very Weak', 'paid-member-subscriptions' ), 'bad' => __( 'Weak', 'paid-member-subscriptions' ), 'good' => __( 'Medium', 'paid-member-subscriptions' ), 'strong' => __( 'Strong', 'paid-member-subscriptions' ) );
            $password_strength_description = '<p>' . sprintf( __( 'The password must have a minimum strength of %s', 'paid-member-subscriptions' ), $password_strength_text[$pms_misc_settings['minimum_password_strength']] ) . '</p>';

            return $password_strength_description;
        } else {
            return '';
        }
    }
    return '';
}

/**
 * Include password strength check scripts on frontend where we have shortcodes present
 */
add_action( 'wp_footer', 'pms_enqueue_password_strength_check' );
function pms_enqueue_password_strength_check() {

    if( pms_password_strength_should_load_assets() ){
        $pms_misc_settings = get_option('pms_misc_settings');
        if( !empty( $pms_misc_settings['minimum_password_strength'] ) ){
            wp_enqueue_script( 'password-strength-meter' );
        }
    }
}

add_action( 'wp_footer', 'pms_password_strength_check', 102 );
function pms_password_strength_check(){

    if( pms_password_strength_should_load_assets() ){
        $pms_misc_settings = get_option('pms_misc_settings');

        if( !empty( $pms_misc_settings['minimum_password_strength'] ) ){
            ?>
            <script type="text/javascript">
                function check_pass_strength() {
                    var pass1 = jQuery('#pms_pass1').val(), pass2 = jQuery('#pms_pass2').val(), strength;

                    if( pass1 === undefined ){
                        pass1 = jQuery('#pms_new_password').val();
                    }

                    if( pass2 === undefined ){
                        pass2 = jQuery('#pms_repeat_password').val();
                    }

                    jQuery('#pms-pass-strength-result').removeClass('short bad good strong');
                    if ( ! pass1 ) {
                        jQuery('#pms-pass-strength-result').html( pwsL10n.empty );
                        return;
                    }
                    <?php
                    global $wp_version;

                    if ( version_compare( $wp_version, "4.9.0", ">=" ) ) {
                    ?>
                    strength = wp.passwordStrength.meter( pass1, wp.passwordStrength.userInputDisallowedList(), pass2 );
                    <?php
                    }
                    else {
                    ?>
                    strength = wp.passwordStrength.meter( pass1, wp.passwordStrength.userInputBlacklist(), pass2);
                    <?php
                    }
                    ?>
                    switch ( strength ) {
                        case 2:
                            jQuery('#pms-pass-strength-result').addClass('bad').html( pwsL10n.bad );
                            jQuery('#pms_password_strength').val('bad');
                            break;
                        case 3:
                            jQuery('#pms-pass-strength-result').addClass('good').html( pwsL10n.good );
                            jQuery('#pms_password_strength').val('good');
                            break;
                        case 4:
                            jQuery('#pms-pass-strength-result').addClass('strong').html( pwsL10n.strong );
                            jQuery('#pms_password_strength').val('strong');
                            break;
                        case 5:
                            jQuery('#pms-pass-strength-result').addClass('short').html( pwsL10n.mismatch );
                            jQuery('#pms_password_strength').val('short');
                            break;
                        default:
                            jQuery('#pms-pass-strength-result').addClass('short').html( pwsL10n['short'] );
                            jQuery('#pms_password_strength').val('short');
                    }
                }
                jQuery( document ).ready( function() {
                    // Binding to trigger checkPasswordStrength
                    jQuery('#pms_pass1, #pms_new_password').val('').on( 'keyup', check_pass_strength );
                    jQuery('#pms_pass2, #pms_repeat_password').val('').on( 'keyup', check_pass_strength );
                    jQuery('#pms-pass-strength-result').show();
                });
            </script>
            <?php
        }
    }
}

add_action( 'pms_register_form_pass1_extra_content', 'pms_password_strength_add_extra_html' );
add_action('pms_edit_profile_form_pass1_extra_content', 'pms_password_strength_add_extra_html');
add_action('pms_recover_password_form_pass1_extra_content', 'pms_password_strength_add_extra_html');
function pms_password_strength_add_extra_html(){
    echo pms_password_length_text(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo wp_kses_post( pms_password_strength_description() );
    echo pms_password_strength_checker_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'pms_register_form_validation', 'pms_password_strength_register_form_validations');
add_action('pms_edit_profile_form_validation', 'pms_password_strength_register_form_validations');
function pms_password_strength_register_form_validations(){

    if( !empty($_POST['pass1']) ){
        $pass1 = sanitize_text_field( $_POST['pass1'] );
    }

    if ( isset( $pass1 ) && trim( $pass1 ) != '' ){

        $pms_misc_settings = get_option('pms_misc_settings');

        if( pms_check_password_length( $pass1 ) )
            pms_errors()->add('pass1', sprintf( __( "The password must have the minimum length of %s characters", "paid-member-subscriptions" ), $pms_misc_settings['minimum_password_length'] ));


        if( pms_check_password_strength() ){
            pms_errors()->add('pass1', sprintf( __( "The password must have a minimum strength of %s", "paid-member-subscriptions" ), pms_check_password_strength() ));
        }
    }

}

add_action('pms_recover_password_form_change_password_validation', 'pms_password_strength_recover_password_form_validations');
function pms_password_strength_recover_password_form_validations(){

    if( !empty( $_POST['pms_new_password']) ){
        $new_pass = sanitize_text_field( $_POST['pms_new_password'] );
    }

    if ( isset( $new_pass ) && trim( $new_pass ) != '' ){

        $pms_misc_settings = get_option('pms_misc_settings');

        if( pms_check_password_length( $new_pass ) )
            pms_errors()->add('pms_new_password', sprintf( __( "The password must have the minimum length of %s characters", "paid-member-subscriptions" ), $pms_misc_settings['minimum_password_length'] ));


        if( pms_check_password_strength() ){
            pms_errors()->add('pms_new_password', sprintf( __( "The password must have a minimum strength of %s", "paid-member-subscriptions" ), pms_check_password_strength() ));
        }
    }

}