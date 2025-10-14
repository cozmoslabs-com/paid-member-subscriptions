<?php
/*
 * HTML output for the Discount Codes details meta-box
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

?>

<?php do_action( 'pms_view_meta_box_discount_codes_top', $discount->id ); ?>

<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

    <label for="pms-discount-code" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Promotion Code / Voucher', 'paid-member-subscriptions' ); ?></label>

    <input type="text" id="pms-discount-code" name="pms_discount_code" value="<?php echo esc_attr( $discount->code ); ?>" />

    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Enter the code for the discount. For example: 50percent', 'paid-member-subscriptions' ); ?></p>

</div>

<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

    <label for="pms-discount-type" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Type', 'paid-member-subscriptions' ); ?></label>

    <select id="pms-discount-type" name="pms_discount_type">
        <option value="percent" <?php selected( 'percent', $discount->type, true ); ?>><?php esc_html_e( 'Percent', 'paid-member-subscriptions' ); ?></option>
        <option value="fixed" <?php selected( 'fixed', $discount->type, true ); ?>><?php esc_html_e( 'Fixed amount', 'paid-member-subscriptions' ); ?></option>
    </select>
    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'The type of discount to apply for the purchase.', 'paid-member-subscriptions' ); ?></p>

</div>


<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

    <label for="pms-discount-amount" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Amount', 'paid-member-subscriptions' ); ?></label>

    <input type="text" id="pms-discount-amount" name="pms_discount_amount" class="small" value="<?php echo esc_attr( $discount->amount ); ?>" /> <span class="pms-discount-currency"> <?php echo esc_html( pms_get_active_currency() ); ?></span>

    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Enter the discount amount.', 'paid-member-subscriptions' ); ?></p>

</div>

<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-checkbox-list-wrapper">

    <label for="pms-discount-subscriptions" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Subscription(s)', 'paid-member-subscriptions' ); ?></label>

    <?php
    // Check if there are any subscription plans
    if ( function_exists('pms_get_subscription_plans') ){

        $subscription_plans = pms_get_subscription_plans();

        if( !empty( $subscription_plans ) ) {

            echo '<div class="cozmoslabs-checkbox-list cozmoslabs-checkbox-4-col-list">';

            // Display active subscriptions
            foreach ( pms_get_subscription_plans() as $subscription_plan) {

                //Exclude free subscriptions as discounts don't make sense for them
                if ( $subscription_plan->price > 0 || $subscription_plan->sign_up_fee > 0 ) {

                    $checked = '';
                    if (in_array($subscription_plan->id, explode(',', $discount->subscriptions))) $checked = "checked";

                    echo '<div class="cozmoslabs-chckbox-container">';
                    echo '<input type="checkbox" id="subscription-' . esc_attr( $subscription_plan->id ) . '" name="pms_discount_subscriptions[]" ' . esc_attr( $checked ) . ' value="' . esc_attr( $subscription_plan->id ) . '" />';
                    echo '<label class="pms-meta-box-checkbox-label" for="subscription-' . esc_attr( $subscription_plan->id ) . '">' . esc_html( $subscription_plan->name ) . ' </label>';
                    echo '</div>';
                }
            }

            echo '</div>';

            echo '<p class="cozmoslabs-description cozmoslabs-description-space-left">' . esc_html__( 'Select the subscription(s) to which the discount should be applied.', 'paid-member-subscriptions' ) . '</p>';

        } else {

            echo '<p class="cozmoslabs-description cozmoslabs-description-space-left">' . wp_kses_post( sprintf( __( 'You do not have any active Subscription Plans yet. Please create them <a href="%s">here</a>.', 'paid-member-subscriptions' ), esc_url( admin_url( 'edit.php?post_type=pms-subscription' ) ) ) ) . '</p>';

        }
    }
    ?>


</div>

<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

    <label for="pms-discount-max-uses" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Maximum Uses', 'paid-member-subscriptions' ); ?></label>

    <input type="text" id="pms-discount-max-uses" name="pms_discount_max_uses" class="small" value="<?php echo esc_attr( $discount->max_uses ); ?>" />

    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Maximum number of times this discount can be used (by any user). Enter 0 for unlimited.', 'paid-member-subscriptions' ); ?></p>

</div>


<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

    <label for="pms-discount-max-uses-per-user" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Limit Discount Uses Per User', 'paid-member-subscriptions' ); ?></label>

    <input type="text" id="pms-discount-max-uses-per-user" name="pms_discount_max_uses_per_user" class="small" value="<?php echo esc_attr( $discount->max_uses_per_user ); ?>" />

    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Maximum number of times this discount code can be used by the same user. Enter 0 for unlimited.', 'paid-member-subscriptions' ); ?></p>

</div>


<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

    <label for="pms-discount-start-date" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Start Date','paid-member-subscriptions' ); ?></label>

    <input type="text" id="pms-discount-start-date" name="pms_discount_start_date" class="pms_datepicker" value="<?php echo esc_attr( $discount->start_date ); ?>">

    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Select the start date for the discount (yyyy-mm-dd). Leave blank for no start date.', 'paid-member-subscriptions' ); ?></p>

</div>


<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

    <label for="pms-discount-expiration-date" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Expiration Date','paid-member-subscriptions' ); ?></label>

    <input type="text" id="pms-discount-expiration-date" name="pms_discount_expiration_date" class="pms_datepicker" value="<?php echo esc_attr( $discount->expiration_date ); ?>">

    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Select the expiration date for the discount (yyyy-mm-dd). Leave blank for no expiration.', 'paid-member-subscriptions' ); ?></p>

</div>


<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper">

    <label for="pms-discount-status" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Status', 'paid-member-subscriptions' ); ?></label>

    <select id="pms-discount-status" name="pms_discount_status">
        <option value="active" <?php selected( 'active', $discount->status, true  ); ?>><?php esc_html_e( 'Active', 'paid-member-subscriptions' ); ?></option>
        <option value="inactive" <?php selected( 'inactive', $discount->status, true  ); ?>><?php esc_html_e( 'Inactive', 'paid-member-subscriptions' ); ?></option>
    </select>
    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e('Select discount code status.', 'paid-member-subscriptions' ); ?></p>

</div>

<?php
    // Check if we have recurring payments enabled
    if ( pms_payment_gateways_support( pms_get_active_payment_gateways(), 'recurring_payments' ) ) {
?>
<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">

    <label for="pms-discount-recurring-payments" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'Recurring Payments','paid-member-subscriptions' ); ?></label>

    <div class="cozmoslabs-toggle-container">
        <input type="checkbox" id="pms-discount-recurring-payments" name="pms_discount_recurring_payments" <?php echo esc_attr( $discount->recurring_payments ); ?>  value="<?php echo esc_attr( $discount->recurring_payments ); ?>">
        <label class="cozmoslabs-toggle-track" for="pms-discount-recurring-payments"></label>
    </div>
    <div class="cozmoslabs-toggle-description">
        <label for="pms-discount-recurring-payments" class="cozmoslabs-description"><?php esc_html_e( 'Apply discount to all future recurring payments (not just the first one).', 'paid-member-subscriptions' ); ?></label>
    </div>

</div>
<?php } ?>

<div class="pms-meta-box-field-wrapper cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">

    <label for="pms-discount-new-users-only" class="pms-meta-box-field-label cozmoslabs-form-field-label"><?php esc_html_e( 'New Users Only','paid-member-subscriptions' ); ?></label>

    <div class="cozmoslabs-toggle-container">
        <input type="checkbox" id="pms-discount-new-users-only" name="pms_discount_new_users_only" <?php echo esc_attr( $discount->new_users_only ); ?>  value="<?php echo esc_attr( $discount->new_users_only ); ?>">
        <label class="cozmoslabs-toggle-track" for="pms-discount-new-users-only"></label>
    </div>
    <div class="cozmoslabs-toggle-description">
        <label for="pms-discount-new-users-only" class="cozmoslabs-description"><?php esc_html_e( 'Apply discount only for new users.', 'paid-member-subscriptions' ); ?></label>
    </div>


</div>

<?php do_action( 'pms_view_meta_box_discount_codes_bottom', $discount->id ); ?>
