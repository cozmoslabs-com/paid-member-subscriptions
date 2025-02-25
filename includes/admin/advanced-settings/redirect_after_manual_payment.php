<?php

add_filter( 'wppb_after_success_email_confirmation_redirect', 'pms_misc_redirect_after_manual_payment' );
add_filter( 'wppb_register_redirect', 'pms_misc_redirect_after_manual_payment' );
add_action( 'pms_get_redirect_url', 'pms_misc_redirect_after_manual_payment');
function pms_misc_redirect_after_manual_payment( $url ) {

    if( !isset( $_POST['pay_gate'] ) || $_POST['pay_gate'] != 'manual' || !isset( $_POST['subscription_plans'] ) )
        return $url;

    $subscription_plan = pms_get_subscription_plan( absint( $_POST['subscription_plans'] ) );

    if( !isset( $subscription_plan->id ) || $subscription_plan->price == 0 )
        return $url;

    $misc_settings = get_option( 'pms_misc_settings', array() );

    if ( isset( $misc_settings['payments']['redirect_after_manual_payment'] ) && filter_var($misc_settings['payments']['redirect_after_manual_payment'], FILTER_VALIDATE_URL) !== false ){
        $query_args = [
            'subscription_plan' => absint( $_POST['subscription_plans'] ),
        ];

        if( !empty( $_POST['pms_current_subscription'] ) )
            $query_args['subscription'] = absint( $_POST['pms_current_subscription'] );

        if( !empty( $_POST['user_email'] ) ){
            $user = get_user_by( 'email', sanitize_text_field( $_POST['user_email'] ) );

            if( !empty( $user->ID ) ){
                $payments = pms_get_payments( array( 'user_id' => $user->ID, 'subscription_plan_id' => absint( $_POST['subscription_plans'] ), 'payment_gateway' => 'manual', 'number' => 1, 'order' => 'DESC' ) );

                if( !empty( $payments ) && !empty( $payments[0] ) ){
                    $query_args['payment_id'] = $payments[0]->id;
                }
            }
        }

        $url = add_query_arg( $query_args, $misc_settings['payments']['redirect_after_manual_payment'] );
    }

    return $url;

}