<?php

add_filter( 'wppb_after_success_email_confirmation_redirect', 'pms_misc_redirect_after_manual_payment' );
add_filter( 'wppb_register_redirect', 'pms_misc_redirect_after_manual_payment' );
add_action('pms_get_redirect_url', 'pms_misc_redirect_after_manual_payment');
function pms_misc_redirect_after_manual_payment( $url ) {
    if( !isset( $_POST['pay_gate'] ) || $_POST['pay_gate'] != 'manual' || !isset( $_POST['subscription_plans'] ) )
        return $url;

    $subscription_plan = pms_get_subscription_plan( absint( $_POST['subscription_plans'] ) );

    if( !isset( $subscription_plan->id ) || $subscription_plan->price == 0 )
        return $url;
    else {
        $misc_settings = get_option( 'pms_misc_settings', array() );
        if ( isset( $misc_settings['payments']['redirect_after_manual_payment']) && filter_var($misc_settings['payments']['redirect_after_manual_payment'], FILTER_VALIDATE_URL) !== false )
            $url = $misc_settings['payments']['redirect_after_manual_payment'];
    }
    return $url;
}
