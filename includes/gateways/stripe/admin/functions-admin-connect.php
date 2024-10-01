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
            'pms_stripe_connect_success' => 1,
        ),
			admin_url( 'admin.php#pms-stripe__gateway-settings' )
		);

	}

	// flush rules to make sure apple domain verification file can be served
	flush_rewrite_rules();

    // set account country
    $gateway = new PMS_Payment_Gateway_Stripe_Connect();
    $gateway->init();

    $gateway->set_account_country();

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

	// flush rules to make sure apple domain verification file can be served
	flush_rewrite_rules();

    // set account country
    $gateway = new PMS_Payment_Gateway_Stripe_Connect();
    $gateway->init();

    $gateway->set_account_country();

	if( !$gateway->domain_is_registered() ){
		$gateway->register_domain();
	}

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

    // Set card id value
    $stripe_customer_id = ( ! empty( $subscription_id ) ? pms_get_member_subscription_meta( $subscription_id, '_stripe_customer_id', true ) : '' );
    $stripe_customer_id = ( ! empty( $_POST['_stripe_customer_id'] ) ? sanitize_text_field( $_POST['_stripe_customer_id'] ) : $stripe_customer_id );

    // Set card id value
    $stripe_card_id = ( ! empty( $subscription_id ) ? pms_get_member_subscription_meta( $subscription_id, '_stripe_card_id', true ) : '' );
    $stripe_card_id = ( ! empty( $_POST['_stripe_card_id'] ) ? sanitize_text_field( $_POST['_stripe_card_id'] ) : $stripe_card_id );

    // Stripe Customer ID
    echo '<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">';

        echo '<label for="pms-subscription-stripe-customer-id" class="pms-meta-box-field-label cozmoslabs-form-field-label">' . esc_html__( 'Stripe Customer ID', 'paid-member-subscriptions' ) . '</label>';
        echo '<input id="pms-subscription-stripe-customer-id" type="text" name="_stripe_customer_id" class="pms-subscription-field" value="' . esc_attr( $stripe_customer_id ) . '" />';

    echo '</div>';

    // Stripe Card ID
    echo '<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">';

        echo '<label for="pms-subscription-stripe-card-id" class="pms-meta-box-field-label cozmoslabs-form-field-label">' . esc_html__( 'Stripe Card ID', 'paid-member-subscriptions' ) . '</label>';
        echo '<input id="pms-subscription-stripe-card-id" type="text" name="_stripe_card_id" class="pms-subscription-field" value="' . esc_attr( $stripe_card_id ) . '" />';

    echo '</div>';

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

function pms_stripe_add_currencies( $currencies ){

    if( version_compare( PMS_VERSION, '2.0.0', '<' ) )
        return $currencies;

    // We're overwriting the currencies from the main plugin
    $currencies = array(
        'USD' => __( 'US Dollar', 'paid-member-subscriptions' ),
        'EUR' => __( 'Euro', 'paid-member-subscriptions' ),
        'GBP' => __( 'Pound sterling', 'paid-member-subscriptions' ),
        'CAD' => __( 'Canadian dollar', 'paid-member-subscriptions' ),
        'AED' => __( 'United Arab Emirates dirham', 'paid-member-subscriptions' ),
		'AFN' => __( 'Afghan afghani', 'paid-member-subscriptions' ),
		'ALL' => __( 'Albanian lek', 'paid-member-subscriptions' ),
		'AMD' => __( 'Armenian dram', 'paid-member-subscriptions' ),
		'ANG' => __( 'Netherlands Antillean guilder', 'paid-member-subscriptions' ),
		'AOA' => __( 'Angolan kwanza', 'paid-member-subscriptions' ),
		'ARS' => __( 'Argentine peso', 'paid-member-subscriptions' ),
		'AUD' => __( 'Australian dollar', 'paid-member-subscriptions' ),
		'AWG' => __( 'Aruban florin', 'paid-member-subscriptions' ),
		'AZN' => __( 'Azerbaijani manat', 'paid-member-subscriptions' ),
		'BAM' => __( 'Bosnia and Herzegovina convertible mark', 'paid-member-subscriptions' ),
		'BBD' => __( 'Barbadian dollar', 'paid-member-subscriptions' ),
		'BDT' => __( 'Bangladeshi taka', 'paid-member-subscriptions' ),
		'BGN' => __( 'Bulgarian lev', 'paid-member-subscriptions' ),
		'BIF' => __( 'Burundian franc', 'paid-member-subscriptions' ),
		'BMD' => __( 'Bermudian dollar', 'paid-member-subscriptions' ),
		'BND' => __( 'Brunei dollar', 'paid-member-subscriptions' ),
		'BOB' => __( 'Bolivian boliviano', 'paid-member-subscriptions' ),
		'BRL' => __( 'Brazilian real', 'paid-member-subscriptions' ),
		'BSD' => __( 'Bahamian dollar', 'paid-member-subscriptions' ),
		'BWP' => __( 'Botswana pula', 'paid-member-subscriptions' ),
		'BZD' => __( 'Belize dollar', 'paid-member-subscriptions' ),
		'CDF' => __( 'Congolese franc', 'paid-member-subscriptions' ),
		'CHF' => __( 'Swiss franc', 'paid-member-subscriptions' ),
		'CLP' => __( 'Chilean peso', 'paid-member-subscriptions' ),
		'CNY' => __( 'Chinese yuan', 'paid-member-subscriptions' ),
		'COP' => __( 'Colombian peso', 'paid-member-subscriptions' ),
		'CRC' => __( 'Costa Rican col&oacute;n', 'paid-member-subscriptions' ),
		'CVE' => __( 'Cape Verdean escudo', 'paid-member-subscriptions' ),
		'CZK' => __( 'Czech koruna', 'paid-member-subscriptions' ),
		'DJF' => __( 'Djiboutian franc', 'paid-member-subscriptions' ),
		'DKK' => __( 'Danish krone', 'paid-member-subscriptions' ),
		'DOP' => __( 'Dominican peso', 'paid-member-subscriptions' ),
		'DZD' => __( 'Algerian dinar', 'paid-member-subscriptions' ),
		'EGP' => __( 'Egyptian pound', 'paid-member-subscriptions' ),
		'ERN' => __( 'Eritrean nakfa', 'paid-member-subscriptions' ),
		'ETB' => __( 'Ethiopian birr', 'paid-member-subscriptions' ),
		'FJD' => __( 'Fijian dollar', 'paid-member-subscriptions' ),
		'FKP' => __( 'Falkland Islands pound', 'paid-member-subscriptions' ),
		'GEL' => __( 'Georgian lari', 'paid-member-subscriptions' ),
		'GIP' => __( 'Gibraltar pound', 'paid-member-subscriptions' ),
		'GMD' => __( 'Gambian dalasi', 'paid-member-subscriptions' ),
		'GNF' => __( 'Guinean franc', 'paid-member-subscriptions' ),
		'GTQ' => __( 'Guatemalan quetzal', 'paid-member-subscriptions' ),
		'GYD' => __( 'Guyanese dollar', 'paid-member-subscriptions' ),
		'HKD' => __( 'Hong Kong dollar', 'paid-member-subscriptions' ),
		'HNL' => __( 'Honduran lempira', 'paid-member-subscriptions' ),
		'HRK' => __( 'Croatian kuna', 'paid-member-subscriptions' ),
		'HTG' => __( 'Haitian gourde', 'paid-member-subscriptions' ),
		'HUF' => __( 'Hungarian forint', 'paid-member-subscriptions' ),
		'IDR' => __( 'Indonesian rupiah', 'paid-member-subscriptions' ),
		'ILS' => __( 'Israeli new shekel', 'paid-member-subscriptions' ),
		'INR' => __( 'Indian rupee', 'paid-member-subscriptions' ),
		'ISK' => __( 'Icelandic kr&oacute;na', 'paid-member-subscriptions' ),
		'JMD' => __( 'Jamaican dollar', 'paid-member-subscriptions' ),
		'JPY' => __( 'Japanese yen', 'paid-member-subscriptions' ),
		'KES' => __( 'Kenyan shilling', 'paid-member-subscriptions' ),
		'KGS' => __( 'Kyrgyzstani som', 'paid-member-subscriptions' ),
		'KHR' => __( 'Cambodian riel', 'paid-member-subscriptions' ),
		'KMF' => __( 'Comorian franc', 'paid-member-subscriptions' ),
		'KRW' => __( 'South Korean won', 'paid-member-subscriptions' ),
		'KYD' => __( 'Cayman Islands dollar', 'paid-member-subscriptions' ),
		'KZT' => __( 'Kazakhstani tenge', 'paid-member-subscriptions' ),
		'LAK' => __( 'Lao kip', 'paid-member-subscriptions' ),
		'LBP' => __( 'Lebanese pound', 'paid-member-subscriptions' ),
		'LKR' => __( 'Sri Lankan rupee', 'paid-member-subscriptions' ),
		'LRD' => __( 'Liberian dollar', 'paid-member-subscriptions' ),
		'LSL' => __( 'Lesotho loti', 'paid-member-subscriptions' ),
		'MAD' => __( 'Moroccan dirham', 'paid-member-subscriptions' ),
		'MDL' => __( 'Moldovan leu', 'paid-member-subscriptions' ),
		'MGA' => __( 'Malagasy ariary', 'paid-member-subscriptions' ),
		'MKD' => __( 'Macedonian denar', 'paid-member-subscriptions' ),
		'MMK' => __( 'Burmese kyat', 'paid-member-subscriptions' ),
		'MNT' => __( 'Mongolian t&ouml;gr&ouml;g', 'paid-member-subscriptions' ),
		'MOP' => __( 'Macanese pataca', 'paid-member-subscriptions' ),
		'MUR' => __( 'Mauritian rupee', 'paid-member-subscriptions' ),
		'MVR' => __( 'Maldivian rufiyaa', 'paid-member-subscriptions' ),
		'MWK' => __( 'Malawian kwacha', 'paid-member-subscriptions' ),
		'MXN' => __( 'Mexican peso', 'paid-member-subscriptions' ),
		'MYR' => __( 'Malaysian ringgit', 'paid-member-subscriptions' ),
		'MZN' => __( 'Mozambican metical', 'paid-member-subscriptions' ),
		'NAD' => __( 'Namibian dollar', 'paid-member-subscriptions' ),
		'NGN' => __( 'Nigerian naira', 'paid-member-subscriptions' ),
		'NIO' => __( 'Nicaraguan c&oacute;rdoba', 'paid-member-subscriptions' ),
		'NOK' => __( 'Norwegian krone', 'paid-member-subscriptions' ),
		'NPR' => __( 'Nepalese rupee', 'paid-member-subscriptions' ),
		'NZD' => __( 'New Zealand dollar', 'paid-member-subscriptions' ),
		'PAB' => __( 'Panamanian balboa', 'paid-member-subscriptions' ),
		'PEN' => __( 'Sol', 'paid-member-subscriptions' ),
		'PGK' => __( 'Papua New Guinean kina', 'paid-member-subscriptions' ),
		'PHP' => __( 'Philippine peso', 'paid-member-subscriptions' ),
		'PKR' => __( 'Pakistani rupee', 'paid-member-subscriptions' ),
		'PLN' => __( 'Polish z&#x142;oty', 'paid-member-subscriptions' ),
		'PYG' => __( 'Paraguayan guaran&iacute;', 'paid-member-subscriptions' ),
		'QAR' => __( 'Qatari riyal', 'paid-member-subscriptions' ),
		'RON' => __( 'Romanian leu', 'paid-member-subscriptions' ),
		'RSD' => __( 'Serbian dinar', 'paid-member-subscriptions' ),
		'RUB' => __( 'Russian ruble', 'paid-member-subscriptions' ),
		'RWF' => __( 'Rwandan franc', 'paid-member-subscriptions' ),
		'SAR' => __( 'Saudi riyal', 'paid-member-subscriptions' ),
		'SBD' => __( 'Solomon Islands dollar', 'paid-member-subscriptions' ),
		'SCR' => __( 'Seychellois rupee', 'paid-member-subscriptions' ),
		'SEK' => __( 'Swedish krona', 'paid-member-subscriptions' ),
		'SGD' => __( 'Singapore dollar', 'paid-member-subscriptions' ),
		'SHP' => __( 'Saint Helena pound', 'paid-member-subscriptions' ),
		'SLL' => __( 'Sierra Leonean leone', 'paid-member-subscriptions' ),
		'SOS' => __( 'Somali shilling', 'paid-member-subscriptions' ),
		'SRD' => __( 'Surinamese dollar', 'paid-member-subscriptions' ),
		'SZL' => __( 'Swazi lilangeni', 'paid-member-subscriptions' ),
		'THB' => __( 'Thai baht', 'paid-member-subscriptions' ),
		'TJS' => __( 'Tajikistani somoni', 'paid-member-subscriptions' ),
		'TOP' => __( 'Tongan pa&#x2bb;anga', 'paid-member-subscriptions' ),
		'TRY' => __( 'Turkish lira', 'paid-member-subscriptions' ),
		'TTD' => __( 'Trinidad and Tobago dollar', 'paid-member-subscriptions' ),
		'TWD' => __( 'New Taiwan dollar', 'paid-member-subscriptions' ),
		'TZS' => __( 'Tanzanian shilling', 'paid-member-subscriptions' ),
		'UAH' => __( 'Ukrainian hryvnia', 'paid-member-subscriptions' ),
		'UGX' => __( 'Ugandan shilling', 'paid-member-subscriptions' ),
		'UYU' => __( 'Uruguayan peso', 'paid-member-subscriptions' ),
		'UZS' => __( 'Uzbekistani som', 'paid-member-subscriptions' ),
		'VND' => __( 'Vietnamese &#x111;&#x1ed3;ng', 'paid-member-subscriptions' ),
		'VUV' => __( 'Vanuatu vatu', 'paid-member-subscriptions' ),
		'WST' => __( 'Samoan t&#x101;l&#x101;', 'paid-member-subscriptions' ),
		'XAF' => __( 'Central African CFA franc', 'paid-member-subscriptions' ),
		'XCD' => __( 'East Caribbean dollar', 'paid-member-subscriptions' ),
		'XOF' => __( 'West African CFA franc', 'paid-member-subscriptions' ),
		'XPF' => __( 'CFP franc', 'paid-member-subscriptions' ),
		'YER' => __( 'Yemeni rial', 'paid-member-subscriptions' ),
		'ZAR' => __( 'South African rand', 'paid-member-subscriptions' ),
		'ZMW' => __( 'Zambian kwacha', 'paid-member-subscriptions' ),
    );

    return $currencies;

}
add_filter( 'pms_currencies', 'pms_stripe_add_currencies' );

/**
 * Function that adds the HTML for Stripe in the payments tab from the Settings page
 *
 * @param array $options    - The saved option settings
 *
 */
function pms_stripe_add_settings_content( $options ) {

	if( !empty( $options['active_pay_gates'] ) && in_array( 'stripe_connect', $options['active_pay_gates'] ) ) :

		echo '<div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-stripe-connect-configs">';

			echo '<h4 class="cozmoslabs-subsection-title" id="pms-stripe__gateway-settings">'
					. esc_html__( 'Stripe', 'paid-member-subscriptions' ) .
					'<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/payment-gateways/stripe-connect/#Initial_Setup" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
				</h4>';

			if( in_array( 'stripe_connect', $options['active_pay_gates'] ) ) :

				// Display link to connect Stripe Account
				$stripe_connect_base_url = 'https://www.cozmoslabs.com/?pms_stripe_connect_handle_authorization';
				$environment             = pms_is_payment_test_mode() ? 'test' : 'live';
				$account                 = pms_stripe_get_connect_account();

				echo '<div class="pms-stripe-connect__gateway-settings">';

					if( !empty( $account ) ){

						$connection_status = pms_stripe_connect_get_account_status();

						if( is_array( $connection_status ) ){

							echo '<p>' . esc_html__( 'An error happened with the connection of your Stripe account. Stripe is reporting the following error: ', 'paid-member-subscriptions' ) . '</p>';

								echo '<p class="cozmoslabs-stripe-connect__settings-error">' . esc_html( $connection_status['message'] ) . '</p>';

							echo '<p>' . esc_html__( 'Please reload the page and connect your account again in order to receive payments.', 'paid-member-subscriptions' ) . '</p>';

						} else if( $connection_status != false ){

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


							$account_id = pms_is_payment_test_mode() ? get_option( 'pms_stripe_connect_test_account_id', '-' ) :  get_option( 'pms_stripe_connect_live_account_id', '-' );
							$country    = get_option( 'pms_stripe_connect_account_country' );

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
								],
								$stripe_connect_base_url
							);

							echo '<div class="cozmoslabs-form-field-wrapper">';

								echo '<label class="cozmoslabs-form-field-label" for="stripe-connect-webhook-url">' . esc_html__( 'Disconnect', 'paid-member-subscriptions' ) . '</label>';

								echo '<a class="pms-stripe-connect__disconnect-handler button-secondary" href="'. esc_url( $stripe_disconnect_link ) .'">Disconnect</a>';

								echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . esc_html__( 'Disconnecting your account will stop all payments from being processed.', 'paid-member-subscriptions' ) . '</p>';

							echo '</div>';

							// if( !pms_stripe_is_domain_registered_for_payment_methods() ){
							// 	echo '<h3 class="cozmoslabs-subsection-title" style="margin-top:16px !important;">' , esc_html__( 'Domain Registration', 'paid-member-subscriptions' ) . '</h3>';

							// 	echo '<div class="cozmoslabs-form-field-wrapper">';

							// 		echo '<label class="cozmoslabs-form-field-label" for="stripe-connect-payment-request">' . esc_html__( 'Status', 'paid-member-subscriptions' ) . '</label>';

							// 		echo '<span class="cozmoslabs-stripe-connect__settings-warning">'. esc_html__( 'Not registered', 'paid-member-subscriptions' ) .'</span>';

							// 		echo '<p class="cozmoslabs-description cozmoslabs-description-align-right">' . esc_html__( 'This domain is not registered with Stripe. In order to enable payment gateways like Apple Pay, Google Pay or Link in your payment forms, your domain needs to be registered and verified.', 'paid-member-subscriptions' ) . '</p>';
							// 		echo '<p class="cozmoslabs-description">' . esc_html__( 'Press the button below to register and validate the current domain.', 'paid-member-subscriptions' ) . '</p>';

							// 	echo '</div>';
							// }


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