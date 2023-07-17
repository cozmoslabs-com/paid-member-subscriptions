<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<form method="post">
    <h3><?php esc_html_e( 'Currency', 'paid-member-subscriptions' ); ?></h3>

    <h4><?php esc_html_e( 'What currency do you want to accept payments in?', 'paid-member-subscriptions' ); ?></h4>
    <select id="payment-currency" name="pms_payments_currency">
        <?php
        foreach( pms_get_currencies() as $currency_code => $currency )
            echo '<option value="' . esc_attr( $currency_code ) . '"' . selected( pms_get_active_currency(), $currency_code, false ) . '>' . esc_html( $currency ) . '</option>';
        ?>
    </select>

    <h4><?php esc_html_e( 'Where do you want the Currency symbol to be displayed?', 'paid-member-subscriptions' ); ?></h4>
    <select id="payment-currency-position" name="pms_payments_currency_position">
        <option value="before" <?php ( isset( $this->payments_settings['currency_position'] ) ? selected( $this->payments_settings['currency_position'], 'before', true ) : ''); ?>><?php esc_html_e( 'Before', 'paid-member-subscriptions' ); ?></option>
        <option value="before_with_space" <?php ( isset( $this->payments_settings['currency_position'] ) ? selected( $this->payments_settings['currency_position'], 'before_with_space', true ) : ''); ?>><?php esc_html_e( 'Before with space', 'paid-member-subscriptions' ); ?></option>
        <option value="after" <?php ( isset( $this->payments_settings['currency_position'] ) ? selected( $this->payments_settings['currency_position'], 'after', true ) : ''); ?>><?php esc_html_e( 'After', 'paid-member-subscriptions' ); ?></option>
        <option value="after_with_space" <?php ( isset( $this->payments_settings['currency_position'] ) ? selected( $this->payments_settings['currency_position'], 'after_with_space', true ) : ''); ?>><?php esc_html_e( 'After with space', 'paid-member-subscriptions' ); ?></option>
    </select>

    <h4><?php esc_html_e( 'How should prices be displayed?', 'paid-member-subscriptions' ); ?></h4>
    <select id="payment-price-format" name="pms_payments_price_format">
        <option value="without_insignificant_zeroes" <?php ( isset( $this->payments_settings['price-display-format'] ) ? selected( $this->payments_settings['price-display-format'], 'without_insignificant_zeroes', true ) : ''); ?>><?php echo isset( $this->payments_settings['currency_position'] ) && $this->payments_settings['currency_position'] == 'after' ? '100$' : '$100'; ?></option>
        <option value="with_insignificant_zeroes" <?php ( isset( $this->payments_settings['price-display-format'] ) ? selected( $this->payments_settings['price-display-format'], 'with_insignificant_zeroes', true ) : ''); ?>><?php echo isset( $this->payments_settings['currency_position'] ) && $this->payments_settings['currency_position'] == 'after' ? '100.00$' : '$100.00'; ?></option>
    </select>

    <h4><?php esc_html_e( 'How should automatic subscription renewal work?', 'paid-member-subscriptions' ); ?></h4>
    <select id="payment-renewal" name="pms_payments_renewal">
        <option value="1" <?php ( isset( $this->payments_settings['recurring'] ) ? selected( $this->payments_settings['recurring'], '1', true ) : ''); ?>><?php esc_html_e( 'Customer opts in for automatic renewal', 'paid-member-subscriptions' ); ?></option>
        <option value="2" <?php ( isset( $this->payments_settings['recurring'] ) ? selected( $this->payments_settings['recurring'], '2', true ) : ''); ?>><?php esc_html_e( 'Always renew automatically', 'paid-member-subscriptions' ); ?></option>
        <option value="3" <?php ( isset( $this->payments_settings['recurring'] ) ? selected( $this->payments_settings['recurring'], '3', true ) : ''); ?>><?php esc_html_e( 'Never renew automatically', 'paid-member-subscriptions' ); ?></option>
    </select>

    <h3><?php esc_html_e( 'Payment Gateways', 'paid-member-subscriptions' ); ?></h3>

    <div class="pms-setup-pages">

        <div class="pms-setup-gateway">
            <div class="pms-setup-gateway__logo">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ) . '/assets/images/pms-stripe.png'; ?>" />
            </div>
            <div class="pms-setup-gateway__description">
                <?php esc_html_e( 'Accept payments directly on your website using a wide range of payment methods allowing for a faster checkout directly on your website. Enable users to pay using debit or credit cards, Bancontact, Giropay, iDEAL, Sofort and many more.', 'paid-member-subscriptions' ); ?>
            </div>
            <div class="pms-setup-toggle">
                <input type="checkbox" name="pms_gateway_stripe" id="pms_gateway_stripe" <?php echo $this->check_gateway( 'stripe_connect' ) || !$this->website_has_payments() ? 'checked' : '' ?> /><label for="pms_gateway_stripe">Toggle</label>
            </div>
        </div>

        <div class="pms-setup-gateway pms-setup-gateway-extra stripe">
            <div class="pms-setup-gateway__logo">

            </div>

            <div class="pms-setup-gateway__description pms-setup-gateway__description-extra">
                <?php
                
                $connection_status = pms_stripe_connect_get_account_status();
                
                if( $connection_status != false ){
                    echo '<p style="text-align:center; font-size: 110%; color: green;">' . sprintf( __('You are connected in %s mode. You can start accepting payments', 'paid-member-subscriptions' ), pms_is_payment_test_mode() ? 'Test' : 'Live' ) . '</p>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                } else {
                    if( isset( $_GET['pms_stripe_connect_success'] ) && $_GET['pms_stripe_connect_success'] == 1 ){

                        echo '<p style="text-align:center; font-size: 110%; color: green;">' . sprintf( __('You are connected in %s mode. You can start accepting payments', 'paid-member-subscriptions' ), pms_is_payment_test_mode() ? 'Test' : 'Live' ) . '</p>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        
                    } else {
                        if( isset( $_GET['pms_stripe_connect_platform_error'] ) && !empty( $_GET['code'] ) ){
    
                            if( !empty( $_GET['error'] ) ){
                                $error = sanitize_text_field( $_GET['error'] );
        
                                echo '<p class="pms-stripe-connect__settings-error">'. esc_html( $error ) . '</p>';
                            } else {
        
                                $error_code = sanitize_text_field( $_GET['code'] );
        
                                if( $error_code == 'generic_error' ){
                                    echo '<p class="pms-stripe-connect__settings-error">' . esc_html__( 'Something went wrong, please attempt the connection again.', 'paid-member-subscriptions' ) . '</p>';
                                }
        
                            }
                        }
        
                        $stripe_connect_base_url = 'https://cozmoslabs.com/?pms_stripe_connect_handle_authorization';
                        $environment             = pms_is_payment_test_mode() ? 'test' : 'live';
        
                        $stripe_connect_link = add_query_arg(
                            [
                                'pms_stripe_connect_action' => 'connect',
                                'environment'               => $environment,
                                'home_url'                  => home_url(),
                                'pms_return_location'       => 'setup',
                            ],
                            $stripe_connect_base_url
                        );
        
                        echo '<a href="'. esc_url( $stripe_connect_link ) .'" class="pms-stripe-connect__button"><img src="' . esc_attr( PMS_PLUGIN_DIR_URL ) . 'includes/gateways/stripe/assets/img/stripe-connect.png" /></a>';
                        echo '<p style="text-align: center; width: 100%;">' . esc_html__( 'Connect your existing Stripe account or create a new one to start accepting payments. Press the button above to start.', 'paid-member-subscriptions' ) . '</p>';
                    }
                }
                ?>
            </div>

            <div class="pms-setup-toggle"></div>
        </div>

        <div class="pms-setup-gateway">
            <div class="pms-setup-gateway__logo">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ) . '/assets/images/pms-paypal-logo.png'; ?>" />
            </div>
            <div class="pms-setup-gateway__description"><?php esc_html_e( 'Safe and secure payments handled by PayPal using the customers account.', 'paid-member-subscriptions' ); ?></div>
            <div class="pms-setup-toggle">
                <input type="checkbox" name="pms_gateway_paypal_standard" id="pms_gateway_paypal_standard" value="1" <?php echo $this->check_gateway( 'paypal_standard' ) ? 'checked' : '' ?>/><label for="pms_gateway_paypal_standard">Toggle</label>
            </div>
        </div>

        <div class="pms-setup-gateway pms-setup-gateway-extra paypal">
            <div class="pms-setup-gateway__logo">

            </div>

            <div class="pms-setup-gateway__description pms-setup-gateway__description-extra paypal">
                <div class="pms-setup-gateway__description">
                    <label class="pms-setup-label" for="pms_gateway_paypal_email_address"><?php esc_html_e( 'PayPal Email Address', 'paid-member-subscriptions' ); ?></label>
                    <input type="email" name="pms_gateway_paypal_email_address" id="pms_gateway_paypal_email_address" value="<?php echo esc_attr( pms_get_paypal_email() ); ?>" />
                </div>
                <div>
                    <?php echo wp_kses( __( 'For payments to work correctly, you will also need to <strong>setup the IPN URL in your PayPal account</strong>.', 'paid-member-subscriptions' ), $this->kses_args ); ?>
                    <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/member-payments/#IPN_for_PayPal_gateways/?utm_source=wpbackend&utm_medium=pms-setup-wizard&utm_campaign=PMSFreePayPalIPN" target="_blank">
                        <?php esc_html_e( 'Learn More', 'paid-member-subscriptions' ); ?>
                    </a>
                </div>
            </div>

            <div class="pms-setup-toggle"></div>
        </div>

        <div class="pms-setup-gateway">
            <div class="pms-setup-gateway__logo">
                <?php esc_html_e( 'Offline Payments', 'paid-member-subscriptions' ); ?>
            </div>
            <div class="pms-setup-gateway__description">
                <?php esc_html_e( 'Manually collect payments from your customers through Checks, Direct Bank Transfers or in person cash.', 'paid-member-subscriptions' ); ?>
            </div>
            <div class="pms-setup-toggle">
                <input type="checkbox" name="pms_gateway_offline" id="pms_gateway_offline" value="1" <?php echo $this->check_gateway( 'manual' ) ? 'checked' : '' ?>/><label for="pms_gateway_offline">Toggle</label>
            </div>
        </div>

        <div class="pms-setup-gateway pms-setup-fade">
            <div class="pms-setup-gateway__logo">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ) . '/assets/images/pms-paypal-pro-express-logo.png'; ?>" />
            </div>
            <div class="pms-setup-gateway__description"><?php esc_html_e( 'PayPal Express Checkout payments using credit cards or customer accounts handled by PayPal.', 'paid-member-subscriptions' ); ?></div>
            <div class="pms-setup-toggle">
                <input type="checkbox" name="pms_gateway_paypal_pro_express" id="pms_gateway_paypal_pro_express" disabled /><label for="pms_gateway_paypal_pro_express">Toggle</label>
            </div>
        </div>

        <div class="pms-setup-gateway">
            <div class="pms-setup-gateway__upsell">
                <?php echo wp_kses_post( __( 'Additional <strong>Payment Gateways</strong>, Taxes, Invoices and other features are available with a <strong>Pro</strong> licence of Paid Member Subscriptions.', 'paid-member-subscriptions' ) ); ?>
                <a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=wpbackend&utm_medium=pms-setup-wizard&utm_campaign=PMSFreeGateways" target="_blank">
                    <?php esc_html_e( 'Learn More', 'paid-member-subscriptions' ); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="pms-setup-form-button">
        <input type="submit" class="button primary button-primary button-hero" value="<?php esc_html_e( 'Continue', 'paid-member-subscriptions' ); ?>" />
    </div>

    <?php wp_nonce_field( 'pms-setup-wizard-nonce', 'pms_setup_wizard_nonce' ); ?>
</form>
