<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class PMS_Review_Request {

    // Number of days to wait until review request is displayed
    public $delay = 7;
    public $pms_review_cron_hook = 'pms_review_check';
    public $notificationId = 'PMS_Review_Request';
    public $query_arg = 'pms_dismiss_admin_notification';

    public function __construct() {
        $pms_review_request_status = get_option( 'pms_review_request_status', 'not_found' );

        // Initialize the option that keeps track of the number of days elapsed
        if ( $pms_review_request_status === 'not_found' || !is_numeric( $pms_review_request_status ) ) {
            update_option( 'pms_review_request_status', 0 );
        }

        // Handle the cron
        if ( $pms_review_request_status <= $this->delay ) {
            if ( !wp_next_scheduled( $this->pms_review_cron_hook ) ) {
                wp_schedule_event( time(), 'daily', $this->pms_review_cron_hook );
            }

            if ( !has_action( $this->pms_review_cron_hook ) ) {
                add_action( $this->pms_review_cron_hook, array( $this, 'check_for_successful_payments') );
            }
        } else if ( wp_next_scheduled( $this->pms_review_cron_hook ) ){
            wp_clear_scheduled_hook( $this->pms_review_cron_hook );
        }

        // Admin notice requesting review
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        add_action( 'admin_init', array( $this, 'dismiss_notification' ) );

        // Footer text requesting review
        add_filter('admin_footer_text', array( $this, 'admin_footer_rate_us' ) );
    }

    // Function that looks for successful payments and counts the number of days elapsed
    public function check_for_successful_payments() {
        global $wpdb;

        $query = "SELECT * FROM {$wpdb->prefix}pms_payments WHERE status = 'completed'";
        $query_results = $wpdb->get_results( $query );
        if ( !empty( $query_results ) && count ($query_results) >= 3 ) {
            $pms_review_request_status = get_option( 'pms_review_request_status', 'not_found' );

            if ( $pms_review_request_status !== 'not_found' && is_numeric( $pms_review_request_status ) ) {
                update_option( 'pms_review_request_status', $pms_review_request_status + 1 );
            } else {
                update_option( 'pms_review_request_status', 1 );
            }
        }
    }

    // Function that displays the notice
    public function admin_notices() {
        $pms_review_request_status = get_option( 'pms_review_request_status' );

        if ( is_numeric( $pms_review_request_status ) && $pms_review_request_status > $this->delay ) {
            global $current_user;
            global $pagenow;

            $user_id = $current_user->ID;

            if ( current_user_can( 'manage_options' ) && apply_filters( 'pms_enable_review_request_notice', true ) ) {
                // Check that the user hasn't already dismissed the message
                if ( !get_user_meta( $user_id, $this->notificationId . '_dismiss_notification' ) ) {
                    do_action( $this->notificationId . '_before_notification_displayed', $current_user, $pagenow );
                    ?>
                    <div class="pms-review-notice pms-notice notice is-dismissible">
                        <p style="margin-top: 16px; font-size: 15px;">
                            <?php esc_html_e("Hello! Seems like you've been using Paid Member Subscriptions to receive payments. That's awesome!", 'paid-member-subscriptions'); ?>
                            <br/>
                            <?php esc_html_e("If you can spare a few moments to rate it on WordPress.org, it would help us a lot (and boost my motivation).", 'paid-member-subscriptions'); ?>
                        </p>
                        <p>
                            <?php esc_html_e("~ Adrian, developer of Paid Member Subscriptions", 'paid-member-subscriptions'); ?>
                        </p>
                        <p></p>
                        <p>
                            <a href="https://wordpress.org/support/plugin/paid-member-subscriptions/reviews/?filter=5#new-post"
                               target="_blank" rel="noopener" class="button-primary" style="margin-right: 20px">
                                <?php esc_html_e('Ok, I will gladly help!', 'paid-member-subscriptions'); ?>
                            </a>
                            <a href="<?php echo esc_url( add_query_arg(array($this->query_arg => $this->notificationId)) ) ?>"
                               class="button button-secondary" style="display: inline-flex !important;">
                                <?php esc_html_e('No, thanks.', 'paid-member-subscriptions'); ?>
                            </a>
                        </p>
                        <a href="<?php echo esc_url( add_query_arg(array($this->query_arg => $this->notificationId)) ) ?>"
                           type="button" class="notice-dismiss" style="text-decoration: none;">
                            <span class="screen-reader-text">
                                <?php esc_html_e('Dismiss this notice.', 'paid-member-subscriptions'); ?>
                            </span>
                        </a>
                    </div>
                    <?php
                    do_action( $this->notificationId . '_after_notification_displayed', $current_user, $pagenow );
                }
            }
        }
    }

    // Function that saves the notification dismissal to the user meta
    public function dismiss_notification() {
        global $current_user;

        $user_id = $current_user->ID;

        // If user clicks to ignore the notice, add that to their user meta
        if ( isset( $_GET[$this->query_arg] ) && $this->notificationId === $_GET[$this->query_arg] ) {
            do_action( $this->notificationId.'_before_notification_dismissed', $current_user );
            add_user_meta($user_id, $this->notificationId . '_dismiss_notification', 'true', true);
            do_action( $this->notificationId.'_after_notification_dismissed', $current_user );
        }
    }

    function admin_footer_rate_us( $footer_text ) {
        global $current_screen;

        if ( $current_screen->parent_base == 'paid-member-subscriptions' ){
            $rate_text = sprintf( __( 'If you enjoy using <strong> %1$s </strong> please <a href="%2$s" target="_blank">rate us on WordPress.org</a>. More happy users means more features, less bugs and better support for everyone. ', 'paid-member-subscriptions' ),
                PAID_MEMBER_SUBSCRIPTIONS,
                'https://wordpress.org/support/plugin/paid-member-subscriptions/reviews/?filter=5#new-post'
            );
            return '<span id="footer-thankyou">' .$rate_text . '</span>';
        } else {
            return $footer_text;
        }
    }
}