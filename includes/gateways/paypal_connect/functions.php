<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

function pms_ppcp_get_api_credentials(){

    $environment = pms_is_payment_test_mode() ? 'test' : 'live';

    return array(
        'client_id'     => get_option( 'pms_paypal_connect_'. $environment .'_client_id', '' ),
        'client_secret' => get_option( 'pms_paypal_connect_'. $environment .'_client_secret', '' ),
        'payer_id'      => get_option( 'pms_paypal_connect_'. $environment .'_payer_id', '' ),
    );

}