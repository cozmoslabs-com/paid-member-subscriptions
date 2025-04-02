<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Modifies the request form location from the form handler class
 *
 * @param string $location
 * @param array  $request_data
 *
 * @return string
 *
 */
function pms_pb_change_request_form_location( $location, $request_data ) {

    if( ! isset( $request_data['form_name'] ) )
        return $location;

    if( isset( $request_data['action'] ) && $request_data['action'] == 'register' )
        return 'register';
    else
        return $location;

}
add_filter( 'pms_request_form_location', 'pms_pb_change_request_form_location', 10, 2 );


/**
 * Hooks to the Profile Builder subscription plans field to add the extra form fields
 *
 * @param string $output
 *
 * @return string
 *
 */
function pms_pb_add_form_extra_fields( $output = '' , $settings = '', $form_location = '' ) {

    if( $form_location == 'wppb_register' ){
        ob_start();

        // Call the extra form fields adder
        pms_add_form_extra_fields();

        $extra_fields_output = ob_get_contents();
        ob_end_clean();

        $output = $output . $extra_fields_output;
    }

    return $output;

}
add_filter( 'pms_get_output_payment_gateways', 'pms_pb_add_form_extra_fields', 50, 3 );


/**
 * Modify the form name for the extra form fields when coming from a PB form
 *
 * @param string $form_name
 * @param string $hook
 *
 * @return string
 *
 */
function pms_pb_form_extra_fields_form_name( $form_name = '', $hook = '' ) {

    if( $hook == 'wppb_register_subscription_plans_field' )
        $form_name = 'register';

    return $form_name;

}
add_filter( 'pms_form_extra_fields_form_name', 'pms_pb_form_extra_fields_form_name', 10, 2 );


/* remove the Subscription Plans auto generated meta tag in userlisting */
add_filter('wppb_userlisting_merge_tags' , 'pms_remove_subscription_plans_from_auto_generated_merge_tags' );
add_filter('wppb_email_customizer_get_fields' , 'pms_remove_subscription_plans_from_auto_generated_merge_tags' );
function pms_remove_subscription_plans_from_auto_generated_merge_tags( $all_fields ){
    if( !empty( $all_fields ) && is_array( $all_fields ) ){
        foreach ($all_fields as $key => $field ) {
            if( $field['field'] == 'Subscription Plans' ){
                $unset_key = $key;
                break;
            }
        }

        if( !empty( $unset_key ) )
            unset( $all_fields[$unset_key] );
    }

    return $all_fields;
}

/* add the tags we need  */
add_filter( 'wppb_userlisting_get_merge_tags', 'pms_add_tags_in_userlisting_and_ec', 10, 2 );
add_filter( 'wppb_email_customizer_get_merge_tags', 'pms_add_tags_in_userlisting_and_ec', 10, 2 );
function pms_add_tags_in_userlisting_and_ec( $merge_tags, $type = '' ){

    if( $type == 'sort' )
        return $merge_tags;

    /* unescaped because they might contain html */
    $merge_tags[] = array( 'name' => 'subscription_name', 'type' => 'subscription_name', 'unescaped' => true, 'label' => __( 'Subscription Name', 'paid-member-subscriptions' ) );
    $merge_tags[] = array( 'name' => 'subscription_status', 'type' => 'subscription_status', 'unescaped' => true, 'label' => __( 'Subscription Status', 'paid-member-subscriptions' ) );
    $merge_tags[] = array( 'name' => 'subscription_start_date', 'type' => 'subscription_start_date', 'unescaped' => true, 'label' => __( 'Subscription Start Date', 'paid-member-subscriptions' ) );
    $merge_tags[] = array( 'name' => 'subscription_expiration_date', 'type' => 'subscription_expiration_date', 'unescaped' => true, 'label' => __( 'Subscription Expiration Date', 'paid-member-subscriptions' ) );

    return $merge_tags;
}

/* add functionality for Subscription Name tag */
add_filter( 'mustache_variable_subscription_name', 'pms_handle_merge_tag_subscription_name', 10, 4 );
function pms_handle_merge_tag_subscription_name( $value, $name, $children, $extra_info ){
    $user_id = ( ! empty( $extra_info['user_id'] ) ? $extra_info['user_id'] : get_query_var( 'username' ) );
    if( !empty( $user_id ) ){
        $member = pms_get_member( $user_id );
        if( !empty( $member->subscriptions ) ){
            if( count( $member->subscriptions ) == 1 ){
                return get_the_title( $member->subscriptions[0]['subscription_plan_id'] );
            }
            else{
                $subscription_names = '';
                foreach( $member->subscriptions as $subscription_plan ){
                    $subscription_names .= '<div>'. get_the_title( $subscription_plan['subscription_plan_id'] ) .'</div>';
                }
                return $subscription_names;
            }
        }
    }
}

/* add functionality for Subscription Status tag */
add_filter( 'mustache_variable_subscription_status', 'pms_handle_merge_tag_subscription_status', 10, 4 );
function pms_handle_merge_tag_subscription_status( $value, $name, $children, $extra_info ){
    $user_id = ( ! empty( $extra_info['user_id'] ) ? $extra_info['user_id'] : get_query_var( 'username' ) );
    if( !empty( $user_id ) ){
        $member = pms_get_member( $user_id );
        if( !empty( $member->subscriptions ) ){
            if( count( $member->subscriptions ) == 1 ){
                return $member->subscriptions[0]['status'];
            }
            else{
                $subscription_status = '';
                foreach( $member->subscriptions as $subscription_plan ){
                    $subscription_status .= '<div>'. $subscription_plan['status'] .'</div>';
                }
                return $subscription_status;
            }
        }
    }
}


/* add functionality for Subscription Start Date tag */
add_filter( 'mustache_variable_subscription_start_date', 'pms_handle_merge_tag_subscription_start_date', 40, 4 );
function pms_handle_merge_tag_subscription_start_date( $value, $name, $children, $extra_info ){
    $user_id = ( ! empty( $extra_info['user_id'] ) ? $extra_info['user_id'] : get_query_var( 'username' ) );

    if( !empty( $user_id ) ){
        $member = pms_get_member( $user_id );

        if( !empty( $member->subscriptions ) ){

            if( count( $member->subscriptions ) == 1 )
            return apply_filters( 'pms_change_userlisting_expiration_date_format', $member->subscriptions[0]['start_date'] );
            else {
                $subscription_start_date = '';

                foreach( $member->subscriptions as $subscription_plan )
                    $subscription_start_date .= '<div>'. apply_filters( 'pms_change_userlisting_expiration_date_format', date_i18n( get_option('date_format'), strtotime( $subscription_plan['start_date'] ) ) ) .'</div>';

                return $subscription_start_date;
            }
        }
    }
}


/* add functionality for Subscription Expiration Date tag */
add_filter( 'mustache_variable_subscription_expiration_date', 'pms_handle_merge_tag_subscription_expiration_date', 40, 4 );
function pms_handle_merge_tag_subscription_expiration_date( $value, $name, $children, $extra_info ){
    $user_id = ( ! empty( $extra_info['user_id'] ) ? $extra_info['user_id'] : get_query_var( 'username' ) );
    if( !empty( $user_id ) ){
        $member = pms_get_member( $user_id );

        if( !empty( $member->subscriptions ) ){

            if( count( $member->subscriptions ) == 1 ){
                $date = $member->subscriptions[0]['expiration_date'];

                if( empty( $date ) || $date == '0000-00-00 00:00:00' || $date == '0000-00-00' ){
                    if( empty( $member->subscriptions[0]['billing_next_payment'] ) || $member->subscriptions[0]['billing_next_payment'] == '0000-00-00 00:00:00' || $member->subscriptions[0]['billing_next_payment'] == '0000-00-00' )
                        $date = esc_html__( 'Unlimited', 'paid-member-subscriptions' );
                    else
                        $date = $member->subscriptions[0]['billing_next_payment'];
                }

                return apply_filters( 'pms_change_userlisting_expiration_date_format', $date );
            }
            else {
                $subscription_expiration_date = '';
                foreach( $member->subscriptions as $subscription ){
                    $date = $subscription['expiration_date'];

                    if( empty( $date ) || $date == '0000-00-00 00:00:00' || $date == '0000-00-00' ){
                        if( empty( $subscription['billing_next_payment'] ) || $subscription['billing_next_payment']  == '0000-00-00 00:00:00' || $subscription['billing_next_payment'] == '0000-00-00' )
                            $date = esc_html__( 'Unlimited', 'paid-member-subscriptions' );
                        else
                            $date = $subscription['billing_next_payment'];
                    }

                    if( $date == 'Unlimited' )
                        $subscription_expiration_date .= '<div>'. apply_filters( 'pms_change_userlisting_expiration_date_format', $date ) .'</div>';
                    else
                        $subscription_expiration_date .= '<div>'. apply_filters( 'pms_change_userlisting_expiration_date_format', date_i18n( get_option('date_format'), strtotime( $date ) ) ) .'</div>';
                }

                return $subscription_expiration_date;
            }
        }
    }
}

/**
 * Output the payment gateways at the end of the form if a Subscription Plans field is defined
 */
function pms_pb_output_payment_gateways( $content, $form_id, $form_type ){

    // ignore WooCommerce Checkout
    if( function_exists( 'is_checkout' ) && is_checkout() )
        return $content;
        
    if( $form_type != 'register' )
        return $content;

    // don't add payment gateways when PB form is displayed for admins
    if( is_user_logged_in() )
        return $content;

    $found_plans = false;

    if( $form_id == null ){

        $fields = get_option( 'wppb_manage_fields', false );

        if( empty( $fields ) )
            return $content;

        if( is_array( $fields ) ){
            foreach( $fields as $field ){
                if( $field['field'] == 'Subscription Plans' ){
                    $found_plans = true;
                    break;
                }
            }
        }

    } else {

        $fields = get_post_meta( $form_id, 'wppb_rf_fields', true );

        if( empty( $fields ) )
            return $content;

        if( is_array( $fields ) ){
            foreach( $fields as $field ){
                if( strpos( $field['field'], 'Subscription Plans' ) !== false ){
                    $found_plans = true;
                    break;
                }
            }
        }
    }

    if( $found_plans === true )
        $content = pms_get_output_payment_gateways( get_option( 'pms_payments_settings' ), 'wppb_register' ) . '</ul>';

    return $content;

}
add_filter( 'wppb_output_after_last_form_field', 'pms_pb_output_payment_gateways', 99, 3 );

function pms_pb_add_hidden_submit_button_loading_placeholder_text( $content, $form_id, $form_type ){

    if( $form_type != 'register' )
        return $content;

    ob_start();

    // Call the extra form fields adder
    pms_add_hidden_submit_button_loading_placeholder_text();

    $submit_button_loading_placeholder_text = ob_get_contents();
    ob_end_clean();

    $content = $content . $submit_button_loading_placeholder_text;

    return $content;

}
add_filter( 'wppb_output_after_last_form_field', 'pms_pb_add_hidden_submit_button_loading_placeholder_text', 100, 3 );


/**
 * Save user_url (Default - Website field), so it can be exported thorough PMS Export feature if needed
 * Gets triggered when Default PB Edit Profile Form is used for PMS Edit Profile on Account Page
 */
function pms_pb_edit_profile_form_field_save( $field, $user_id, $request_data, $form_location ){
    if( $field['field'] == 'Default - Website' && $form_location == 'edit_profile' )
            update_user_meta( $user_id, 'user_url', $request_data['website'] );
}
add_action( 'wppb_save_form_field', 'pms_pb_edit_profile_form_field_save', 10, 4 );


/**
 * Save user's Website, so it can be exported thorough PMS Export feature if needed
 * Gets triggered when the user's info is updated through WP Dashboard Edit User Form
 */
function pms_pb_admin_user_update_form_field_save( $user_id ) {
    if ( isset( $_POST['url'] ) )
        update_user_meta( $user_id, 'user_url', esc_url_raw( $_POST['url'] ));
}
add_action( 'edit_user_profile_update', 'pms_pb_admin_user_update_form_field_save' );


/**
 * Match the Subscription start and expiration date format with the one set in WordPress General Settings
 */
function pms_change_sub_date_to_wp_format( $date ) {

    if( !empty( $date ) )
        if( $date !== 'Unlimited' )
            $date = date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime( $date ) );

    return $date;

}
add_filter('pms_change_userlisting_expiration_date_format', 'pms_change_sub_date_to_wp_format');

add_action( 'pms_before_processing_wppb_checkout', 'pms_pb_before_processing_wppb_checkout', 10, 2 );
function pms_pb_before_processing_wppb_checkout( $form, $only_validate ){
    
    if( isset( $_POST['pay_gate'] ) && $_POST['pay_gate'] == 'paypal_connect' && isset( $_REQUEST['g-recaptcha-response'] ) && !isset( $_POST['wppb-recaptcha-v3'] ) ){
        
        remove_filter( 'wppb_check_form_field_recaptcha', 'wppb_check_recaptcha_value', 10, 4 );

    }
}