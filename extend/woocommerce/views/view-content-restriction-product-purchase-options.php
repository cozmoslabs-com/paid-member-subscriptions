<?php
/*
 * HTML output for content restriction meta-box regarding product purchase options
 */
?>

<!-- Who Can Purchase? options -->
<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-checkbox-list-wrapper">
    <label class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Who can purchase?', 'paid-member-subscriptions' ); ?></label>

    <?php
    $user_status          = get_post_meta( $post_id, 'pms-purchase-restrict-user-status', true );
    $subscription_plans   = pms_get_subscription_plans();
    usort($subscription_plans, 'pms_compare_subscription_plan_objects');
    $selected_subscription_plans = get_post_meta( $post_id, 'pms-purchase-restrict-subscription-plan' );
    ?>

    <div class="cozmoslabs-checkbox-list cozmoslabs-checkbox-multi-col-list">

        <div class="cozmoslabs-chckbox-container">
            <input type="checkbox" value="loggedin" <?php if( ! empty( $user_status ) ) checked($user_status, 'loggedin' ); ?> name="pms-purchase-restrict-user-status" id="pms-purchase-restrict-user-status">
            <label class="pms-meta-box-checkbox-label" for="pms-purchase-restrict-user-status"><?php esc_html_e( 'Logged In Users', 'paid-member-subscriptions' ); ?></label>
        </div>

        <?php if( !empty( $subscription_plans ) ): foreach( $subscription_plans as $subscription_plan ): ?>

            <div class="cozmoslabs-chckbox-container">
                <input type="checkbox" value="<?php echo esc_attr( $subscription_plan->id ); ?>" <?php if( in_array( $subscription_plan->id, $selected_subscription_plans ) ) echo 'checked="checked"'; ?> name="pms-purchase-restrict-subscription-plan[]" id="pms-purchase-restrict-subscription-plan-<?php echo esc_attr( $subscription_plan->id ) ?>">
                <label class="pms-meta-box-checkbox-label" for="pms-purchase-restrict-subscription-plan-<?php echo esc_attr( $subscription_plan->id ) ?>"><?php echo esc_html($subscription_plan->name); ?></label>
            </div>

        <?php endforeach; ?>
    </div>

        <p class="cozmoslabs-description cozmoslabs-description-space-left" style="margin-top: 10px;">
            <?php esc_html_e( 'Select who can purchase this product.', 'paid-member-subscriptions' ); ?>
        </p>

    <?php endif; ?>

</div>
