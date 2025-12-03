<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

    /*
     * HTML output for subscription plan details meta-box
     */
?>

<?php do_action( 'pms_view_meta_box_subscription_details_top', $subscription_plan->id ); ?>

<!-- Description -->
<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

    <label for="pms-subscription-plan-description" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Description', 'paid-member-subscriptions' ); ?></label>

    <textarea id="pms-subscription-plan-description" name="pms_subscription_plan_description" class="widefat" placeholder="<?php esc_html_e( 'Write description', 'paid-member-subscriptions' ); ?>"><?php echo esc_html( $subscription_plan->description ); ?></textarea>
    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'A description for this subscription plan. This will be displayed on the register form.', 'paid-member-subscriptions' ); ?></p>

</div>

<?php do_action( 'pms_view_meta_box_subscription_details_description_bottom', $subscription_plan->id ); ?>

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
    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Set the subscription duration. Leave 0 for unlimited.', 'paid-member-subscriptions' ); ?></p>

</div>

<?php do_action( 'pms_view_meta_box_subscription_details_duration_bottom', $subscription_plan->id ); ?>

<!-- Price -->
<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

    <label for="pms-subscription-plan-price" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Price', 'paid-member-subscriptions' ); ?></label>

    <input type="text" id="pms-subscription-plan-price" name="pms_subscription_plan_price" class="small" value="<?php echo esc_attr( $subscription_plan->price ); ?>" /> <strong><span id="pms-default-currency"><?php echo esc_html( pms_get_active_currency() ); ?></span></strong>

    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Amount you want to charge people who join this plan. Leave 0 if you want this plan to be free.', 'paid-member-subscriptions' ); ?></p>

</div>

<?php do_action( 'pms_view_meta_box_subscription_details_price_bottom', $subscription_plan->id ); ?>

<!-- Sign Up Fee -->
<?php if( pms_payment_gateways_support( pms_get_active_payment_gateways(), 'subscription_sign_up_fee' ) ) : ?>
    <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

        <label for="pms-subscription-plan-sign-up-fee" class="pms-meta-box-field-label cozmoslabs-form-field-label">
            <?php esc_html_e( 'Sign-up Fee', 'paid-member-subscriptions' ); ?>
            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/subscription-plans/?utm_source=pms-subscription-plans&utm_medium=client-site&utm_campaign=pms-sign-up-fee#Sign-up_Fee" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
        </label>

        <input type="text" id="pms-subscription-plan-sign-up-fee" name="pms_subscription_plan_sign_up_fee" class="small" value="<?php echo esc_attr( $subscription_plan->sign_up_fee ); ?>" /> <strong><?php echo esc_html( pms_get_active_currency() ); ?></strong>

        <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Amount you want to charge people upfront when subscribing to this plan.', 'paid-member-subscriptions' ); ?></p>

    </div>

    <?php do_action( 'pms_view_meta_box_subscription_details_sign_up_fee_bottom', $subscription_plan->id ); ?>
<?php endif; ?>

<!-- Free trial -->
<?php if( pms_payment_gateways_support( pms_get_active_payment_gateways(), 'subscription_free_trial' ) ) : ?>
    <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

        <label for="pms-subscription-plan-trial-duration" class="pms-meta-box-field-label cozmoslabs-form-field-label">
            <?php esc_html_e( 'Free Trial', 'paid-member-subscriptions' ); ?>
            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/subscription-plans/?utm_source=pms-subscription-plans&utm_medium=client-site&utm_campaign=pms-free-trial#Free_Trial" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
        </label>

        <input type="text" id="pms-subscription-plan-trial-duration" name="pms_subscription_plan_trial_duration" value="<?php echo esc_attr( $subscription_plan->trial_duration ); ?>" />

        <select id="pms-subscription-plan-trial-duration-unit" name="pms_subscription_plan_trial_duration_unit">
            <option value="day"   <?php selected( 'day', $subscription_plan->trial_duration_unit, true ); ?>><?php esc_html_e( 'Day(s)', 'paid-member-subscriptions' ); ?></option>
            <option value="week"  <?php selected( 'week', $subscription_plan->trial_duration_unit, true ); ?>><?php esc_html_e( 'Week(s)', 'paid-member-subscriptions' ); ?></option>
            <option value="month" <?php selected( 'month', $subscription_plan->trial_duration_unit, true ); ?>><?php esc_html_e( 'Month(s)', 'paid-member-subscriptions' ); ?></option>
            <option value="year"  <?php selected( 'year', $subscription_plan->trial_duration_unit, true ); ?>><?php esc_html_e( 'Year(s)', 'paid-member-subscriptions' ); ?></option>
        </select>
        <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'The free trial represents the amount of time before charging the first recurring payment. The sign-up fee applies regardless of the free trial.', 'paid-member-subscriptions' ); ?></p>

    </div>

    <?php do_action( 'pms_view_meta_box_subscription_details_free_trial_bottom', $subscription_plan->id ); ?>
<?php endif; ?>

<!-- Payment Installments -->
<?php if( pms_payment_gateways_support( pms_get_active_payment_gateways(), 'billing_cycles' ) ) : ?>

    <div id="payment-cycles">

        <!-- Payment Cycles Toggle -->
        <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">

            <label for="pms-limit-payment-cycles" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Limit Payment Cycles', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="pms-limit-payment-cycles" name="pms_subscription_plan_limit_payment_cycles" value="yes" <?php echo esc_attr( $subscription_plan->limit_payment_cycles ) === 'yes' ? checked( $subscription_plan->limit_payment_cycles, 'yes', false ) : ''; ?> />
                <label class="cozmoslabs-toggle-track" for="pms-limit-payment-cycles"></label>
            </div>

            <div class="cozmoslabs-toggle-description">
                <label for="pms-limit-payment-cycles" class="cozmoslabs-description"> <?php esc_html_e( 'Enable this option to let customers pay in installments.', 'paid-member-subscriptions' ) ?></label>
            </div>

        </div>

        <!-- Payment Cycle options -->
        <div id="pms-payment-cycle-options">

            <!-- Number of Payments -->
            <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper" id="pms-subscription-plan-number-of-payments-field">
                <label for="pms-subscription-plan-number-of-payments" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Number of Payments', 'paid-member-subscriptions' ) ?></label>

                <input type="number" id="pms-subscription-plan-number-of-payments" name="pms_subscription_plan_number_of_payments" value="<?php echo esc_attr( $subscription_plan->number_of_payments ) ?>" />

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Limit how many payments are made before the subscription ends.', 'paid-member-subscriptions' ); ?></p>
            </div>

            <!-- Status after last cycle -->
            <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper" id="pms-subscription-plan-status-after-last-cycle-field">
                <label for="pms-subscription-plan-status-after-last-cycle" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Status After Last Cycle', 'paid-member-subscriptions' ); ?></label>

                <select id="pms-subscription-plan-status-after-last-cycle" name="pms_subscription_plan_status_after_last_cycle">
                    <option value="expire" <?php echo ( empty( $subscription_plan->status_after_last_cycle ) || $subscription_plan->status_after_last_cycle == 'expire' ? 'selected' : '' ); ?> ><?php esc_html_e( 'Expire subscription', 'paid-member-subscriptions' ); ?></option>
                    <option value="unlimited" <?php echo ( !empty( $subscription_plan->status_after_last_cycle ) && ( $subscription_plan->status_after_last_cycle == 'unlimited' ) ? 'selected' : '' ); ?> ><?php esc_html_e( 'Unlimited subscription', 'paid-member-subscriptions' ); ?></option>
                    <option value="expire_after" <?php echo ( !empty( $subscription_plan->status_after_last_cycle ) && ( $subscription_plan->status_after_last_cycle == 'expire_after' ) ? 'selected' : '' ); ?> ><?php esc_html_e( 'Expire subscription after', 'paid-member-subscriptions' ); ?></option>
                </select>

                <!-- Expire After options -->
                <div class="cozmoslabs-form-field-wrapper cozmoslabs-description-align-right" id="pms-subscription-plan-expire-after-field">

                    <!-- Expire After duration -->
                    <input type="number" id="pms-subscription-plan-expire-after" name="pms_subscription_plan_expire_after" value="<?php echo esc_attr( $subscription_plan->expire_after ); ?>" />

                    <!-- Expire After duration unit -->
                    <select id="pms-subscription-plan-expire-after-unit" name="pms_subscription_plan_expire_after_unit">
                        <option value="day"   <?php selected( 'day', $subscription_plan->expire_after_unit, true ); ?>><?php esc_html_e( 'Day(s)', 'paid-member-subscriptions' ); ?></option>
                        <option value="week"  <?php selected( 'week', $subscription_plan->expire_after_unit, true ); ?>><?php esc_html_e( 'Week(s)', 'paid-member-subscriptions' ); ?></option>
                        <option value="month" <?php selected( 'month', $subscription_plan->expire_after_unit, true ); ?>><?php esc_html_e( 'Month(s)', 'paid-member-subscriptions' ); ?></option>
                        <option value="year"  <?php selected( 'year', $subscription_plan->expire_after_unit, true ); ?>><?php esc_html_e( 'Year(s)', 'paid-member-subscriptions' ); ?></option>
                    </select>
                </div>

                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Select what happens to a member’s subscription once the final billing cycle is completed.', 'paid-member-subscriptions' ); ?></p>
            </div>

        </div>

        <?php do_action( 'pms_view_meta_box_subscription_details_payment_installments_bottom', $subscription_plan->id ); ?>

    </div>

<?php endif; ?>

<!-- Renewal option -->
<?php if( pms_payment_gateways_support( pms_get_active_payment_gateways(), 'recurring_payments' ) ) : ?>

    <div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper" id="pms-subscription-plan-renewal-option-field">

        <label for="pms-subscription-plan-recurring" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Renewal', 'paid-member-subscriptions' ); ?></label>

        <select id="pms-subscription-plan-recurring" name="pms_subscription_plan_recurring">

            <option value="0" <?php echo ( empty( $subscription_plan->recurring ) ? 'selected' : '' ); ?> ><?php esc_html_e( 'Settings default', 'paid-member-subscriptions' ); ?></option>
            <option value="1" <?php echo ( isset( $subscription_plan->recurring ) && ( $subscription_plan->recurring == 1 ) ? 'selected' : '' ); ?> ><?php esc_html_e( 'Customer opts in for automatic renewal', 'paid-member-subscriptions' ); ?></option>
            <option value="2" <?php echo ( isset( $subscription_plan->recurring ) && ( $subscription_plan->recurring == 2 ) ? 'selected' : '' ); ?> ><?php esc_html_e( 'Always renew automatically', 'paid-member-subscriptions' ); ?></option>
            <option value="3" <?php echo ( isset( $subscription_plan->recurring ) && ( $subscription_plan->recurring == 3 ) ? 'selected' : '' ); ?> ><?php esc_html_e( 'Never renew automatically', 'paid-member-subscriptions' ); ?></option>

        </select>

        <p class="cozmoslabs-description cozmoslabs-description-align-right" id="pms-renewal-description"><?php esc_html_e( 'Select renewal type. You can either allow the customer to opt in, force automatic renewal or force no renewal.', 'paid-member-subscriptions' ); ?></p>

        <!-- Billing Cycles description -->
        <?php pms_output_billing_cycles_renewal_message( $subscription_plan ) ?>

    </div>

    <?php do_action( 'pms_view_meta_box_subscription_details_renewal_bottom', $subscription_plan->id ); ?>

<?php endif; ?>

<!-- Status -->
<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

    <label for="pms-subscription-plan-status" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Status', 'paid-member-subscriptions' ); ?></label>

    <select id="pms-subscription-plan-status" name="pms_subscription_plan_status">
        <option value="active" <?php selected( 'active', $subscription_plan->status, true  ); ?>><?php esc_html_e( 'Active', 'paid-member-subscriptions' ); ?></option>
        <option value="inactive" <?php selected( 'inactive', $subscription_plan->status, true  ); ?>><?php esc_html_e( 'Inactive', 'paid-member-subscriptions' ); ?></option>
    </select>
    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Only active subscription plans will be displayed to the user.', 'paid-member-subscriptions' ); ?></p>

</div>

<?php do_action( 'pms_view_meta_box_subscription_details_status_bottom', $subscription_plan->id ); ?>

<!-- User Role -->
<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

    <label for="pms-subscription-plan-user-role" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'User role', 'paid-member-subscriptions' ); ?></label>

    <select id="pms-subscription-plan-user-role" name="pms_subscription_plan_user_role">

        <?php
            if( !pms_user_role_exists( 'pms_subscription_plan_' . $subscription_plan->id ) )
                echo '<option value="create-new">' . esc_html__( '... Create new User Role', 'paid-member-subscriptions' ) . '</option>';
            else
                echo '<option value="pms_subscription_plan_' . esc_attr( $subscription_plan->id ) . '" ' . selected( 'pms_subscription_plan_' . $subscription_plan->id, $subscription_plan->user_role, false) . '>' . esc_html( pms_get_user_role_name( 'pms_subscription_plan_' . $subscription_plan->id ) ) . '</option>';
        ?>

        <?php foreach( pms_get_user_role_names() as $role_slug => $role_name ): ?>
            <option value="<?php echo esc_attr( $role_slug ); ?>" <?php selected( $role_slug, $subscription_plan->user_role, true); ?> ><?php echo esc_html( $role_name ); ?></option>
        <?php endforeach; ?>

    </select>
    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Create a new User Role from this Subscription Plan or select which User Role to associate with this Subscription Plan.', 'paid-member-subscriptions' ); ?></p>

</div>

<?php do_action( 'pms_view_meta_box_subscription_details_user_role_bottom', $subscription_plan->id ); ?>

<?php do_action( 'pms_view_meta_box_subscription_details_bottom', $subscription_plan->id ); ?>
