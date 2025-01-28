<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * View for the Payments Summary meta-box in the WP Dashboard page
 */
?>

<?php

    // Get payments
    $today_payments = pms_get_payments( array( 'status' => 'completed', 'date' => date( 'Y-m-d' ), 'number' => '-1' ) );
    $month_payments = pms_get_payments( array( 'status' => 'completed', 'date' => array( date( 'Y-m-01' ) , date( 'Y-m-d' ) ), 'number' => '-1' ) );
    $recent_payments = pms_get_payments( array( 'status' => 'completed', 'order' => 'DESC', 'number' => 5 ) );

    // Calculate monthly income
    $month_income = 0;
    foreach( $month_payments as $payment )
        $month_income += $payment->amount;

    // Calculate today's income
    $today_income = 0;
    foreach( $today_payments as $payment )
        $today_income += $payment->amount;

?>

<div id="pms-payments-summary">

    <!-- This Month's Payments -->
    <div class="pms-month-income cozmoslabs-form-subsection-wrapper">

        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Current Month', 'paid-member-subscriptions' ); ?></h4>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Income:', 'paid-member-subscriptions' ); ?></label>
            <p><?php echo esc_html( pms_format_price( $month_income ) ); ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Payments:', 'paid-member-subscriptions' ); ?></label>
            <p><?php echo count( $month_payments ); ?></p>
        </div>



    </div>

    <!-- Today's Payments -->
    <div class="pms-today-income cozmoslabs-form-subsection-wrapper">

        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Today', 'paid-member-subscriptions' ); ?></h4>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Income:', 'paid-member-subscriptions' ); ?></label>
            <p><?php echo esc_html( pms_format_price( $today_income ) ); ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Payments:', 'paid-member-subscriptions' ); ?></label>
            <p><?php echo count( $today_payments ); ?></p>
        </div>

    </div>

    <!-- Recent Payments -->
    <div class="pms-recent-payments cozmoslabs-form-subsection-wrapper">

        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Recent Payments', 'paid-member-subscriptions' ); ?></h4>

        <?php if( !empty( $recent_payments ) ): ?>
        <?php foreach( $recent_payments as $payment ): ?>
            <?php $payment_user = get_userdata( $payment->user_id ); ?>
            <?php $payment_data = $payment->get_data( $payment->id ); ?>
            <?php $currency = !empty( $payment_data['currency'] ) ? $payment_data['currency'] : pms_get_payment_meta( $payment->id, 'currency', true ); ?>

            <div class="cozmoslabs-form-field-wrapper">
                <p><?php echo esc_html( $payment_user->user_login ) . ' (' . esc_html( $payment_user->user_email ) . ')' ?></p>
                <p class="pms-recent-payments-amount"><?php echo esc_html( pms_format_price( $payment->amount, $currency ) ); ?></p>
                <p class="cozmoslabs-description"><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pms-payments-page', 'pms-action' => 'edit_payment', 'payment_id' => $payment->id ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html__( 'View Details', 'paid-member-subscriptions' ); ?></a></p>
            </div>

        <?php endforeach; ?>

            <a class="pms-view-all-payments" href="<?php echo esc_url( add_query_arg( array( 'page' => 'pms-payments-page' ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html__( 'View All Payments', 'paid-member-subscriptions' ); ?></a>

        <?php else: ?>
            <div><?php esc_html_e( 'No payments found.', 'paid-member-subscriptions' ); ?></div>
        <?php endif; ?>
    </div>

</div>
