<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Check if the payments cron is defined
 * 
 * @return bool True if the cron is defined, false otherwise
 */
function pms_is_payments_cron_defined(){
    return wp_next_scheduled( 'pms_cron_process_member_subscriptions_payments' );
}

/**
 * Get the next scheduled payments cron date
 * 
 * @return int The timestamp of the next scheduled payments cron
 */
function pms_get_next_scheduled_payments_cron_date(){
    return wp_next_scheduled( 'pms_cron_process_member_subscriptions_payments' );
}

/**
 * Get the health status of the scheduled payments
 * 
 * @return string 'healthy' if the scheduled payments are healthy, 'needs_attention' if there are issues
 */
function pms_get_psp_health_status(){

    $issues = pms_get_scheduled_payments_issues();

    if( !empty( $issues ) ){
        return 'needs_attention';
    }

    return 'healthy';

}

/**
 * Check if there are any scheduled payments
 * 
 * @return bool True if there are scheduled payments, false otherwise
 */
function pms_scheduled_payments_exist(){
    
    $args = array(
        'status'                      => 'active',
        'billing_next_payment_after'  => date( 'Y-m-d H:i:s', time() ),
        'number'                      => 1,
    );

    $subscriptions = pms_get_member_subscriptions( $args );

    return !empty( $subscriptions );
    
}

/**
 * Check if there are any recent recurring payments in the last 10 days
 * 
 * @return bool True if there are recent recurring payments, false otherwise
 */
function pms_has_recent_recurring_payments(){
    
    $args = array(
        'type'   => 'subscription_recurring_payment',
        'date'   => array(
            date( 'Y-m-d H:i:s', strtotime( '-10 days' ) ),
            date( 'Y-m-d H:i:s' )
        ),
        'number' => 1
    );

    $payments = pms_get_payments( $args );

    return !empty( $payments );
    
}

/**
 * Send email notification for never processed payments
 * 
 * Sends a one-time email notification to the site admin when payments older than 30 days
 * are detected. The email is only sent once to avoid spamming the admin.
 * 
 * @param int    $count  Number of stuck payments
 * @param string $amount Formatted total amount of stuck payments
 * @return bool True if email was sent, false otherwise
 */
function pms_send_never_processed_payments_email( $count, $amount ){
    
    // Check if email has already been sent
    if( get_option( 'pms_never_processed_payments_email_sent' ) ) {
        return false;
    }
    
    // Get admin email
    $admin_email = get_option( 'admin_email' );
    
    if( empty( $admin_email ) ) {
        return false;
    }
    
    // Prepare email subject
    $subject = sprintf(
        __(
            '[%1$s] We have detected issues with your scheduled payments',
            'paid-member-subscriptions'
        ),
        get_bloginfo( 'name' ),
    );
    
    // Prepare email body
    $message = sprintf(
        __( 'Hello,', 'paid-member-subscriptions' ) . "\n\n" .
        _n(
            'The Paid Member Subscriptions plugin has detected %1$d payment (totaling %2$s) that is older than 30 days and will not be automatically processed.',
            'The Paid Member Subscriptions plugin has detected %1$d payments (totaling %2$s) that are older than 30 days and will not be automatically processed.',
            $count,
            'paid-member-subscriptions'
        ) . "\n\n" .
        __( 'These payments require your attention to ensure your members can continue their subscriptions.', 'paid-member-subscriptions' ) . "\n\n" .
        __( 'To analyze the issue and resolve it, please contact our support:', 'paid-member-subscriptions' ) . "\n" .  
        'https://wordpress.org/support/plugin/paid-member-subscriptions/#new-topic-0' . "\n\n" .
        __( 'Paid Member Subscriptions', 'paid-member-subscriptions' ),
        $count,
        html_entity_decode( $amount )
    );
    
    $sent = wp_mail( $admin_email, $subject, $message );
    
    if( $sent ) {
        update_option( 'pms_never_processed_payments_email_sent', time() );
    }
    
    return $sent;
}

/**
 * AJAX handler to get scheduled payments stats based on interval
 * 
 * @return void
 */
function pms_ajax_get_psp_stats(){
    check_admin_referer( 'pms_dashboard_get_psp_stats' );

    if( !current_user_can( 'manage_options' ) )
        die();

    if( empty( $_POST['interval'] ) )
        return;

    $interval = sanitize_text_field( $_POST['interval'] );
    $return = array(
        'success' => true,
        'data'    => array(),
    );

    // Get scheduled payments stats for the selected interval
    $payments_stats = pms_get_scheduled_payments_by_interval( $interval );

    // Prepare return data
    $return['data']['payments_count'] = isset( $payments_stats['count'] ) ? $payments_stats['count'] : 0;
    $return['data']['revenue_total']  = isset( $payments_stats['total_amount'] ) ? $payments_stats['total_amount'] : 0;

    echo json_encode( $return );
    die();
}
add_action( 'wp_ajax_get_psp_stats', 'pms_ajax_get_psp_stats' );

/**
 * Get scheduled payments statistics by time interval
 *
 * @param string $interval Time interval: 'next_run', 'this_month', 'next_month', 'this_year', 'never_processed'
 * @param array $additional_args Additional arguments to pass to pms_get_member_subscriptions()
 * @return array Array with 'count' and 'total_amount' keys
 */
function pms_get_scheduled_payments_by_interval( $interval = 'next_run', $additional_args = array() ){
    
    $current_time = time();
    $args = array(
        'status' => 'active',
    );

    // Set date ranges based on interval
    switch( $interval ) {
        
        case 'next_run':
            // Payments that are due now or overdue (would be processed in next cron run)
            $args['billing_next_payment_after']  = date( 'Y-m-d H:i:s', $current_time - 1 * MONTH_IN_SECONDS );
            $args['billing_next_payment_before'] = date( 'Y-m-d H:i:s', $current_time );
            break;
            
        case 'this_month':
            // Payments due from now until end of current month
            $args['billing_next_payment_after']  = date( 'Y-m-d H:i:s', $current_time );
            $args['billing_next_payment_before'] = date( 'Y-m-d 23:59:59', strtotime( 'last day of this month', $current_time ) );
            break;
            
        case 'next_month':
            // Payments due during next month
            $args['billing_next_payment_after']  = date( 'Y-m-d 00:00:00', strtotime( 'first day of next month', $current_time ) );
            $args['billing_next_payment_before'] = date( 'Y-m-d 23:59:59', strtotime( 'last day of next month', $current_time ) );
            break;
            
        case 'this_year':
            // Payments due from now until end of current year
            $args['billing_next_payment_after']  = date( 'Y-m-d H:i:s', $current_time );
            $args['billing_next_payment_before'] = date( 'Y-12-31 23:59:59', $current_time );
            break;
            
        case 'never_processed':
            // The cron only processes payments up to 30 days old
            $args['billing_next_payment_before'] = date( 'Y-m-d H:i:s', $current_time - 1 * MONTH_IN_SECONDS );
            break;
            
        default:
            // If invalid interval, return empty results
            return array(
                'count'        => 0,
                'total_amount' => pms_format_price( 0, pms_get_active_currency() )
            );
    }

    // Merge with additional arguments (allows overriding defaults)
    $args = array_merge( $args, $additional_args );

    // Get the subscriptions
    $subscriptions = pms_get_member_subscriptions( $args );

    // Calculate total billing amount
    $total_amount = 0;
    foreach( $subscriptions as $subscription ) {
        if( !empty( $subscription->billing_amount ) ) {
            $total_amount += (float) apply_filters( 'pms_scheduled_payments_total_amount', $subscription->billing_amount, $subscription );
        }
    }

    // Format the total amount with the active currency
    $formatted_amount = pms_format_price( $total_amount, pms_get_active_currency() );

    // Return count and formatted total amount
    return array(
        'count'        => count( $subscriptions ),
        'total_amount' => $formatted_amount
    );
    
}

/**
 * Check if PayPal Connect gateway is active in live mode but not connected
 * 
 * @return bool True if PayPal Connect is active in live mode but not connected, false otherwise
 */
function pms_check_paypal_connect_live_connection(){
    
    // Only check in live mode
    if( pms_is_payment_test_mode() ) {
        return false;
    }
    
    // Check if PayPal Connect is active
    $active_gateways = pms_get_active_payment_gateways();
    if( !in_array( 'paypal_connect', $active_gateways ) ) {
        return false;
    }
    
    // Check if live credentials are missing
    $live_client_id     = get_option( 'pms_paypal_connect_live_client_id', '' );
    $live_client_secret = get_option( 'pms_paypal_connect_live_client_secret', '' );
    
    // Return true if credentials are missing
    return empty( $live_client_id ) || empty( $live_client_secret );
}

/**
 * Check if Stripe Connect gateway is active in live mode but not connected
 * 
 * @return bool True if Stripe Connect is active in live mode but not connected, false otherwise
 */
function pms_check_stripe_connect_live_connection(){
    
    // Only check in live mode
    if( pms_is_payment_test_mode() ) {
        return false;
    }
    
    // Check if Stripe Connect is active
    $active_gateways = pms_get_active_payment_gateways();
    if( !in_array( 'stripe_connect', $active_gateways ) ) {
        return false;
    }
    
    // Check if live account is connected
    $live_account_id = get_option( 'pms_stripe_connect_live_account_id', false );
    
    // Return true if account ID is missing
    return empty( $live_account_id );
}

/**
 * Get scheduled payments issues with detailed information
 * 
 * @return array Array of issues with count and revenue for each
 */
function pms_get_scheduled_payments_issues(){

    $issues = array();

    // Check if website was previously initialized
    if( pms_website_was_previously_initialized() && pms_is_psp_gateway_enabled() && !get_option( 'pms_dashboard_issue_website_previously_initialized_dismissed' ) ){
        if( !get_option( 'pms_dashboard_issue_website_previously_initialized_dismissed' ) ){
            $issues['website_previously_initialized'] = true;
        }
    }

    // Check if development environment constant is defined
    if( pms_is_psp_gateway_enabled() && defined( 'PMS_DEV_ENVIRONMENT' ) && PMS_DEV_ENVIRONMENT === true ){
        if( !get_option( 'pms_dashboard_issue_dev_environment_dismissed' ) ){
            $issues['dev_environment'] = true;
        }
    }

    // Check if cron is not defined but payments exist
    if( !pms_is_payments_cron_defined() && pms_scheduled_payments_exist() ){
        $issues['no_cron_defined'] = true;
    }

    // Check for payments that will never be processed (older than 30 days)
    $never_processed = pms_get_scheduled_payments_by_interval( 'never_processed' );
    
    if( $never_processed['count'] > 0 ){
        $issues['never_processed'] = array(
            'count'        => $never_processed['count'],
            'total_amount' => $never_processed['total_amount']
        );
        
        pms_send_never_processed_payments_email( $never_processed['count'], $never_processed['total_amount'] );
    }

    // Check if DISABLE_WP_CRON is enabled and no recent recurring payments exist
    if( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON === true && !pms_has_recent_recurring_payments() && !get_option( 'pms_dashboard_issue_wp_cron_on_server' ) ){
        $issues['wp_cron_disabled'] = true;
    }

    return $issues;

}

/**
 * Add scheduled payment issues to the dashboard issues filter
 * 
 * @param array $issues Existing dashboard issues
 * @return array Modified issues array with PSP issues added
 */
function pms_add_scheduled_payment_issues_to_dashboard( $issues ){
    $psp_issues = pms_get_scheduled_payments_issues();
    
    if( !empty( $psp_issues ) ) {
        // Merge PSP issues with existing issues
        $issues = array_merge( $issues, $psp_issues );
    }
    
    // Check PayPal Connect live connection
    if( pms_check_paypal_connect_live_connection() ) {
        $issues['paypal_connect_not_connected'] = true;
    }
    
    // Check Stripe Connect live connection
    if( pms_check_stripe_connect_live_connection() ) {
        $issues['stripe_connect_not_connected'] = true;
    }
    
    return $issues;
}
add_filter( 'pms_dashboard_issues', 'pms_add_scheduled_payment_issues_to_dashboard' );

/**
 * Reset never processed payments email flag when issue is resolved
 * 
 * This function checks if stuck payments still exist when the dashboard cache is cleared.
 * If no stuck payments are found, it deletes the email sent flag to allow re-notification
 * if the issue recurs in the future.
 * 
 * @return void
 */
function pms_reset_never_processed_email_flag_on_resolution(){
    
    // Check if the email was previously sent
    if( !get_option( 'pms_never_processed_payments_email_sent' ) ) {
        return;
    }
    
    // Check if stuck payments still exist
    $never_processed = pms_get_scheduled_payments_by_interval( 'never_processed' );
    
    // If no stuck payments exist, reset the email flag
    if( $never_processed['count'] === 0 ) {
        delete_option( 'pms_never_processed_payments_email_sent' );
    }
}
add_action( 'pms_dashboard_issues_cache_cleared', 'pms_reset_never_processed_email_flag_on_resolution' );

/**
 * Interpret scheduled payment issues based on issue key
 * 
 * @param array|null $interpreted_issue Current interpretation (null by default)
 * @param string     $issue_key         The unique key identifying the issue type
 * @param array      $issue_data        Raw issue data
 * @return array|null Formatted issue array or null if not a PSP issue
 */
function pms_interpret_scheduled_payment_issue( $interpreted_issue, $issue_key, $issue_data ){
    
    // Only interpret PSP-related issues
    switch( $issue_key ) {
        
        case 'website_previously_initialized':
            // Build dialog content
            $dialog_content = '<p>' . __( 'The plugin has detected that this website was previously initialized. This typically happens when:', 'paid-member-subscriptions' ) . '</p>';
            
            $dialog_content .= '<ul style="margin-left: 20px; list-style-type: disc;">';
            $dialog_content .= '<li>' . __( 'The site was cloned or duplicated for staging/testing purposes', 'paid-member-subscriptions' ) . '</li>';
            $dialog_content .= '<li>' . __( 'The site was migrated from another server or domain', 'paid-member-subscriptions' ) . '</li>';
            $dialog_content .= '<li>' . __( 'The site was restored from a backup', 'paid-member-subscriptions' ) . '</li>';
            $dialog_content .= '</ul>';
            
            $dialog_content .= '<p>' . __( 'To protect your members from duplicate charges, scheduled payments have been automatically disabled on this installation.', 'paid-member-subscriptions' ) . '</p>';
            
            $dialog_content .= '<p>' . sprintf( __( 'If this is your %sLive site%s, you can restore this functionality by going to the plugin Settings page and switching the plugin to Test mode and then back to Live mode.', 'paid-member-subscriptions' ), '<strong>', '</strong>' ) . '</p>';

            $dialog_content .= '<p>' . __( 'If you need help with this, please contact our support.', 'paid-member-subscriptions' ) . '</p>';
            
            return array(
                'severity'    => 'warning',
                'title'       => __( 'Website Previously Initialized', 'paid-member-subscriptions' ),
                'description' => __( 'The plugin has detected that this website was previously initialized. Scheduled payments are disabled to prevent duplicate charges on staging sites or during site migration and restoration.', 'paid-member-subscriptions' ),
                'actions'     => array(
                    array(
                        'id'           => 'website-previously-initialized-info',
                        'text'         => __( 'More Info', 'paid-member-subscriptions' ),
                        'type'         => 'primary',
                        'behavior'     => 'dialog',
                        'dialog'       => array(
                            'title'   => __( 'Website Previously Initialized', 'paid-member-subscriptions' ),
                            'content' => $dialog_content,
                            'buttons' => array(
                                array(
                                    'text'     => __( 'Contact Support', 'paid-member-subscriptions' ),
                                    'type'     => 'primary',
                                    'behavior' => 'url',
                                    'url'      => 'https://wordpress.org/support/plugin/paid-member-subscriptions/#new-topic-0',
                                    'target'   => '_blank',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'id'           => 'website-previously-initialized-dismiss',
                        'text'         => __( 'Dismiss', 'paid-member-subscriptions' ),
                        'type'         => 'secondary',
                        'behavior'     => 'ajax',
                        'ajax_action'  => 'website_previously_initialized_dismiss',
                    ),
                ),
            );
        
        case 'dev_environment':
            return array(
                'severity'    => 'warning',
                'title'       => __( 'Development Environment Active', 'paid-member-subscriptions' ),
                'description' => __( 'The PMS_DEV_ENVIRONMENT constant is defined and set to true. Scheduled payments are disabled in development mode to prevent processing test transactions.', 'paid-member-subscriptions' ),
                'actions'     => array(
                    array(
                        'text'     => __( 'Contact Support', 'paid-member-subscriptions' ),
                        'url'      => 'https://wordpress.org/support/plugin/paid-member-subscriptions/#new-topic-0',
                        'type'     => 'primary',
                        'target'   => '_blank',
                        'behavior' => 'url',
                    ),
                    array(
                        'text'        => __( 'Dismiss', 'paid-member-subscriptions' ),
                        'type'        => 'secondary',
                        'behavior'    => 'ajax',
                        'ajax_action' => 'dev_environment_dismiss',
                    ),
                ),
            );
        
        case 'no_cron_defined':
            return array(
                'severity'    => 'critical',
                'title'       => __( 'Scheduled Payments Not Running', 'paid-member-subscriptions' ),
                'description'        => sprintf(
                    __( 'The scheduled payments cron job is not active. Recurring payments will not be processed automatically. To fix this, you can try going to the %1$s page and use the %2$s option or %3$s.', 'paid-member-subscriptions' ),
                    '<strong>' . __( 'Settings -> Misc -> Others', 'paid-member-subscriptions' ) . '</strong>',
                    '<strong>' . __( 'Reset cron jobs', 'paid-member-subscriptions' ) . '</strong>',
                    '<strong>' . __( 'Contact Support', 'paid-member-subscriptions' ) . '</strong>'
                ),
                'actions'     => array(
                    array(
                        'text'     => __( 'Go to Settings', 'paid-member-subscriptions' ),
                        'url'      => admin_url( 'admin.php?page=pms-settings-page&tab=misc&nav_sub_tab=misc_others' ),
                        'type'     => 'primary',
                        'target'   => '_self',
                        'behavior' => 'url',
                    ),
                    array(
                        'text'     => __( 'Contact Support', 'paid-member-subscriptions' ),
                        'url'      => 'https://wordpress.org/support/plugin/paid-member-subscriptions/#new-topic-0',
                        'type'     => 'secondary',
                        'target'   => '_blank',
                        'behavior' => 'url',
                    ),
                ),
            );
        
        case 'never_processed':
            $count  = isset( $issue_data['count'] ) ? $issue_data['count'] : 0;
            $amount = isset( $issue_data['total_amount'] ) ? $issue_data['total_amount'] : '';

            return array(
                'severity'    => 'critical',
                'title'       => sprintf( 
                    _n( 
                        '%d Stuck Payment Detected', 
                        '%d Stuck Payments Detected', 
                        $count, 
                        'paid-member-subscriptions' 
                    ), 
                    $count 
                ),
                'description' => sprintf(
                    __( 'There are %1$s payments (totaling %2$s) that are older than 30 days and will not be automatically processed. Contact support for assistance.', 'paid-member-subscriptions' ),
                    '<strong>' . $count . '</strong>',
                    '<strong>' . $amount . '</strong>'
                ),
                'count'       => $count,
                'amount'      => $amount,
                'actions'     => array(
                    array(
                        'text'     => __( 'Contact Support', 'paid-member-subscriptions' ),
                        'url'      => 'https://wordpress.org/support/plugin/paid-member-subscriptions/#new-topic-0',
                        'type'     => 'primary',
                        'target'   => '_blank',
                        'behavior' => 'url',
                    ),
                ),
            );
        
        case 'wp_cron_disabled':
            // Build dialog content
            $dialog_content = '<p>' . __( 'You have disabled WordPress\'s built-in cron system (DISABLE_WP_CRON is set to true). To ensure your recurring payments are processed automatically, you need to set up a server-level cron job.', 'paid-member-subscriptions' ) . '</p>';
            
            $dialog_content .= '<p>' . __( 'To learn more about setting up a server-level cron job, please contact our support or visit our documentation:', 'paid-member-subscriptions' ) . '</p>';
            
            return array(
                'severity'    => 'critical',
                'title'       => __( 'WordPress Cron Disabled', 'paid-member-subscriptions' ),
                'description' => __( 'WordPress Cron is disabled (DISABLE_WP_CRON is set to true) and no recurring payments have been processed in the last 10 days. If you have set up a server-level cron job to handle WordPress cron tasks, please confirm below.', 'paid-member-subscriptions' ),
                'actions'     => array(
                    array(
                        'id'           => 'wp-cron-confirm-yes',
                        'text'         => __( 'Yes, I have server cron', 'paid-member-subscriptions' ),
                        'type'         => 'primary',
                        'behavior'     => 'ajax',
                        'ajax_action'  => 'wp_cron_disabled',
                    ),
                    array(
                        'id'           => 'wp-cron-confirm-no',
                        'text'         => __( 'No, I need help', 'paid-member-subscriptions' ),
                        'type'         => 'secondary',
                        'behavior'     => 'dialog',
                        'dialog'       => array(
                            'title'   => __( 'Setting Up Server-Level Cron', 'paid-member-subscriptions' ),
                            'content' => $dialog_content,
                            'buttons' => array(
                                array(
                                    'text'     => __( 'Contact Support', 'paid-member-subscriptions' ),
                                    'type'     => 'primary',
                                    'behavior' => 'url',
                                    'url'      => 'https://wordpress.org/support/plugin/paid-member-subscriptions/#new-topic-0',
                                    'target'   => '_blank',
                                ),
                                array(
                                    'text'     => __( 'Documentation', 'paid-member-subscriptions' ),
                                    'type'     => 'secondary',
                                    'behavior' => 'url',
                                    'url'      => 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/settings/cron-jobs/?utm_source=pms-dashboard&utm_medium=client-site&utm_campaign=pms-wp-cron-help',
                                    'target'   => '_blank',
                                ),
                            ),
                        ),
                    ),
                ),
            );
        
        case 'paypal_connect_not_connected':
            return array(
                'severity'    => 'critical',
                'title'       => __( 'PayPal Gateway Not Connected', 'paid-member-subscriptions' ),
                'description' => __( 'PayPal is active but your account is not connected in live mode. To accept live PayPal payments, you need to connect your PayPal account.', 'paid-member-subscriptions' ),
                'actions'     => array(
                    array(
                        'text'     => __( 'Go to Payments Settings', 'paid-member-subscriptions' ),
                        'url'      => admin_url( 'admin.php?page=pms-settings-page&tab=payments&nav_sub_tab=payments_gateways' ),
                        'type'     => 'primary',
                        'target'   => '_self',
                        'behavior' => 'url',
                    )
                ),
            );
        
        case 'stripe_connect_not_connected':
            return array(
                'severity'    => 'critical',
                'title'       => __( 'Stripe Gateway Not Connected', 'paid-member-subscriptions' ),
                'description' => __( 'Stripe is active but your account is not connected in live mode. To accept live credit card payments, you need to connect your Stripe account.', 'paid-member-subscriptions' ),
                'actions'     => array(
                    array(
                        'text'     => __( 'Go to Payments Settings', 'paid-member-subscriptions' ),
                        'url'      => admin_url( 'admin.php?page=pms-settings-page&tab=payments&nav_sub_tab=payments_gateways' ),
                        'type'     => 'primary',
                        'target'   => '_self',
                        'behavior' => 'url',
                    )
                ),
            );
        
        default:
            return $interpreted_issue;
    }
}
add_filter( 'pms_interpret_dashboard_issue', 'pms_interpret_scheduled_payment_issue', 10, 3 );