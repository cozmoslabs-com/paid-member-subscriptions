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

            <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Website Health', 'paid-member-subscriptions' ); ?></h4>

            <div class="pms-dashboard-glance">
                <?php 
                $health_status = PMS_Submenu_Page_Dashboard::get_dashboard_health_status(); 
                $health_status_label = $health_status === 'healthy' ? __( 'Healthy', 'paid-member-subscriptions' ) : __( 'Needs Attention', 'paid-member-subscriptions' );
                ?>
                <div class="pms-dashboard-box pms-dashboard-glance__payments-status <?php echo $health_status === 'healthy' ? 'pms-payments-status-wrap--healthy' : 'pms-payments-status-wrap--needs-attention' ?>">
                    <div class="pms-payments-status <?php echo $health_status === 'healthy' ? 'pms-payments-status--healthy' : 'pms-payments-status--needs-attention' ?>"></div>
                    <?php echo esc_html( $health_status_label ); ?>
                </div>
            </div>

            <?php if( $health_status === 'needs_attention' ) : 
                $interpreted_issues = PMS_Submenu_Page_Dashboard::interpret_dashboard_issues( PMS_Submenu_Page_Dashboard::get_dashboard_issues() );

                if( !empty( $interpreted_issues ) ) : ?>
                    <div class="pms-dashboard-issues">
                        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Issues', 'paid-member-subscriptions' ); ?></h4>

                        <?php foreach( $interpreted_issues as $index => $issue ) : 
                            $severity_class = isset( $issue['severity'] ) ? 'pms-dashboard-issue--' . $issue['severity'] : 'pms-dashboard-issue--warning';
                        ?>
                            <div class="pms-dashboard-issue <?php echo esc_attr( $severity_class ); ?>">
                                <div class="pms-dashboard-issue__header">
                                    <strong class="pms-dashboard-issue__title"><?php echo esc_html( $issue['title'] ); ?></strong>
                                </div>
                                <div class="pms-dashboard-issue__description">
                                    <?php echo wp_kses_post( $issue['description'] ); ?>
                                </div>
                                <?php if( !empty( $issue['actions'] ) && is_array( $issue['actions'] ) ) : ?>
                                    <div class="pms-dashboard-issue__action">
                                        <?php
                                        foreach ( $issue['actions'] as $button ) :
                                            // Skip if button doesn't have required fields
                                            if ( empty( $button['text'] ) || empty( $button['behavior'] ) ) {
                                                continue;
                                            }
                                            
                                            // Determine button classes
                                            $button_type = isset( $button['type'] ) ? $button['type'] : 'secondary';
                                            $button_class = 'button pms-dashboard-issue-button';
                                            $button_class .= ( $button_type === 'primary' ) ? ' button-primary' : ' button-secondary';
                                            
                                            if ( !empty( $button['css_class'] ) ) {
                                                $button_class .= ' ' . esc_attr( $button['css_class'] );
                                            }
                                            
                                            // Add behavior-specific class
                                            $button_class .= ' pms-issue-button--' . esc_attr( $button['behavior'] );
                                            
                                            // Build data attributes
                                            $data_attrs = array();
                                            $data_attrs[] = 'data-behavior="' . esc_attr( $button['behavior'] ) . '"';
                                            
                                            if ( !empty( $button['id'] ) ) {
                                                $data_attrs[] = 'data-button-id="' . esc_attr( $button['id'] ) . '"';
                                            }
                                            
                                            // Behavior-specific attributes
                                            if ( $button['behavior'] === 'ajax' && !empty( $button['ajax_action'] ) ) {
                                                $action_name = $button['ajax_action'];
                                                $nonce = isset( $button['nonce'] ) ? $button['nonce'] : wp_create_nonce( 'pms_dashboard_issue_' . $action_name );
                                                $data_attrs[] = 'data-ajax-action="' . esc_attr( $action_name ) . '"';
                                                $data_attrs[] = 'data-nonce="' . esc_attr( $nonce ) . '"';
                                            } elseif ( $button['behavior'] === 'dialog' && !empty( $button['dialog'] ) ) {
                                                // Encode dialog configuration as JSON
                                                $data_attrs[] = 'data-dialog-config="' . esc_attr( wp_json_encode( $button['dialog'] ) ) . '"';
                                            }
                                            
                                            // Render button
                                            if ( $button['behavior'] === 'url' && !empty( $button['url'] ) ) {
                                                // Render as link
                                                $target = isset( $button['target'] ) ? $button['target'] : '_self';
                                                ?>
                                                <a href="<?php echo esc_url( $button['url'] ); ?>" 
                                                   class="<?php echo esc_attr( $button_class ); ?>"
                                                   target="<?php echo esc_attr( $target ); ?>"
                                                   <?php echo implode( ' ', $data_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- All values in $data_attrs are escaped when added to the array ?>>
                                                    <?php echo esc_html( $button['text'] ); ?>
                                                </a>
                                                <?php
                                            } else {
                                                // Render as button
                                                ?>
                                                <button type="button" 
                                                        class="<?php echo esc_attr( $button_class ); ?>"
                                                        <?php echo implode( ' ', $data_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- All values in $data_attrs are escaped when added to the array ?>>
                                                    <?php echo esc_html( $button['text'] ); ?>
                                                </button>
                                                <?php
                                            }
                                        endforeach;
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        </div>

        <?php if ( !defined( 'PMS_PAID_PLUGIN_DIR' ) ) : ?>
            <div class="postbox cozmoslabs-form-subsection-wrapper">
                <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Have a question? Not sure how to proceed?', 'paid-member-subscriptions' ); ?><span class="dashicons dashicons-editor-help" style="color: #08734C;"> </span></h4>

                <p><strong><span class="dashicons dashicons-plus" style="color: green;"></span> <?php esc_html_e( 'Open a new ticket over at', 'paid-member-subscriptions' ); ?></strong>

                <br>

                <a href="https://wordpress.org/support/plugin/paid-member-subscriptions/#new-topic-0" target="_blank" style="display:block;padding-left:24px;margin-top:4px;">https://wordpress.org/support/plugin/paid-member-subscriptions/</a></p>

                <p><strong><span class="dashicons dashicons-welcome-write-blog" style="color: green;"></span> <?php esc_html_e( 'Describe your problem:', 'paid-member-subscriptions' ); ?></strong></p>

                <ul style="padding-left:24px;">
                    <li><?php esc_html_e( 'What you tried to do', 'paid-member-subscriptions' ); ?></li><li><?php esc_html_e( 'What you expected to happen', 'paid-member-subscriptions' ); ?></li>
                    <li><?php esc_html_e( 'What actually happened', 'paid-member-subscriptions' ); ?></li>
                    <li><?php printf( esc_html__( 'Screenshots help. Use a service like %s and share the link.', 'paid-member-subscriptions' ), '<a href="https://snipboard.io/">snipboard.io</a>' ); ?></li>
                </ul>

                <p><strong><span class="dashicons dashicons-yes" style="color: green;"></span><?php esc_html_e( 'Get help from our team', 'paid-member-subscriptions' ); ?> </strong></p>

            </div>
        <?php endif; ?>

        <?php if ( get_option( 'pms_dismiss_setup_progress' ) != 'yes' ) : ?>
        <div class="postbox cozmoslabs-form-subsection-wrapper pms-dashboard-progress">
            <span id="pms-dismiss-widget" data-nonce="<?php echo esc_html( wp_create_nonce( 'pms_dismiss_nonce' ) ); ?>"></span>
            <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Setup Progress Review', 'paid-member-subscriptions' ); ?></h4>

            <?php PMS_Setup_Wizard::output_progress_steps(); ?>

            <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=pms-dashboard-page&subpage=pms-setup' ) ); ?>"><?php esc_html_e( 'Open the Setup Wizard', 'paid-member-subscriptions' ); ?></a>
        </div>
        <?php endif; ?>

        <div class="postbox cozmoslabs-form-subsection-wrapper pms-dashboard-totals">

            <?php if( pms_is_psp_gateway_enabled() ) : ?>
                <div class="pms-dashboard-scheduled-payments">
                    <div class="pms-dashboard-stats__title">
                        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Scheduled Payments', 'paid-member-subscriptions' ); ?></h4>

                        <select name="pms_dashboard_psp_stats_select" id="pms-dashboard-psp-stats-select">
                            <option value="next_run" selected><?php esc_html_e( 'Next run', 'paid-member-subscriptions' ); ?></option>
                            <option value="this_month"><?php esc_html_e( 'This Month', 'paid-member-subscriptions' ); ?></option>
                            <option value="next_month"><?php esc_html_e( 'Next Month', 'paid-member-subscriptions' ); ?></option>
                            <option value="this_year"><?php esc_html_e( 'This Year', 'paid-member-subscriptions' ); ?></option>
                        </select>

                        <input type="hidden" id="pms-dashboard-psp-stats-select__nonce" value="<?php echo esc_html( wp_create_nonce( 'pms_dashboard_get_psp_stats' ) ); ?>" />
                    </div>

                    <div class="pms-dashboard-stats">
                        <?php

                        $payments_stats = pms_get_scheduled_payments_by_interval( 'next_run' );

                        if( isset( $payments_stats['count'] ) ) {
                            $payments_count = $payments_stats['count'];
                        } else {
                            $payments_count = 0;
                        }

                        if( isset( $payments_stats['total_amount'] ) ) {
                            $revenue_total = $payments_stats['total_amount'];
                        } else {
                            $revenue_total = 0;
                        }

                        $next_run = pms_get_next_scheduled_payments_cron_date();

                        if( !empty( $next_run ) ){
                            $next_run = human_time_diff( time(), $next_run );
                        } else {
                            $next_run = esc_html__( 'Not scheduled', 'paid-member-subscriptions' );
                        }

                        $stats = [
                            'next_run'       => $next_run,
                            'payments_count' => $payments_count,
                            'revenue_total'  => $revenue_total,
                        ];

                        $stats_labels = [
                            'next_run'       => esc_html__( 'Next run in', 'paid-member-subscriptions' ),
                            'payments_count' => esc_html__( 'Payments scheduled', 'paid-member-subscriptions' ),
                            'revenue_total'  => esc_html__( 'Expected revenue', 'paid-member-subscriptions' ),
                        ];
                        
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
                </div>
            <?php endif; ?>

            <div class="pms-dashboard-stats__title">
                <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Totals', 'paid-member-subscriptions' ); ?></h4>
                
                <select name="pms_dashboard_stats_select" id="pms-dashboard-stats-select">
                    <option value="30days" selected><?php esc_html_e( '30 days', 'paid-member-subscriptions' ); ?></option>
                    <option value="this_month"><?php esc_html_e( 'This Month', 'paid-member-subscriptions' ); ?></option>
                    <option value="last_month"><?php esc_html_e( 'Last Month', 'paid-member-subscriptions' ); ?></option>
                    <option value="this_year"><?php esc_html_e( 'This Year', 'paid-member-subscriptions' ); ?></option>
                    <option value="last_year"><?php esc_html_e( 'Last Year', 'paid-member-subscriptions' ); ?></option>
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
                        <div class="label"><?php esc_html_e( 'Register', 'paid-member-subscriptions' ); ?></div>
                        <p><?php esc_html_e( 'Add registration forms where members can sign-up for a subscription plan.', 'paid-member-subscriptions' ); ?></p>
                    </div>

                    <div title='<?php echo esc_attr__( 'Click to copy', 'paid-member-subscriptions' ); ?>' class="pms-shortcode_copy-text pms-dashboard-shortcodes__row__input">
                        [pms-register]
                    </div>
                    <span style='display: none; margin-left: 10px' class='pms-copy-message'><?php echo esc_html__('Shortcode copied', 'paid-member-subscriptions'); ?></span>
                </div>
                <div class="pms-dashboard-shortcodes__row">
                    <div class="pms-dashboard-shortcodes__row__wrap">
                        <div class="label"><?php esc_html_e( 'Login', 'paid-member-subscriptions' ); ?></div>
                        <p><?php esc_html_e( 'Allow members to login.', 'paid-member-subscriptions' ); ?></p>
                    </div>

                    <div title='<?php echo esc_attr__( 'Click to copy', 'paid-member-subscriptions' ); ?>' class="pms-shortcode_copy-text pms-dashboard-shortcodes__row__input">
                        [pms-login]
                    </div>
                    <span style='display: none; margin-left: 10px' class='pms-copy-message'><?php echo esc_html__('Shortcode copied', 'paid-member-subscriptions'); ?></span>
                </div>
                <div class="pms-dashboard-shortcodes__row">
                    <div class="pms-dashboard-shortcodes__row__wrap">
                        <div class="label"><?php esc_html_e( 'Account', 'paid-member-subscriptions' ); ?></div>
                        <p><?php esc_html_e( 'Allow members to edit their account information and manage their subscription plans.', 'paid-member-subscriptions' ); ?></p>
                    </div>

                    <div title='<?php echo esc_attr__( 'Click to copy', 'paid-member-subscriptions' ); ?>' class="pms-shortcode_copy-text pms-dashboard-shortcodes__row__input">
                        [pms-account]
                    </div>
                    <span style='display: none; margin-left: 10px' class='pms-copy-message'><?php echo esc_html__('Shortcode copied', 'paid-member-subscriptions'); ?></span>
                </div>
                <div class="pms-dashboard-shortcodes__row">
                    <div class="pms-dashboard-shortcodes__row__wrap">
                        <div class="label"><?php esc_html_e( 'Restrict Content', 'paid-member-subscriptions' ); ?></div>
                        <p><?php esc_html_e( 'Restrict pieces of content on individual posts and pages based on subscription ID.', 'paid-member-subscriptions' ); ?></p>
                    </div>

                    <div title='<?php echo esc_attr__( 'Click to copy', 'paid-member-subscriptions' ); ?>' class="pms-shortcode_copy-text pms-dashboard-shortcodes__row__input">
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