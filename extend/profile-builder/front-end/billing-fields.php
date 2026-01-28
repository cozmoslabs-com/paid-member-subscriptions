<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get the PMS Billing Fields list
 *
 * @return array
 */
function pms_pb_get_billing_fields() {
    $extra_fields = apply_filters( 'pms_extra_form_fields', array(), 'wppb_register' );

    if ( empty( $extra_fields ) || ! is_array( $extra_fields ) )
        return array();

    foreach ( $extra_fields as $slug => $field_data ) {

        if ( ! isset( $field_data['name'] ) || ! isset( $field_data['label'] ) || ! isset( $field_data['section'] ) || $field_data['section'] !== 'billing_details' )
            unset( $extra_fields[ $slug ] );

    }

    return $extra_fields;
}

/**
 * Handle the PMS Billing Fields output
 *
 * @param $output
 * @param $form_location
 * @param $field
 * @param $user_id
 * @param $field_check_errors
 * @param $request_data
 * @return mixed|null
 */
function pms_pb_billing_fields_handler( $output, $form_location, $field, $user_id, $field_check_errors, $request_data ) {

    if ( $field['field'] !== 'PMS Billing Fields' || ( $form_location !== 'edit_profile' && $form_location !== 'back_end') )
        return $output;

    $billing_fields = pms_pb_get_billing_fields();
    $selected_fields = ! empty( $field['pms-billing-fields'] ) ? apply_filters( 'pms_pb_displayed_billing_fields', explode( ', ', $field['pms-billing-fields'] ), $form_location, $field ) : array();

    // Field title
    $field_title = !empty( $field['field-title'] ) ? '<h4>' . esc_html( $field['field-title'] ) . '</h4>' : '';
    $output .= apply_filters( 'pms_pb_billing_fields_title', $field_title, $form_location );

    foreach ( $billing_fields as $slug => $field_data ) {

        if ( empty( $selected_fields ) || in_array( $slug, $selected_fields ) )
            $output .= pms_pb_get_billing_field_output( $field_data, $user_id );

    }

    return apply_filters( 'wppb_'.$form_location.'_pms_billing_fields', $output, $form_location, $field, $user_id, $field_check_errors, $request_data );
}
add_filter( 'wppb_output_form_field_pms-billing-fields', 'pms_pb_billing_fields_handler', 10, 6 );
add_filter( 'wppb_admin_output_form_field_pms-billing-fields', 'pms_pb_billing_fields_handler', 10, 6 );

/**
 * Get the HTML output for each PMS Billing Fields
 *
 * @param $field
 * @param $user_id
 * @return string
 */
function pms_pb_get_billing_field_output( $field, $user_id ) {
    $output = '';

    // Determine field wrapper element tag
    $field_element_wrapper = ( ! empty( $field['element_wrapper'] ) ? $field['element_wrapper'] : 'div' );

    // Opening element tag of the field
    $output .= '<' . esc_attr( $field_element_wrapper ) . ' class="cozmoslabs-form-field-wrapper pms-field pms-field-type-' . esc_attr( $field['type'] ) . ' ' . ( ! empty( $field['required'] ) ? 'pms-field-required' : '' ) . ' ' . ( ! empty( $field['wrapper_class'] ) ? esc_attr( $field['wrapper_class'] ) : '' ) . '">';

    // Field label
    if( ! empty( $field['label'] ) ) {

        $output .= '<label ' . ( ! empty( $field['name'] ) ? 'for="' . esc_attr( $field['name'] ) . '"' : '' ) . ' class="cozmoslabs-form-field-label">';

        $output .= esc_attr( $field['label'] );

        // Required asterix
        $output .= ( ! empty( $field['required'] ) ? '<span class="pms-field-required-asterix">*</span>' : '' );

        $output .= '</label>';

    }

    $output .= apply_filters( 'pms_pb_billing_field_inner_' . $field['type'], '', $field, $user_id );

    // Field description
    if( ! empty( $field['description'] ) )
        $output .= '<p class="pms-field-description cozmoslabs-description cozmoslabs-description-align-right">' . esc_html( $field['description'] ) . '</p>';

    // Field errors
    if( ! empty( $field['name'] ) ) {

        $errors = pms_errors()->get_error_messages( $field['name'] );

        if( ! empty( $errors ) )
            pms_display_field_errors( $errors );

    }

    // Closing element tag of each section
    $output .= '</' . esc_attr( $field_element_wrapper ) . '>';

    return $output;
}

/**
 * Get the html output for the input type text PMS Billing Fields
 *
 * @param $output
 * @param $field
 * @param $user_id
 * @return mixed|string
 */
function pms_pb_get_billing_field_inner_text( $output, $field, $user_id ) {

    if( $field['type'] !== 'text' || empty( $field['name'] ) )
        return $output;

    // Set value (get the value like this for it to be accessible for both back_end and front_end Edit Profile forms)
    $value = get_user_meta( $user_id, $field['name'], true );

    // Field output
    $output = '<input type="text" id="' . esc_attr( $field['name'] ) . '" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $value ) . '" />';

    return $output;
}
add_filter( 'pms_pb_billing_field_inner_text', 'pms_pb_get_billing_field_inner_text', 10, 3 );

/**
 * Get the html output for the select type PMS Billing Fields
 *
 * @param $output
 * @param $field
 * @param $user_id
 * @return mixed|string
 */
function pms_pb_billing_field_inner_select( $output, $field, $user_id ) {

    if( $field['type'] !== 'select' || empty( $field['name'] ) )
        return $output;

    // Set value (get the value like this for it to be accessible for both back_end and front_end Edit Profile forms)
    $value = get_user_meta( $user_id, $field['name'], true );

    // Field output
    $output  = '<select id="' . esc_attr( $field['name'] ) . '" name="' . esc_attr( $field['name'] ) . '">';

    if( ! empty( $field['options'] ) ) {

        foreach( $field['options'] as $option_value => $option_label )
            $output .= '<option value="' . esc_attr( $option_value ) . '" ' . selected( $value, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';

    }

    $output .= '</select>';

    return $output;
}
add_filter( 'pms_pb_billing_field_inner_select', 'pms_pb_billing_field_inner_select', 10, 3 );

/**
 * Get the html output for the select_state type PMS Billing Fields
 *
 * @param $output
 * @param $field
 * @param $user_id
 * @return mixed|string
 */
function pms_pb_billing_field_inner_select_state( $output, $field, $user_id ) {

    if( $field['type'] !== 'select_state' || empty( $field['name'] ) )
        return $output;

    // Set value (get the value like this for it to be accessible for both back_end and front_end Edit Profile forms)
    $value = get_user_meta( $user_id, $field['name'], true );

    $output  = '<select id="' . esc_attr( $field['name'] ) . '" name="' . esc_attr( $field['name'] ) . '" class="pms-billing-state__select"></select>';
    $output .= '<input type="text" id="' . esc_attr( $field['name'] ) . '" class="pms-billing-state__input" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $value ) . '" />';

    return $output;
}
add_filter( 'pms_pb_billing_field_inner_select_state', 'pms_pb_billing_field_inner_select_state', 10, 3 );

/**
 * Save the PMS Billing Fields data
 *
 * @param $field
 * @param $user_id
 * @param $request_data
 * @param $form_location
 * @return void
 */
function pms_pb_billing_fields_save( $field, $user_id, $request_data, $form_location ) {

    if( $field['field'] !== 'PMS Billing Fields' || ( $form_location !== 'edit_profile' && $form_location !== 'backend-form' ) || empty( $user_id ) || is_wp_error( $user_id ) )
        return;

    $billing_fields = pms_pb_get_billing_fields();

    foreach( $billing_fields as $slug => $field_data ) {

        if( isset( $request_data[$slug] ) )
            update_user_meta( $user_id, $slug, sanitize_text_field( $request_data[$slug] ) );

    }

}
add_action( 'wppb_save_form_field', 'pms_pb_billing_fields_save', 10, 4 );
add_action( 'wppb_backend_save_form_field', 'pms_pb_billing_fields_save', 10, 4 );