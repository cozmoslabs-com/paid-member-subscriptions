<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

    /**
     * Stash gateway checkout context for later PB PayPal redirect handling.
     *
     * Populates global $pms_gateway_data with payment_id, user_id, subscription_plan_id,
     * payment_gateway_slug, and redirect_token (one-time autologin secret appended to the
     * redirect URL when login_after_register is enabled).
     *
     * @param object $gateway_object
     * @param array  $gateway_data
     */
    function pms_pb_set_gateway_details( $gateway_object, $gateway_data = array() ) {

        // Do this only when PB is found
        if( ! defined( 'PROFILE_BUILDER_VERSION' ) )
            return;

        if( empty( $gateway_data ) )
            return;

        global $pms_gateway_data;
        $pms_gateway_data = array();

        // Add the payment id
        $pms_gateway_data['payment_id']           = $gateway_object->payment_id;
        $pms_gateway_data['user_id']              = $gateway_object->user_id;
        $pms_gateway_data['subscription_plan_id'] = $gateway_object->subscription_plan->id;

        // Add the payment gateway slug
        $gateways = pms_get_payment_gateways();

        foreach( $gateways as $gateway_slug => $gateway ) {
            if( $gateway['class_name'] == get_class( $gateway_object ) )
                $pms_gateway_data['payment_gateway_slug'] = $gateway_slug;
        }

        $pms_gateway_data['redirect_token'] = wp_hash( wp_generate_password( 32, false ) );

        set_transient( 'pms_wppb_paypal_checkout_' . $gateway_object->payment_id, $pms_gateway_data, 30 );

    }
    add_action( 'pms_payment_gateway_initialised', 'pms_pb_set_gateway_details', 10, 2 );


    /**
     * Set auth cookie before PayPal redirect when the form enables login after register.
     *
     * @param int $payment_id
     */
    function pms_pb_maybe_autologin_before_paypal_redirect( $payment_id ) {

        if ( empty( $_GET['pms_autologin_before_redirect'] ) || $_GET['pms_autologin_before_redirect'] !== 'true' )
            return;

        $paypal_checkout_data = get_transient( 'pms_wppb_paypal_checkout_' . $payment_id );

        if ( false === $paypal_checkout_data )
            return;

        if ( empty( $paypal_checkout_data['payment_id'] ) || (int) $paypal_checkout_data['payment_id'] !== (int) $payment_id )
            return;

        $redirect_token = isset( $_GET['pms_redirect_token'] ) ? sanitize_text_field( $_GET['pms_redirect_token'] ) : '';

        if ( empty( $paypal_checkout_data['redirect_token'] ) || empty( $redirect_token ) || ! hash_equals( $paypal_checkout_data['redirect_token'], $redirect_token ) )
            return;

        $payment = pms_get_payment( $payment_id );

        if ( empty( $payment->user_id ) || empty( $paypal_checkout_data['user_id'] ) || (int) $payment->user_id !== (int) $paypal_checkout_data['user_id'] )
            return;

        if ( user_can( $payment->user_id, 'manage_options' ) )
            return;

        wp_set_auth_cookie( $payment->user_id );

    }


    /*
     * Handle the redirect to PayPal from the saved transients
     * Also, we change the default
     *
     */
    function pms_pb_payment_redirect_link() {

        if( empty( $_GET['pms_payment_id'] ) )
            return;

        $payment_id = absint( $_GET['pms_payment_id'] );

        if( empty( $payment_id ) )
            return;

        if( ! isset( $_GET['pmstkn'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['pmstkn'] ), 'pms_payment_redirect_link_' . $payment_id ) )
            return;

        pms_pb_maybe_autologin_before_paypal_redirect( $payment_id );

        $redirect_to    = get_transient( 'pms_pb_pp_redirect_' . $payment_id );
        $redirect_back  = get_transient( 'pms_pb_pp_redirect_back_' . $payment_id );

        $redirect_to_base  = explode( '?', $redirect_to );
        $redirect_to_parts = explode( '&', $redirect_to_base[1] );

        $redirect_to_base = $redirect_to_base[0];
        $redirect_to_args = '';

        $current_part = 1;
        foreach( $redirect_to_parts as $redirect_to_part ) {

            if( strpos( $redirect_to_part, 'return' ) === 0 && !empty( $redirect_back ) ) {
                $redirect_to_part = 'return=' . $redirect_back;
            }

            $redirect_to_args .=  $redirect_to_part . ( $current_part != count( $redirect_to_parts ) ? '&' : '' );

            $current_part++;
        }

        $redirect_to_base .= '?' . $redirect_to_args;

        delete_transient( 'pms_wppb_paypal_checkout_' . $payment_id );
        delete_transient( 'pms_pb_pp_redirect_' . $payment_id );
        delete_transient( 'pms_pb_pp_redirect_back_' . $payment_id );

        header( 'Location:' . $redirect_to_base );
        exit;

    }
    add_action('init', 'pms_pb_payment_redirect_link');


    /*
     * Because redirects happen later and are handled with JS we will save the PayPal link in a transient
     * for security reasons. In the end we will refresh the current page and handle the redirect to PayPal on init
     * with the value we save in this transient
     *
     */
    function pms_pb_before_paypal_redirect( $paypal_link, $gateway_object, $settings ) {

        if( !isset( $gateway_object->payment_id ) )
            return;

        set_transient( 'pms_pb_pp_redirect_' . $gateway_object->payment_id, $paypal_link, DAY_IN_SECONDS );

    }
    add_action( 'pms_before_paypal_redirect', 'pms_pb_before_paypal_redirect', 99, 3 );


    /**
     * Change PB's ( until PB version 2.5.5 ) default success message with a custom one when a payment has been made
     *
     * This function is compatible with Profile Builder until version 2.5.5. In version 2.5.6 of Profile Builder
     * a refactoring for the redirects has been made and some hooks have been removed / modified, one of them being
     * the "wppb_register_redirect" filter, making this callback incompatible with newer versions of PB
     *
     */
    function pms_pb_register_redirect_plugins_loaded() {

        if( ! function_exists( 'wppb_build_redirect' ) ) {

            function pms_pb_register_redirect_link( $redirect_link ) {

                global $pms_gateway_data;

                if( !isset( $pms_gateway_data['payment_id'] ) || ( isset( $pms_gateway_data['payment_gateway_slug'] ) && $pms_gateway_data['payment_gateway_slug'] != 'paypal_standard' ) )
                    return $redirect_link;

                // Scrap the redirect URL from the whole redirect message
                $link = pms_pb_scrap_register_redirect_link( $redirect_link );

                if ( empty( $redirect_link ) || !empty($link) ) {

                    // save in transient
                    set_transient('pms_pb_pp_redirect_back_' . $pms_gateway_data['payment_id'], $link, DAY_IN_SECONDS );

                    $redirect_link = sprintf(
                        '<p class="redirect_message">%1$s <meta http-equiv="Refresh" content="5;url=%2$s" /></p>',
                        __( 'You will soon be redirected to complete the payment.', 'paid-member-subscriptions' ),
                        wp_nonce_url( add_query_arg( array( 'pms_payment_id' => $pms_gateway_data['payment_id'] ), pms_get_current_page_url() ), 'pms_payment_redirect_link_' . $pms_gateway_data['payment_id'], 'pmstkn' )
                    );

                    return $redirect_link;
                }

                return $redirect_link;

            }
            add_filter( 'wppb_register_redirect', 'pms_pb_register_redirect_link', 100 );

        }


        /**
         * Change PB's ( PB version 2.5.6 and higher ) default success message with a custom one when a payment has been made
         *
         */
        if( function_exists( 'wppb_build_redirect' ) ) {

            /**
             * Change the redirect link
             *
             */
            function pms_pb_register_redirect_link( $redirect_link ) {

                global $pms_gateway_data;

                if( !isset( $pms_gateway_data['payment_id'] ) || ( isset( $pms_gateway_data['payment_gateway_slug'] ) && $pms_gateway_data['payment_gateway_slug'] != 'paypal_standard' ) )
                    return $redirect_link;

                // Save the redirect link in a transient
                set_transient('pms_pb_pp_redirect_back_' . $pms_gateway_data['payment_id'], $redirect_link, DAY_IN_SECONDS );

                return wp_nonce_url( add_query_arg( array( 'pms_payment_id' => $pms_gateway_data['payment_id'] ), pms_get_current_page_url() ), 'pms_payment_redirect_link_' . $pms_gateway_data['payment_id'], 'pmstkn' );

            }
            add_filter( 'wppb_register_redirect', 'pms_pb_register_redirect_link', 100 );

            /**
             * Remove PB's default redirect message, but keep the refresh meta element
             *
             */
            function pms_pb_remove_redirect_message( $message, $redirect_url, $redirect_delay, $redirect_url_href, $redirect_type, $form_args ) {

                global $pms_gateway_data;

                if( !isset( $pms_gateway_data['payment_id'] ) || ( isset( $pms_gateway_data['payment_gateway_slug'] ) && $pms_gateway_data['payment_gateway_slug'] != 'paypal_standard' ) )
                    return $message;

                //we are doing a <meta> tag redirect below
                //if this number is not set in front of the URL under the content attribute certain browsers do not redirect
                if ( empty( $redirect_delay) || !is_numeric( $redirect_delay ) )
                    $redirect_delay = 0;

                /**
                 * Add a parameter to the redirect URL if autologin is enabled for this form
                 *
                 * @since 2.0.5
                 */
                if( isset( $form_args['login_after_register'] ) && $form_args['login_after_register'] == 'Yes' ) {
                    $redirect_url = add_query_arg( 'pms_autologin_before_redirect', 'true', $redirect_url );

                    if( ! empty( $pms_gateway_data['redirect_token'] ) )
                        $redirect_url = add_query_arg( 'pms_redirect_token', $pms_gateway_data['redirect_token'], $redirect_url );
                }

                $message = '<meta http-equiv="Refresh" content="'. $redirect_delay .';url='. $redirect_url .'" />';

                $message .= '<p class="pms-wppb-paypal-redirect-message">' . __( 'You are being redirected to PayPal to complete the payment...', 'paid-member-subscriptions' ) . '<br>';
                /* translators: %s: anchor tags */
                $message .= sprintf( __( '%1$sClick here%2$s to go now.', 'paid-member-subscriptions' ), '<a href="'.esc_url( $redirect_url ).'">', '</a>' ) . '</p>';

                return $message;

            }
            add_filter( 'wppb_redirect_message_before_returning', 'pms_pb_remove_redirect_message', 10, 6 );

        }

    }
    add_action( 'plugins_loaded', 'pms_pb_register_redirect_plugins_loaded', 11 );

    /*
     * When redirecting after successful registration, PB inserts a redirection message instead of the register form
     * We need to scrap this message and return only the URL. This is used in cases where this URL does not exist and
     * we need to redirect the user to the Register Success message from PMS
     *
     */
    function pms_pb_scrap_register_redirect_link( $redirect_link ) {

        $link = '';
        $redirect_link_parts = explode("'", $redirect_link);

        if ( strpos($redirect_link, '<script>') !== false ) { // happens when login after register is true

            $link = $redirect_link_parts[1];

        } else {

            foreach ($redirect_link_parts as $part) {

                if ( strpos( $part, 'http') !== false ) {

                    $parts = explode( '"', $part );

                    foreach( $parts as $small_part ) {
                        if( strpos( $small_part, 'http' ) === 0 ) {
                            $link = $small_part;
                            break 2;
                        }
                    }
                }
            }

        }

        return $link;

    }
