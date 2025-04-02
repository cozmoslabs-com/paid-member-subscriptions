<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/**
 * Remove the Stripe Intents payment gateway from the active gateways list if it's not active already
 */
function pms_stripe_filter_active_payment_gateways( $payment_gateways ){

    $pms_payments_settings        = get_option( 'pms_payments_settings', array() );
    $disabled_base_stripe_gateway = true;

    if( !empty( $pms_payments_settings ) && !empty( $pms_payments_settings['active_pay_gates'] ) ){

        if( in_array( 'stripe_intents', $pms_payments_settings['active_pay_gates'] ) ){

            $disabled_base_stripe_gateway = false;
        }

    }

    if( $disabled_base_stripe_gateway && isset( $payment_gateways['stripe_intents'] ) )
        unset( $payment_gateways['stripe_intents'] );

    return $payment_gateways;

}
add_filter( 'pms_admin_display_payment_gateways', 'pms_stripe_filter_active_payment_gateways', 20, 2 );

/**
 * When Stripe Connect is active and the plugin tries to charge an user through the
 * regular Charges API or Payment Intents API, switch the charge to the Connect implementation
 */
add_filter( 'pms_get_payment_gateway_class_name', 'pms_stripe_connect_filter_payment_gateway', 30, 3 );
function pms_stripe_connect_filter_payment_gateway( $class, $gateway_slug, $payment_data ){

    $active_stripe_gateway = pms_get_active_stripe_gateway();

    if( empty( $active_stripe_gateway ) )
        return $class;
    else if( $active_stripe_gateway == 'stripe_connect' && $gateway_slug == 'stripe' )
        return 'PMS_Payment_Gateway_Stripe_Connect';
    else if( $active_stripe_gateway == 'stripe_connect' && $gateway_slug == 'stripe_intents' )
        return 'PMS_Payment_Gateway_Stripe_Connect';

    return $class;

}

// Update the payment gateway slug in the payment data when processing a regular Stripe payment
// through the Payment Intents API
add_filter( 'pms_cron_process_member_subscriptions_payment_data', 'pms_stripe_filter_member_subscriptions_payment_data', 20, 2 );
function pms_stripe_filter_member_subscriptions_payment_data( $data, $subscription ){
    $active_stripe_gateway = pms_get_active_stripe_gateway();

    if( empty( $active_stripe_gateway ) )
        return $data;
    else if( $active_stripe_gateway == 'stripe_connect' && $subscription->payment_gateway == 'stripe' )
        $data['payment_gateway'] = $active_stripe_gateway;
    else if( $active_stripe_gateway == 'stripe_connect' && $subscription->payment_gateway == 'stripe_intents' )
        $data['payment_gateway'] = $active_stripe_gateway;

    return $data;
}

/**
 * Adds extra system Payment Logs messages
 *
 * @param  string  $message    error message
 * @param  array   $log        array with data about the current error
 */
add_filter( 'pms_payment_logs_system_error_messages', 'pms_stripe_connect_payment_logs_system_error_messages', 20, 2 );
function pms_stripe_connect_payment_logs_system_error_messages( $message, $log ) {

    if ( empty( $log['type'] ) )
        return $message;

    $kses_args = array(
        'strong' => array()
    );

    switch( $log['type'] ) {
        case 'stripe_intent_created':
            $message = __( 'Payment Intent created.', 'paid-member-subscriptions' );
            break;
        case 'stripe_intent_processing':
            $message = __( 'Payment Intent is still processing. Subscription was activated until confirmation of success or failure is received.', 'paid-member-subscriptions' );
            break;
        case 'stripe_intent_attempted_confirmation':
            $message = __( 'Attempting to confirm Payment Intent.', 'paid-member-subscriptions' );
            break;
        case 'stripe_intent_confirmed':
            $message = __( 'Payment Intent confirmed successfully.', 'paid-member-subscriptions' );
            break;
        case 'stripe_intent_failed':
            $message = __( 'Payment Intent has failed.', 'paid-member-subscriptions' );
            break;
        case 'stripe_authentication_sent':
            $message = __( '3D Secure authentication required. An email with the confirmation link was sent to the user.', 'paid-member-subscriptions' );
            break;
        case 'stripe_authentication_succeeded':
            $message = __( '3D Secure authentication is successful.', 'paid-member-subscriptions' );
            break;
        case 'stripe_authentication_failed':
            $message = __( '3D Secure authentication has failed.', 'paid-member-subscriptions' );
            break;
        case 'stripe_authentication_link_not_clicked':
            $message = __( 'The user did not click on the confirmation link that was sent.', 'paid-member-subscriptions' );
            break;
        case 'stripe_returned_for_authentication':
            $message = __( 'User returned to the website for authentication.', 'paid-member-subscriptions' );
            break;
        case 'stripe_webhook_received':
            $message = sprintf( __( 'Stripe webhook received: %1$s. Event ID: %2$s' ,'paid-member-subscriptions' ), '<strong>' . $log['data']['event_type'] . '</strong>', '<strong>' . $log['data']['event_id'] . '</strong>' );
            break;
        case 'stripe_charge_refunded':
            $message = __( 'Payment was refunded in the Stripe Dashboard.', 'paid-member-subscriptions' );
            break;
        default:
            $message = $message;
            break;
    }

    return wp_kses( $message, $kses_args );

}

/**
 * Adds extra system Subscription Logs messages
 *
 * @param  string  $message    error message
 * @param  array   $log        array with data about the current error
 */
add_filter( 'pms_subscription_logs_system_error_messages', 'pms_stripe_connect_add_subscription_log_messages', 20, 2 );
function pms_stripe_connect_add_subscription_log_messages( $message, $log ){

    if( empty( $log ) )
        return $message;

    switch ( $log['type'] ) {
        case 'stripe_webhook_subscription_expired':
            $message = __( 'Subscription expired because the payment was refunded in the Stripe Dashboard.', 'paid-member-subscriptions' );
            break;
        case 'stripe_webhook_setup_intent_failed':
            $message = sprintf( __( 'User attemped to setup a payment method for this subscription but failed. Reason: %s', 'paid-member-subscriptions' ), '<strong>' . $log['data']['message'] . '</strong>' );
            break;
    }

    return $message;

}

/*
 * Add payment types for Stripe
 */
function pms_stripe_payment_types( $types ) {

    $types['stripe_card_one_time']              = __( 'Card - One Time', 'paid-member-subscriptions' );
    $types['stripe_card_subscription_payment']  = __( 'Subscription Recurring Payment', 'paid-member-subscriptions' );

    return $types;

}
add_filter( 'pms_payment_types', 'pms_stripe_payment_types' );

/**
 * Add data-type="extra_fields" attribute to the pay_gate hidden and radio input for Stripe
 *
 */
function pms_stripe_payment_gateway_input_data_type( $value, $payment_gateway ) {

    if( in_array( $payment_gateway, array( 'stripe_connect', 'stripe', 'stripe_intents' ) ) )
        $value = str_replace( '/>', 'data-type="extra_fields" />', $value );

    return $value;

}
add_filter( 'pms_output_payment_gateway_input_radio', 'pms_stripe_payment_gateway_input_data_type', 10, 2 );
add_filter( 'pms_output_payment_gateway_input_hidden', 'pms_stripe_payment_gateway_input_data_type', 10, 2 );

/**
 * Adds error data to the failed payment message, if available.
 *
 * @param  string  $output      Default error message.
 * @param  boolean $is_register Equals to 1 if checkout was initiated from the registration form.
 * @param  int  $payment_id     ID of the payment associated with the error.
 * @return string
 */
function pms_stripe_error_message( $output, $is_register, $payment_id ) {

    if ( empty( $payment_id ) )
        return $output;

    $payment = new PMS_Payment( $payment_id );

    if ( isset( $payment->payment_gateway ) && !in_array( $payment->payment_gateway, array( 'stripe_connect', 'stripe', 'stripe_intents' ) ) )
        return $output;

    if ( empty( $payment->id ) || empty( $payment->logs ) )
        return $output;

    $log_entry = '';

    foreach( array_reverse( $payment->logs ) as $log ) {
        if ( !empty( $log['type'] ) && $log['type'] == 'payment_failed' ) {
            $log_entry = $log;
            break;
        }
    }

    if ( empty( $log_entry ) )
        return $output;

    $stripe_errors = pms_stripe_add_error_codes();

    if ( !empty( $stripe_errors[ $log_entry['error_code'] ] ) )
        $displayed_error = $stripe_errors[ $log_entry['error_code'] ];
    else if ( !empty( $log_entry['data']['message'] ) )
        $displayed_error = $log['data']['message'];
    else
        $displayed_error = __( 'Payment could not be processed.', 'paid-member-subscriptions' );

    ob_start(); ?>

    <div class="pms-payment-error">
        <p>
            <?php esc_html_e( 'The payment gateway is reporting the following error:', 'paid-member-subscriptions' ); ?>
            <span class="pms-payment-error__message"><?php echo esc_html( $displayed_error ); ?></span>
        </p>
        <p>
            <?php
                if( isset( $_GET['pms_stripe_authentication'] ) && $_GET['pms_stripe_authentication'] == 1 ){

                    if( is_user_logged_in() ){
                        $message = __( 'Please try again.', 'paid-member-subscriptions' );

                        if ( pms_get_page( 'account' ) != false && $payment_id != 0 ){
                            $payment = pms_get_payment( $payment_id );
                            $message = sprintf( $message, '<a href="'. pms_get_retry_url( $payment->subscription_id ) .'">', '</a>' );
                        }
                        else
                            $message = sprintf( $message, '', '' );

                        echo wp_kses_post( $message );
                    }
                    else {
                        $message = __( 'Please %slog in%s and try again.', 'paid-member-subscriptions' );

                        if ( $account_page = esc_url( pms_get_page( 'account', true ) ) )
                            $message = sprintf( $message, '<a href="'. $account_page .'">', '</a>' );
                        else
                            $message = sprintf( $message, '', '' );

                        echo wp_kses_post( $message );
                    }

                } else
                    echo pms_payment_error_message_retry( $is_register, $payment_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
        </p>
    </div>

    <?php

    $output = ob_get_contents();

    ob_end_clean();

    return $output;
}
add_filter( 'pms_payment_error_message', 'pms_stripe_error_message', 20, 3 );

/**
 * Localized array with Stripe error messages that are shown to the user
 *
 * @param array  $error_codes
 */
function pms_stripe_add_error_codes() {

    return array(
        'card_not_supported'              => __( 'The card does not support this type of purchase.', 'paid-member-subscriptions' ),
        'card_velocity_exceeded'          => __( 'The customer has exceeded the balance or credit limit available on their card.', 'paid-member-subscriptions' ),
        'currency_not_supported'          => __( 'The card does not support the specified currency.', 'paid-member-subscriptions' ),
        'duplicate_transaction'           => __( 'A transaction with identical amount and credit card information was submitted very recently.', 'paid-member-subscriptions' ),
        'expired_card'                    => __( 'The card has expired.', 'paid-member-subscriptions' ),
        'fraudulent'                      => __( 'The payment has been declined as Stripe suspects it is fraudulent.', 'paid-member-subscriptions' ),
        'generic_decline'                 => __( 'The card has been declined for an unknown reason.', 'paid-member-subscriptions' ),
        'card_declined'                   => __( 'The card has been declined for an unknown reason.', 'paid-member-subscriptions' ),
        'incorrect_number'                => __( 'The card number is incorrect.', 'paid-member-subscriptions' ),
        'incorrect_cvc'                   => __( 'The CVC number is incorrect.', 'paid-member-subscriptions' ),
        'incorrect_pin'                   => __( 'The PIN entered is incorrect', 'paid-member-subscriptions' ),
        'incorrect_zip'                   => __( 'The ZIP/postal code is incorrect.', 'paid-member-subscriptions' ),
        'insufficient_funds'              => __( 'The card has insufficient funds to complete the purchase.', 'paid-member-subscriptions' ),
        'invalid_account'                 => __( 'The card, or account the card is connected to, is invalid.', 'paid-member-subscriptions' ),
        'invalid_amount'                  => __( 'The payment amount is invalid, or exceeds the amount that is allowed.', 'paid-member-subscriptions' ),
        'invalid_cvc'                     => __( 'The CVC number is incorrect.', 'paid-member-subscriptions' ),
        'invalid_expiry_year'             => __( 'The expiration year invalid.', 'paid-member-subscriptions' ),
        'invalid_number'                  => __( 'The card number is incorrect.', 'paid-member-subscriptions' ),
        'invalid_pin'                     => __( 'The PIN entered is incorrect', 'paid-member-subscriptions' ),
        'issuer_not_available'            => __( 'The card issuer could not be reached, so the payment could not be authorized.', 'paid-member-subscriptions' ),
        'lost_card'                       => __( 'The payment has been declined because the card is reported lost.', 'paid-member-subscriptions' ),
        'merchant_blacklist'              => __( 'The payment has been declined because it matches a value on the Stripe user\'s blocklist.', 'paid-member-subscriptions' ),
        'not_permitted'                   => __( 'The payment is not permitted.', 'paid-member-subscriptions' ),
        'processing_error'                => __( 'An error occurred while processing the card.', 'paid-member-subscriptions' ),
        'reenter_transaction'             => __( 'The payment could not be processed by the issuer for an unknown reason.', 'paid-member-subscriptions' ),
        'restricted_card'                 => __( 'The card cannot be used to make this payment (it is possible it has been reported lost or stolen).', 'paid-member-subscriptions' ),
        'stolen_card'                     => __( 'The payment has been declined because the card is reported stolen.', 'paid-member-subscriptions' ),
        'testmode_decline'                => __( 'A Stripe test card number was used.', 'paid-member-subscriptions' ),
        'withdrawal_count_limit_exceeded' => __( 'The customer has exceeded the balance or credit limit available on their card. ', 'paid-member-subscriptions' ),
    );

}

add_filter( 'wppb_form_class', 'pms_stripe_add_class_to_edit_profile_form', 20, 2 );
function pms_stripe_add_class_to_edit_profile_form( $classes, $form ){

    $form = (array)$form;

    if( !isset( $form['args'] ) || !isset( $form['args']['form_type'] ) || $form['args']['form_type'] != 'edit_profile' )
        return $classes;

    if( !isset( $form['args']['form_fields'] ) )
        return $classes;

    $found_subscription_plans = false;

    foreach( $form['args']['form_fields'] as $field ){
        if( $field['field'] == 'Subscription Plans' ){
            $found_subscription_plans = true;
            break;
        }
    }

    if( $found_subscription_plans )
        $classes .= ' pms-form';

    return $classes;

}

add_filter( 'pms_payment_logs_modal_header_content', 'pms_stripe_payment_logs_modal_header_content', 20, 3 );
function pms_stripe_payment_logs_modal_header_content( $content, $log, $payment_id ) {
    if ( empty( $payment_id ) || ( isset( $log['type'] ) && $log['type'] != 'payment_failed' ) )
        return $content;

    $payment = pms_get_payment( $payment_id );

    if ( empty( $payment->id ) || !in_array( $payment->payment_gateway, array( 'stripe_connect', 'stripe', 'stripe_intents' ) ) )
        return $content;

    ob_start(); ?>

        <h2><?php esc_html_e( 'Payment Gateway Message', 'paid-member-subscriptions' ); ?></h2>

        <p>
            <strong><?php esc_html_e( 'Error code:', 'paid-member-subscriptions' ); ?> </strong>
            <?php echo esc_html( $log['error_code'] ); ?>
        </p>

        <p>
            <strong><?php esc_html_e( 'Message:', 'paid-member-subscriptions' ); ?> </strong>
            <?php echo esc_html( $log['data']['message'] ); ?>
        </p>

        <p>
            <strong><?php esc_html_e( 'More info:', 'paid-member-subscriptions' ); ?> </strong>
            <?php if ( !empty( $log['data']['data']['doc_url'] ) ) : ?>
                <a href="<?php echo esc_url( $log['data']['data']['doc_url'] ); ?>" target="_blank"><?php echo esc_html( $log['data']['data']['doc_url'] ); ?></a>
            <?php else : ?>
                <a href="https://stripe.com/docs/error-codes" target="_blank">https://stripe.com/docs/error-codes</a>
            <?php endif; ?>
        </p>

    <?php
    $output = ob_get_clean();

    return $output;
}

add_action( 'plugins_loaded', 'pms_stripe_add_deprecation_notice' );
function pms_stripe_add_deprecation_notice() {

    if( pms_get_active_stripe_gateway() != 'stripe_intents' )
        return;

    $message = sprintf( __( '<strong>Action Required!</strong><br><br> The Stripe version you are using right now is deprecated. In order to benefit from the latest security updates please <strong>migrate to the Stripe Connect gateway</strong> as soon as possible. <br>Starting with <strong>June 2025</strong>, Stripe will <strong>charge you additional fees</strong> if you don\'t migrate. <br><br>Go to the %sSettings -> Payments -> Gateways%s page, enable the Stripe gateway and connect your account. %sMigration instructions%s', 'paid-member-subscriptions' ), '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/payment-gateways/stripe-connect/#Migration_from_other_Stripe_gateways_to_Stripe_Connect" target="_blank">', '</a>', '<a href="'. admin_url( 'admin.php?page=pms-settings-page&tab=payments' ) .'" target="_blank">', '</a>' );

    if( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'pms-settings-page' ) {

        new PMS_Add_General_Notices( 'pms_stripe_deprecation_notice',
        '<p>' . $message . '</p>',
        'notice-error');

    } else {

        new PMS_Add_General_Notices( 'pms_stripe_deprecation_notice',
        sprintf( '<p>' . $message . '<br>' . __( ' %1$sDismiss%2$s', 'paid-member-subscriptions'), "<a href='" . esc_url( wp_nonce_url( add_query_arg( 'pms_stripe_deprecation_notice_dismiss_notification', '0' ), 'pms_general_notice_dismiss' ) ) . "' type='button' class='notice-dismiss'><span class='screen-reader-text'>", "</span></a>" ) . '</p>',
        'notice-error');

    }
}

// WooCommerce Stripe compatibility with autologin
add_action( 'pms_register_form_after_create_user', 'pms_stripe_woocommerce_stripe_compatibility', 9 );
function pms_stripe_woocommerce_stripe_compatibility(){
    if( class_exists( 'WC_Stripe' ) ){
        $instance           = WC_Stripe::get_instance();
        $woo_stripe_gateway = $instance->get_main_stripe_gateway();

        remove_action( 'set_logged_in_cookie', [ $woo_stripe_gateway, 'set_cookie_on_current_request' ] );
    }
}
