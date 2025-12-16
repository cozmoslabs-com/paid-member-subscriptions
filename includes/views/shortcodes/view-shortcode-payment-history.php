<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * User's payment history table
 */
?>

<?php

    $number_per_page = $args['number_per_page'];

    if (!is_numeric($number_per_page) || empty($number_per_page)){
        $number_per_page = 10;
    }

    $page = ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : ( get_query_var( 'page' ) ? get_query_var( 'page' ) : 1 ) );

    $args = apply_filters( 'pms_payment_history_shortcode_args', array(
        'order'   => 'DESC',
        'user_id' => $user_id,
        'number'  => $number_per_page,
        'offset'  => ( $page !== 1 ? ( $page - 1 ) * $number_per_page : '' )
    ));

    $payments = pms_get_payments( $args );

    $payment_statuses = pms_get_payment_statuses();

?>

<?php if( !empty( $payments ) ): // Handle no payments situation ?>
<table id="pms-payment-history" class="pms-table">

    <thead>
        <tr>
            <th class="pms-payment-id"><?php esc_html_e( 'ID', 'paid-member-subscriptions' ); ?></th>
            <th class="pms-payment-subscription-plan"><?php esc_html_e( 'Subscription Plan', 'paid-member-subscriptions' ); ?></th>
            <th class="pms-payment-amount"><?php esc_html_e( 'Amount', 'paid-member-subscriptions' ); ?></th>
            <th class="pms-payment-date"><?php esc_html_e( 'Date / Time', 'paid-member-subscriptions' ); ?></th>
            <th class="pms-payment-status"><?php esc_html_e( 'Status', 'paid-member-subscriptions' ); ?></th>

            <?php do_action( 'pms_payment_history_table_header', $user_id, $payments ); ?>
        </tr>
    </thead>

    <tbody>
        <?php foreach( $payments as $payment ):
            $subscription_plan = pms_get_subscription_plan( $payment->subscription_id );
            $currency = !empty( $payment->currency ) ? $payment->currency : pms_get_active_currency();
            ?>
            <tr>
                <td class="pms-payment-id"><?php echo '#' . esc_html( apply_filters( 'pms_payment_history_id', $payment->id, $payment ) ); ?></td>
                <td class="pms-payment-subscription-plan"><?php echo wp_kses_post( apply_filters( 'pms_payment_history_subscription_plan', $subscription_plan->name, $subscription_plan, $payment ) ); ?></td>
                <td class="pms-payment-amount" title='<?php apply_filters( 'pms_payment_history_amount_row_title', '', $payment ); ?>'><?php echo esc_html( pms_format_price( $payment->amount, apply_filters( 'pms_payment_history_amount_row_currency', $currency, $payment->id ) ) ); ?></td>
                <td class="pms-payment-date"><?php echo esc_html( ucfirst( date_i18n( apply_filters( 'pms_payment_history_date_format', 'j F, Y H:i' ), strtotime( $payment->date ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ) ); ?></td>
                <td class="pms-payment-status status-<?php echo esc_html( $payment->status );?>"><?php echo ( ! empty( $payment_statuses[$payment->status] ) ? esc_html( $payment_statuses[$payment->status] ) : '' ); ?></td>

                <?php do_action( 'pms_payment_history_table_row', $user_id, $payment ); ?>
            </tr>
        <?php endforeach; ?>

        <?php do_action( 'pms_payment_history_table_body', $user_id, $payments ); ?>
    </tbody>

</table>

<?php echo pms_paginate_links( apply_filters( 'pms_payment_history_table_paginate_links', array( 'id' => 'pms-payment-history', 'current' => max( 1, $page ), 'total'   => ceil( pms_get_member_payments_count( $user_id ) / $number_per_page )  ) ) ); /*phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>

<?php else: // Add payments ?>
    <p class="pms-no-payments"><?php esc_html_e( 'No payments found', 'paid-member-subscriptions' ); ?></p>
<?php endif; // End of payments ?>
