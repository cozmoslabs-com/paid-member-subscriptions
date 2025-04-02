<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/**
 * Add data-type="extra_fields" attribute to the pay_gate hidden and radio input for PayPal Connect
 *
 */
function pms_ppcp_payment_gateway_input_data_type( $value, $payment_gateway ) {

    if( in_array( $payment_gateway, array( 'paypal_connect' ) ) )
        $value = str_replace( '/>', 'data-type="extra_fields" />', $value );

    return $value;

}
add_filter( 'pms_output_payment_gateway_input_radio', 'pms_ppcp_payment_gateway_input_data_type', 10, 2 );
add_filter( 'pms_output_payment_gateway_input_hidden', 'pms_ppcp_payment_gateway_input_data_type', 10, 2 );

/**
 * Adds extra system Payment Logs messages
 *
 * @param  string  $message    error message
 * @param  array   $log        array with data about the current error
 */
add_filter( 'pms_payment_logs_system_error_messages', 'pms_paypal_connect_payment_logs_system_error_messages', 20, 2 );
function pms_paypal_connect_payment_logs_system_error_messages( $message, $log ){

    if ( empty( $log['type'] ) )
        return $message;

    $kses_args = array(
        'strong' => array()
    );

    switch ( $log['type'] ) {
        case 'paypal_psp_order_created':
            $message = __( 'PayPal order created.', 'paid-member-subscriptions' );
            break;
        case 'paypal_psp_order_completed':
            $message = __( 'PayPal order completed.', 'paid-member-subscriptions' );
            break;
        case 'paypal_psp_order_failed':
            $message = __( 'PayPal order failed.', 'paid-member-subscriptions' );
            break;
        case 'paypal_order_creation_failed':
            $message = sprintf( __( 'PayPal order creation failed. Error code: <strong>%s</strong>', 'paid-member-subscriptions' ), ( !empty( $log['data']['data']['error_code'] ) ? $log['data']['data']['error_code'] : '-' ) );
            break;
        case 'paypal_order_capture_failed':
            $message = sprintf( __( 'PayPal order capture failed. Error code: <strong>%s</strong>', 'paid-member-subscriptions' ), ( !empty( $log['data']['data']['error_code'] ) ? $log['data']['data']['error_code'] : '-' ) );
            break;
        case 'paypal_order_capture_pending_payment':
            $message = __( 'PayPal order capture resulted in a pending payment. The payment will finish processing after review from the payment provider.', 'paid-member-subscriptions' );
            break;
        case 'paypal_order_capture_completed':
            $message = __( 'PayPal capture completed.', 'paid-member-subscriptions' );
            break;
        case 'paypal_webhook_received': 
            $message = sprintf( __( 'PayPal webhook received: %1$s. Event ID: %2$s' ,'paid-member-subscriptions' ), '<strong>' . $log['data']['event_type'] . '</strong>', '<strong>' . $log['data']['event_id'] . '</strong>' );
            break;
        case 'paypal_transaction_refunded':
            $message = __( 'Payment was refunded in the PayPal Dashboard.', 'paid-member-subscriptions' );
            break;
        default:
            $message = $message;
            break;
    }

    return wp_kses( $message, $kses_args );

}