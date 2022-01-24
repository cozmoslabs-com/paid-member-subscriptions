<?php

add_filter('pms_output_subscription_plan_action_renewal_time', 'pms_misc_modify_renewal_action_output_time');
function pms_misc_modify_renewal_action_output_time( $value ) {
    $misc_settings = get_option( 'pms_misc_settings', array() );
    if ( isset( $misc_settings['payments']['payment_renew_button_delay']))
        $value = $misc_settings['payments']['payment_renew_button_delay'];

    return $value;
}