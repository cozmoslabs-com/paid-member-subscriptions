<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/**
 * Get the PayPal Connect Seller Onboarding URL
 * 
 * @return string The PayPal Connect URL
 */
function pms_ppcp_get_paypal_connect_url(){

    $paypal_url = pms_is_payment_test_mode() ? 'https://www.sandbox.paypal.com' : 'https://www.paypal.com';

    $paypal_url .= '/bizsignup/partner/entry';

    $paypal_url = add_query_arg(
        [
            'partnerId'          => pms_ppcp_get_platform_partner_id(),
            'product'            => 'ppcp',
            'secondaryProducts'  => 'payment_methods,advanced_vaulting',
            'capabilities'       => 'APPLE_PAY,GOOGLE_PAY,paypal_wallet_vaulting_advanced',
            'features'           => 'PAYMENT,REFUND,ACCESS_MERCHANT_INFORMATION,BILLING_AGREEMENT,VAULT',
            'integrationType'    => 'FO',
            'partnerClientId'    => pms_ppcp_get_platform_partner_client_id(),
            //'partnerLogoUrl'     => 'https://www.paypal.com/', @TODO: ADD LOGO, WHITELIST URL
            'displayMode'        => 'minibrowser',
            'sellerNonce'        => pms_ppcp_generate_seller_nonce( 45 ),
        ],
        $paypal_url
    );

    return $paypal_url;

}

/**
 * Generate a seller nonce for PayPal Connect
 * 
 * @param int $length The length of the nonce
 * @return string The generated nonce
 */
function pms_ppcp_generate_seller_nonce( $length ){

    $seller_nonce = '';

    while ( strlen( $seller_nonce ) < $length ) {
        $chunk = str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        $seller_nonce .= $chunk;
    }

    $environment = pms_is_payment_test_mode() ? 'test' : 'live';

    delete_option( 'pms_ppcp_seller_nonce_' . $environment );

    update_option( 'pms_ppcp_seller_nonce_' . $environment, $seller_nonce );

    return $seller_nonce;

}

/**
 * Get the Merchant data
 *
 * @return mixed|boolean
 */
function pms_ppcp_get_merchant_status(){

    $merchant_status = get_transient( 'pms_ppcp_merchant_status' );

    if( $merchant_status !== false && !empty( $merchant_status ) )
        return $merchant_status;

    $gateway = pms_get_payment_gateway( 'paypal_connect' );

    if( !is_null( $gateway ) ) {
        $merchant_status = $gateway->get_merchant_status();
        set_transient( 'pms_ppcp_merchant_status', $merchant_status, 5 * MINUTE_IN_SECONDS );
        
        return $merchant_status;
    }

    return false;

}

/**
 * Get the Merchant Vaulting status
 *
 * @param $merchant_status
 * @return mixed|string
 */
function pms_ppcp_vaulting_status( $merchant_status = array() ){

    if ( empty( $merchant_status ) )
        $merchant_status = pms_ppcp_get_merchant_status();

    $vaulting_status = '';

    if( is_array( $merchant_status ) && !empty( $merchant_status ) ) {
        foreach ( $merchant_status['capabilities'] as $capability ) {

            if ( $capability['name'] == 'PAYPAL_WALLET_VAULTING_ADVANCED' ) {
                $vaulting_status = $capability['status'];
                break;
            }

        }
    }

    return $vaulting_status;
}

/**
 * Get the platform partner ID
 * 
 * @return string The platform partner ID
 */
function pms_ppcp_get_platform_partner_id(){

    if( pms_is_payment_test_mode() )
        return 'M75F44ZWPHUPS';
    else
        return 'FFK32UPTTCN6Y';

}

/**
 * Get the platform partner client ID
 * 
 * @return string The platform partner client ID
 */
function pms_ppcp_get_platform_partner_client_id(){

    if( pms_is_payment_test_mode() )
        return 'AUC3lmYUOtHRHldyCgy_ISDqd7bbwRbNT0RtJeEz8UHlMBn0LqQKGf0c0IdgdiRBWgQyBvhOWyaBgjYY';
    else
        return 'AbmedhiytvG133gzopzWDsscDaafvdlcJTCFRPZRdnxnz2tSHG1YP_aSWrzxhsprqjn3dlMrko1OowmE';

}

/**
 * Get the platform BN code
 * 
 * @return string The platform BN code
 */
function pms_ppcp_get_platform_bn_code(){

    return 'COZMOSLABSSRL_SP_PPCP';

}

/**
 * Get the currencies that are not supported by PayPal
 *
 * @return mixed|null
 */
function pms_ppcp_get_paypal_unsupported_currencies() {

    $supported_currencies   = array( 'AUD', 'BRL', 'CAD', 'CHF', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'SGD', 'SEK', 'THB', 'USD' );
    $unsupported_currencies = pms_get_currencies();

    foreach ( $unsupported_currencies as $currency_code => $currency_name ) {
        if ( in_array( $currency_code, $supported_currencies ) )
            unset( $unsupported_currencies[$currency_code] );
    }

    return apply_filters( 'pms_paypal_unsupported_currencies', $unsupported_currencies );
    
}
