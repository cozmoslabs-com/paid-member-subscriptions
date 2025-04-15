<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

add_action( 'admin_post_pms_stripe_connect_platform_authorization_return', 'pms_stripe_connect_handle_authorization_return' );
function pms_stripe_connect_handle_authorization_return(){

	if( !isset( $_POST['environment'] ) )
		return;

	if( !current_user_can( 'manage_options' ) )
		return;

    $environment = sanitize_text_field( $_POST['environment'] );

    if( !empty( $_POST['account_id'] ) )
        update_option( 'pms_stripe_connect_'. $environment .'_account_id', sanitize_text_field( $_POST['account_id'] ) );

    if( !empty( $_POST['stripe_publishable_key'] ) )
        update_option( 'pms_stripe_connect_'. $environment .'_publishable_key', sanitize_text_field( $_POST['stripe_publishable_key'] ) );

    if( !empty( $_POST['stripe_secret_key'] ) )
        update_option( 'pms_stripe_connect_'. $environment .'_secret_key', sanitize_text_field( $_POST['stripe_secret_key'] ) );

	if( isset( $_POST['return_location'] ) && $_POST['return_location'] == 'setup' ){

		$redirect_url = add_query_arg( array(
            'page'                       => 'pms-setup',
            'step'                       => 'payments',
            'pms_stripe_connect_success' => 1,
        ),
			admin_url( 'index.php' )
		);

	} elseif( isset( $_POST['return_location'] ) && $_POST['return_location'] == 'setup_new' ) {

		$redirect_url = add_query_arg( array(
			'page'                       => 'pms-dashboard-page',
			'subpage'                    => 'pms-setup',
			'step'                       => 'payments',
			'pms_stripe_connect_success' => 1,
        ),
			admin_url( 'admin.php' )
		);

	} else {

		$redirect_url = add_query_arg( array(
            'page'                       => 'pms-settings-page',
            'tab'                        => 'payments',
            'nav_sub_tab'                => 'payments_gateways',
            'pms_stripe_connect_success' => 1,
        ),
			admin_url( 'admin.php#pms-stripe__gateway-settings' )
		);

	}

	// flush rules to make sure apple domain verification file can be served
	flush_rewrite_rules();

    // set account country
    $gateway = pms_get_payment_gateway( 'stripe_connect' );

    $gateway->set_account_country( $environment );

	if( !$gateway->domain_is_registered() ){
		$gateway->register_domain();
	}


    wp_redirect( $redirect_url );
    die();

}

add_action( 'init', 'pms_stripe_connect_handle_authorization_return_admin_init' );
function pms_stripe_connect_handle_authorization_return_admin_init(){

	if( !isset( $_GET['environment'] ) || !isset( $_GET['pms_stripe_connect_platform_authorization_return'] ) )
		return;

	if( !current_user_can( 'manage_options' ) )
		return;

    $environment = sanitize_text_field( $_GET['environment'] );

    if( !empty( $_GET['account_id'] ) )
        update_option( 'pms_stripe_connect_'. $environment .'_account_id', sanitize_text_field( $_GET['account_id'] ) );

    if( !empty( $_GET['stripe_publishable_key'] ) )
        update_option( 'pms_stripe_connect_'. $environment .'_publishable_key', sanitize_text_field( $_GET['stripe_publishable_key'] ) );

    if( !empty( $_GET['stripe_secret_key'] ) )
        update_option( 'pms_stripe_connect_'. $environment .'_secret_key', sanitize_text_field( $_GET['stripe_secret_key'] ) );

	if( $environment == 'live' )
		update_option( 'pms_stripe_connect_live_account_connected', 'yes' );

	// flush rules to make sure apple domain verification file can be served
	flush_rewrite_rules();

    // set account country
    $gateway = pms_get_payment_gateway( 'stripe_connect' );

    $gateway->set_account_country( $environment );

	if( !$gateway->domain_is_registered() ){
		$gateway->register_domain();
	}

	// Set Stripe as active gateway
	$payments_settings = get_option( 'pms_payments_settings', array() );

	if( !isset( $payments_settings['active_pay_gates'] ) ){
		$payments_settings['active_pay_gates']   = array();
		$payments_settings['active_pay_gates'][] = 'stripe_connect';
	} else if( !in_array( 'stripe_connect', $payments_settings['active_pay_gates'] ) ){
		$payments_settings['active_pay_gates'][] = 'stripe_connect';
	}

	update_option( 'pms_payments_settings', $payments_settings );

	if( isset( $_POST['return_location'] ) && $_POST['return_location'] == 'setup' ){

		$redirect_url = add_query_arg( array(
            'page'                       => 'pms-setup',
            'step'                       => 'payments',
            'pms_stripe_connect_success' => 1,
        ),
			admin_url( 'index.php' )
		);

	} elseif( isset( $_POST['return_location'] ) && $_POST['return_location'] == 'setup_new' ) {

		$redirect_url = add_query_arg( array(
			'page'                       => 'pms-dashboard-page',
			'subpage'                    => 'pms-setup',
			'step'                       => 'payments',
			'pms_stripe_connect_success' => 1,
        ),
			admin_url( 'admin.php' )
		);

	} else {

		$redirect_url = add_query_arg( array(
            'page'                       => 'pms-settings-page',
            'tab'                        => 'payments',
			'nav_sub_tab'                => 'payments_gateways',
            'pms_stripe_connect_success' => 1,
        ),
			admin_url( 'admin.php#pms-stripe__gateway-settings' )
		);

	}

    wp_redirect( $redirect_url );
    die();

}

add_action( 'admin_init', 'pms_stripe_connect_platform_disconnect' );
function pms_stripe_connect_platform_disconnect(){

    if( !isset( $_GET['pms_nonce'] ) || !isset( $_GET['pms_stripe_connect_platform_disconnect'] ) || $_GET['pms_stripe_connect_platform_disconnect'] != 1 || !isset( $_GET['environment' ] ) )
        return;

	if( !current_user_can( 'manage_options' ) )
		return;

	if( !wp_verify_nonce( sanitize_text_field( $_GET['pms_nonce'] ), 'pms_stripe_disconnect' ) )
		return;

    $environment = sanitize_text_field( $_GET['environment'] );

    delete_option( 'pms_stripe_connect_'. $environment .'_account_id' );
    delete_option( 'pms_stripe_connect_'. $environment .'_publishable_key' );
    delete_option( 'pms_stripe_connect_'. $environment .'_secret_key' );

}

add_action( 'admin_init', 'pms_stripe_connect_dismiss_domain_registration_notice' );
function pms_stripe_connect_dismiss_domain_registration_notice(){

	if( !isset( $_GET['pms_nonce'] ) || !wp_verify_nonce( sanitize_text_field( $_GET['pms_nonce'] ), 'stripe_connect_dismiss_domain_registration_notice' ) )
		return;

	if( !isset( $_GET['pms_stripe_connect_dismiss_domain_registration_notice'] ) )
		return;

	if( !current_user_can( 'manage_options' ) )
		return;

	$environment = pms_is_payment_test_mode() ? 'test' : 'live';

	update_option( 'pms_stripe_connect_'. $environment .'_domain_registration_notice_dismiss', true );

}

add_action( 'admin_init', 'pms_stripe_connect_register_domain' );
function pms_stripe_connect_register_domain(){

	if( !isset( $_GET['pms_nonce'] ) || !wp_verify_nonce( sanitize_text_field( $_GET['pms_nonce'] ), 'stripe_connect_register_domain' ) )
		return;

	if( !isset( $_GET['pms_stripe_connect_register_domain'] ) )
		return;

	if( !current_user_can( 'manage_options' ) )
		return;

	$gateway = new PMS_Payment_Gateway_Stripe_Connect();
	$gateway->init();

	if( !$gateway->domain_is_registered() ){
		$gateway->register_domain();
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
function pms_stripe_add_payment_gateway_admin_subscription_fields( $subscription_id = 0, $gateway_slug = '', $gateway_details = array() ) {

    if( empty( $gateway_slug ) || empty( $gateway_details ) )
        return;

    if( !function_exists( 'pms_get_member_subscription_meta' ) )
        return;

	$target_gateways = array( 'stripe', 'stripe_connect', 'stripe_intents' );

	// Only add fields for the current gateway of the subscription
	if( !empty( $subscription_id ) ){
		$subscription = pms_get_member_subscription( $subscription_id );

		if( $gateway_slug != $subscription->payment_gateway || !in_array( $subscription->payment_gateway, $target_gateways ) )
        	return;
	}

	if( !in_array( $gateway_slug, $target_gateways ) )
        return;

    // Set customer id value
    $stripe_customer_id = ( ! empty( $subscription_id ) ? pms_get_member_subscription_meta( $subscription_id, '_stripe_customer_id', true ) : '' );
    $stripe_customer_id = ( ! empty( $_POST['_stripe_customer_id'] ) ? sanitize_text_field( $_POST['_stripe_customer_id'] ) : $stripe_customer_id );

    // Set card id value
    $stripe_card_id = ( ! empty( $subscription_id ) ? pms_get_member_subscription_meta( $subscription_id, '_stripe_card_id', true ) : '' );
    $stripe_card_id = ( ! empty( $_POST['_stripe_card_id'] ) ? sanitize_text_field( $_POST['_stripe_card_id'] ) : $stripe_card_id );

    ?>
	<!-- Stripe Customer ID -->
	<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">
        <label for="pms-subscription-stripe-customer-id" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Stripe Customer ID', 'paid-member-subscriptions' ); ?></label>
        <input id="pms-subscription-stripe-customer-id" type="text" name="_stripe_customer_id" class="pms-subscription-field" value="<?php echo esc_attr( $stripe_customer_id ); ?>" />

    </div>

    <!-- Stripe Card ID -->
    <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

        <label for="pms-subscription-stripe-card-id" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php echo esc_html__( 'Stripe Card ID', 'paid-member-subscriptions' ); ?></label>
        <input id="pms-subscription-stripe-card-id" type="text" name="_stripe_card_id" class="pms-subscription-field" value="<?php echo esc_attr( $stripe_card_id ); ?>" />

    </div><?php

}
add_action( 'pms_view_add_new_edit_subscription_payment_gateway_extra', 'pms_stripe_add_payment_gateway_admin_subscription_fields', 10, 3 );


/**
 * Checks to see if data from the extra subscription fields is valid
 *
 * @param array $admin_notices
 *
 * @return array
 *
 */
function pms_stripe_validate_subscription_data_admin_fields( $admin_notices = array() ) {

    // Validate the customer id
    if( ! empty( $_POST['_stripe_customer_id'] ) ) {

        if( false === strpos( sanitize_text_field( $_POST['_stripe_customer_id'] ), 'cus_' ) )
            $admin_notices[] = array( 'error' => __( 'The provided Stripe Customer ID is not valid.', 'paid-member-subscriptions' ) );

    }

    // Validate the card id
    if( ! empty( $_POST['_stripe_card_id'] ) ) {

        if( preg_match( '(card_|pm_)', sanitize_text_field( $_POST['_stripe_card_id'] ) ) !== 1 )
            $admin_notices[] = array( 'error' => __( 'The provided Stripe Card ID is not valid.', 'paid-member-subscriptions' ) );

    }

    return $admin_notices;

}
add_filter( 'pms_submenu_page_members_validate_subscription_data', 'pms_stripe_validate_subscription_data_admin_fields' );


/**
 * Saves the values for the payment gateway subscription extra fields
 *
 * @param int $subscription_id
 *
 */
function pms_stripe_save_payment_gateway_admin_subscription_fields( $subscription_id = 0 ) {

    if( ! function_exists( 'pms_update_member_subscription_meta' ) )
        return;

    if( $subscription_id == 0 )
        return;

    if( ! is_admin() )
        return;

    if( ! current_user_can( 'manage_options' ) )
        return;

	if( empty( $_POST['payment_gateway'] ) || !in_array( $_POST['payment_gateway'], array( 'stripe', 'stripe_intents', 'stripe_connect' ) ) )
        return;

    // Update the customer id
    if( isset( $_POST['_stripe_customer_id'] ) ){

        if( pms_update_member_subscription_meta( $subscription_id, '_stripe_customer_id', sanitize_text_field( $_POST['_stripe_customer_id'] ) ) )
            pms_add_member_subscription_log( $subscription_id, 'admin_subscription_edit', array( 'field' => 'stripe_customer_id', 'who' => get_current_user_id() ) );

    }


    // Update the card id
    if( isset( $_POST['_stripe_card_id'] ) ){

        if( pms_update_member_subscription_meta( $subscription_id, '_stripe_card_id', sanitize_text_field( $_POST['_stripe_card_id'] ) ) )
            pms_add_member_subscription_log( $subscription_id, 'admin_subscription_edit', array( 'field' => 'stripe_card_id', 'who' => get_current_user_id() ) );

    }

}
add_action( 'pms_member_subscription_insert', 'pms_stripe_save_payment_gateway_admin_subscription_fields' );
add_action( 'pms_member_subscription_update', 'pms_stripe_save_payment_gateway_admin_subscription_fields' );

/**
 * Function that adds the HTML for Stripe in the payments tab from the Settings page
 *
 * @param array $options    - The saved option settings
 *
 */
function pms_stripe_add_settings_content( $options ) {

	$account = pms_stripe_connect_get_account();

	if( ( !empty( $options['active_pay_gates'] ) && in_array( 'stripe_connect', $options['active_pay_gates'] ) ) || empty( $account ) ) :

		echo '<div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-stripe-connect-configs" '. ( !in_array( 'stripe_connect', $options['active_pay_gates'] ) && empty( $account ) ? 'style="display:none"' : '' ) .'>';

            echo '<div class="cozmoslabs-subsection-title-container">';
                echo '<img class="cozmoslabs-payment-gateway__metabox-icon" src="' . esc_attr( PMS_PLUGIN_DIR_URL ) . 'includes/gateways/stripe/assets/img/stripe-icon.jpeg"  alt="PayPal" />';
                echo '<h4 class="cozmoslabs-subsection-title" id="pms-stripe__gateway-settings">'
                    . esc_html__( 'Stripe', 'paid-member-subscriptions' ) .
                    '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/payment-gateways/stripe-connect/#Initial_Setup" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
                        </h4>';
            echo '</div>';

			if( in_array( 'stripe_connect', $options['active_pay_gates'] ) || empty( $account ) ) :

				// Display link to connect Stripe Account
				$stripe_connect_base_url = 'https://www.cozmoslabs.com/?pms_stripe_connect_handle_authorization';
				$environment             = pms_is_payment_test_mode() ? 'test' : 'live';

				echo '<div class="pms-stripe-connect__gateway-settings">';

					if( !empty( $account ) ){

						$connection_status = pms_stripe_connect_get_account_status();

						if( is_array( $connection_status ) ){

							echo '<p>' . esc_html__( 'An error happened with the connection of your Stripe account. Stripe is reporting the following error: ', 'paid-member-subscriptions' ) . '</p>';

								echo '<p class="cozmoslabs-stripe-connect__settings-error">' . esc_html( $connection_status['message'] ) . '</p>';

							echo '<p>' . esc_html__( 'Please reload the page and connect your account again in order to receive payments.', 'paid-member-subscriptions' ) . '</p>';

						} else if( $connection_status != false ){

							if( isset( $_GET['pms_stripe_connect_success'] ) && $_GET['pms_stripe_connect_success'] == 1 )
								echo '<p style="text-align:center; font-size: 110%; color: green;">' . sprintf( __('You connected successfully in %s mode. You can start accepting payments.', 'paid-member-subscriptions' ), pms_is_payment_test_mode() ? '<strong>Test</strong>' : '<strong>Live</strong>' ) . '</p>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

							echo '<div class="cozmoslabs-form-field-wrapper">';

								echo '<label class="cozmoslabs-form-field-label" for="stripe-connect-webhook-url">' . esc_html__( 'Connection Status', 'paid-member-subscriptions' ) . '</label>';

								echo '<span class="'. ( pms_is_payment_test_mode() ? 'cozmoslabs-stripe-connect__settings-warning' : 'cozmoslabs-stripe-connect__settings-success' ) .'">'. esc_html__( 'Success', 'paid-member-subscriptions' ) .'</span>';

                                if( pms_is_payment_test_mode() )
                                    echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . sprintf( esc_html__( 'Your account is connected successfully in %s mode. You can start accepting test payments.', 'paid-member-subscriptions' ), '<span class="cozmoslabs-stripe-connect__connection--test">TEST</span>' ) . '</p>';
                                else
                                    echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . sprintf( esc_html__( 'Your account is connected successfully in %s mode. You can start accepting payments.', 'paid-member-subscriptions' ), '<span class="cozmoslabs-stripe-connect__connection--live">LIVE</span>' ) . '</p>';


							echo '</div>';

							$serial_number        = pms_get_serial_number();
							$serial_number_status = pms_get_serial_number_status();

							if ( pms_is_paid_version_active() ){

								// serial is empty
								if( empty( $serial_number ) ){
									echo '<p class="cozmoslabs-description cozmoslabs-stripe-connect__notice">' . wp_kses_post( sprintf( __( '<strong>NOTE</strong>: All payments include a <strong>2%% fee</strong> because you don\'t have a license. %sClick here%s to purchase a license now.', 'paid-member-subscriptions' ), '<a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=stripe-connect-fee-notice#pricing">', '</a>' ) ) . '</p>';
								// serial is not empty but it's not activated
								} else if ( !empty( $serial_number ) && $serial_number_status == false ) {
									echo '<p class="cozmoslabs-description cozmoslabs-stripe-connect__notice">' . wp_kses_post( sprintf( __( '<strong>NOTE</strong>: All payments include a <strong>2%% fee</strong> because your license is not activated. Go to your %sSettings%s page in order to activated it.', 'paid-member-subscriptions' ), '<a href="'. admin_url( 'admin.php?page=pms-settings-page' ) .'">', '</a>' ) ) . '</p>';
								// serial is activated but it's expired
								} else if ( !empty( $serial_number ) && $serial_number_status != 'valid' ) {
									echo '<p class="cozmoslabs-description cozmoslabs-stripe-connect__notice">' . wp_kses_post( sprintf( __( '<strong>NOTE</strong>: All payments include a <strong>2%% fee</strong> because your license is expired. Go to your %sCozmoslabs Account%s page in order to renew.', 'paid-member-subscriptions' ), '<a href="https://www.cozmoslabs.com/account/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=stripe-connect-fee-notice">', '</a>' ) ) . '</p>';
								}

							}
							elseif( !pms_is_paid_version_active() && empty( $serial_number ) )
								echo '<p class="cozmoslabs-description cozmoslabs-stripe-connect__notice">' . wp_kses_post( sprintf( __( '<strong>NOTE</strong>: All payments done through Stripe include a <strong>2%% fee</strong> because you\'re using the free version of Paid Member Subscriptions. <br>This fee goes to the Paid Member Subscriptions team and is used to continue supporting the development of this gateway and the plugin in general. <br>Users with an active license key will not be charged this fee, %sclick here%s to purchase one.', 'paid-member-subscriptions' ), '<a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=clientsite&utm_campaign=PMSFree&utm_content=stripe-connect-fee-notice#pricing" target="_blank">', '</a>' ) ) . '</p>';


							$account_id = pms_stripe_connect_get_account();
							$country    = pms_stripe_connect_get_account_country();

							echo '<div class="cozmoslabs-form-field-wrapper">';

								echo '<label class="cozmoslabs-form-field-label" for="stripe-connect-account">' . esc_html__( 'Connected Account', 'paid-member-subscriptions' ) . '</label>';

								echo '<span><strong>'. esc_html( $account_id ) .'</strong> ('. esc_html( $country ) .')</span>';

							echo '</div>';

						}

						echo '<div class="pms-stripe-connect__settings">';
							echo '<div class="cozmoslabs-form-field-wrapper">';

								echo '<label class="cozmoslabs-form-field-label" for="stripe-connect-webhook-url">' . esc_html__( 'Webhooks Status', 'paid-member-subscriptions' ) . '</label>';

								$webhook_status = get_option( 'pms_stripe_connect_webhook_connection', false );

								if( empty( $webhook_status ) ){

									echo '<span class="cozmoslabs-stripe-connect__settings-warning">'. esc_html__( 'Waiting for data', 'paid-member-subscriptions' ) .'</span>';
									echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . esc_html__( 'When the status changes to Connected, the website has started processing webhook data from Stripe.', 'paid-member-subscriptions' ) . '</p>';

								} elseif( !empty( $webhook_status ) && $webhook_status < strtotime('-14 days') ) {

									echo '<span class="cozmoslabs-stripe-connect__settings-warning" style="font-size: 100%">'. esc_html__( 'Unknown', 'paid-member-subscriptions' ) .'</span>';
									echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . esc_html__( 'Webhooks were connected successfully, but the last webhook received was more than 14 days ago. You should verify that the webhook URL still exists in your Stripe Account.', 'paid-member-subscriptions' ) . '</p>';

								} else {

									$date_format = get_option('date_format');
									$time_format = get_option('time_format');

									echo '<span class="cozmoslabs-stripe-connect__settings-success" style="font-size: 100%">'. esc_html__( 'Connected', 'paid-member-subscriptions' ) .'</span>';
									echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . wp_kses_post( sprintf( __( 'Webhooks are connected successfully. Last webhook received at: %s', 'paid-member-subscriptions' ), '<strong>' . date_i18n( $date_format . ' ' . $time_format, $webhook_status ) . '</strong>' ) ). '</p>';

								}

							echo '</div>';

							echo '<div class="cozmoslabs-form-field-wrapper">';

								echo '<label class="cozmoslabs-form-field-label" for="stripe-connect-webhook-url">' . esc_html__( 'Webhooks URL', 'paid-member-subscriptions' ) . '</label>';

								echo '<input id="stripe-connect-webhook-url" type="text" name="stripe_connect_webhook_url" value="' . esc_url( add_query_arg( 'pay_gate_listener', 'stripe', trailingslashit( home_url() ) ) ) . '" class="widefat" disabled /><a class="stripe-connect__copy button-secondary" data-id="stripe-connect-webhook-url" href="" style="margin-left: 4px;">Copy</a>';

								echo '<p class="cozmoslabs-description cozmoslabs-description-space-left">' . wp_kses_post( sprintf( __( 'Copy this URL and configure it in your Stripe Account. %sClick here%s to learn more about the Webhooks setup process. ', 'paid-member-subscriptions' ), '<br><a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/payment-gateways/stripe-connect/#Webhooks_setup">', '</a>' ) ) . '</p>';

							echo '</div>';

							$stripe_disconnect_link = add_query_arg(
								[
									'pms_stripe_connect_action' => 'disconnect',
									'environment'               => $environment,
									'pms_stripe_account_id'     => get_option( 'pms_stripe_connect_'. $environment .'_account_id', false ),
									'home_url'                  => site_url(),
									'pms_nonce'                 => wp_create_nonce( 'pms_stripe_disconnect' ),
								],
								$stripe_connect_base_url
							);

							echo '<div class="cozmoslabs-form-field-wrapper">';

								echo '<label class="cozmoslabs-form-field-label" for="stripe-connect-webhook-url">' . esc_html__( 'Disconnect', 'paid-member-subscriptions' ) . '</label>';

								echo '<a class="pms-stripe-connect__disconnect-handler button-secondary" href="'. esc_url( $stripe_disconnect_link ) .'">Disconnect</a>';

								echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . esc_html__( 'Disconnecting your account will stop all payments from being processed.', 'paid-member-subscriptions' ) . '</p>';

							echo '</div>';

							$domain_registration_status = pms_stripe_is_domain_registered_for_payment_methods();
							$domain_registration_notice_dismiss = get_option( 'pms_stripe_connect_'. $environment .'_domain_registration_notice_dismiss', false );

							if( $domain_registration_status !== true && $domain_registration_status['status'] == false && !$domain_registration_notice_dismiss ){
								echo '<h3 class="cozmoslabs-subsection-title" style="margin-top:16px !important;">' , esc_html__( 'Domain Registration', 'paid-member-subscriptions' ) . '</h3>';

								echo '<div class="cozmoslabs-form-field-wrapper">';

									echo '<label class="cozmoslabs-form-field-label" for="stripe-connect-payment-request">' . esc_html__( 'Status', 'paid-member-subscriptions' ) . '</label>';

									if( $domain_registration_status['message'] == 'domain_not_verified' ){
										echo '<span class="cozmoslabs-stripe-connect__settings-warning">'. esc_html__( 'Current domain is not verified.', 'paid-member-subscriptions' ) .'</span>';
										echo '<p class="cozmoslabs-description cozmoslabs-description-space-left">' . sprintf( esc_html__( 'This domain is not registered with %sStripe%s. In order to enable payment gateways like %sApple Pay, Google Pay or Link%s in your payment forms, your domain needs to be registered and verified.', 'paid-member-subscriptions' ), '<strong>', '</strong>', '<strong>', '</strong>' ) . '</p>';
										echo '<p class="cozmoslabs-description cozmoslabs-description-space-left">' . esc_html__( 'Press the button below to register and validate the current domain.', 'paid-member-subscriptions' ) . '</p>';
										echo '<p class="cozmoslabs-description-space-left"><a class="button-secondary" href="'. esc_url( wp_nonce_url( add_query_arg( 'pms_stripe_connect_register_domain', 'true' ), 'stripe_connect_register_domain', 'pms_nonce' ) ) .'" style="max-width: 150px;">'. esc_html__( 'Register domain', 'paid-member-subscriptions' ) .'</a></p>';
									} else {
										echo '<span class="cozmoslabs-stripe-connect__settings-warning">'. esc_html__( 'Verification status couldn\'t be determined.', 'paid-member-subscriptions' ) .'</span>';
										echo '<p class="cozmoslabs-description cozmoslabs-description-space-left">' . esc_html__( 'The plugin cannot determine the verification status of the current domain. Your domain might already be validated.', 'paid-member-subscriptions' ) . '</p>';
										echo '<p class="cozmoslabs-description cozmoslabs-description-space-left">' . sprintf( esc_html__( 'To verify this, go to the %sStripe Dashboard -> Settings -> Payments -> Payment Method Domains%s and look for the current domain, it should be displayed as %sEnabled%s.', 'paid-member-subscriptions' ), '<strong>', '</strong>', '<strong>', '</strong>' ) . '</p>';
										echo '<p class="cozmoslabs-description-space-left"><a class="button-secondary" href="'. esc_url( wp_nonce_url( add_query_arg( 'pms_stripe_connect_dismiss_domain_registration_notice', 'true' ), 'stripe_connect_dismiss_domain_registration_notice', 'pms_nonce' ) ) .'" style="max-width: 150px;">'. esc_html__( 'Dismiss notice', 'paid-member-subscriptions' ) .'</a></p>';
									}

								echo '</div>';
							}


						echo '</div>';

					} else {

						if( isset( $_GET['pms_stripe_connect_platform_error'] ) && !empty( $_GET['code'] ) ){

							if( !empty( $_GET['error'] ) ){
								$error = sanitize_text_field( $_GET['error'] );

								echo '<p class="cozmoslabs-stripe-connect__settings-error">'. esc_html( $error ) . '</p>';
							} else {

								$error_code = sanitize_text_field( $_GET['code'] );

								if( $error_code == 'generic_error' ){
									echo '<p class="cozmoslabs-stripe-connect__settings-error">' . esc_html__( 'Something went wrong, please attempt the connection again.', 'paid-member-subscriptions' ) . '</p>';
								}

							}

						}

						$stripe_connect_link = add_query_arg(
							[
								'pms_stripe_connect_action' => 'connect',
								'environment'               => $environment,
								'home_url'                  => site_url(),
								'pms_nonce'                 => wp_create_nonce( 'stripe_connnect_account' ),
								'version'                   => 'v2'
							],
							$stripe_connect_base_url
						);

						echo '<a href="'. esc_url( $stripe_connect_link ) .'" class="cozmoslabs-stripe-connect__button"><img src="' . esc_attr( PMS_PLUGIN_DIR_URL ) . 'includes/gateways/stripe/assets/img/stripe-connect.png" /></a><br>';
						echo '<p class="cozmoslabs-description">'
                                . esc_html__( 'Connect your existing Stripe account or create a new one to start accepting payments. Press the button above to start.', 'paid-member-subscriptions' ) .
                                '<br>'
                                . esc_html__( 'You will be redirected back here once the process is completed.', 'paid-member-subscriptions' ) .
                             '<p>';

					}

				echo '</div>';

			endif;

			do_action( 'pms_settings_page_payment_gateway_stripe_extra_fields', $options );

		echo '</div>';

	endif;

}
add_action( 'pms-settings-page_payment_gateways_content', 'pms_stripe_add_settings_content', 9 );


function pms_stripe_add_backend_warning( $options ){

    if( !isset( $options['active_pay_gates'] ) || !in_array( 'stripe_intents', $options['active_pay_gates'] ) )
        return;

    echo '<div class="pms-form-field-wrapper pms-stripe-admin-warning" style="background: #fde0dd;padding: 10px 15px; margin-top: 10px;">
        <strong>Action Required!</strong><br> The Stripe version you are using right now is being deprecated soon. In order to benefit from the latest security updates please <strong>migrate to the Stripe Connect gateway</strong> as soon as possible. <br>Starting with the second half of this year, Stripe might charge you additional fees if you don\'t migrate. <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/payment-gateways/stripe-connect/#Migration_from_other_Stripe_gateways_to_Stripe_Connect" target="_blank">Migration instructions</a>
    </div>';

}
add_action( 'pms-settings-page_payment_general_after_gateway_checkboxes', 'pms_stripe_add_backend_warning' );