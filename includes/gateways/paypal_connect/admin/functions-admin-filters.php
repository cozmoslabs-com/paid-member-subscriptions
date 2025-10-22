<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/**
 * Function that adds the HTML for Stripe in the payments tab from the Settings page
 *
 * @param array $options    - The saved option settings
 *
 */
function pms_ppcp_add_settings_content( $options ){
    $paypal_credentials = pms_ppcp_get_api_credentials();

	if( ( !empty( $options['active_pay_gates'] ) && in_array( 'paypal_connect', $options['active_pay_gates'] ) ) || empty( $paypal_credentials['payer_id'] ) ) :

		echo '<div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-paypal-connect-configs"'. ( !in_array( 'paypal_connect', $options['active_pay_gates'] ) && empty( $paypal_credentials['payer_id'] ) ? 'style="display:none"' : '' ) .'>';

            echo '<div class="cozmoslabs-subsection-title-container">';
                echo '<img class="cozmoslabs-payment-gateway__metabox-icon" src="' . esc_attr( PMS_PLUGIN_DIR_URL ) . 'includes/gateways/paypal_connect/assets/img/paypal-icon.png"  alt="PayPal" />';
                echo '<h4 class="cozmoslabs-subsection-title" id="pms-paypal__gateway-settings">'
                        . esc_html__( ' PayPal', 'paid-member-subscriptions' ) .
                        '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/payment-gateways/paypal/?utm_source=pms-payments-settings&utm_medium=client-site&utm_campaign=pms-paypal-docs#Initial_Setup" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
                    </h4>';
            echo '</div>';

			if( in_array( 'paypal_connect', $options['active_pay_gates'] ) || empty( $paypal_credentials['payer_id'] ) ) :

				// Display link to connect PayPal Account or Connection Information
                echo '<div class="pms-paypal-connect__gateway-settings">';

                    if( empty( $paypal_credentials['client_id'] ) || empty( $paypal_credentials['client_secret'] ) ) :
                        $paypal_url = pms_ppcp_get_paypal_connect_url();
                        $ajax_nonce = wp_create_nonce( 'pms_paypal_connect_onboarding_nonce' );

                        echo '<a id="pms-paypal-connect-onboarding" class="cozmoslabs-paypal-connect__button" target="_blank" data-paypal-onboard-complete="pms_ppcp_connect_callback" href="'. esc_url( $paypal_url ) .'" data-paypal-button="true" data-ajax-nonce="'. esc_attr( $ajax_nonce ) .'"><img src="' . esc_attr( PMS_PLUGIN_DIR_URL ) . 'includes/gateways/paypal_connect/assets/img/paypal-connect.png" /></a>';
                        echo '<p class="cozmoslabs-description">'
                            . esc_html__( 'Connect your existing PayPal account or create a new one to start accepting payments. Press the button above to start.', 'paid-member-subscriptions' ) .
                            '<br>'
                            . esc_html__( 'You will be redirected back here once the process is completed.', 'paid-member-subscriptions' ) .
                            '<p>';

                    else :

                        $merchant_status      = pms_ppcp_get_merchant_status();
                        $vaulting_status      = pms_ppcp_vaulting_status( $merchant_status );
                        $is_payment_test_mode = pms_is_payment_test_mode();
                        $disconnect_nonce     = wp_create_nonce( 'pms_paypal_connect_disconnect_nonce' );

                        // connection status
                        echo '<div class="cozmoslabs-form-field-wrapper">';

                            echo '<label class="cozmoslabs-form-field-label">' . esc_html__( 'Connection Status', 'paid-member-subscriptions' ) . '</label>';

                            echo '<span class="'. ( $is_payment_test_mode ? 'cozmoslabs-paypal-connect__settings-warning' : 'cozmoslabs-paypal-connect__settings-success' ) .'">'. esc_html__( 'Success', 'paid-member-subscriptions' ) .'</span>';

                            if( $is_payment_test_mode )
                                echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . sprintf( esc_html__( 'Your account is connected successfully in %s mode.', 'paid-member-subscriptions' ), '<span class="cozmoslabs-paypal-connect__connection--test">TEST</span>' ) . '</p>';
                            else
                                echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . sprintf( esc_html__( 'Your account is connected successfully in %s mode.', 'paid-member-subscriptions' ), '<span class="cozmoslabs-paypal-connect__connection--live">LIVE</span>' ) . '</p>';

                        echo '</div>';

                        // connected account
                        if( !empty( $merchant_status['merchant_id'] ) ) :
                            echo '<div class="cozmoslabs-form-field-wrapper">';

                                echo '<label class="cozmoslabs-form-field-label">' . esc_html__( 'Connected Account', 'paid-member-subscriptions' ) . '</label>';

                                echo '<span><strong>'. esc_html( $merchant_status['merchant_id'] );

                                if( !empty( $merchant_status['country'] ) )
                                    echo ' ('. esc_html( $merchant_status['country'] ) .')';

                                echo '</strong></span>';

                            echo '</div>';
                        endif;

                        if( !empty( $paypal_credentials['client_id'] ) ) :
                            // Client ID
                            echo '<div class="cozmoslabs-form-field-wrapper">';

                                echo '<label class="cozmoslabs-form-field-label">' . esc_html__( 'Client ID', 'paid-member-subscriptions' ) . '</label>';

                                echo '<span><a class="pms-copy" href="#" title="' . esc_html__( 'Copy to clipboard', 'paid-member-subscriptions' ) . '" style="color:black"><strong>'. esc_html( $paypal_credentials['client_id'] ) .'</strong></a></span>';

                            echo '</div>';
                        endif;

                        if( !empty( $merchant_status['primary_email'] ) ) :
                            // email address
                            echo '<div class="cozmoslabs-form-field-wrapper">';

                                echo '<label class="cozmoslabs-form-field-label">' . esc_html__( 'Email Address', 'paid-member-subscriptions' ) . '</label>';

                                echo '<span><strong>'. esc_html( $merchant_status['primary_email'] ) .'</strong></span>';

                                if ( $merchant_status['primary_email_confirmed'] ) {
                                    echo '<p class="cozmoslabs-description cozmoslabs-description-align-right"><span class="'. ( $is_payment_test_mode ? 'cozmoslabs-paypal-connect__settings-warning' : 'cozmoslabs-paypal-connect__settings-success' ) .'">'. esc_html__( 'Confirmed', 'paid-member-subscriptions' ) .'</span></p>';
                                }
                                else {
                                    echo '<p class="cozmoslabs-description cozmoslabs-description-align-right"><span class="cozmoslabs-paypal-connect__settings-error">'. esc_html__( 'Not Confirmed', 'paid-member-subscriptions' ) .'</span></p>';
                                    echo '<p class="cozmoslabs-description cozmoslabs-description-space-left">' . esc_html__( 'You currently cannot receive payments.', 'paid-member-subscriptions' ) . '</p>';
                                    echo '<p class="cozmoslabs-description cozmoslabs-description-space-left">' . sprintf( esc_html__( 'Please confirm your email address on your %s in order to receive payments.', 'paid-member-subscriptions' ), '<a href="https://www.paypal.com/businessprofile/settings" target="_blank">PayPal Business Profile</a>' ) . '</p>';
                                }

                            echo '</div>';
                        endif;

                        // payments status
                        echo '<div class="cozmoslabs-form-field-wrapper">';

                            echo '<label class="cozmoslabs-form-field-label">' . esc_html__( 'Payments Status', 'paid-member-subscriptions' ) . '</label>';

                            if ( !empty( $merchant_status['payments_receivable'] ) && !empty( $merchant_status['primary_email_confirmed'] ) && $vaulting_status == 'ACTIVE' ) {
                                echo '<span class="'. ( $is_payment_test_mode ? 'cozmoslabs-paypal-connect__settings-warning' : 'cozmoslabs-paypal-connect__settings-success' ) .'">'. esc_html__( 'Active', 'paid-member-subscriptions' ) .'</span>';

                                if( $is_payment_test_mode )
                                    echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . esc_html__( 'You can start accepting test payments.', 'paid-member-subscriptions' ) . '</p>';
                                else
                                    echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . esc_html__( 'You can start accepting payments.', 'paid-member-subscriptions' ) . '</p>';
                            }
                            else {
                                echo '<span class="cozmoslabs-paypal-connect__settings-error">'. esc_html__( 'Inactive', 'paid-member-subscriptions' ) .'</span>';
                                echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . esc_html__( 'You currently cannot receive payments.', 'paid-member-subscriptions' ) . '</p>';
                            }

                        echo '</div>';

                        // payment receivable and vaulting (email address is also displayed here if not confirmed)
                        echo '<div class="cozmoslabs-description-space-left" id="paypal-connect-payments-data">';

                            if( !empty( $merchant_status['payments_receivable'] ) ) :
                                // payment receivable
                                echo '<div class="cozmoslabs-form-field-wrapper">';

                                    echo '<label class="cozmoslabs-form-field-label">' . esc_html__( 'Payment Receivable', 'paid-member-subscriptions' ) . '</label>';

                                    if ( $merchant_status['payments_receivable'] ) {
                                        echo '<span class="'. ( $is_payment_test_mode ? 'cozmoslabs-paypal-connect__settings-warning' : 'cozmoslabs-paypal-connect__settings-success' ) .'">'. esc_html__( 'Active', 'paid-member-subscriptions' ) .'</span>';
                                    }
                                    else {
                                        echo '<span class="cozmoslabs-paypal-connect__settings-error">'. esc_html__( 'Inactive', 'paid-member-subscriptions' ) .'</span>';
                                        echo '<p class="cozmoslabs-description">' . esc_html__( 'You currently cannot receive payments due to possible restriction on your PayPal account.', 'paid-member-subscriptions' ) . '</p>';
                                        echo '<p class="cozmoslabs-description">' . sprintf( esc_html__( 'Please reach out to PayPal Customer Support or connect to %s for more information.', 'paid-member-subscriptions' ), '<a href="https://www.paypal.com" target="_blank">PayPal.com</a>' ) . '</p>';
                                    }

                                echo '</div>';
                            endif;

                            // vaulting
                            echo '<div class="cozmoslabs-form-field-wrapper">';

                                echo '<label class="cozmoslabs-form-field-label">' . esc_html__( 'Vaulting', 'paid-member-subscriptions' ) . '</label>';

                                if ( !empty( $vaulting_status ) && $vaulting_status == 'ACTIVE' ) {
                                    echo '<span class="'. ( $is_payment_test_mode ? 'cozmoslabs-paypal-connect__settings-warning' : 'cozmoslabs-paypal-connect__settings-success' ) .'">'. esc_html__( 'Enabled', 'paid-member-subscriptions' ) .'</span>';
                                } else {
                                    echo '<span class="cozmoslabs-paypal-connect__settings-error">'. esc_html__( 'Disabled', 'paid-member-subscriptions' ) .'</span>';
                                    echo '<p class="cozmoslabs-description ">' . sprintf( esc_html__( 'You are not able to offer the Vaulting functionality because its onboarding status is %s.', 'paid-member-subscriptions' ), '<strong>' . esc_html( $vaulting_status ) . '</strong>' ) . '</p>';
                                    echo '<p class="cozmoslabs-description ">' . sprintf( esc_html__( ' Please reach out to %s for more information.', 'paid-member-subscriptions' ), '<a href="https://www.paypal.com" target="_blank">PayPal.com</a>' ) . '</p>';
                                }

                            echo '</div>';

                            // email address
                            if ( empty( $merchant_status['primary_email_confirmed'] ) ) {
                                echo '<div class="cozmoslabs-form-field-wrapper">';

                                    echo '<label class="cozmoslabs-form-field-label">' . esc_html__( 'Email Address', 'paid-member-subscriptions' ) . '</label>';

                                    echo '<span class="cozmoslabs-paypal-connect__settings-error">'. esc_html__( 'Not Confirmed', 'paid-member-subscriptions' ) .'</span>';

                                    echo '<p class="cozmoslabs-description">' . sprintf( esc_html__( 'Please confirm your email address on your %s in order to receive payments.', 'paid-member-subscriptions' ), '<a href="https://www.paypal.com/businessprofile/settings" target="_blank">PayPal Business Profile</a>' ) . '</p>';

                                echo '</div>';
                            }

                        echo '</div>';

                        // webhooks status
                        echo '<div class="cozmoslabs-form-field-wrapper">';

                            echo '<label class="cozmoslabs-form-field-label">' . esc_html__( 'Webhooks Status', 'paid-member-subscriptions' ) . '</label>';

                            $webhook_status = get_option( 'pms_paypal_connect_webhook_connection', false );

                            if( empty( $webhook_status ) ){

                                echo '<span class="cozmoslabs-paypal-connect__settings-warning">'. esc_html__( 'Waiting for data', 'paid-member-subscriptions' ) .'</span>';
                                echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . esc_html__( 'When the status changes to Connected, the website has started processing webhook data from PayPal.', 'paid-member-subscriptions' ) . '</p>';

                            } elseif( !empty( $webhook_status ) && $webhook_status < strtotime('-14 days') ) {

                                echo '<span class="cozmoslabs-paypal-connect__settings-warning" style="font-size: 100%">'. esc_html__( 'Unknown', 'paid-member-subscriptions' ) .'</span>';
                                echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . esc_html__( 'Webhooks were connected successfully, but the last webhook received was more than 14 days ago. You should verify that the webhook URL still exists in your PayPal Account.', 'paid-member-subscriptions' ) . '</p>';

                            } else {

                                $date_format = get_option('date_format');
                                $time_format = get_option('time_format');

                                echo '<span class="cozmoslabs-paypal-connect__settings-success" style="font-size: 100%">'. esc_html__( 'Connected', 'paid-member-subscriptions' ) .'</span>';
                                echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . wp_kses_post( sprintf( __( 'Webhooks are connected successfully. Last webhook received at: %s', 'paid-member-subscriptions' ), '<strong>' . date_i18n( $date_format . ' ' . $time_format, $webhook_status ) . '</strong>' ) ). '</p>';

                            }

                        echo '</div>';

                        // disconnect button
                        echo '<div class="cozmoslabs-form-field-wrapper">';

                            echo '<label class="cozmoslabs-form-field-label">' . esc_html__( 'Disconnect', 'paid-member-subscriptions' ) . '</label>';

                            echo '<a class="pms-paypal-connect__disconnect-handler button-secondary" href="#" data-ajax-nonce="'. esc_attr( $disconnect_nonce ) .'">Disconnect</a>';

                            echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . esc_html__( 'Disconnecting your account will stop all payments from being processed.', 'paid-member-subscriptions' ) . '</p>';

                        echo '</div>';


                    endif;

                echo '</div>';

			endif;

			do_action( 'pms_settings_page_payment_gateway_ppcp_extra_fields', $options );

		echo '</div>';

	endif;

}
add_action( 'pms-settings-page_payment_gateways_content', 'pms_ppcp_add_settings_content', 9 );

/**
 * Enqueue admin scripts
 *
 */
add_action( 'admin_enqueue_scripts', 'pms_ppcp_admin_scripts' );
function pms_ppcp_admin_scripts( $hook ){
    
	$parent_menu_slug = sanitize_title( __( 'Paid Member Subscriptions', 'paid-member-subscriptions' ) );

    if( $parent_menu_slug . '_page_pms-settings-page' != $hook || ( isset( $_GET['tab'] ) && $_GET['tab'] != 'payments' ) )
        return;

    if ( file_exists( PMS_PLUGIN_DIR_PATH . 'includes/gateways/paypal_connect/assets/pms-paypal-connect-back-end.js' ) )
        wp_enqueue_script( 'pms-paypal-connect-back-end', PMS_PLUGIN_DIR_URL . 'includes/gateways/paypal_connect/assets/pms-paypal-connect-back-end.js', array( 'jquery' ), PMS_VERSION );
    
    $base_url = pms_is_payment_test_mode() ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com';

    $paypal_credentials = pms_ppcp_get_api_credentials();
    
    if( empty( $paypal_credentials['client_id'] ) || empty( $paypal_credentials['client_secret'] ) )
        wp_enqueue_script( 'pms-paypal-merchantonboarding', $base_url . '/webapps/merchantboarding/js/lib/lightbox/partner.js', array(), PMS_VERSION, true );

}

/**
 * Onboard Merchant to PayPal Connect
 *
 */
add_action( 'wp_ajax_pms_ppcp_process_onboarding', 'pms_ppcp_process_onboarding' );
add_action( 'wp_ajax_nopriv_pms_ppcp_process_onboarding', 'pms_ppcp_process_onboarding' );
function pms_ppcp_process_onboarding(){

    check_ajax_referer( 'pms_paypal_connect_onboarding_nonce', 'ajaxNonce' );

	$shared_id = !empty( $_POST['sharedId'] ) ? sanitize_text_field( $_POST['sharedId'] ) : '';
	$auth_code = !empty( $_POST['authCode'] ) ? sanitize_text_field( $_POST['authCode'] ) : '';

	if( empty( $shared_id ) || empty( $auth_code ) )
		die();

    $environment = pms_is_payment_test_mode() ? 'test' : 'live';

    $seller_nonce = get_option( 'pms_ppcp_seller_nonce_' . $environment, false );

	if ( false !== $seller_nonce ) {

		$gateway = pms_get_payment_gateway( 'paypal_connect' );

		$gateway->onboard_paypal_merchant( $shared_id, $auth_code, $seller_nonce );

	} else {
		die();
	}

}

/**
 * Disconnect Merchant from PayPal Connect
 *
 */
add_action( 'wp_ajax_pms_ppcp_disconnect_paypal', 'pms_ppcp_disconnect_paypal' );
function pms_ppcp_disconnect_paypal(){

    check_ajax_referer( 'pms_paypal_connect_disconnect_nonce', 'ajaxNonce' );

    if( current_user_can( 'manage_options' ) || current_user_can( 'pms_edit_capability' ) ){

        $paypal_credentials = pms_ppcp_get_api_credentials();
    
        if( empty( $paypal_credentials['payer_id'] ) )
            die();
    
        $gateway = pms_get_payment_gateway( 'paypal_connect' );
        $gateway->disconnect_paypal_merchant( $paypal_credentials );

    }

}

/**
 * Adds extra fields for the member's subscription in the add new / edit subscription screen
 *
 * @param int    $subscription_id      - the id of the current subscription's edit screen. 0 for add new screen.
 * @param string $gateway_slug
 * @param array  $gateway_details
 *
 */
add_action('pms_view_add_new_edit_subscription_payment_gateway_extra', 'pms_paypal_connect_add_payment_gateway_admin_subscription_fields', 10, 3);
function pms_paypal_connect_add_payment_gateway_admin_subscription_fields( $subscription_id = 0, $gateway_slug = '', $gateway_details = array() ){

	if ( empty( $gateway_slug ) || empty( $gateway_details ) )
		return;

	$target_gateways = array( 'paypal_connect' );

	// Only add fields for the current gateway of the subscription
	if ( !empty( $subscription_id ) ) {
		$subscription = pms_get_member_subscription( $subscription_id );

		if ( $gateway_slug != $subscription->payment_gateway || !in_array( $subscription->payment_gateway, $target_gateways ) )
			return;
	}

	if ( !in_array($gateway_slug, $target_gateways ) )
		return;

	$paypal_customer_id = (! empty( $subscription_id ) ? pms_get_member_subscription_meta( $subscription_id, '_paypal_customer_id', true ) : '');
	$paypal_customer_id = (! empty( $_POST['_paypal_customer_id'] ) ? sanitize_text_field( $_POST['_paypal_customer_id'] ) : $paypal_customer_id) ;

	$paypal_vault_id = (! empty( $subscription_id ) ? pms_get_member_subscription_meta( $subscription_id, '_paypal_vault_id', true ) : '');
	$paypal_vault_id = (! empty( $_POST['_paypal_vault_id'] ) ? sanitize_text_field( $_POST['_paypal_vault_id'] ) : $paypal_vault_id );

	?>
	<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">
		<label for="pms-subscription-paypal-customer-id" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'PayPal Customer ID', 'paid-member-subscriptions' ); ?></label>
		<input id="pms-subscription-paypal-customer-id" type="text" name="_paypal_customer_id" class="pms-subscription-field" value="<?php echo esc_attr( $paypal_customer_id ); ?>" />
	</div>

	<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">
		<label for="pms-subscription-paypal-vault-id" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'PayPal Vault ID', 'paid-member-subscriptions' ); ?></label>
		<input id="pms-subscription-paypal-vault-id" type="text" name="_paypal_vault_id" class="pms-subscription-field" value="<?php echo esc_attr( $paypal_vault_id ); ?>" />
	</div>
	<?php
	
}

/**
 * Saves the values for the payment gateway subscription extra fields
 *
 * @param int $subscription_id
 *
 */
add_action('pms_member_subscription_insert', 'pms_paypal_connect_save_payment_gateway_admin_subscription_fields');
add_action('pms_member_subscription_update', 'pms_paypal_connect_save_payment_gateway_admin_subscription_fields');
function pms_paypal_connect_save_payment_gateway_admin_subscription_fields( $subscription_id = 0 ){

	if ( $subscription_id == 0 )
		return;

	if ( !is_admin() || !current_user_can( 'manage_options' ) )
		return;

	if ( empty( $_POST['payment_gateway'] ) || !in_array( $_POST['payment_gateway'], array( 'paypal_connect' ) ) )
		return;

	// Update the customer id
	if ( isset( $_POST['_paypal_customer_id'] ) ) {

		if ( pms_update_member_subscription_meta( $subscription_id, '_paypal_customer_id', sanitize_text_field( $_POST['_paypal_customer_id'] ) ) )
			pms_add_member_subscription_log( $subscription_id, 'admin_subscription_edit', array( 'field' => 'paypal_customer_id', 'who' => get_current_user_id() ) );
	}


	// Update the vault id
	if ( isset( $_POST['_paypal_vault_id'] ) ) {

		if ( pms_update_member_subscription_meta( $subscription_id, '_paypal_vault_id', sanitize_text_field( $_POST['_paypal_vault_id'] ) ) )
			pms_add_member_subscription_log( $subscription_id, 'admin_subscription_edit', array( 'field' => 'paypal_vault_id', 'who' => get_current_user_id() ) );
	}

}

/**
 * Remove other PayPal payment gateway from the active gateways list if they are not active already
 */
function pms_paypal_connect_filter_active_payment_gateways( $payment_gateways ){

    $pms_payments_settings = get_option( 'pms_payments_settings', array() );

    $target_gateways = array( 'paypal_standard', 'paypal_express' );

    foreach( $target_gateways as $gateway ){

        $existing_paypal_gateways = get_option( 'pms_paypal_migration_existing_paypal_gateways_' . $gateway, false );

        if( $existing_paypal_gateways )
            continue;

        $disabled_base_gateway = true;

        if( !empty( $pms_payments_settings ) && !empty( $pms_payments_settings['active_pay_gates'] ) ){
    
            if( in_array( $gateway, $pms_payments_settings['active_pay_gates'] ) ){
    
                $disabled_base_gateway = false;
            }
    
        }
    
        if( $disabled_base_gateway ){

            if( isset( $payment_gateways[$gateway] ) )
                unset( $payment_gateways[$gateway] );

        } else {
            update_option( 'pms_paypal_migration_existing_paypal_gateways_' . $gateway, true );
        }

    }

    return $payment_gateways;

}
add_filter( 'pms_admin_display_payment_gateways', 'pms_paypal_connect_filter_active_payment_gateways', 20, 2 );

/**
 * Add deprecation notice for other PayPal gateways
 */
add_action( 'admin_init', 'pms_paypal_connect_add_deprecation_notice' );
function pms_paypal_connect_add_deprecation_notice() {

    $active_gateways = pms_get_active_payment_gateways();

    if( !in_array( 'paypal_connect', $active_gateways ) && ( in_array( 'paypal_standard', $active_gateways ) || in_array( 'paypal_express', $active_gateways ) ) ){

        $notification_id = 'pms_paypal_deprecation_notice';

        $message = '<img style="max-width: 32px;width: 32px;" src="' . PMS_PLUGIN_DIR_URL . 'assets/images/pms-logo.svg" />';
        $message .= '<strong style="font-size:110%;position:relative;top:-10px;margin-left: 8px;">' . __( 'Deprecation notice', 'paid-member-subscriptions' ) . '</strong><br><br>';
        $message .= sprintf( __( 'The PayPal version you are using right now has been deprecated.<br> Benefit from the latest security updates and <strong>improved conversion rates</strong> with the new streamlined payment experience that keeps customers on your website throughout the payment process.<br><br>Go to the %sSettings -> Payments -> Gateways%s page, enable the <strong>PayPal gateway</strong> and connect your account. %sMore details%s', 'paid-member-subscriptions' ), '<a href="'. admin_url( 'admin.php?page=pms-settings-page&tab=payments&nav_sub_tab=payments_gateways' ) .'" target="_blank">', '</a>', '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/payment-gateways/paypal/?utm_source=wpbackend&utm_medium=pms-paypal-migration&utm_campaign=PMSFree#Migration_from_older_PayPal_gateways" target="_blank">', '</a>' );

        $message .= sprintf( __( ' %1$sDismiss%2$s', 'paid-member-subscriptions'), "<a href='" . wp_nonce_url( add_query_arg( $notification_id . '_dismiss_notification', '0' ), 'pms_general_notice_dismiss' ) . "' type='button' class='notice-dismiss'><span class='screen-reader-text'>", "</span></a>" );

        pms_add_plugin_notification( $notification_id, $message, 'notice-error', '', '', true );

    }

    $multiple_currencies_addon_active = apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-multiple-currencies/index.php' );

    // Add notice about currency incompatibility
    if( in_array( 'paypal_connect', $active_gateways ) && !$multiple_currencies_addon_active ){

        $default_currency              = pms_get_active_currency();
        $paypal_unsupported_currencies = pms_ppcp_get_paypal_unsupported_currencies();
        $pms_notifications_instance = PMS_Plugin_Notifications::get_instance();

        if( in_array( $default_currency, array_keys( $paypal_unsupported_currencies ) ) ){

            $notification_id = 'pms_paypal_connect_currency_incompatibility_notice';

            if( $pms_notifications_instance->is_plugin_page() )
                $notification_id = 'pms_paypal_connect_currency_incompatibility_notice_own_pages';

            $message = '<img style="max-width: 32px;width: 32px;" src="' . PMS_PLUGIN_DIR_URL . 'includes/gateways/paypal_connect/assets/img/paypal-icon.png" />';
            $message .= '<strong style="font-size:110%;position:relative;top:-10px;margin-left: 8px;">' . __( 'PayPal Currency Incompatibility', 'paid-member-subscriptions' ) . '</strong><br><br>';
            $message .= sprintf( __( 'The default currency you are using right now is not supported by %s.', 'paid-member-subscriptions' ), '<strong>PayPal</strong>' ) . '<br>';
            $message .= sprintf( __( 'In order for checkout to work, you need to select a supported currency. The list of supported currencies for PayPal can be found %shere%s.', 'paid-member-subscriptions' ), '<a href="https://developer.paypal.com/docs/reports/reference/paypal-supported-currencies/" target="_blank">', '</a>' ) . '<br><br>';
            $message .= sprintf( __( 'Go to the %sSettings -> Payments -> General%s page and select a supported currency.', 'paid-member-subscriptions' ), '<a href="'. admin_url( 'admin.php?page=pms-settings-page&tab=payments&nav_sub_tab=payments_general' ) .'" target="_blank">', '</a>' ) . '<br><br>';

            $message .= '<em>'.sprintf( __( '%sDid you know?%s With %s and the %s add-on, you can accept payments in any currency with %s by displaying the local currency to users and converting the payment to a supported currency when the user is charged. %sLearn More%s or %sBuy Now%s', 'paid-member-subscriptions' ), '<strong>', '</strong>', '<strong>Paid Member Subscriptions Pro</strong>', '<strong>Multiple Currencies</strong>', '<strong>PayPal</strong>', '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/payment-gateways/paypal/?utm_source=wpbackend&utm_medium=pms-paypal-currency-incompatibility&utm_campaign=PMSFree#Supported_Currencies" target="_blank">', '</a>', '<a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=pms-paypal-currency-incompatibility&utm_campaign=PMSFree#pricing" target="_blank">', '</a>' ) . '</em><br><br>';


            if( !$pms_notifications_instance->is_plugin_page() )
                $message .= sprintf( __( ' %1$sDismiss%2$s', 'paid-member-subscriptions'), "<a href='" . wp_nonce_url( add_query_arg( $notification_id . '_dismiss_notification', '0' ), 'pms_general_notice_dismiss' ) . "' type='button' class='notice-dismiss'><span class='screen-reader-text'>", "</span></a>" );

            pms_add_plugin_notification( $notification_id, $message, 'notice-error', '', '', true );

        }   
    }
        
    
}