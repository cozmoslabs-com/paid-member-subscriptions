<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// vars:
//      $user_id
//      $member
//      $current_subscription
//      $current_subscription_plan_id
//      $current_subscription_plan
//      $subscription_plan_upgrades
//      $subscription_plan_downgrades
//      $subscription_plan_others
//      $payment_settings

pms_output_subscription_plans_filter( 'remove' );
$extra_classes = apply_filters( 'pms_add_extra_form_classes', '' , 'change_subscription_form' );
?>

<form id="pms-change-subscription-form" action="" method="POST" class="pms-form <?php echo esc_attr( $extra_classes ) ?>">

    <?php do_action('pms_change_subscription_form_top'); ?>

    <?php
    if( empty( $subscription_plan_downgrades ) && empty( $subscription_plan_others ) )
        pms_output_subscription_plans_filter( 'add' );

    if( !empty( $subscription_plan_upgrades ) ) : ?>

        <div class="pms-upgrade__group pms-upgrade__group--upgrade">

            <div class="pms-upgrade__message">
                <?php if( count( $subscription_plan_upgrades ) == 1 ) : ?>
                    <?php echo wp_kses_post( sprintf( __( 'Upgrade %1$s to %2$s', 'paid-member-subscriptions' ), '<strong>' . $current_subscription_plan->name . '</strong>', '<strong>' . $subscription_plan_upgrades[0]->name . '</strong>' ) ); ?>
                <?php else : ?>
                    <?php echo wp_kses_post( sprintf(  __( 'Upgrade %s to:', 'paid-member-subscriptions' ), '<strong>' . $current_subscription_plan->name . '</strong>' ) ); ?>
                <?php endif; ?>

                <?php do_action('pms_change_subscription_message_extra_info', 'upgrade_subscription', $subscription_plan_upgrades, $subscription_plan_downgrades, $subscription_plan_others );?>
            </div>

            <?php echo pms_output_subscription_plans( $subscription_plan_upgrades, array(), false, '', 'upgrade_subscription' ); //phpcs:ignore  WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        </div>

    <?php endif; ?>

    <?php
    if( empty( $subscription_plan_others ) )
        pms_output_subscription_plans_filter( 'add' );

    if( !empty( $subscription_plan_downgrades ) ) : ?>

    <div class="pms-upgrade__group pms-upgrade__group--downgrade">

        <div class="pms-upgrade__message">
            <?php if( count( $subscription_plan_downgrades ) == 1 ) : ?>
                <?php echo wp_kses_post( sprintf( __( 'Downgrade %1$s to %2$s', 'paid-member-subscriptions' ), '<strong>' . $current_subscription_plan->name . '</strong>', '<strong>' . $subscription_plan_downgrades[0]->name . '</strong>' ) ); ?>
            <?php else : ?>
                <?php echo wp_kses_post( sprintf(  __( 'Downgrade %s to:', 'paid-member-subscriptions' ), '<strong>' . $current_subscription_plan->name . '</strong>' ) ); ?>
            <?php endif; ?>

            <?php do_action('pms_change_subscription_message_extra_info', 'downgrade_subscription', $subscription_plan_upgrades, $subscription_plan_downgrades, $subscription_plan_others );?>
        </div>

        <?php echo pms_output_subscription_plans( $subscription_plan_downgrades, array(), false, '', 'downgrade_subscription' ); //phpcs:ignore  WordPress.Security.EscapeOutput.OutputNotEscaped ?>

    </div>

    <?php endif; ?>

    <?php do_action( 'pms_change_subscription_form_after_downgrade_group', $current_subscription, $subscription_plan_upgrades, $subscription_plan_downgrades, $subscription_plan_others ); ?>

    <?php
    pms_output_subscription_plans_filter( 'add' );

    if( !empty( $subscription_plan_others ) ) : ?>

    <div class="pms-upgrade__group pms-upgrade__group--change">

        <div class="pms-upgrade__message">
            <?php echo wp_kses_post( sprintf(  __( 'Change %s to:', 'paid-member-subscriptions' ), '<strong>' . $current_subscription_plan->name . '</strong>' ) ); ?>

            <?php do_action('pms_change_subscription_message_extra_info', 'change_subscription', $subscription_plan_upgrades, $subscription_plan_downgrades, $subscription_plan_others );?>
        </div>

        <?php echo pms_output_subscription_plans( $subscription_plan_others, array(), false, '', 'change_subscription' ); //phpcs:ignore  WordPress.Security.EscapeOutput.OutputNotEscaped ?>

    </div>

    <?php endif; ?>

    <input type="hidden" name="pms_current_subscription" value="<?php echo isset( $_GET['subscription_id'] ) ? esc_attr( $_GET['subscription_id'] ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized ?>" />
    <input type="hidden" name="pmstkn" value="<?php echo esc_attr( wp_create_nonce( 'pms_change_subscription', 'pmstkn' ) ); ?>" />
    <input type="hidden" name="form_action" value="<?php echo esc_attr( wp_create_nonce( 'pms_change_subscription', 'pmstkn' ) ); ?>" />

    <input type="hidden" data-name="upgrade_subscription" value="<?php echo esc_attr( wp_create_nonce( 'pms_upgrade_subscription', 'pmstkn' ) ); ?>" />
    <input type="hidden" data-name="downgrade_subscription" value="<?php echo esc_attr( wp_create_nonce( 'pms_downgrade_subscription', 'pmstkn' ) ); ?>" />

    <?php do_action('pms_change_subscription_form_bottom'); ?>

    <!-- Dynamic button name based on which group the user selects -->
    <input type="hidden" name="pms_button_name_upgrade" value="<?php esc_attr_e( 'Upgrade Subscription', 'paid-member-subscriptions' ); ?>" />
    <input type="hidden" name="pms_button_name_downgrade" value="<?php esc_attr_e( 'Downgrade Subscription', 'paid-member-subscriptions' ); ?>" />
    <input type="hidden" name="pms_button_name_change" value="<?php esc_attr_e( 'Change Subscription', 'paid-member-subscriptions' ); ?>" />

    <input type="submit" name="pms_change_subscription" value="<?php esc_attr_e( 'Change Subscription', 'paid-member-subscriptions' ); ?>" />
    <input type="submit" name="pms_redirect_back" value="<?php echo esc_attr( apply_filters( 'pms_change_subscription_go_back_button_value', __( 'Go back', 'paid-member-subscriptions' ) ) ); ?>" />

</form>