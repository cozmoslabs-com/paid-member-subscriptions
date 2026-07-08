<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for subscription plan pause meta-box
 */
?>
<?php
$license_status      = pms_get_serial_number_status();
$button_label        = __( 'Upgrade to Pro', 'paid-member-subscriptions' );
$button_url          = 'https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=pms-subscription-plans&utm_medium=client-site&utm_campaign=pms-pause-subscriptions-addon#pricing';
$title               = __( 'Upgrade to pro to use Pause Subscription Addon', 'paid-member-subscriptions' );
$upsell_state        = 'license_gated';
$button_target_attrs = ' target="_blank" rel="noopener noreferrer"';

if ( ! class_exists( 'PMS_IN_PS' ) ) {
    /*
    if ( $license_status === 'valid' && defined( 'PMS_PAID_PLUGIN_DIR' ) ) {
        $button_label        = __( 'Activate Add-On', 'paid-member-subscriptions' );
        $button_url          = admin_url( 'admin.php?page=pms-addons-page' );
        $title               = __( 'Activate Pause Subscription Addon', 'paid-member-subscriptions' );
        $upsell_state        = 'inactive_addon';
        $button_target_attrs = '';
    }
    ?>
    <div class="pms-gcr-upsell pms-pause-upsell" data-upsell-state="<?php echo esc_attr( $upsell_state ); ?>">
        <div class="pms-gcr-upsell__content">
            <p class="pms-gcr-upsell__eyebrow"><?php esc_html_e( 'Premium Addon', 'paid-member-subscriptions' ); ?></p>
            <h3 class="pms-gcr-upsell__title"><?php echo esc_html( $title ); ?></h3>
            <p class="pms-gcr-upsell__description"><?php esc_html_e( 'Allow members to pause recurring subscriptions with flexible duration limits, pause frequency controls, and resume behavior tailored to each plan.', 'paid-member-subscriptions' ); ?></p>
            <a class="button-primary pms-gcr-upsell__button" href="<?php echo esc_url( $button_url ); ?>"<?php echo $button_target_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $button_label ); ?></a>
        </div>
        <div class="pms-gcr-upsell__media">
            <div class="pms-gcr-upsell__artwork">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL . 'assets/images/addons/pms-add-on-pause-subscriptions-logo.png' ); ?>" alt="<?php esc_attr_e( 'Pause Subscriptions', 'paid-member-subscriptions' ); ?>" class="pms-gcr-upsell__image" />
            </div>
        </div>
    </div>
    <?php
    */
    return;
}
?>
<?php wp_nonce_field( 'pms_subscription_plan_pause_nonce', 'pms_subscription_plan_pause_nonce' ); ?>
<?php do_action( 'pms_subscription_plan_pause_top', $subscription_plan ); ?>
<?php do_action( 'pms_subscription_plan_pause_bottom', $subscription_plan ); ?>
