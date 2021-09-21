<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for the payments admin edit and add new payment page
 */

    // Get current actions
    $action = !empty( $_GET['pms-action'] ) ? sanitize_text_field( $_GET['pms-action'] ) : '';

    if( empty($action) )
        return;


    // Set some defaults for add_new payment
    $default_data = array(
        'pms-payment-date'              => date( 'Y-m-d H:i:s' ),
        'pms-payment-status'            => 'active',
        'pms-payment-subscription-id'   => '0',
        'pms-payment-status'            => 'completed'
    );

    if( !empty( $_POST ) )
        $form_data = array_merge( $default_data, pms_array_sanitize_text_field($_POST));
    else
        $form_data = $default_data;

    $payment_id = !empty($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;

    // If we edit an existing payment, grab payment and member data
    if ($action == 'edit_payment') {

        $payment = pms_get_payment($payment_id);

        // Display nothing if this is not a valid payment
        if (!$payment->is_valid())
            return;

        $member = pms_get_member($payment->user_id);
    }
?>

<div class="wrap">
    <h1><?php ($action == 'edit_payment' ) ? printf( esc_html__( 'Payment #%s', 'paid-member-subscriptions' ), esc_html( $payment_id ) ) : esc_html_e('Add New Payment', 'paid-member-subscriptions');  ?></h1>
</div>

<div class="pms-wrap-flex">

    <div class="">

        <?php $url = ( $action == 'add_payment' ) ? add_query_arg( array( 'pms-action' => 'add_payment'), admin_url( 'admin.php?page=pms-payments-page' ) ) : admin_url( 'admin.php?page=pms-payments-page' ); ?>

        <form id="pms-form-<?php echo ( $action == 'edit_payment' ? 'edit' : 'add' ); ?>-payment" class="pms-form" method="POST" action="<?php echo esc_url( $url ); ?>">

            <!-- Hidden fields -->
            <input type="hidden" name="pms-action" value="<?php echo esc_attr( $action ); ?>" />
            <input type="hidden" name="payment_id" value="<?php echo esc_attr( $payment_id ); ?>" />

            <!-- User's Username -->
            <div class="pms-form-field-wrapper pms-form-field-user-name">

                <label class="pms-form-field-label"><?php echo esc_html__( 'Username', 'paid-member-subscriptions' ); ?></label>

                <?php if ($action == 'add_payment') { ?>
                    <?php
                    $users = pms_count_users();

                    if( $users < apply_filters( 'pms_add_new_payment_select_user_limit', '8000' ) ) : ?>
                        <select id="pms-member-username" name="pms-member-username" class="widefat pms-chosen">
                            <option value=""><?php esc_html_e( 'Select...', 'paid-member-subscriptions' ); ?></option>
                            <?php
                            $users = get_users();

                            foreach( $users as $user ) {
                                echo '<option ' . ( ! empty( $form_data['pms-member-username'] ) ? selected( $form_data['pms-member-username'], $user->ID, false ) : '' ) . ' value="' . esc_attr( $user->ID ) . '">' . esc_html( $user->data->user_login ) . '</option>';
                            }
                            ?>
                        </select>

                        <p class="description"><?php printf( esc_html__( 'Select the username you wish to associate a subscription plan with. You can create a new user <a href="%s">here</a>.', 'paid-member-subscriptions' ), esc_url( admin_url('user-new.php') ) ); ?></p>
                    <?php else : ?>
                        <label for="pms-member-username-input"><?php esc_html_e( 'Username', 'paid-member-subscriptions' ) ?></label>
                        <input type="text" id="pms-member-username-input" name="pms-member-username" value="<?php echo !empty( $form_data['pms-member-username'] ) ? esc_attr( $form_data['pms-member-username'] ) : ''; ?>" />

                        <p class="description"><?php printf( esc_html__( 'Enter the username you wish to associate a payment with. You can create a new user <a href="%s">here</a>.', 'paid-member-subscriptions' ), esc_url( admin_url('user-new.php') ) ); ?></p>
                    <?php endif; ?>

                    <input type="hidden" id="pms-member-user-id" name="user_id" class="widefat" value="<?php echo ( ! empty( $form_data['user_id'] ) ? esc_attr( $form_data['user_id'] ) : 0 ); ?>" />

                <?php } else { ?>

                    <strong><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pms-members-page', 'pms-action' => 'edit_member', 'member_id' => $payment->user_id, 'subpage' => 'edit_member' ), admin_url( 'admin.php' ) ) ) ?>" title="<?php esc_html_e( 'Edit Member', 'paid-member-subscriptions' ); ?>"><?php echo esc_html( $member->username ); ?></a></strong>

                <?php } ?>

            </div>

            <!-- Payment Subscription -->
            <div class="pms-form-field-wrapper">

                <label for="pms-payment-subscription-id" class="pms-form-field-label"><?php esc_html_e( 'Subscription', 'paid-member-subscriptions' ); ?></label>

                <select id="pms-payment-subscription-id" name="pms-payment-subscription-id" class="medium">
                    <?php
                    $subscription_plans = pms_get_subscription_plans();

                    if ( $action == 'add_payment' ) {
                        echo '<option value="0">' . esc_html__('Choose...', 'paid-member-subscriptions') . '</option>';
                    }

                    foreach( $subscription_plans as $subscription_plan ) {
                        $selected = ( $action == 'add_payment' ) ? selected( $form_data['pms-payment-subscription-id'], $subscription_plan->id, false ) : selected( $payment->subscription_id, $subscription_plan->id, false );
                        echo '<option ' . esc_attr( $selected ) .  ' value="' . esc_attr( $subscription_plan->id ) . '">' . esc_html( $subscription_plan->name ) . '</option>';
                    }
                    ?>
                </select>

            </div>


            <!-- Payment Amount -->
            <?php
            $currency_symbol = pms_get_currency_symbol( pms_get_active_currency() );
            if ( $action == 'edit_payment' )
                $amount = $payment->amount;
            else
                $amount = ( !empty($form_data['pms-payment-amount']) ) ? $form_data['pms-payment-amount'] : '0';
            ?>

            <div class="pms-form-field-wrapper">

                <label for="pms-payment-amount" class="pms-form-field-label"><?php printf( esc_html__( 'Amount (%s)', 'paid-member-subscriptions' ), esc_html( $currency_symbol ) ); ?></label>
                <input type="text" id="pms-payment-amount" name="pms-payment-amount" class="medium" value="<?php echo esc_attr( $amount ) ?>" />

            </div>


            <!-- Payment Discount Code -->
            <?php if( ( $action == 'edit_payment' ) && !empty( $payment->discount_code ) ): ?>

            <div class="pms-form-field-wrapper">

                <label for="pms-payment-discount" class="pms-form-field-label"><?php esc_html_e( 'Discount Code', 'paid-member-subscriptions' ); ?></label>
                <span class="readonly medium"><strong><?php echo esc_html( $payment->discount_code ); ?></strong></span>

            </div>
            <?php endif; ?>


            <!-- Payment Date -->
            <?php $payment_date = ($action == 'edit_payment') ? date( 'Y-m-d H:i:s', strtotime( $payment->date ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) : $form_data['pms-payment-date'] ?>

            <div class="pms-form-field-wrapper">

                <label for="pms-payment-date" class="pms-form-field-label"><?php echo esc_html__( 'Date', 'paid-member-subscriptions' ); ?></label>
                <input type="text" id="pms-payment-date" name="pms-payment-date" class="datepicker medium" value="<?php echo esc_attr( $payment_date ); ?>" />

            </div>


            <!-- Payment Type -->
            <div class="pms-form-field-wrapper">

                <label for="pms-payment-type" class="pms-form-field-label"><?php esc_html_e( 'Type', 'paid-member-subscriptions' ); ?></label>

                <?php
                    $payment_types = pms_get_payment_types();
                ?>

                <?php if( $action == 'add_payment' ) : ?>

                    <span class="readonly medium"><strong><?php esc_html_e('Manual Payment', 'paid-member-subscriptions' ); ?><strong></span>
                    <input type="hidden" name="pms-payment-type" value="manual_payment" />

                <?php else : ?>

                    <span class="readonly medium"><strong><?php echo ( !empty( $payment->type ) && !empty( $payment_types[ $payment->type ] ) ? esc_html( $payment_types[ $payment->type ] ) : '-' ); ?></strong></span>

                <?php endif; ?>

            </div>


            <!-- Payment Transaction ID -->
            <?php if ( $action == 'edit_payment' )
                $transaction_id = ( !empty( $payment->transaction_id ) ) ? $payment->transaction_id : '';
            else
                $transaction_id = ( !empty( $form_data['pms-payment-transaction-id'] ) ) ? $form_data['pms-payment-transaction-id'] : ''; ?>

            <div class="pms-form-field-wrapper">

                <label for="pms-payment-transaction-id" class="pms-form-field-label"><?php esc_html_e( 'Transaction ID', 'paid-member-subscriptions' ); ?></label>

                <input type="text" id="pms-payment-transaction-id" name="pms-payment-transaction-id" class="widefat" value="<?php echo esc_attr( $transaction_id ); ?>" />

                <?php if( ( $action == 'edit_payment') && empty( $payment->transaction_id ) && $payment->payment_gateway != 'manual' ): ?>

                    <p class="description"><?php esc_html_e( 'The Transaction ID will be provided by the payment gateway when the payment is registered within their system.', 'paid-member-subscriptions' ); ?></p>

                <?php endif; ?>

            </div>


            <!-- Payment Status -->
            <div class="pms-form-field-wrapper">

                <label for="pms-payment-status" class="pms-form-field-label"><?php esc_html_e( 'Status', 'paid-member-subscriptions' ); ?></label>

                <select id="pms-payment-status" name="pms-payment-status" class="medium">
                    <?php
                    $statuses = pms_get_payment_statuses();

                    $payment_status = ($action == 'edit_payment') ? $payment->status : $form_data['pms-payment-status'];

                    foreach( $statuses as $status_slug => $status_name ) {
                        echo '<option ' . selected( $payment_status, $status_slug, false ) . ' value="' . esc_attr( $status_slug ) . '">' . esc_html( $status_name ) . '</option>';
                    }
                    ?>
                </select>

            </div>

            <!-- Payment Gateway -->
            <?php
            if ( $action == 'edit_payment' ) :
                $gateways = pms_get_payment_gateways();
            ?>
                <div class="pms-form-field-wrapper pms-form-field-gateway">

                    <label class="pms-form-field-label"><?php esc_html_e( 'Gateway', 'paid-member-subscriptions' ); ?></label>
                    <span class="readonly medium"><strong><?php echo ( !empty( $payment->payment_gateway ) && !empty( $gateways[ $payment->payment_gateway ] ) ? esc_html( $gateways[ $payment->payment_gateway ]['display_name_admin'] ) : '-' ); ?></strong></span>

                </div>
            <?php endif; ?>

            <!-- Payment IP Address -->
            <?php if ( $action == 'edit_payment' ) : ?>
            <div class="pms-form-field-wrapper pms-form-field-ip-address">

                <label class="pms-form-field-label"><?php esc_html_e( 'IP Address', 'paid-member-subscriptions' ); ?></label>
                <span class="readonly medium"><strong><?php echo ( !empty( $payment->ip_address ) ? esc_html( $payment->ip_address ) : '-' ); ?></strong></span>

            </div>
            <?php endif; ?>

            <?php
            if ( $action == 'edit_payment' )
                do_action( 'pms_payment_edit_form_field', $payment, $member );
            else
                do_action( 'pms_payment_add_new_form_field' );
            ?>

            <?php wp_nonce_field( 'pms_payment_nonce' ); ?>

            <!-- Submit button and Cancel button -->
            <?php
                $submit_text = ( $action == 'edit_payment' ) ? esc_html__( 'Save Payment', 'paid-member-subscriptions' ) : esc_html__( 'Add Payment', 'paid-member-subscriptions' );
                $submit_name = ( $action == 'edit_payment' ) ? 'submit_edit_payment' : 'submit_add_payment';
            ?>

            <p class="submit">
                <?php submit_button( $submit_text, 'primary', $submit_name, false ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=pms-payments-page' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'paid-member-subscriptions' ); ?></a>
            </p>

        </form>
    </div>

    <?php if ( isset( $_GET['pms-action'] ) && $_GET['pms-action'] == 'edit_payment' ) : ?>
        <div class="pms-payment-logs">
            <h3><?php esc_html_e( 'Payment Logs', 'paid-member-subscriptions' ); ?></h3>

            <?php
                $payment_logs_table = new PMS_Payments_Log_List_Table( $member->user_id );
                $payment_logs_table->prepare_items();
                $payment_logs_table->display();
            ?>
        </div>
    <?php endif; ?>

</div>

<div class="pms-modal">
    <div class="pms-modal__holder">
    </div>
</div>
