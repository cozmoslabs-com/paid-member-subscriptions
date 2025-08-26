<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Wrapper function to return a payment object
 *
 * @param int $payment_id
 *
 */
function pms_get_payment( $payment_id = 0 ) {

    return new PMS_Payment( $payment_id );

}

/**
 * Return payments filterable by an array of arguments
 *
 * @param array $args
 *
 * @return array
 *
 */
function pms_get_payments( $args = array() ) {

    global $wpdb;

    $defaults = array(
        'order'                         => 'DESC',
        'orderby'                       => 'id',
        'offset'                        => '',
        'status'                        => '',
        'type'                          => '',
        'currency'                      => '',
        'user_id'                       => '',
        'subscription_plan_id'          => '',
        'exclude_subscription_plan_ids' => '',
        'profile_id'                    => '',
        'transaction_id'                => '',
        'date'                          => '',
        'member_subscription_id'        => '',
        'payment_gateway'               => '',
        'search'                        => ''
    );

    $args = apply_filters( 'pms_get_payments_args', wp_parse_args( $args, $defaults ), $args, $defaults );

    // If Limit is empty, add a default one
    if ( empty( $args['number'] ) )
        $args['number'] = 10;

    // Start query string
    $query_string       = "SELECT pms_payments.* ";

    // Query string sections
    $query_from         = "FROM {$wpdb->prefix}pms_payments pms_payments ";
    
    $query_inner_join = '';
    if( !empty( $args['search'] ) ) {
        $query_inner_join .= "INNER JOIN {$wpdb->users} users ON pms_payments.user_id = users.id ";
        $query_inner_join .= "INNER JOIN {$wpdb->posts} posts ON pms_payments.subscription_plan_id = posts.id ";
    }

    if( !empty( $args['member_subscription_id'] ) )
        $query_inner_join .= "INNER JOIN {$wpdb->prefix}pms_paymentmeta payment_meta ON pms_payments.id = payment_meta.payment_id ";

    $query_where = "WHERE 1=%d ";

    // Add search query
    if( !empty($args['search']) ) {
        $search_term    = sanitize_text_field( $args['search'] );
        $query_where    = $query_where . " AND " . " ( pms_payments.discount_code LIKE '%s' OR pms_payments.transaction_id LIKE '%s' OR users.user_nicename LIKE '%%%s%%' OR users.user_login LIKE '%%%s%%' OR users.user_email LIKE '%%%s%%' OR users.display_name LIKE '%%%s%%' OR posts.post_title LIKE '%%%s%%' ) ". " ";
    }

    // Filter by status
    if( !empty( $args['status'] ) ) {
        if( is_array( $args['status'] ) ){
            $status = implode( '\',\'', array_map( 'sanitize_text_field', $args['status'] ) );
            $query_where    = $query_where . " AND " . " pms_payments.status IN ('{$status}')";
        }
        else{
            $status         = sanitize_text_field( $args['status'] );
            $query_where    = $query_where . " AND " . " pms_payments.status LIKE '{$status}'";
        }
    }

    /*
     * Filter by date
     * Can be filtered by - a single date that will return payments from that date
     *                    - an array with two dates that will return payments between the two dates
     */
    if( !empty( $args['date'] ) ) {

        if( is_array( $args['date'] ) && !empty( $args['date'][0] ) && !empty( $args['date'][1] ) ) {

            $args['date'][0] = sanitize_text_field( $args['date'][0] );
            $args['date'][1] = sanitize_text_field( $args['date'][1] );

            $query_where = $query_where . " AND " . " ( pms_payments.date BETWEEN '{$args['date'][0]}' AND '{$args['date'][1]}' )";

        } elseif( is_string( $args['date'] ) ) {

            $args['date'] = sanitize_text_field( $args['date'] );

            $query_where = $query_where . " AND " . " pms_payments.date LIKE '%%{$args['date']}%%'";

        }

    }

    // Filter by type
    if( !empty( $args['type'] ) ) {
        $type = sanitize_text_field( $args['type'] );
        $query_where    = $query_where . " AND " . " pms_payments.type LIKE '{$type}'";
    }

    // Filter by currency
    if( !empty( $args['currency'] ) ) {
        $currency = sanitize_text_field( $args['currency'] );
        $query_where    = $query_where . " AND " . " pms_payments.currency LIKE '{$currency}'";
    }

    // Filter by profile_id
    if( !empty( $args['profile_id'] ) ) {
        $profile_id = sanitize_text_field( $args['profile_id'] );
        $query_where    = $query_where . " AND " . " pms_payments.profile_id LIKE '{$profile_id}'";
    }

    // Filter by user_id
    if( !empty( $args['user_id'] ) ) {
        $user_id = (int)trim( $args['user_id'] );
        $query_where    = $query_where . " AND " . " pms_payments.user_id = {$user_id}";
    }

    // Filter by subscription_plan_id
    if( !empty( $args['subscription_plan_id'] ) ) {
        if( is_array( $args['subscription_plan_id'] ) ){
            $subscription_plan_ids = implode( ',', array_map('absint', $args['subscription_plan_id'] ) );
            $query_where          = $query_where . " AND " . " pms_payments.subscription_plan_id IN ({$subscription_plan_ids})";

        } else {
            $subscription_plan_id = (int)trim( $args['subscription_plan_id'] );
            $query_where          = $query_where . " AND " . " pms_payments.subscription_plan_id = {$subscription_plan_id}";

        }
    }

    if( !empty( $args['exclude_subscription_plan_ids'] ) ) {
        $subscription_plan_ids = implode( ',', array_map('absint', $args['exclude_subscription_plan_ids'] ) );
        $query_where          = $query_where . " AND " . " pms_payments.subscription_plan_id NOT IN ({$subscription_plan_ids})";
    }

    // Filter by transaction_id
    if( !empty( $args['transaction_id'] ) ) {
        $transaction_id = sanitize_text_field( $args['transaction_id'] );
        $query_where    = $query_where . " AND " . " pms_payments.transaction_id = '{$transaction_id}'";
    }

    // Filter by member_subscription_id
    if( !empty( $args['member_subscription_id'] ) ) {
        $member_subscription_id = absint( $args['member_subscription_id'] );
        $query_where  = $query_where . " AND " . " payment_meta.meta_key = 'subscription_id' AND payment_meta.meta_value = '{$member_subscription_id}'";
    }

    // Filter by payment_gateway
    if( !empty( $args['payment_gateway'] ) ) {
        $payment_gateway = sanitize_text_field( $args['payment_gateway'] );
        $query_where     = $query_where . " AND " . " pms_payments.payment_gateway LIKE '{$payment_gateway}'";
    }

    $query_order_by = '';
    // removed table name so it can be sanitized using the core function
    if ( !empty($args['orderby']) )
        $query_order_by = sanitize_text_field( $args['orderby'] ) . ' ';

    $query_order = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
    $query_order .= ' ';
    
    $query_order_string = " ORDER BY " . sanitize_sql_orderby( $query_order_by . $query_order );

    $query_limit        = '';
    if( $args['number'] && $args['number'] != '-1' )
        $query_limit    = 'LIMIT ' . (int)trim( $args['number'] ) . ' ';

    $query_offset       = '';
    if( $args['offset'] )
        $query_offset   = 'OFFSET ' . (int)trim( $args['offset'] ) . ' ';

    // Concatenate query string
    $query_string .= $query_from . $query_inner_join . $query_where . $query_order_string . $query_limit . $query_offset;

    // Return results
    if (!empty($search_term))
        $data_array = $wpdb->get_results( $wpdb->prepare( $query_string, 1, $wpdb->esc_like( $search_term ) , $wpdb->esc_like( $search_term ), $wpdb->esc_like( $search_term ), $wpdb->esc_like( $search_term ), $wpdb->esc_like( $search_term ), $wpdb->esc_like( $search_term ), $wpdb->esc_like( $search_term ) ), ARRAY_A );
    else
        $data_array = $wpdb->get_results( $wpdb->prepare( $query_string, 1 ), ARRAY_A );

    $payments = array();

    if( !empty( $data_array ) ) {
        foreach( $data_array as $key => $data ) {

            // Inconsistency fix between the db table row name and
            // the PMS_Payment property
            if( !empty( $data['subscription_plan_id'] ) )
                $data['subscription_id'] = $data['subscription_plan_id'];

            $payment = new PMS_Payment();
            $payment->set_instance( $data );

            $payments[] = $payment;
        }
    }

    /**
     * Filter payments just before returning them
     *
     * @param array $payments - the array of returned payments from the db
     * @param array $args     - the arguments used to query the payments from the db
     *
     */
    $payments = apply_filters( 'pms_get_payments', $payments, $args );

    return $payments;

}


/**
 * Returns the metadata for a given payment
 *
 * @param int    $payment_id
 * @param string $meta_key
 * @param bool   $single
 *
 * @return mixed - single metadata value | array of values
 *
 */
function pms_get_payment_meta( $payment_id = 0, $meta_key = '', $single = false ) {

    return get_metadata( 'payment', $payment_id, $meta_key, $single );

}


/**
 * Adds the metadata for a payment
 *
 * @param int    $payment_id
 * @param string $meta_key
 * @param string $meta_value
 * @param bool   $unique
 *
 * @return mixed - int | false
 *
 */
function pms_add_payment_meta( $payment_id = 0, $meta_key = '', $meta_value = '', $unique = false ) {

    return add_metadata( 'payment', $payment_id, $meta_key, $meta_value, $unique );

}


/**
 * Updates the metadata for a payment
 *
 * @param int    $payment_id
 * @param string $meta_key
 * @param string $meta_value
 * @param string $prev_value
 *
 * @return bool
 *
 */
function pms_update_payment_meta( $payment_id = 0, $meta_key = '', $meta_value = '', $prev_value = '' ) {

    return update_metadata( 'payment', $payment_id, $meta_key, $meta_value, $prev_value );

}


/**
 * Deletes the metadata for a payment
 *
 * @param int    $payment_id
 * @param string $meta_key
 * @param string $meta_value
 * @param string $delete_all - If true, delete matching metadata entries for all payments, ignoring
 *                             the specified payment_id. Otherwise, only delete matching metadata entries
 *                             for the specified payment_id.
 *
 */
function pms_delete_payment_meta( $payment_id = 0, $meta_key = '', $meta_value = '', $delete_all = false ) {

    return delete_metadata( 'payment', $payment_id, $meta_key, $meta_value, $delete_all );

}


/**
 * Returns the number of payments that match the given arguments
 *
 * @param array $args
 *
 * @return int
 *
 */
function pms_get_payments_count( $args = array() ) {

    global $wpdb;

    $defaults = array(
        'status'                        => '',
        'type'                          => '',
        'user_id'                       => '',
        'subscription_plan_id'          => '',
        'payment_gateway'               => '',
        'date'                          => '',
        'search'                        => ''
    );

    $args = apply_filters( 'pms_get_payments_count_args', wp_parse_args( $args, $defaults ), $args, $defaults );

    // Start query string
    $query_string = "SELECT COUNT(*) FROM {$wpdb->prefix}pms_payments pms_payments ";

    // Add inner join only when searching
    $query_inner_join = '';
    if( !empty($args['search']) ) {
        $query_inner_join = "INNER JOIN {$wpdb->users} users ON pms_payments.user_id = users.id ";
        $query_inner_join .= "INNER JOIN {$wpdb->posts} posts ON pms_payments.subscription_plan_id = posts.id ";
    }

    // Where conditions
    $query_where = "WHERE 1=%d ";

    // Add search query
    if( !empty( $args['search'] ) ) {
        $search_term = sanitize_text_field( $args['search'] );
        $query_where .= " AND " . " ( pms_payments.discount_code LIKE '%s' OR pms_payments.transaction_id LIKE '%s' OR users.user_nicename LIKE '%%%s%%' OR users.user_email LIKE '%%%s%%' OR posts.post_title LIKE '%%%s%%' ) ". " ";
    }

    // Filter by status
    if( !empty( $args['status'] ) ) {
        $status = sanitize_text_field( $args['status'] );
        $query_where .= " AND " . " pms_payments.status LIKE '{$status}'";
    }

    // Filter by type
    if( !empty( $args['type'] ) ) {
        $type = sanitize_text_field( $args['type'] );
        $query_where .= " AND " . " pms_payments.type LIKE '{$type}'";
    }

    // Filter by user_id
    if( !empty( $args['user_id'] ) ) {
        $user_id = (int)trim( $args['user_id'] );
        $query_where .= " AND " . " pms_payments.user_id = {$user_id}";
    }

    // Filter by subscription_plan_id
    if( !empty( $args['subscription_plan_id'] ) ) {
        $subscription_plan_id = (int)trim( $args['subscription_plan_id'] );
        $query_where .= " AND " . " pms_payments.subscription_plan_id = {$subscription_plan_id}";
    }

    // Filter by payment_gateway
    if( !empty( $args['payment_gateway'] ) ) {
        $payment_gateway = sanitize_text_field( $args['payment_gateway'] );
        $query_where .= " AND " . " pms_payments.payment_gateway LIKE '{$payment_gateway}'";
    }

    // Filter by date
    if( !empty( $args['date'] ) ) {
        if( is_array( $args['date'] ) && !empty( $args['date'][0] ) && !empty( $args['date'][1] ) ) {
            $args['date'][0] = sanitize_text_field( $args['date'][0] );
            $args['date'][1] = sanitize_text_field( $args['date'][1] );
            $query_where .= " AND ( pms_payments.date BETWEEN '{$args['date'][0]}' AND '{$args['date'][1]}' )";
        } elseif( is_string( $args['date'] ) ) {
            $args['date'] = sanitize_text_field( $args['date'] );
            $query_where .= " AND pms_payments.date LIKE '%%{$args['date']}%%'";
        }
    }

    // Get cached payments count
    $key   = md5( 'pms_payments_count_' . serialize( $args ) );
    $count = get_transient( $key );

    if( $count === false ) {
        // Concatenate query string
        $query_string .= $query_inner_join . $query_where;

        // Return results
        if( !empty( $args['search'] ) )
            $count = $wpdb->get_var( $wpdb->prepare( $query_string, 1, $wpdb->esc_like( $search_term ), $wpdb->esc_like( $search_term ), $wpdb->esc_like( $search_term ), $wpdb->esc_like( $search_term ), $wpdb->esc_like( $search_term ) ) );
        else
            $count = $wpdb->get_var( $wpdb->prepare( $query_string, 1 ) );

        /**
         * The expiration time ( in seconds ) for the cached payments count returned for
         * the given args
         *
         * @param array $args
         *
         */
        $cache_time = apply_filters( 'pms_payments_count_cache_time', 1800, $args );

        set_transient( $key, $count, $cache_time );

    }

    return (int)$count;
}


/**
 * Returns the number of payments a user has made
 *
 * @param int $user_id
 *
 * @return int
 *
 */
function pms_get_member_payments_count( $user_id = 0 ) {

    if( $user_id === 0 )
        return 0;

    global $wpdb;

    $user_id = (int)$user_id;

    $query_string = "SELECT COUNT( DISTINCT id ) FROM {$wpdb->prefix}pms_payments WHERE 1=%d AND user_id LIKE {$user_id}";

    $count = $wpdb->get_var( $wpdb->prepare( $query_string, 1 ) );

    return (int)$count;

}


/**
 * Function that returns all possible payment statuses
 *
 * @return array
 *
 */
function pms_get_payment_statuses() {

    $payment_statuses = array(
        'pending'   => __( 'Pending', 'paid-member-subscriptions' ),
        'completed' => __( 'Completed', 'paid-member-subscriptions' ),
        'failed'    => __( 'Failed', 'paid-member-subscriptions' ),
        'refunded'  => __( 'Refunded', 'paid-member-subscriptions' )
    );

    /**
     * Filter to add/remove payment statuses
     *
     * @param array $payment_statuses
     *
     */
    $payment_statuses = apply_filters( 'pms_payment_statuses', $payment_statuses );

    return $payment_statuses;

}


/**
 * Returns an array with the payment types supported
 *
 * @return array
 *
 */
function pms_get_payment_types() {

    $payment_types = array(
        'manual_payment'                             => __( 'Manual Payment', 'paid-member-subscriptions' ),
        'web_accept_paypal_standard'                 => __( 'PayPal Standard - One-Time Payment', 'paid-member-subscriptions' ),
        'subscription_initial_payment'               => __( 'Subscription Initial Payment', 'paid-member-subscriptions' ),
        'subscription_recurring_payment'             => __( 'Subscription Recurring Payment', 'paid-member-subscriptions' ),
        'subscription_renewal_payment'               => __( 'Subscription Renewal Payment', 'paid-member-subscriptions' ),
        'subscription_upgrade_payment'               => __( 'Subscription Upgrade Payment', 'paid-member-subscriptions' ),
        'subscription_downgrade_payment'             => __( 'Subscription Downgrade Payment', 'paid-member-subscriptions' ),
        'subscription_retry_payment'                 => __( 'Subscription Retry Payment', 'paid-member-subscriptions' ),
        'subscription_installment_initial_payment'   => __( 'Installment - Initial Payment', 'paid-member-subscriptions' ),
        'subscription_installment_recurring_payment' => __( 'Installment - Recurring Payment', 'paid-member-subscriptions' ),
        'subscription_installment_final_payment'     => __( 'Installment - Final Payment', 'paid-member-subscriptions' ),
    );

    /**
     * Filter to add/remove payment types
     *
     * @param array $payment_types
     *
     */
    $payment_types = apply_filters( 'pms_payment_types', $payment_types );

    return $payment_types;

}


/**
 * Returns true if the test mode is checked in the payments settings page
 * and false if it is not checked
 *
 * @return bool
 *
 */
function pms_is_payment_test_mode() {

    $pms_settings = get_option( 'pms_payments_settings' );

    if( isset( $pms_settings['test_mode'] ) && $pms_settings['test_mode'] == 1 )
        return true;
    else
        return false;

}


/*
 * Returns the name of the payment type given its slug
 *
 * @param string $payment_type_slug
 *
 * @return string
 *
 */
function pms_get_payment_type_name( $payment_type_slug ) {

    $payment_types = pms_get_payment_types();

    if( isset( $payment_types[$payment_type_slug] ) )
        return $payment_types[$payment_type_slug];
    else
        return '';

}


/**
 * Processes payments for custom member subscriptions
 * Is a callback to the cron job with the same name
 *
 */
function pms_cron_process_member_subscriptions_payments() {

    if( defined( 'PMS_DEV_ENVIRONMENT' ) && PMS_DEV_ENVIRONMENT === true )
        return;

    if( pms_website_was_previously_initialized() )
        return false;

    if ( false === ( $pms_psp_cron_running = get_transient( 'pms_psp_cron_running' ) ) ) {

        set_transient( 'pms_psp_cron_running', time(), HOUR_IN_SECONDS );

        $args = array(
            'status'                      => 'active',
            'billing_next_payment_after'  => date( 'Y-m-d H:i:s', time() - 1 * MONTH_IN_SECONDS ),
            'billing_next_payment_before' => date( 'Y-m-d H:i:s' ),
            'cron_query'                  => true,
        );

        $subscriptions = pms_get_member_subscriptions( $args );

        foreach( $subscriptions as $subscription ) {

            if( empty( $subscription->payment_gateway ) )
                continue;

            if( empty( $subscription->user_id ) )
                continue;
            else if( get_userdata( $subscription->user_id ) === false )
                continue;

            $payment_gateway       = pms_get_payment_gateway( $subscription->payment_gateway );
            $subscription_plan     = pms_get_subscription_plan( $subscription->subscription_plan_id );
            $subscription_currency = pms_get_member_subscription_meta( $subscription->id, 'currency', true );
            $current_billing_cycle = ( $subscription->has_installments() && pms_payment_gateway_supports_cycles( $subscription->payment_gateway ) ) ? pms_get_member_subscription_billing_processed_cycles( $subscription->id ) + 1 : false;

            if( is_null( $payment_gateway ) || !method_exists( $payment_gateway, 'process_payment' ) )
                continue;

            if( !apply_filters( 'pms_cron_process_member_subscriptions_process_subscription', true, $subscription ) )
                continue;

            // Payment data
            $payment_data = apply_filters( 'pms_cron_process_member_subscriptions_payment_data' ,
                array(
                    'user_id'                => $subscription->user_id,
                    'subscription_plan_id'   => $subscription->subscription_plan_id,
                    'date'                   => date( 'Y-m-d H:i:s' ),
                    'amount'                 => ( isset( $payment_gateway->payment_gateway ) && $payment_gateway->payment_gateway == 'manual' && ( $subscription->billing_amount == 0 || $subscription_plan->has_sign_up_fee() ) ) ? $subscription_plan->price : $subscription->billing_amount,
                    'payment_gateway'        => $subscription->payment_gateway,
                    'currency'               => !empty( $subscription_currency ) ? $subscription_currency : pms_get_active_currency(),
                    'status'                 => 'pending',
                    'type'                   => 'subscription_recurring_payment',
                    'member_subscription_id' => $subscription->id
                ),
                $subscription
            );

            if( pms_is_payment_retry_enabled() && pms_get_member_subscription_meta( $subscription->id, 'pms_retry_payment', true ) == 'active' ) {
                $payment_data['type'] = 'subscription_retry_payment';
            }
            elseif ( $current_billing_cycle ) {

                if ( $current_billing_cycle == 1 ) {
                    $payment_data['type'] = 'subscription_installment_initial_payment';
                }
                elseif ( $current_billing_cycle < $subscription->billing_cycles ) {
                    $payment_data['type'] = 'subscription_installment_recurring_payment';
                }
                else {
                    $payment_data['type'] = 'subscription_installment_final_payment';
                }

            }

            $payment = new PMS_Payment();
            $payment->insert( $payment_data );

            $payment->log_data( 'new_payment', array( 'user' => 0 ) );

            // Process payment
            $response = $payment_gateway->process_payment( $payment->id, $subscription->id );

            if( $response ) {

                $subscription_data = array(
                    'status'               => 'active',
                    'billing_last_payment' => date( 'Y-m-d H:i:s' )
                );

                // Set the next billing date
                if( ! empty( $subscription->billing_duration ) ) {

                    $plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

                    // If trial ends for fixed period membership next payment is the expiration date
                    if( $plan->is_fixed_period_membership() && !( $subscription->billing_duration == '1' && $subscription->billing_duration_unit == 'year' ) ){

                        $next_payment = $plan->get_expiration_date();
                        $subscription_data['billing_duration'] = '1';
                        $subscription_data['billing_duration_unit'] = 'year';

                    }
                    else
                        $next_payment = date( 'Y-m-d H:i:s', strtotime( "+" . $subscription->billing_duration . " " . $subscription->billing_duration_unit, strtotime( $subscription->billing_next_payment ) ) );

                    $subscription_data['billing_next_payment'] = $next_payment;

                } else {

                    //here I think we should treat the non auto recurring with free trial cases
                    $subscription_data['billing_next_payment'] = null;

                    // For Unlimited plans we need to set the expiration date; we get here after a free trial has ended
                    $plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

                    if( isset( $plan->id, $plan->duration ) && $plan->duration == 0 && !$plan->is_fixed_period_membership() )
                        $subscription_data['expiration_date'] = '';

                }

                // Process the current billing cycle if the Subscription has Payment Installments enabled
                if ( $current_billing_cycle )
                    $subscription_data = pms_process_subscription_billing_cycles( $current_billing_cycle, $subscription_data, $subscription, $subscription_plan );

                pms_update_member_subscription_meta( $subscription->id, 'pms_retry_payment', 'inactive' );
                pms_update_member_subscription_meta( $subscription->id, 'pms_retry_payment_count', 0 );

                pms_add_member_subscription_log( $subscription->id, 'subscription_renewed_automatically' );

            } else {

                $subscription_data = array();

                if( !isset( $payment_gateway->payment_gateway ) || $payment_gateway->payment_gateway != 'manual' ) {

                    $subscription_data = array(
                        'status'                => 'expired',
                        'expiration_date'       => date( 'Y-m-d H:i:s' ),
                        'billing_duration'      => '',
                        'billing_duration_unit' => '',
                        'billing_next_payment'  => NULL,
                    );

                    if( pms_is_payment_retry_enabled() ){

                        $retry_count = pms_get_subscription_payments_retry_count( $subscription->id );

                        if( $retry_count < apply_filters( 'pms_retry_payment_count', 3, $subscription->id ) ){

                            $plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

                            $subscription_data = array(
                                'status'                => pms_get_subscription_payments_retry_status(),
                                'billing_duration'      => $subscription->billing_duration,
                                'billing_duration_unit' => $subscription->billing_duration_unit,
                                'billing_next_payment'  => $plan->is_fixed_period_membership() ? $subscription->billing_next_payment : date( 'Y-m-d H:i:s', strtotime( "+" . apply_filters( 'pms_retry_payment_interval', 3, $subscription->id ) . " day", strtotime( $subscription->billing_next_payment ) ) ),
                            );

                            pms_add_member_subscription_log( $subscription->id, 'subscription_renewal_failed_retry_enabled', array( 'days'=> apply_filters( 'pms_retry_payment_interval', 3, $subscription->id ) ) );

                            pms_update_member_subscription_meta( $subscription->id, 'pms_retry_payment', 'active' );
                            pms_update_member_subscription_meta( $subscription->id, 'pms_retry_payment_count', $retry_count + 1 );

                        } else {
                            pms_update_member_subscription_meta( $subscription->id, 'pms_retry_payment', 'inactive' );

                            pms_add_member_subscription_log( $subscription->id, 'subscription_renewal_failed_retry_disabled' );
                        }

                    } else {
                        pms_add_member_subscription_log( $subscription->id, 'subscription_renewal_failed' );
                    }

                }
                // If there is an automatically recurring manual payment
                else if( isset( $payment_gateway->payment_gateway ) && $payment_gateway->payment_gateway == 'manual' ){

                    if( $subscription_plan->is_fixed_period_membership() ){
                        $expiration_date = date( 'Y-m-d H:i:s', strtotime( "+ 1 year", strtotime( $subscription->expiration_date ) ) );
                    }
                    else{
                        $expiration_date = date( 'Y-m-d H:i:s', strtotime( "+" . $subscription->billing_duration . " " . $subscription->billing_duration_unit, strtotime( $subscription->billing_next_payment ) ) );
                    }

                    if( !empty( $subscription->trial_end ) ){
                        $expiration_date = date( 'Y-m-d H:i:s', strtotime( $subscription->expiration_date ) );
                    }

                    $subscription_data = array(
                        'status'               => 'pending',
                        'expiration_date'      => $expiration_date,
                        'billing_last_payment' => date( 'Y-m-d H:i:s' ),
                        'billing_next_payment' => ( !empty( $subscription->billing_duration ) ) ? $expiration_date : null,
                    );

                    // Process the current billing cycle if the Subscription has Payment Installments enabled
                    if ( $current_billing_cycle )
                        $subscription_data = pms_process_subscription_billing_cycles( $current_billing_cycle, $subscription_data, $subscription, $subscription_plan );

                }

            }

            if( !empty( $subscription_data ) ) {
                $subscription_data = apply_filters( 'pms_cron_process_member_subscriptions_subscription_data', $subscription_data, $response, $payment );

                $subscription->update( $subscription_data );
            }

            do_action( 'pms_cron_after_processing_member_subscription', $subscription, $payment );

        }

        delete_transient( 'pms_psp_cron_running' );

    }

}
add_action( 'pms_cron_process_member_subscriptions_payments', 'pms_cron_process_member_subscriptions_payments' );


/**
 * Sets pending payments that are waiting for an IPN to failed if too much time has passed
 */
function pms_cron_process_pending_payments() {

    global $wpdb;

    $gateways = array( 'paypal_standard', 'paypal_express', 'stripe_intents' );

    $payments = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}pms_payments WHERE `status` = 'pending' AND `date` > '0000-00-00 00:00:00' AND `date` < DATE_SUB( NOW(), INTERVAL 2 DAY ) AND `payment_gateway` IN ('". implode( '\',\'', $gateways ) ."') ORDER BY id DESC", ARRAY_A );

    if ( empty( $payments ) )
        return;

    foreach( $payments as $payment ) {

        $update_result = $wpdb->update( $wpdb->prefix . 'pms_payments', array( 'status' => 'failed' ), array( 'user_id' => $payment['user_id'], 'id' => $payment['id'] ) );

        if( $update_result !== false )
            $update_result = true;

        if ( $update_result ) {

            $payment_obj = pms_get_payment( $payment['id'] );

            if( $payment_obj->payment_gateway == 'stripe_intents' )
                $payment_obj->log_data( 'stripe_authentication_link_not_clicked' );
            else
                $payment_obj->log_data( 'paypal_ipn_not_received' );

            /**
             * Fires right after the Payment db entry was updated
             *
             * @param int   $id            - the id of the payment that was updated
             * @param array $data          - the provided data to be changed for the payment
             * @param array $old_data      - the array of values representing the payment before the update
             *
             */
            do_action( 'pms_payment_update', $payment['id'], array( 'status' => 'failed' ), $payment );

        }

    }
}
add_action( 'pms_cron_process_pending_payments', 'pms_cron_process_pending_payments' );

/**
 * If subscriptions payment retry is enabled, add applicable subscriptions (which are not active) to the cron query
 *
 * @var [type]
 */
add_filter( 'pms_get_member_subscriptions', 'pms_add_retry_subscriptions_to_cron_query', 20, 2 );
function pms_add_retry_subscriptions_to_cron_query( $subscriptions, $args ){

    if( !pms_is_payment_retry_enabled() )
        return $subscriptions;

    if( !isset( $args['cron_query'] ) )
        return $subscriptions;

    global $wpdb;

    $query = "SELECT DISTINCT member_subscription_id FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key = 'pms_retry_payment' AND meta_value = 'active'";

    $results = $wpdb->get_results( $query );

    $retry_subscriptions = array();

    foreach( $results as $result ){

        $subscription = pms_get_member_subscription( $result->member_subscription_id );

        if( !empty( $subscription->id ) && in_array( $subscription->status, array( 'expired' ) ) && strtotime( $subscription->billing_next_payment ) < strtotime( $args['billing_next_payment_before'] ) )
            $retry_subscriptions[] = $subscription;

    }

    return array_merge( $subscriptions, $retry_subscriptions );

}

/**
 * Check if the payment retry functionality is enabled
 *
 * @return boolean
 */
function pms_is_payment_retry_enabled(){

    $settings = get_option( 'pms_payments_settings', false );

    if( !empty( $settings ) && isset( $settings['retry-payments'] ) && $settings['retry-payments'] == '1' )
        return true;

    return false;

}

/**
 * Get the retry count for a subscription
 *
 * @param int $subscription_id - the id of the subscription
 * @return int
 */
function pms_get_subscription_payments_retry_count( $subscription_id ){

    $retry_count = pms_get_member_subscription_meta( $subscription_id, 'pms_retry_payment_count', true );

    if( empty( $retry_count ) )
        $retry_count = 0;

    return absint( $retry_count );

}

/**
 * Get the status that the subscription will retain while payments are retried
 * 
 * @return string
 */
function pms_get_subscription_payments_retry_status(){

    $settings = get_option( 'pms_misc_settings', false );

    if( !empty( $settings ) && isset( $settings['payments'] ) && !empty( $settings['payments']['payment_retry_status'] ) )
        return sanitize_text_field( $settings['payments']['payment_retry_status'] );

    return 'expired';

}

function pms_calculate_payment_amount( $subscription_plan, $request_data = array(), $billing_amount = false, $return_breakdown = false ){

    if( empty( $subscription_plan->id ) )
        return 0;

    // need to take into account PayWhatYouWant, Discounts and Taxes
    $amount = apply_filters( 'pms_stripe_calculate_payment_amount', $subscription_plan->price, $subscription_plan ); // both filters are kept for backwards compatibility
    $amount = apply_filters( 'pms_calculate_payment_amount', $amount, $subscription_plan );

    // Check PWYW pricing
    if( function_exists( 'pms_in_pwyw_pricing_enabled' ) && pms_in_pwyw_pricing_enabled( $subscription_plan->id ) ){

        $subscription_price = isset( $request_data['subscription_price_' . $subscription_plan->id ] ) ? $request_data['subscription_price_' . $subscription_plan->id ] : ( isset( $_POST['subscription_price_' . $subscription_plan->id ] ) ? sanitize_text_field( $_POST['subscription_price_' . $subscription_plan->id ] ) : 0 );

        if( !empty( $subscription_price ) )
            $amount = floatval( $subscription_price );

    }

    global $pms_prorate;

    if( is_user_logged_in() && class_exists( 'PMS_IN_ProRate' ) && isset( $pms_prorate ) ){
        $amount = $pms_prorate->get_stripe_intents_prorated_amount( $amount, $subscription_plan->id, $request_data );
    }

    // Add sign-up fee if necessary
    if( $subscription_plan->has_sign_up_fee() && $billing_amount == false && apply_filters( 'pms_calculate_payment_amount_apply_sign_up_fee', true, $subscription_plan ) ){

        if( !empty( $request_data['form_location'] ) ){
            $form_location = $request_data['form_location'];
        } else {
            $target = isset( $request_data['pmstkn_original'] ) ? 'pmstkn_original' : 'pmstkn';
    
            $form_location = PMS_Form_Handler::get_request_form_location( $target );
        }

        if( !is_user_logged_in() || in_array( $form_location, apply_filters( 'pms_checkout_signup_fee_form_locations', array( 'register', 'new_subscription', 'retry_payment', 'register_email_confirmation', 'change_subscription', 'wppb_register' ) ) ) ){

            if( $subscription_plan->has_trial() )
                $amount = $subscription_plan->sign_up_fee;
            else
                $amount = $amount + $subscription_plan->sign_up_fee;

        }

    }

    $breakdown_data = array();
    $breakdown_data['subtotal'] = $amount;

    // Apply discount code if present
    $discount_code = isset( $request_data['discount_code'] ) ? $request_data['discount_code'] : ( isset( $_POST['discount_code'] ) ? sanitize_text_field( $_POST['discount_code'] ) : '' );
    
    if( function_exists( 'pms_in_calculate_discounted_amount' ) && !empty( $discount_code ) ){

        $discount_code = pms_in_get_discount_by_code( sanitize_text_field( $discount_code ) );

        $amount = pms_in_calculate_discounted_amount( $amount, $discount_code );

        $breakdown_data['discount'] = $breakdown_data['subtotal'] - $amount;

    }

    // Apply taxes if they are enabled
    if( function_exists( 'pms_in_tax_enabled' ) && pms_in_tax_enabled() ){
        $amount = apply_filters( 'pms_tax_apply_to_amount', $amount, $subscription_plan->id, $request_data );

        if( isset( $breakdown_data['discount'] ) )
            $breakdown_data['tax'] = $amount - ( $breakdown_data['subtotal'] - $breakdown_data['discount'] );
        else
            $breakdown_data['tax'] = $amount - $breakdown_data['subtotal'];
    }

    $breakdown_data['total'] = $amount;

    if( $return_breakdown === true ){
        return $breakdown_data;
    }

    return $amount;

}

/**
 * @TODO: Remove as this can be done through pms_get_payments now
 */
function pms_get_payments_by_subscription_id( $subscription_id, $count = 0 ){

    global $wpdb;

    // Maybe this should join the main payments table and also match the subscription plan id ? Not really necessary
    $query = "SELECT payment_id FROM {$wpdb->prefix}pms_paymentmeta WHERE meta_key = %s AND meta_value = %d ORDER BY `payment_id` DESC";

    if( $count != 0 )
        $query .= " LIMIT " . $count;
    
    $result = $wpdb->get_results( $wpdb->prepare( $query, 'subscription_id', $subscription_id ), 'ARRAY_A' );

    if( !empty( $result ) )
        return $result;

    return false;

}

/**
 * Function that resets payment counters for Payments List Table when adding or deleting a payment
 *
 * @param int   $payment_id   - the id of the new payment
 * @param object|array $payment_data - data for the current payment
 *
 */
function pms_reset_payment_counters( $payment_id, $payment_data ){

    if ( is_object( $payment_data ) && isset( $payment_data->status ))
        $status_list = array( $payment_data->status, '' );
    elseif ( is_array( $payment_data ) && isset( $payment_data['status'] ))
        $status_list = array( $payment_data['status'], '' );
    else return;

    foreach ( $status_list as $status ) {
        $key   = md5( 'pms_payments_count_' . serialize( array( 'status' => $status ) ) );
        delete_transient( $key );
    }

}
add_action( 'pms_after_bulk_delete_payments', 'pms_reset_payment_counters', 10, 2 );
add_action( 'pms_after_delete_payment', 'pms_reset_payment_counters', 10, 2 );
add_action( 'pms_payment_insert', 'pms_reset_payment_counters', 10, 2 );