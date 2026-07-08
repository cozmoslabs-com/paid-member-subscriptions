<?php

$capture_subscription_details_hook = function( $hook_name ) use ( $subscription_plan ) {
    ob_start();
    do_action( $hook_name, $subscription_plan->id );
    return ob_get_clean();
};

$user_role_bottom_output = $capture_subscription_details_hook( 'pms_view_meta_box_subscription_details_user_role_bottom' );

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<?php do_action( 'pms_subscription_plan_access_behavior_top', $subscription_plan->id ); ?>

<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">
    <label for="pms-subscription-plan-user-role" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'User role', 'paid-member-subscriptions' ); ?></label>

    <select id="pms-subscription-plan-user-role" name="pms_subscription_plan_user_role">
        <?php
            if ( ! pms_user_role_exists( 'pms_subscription_plan_' . $subscription_plan->id ) ) {
                echo '<option value="create-new">' . esc_html__( '... Create new User Role', 'paid-member-subscriptions' ) . '</option>';
            } else {
                echo '<option value="pms_subscription_plan_' . esc_attr( $subscription_plan->id ) . '" ' . selected( 'pms_subscription_plan_' . $subscription_plan->id, $subscription_plan->user_role, false ) . '>' . esc_html( pms_get_user_role_name( 'pms_subscription_plan_' . $subscription_plan->id ) ) . '</option>';
            }
        ?>

        <?php foreach ( pms_get_user_role_names() as $role_slug => $role_name ) : ?>
            <option value="<?php echo esc_attr( $role_slug ); ?>" <?php selected( $role_slug, $subscription_plan->user_role, true ); ?>><?php echo esc_html( $role_name ); ?></option>
        <?php endforeach; ?>
    </select>

    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Create a new User Role from this Subscription Plan or select which User Role to associate with this Subscription Plan.', 'paid-member-subscriptions' ); ?></p>
</div>

<?php echo $user_role_bottom_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php do_action( 'pms_subscription_plan_access_behavior_bottom', $subscription_plan->id ); ?>
