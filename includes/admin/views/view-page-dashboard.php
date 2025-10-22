<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML Output for the Dashboard Page
 */
?>

<div class="wrap cozmoslabs-wrap--big">

    <h1></h1>
    <!-- WordPress Notices are added after the h1 tag -->

    <div class="cozmoslabs-page-header">
        <div class="cozmoslabs-section-title">
            <h3 class="cozmoslabs-page-title"><?php echo esc_html( $this->page_title ); ?></h3>
        </div>
    </div>

    <div class="cozmoslabs-page-grid pms-dashboard-overview">
        <div class="postbox cozmoslabs-form-subsection-wrapper">
            <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'At a glance', 'paid-member-subscriptions' ); ?></h4>

            <div class="pms-dashboard-glance">
                <div class="pms-dashboard-box pms-dashboard-glance__payments-status <?php echo pms_is_payment_test_mode() ? 'test' : 'live' ?>">
                    <div class="label">
                        <?php printf( esc_html__( '%s payments are enabled', 'paid-member-subscriptions' ), pms_is_payment_test_mode() ? esc_html__( 'Test', 'paid-member-subscriptions' ) : esc_html__( 'Live', 'paid-member-subscriptions' ) ); ?>
                    </div>

                    <div class="pms-payments-status <?php echo pms_is_payment_test_mode() ? 'pms-payments-status--test' : 'pms-payments-status--live' ?>"></div>
                </div>

                <div class="pms-dashboard-box pms-dashboard-glance__payment-gateways">
                    <div class="label">
                        <?php esc_html_e( 'Active Payment Gateways', 'paid-member-subscriptions' ); ?>
                    </div>

                    <?php echo $this->get_active_payment_gateways(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>
        </div>

        <?php if ( !defined( 'PMS_PAID_PLUGIN_DIR' ) ) : ?>
            <div class="postbox cozmoslabs-form-subsection-wrapper">
                <h4 class="cozmoslabs-subsection-title"> Have a question? Not sure how to proceed?<span class="dashicons dashicons-editor-help" style="color: #08734C;"> </span></h4>

                <p><strong><span class="dashicons dashicons-plus" style="color: green;"></span> Open a new ticket over at</strong>

                <br>

                <a href="https://wordpress.org/support/plugin/paid-member-subscriptions/" target="_blank" style="display:block;padding-left:24px;margin-top:4px;">https://wordpress.org/support/plugin/paid-member-subscriptions/</a></p>

                <p><strong><span class="dashicons dashicons-welcome-write-blog" style="color: green;"></span> Describe your problem:</strong></p>

                <ul style="padding-left:24px;">
                    <li>What you tried to do</li><li>What you expected to happen</li>
                    <li>What actually happened</li>
                    <li>Screenshots help. Use a service like <a href="https://snipboard.io/">snipboard.io</a> and share the link.</li>
                </ul>

                <p><strong><span class="dashicons dashicons-yes" style="color: green;"></span>Get help from our team </strong></p>

            </div>
        <?php endif; ?>

        <div class="postbox cozmoslabs-form-subsection-wrapper pms-dashboard-progress">
            <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Setup Progress Review', 'paid-member-subscriptions' ); ?></h4>

            <?php PMS_Setup_Wizard::output_progress_steps(); ?>

            <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=pms-dashboard-page&subpage=pms-setup' ) ); ?>"><?php esc_html_e( 'Open the Setup Wizard', 'paid-member-subscriptions' ); ?></a>
        </div>

        <div class="postbox cozmoslabs-form-subsection-wrapper">

            <div class="pms-dashboard-stats__title">
                <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Totals', 'paid-member-subscriptions' ); ?></h4>
                
                <select name="pms_dashboard_stats_select" id="pms-dashboard-stats-select">
                    <option value="30days" selected>30 days</option>
                    <option value="this_month">This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="this_year">This Year</option>
                    <option value="last_year">Last Year</option>
                </select>

                <input type="hidden" id="pms-dashboard-stats-select__nonce" value="<?php echo esc_html( wp_create_nonce( 'pms_dashboard_get_stats' ) ); ?>" />
            </div>

            <div class="pms-dashboard-stats">
                <?php
                $stats        = $this->get_stats();
                $stats_labels = $this->get_stats_labels();
                
                if( !empty( $stats ) ){
                    foreach( $stats as $key => $value ) : ?>

                        <div class="pms-dashboard-box <?php echo esc_html( $key ); ?>">
                            <div class="label">
                                <?php echo esc_html( $stats_labels[ $key ] ); ?>
                            </div>

                            <div class="value">
                                <?php
                                    echo esc_html( $value );
                                ?>
                            </div>
                        </div>

                    <?php endforeach; 
                }
                ?>
            </div>

            <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Recent Payments', 'paid-member-subscriptions' ); ?></h4>
            
            <div class="pms-dashboard-payments">
                <?php 
                $recent_payments = pms_get_payments( array( 'status' => 'completed', 'order' => 'DESC', 'number' => 5 ) );

                if( !empty( $recent_payments ) ): ?>
                    <?php foreach( $recent_payments as $payment ): ?>
                        <?php $payment_user = get_userdata( $payment->user_id ); ?>

                        <div class="pms-dashboard-payments__row">
                            <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pms-payments-page', 'pms-action' => 'edit_payment', 'payment_id' => $payment->id ), admin_url( 'admin.php' ) ) ); ?>">
                                <?php printf( esc_html__( '%1s purchased a %2s subscription for %3s', 'paid-member-subscriptions' ), esc_html( $payment_user->user_login ), esc_html( $this->get_plan_name( $payment->subscription_id ) ), esc_html( pms_format_price( $payment->amount, $payment->currency ) ) ); ?>
                            </a>
                            <div class="pms-dashboard-payments__date">
                                <?php printf( '%1s - %2s', esc_html( $payment->date ), esc_html( $payment->status ) ) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=pms-payments-page' ) ); ?>"><?php esc_html_e( 'View All Payments', 'paid-member-subscriptions' ); ?></a>
        </div>
        
        <div class="postbox cozmoslabs-form-subsection-wrapper">
            <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Useful shortcodes for setup', 'paid-member-subscriptions' ); ?></h4>

            <p class="pms-dashboard-shortcodes__description"><?php esc_html_e( 'Use these shortcodes to quickly setup and customize your membership website.', 'paid-member-subscriptions' ); ?></p>
            
            <div class="pms-dashboard-shortcodes">
                <div class="pms-dashboard-shortcodes__row">
                    <div class="pms-dashboard-shortcodes__row__wrap">
                        <div class="label">Register</div>
                        <p>Add registration forms where members can sign-up for a subscription plan.</p>
                    </div>

                    <div title='Click to copy' class="pms-shortcode_copy-text pms-dashboard-shortcodes__row__input">
                        [pms-register]
                    </div>
                    <span style='display: none; margin-left: 10px' class='pms-copy-message'><?php echo esc_html__('Shortcode copied', 'paid-member-subscriptions'); ?></span>
                </div>
                <div class="pms-dashboard-shortcodes__row">
                    <div class="pms-dashboard-shortcodes__row__wrap">
                        <div class="label">Login</div>
                        <p>Allow members to login.</p>
                    </div>

                    <div title='Click to copy' class="pms-shortcode_copy-text pms-dashboard-shortcodes__row__input">
                        [pms-login]
                    </div>
                    <span style='display: none; margin-left: 10px' class='pms-copy-message'><?php echo esc_html__('Shortcode copied', 'paid-member-subscriptions'); ?></span>
                </div>
                <div class="pms-dashboard-shortcodes__row">
                    <div class="pms-dashboard-shortcodes__row__wrap">
                        <div class="label">Account</div>
                        <p>Allow members to edit their account information and manage their subscription plans.</p>
                    </div>

                    <div title='Click to copy' class="pms-shortcode_copy-text pms-dashboard-shortcodes__row__input">
                        [pms-account]
                    </div>
                    <span style='display: none; margin-left: 10px' class='pms-copy-message'><?php echo esc_html__('Shortcode copied', 'paid-member-subscriptions'); ?></span>
                </div>
                <div class="pms-dashboard-shortcodes__row">
                    <div class="pms-dashboard-shortcodes__row__wrap">
                        <div class="label">Restrict Content</div>
                        <p>Restrict pieces of content on individual posts and pages based on subscription ID.</p>
                    </div>

                    <div title='Click to copy' class="pms-shortcode_copy-text pms-dashboard-shortcodes__row__input">
                        [pms-restrict subscription_plans="9,10"]
                    </div>
                    <span style='display: none; margin-left: 10px' class='pms-copy-message'><?php echo esc_html__('Shortcode copied', 'paid-member-subscriptions'); ?></span>
                </div>
            </div>

            <a class="button button-secondary" href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/shortcodes/?utm_source=pms-dashboard&utm_medium=client-site&utm_campaign=pms-shortcodes"><?php esc_html_e( 'Learn more about shortcodes', 'paid-member-subscriptions' ); ?></a>
        </div>

        <?php PMS_Setup_Wizard::output_modal_progress_steps(); ?>

    </div>

</div>