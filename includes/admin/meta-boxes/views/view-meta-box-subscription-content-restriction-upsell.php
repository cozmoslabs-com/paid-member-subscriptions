<?php
/*
 * HTML output for Global Content Restriction upsell fallback.
 */

$license_status      = pms_get_serial_number_status();
$button_label        = __( 'Upgrade to Pro', 'paid-member-subscriptions' );
$button_url          = 'https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=pms-subscription-plans&utm_medium=client-site&utm_campaign=pms-global-content-restriction-addon#pricing';
$title               = __( 'Upgrade to pro to use Global Content Restriction Addon', 'paid-member-subscriptions' );
$upsell_state        = 'license_gated';
$button_target_attrs = ' target="_blank" rel="noopener noreferrer"';

if ( $pms_gcr_upsell_context === 'inactive_addon' && $license_status === 'valid' && defined( 'PMS_PAID_PLUGIN_DIR' ) ) {
    $button_label        = __( 'Activate Add-On', 'paid-member-subscriptions' );
    $button_url          = admin_url( 'admin.php?page=pms-addons-page' );
    $title               = __( 'Activate Global Content Restriction Addon', 'paid-member-subscriptions' );
    $upsell_state        = 'inactive_addon';
    $button_target_attrs = '';
}
?>

<div class="pms-gcr-upsell" data-upsell-state="<?php echo esc_attr( $upsell_state ); ?>">
    <div class="pms-gcr-upsell__content">
        <p class="pms-gcr-upsell__eyebrow"><?php esc_html_e( 'Premium Addon', 'paid-member-subscriptions' ); ?></p>
        <h3 class="pms-gcr-upsell__title"><?php echo esc_html( $title ); ?></h3>
        <p class="pms-gcr-upsell__description"><?php esc_html_e( 'It provides an easy way to restrict content globally per subscription, instead of doing it for each Page, Post, or Custom Post Type individually.', 'paid-member-subscriptions' ); ?></p>
        <div class="pms-gcr-upsell__actions">
            <a class="button-primary pms-gcr-upsell__button" href="<?php echo esc_url( $button_url ); ?>"<?php echo $button_target_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $button_label ); ?></a>
            <a class="button-secondary pms-gcr-upsell__button pms-gcr-upsell__button-secondary" href="<?php echo esc_url( 'https://www.cozmoslabs.com/add-ons/global-content-restriction/' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn More', 'paid-member-subscriptions' ); ?></a>
        </div>
    </div>
    <div class="pms-gcr-upsell__media">
        <div class="pms-gcr-upsell__artwork">
            <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL . 'assets/images/addons/pms-add-on-global-content-restriction-logo.png' ); ?>" alt="<?php esc_attr_e( 'Global Content Restriction', 'paid-member-subscriptions' ); ?>" class="pms-gcr-upsell__image" />
        </div>
    </div>
</div>
