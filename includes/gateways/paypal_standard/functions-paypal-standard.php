<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Function that adds the HTML for PayPal Standard in the payments tab from the Settings page
 *
 * @param array $options    - The saved option settings
 *
 */
function pms_add_settings_content_paypal_standard( $options ) {

    if( !in_array( 'paypal_standard', $options['active_pay_gates'] ) && !in_array( 'paypal_express', $options['active_pay_gates'] ) )
        return;

    ?>

    <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-paypal-configs">

        <h4 class="cozmoslabs-subsection-title">
            <?php echo esc_html( apply_filters( 'pms_settings_page_payment_gateway_paypal_title', esc_html__( 'Paypal Standard', 'paid-member-subscriptions' ) ) ); ?>

            <?php if( in_array( 'paypal_standard', $options['active_pay_gates'] ) ) : ?>
                <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/payment-gateways/paypal-standard/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs#Entering_your_PayPal_API_Credentials" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
            <?php elseif( in_array( 'paypal_express', $options['active_pay_gates'] ) ) : ?>
                <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/paypal-pro-and-express-checkout/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs#Entering_your_PayPal_API_Credentials" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
            <?php endif; ?>
        </h4>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="paypal-standard-email"><?php esc_html_e( 'PayPal E-mail Address', 'paid-member-subscriptions' ); ?></label>
            <input id="paypal-standard-email" type="text" name="pms_payments_settings[gateways][paypal_standard][email_address]" value="<?php echo isset( $options['gateways']['paypal_standard']['email_address' ]) ? esc_attr( $options['gateways']['paypal_standard']['email_address'] ) : ''; ?>" class="widefat" />

            <input type="hidden" name="pms_payments_settings[gateways][paypal_standard][name]" value="PayPal" />

            <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Enter your PayPal e-mail address', 'paid-member-subscriptions' ); ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="paypal-standard-test-email"><?php esc_html_e( 'Test PayPal E-mail Address', 'paid-member-subscriptions' ); ?></label>
            <input id="paypal-standard-test-email" type="text" name="pms_payments_settings[gateways][paypal_standard][test_email_address]" value="<?php echo isset( $options['gateways']['paypal_standard']['test_email_address' ]) ? esc_attr( $options['gateways']['paypal_standard']['test_email_address'] ) : ''; ?>" class="widefat" />

            <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'PayPal E-mail address to use for test transactions', 'paid-member-subscriptions' ); ?></p>
        </div>

        <?php do_action( 'pms_settings_page_payment_gateway_paypal_extra_fields', $options ); ?>

        <!-- IPN Message -->

        <?php if( in_array( 'paypal_standard', $options['active_pay_gates'] ) ) : ?>
            <?php
                $paypal_ipn_url = esc_url( add_query_arg( 'pay_gate_listener', 'paypal_ipn', trailingslashit( home_url() ) ) );
                $paypal_link_doc = '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/payment-gateways/paypal-standard/#Setting_up_Instant_Payment_Notifications_IPN" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>';
            ?>
        <?php elseif( in_array( 'paypal_express', $options['active_pay_gates'] ) ) : ?>
            <?php
                $paypal_ipn_url = esc_url( add_query_arg( 'pay_gate_listener', 'paypal_epipn', trailingslashit( home_url() ) ) );
                $paypal_link_doc = '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/paypal-pro-and-express-checkout/#Setting_up_Instant_Payment_Notifications_IPN" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>';
            ?>
        <?php else: ?>
            <?php $paypal_ipn_url = ""; ?>
        <?php endif; ?>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="paypal-ipn-url"><?php echo esc_html__( 'Use the following URL for the IPN:', 'paid-member-subscriptions' ); ?></label>
            <input id="paypal-ipn-url" type="text" name="paypal-ipn-url" value="<?php echo esc_url( $paypal_ipn_url ); ?>" class="widefat" disabled />
            <a class="paypal-connect__copy button-secondary" data-id="paypal-ipn-url" href="" style="margin-left: 4px;">Copy</a>


            <p class="pms-ipn-notice cozmoslabs-description cozmoslabs-description-space-left">
                <?php printf( wp_kses_post( __( 'In order for <strong>PayPal payments to work correctly</strong>, you need to setup the IPN Url in your PayPal account. %s', 'paid-member-subscriptions' ) ), wp_kses_post( $paypal_link_doc ) ); ?>
            </p>
        </div>

    </div>

    <?php
}
add_action( 'pms-settings-page_payment_gateways_content', 'pms_add_settings_content_paypal_standard' );

/*
 * Display a warning to administrators if the PayPal Email is not entered in settings
 *
 */
function pms_paypal_email_address_admin_warning() {

    if( !current_user_can( 'manage_options' ) )
        return;

    $are_active = array_intersect( array( 'paypal_standard', 'paypal_express', 'paypal_pro' ), pms_get_active_payment_gateways() );

    if( !empty( $are_active ) && pms_get_paypal_email() === false ) {

        echo '<div class="pms-warning-message-wrapper">';
            echo '<p>' . wp_kses_post( sprintf( __( 'Your <strong>PayPal Email Address</strong> is missing. In order to make payments you will need to add the Email Address of your PayPal account %1$s here %2$s.', 'paid-member-subscriptions' ), '<a href="' . esc_url( admin_url( 'admin.php?page=pms-settings-page&tab=payments' ) ) .'" target="_blank">', '</a>' ) ) . '</p>';
            echo '<p><em>' . esc_html__( 'This message is visible only by Administrators.', 'paid-member-subscriptions' ) . '</em></p>';
        echo '</div>';

    }

}
add_action( 'pms_register_form_top', 'pms_paypal_email_address_admin_warning' );
add_action( 'pms_new_subscription_form_top', 'pms_paypal_email_address_admin_warning' );
add_action( 'pms_upgrade_subscription_form_top', 'pms_paypal_email_address_admin_warning' );
add_action( 'pms_renew_subscription_form_top', 'pms_paypal_email_address_admin_warning' );
add_action( 'pms_retry_payment_form_top', 'pms_paypal_email_address_admin_warning' );

function pms_wppb_paypal_email_address_admin_warning() {

    if( !current_user_can( 'manage_options' ) )
        return;

    $fields = get_option( 'wppb_manage_fields' );

    if ( empty( $fields ) )
        return;

    $are_active = array_intersect( array( 'paypal_standard', 'paypal_express', 'paypal_pro' ), pms_get_active_payment_gateways() );

    foreach( $fields as $field ) {
        if ( $field['field'] == 'Subscription Plans' && !empty( $are_active ) && pms_get_paypal_email() === false ) {
            echo '<div class="pms-warning-message-wrapper">';
                echo '<p>' . wp_kses_post( sprintf( __( 'Your <strong>PayPal Email Address</strong> is missing. In order to make payments you will need to add the Email Address of your PayPal account %1$s here %2$s.', 'paid-member-subscriptions' ), '<a href="' . admin_url( 'admin.php?page=pms-settings-page&tab=payments' ) .'" target="_blank">', '</a>' ) ) . '</p>';
                echo '<p><em>' . esc_html__( 'This message is visible only by Administrators.', 'paid-member-subscriptions' ) . '</em></p>';
            echo '</div>';

            break;
        }
    }

}
add_action( 'wppb_before_register_fields', 'pms_wppb_paypal_email_address_admin_warning' );

/**
 * Returns the PayPal Email Address
 *
 * @since 1.8.5
 */
function pms_get_paypal_email() {
    $settings = get_option( 'pms_payments_settings' );

    $slug = 'email_address';

    if( isset( $settings['test_mode'] ) && $settings['test_mode'] == '1' )
        $slug = 'test_email_address';

    if ( !empty( $settings['gateways']['paypal_standard'][$slug] ) )
        return $settings['gateways']['paypal_standard'][$slug];

    return false;
}

/**
 * Add custom log messages for the PayPal Standard gateway
 *
 */
function pms_paypal_payment_logs_system_error_messages( $message, $log ) {

    if ( empty( $log['type'] ) )
        return $message;

    $kses_args = array(
        'strong' => array()
    );

    switch( $log['type'] ) {
        case 'paypal_to_checkout':
            $message = __( 'User sent to <strong>PayPal Checkout</strong> to continue the payment process.', 'paid-member-subscriptions' );
            break;
        case 'paypal_ipn_waiting':
            $message = __( 'Waiting to receive Instant Payment Notification (IPN) from <strong>PayPal</strong>.', 'paid-member-subscriptions' );
            break;
        case 'paypal_ipn_received':
            $message = __( 'Instant Payment Notification (IPN) received from PayPal.', 'paid-member-subscriptions' );
            break;
        case 'paypal_ipn_not_received':
            $message = __( 'Instant Payment Notification (IPN) not received from PayPal.', 'paid-member-subscriptions' );
            break;
    }

    return apply_filters( 'pms_paypal_payment_logs_system_error_messages', wp_kses_post( $message, $kses_args ), $log );

}
add_filter( 'pms_payment_logs_system_error_messages', 'pms_paypal_payment_logs_system_error_messages', 10, 2 );

/**
 * Returns an array with the API username, API password and API signature of the PayPal business account
 * if they all exist, if not will return false
 *
 * @return mixed array or bool false
 *
 */
function pms_get_paypal_api_credentials() {

    if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '1.7.8' ) == -1 ) {
        $pms_settings = get_option( 'pms_settings' );

        if ( !isset( $pms_settings['payments']['gateways']['paypal'] ) )
            return false;

        $pms_settings = $pms_settings['payments']['gateways']['paypal'];
    } else {
        $pms_settings = get_option( 'pms_payments_settings' );

        if ( !isset( $pms_settings['gateways']['paypal'] ) )
            return false;

        $pms_settings = $pms_settings['gateways']['paypal'];
    }

    if ( empty( $pms_settings ) )
        return false;

    if( pms_is_payment_test_mode() )
        $sandbox_prefix = 'test_';
    else
        $sandbox_prefix = '';

    $api_credentials = array(
        'username'  => $pms_settings[$sandbox_prefix . 'api_username'],
        'password'  => $pms_settings[$sandbox_prefix . 'api_password'],
        'signature' => $pms_settings[$sandbox_prefix . 'api_signature']
    );

    $api_credentials = array_map( 'trim', $api_credentials );

    if( count( array_filter($api_credentials) ) == count($api_credentials) )
        return $api_credentials;
    else
        return false;

}

/*
 * Display a warning to the administrators if the API credentials are missing in the
 * register page
 *
 */
function pms_paypal_api_credentials_admin_warning() {

    if( !current_user_can( 'manage_options' ) )
        return;

    $are_active = array_intersect( array( 'paypal_express', 'paypal_pro' ), pms_get_active_payment_gateways() );

    if( pms_get_paypal_api_credentials() == false && !empty( $are_active ) ) {

        echo '<div class="pms-warning-message-wrapper">';
        echo '<strong>' . esc_html__( 'Error', 'paid-member-subscriptions' ) . '</strong>';
            echo '<p>' . sprintf( esc_html__( 'Your %3$s PayPal API credentials %4$s are missing. In order to make payments you will need to add your API credentials %1$s here %2$s.', 'paid-member-subscriptions' ), '<a href="' . esc_url( admin_url( 'admin.php?page=pms-settings-page&tab=payments' ) ) .'" target="_blank">', '</a>', '<strong>', '</strong>' ) . '</p>';
            echo '<p><em>' . esc_html__( 'This message is visible only by Administrators.', 'paid-member-subscriptions' ) . '</em></p>';
        echo '</div>';

    }

}
add_action( 'pms_register_form_top', 'pms_paypal_api_credentials_admin_warning' );
add_action( 'pms_new_subscription_form_top', 'pms_paypal_api_credentials_admin_warning' );
add_action( 'pms_upgrade_subscription_form_top', 'pms_paypal_api_credentials_admin_warning' );
add_action( 'pms_renew_subscription_form_top', 'pms_paypal_api_credentials_admin_warning' );
add_action( 'pms_retry_payment_form_top', 'pms_paypal_api_credentials_admin_warning' );

function pms_wppb_paypal_api_credentials_admin_warning() {

    if( !current_user_can( 'manage_options' ) )
        return;

    $fields = get_option( 'wppb_manage_fields' );

    if ( empty( $fields ) )
        return;

    $are_active = array_intersect( array( 'paypal_express', 'paypal_pro' ), pms_get_active_payment_gateways() );

    foreach( $fields as $field ) {
        if ( $field['field'] == 'Subscription Plans' && !empty( $are_active ) && pms_get_paypal_api_credentials() === false ) {
            echo '<div class="pms-warning-message-wrapper">';
                echo '<p>' . sprintf( esc_html__( 'Your <strong>PayPal API credentials</strong> are missing. In order to make payments you will need to add your API credentials %1$s here %2$s.', 'paid-member-subscriptions' ), '<a href="' . esc_url( admin_url( 'admin.php?page=pms-settings-page&tab=payments' ) ) .'" target="_blank">', '</a>' ) . '</p>';
                echo '<p><em>' . esc_html__( 'This message is visible only by Administrators.', 'paid-member-subscriptions' ) . '</em></p>';
            echo '</div>';

            break;
        }
    }

}
add_action( 'wppb_before_register_fields', 'pms_wppb_paypal_api_credentials_admin_warning' );

/*
 * Checks to see if the payment profile id provided is one supported by
 * PayPal
 *
 * @param string $payment_profile_id
 *
 * @return bool
 *
 */
function pms_is_paypal_payment_profile_id( $payment_profile_id = '' ) {

    if( empty( $payment_profile_id ) )
        return false;

    if( strpos( $payment_profile_id, 'I-' ) !== false )
        return true;
    else
        return false;

}