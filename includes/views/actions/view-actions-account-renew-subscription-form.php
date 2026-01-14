<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// vars:
//      $user_id
//      $member
//      $member_subscription
//      $subscription_plan
//      $renew_expiration_date
//      $extra_classes

?>

<form id="pms-renew-subscription-form" action="" method="POST" class="pms-form <?php echo esc_attr( $extra_classes ) ?>">

    <?php do_action('pms_renew_subscription_form_top'); ?>

    <div class="pms-renew-subscription__plans pms-form-fields-wrapper">

        <?php do_action( 'pms_renew_subscription_form_before_subscription_plans_output' ); ?>

        <?php echo apply_filters( 'pms_renew_subscription_before_form', '<p>' . sprintf( __( 'Renew %s subscription. The subscription will be active until %s', 'paid-member-subscriptions' ), '<strong>' . esc_html( $subscription_plan->name ) . '</strong>', '<strong>' . esc_html( $renew_expiration_date ) .'</strong>' ) . '</p>', $subscription_plan, $member ); //phpcs:ignore  WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <?php echo pms_output_subscription_plans( array( $subscription_plan ), array(), false, '', 'renew_subscription' ); //phpcs:ignore  WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <?php do_action( 'pms_renew_subscription_form_after_subscription_plans_output' ); ?>

    </div>

    <?php do_action('pms_renew_subscription_form_bottom'); ?>

    <?php wp_nonce_field( 'pms_renew_subscription', 'pmstkn' ); ?>

    <input type="hidden" name="pms_current_subscription" value="<?php echo esc_attr( $member_subscription['id'] ); ?>" />

    <?php if( apply_filters( 'pms_renew_subscription_form_submit_button_enabled', true, 'renew_subscription' ) ) : ?>
        <input type="submit" name="pms_renew_subscription" value="<?php echo esc_attr( apply_filters( 'pms_renew_subscription_button_value', esc_html__( 'Renew Subscription', 'paid-member-subscriptions' ) ) ); ?>" />
    <?php endif; ?>

    <input type="submit" name="pms_redirect_back" value="<?php echo esc_attr( apply_filters( 'pms_renew_subscription_go_back_button_value', esc_html__( 'Go back', 'paid-member-subscriptions' ) ) ); ?>" />

</form>