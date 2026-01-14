<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// vars:
//      $user_id
//      $member
//      $extra_classes

// Get subscription plan
$subscription_plan = pms_get_subscription_plan( trim( absint( $_REQUEST['subscription_plan'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated

?>

<form id="pms-retry-payment-subscription-form" action="" method="POST" class="pms-form <?php echo esc_attr( $extra_classes ) ?>">

    <?php do_action( 'pms_retry_payment_form_top' ); ?>

    <div class="pms-retry-payment-subscription__plans pms-form-fields-wrapper">

        <?php do_action( 'pms_retry_payment_form_before_subscription_plans_output' ); ?>

        <?php echo apply_filters( 'pms_retry_payment_subscription_confirmation_message', '<p>' . sprintf( __( 'Your %s subscription is still pending. Do you wish to retry the payment?', 'paid-member-subscriptions' ) . '</p>', '<strong>' . esc_html( $subscription_plan->name ) . '</strong>' ), $subscription_plan ); //phpcs:ignore  WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <?php echo pms_output_subscription_plans( array( $subscription_plan ), array(), false, '', 'retry_payment' ); //phpcs:ignore  WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <?php do_action( 'pms_retry_payment_form_after_subscription_plans_output' ); ?>

    </div>

    <?php do_action( 'pms_retry_payment_form_bottom' ); ?>

    <?php wp_nonce_field( 'pms_retry_payment_subscription', 'pmstkn' ); ?>

    <?php if( apply_filters( 'pms_retry_payment_subscription_form_submit_button_enabled', true, 'retry_payment' ) ) : ?>
        <input type="submit" name="pms_confirm_retry_payment_subscription" value="<?php echo esc_attr( apply_filters( 'pms_retry_payment_subscription_button_value', __( 'Retry payment', 'paid-member-subscriptions' ) ) ); ?>" />
    <?php endif; ?>
        
    <input type="submit" name="pms_redirect_back" value="<?php echo esc_attr( apply_filters( 'pms_retry_payment_subscription_go_back_button_value', __( 'Go back', 'paid-member-subscriptions' ) ) ); ?>" />

</form>