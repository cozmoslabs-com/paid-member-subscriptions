<?php

$capture_subscription_details_hook = function( $hook_name ) use ( $subscription_plan ) {
    ob_start();
    do_action( $hook_name, $subscription_plan->id );
    return ob_get_clean();
};

$details_top_output         = $capture_subscription_details_hook( 'pms_view_meta_box_subscription_details_top' );
$description_bottom_output  = $capture_subscription_details_hook( 'pms_view_meta_box_subscription_details_description_bottom' );
$duration_bottom_output     = $capture_subscription_details_hook( 'pms_view_meta_box_subscription_details_duration_bottom' );

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

    /*
     * HTML output for subscription plan details meta-box
     */
?>

<?php wp_nonce_field( 'pms_subscription_details_nonce', 'pms_subscription_details_nonce' ); ?>

<?php do_action( 'pms_subscription_plan_general_top', $subscription_plan->id ); ?>

<?php echo $details_top_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<!-- Description -->
<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

    <label for="pms-subscription-plan-description" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Description', 'paid-member-subscriptions' ); ?></label>

    <textarea id="pms-subscription-plan-description" name="pms_subscription_plan_description" class="widefat" placeholder="<?php esc_html_e( 'Write description', 'paid-member-subscriptions' ); ?>"><?php echo esc_html( $subscription_plan->description ); ?></textarea>
    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'A description for this subscription plan. This will be displayed on the register form.', 'paid-member-subscriptions' ); ?></p>

</div>

<?php echo $description_bottom_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<!-- Duration -->
<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper" id="pms-subscription-plan-duration-field">

    <label for="pms-subscription-plan-duration" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Duration', 'paid-member-subscriptions' ); ?></label>

    <input type="text" id="pms-subscription-plan-duration" name="pms_subscription_plan_duration" value="<?php echo esc_attr( $subscription_plan->duration ); ?>" />

    <select id="pms-subscription-plan-duration-unit" name="pms_subscription_plan_duration_unit">
        <option value="day"   <?php selected( 'day', $subscription_plan->duration_unit, true ); ?>><?php esc_html_e( 'Day(s)', 'paid-member-subscriptions' ); ?></option>
        <option value="week"  <?php selected( 'week', $subscription_plan->duration_unit, true ); ?>><?php esc_html_e( 'Week(s)', 'paid-member-subscriptions' ); ?></option>
        <option value="month" <?php selected( 'month', $subscription_plan->duration_unit, true ); ?>><?php esc_html_e( 'Month(s)', 'paid-member-subscriptions' ); ?></option>
        <option value="year"  <?php selected( 'year', $subscription_plan->duration_unit, true ); ?>><?php esc_html_e( 'Year(s)', 'paid-member-subscriptions' ); ?></option>
    </select>
    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Set the subscription duration. Leave 0 for unlimited.', 'paid-member-subscriptions' ); ?></p>

</div>

<?php echo $duration_bottom_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<?php do_action( 'pms_subscription_plan_general_bottom', $subscription_plan->id ); ?>
