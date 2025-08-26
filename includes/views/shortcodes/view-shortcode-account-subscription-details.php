<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * HTML output for the member subscription details
 *
 */
$subscriptions         = pms_get_member_subscriptions( array( 'user_id' => $user_id ) );
$subscription_statuses = pms_get_member_subscription_statuses();

do_action( 'pms_member_account_subscriptions_view_before_table', $subscriptions );

foreach( $subscriptions as $subscription ) :
	if ( is_null( $subscription ) )
		continue;

	$subscription_plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

	ob_start();
	?>

	<table class="pms-account-subscription-details-table pms-account-subscription-details-table__<?php echo esc_attr( $subscription->subscription_plan_id ); ?>">
		<tbody>

			<?php do_action( 'pms_subscriptions_table_before_rows', $subscription ); ?>

			<!-- Subscription plan -->
			<tr class="pms-account-subscription-details-table__plan">
				<td><?php esc_html_e( 'Subscription Plan', 'paid-member-subscriptions' ); ?></td>
				<td><?php echo esc_html( ! empty( $subscription_plan->name ) ? $subscription_plan->name : '' ); ?></td>
			</tr>

			<!-- Subscription status -->
			<tr class="pms-account-subscription-details-table__status">
				<td><?php esc_html_e( 'Status', 'paid-member-subscriptions' ); ?></td>
				<td class="status-<?php echo esc_html( $subscription->status ) ?>">
                    <?php echo ( ! empty( $subscription_statuses[$subscription->status] ) ? esc_html( $subscription_statuses[$subscription->status] ) : '' ); ?>
                    <?php echo ( $subscription->is_trial_period() ? ' (' . esc_html__( 'Trial', 'paid-member-subscriptions' ) . ')' : '' ); ?>
                    <?php echo ( !empty( $subscription->payment_profile_id ) ? ' (' . esc_html__( 'Auto-renewing', 'paid-member-subscriptions' ) . ')' : '' ); ?>
                </td>
			</tr>

            <!-- Subscription start date -->
            <tr class="pms-account-subscription-details-table__start-date">
                <td><?php esc_html_e( 'Start Date', 'paid-member-subscriptions' ); ?></td>
                <td><?php echo ( ! empty( $subscription->start_date ) ? esc_html( ucfirst( date_i18n( get_option('date_format'), strtotime( $subscription->start_date ) ) ) ) : '' ); ?></td>
            </tr>

            <!-- Subscription expiration date -->
			<?php if( empty( $subscription->billing_next_payment ) ) : ?>
	            <tr class="pms-account-subscription-details-table__expiration-date">
                    
                <?php if( !empty( $subscription->payment_profile_id ) && $subscription->status == 'active' ) : ?>
	                    <td><?php esc_html_e( 'Next Payment Date', 'paid-member-subscriptions' ); ?></td>
                    <?php else : ?>
                        <td><?php esc_html_e( 'Expiration Date', 'paid-member-subscriptions' ); ?></td>
                    <?php endif; ?>

	                <td><?php echo ( ! empty( $subscription->expiration_date ) ? esc_html( ucfirst( date_i18n( get_option('date_format'), strtotime( $subscription->expiration_date ) ) ) ) : esc_html__( 'Unlimited', 'paid-member-subscriptions' ) ); ?></td>
	            </tr>
			<?php endif; ?>

            <!-- Subscription Trial End Date -->
            <?php if( $subscription->is_trial_period() ): ?>
                <tr class="pms-account-subscription-details-table__trial">
                    <td><?php esc_html_e( 'Trial End Date', 'paid-member-subscriptions' ); ?></td>
                    <td><?php echo esc_html( ucfirst( date_i18n( get_option('date_format'), strtotime( $subscription->trial_end ) ) ) ); ?></td>
                </tr>
            <?php endif; ?>

            <!-- Subscription next payment -->
            <?php if( ! empty( $subscription->billing_next_payment ) && $subscription->status == 'active' ): ?>
            <tr>
                <td><?php esc_html_e( 'Next Payment', 'paid-member-subscriptions' ); ?></td>
				<td>
                    <?php
                        $billing_amount = $subscription->billing_amount;

                        // check if discount is saved as meta and apply it
                        // this is used to determine if the price of the first recurring payment needs to be discounted or not
                        $discount_id = pms_get_member_subscription_meta( $subscription->id, '_discount_code_id', true );

                        if( !empty( $discount_id ) && function_exists( 'pms_in_get_discount' ) ){
                            $discount = pms_in_get_discount( $discount_id );

                            $billing_amount = pms_in_calculate_discounted_amount( $billing_amount, $discount );
                        }

                    $extra_attributes = apply_filters( 'pms_subscription_next_payment_amount_extra_attributes', '', $subscription );

                    $next_payment_output = sprintf(
                        _x( '%s on %s', '[amount] on [date]', 'paid-member-subscriptions' ),
                        '<span ' . $extra_attributes . '>' . pms_format_price( $billing_amount, apply_filters( 'pms_account_next_payment_date_currency', pms_get_active_currency(), $subscription ) ) . '</span>',
                        ucfirst( date_i18n( get_option( 'date_format' ), strtotime( $subscription->billing_next_payment ) ) )
                    );

                    // Append billing cycle info if enabled and supported
                    if ( $subscription->has_installments() && pms_payment_gateway_supports_cycles( $subscription->payment_gateway ) ) {
                        $next_cycle = pms_get_member_subscription_billing_processed_cycles( $subscription->id ) + 1;
                        $next_payment_output .= ' (' . $next_cycle . ' of ' . $subscription->billing_cycles . ')';
                    }

                    echo wp_kses_post( $next_payment_output );

                    ?>
                </td>
            </tr>
            <?php endif; ?>

            <!-- Payment Method -->
            <?php 
                if( $subscription->is_auto_renewing() && pms_payment_gateways_support( array( $subscription->payment_gateway ), 'update_payment_method' ) && $subscription->status != 'pending' ) : 
                
                $payment_method_data = pms_get_member_subscription_payment_method_details( $subscription->id );
            ?>
                <tr>
                    <td><?php esc_html_e( 'Payment Method', 'paid-member-subscriptions' ); ?></td>
                    <td>
                        <div class="pms-account-subscription-details-table__payment-method">

                            <?php if( !empty( $payment_method_data ) ) : ?>
                                <div class="pms-account-subscription-details-table__payment-method__wrap">
                                    <span class="pms-account-subscription-details-table__payment-method__brand">
                                        <?php
                                        $assets_src = esc_url( PMS_PLUGIN_DIR_PATH ) . 'assets/images/card-icons/';

                                        if( !empty( $payment_method_data['pms_payment_method_type'] ) ) 
                                            echo file_get_contents( $assets_src . $payment_method_data['pms_payment_method_type'] . '.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                        ?>
                                    </span>

                                    <span class="pms-account-subscription-details-table__payment-method__number">
                                        <?php echo !empty( $payment_method_data['pms_payment_method_number'] ) ? '&bull;&bull;&bull;&bull; ' . esc_html( $payment_method_data['pms_payment_method_number'] ) : '' ?>
                                    </span>
                                    
                                    <span class="pms-account-subscription-details-table__payment-method__expiration">
                                        <?php esc_html_e( 'Expires:', 'paid-member-subscriptions' ) ?>
                                        <?php echo !empty( $payment_method_data['pms_payment_method_expiration_month'] ) ? esc_html( $payment_method_data['pms_payment_method_expiration_month'] ) . ' /' : '' ?>
                                        <?php echo !empty( $payment_method_data['pms_payment_method_expiration_year'] ) ? esc_html( $payment_method_data['pms_payment_method_expiration_year'] ) : '' ?>
                                    </span>
                                </div>
                                <?php else : 
                                    // If we don't deal with a traditional Card payment method, run a custom action so the payment gateway can add it's own output
                                    do_action( 'pms_account_subscription_details_table_payment_method_content', $subscription );
                                endif; ?>

                            <?php echo wp_kses_post( apply_filters( 'pms_output_subscription_plan_action_update_payment_method', '<a class="pms-account-subscription-action-link pms-account-subscription-action-link__update-payment-method" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'update_payment_method', 'subscription_id' => $subscription->id  ), pms_get_current_page_url( true ) ), 'pms_update_payment_method', 'pmstkn' ) ) . '" title="'. __( 'Update the payment method attached to a recurring subscription.', 'paid-member-subscriptions' ) .'">' . __( 'Update', 'paid-member-subscriptions' ) . '</a>', $subscription_plan, $subscription->to_array(), $member->user_id ) ); ?>

                        </div>
                    </td>
                </tr>
            <?php endif; ?>

            <!-- Subscription actions -->
            <tr class="pms-account-subscription-details-table__actions">
                <td><?php esc_html_e( 'Actions', 'paid-member-subscriptions' ); ?></td>
                <td>
                    <?php

                    if( $subscription_plan->status != 'inactive' ) {

                        if( $subscription->status != 'pending' ) {

                            // Number of days before expiration to show the renewal action
                            $renewal_display_time = apply_filters( 'pms_output_subscription_plan_action_renewal_time', 15 );

                            if( ( ( !$subscription_plan->is_fixed_period_membership() && $subscription_plan->duration != '0' ) || ( $subscription_plan->is_fixed_period_membership() && $subscription_plan->fixed_expiration_date != '' && $subscription_plan->fixed_period_renewal_allowed() ) ) && ( ! $subscription->is_auto_renewing() && strtotime( $subscription->expiration_date ) - time() < $renewal_display_time * DAY_IN_SECONDS ) || in_array( $subscription->status, array( 'canceled', 'expired' ) ) ){
                                $renew_plan_button = apply_filters( 'pms_output_subscription_plan_action_renewal', '<a class="pms-account-subscription-action-link pms-account-subscription-action-link__renew" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'renew_subscription', 'subscription_id' => $subscription->id, 'subscription_plan' => $subscription_plan->id ), pms_get_current_page_url( true ) ), 'pms_member_nonce', 'pmstkn' ) ) . '">' . __( 'Renew', 'paid-member-subscriptions' ) . '</a>', $subscription_plan, $subscription->to_array(), $member->user_id );

                                if( !empty( $renew_plan_button ) )
                                    echo wp_kses_post( $renew_plan_button );
                            }

                        } else {

                            if( $subscription_plan->price > 0 || $subscription_plan->has_sign_up_fee() ){
                                $retry_payment_button = apply_filters( 'pms_output_subscription_plan_pending_retry_payment', '<a class="pms-account-subscription-action-link pms-account-subscription-action-link__retry" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'retry_payment_subscription', 'subscription_plan' => $subscription_plan->id  ) ), 'pms_member_nonce', 'pmstkn' ) ) . '">' . __( 'Retry payment', 'paid-member-subscriptions' ) . '</a>', $subscription_plan, $subscription->to_array() );

                                if( !empty( $retry_payment_button ) )
                                    echo wp_kses_post( $retry_payment_button );
                            }

                        }

                    }

                    if( $subscription->status != 'pending' ) {

                        // Show the Change action if any other subscription plan besides the current one exists
                        $plans           = pms_get_subscription_plan_others( $user_id );
                        $plan_upgrades   = pms_get_subscription_plan_upgrades( $subscription_plan->id );
                        $plan_downgrades = pms_get_subscription_plan_downgrades( $subscription_plan->id );

                        // remove current plan
                        if( isset( $plans[$subscription->subscription_plan_id ] ) )
                            unset( $plans[$subscription->subscription_plan_id ] );

                        $payments_settings = get_option( 'pms_payments_settings' );

                        $change_action_name = __( 'Change', 'paid-member-subscriptions' );

                        if( !isset( $payments_settings['allow-downgrades'] ) && !isset( $payments_settings['allow-change'] ) )
                            $change_action_name = __( 'Upgrade', 'paid-member-subscriptions' );
                                                
                        // Display logic
                        $display_action = false;

                        if( ( !isset( $payments_settings['allow-downgrades'] ) && !isset( $payments_settings['allow-change'] ) ) && !empty( $plan_upgrades ) )
                            $display_action = true;
                        else if( ( !isset( $payments_settings['allow-downgrades'] ) && isset( $payments_settings['allow-change'] ) ) && ( !empty( $plans ) || !empty( $plan_upgrades ) ) )
                            $display_action = true;
                        else if( ( !isset( $payments_settings['allow-change'] ) && isset( $payments_settings['allow-downgrades'] ) ) && ( !empty( $plan_downgrades ) || !empty( $plan_upgrades ) ) )
                            $display_action = true;
                        else if( isset( $payments_settings['allow-change'] ) && ( !empty( $plans ) || !empty( $plan_upgrades ) ) )
                            $display_action = true;
                        else if( isset( $payments_settings['allow-downgrades'] ) && ( !empty( $plan_downgrades ) || !empty( $plan_upgrades ) ) )
                            $display_action = true;

                        if( $display_action === true ){
                            $change_plan_button = apply_filters( 'pms_output_subscription_plan_action_change', '<a class="pms-account-subscription-action-link pms-account-subscription-action-link__change" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'change_subscription', 'subscription_id' => $subscription->id, 'subscription_plan' => $subscription_plan->id ), pms_get_current_page_url( true ) ), 'pms_member_nonce', 'pmstkn' ) ) . '">' . $change_action_name . '</a>', $subscription_plan, $subscription->to_array(), $member->user_id );

                            if( !empty( $change_plan_button ) )
                                echo wp_kses_post( $change_plan_button );
                        }

                        if( !pms_is_https() )
                            $cancel_plan_button = apply_filters( 'pms_output_subscription_plan_action_cancel', '<span class="pms-account-subscription-action-link pms-account-subscription-action-link__cancel" title="'. __( 'This action is not available because your website doesn\'t have https enabled.', 'paid-member-subscriptions' ) .'">' . __( 'Cancel', 'paid-member-subscriptions' ) . '</span>', $subscription_plan, $subscription->to_array(), $member->user_id );
                        elseif( $subscription->status == 'active' && ( 
                                ( $subscription_plan->duration != '0' || ( $subscription_plan->is_fixed_period_membership() && $subscription_plan->fixed_expiration_date != '' ) )
                                && ( $subscription_plan->price > 0 )
                                ) ){
                            $cancel_plan_button = apply_filters( 'pms_output_subscription_plan_action_cancel', '<a class="pms-account-subscription-action-link pms-account-subscription-action-link__cancel" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'cancel_subscription', 'subscription_id' => $subscription->id  ), pms_get_current_page_url( true ) ), 'pms_member_nonce', 'pmstkn' ) ) . '" title="'. __( 'Cancels recurring payments for this subscription, letting it expire at the end of the current period.', 'paid-member-subscriptions' ) .'">' . __( 'Cancel', 'paid-member-subscriptions' ) . '</a>', $subscription_plan, $subscription->to_array(), $member->user_id );
                        }
                    }

                    if( !empty( $cancel_plan_button ) )
                        echo wp_kses_post( $cancel_plan_button );

					if( !pms_is_https() )
                        $abandon_plan_button = apply_filters( 'pms_output_subscription_plan_action_abandon', '<span class="pms-account-subscription-action-link pms-account-subscription-action-link__abandon" title="'. __( 'This action is not available because your website doesn\'t have https enabled.', 'paid-member-subscriptions' ) .'">' . __( 'Abandon', 'paid-member-subscriptions' ) . '</span>', $subscription_plan, $subscription->to_array(), $member->user_id );
                    else
                        $abandon_plan_button = apply_filters( 'pms_output_subscription_plan_action_abandon', '<a class="pms-account-subscription-action-link pms-account-subscription-action-link__abandon" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'pms-action' => 'abandon_subscription', 'subscription_id' => $subscription->id  ), pms_get_current_page_url( true ) ), 'pms_member_nonce', 'pmstkn' ) ) . '" title="'. __( 'Cancels recurring payments and then removes the subscription from your account immediately.', 'paid-member-subscriptions' ) .'">' . __( 'Abandon', 'paid-member-subscriptions' ) . '</a>', $subscription_plan, $subscription->to_array(), $member->user_id );

                    if( !empty( $abandon_plan_button ) )
                        echo wp_kses_post( $abandon_plan_button );

                    ?>

                    <?php do_action( 'pms_subscriptions_table_after_actions', $subscription, $subscription_plan, $member ); ?>

                </td>
            </tr>

			<?php do_action( 'pms_subscriptions_table_after_rows', $subscription ); ?>

		</tbody>
	</table>

<?php
	$subscription_row = ob_get_clean();

	$subscription_row = apply_filters( 'pms_member_account_subscriptions_view_row', $subscription_row, $subscription, $subscription_plan );

	echo $subscription_row;//phpcs:ignore  WordPress.Security.EscapeOutput.OutputNotEscaped

endforeach;

do_action( 'pms_member_account_subscriptions_view_after_table', $subscriptions );
?>
