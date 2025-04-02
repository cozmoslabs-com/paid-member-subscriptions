<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML Output for the Payments tab
 */
?>

<?php $active_sub_tab = ( ! empty( $_GET['nav_sub_tab'] ) ? sanitize_text_field( $_GET['nav_sub_tab'] ) : 'payments_general' ); ?>

<!-- Sub-tab navigation -->
<ul class="subsubsub cozmoslabs-nav-sub-tab-wrapper">
    <li class="subsubsub-sub-tab"><a data-sub-tab-slug="payments_general"  href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pms-settings-page', 'tab' => 'payments', 'nav_sub_tab' => 'payments_general' ), 'admin.php' ) ) ); ?>" class="nav-sub-tab <?php echo ( $active_sub_tab == 'payments_general' ? 'current' : '' ) ?>"><?php esc_html_e( 'General', 'paid-member-subscriptions' ); ?></a></li>
    <li class="subsubsub-sub-tab"><a data-sub-tab-slug="payments_gateways"  href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pms-settings-page', 'tab' => 'payments', 'nav_sub_tab' => 'payments_gateways' ), 'admin.php' ) ) ); ?>" class="nav-sub-tab <?php echo ( $active_sub_tab == 'payments_gateways' ? 'current' : '' ) ?>"><?php esc_html_e( 'Gateways', 'paid-member-subscriptions' ); ?></a></li>

    <?php do_action( $this->menu_slug . '_payments_sub_tab_navigation_items', $this->options ); ?>

</ul>

<div id="payments-general" class="cozmoslabs-sub-tab cozmoslabs-sub-tab-general <?php echo ( $active_sub_tab == 'payments_general' ? 'tab-active' : '' ); ?>" data-sub-tab-slug="payments_general">

    <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-payments-test-mode">
        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="payment-test-mode"><?php esc_html_e( 'Test Mode', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="payment-test-mode" name="pms_payments_settings[test_mode]" value="1" <?php echo ( isset( $this->options['test_mode'] ) ? 'checked' : '' ); ?> />
                <label class="cozmoslabs-toggle-track" for="payment-test-mode"></label>
            </div>

            <div class="cozmoslabs-toggle-description">
                <label for="payment-test-mode" class="cozmoslabs-description">
                    <?php printf( wp_kses_post( __( 'Use with test accounts from your payment processors. <a href="%s">More Details</a>', 'paid-member-subscriptions' ) ), 'https://www.cozmoslabs.com/docs/paid-member-subscriptions/settings/payments/#Test_Mode' ); ?>
                </label>
            </div>
        </div>
    </div>

    <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-currency-config">
        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Currency Config', 'paid-member-subscriptions' ); ?></h4>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="payment-currency"><?php esc_html_e( 'Currency', 'paid-member-subscriptions' ) ?></label>

            <select id="payment-currency" class="pms-chosen" name="pms_payments_settings[currency]">
                <?php
                foreach( pms_get_currencies() as $currency_code => $currency )
                    echo '<option value="' . esc_attr( $currency_code ) . '"' . ( isset( $this->options['currency'] ) ? selected( $this->options['currency'], $currency_code, false ) : '') . '>' . esc_html( $currency ) . ' (' . esc_html( $currency_code ) . ')</option>';
                ?>
            </select>

            <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Select your currency. Please note that some payment gateways can have currency restrictions.', 'paid-member-subscriptions' ); ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="payment-currency-position"><?php esc_html_e( 'Currency Position', 'paid-member-subscriptions' ) ?></label>

            <select id="payment-currency-position" name="pms_payments_settings[currency_position]">
                <option value="before" <?php ( isset( $this->options['currency_position'] ) ? selected( $this->options['currency_position'], 'before', true ) : ''); ?>><?php esc_html_e( 'Before', 'paid-member-subscriptions' ); ?></option>
                <option value="before_with_space" <?php ( isset( $this->options['currency_position'] ) ? selected( $this->options['currency_position'], 'before_with_space', true ) : ''); ?>><?php esc_html_e( 'Before with space', 'paid-member-subscriptions' ); ?></option>
                <option value="after" <?php ( isset( $this->options['currency_position'] ) ? selected( $this->options['currency_position'], 'after', true ) : ''); ?>><?php esc_html_e( 'After', 'paid-member-subscriptions' ); ?></option>
                <option value="after_with_space" <?php ( isset( $this->options['currency_position'] ) ? selected( $this->options['currency_position'], 'after_with_space', true ) : ''); ?>><?php esc_html_e( 'After with space', 'paid-member-subscriptions' ); ?></option>
            </select>

            <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Select whether the currency symbol should appear before the price or after the price.', 'paid-member-subscriptions' ); ?></p>
        </div>

        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="payment-price-display-format"><?php esc_html_e( 'Price Display Format', 'paid-member-subscriptions' ) ?></label>

            <select id="payment-price-display-format" name="pms_payments_settings[price-display-format]">
                <option value="without_insignificant_zeroes" <?php ( isset( $this->options['price-display-format'] ) ? selected( $this->options['price-display-format'], 'without_insignificant_zeroes', true ) : ''); ?>><?php echo isset( $this->options['currency_position'] ) && $this->options['currency_position'] == 'after' ? '100$' : '$100'; ?></option>
                <option value="with_insignificant_zeroes" <?php ( isset( $this->options['price-display-format'] ) ? selected( $this->options['price-display-format'], 'with_insignificant_zeroes', true ) : ''); ?>><?php echo isset( $this->options['currency_position'] ) && $this->options['currency_position'] == 'after' ? '100.00$' : '$100.00'; ?></option>
            </select>

            <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Select how prices should be displayed.', 'paid-member-subscriptions' ); ?></p>
        </div>
    </div>

    <!-- Notify users about the Active Payment Gateways section relocation -->
    <?php if ( !get_option( 'pms_payments_gateways_notice_clicked', false ) ) : ?>
        <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-payment-notice-subsection">
            <div id="cozmoslabs-payment-notice">
                <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Payment Gateways', 'paid-member-subscriptions' ) ?></h4>
                <p class="cozmoslabs-description" style="font-size:14px !important;">
                    <?php
                    printf(
                        esc_html__( 'The payment gateway selection has moved to a separate sub-tab that can be accessed from the navigation menu above. %sClick here%s to open it now.', 'paid-member-subscriptions' ),
                        '<a style="font-weight: 500;" href="'. esc_url( wp_nonce_url( admin_url( 'admin.php?page=pms-settings-page&tab=payments&nav_sub_tab=payments_gateways&pms_payments_gateways_notice_clicked=true#cozmoslabs-subsection-payment-gateways' ), 'pms_payment_gateways_relocation_dismiss' ) ) .'">',
                        '</a>'
                    );
                    ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php
    $payment_gateways = pms_get_payment_gateways();
    $payment_gateways = apply_filters( 'pms_admin_display_payment_gateways', $payment_gateways );

    if( count( $payment_gateways ) > 1 ) :

        echo '<div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-global-configs">';
            echo '<h4 class="cozmoslabs-subsection-title">'. esc_html__( 'Subscription Global Configs', 'paid-member-subscriptions' ) .'</h4>';

            // Select renewal type if payment gateways support this
            if( pms_payment_gateways_support( pms_get_payment_gateways( true ), 'recurring_payments' ) ) {

                echo '<div class="cozmoslabs-form-field-wrapper">';
                    echo '<label class="cozmoslabs-form-field-label" for="payment-recurring">' . esc_html__( 'Renewal', 'paid-member-subscriptions' ) . '</label>';

                    echo '<select id="payment-recurring" name="pms_payments_settings[recurring]" class="widefat">';

                        echo '<option value="1" ' . ( isset( $this->options['recurring'] ) && ( $this->options['recurring'] == 1 ) ? 'selected' : '' ) . '>' . esc_html__( 'Customer opts in for automatic renewal', 'paid-member-subscriptions' ) . '</option>';
                        echo '<option value="2" ' . ( isset( $this->options['recurring'] ) && ( $this->options['recurring'] == 2 ) ? 'selected' : '' ) . '>' . esc_html__( 'Always renew automatically', 'paid-member-subscriptions' ) . '</option>';
                        echo '<option value="3" ' . ( isset( $this->options['recurring'] ) && ( $this->options['recurring'] == 3 ) ? 'selected' : '' ) . '>' . esc_html__( 'Never renew automatically', 'paid-member-subscriptions' ) . '</option>';

                    echo '</select>';

                    echo '<p class="cozmoslabs-description cozmoslabs-description-space-left">' . esc_html__( 'Select renewal type. Allow the customer to opt in or force automatic renewal.', 'paid-member-subscriptions' ) . '</p>';

                echo '</div>';

            }

            // Enable Payment Retrying for PSP
            if( pms_payment_gateways_support( pms_get_payment_gateways( true ), 'plugin_scheduled_payments' ) ) : ?>

                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                    <label class="cozmoslabs-form-field-label" for="retry-payments"><?php esc_html_e( 'Retry Payments', 'paid-member-subscriptions' ) ?></label>

                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="retry-payments" name="pms_payments_settings[retry-payments]" value="1" <?php echo ( isset( $this->options['retry-payments'] ) ? 'checked' : '' ); ?> />
                        <label class="cozmoslabs-toggle-track" for="retry-payments"></label>
                    </div>
                    <div class="cozmoslabs-toggle-description">
                        <label for="retry-payments" class="cozmoslabs-description"><?php printf( esc_html__( 'If a payment fails, the plugin will try to charge the user again after %s days for %s maximum retries. ', 'paid-member-subscriptions' ), esc_html( apply_filters( 'pms_retry_payment_interval', 3, '' ) ), esc_html( apply_filters( 'pms_retry_payment_count', 3, '' ) ) );?>                        </label>
                    </div>

                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Valid for the Stripe and PayPal payment gateways. ', 'paid-member-subscriptions' ); ?></p>
                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php printf( esc_html__( 'Change settings from %sSettings -> %sMisc%s -> Payments page%s.', 'paid-member-subscriptions' ), '<strong>','<a href="'. esc_url( admin_url( 'admin.php?page=pms-settings-page&tab=misc' ) ) .'">', '</a>', '</strong>' ); ?></p>

                </div>

            <?php endif; ?>

            <!-- Allow downgrades -->
            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="allow-downgrades"><?php esc_html_e( 'Allow Subscription Downgrades', 'paid-member-subscriptions' ) ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="allow-downgrades" name="pms_payments_settings[allow-downgrades]" value="1" <?php echo ( isset( $this->options['allow-downgrades'] ) ? 'checked' : '' ); ?> />
                    <label class="cozmoslabs-toggle-track" for="allow-downgrades"></label>
                </div>
                <div class="cozmoslabs-toggle-description">
                      <label for="allow-downgrades" class="cozmoslabs-description"><?php esc_html_e( 'Allow members to downgrade their subscription plan to a lower one from the same tier.', 'paid-member-subscriptions' ); ?></label>
                </div>
            </div>

            <!-- Allow users to jump between tiers -->
            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="allow-change"><?php esc_html_e( 'Allow Subscription Change', 'paid-member-subscriptions' ) ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="allow-change" name="pms_payments_settings[allow-change]" value="1" <?php echo ( isset( $this->options['allow-change'] ) ? 'checked' : '' ); ?> />
                    <label class="cozmoslabs-toggle-track" for="allow-change"></label>
                </div>
                <div class="cozmoslabs-toggle-description">
                      <label for="allow-change" class="cozmoslabs-description"><?php esc_html_e( 'Allow members to change their subscription to one from another tier.', 'paid-member-subscriptions' ); ?></label>
                </div>
            </div>


        </div>

    <?php endif; ?>

    <?php do_action( $this->menu_slug . '_payment_general_after_content', $this->options ); ?>

</div>


<div id="payments-gateways" class="cozmoslabs-sub-tab cozmoslabs-sub-tab-gateways <?php echo ( $active_sub_tab == 'payments_gateways' ? 'tab-active' : '' ); ?>" data-sub-tab-slug="payments_gateways">

    <?php
    if( count( $payment_gateways ) > 1 ) {
        // Checkboxes to select active Payment Gateways
        echo '<div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-payment-gateways">';
            echo '<h4 class="cozmoslabs-subsection-title">' . esc_html__('Active Payment Gateways', 'paid-member-subscriptions') . '</h4>';

            echo '<div class="pms-form-field-active-payment-gateways">';

                foreach ($payment_gateways as $payment_gateway_slug => $payment_gateways_details) {
                    echo '<div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">';
                        echo '<label class="cozmoslabs-form-field-label" for="' . esc_attr($payment_gateway_slug) . '">' . esc_html($payment_gateways_details['display_name_admin']) . '</label>';

                        echo '<div class="cozmoslabs-toggle-container">';
                            echo '<input type="checkbox" name="pms_payments_settings[active_pay_gates][]" id="' . esc_attr($payment_gateway_slug) . '" value="' . esc_attr($payment_gateway_slug) . '" ' . (!empty($this->options['active_pay_gates']) && in_array($payment_gateway_slug, $this->options['active_pay_gates']) ? 'checked="checked"' : '') . '/>';
                            echo '<label class="cozmoslabs-toggle-track" for="' . esc_attr($payment_gateway_slug) . '"></label>';
                        echo '</div>';

                        echo '<div class="cozmoslabs-toggle-description">';
                           echo '<label for="' . esc_attr($payment_gateway_slug) . '" class="cozmoslabs-description">' . esc_html($payment_gateways_details['description']) . '</label>';
                        echo '</div>';

                    echo '</div>';
                }

            echo '</div>';

            do_action($this->menu_slug . '_payment_general_after_gateway_checkboxes', $this->options);

            $default_gateway = '';

            if (empty($this->options['default_payment_gateway']) && !empty($this->options['active_pay_gates'][0]))
                $default_gateway = $this->options['active_pay_gates'][0];
            else
                $default_gateway = $this->options['default_payment_gateway'];

            // Select the default active Payment Gateway
            echo '<div class="cozmoslabs-form-field-wrapper">';

                echo '<label class="cozmoslabs-form-field-label" for="default-payment-gateway">' . esc_html__('Default Payment Gateway', 'paid-member-subscriptions') . '</label>';

                echo '<select id="default-payment-gateway" name="pms_payments_settings[default_payment_gateway]">';

                    foreach ($payment_gateways as $payment_gateway_slug => $payment_gateways_details) {
                        echo '<option value="' . esc_attr($payment_gateway_slug) . '" ' . selected($default_gateway, $payment_gateway_slug, false) . '>' . esc_html($payment_gateways_details['display_name_admin']) . '</option>';
                    }

                echo '</select>';

            echo '</div>';
        echo '</div>';
    }
    ?>

    <?php do_action( $this->menu_slug . '_payment_gateways_content', $this->options ); ?>

    <?php do_action( $this->menu_slug . '_payment_gateways_after_content', $this->options ); ?>

</div>


<?php do_action( $this->menu_slug . '_payments_after_subtabs', $this->options ); ?>