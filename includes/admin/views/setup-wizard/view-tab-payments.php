<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<form method="post">

    <h3><?php esc_html_e( 'Let\'s make some money!', 'paid-member-subscriptions' ); ?></h3>
    <p class="cozmoslabs-description"><?php esc_html_e( 'Choose your currency, renewal setting and payment gateways.', 'paid-member-subscriptions' ); ?></p>

    <p class="pms-setup-payment-settings__description"><?php esc_html_e( 'First some general payments settings...', 'paid-member-subscriptions' ); ?></p>

    <div class="pms-setup-payment-settings">
        <div class="cozmoslabs-form-field-wrapper">
            <select id="payment-currency" name="pms_payments_currency">
                <option value="-1" disabled><?php esc_html_e( 'Select Your Payment Currency', 'paid-member-subscriptions' ) ?></option>

                <?php
                foreach( pms_get_currencies() as $currency_code => $currency )
                    echo '<option value="' . esc_attr( $currency_code ) . '"' . selected( pms_get_active_currency(), $currency_code, false ) . '>' . esc_html( $currency ) . '</option>';
                ?>
            </select>


        <select id="payment-currency-position" name="pms_payments_currency_position">
            <option value="-1" disabled><?php esc_html_e( 'Select Currency Position', 'paid-member-subscriptions' ) ?></option>

            <option value="before" <?php ( isset( $this->payments_settings['currency_position'] ) ? selected( $this->payments_settings['currency_position'], 'before', true ) : ''); ?>><?php esc_html_e( 'Before', 'paid-member-subscriptions' ); ?></option>
            <option value="before_with_space" <?php ( isset( $this->payments_settings['currency_position'] ) ? selected( $this->payments_settings['currency_position'], 'before_with_space', true ) : ''); ?>><?php esc_html_e( 'Before with space', 'paid-member-subscriptions' ); ?></option>
            <option value="after" <?php ( isset( $this->payments_settings['currency_position'] ) ? selected( $this->payments_settings['currency_position'], 'after', true ) : ''); ?>><?php esc_html_e( 'After', 'paid-member-subscriptions' ); ?></option>
            <option value="after_with_space" <?php ( isset( $this->payments_settings['currency_position'] ) ? selected( $this->payments_settings['currency_position'], 'after_with_space', true ) : ''); ?>><?php esc_html_e( 'After with space', 'paid-member-subscriptions' ); ?></option>
        </select>

        <select id="payment-price-format" name="pms_payments_price_format">
            <option value="-1" disabled><?php esc_html_e( 'Select Price Display Format', 'paid-member-subscriptions' ) ?></option>

            <option value="without_insignificant_zeroes" <?php ( isset( $this->payments_settings['price-display-format'] ) ? selected( $this->payments_settings['price-display-format'], 'without_insignificant_zeroes', true ) : ''); ?>><?php echo isset( $this->payments_settings['currency_position'] ) && $this->payments_settings['currency_position'] == 'after' ? '100$' : '$100'; ?></option>
            <option value="with_insignificant_zeroes" <?php ( isset( $this->payments_settings['price-display-format'] ) ? selected( $this->payments_settings['price-display-format'], 'with_insignificant_zeroes', true ) : ''); ?>><?php echo isset( $this->payments_settings['currency_position'] ) && $this->payments_settings['currency_position'] == 'after' ? '100.00$' : '$100.00'; ?></option>
        </select>

        </div>
    </div>

    <div class="pms-setup-pages">

        <div class="pms-setup-gateway">
            <div class="pms-setup-gateway__logo">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ) . '/assets/images/pms-stripe.png'; ?>" />
            </div>
            <div class="pms-setup-gateway__description">
                <?php esc_html_e( 'Accept payments directly on your website using a wide range of payment methods allowing for a faster checkout directly on your website. Enable users to pay using debit or credit cards, Bancontact, Giropay, iDEAL, Sofort and many more.', 'paid-member-subscriptions' ); ?>
            </div>
            <div class="cozmoslabs-toggle-switch">
                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="pms_gateway_stripe" name="pms_gateway_stripe" value="1" <?php echo $this->check_gateway( 'stripe_connect' ) || !$this->website_has_payments() ? 'checked' : '' ?> />
                    <label class="cozmoslabs-toggle-track" for="pms_gateway_stripe"></label>
                </div>
            </div>
        </div>

        <div class="pms-setup-gateway pms-setup-gateway-extra stripe">
            <div class="pms-setup-gateway__logo" style="border:none;">

            </div>

            <div class="pms-setup-gateway__description pms-setup-gateway__description-extra">
                <?php

                $connection_status = pms_stripe_connect_get_account_status();

                if( $connection_status != false ){
                    echo '<p style="text-align:center; font-size: 110%; color: green;">' . sprintf( __('You are connected in %s mode. You can start accepting payments', 'paid-member-subscriptions' ), pms_is_payment_test_mode() ? 'Test' : 'Live' ) . '</p>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                } else {
                    if( isset( $_GET['pms_stripe_connect_success'] ) && $_GET['pms_stripe_connect_success'] == 1 ){

                        echo '<p style="text-align:center; font-size: 110%; color: green;">' . sprintf( __('You are connected in %s mode. You can start accepting payments.', 'paid-member-subscriptions' ), pms_is_payment_test_mode() ? 'Test' : 'Live' ) . '</p>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

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

                        $stripe_connect_base_url = 'https://www.cozmoslabs.com/?pms_stripe_connect_handle_authorization';
                        $environment             = pms_is_payment_test_mode() ? 'test' : 'live';

                        $stripe_connect_link = add_query_arg(
                            [
                                'pms_stripe_connect_action' => 'connect',
                                'environment'               => $environment,
                                'home_url'                  => home_url(),
                                'pms_return_location'       => 'setup_new',
                                'pms_nonce'                 => wp_create_nonce( 'stripe_connnect_account' ),
                                'version'                   => 'v2'
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

            <div class="cozmoslabs-toggle-switch">
                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="pms_gateway_paypal_connect" name="pms_gateway_paypal_connect" value="1" <?php echo $this->check_gateway( 'paypal_connect' ) ? 'checked' : '' ?> />
                    <label class="cozmoslabs-toggle-track" for="pms_gateway_paypal_connect"></label>
                </div>
            </div>
        </div>

        <div class="pms-setup-gateway pms-setup-gateway-extra paypal_connect">
            <div class="pms-setup-gateway__logo" style="border:none;"></div>

            <div class="pms-setup-gateway__description pms-setup-gateway__description-extra paypal_connect">
                <div class="pms-setup-gateway__description--inner" style="text-align: center;width:100%;font-size: 105%;">
                    <?php
                    $paypal_credentials = pms_ppcp_get_api_credentials();
    
                        if( empty( $paypal_credentials['client_id'] ) || empty( $paypal_credentials['client_secret'] ) ):
                            echo wp_kses( __( 'Go to the Paid Member Subscriptions -> Settings -> Payments -> Gateways page to connect your PayPal account and start accepting payments.', 'paid-member-subscriptions' ), $this->kses_args );
                        else :
                            echo wp_kses( sprintf( __( 'You are connected in %s mode. You can start accepting payments.', 'paid-member-subscriptions' ), pms_is_payment_test_mode() ? '<strong>Test</strong>' : '<strong>Live</strong>' ), $this->kses_args );
                        endif;
                    ?>
                </div>
            </div>

            <div class="pms-setup-toggle"></div>
        </div>

        <div class="pms-setup-gateway manual">
            <div class="pms-setup-gateway__logo">
                <img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL ) . '/assets/images/pms-manual-payments-logo.png'; ?>" />
            </div>
            <div class="pms-setup-gateway__description">
                <?php esc_html_e( 'Manually collect payments from your customers through Checks, Direct Bank Transfers or in person cash.', 'paid-member-subscriptions' ); ?>
            </div>
            <div class="cozmoslabs-toggle-switch">
                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="pms_gateway_offline" name="pms_gateway_offline" value="1" <?php echo $this->check_gateway( 'manual' ) ? 'checked' : '' ?> />
                    <label class="cozmoslabs-toggle-track" for="pms_gateway_offline"></label>
                </div>
            </div>
        </div>

    </div>

    <div class="pms-setup-form-button">
        <input type="submit" class="button primary button-primary button-hero" value="<?php esc_html_e( 'Continue', 'paid-member-subscriptions' ); ?>" />
    </div>

    <?php wp_nonce_field( 'pms-setup-wizard-nonce', 'pms_setup_wizard_nonce' ); ?>
</form>