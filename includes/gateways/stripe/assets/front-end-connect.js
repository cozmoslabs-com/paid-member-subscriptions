jQuery( function( $ ) {

    if( !( $('#stripe-pk').length > 0 ) )
        return false

    var stripe_pk = $( '#stripe-pk' ).val()

    //compatibility with PB conditional logic. if there are multiple subscription plans fields and the first one is hidden then it won't have a value attribute because of conditional logic
    if( typeof stripe_pk == 'undefined' || stripe_pk == '' )
        stripe_pk = $('#stripe-pk').attr('conditional-value')

    if( typeof stripe_pk == 'undefined' )
        return false

    if ( typeof pms.stripe_connected_account == 'undefined' || pms.stripe_connected_account == '' ){
        console.log( 'Before you can accept payments, you need to connect your Stripe Account by going to Dashboard -> Paid Member Subscriptions -> Settings -> Payments.' )
        return false
    }

    var $client_secret              = ''
    var $client_secret_setup_intent = ''

    var elements              = false
    var elements_setup_intent = false
    var stripe                = false

    // Grab intents so we can generate the payment form
    pms_stripe_get_payment_intents().then( function( result ){

        let intents = JSON.parse( result )

        $client_secret              = intents.payment_intent
        $client_secret_id           = intents.payment_intent_id
        $client_secret_setup_intent = intents.setup_intent
        $client_secret_setup_id     = intents.setup_intent_id

        $('.pms-form input[name="pms_stripe_connect_payment_intent"], .wppb-register-user input[name="pms_stripe_connect_payment_intent"]').val( $client_secret )
        $('.pms-form input[name="pms_stripe_connect_setup_intent"], .wppb-register-user input[name="pms_stripe_connect_setup_intent"]').val( $client_secret_setup_intent )

        var StripeData = {
            stripeAccount: pms.stripe_connected_account
        }

        if( pms.stripe_locale )
            StripeData.locale = pms.stripe_locale

        var stripe_appearance = ''

        if( pms.pms_elements_appearance_api )
            stripe_appearance = pms.pms_elements_appearance_api

        stripe = Stripe( stripe_pk, StripeData )

        // This only exists on payment pages that display the payment element
        if( $client_secret && $client_secret.length > 0 )
            elements = stripe.elements({ clientSecret: $client_secret, appearance: stripe_appearance })

        // This exists on payment pages and also on the Update Payment Method page
        if ( $client_secret_setup_intent && $client_secret_setup_intent.length > 0 )
            elements_setup_intent = stripe.elements({ clientSecret: $client_secret_setup_intent, appearance: stripe_appearance })

        stripeConnectInit()

    })

    var $payment_element        = ''
    var $elements_instance_slug = ''

    var cardIsEmpty = true

    var subscription_plan_selector = 'input[name=subscription_plans]'


    // Update Stripe Payment Intent on subscription plan change
    $(document).on('click', subscription_plan_selector, function ( event ) {

        stripeConnectInit()

    })

    // Discount applied
    $(document).on('pms_discount_success', function ( event ) {

        stripeConnectInit()

    })

    // Show credit card details on the update payment method form
    if ( $( 'input[name="pms_update_payment_method"]' ).length > 0 && $( '.pms-paygate-extra-fields-stripe_connect' ).length > 0 ){
        $('.pms-paygate-extra-fields-stripe_connect').show()
    }

    // Paid Member Subscription submit buttons
    var payment_buttons  = 'input[name=pms_register], ';
        payment_buttons += 'input[name=pms_new_subscription], ';
        payment_buttons += 'input[name=pms_change_subscription], ';
        payment_buttons += 'input[name=pms_upgrade_subscription], ';
        payment_buttons += 'input[name=pms_renew_subscription], ';
        payment_buttons += 'input[name=pms_confirm_retry_payment_subscription], ';

    // Profile Builder submit buttons
    payment_buttons += '.wppb-register-user input[name=register]';

    // Payment Intents
    $(document).on( 'wppb_invisible_recaptcha_success', stripeConnectPaymentGatewayHandler )
    $(document).on( 'wppb_v3_recaptcha_success', stripeConnectPaymentGatewayHandler )
    $(document).on( 'pms_v3_recaptcha_success', stripeConnectPaymentGatewayHandler )

    $(document).on('submit', '.pms-form', function (e) {

        if( e.target && ( jQuery( e.target ).attr('id') == 'pms_recover_password_form' || jQuery( e.target ).attr('id') == 'pms_new_password_form' || jQuery( e.target ).attr('id') == 'pms_login' ) )
            return

        var target_button = $('input[type="submit"], button[type="submit"]', $(this)).not('#pms-apply-discount').not('input[name="pms_redirect_back"]')

        // Email Confirmation using PB form
        var form = $(this).closest( 'form' )

        if( typeof form != 'undefined' && form && form.length > 0 && form.hasClass( 'pms-ec-register-form' ) ){

            stripeConnectPaymentGatewayHandler(e, target_button)

        // Skip if the Go Back button was pressed
        } else if ( !e.originalEvent || !e.originalEvent.submitter || $(e.originalEvent.submitter).attr('name') != 'pms_redirect_back' ) {

            if ( $(e.originalEvent.submitter).attr('name') == 'pms_update_payment_method' )
                stripeConnectUpdatePaymentMethod(e, target_button)
            else
                stripeConnectPaymentGatewayHandler(e, target_button)

        }

    })

    $(document).on('submit', '.wppb-register-user', function (e) {

        if ( ! ( $( '.wppb-recaptcha .wppb-recaptcha-element', $(e.currentTarget) ).hasClass( 'wppb-invisible-recaptcha' ) || $( '.wppb-recaptcha .wppb-recaptcha-element', $(e.currentTarget) ).hasClass( 'wppb-v3-recaptcha' ) ) ) {

            var target_button = $('input[type="submit"], button[type="submit"]', $(this)).not('#pms-apply-discount').not('input[name="pms_redirect_back"]')

            stripeConnectPaymentGatewayHandler(e, target_button)

        }

    })

    function stripeConnectPaymentGatewayHandler( e, target_button = false ){

        if( $('input[type=hidden][name=pay_gate]').val() != 'stripe_connect' && $('input[type=radio][name=pay_gate]:checked').val() != 'stripe_connect' )
            return

        if( $('input[type=hidden][name=pay_gate]').is(':disabled') || $('input[type=radio][name=pay_gate]:checked').is(':disabled') )
            return

        e.preventDefault()

        $.pms_form_remove_errors()

        var current_button = $(this)

        // Current submit button can't be determined from `this` context in case of the Invisible reCaptcha handler
        if( e.type == 'wppb_invisible_recaptcha_success' || e.type == 'wppb_v3_recaptcha_success' || e.type == 'pms_v3_recaptcha_success' ){

            // target_button is supplied to the handler starting with version 3.5.0 of Profile Builder, we use this for backwards compatibility
            current_button = target_button == false ? $( 'input[type="submit"]', $( '.wppb-recaptcha-element' ).closest( 'form' ) ) : $( target_button )

        } else if ( e.type == 'submit' ){

            if( target_button != false )
                current_button = $( target_button )

        }

        // Disable the button
        current_button.attr( 'disabled', true )

        // Add error if credit card was not completed
        if ( cardIsEmpty === true ){
            $.pms_form_add_validation_errors([{ target: 'credit_card', message: pms.invalid_card_details_error } ], current_button )

            if( typeof paymentSidebarPosition == 'function' ){
                paymentSidebarPosition()
            }

            return
        }

        // Update Payment Intent
        stripeConnectUpdatePaymentIntent().then( function( result ){

            if( result == false )
                return

            // grab all data from the form
            var data = $.pms_form_get_data( current_button, true )

            if( data == false )
                return

            // Make request to process checkout (create user, add pending payment and subscription)
            $.post( pms.ajax_url, data, function( response ) {

                if( response ){
                    response = JSON.parse( response )

                    if( response.success == true ){

                        if( data.form_type == 'wppb' ){

                            var return_url = new URL( pms.stripe_return_url )

                            return_url.searchParams.set( 'form_type', 'wppb' )
                            return_url.searchParams.set( 'form_name', data.form_name )

                            pms.stripe_return_url = return_url.toString()

                        }

                        // Handle card setup for a trial subscription
                        if( data.setup_intent && data.setup_intent === true ){

                            stripe.confirmSetup({
                                elements: elements_setup_intent,
                                confirmParams: {
                                    return_url         : pms.stripe_return_url,
                                    payment_method_data: { billing_details: pms_stripe_get_billing_details() }
                                },
                                redirect: 'if_required',
                            }).then(function(result) {

                                // Make request to process payment
                                stripeConnectProcessPayment( result, response, data, current_button )

                            })

                        // Take the payment if there's no trial
                        } else {

                            stripe.confirmPayment({
                                elements,
                                confirmParams: {
                                    return_url         : pms.stripe_return_url,
                                    payment_method_data: { billing_details: pms_stripe_get_billing_details() }
                                },
                                redirect : 'if_required',
                            }).then(function(result){

                                // Make request to process payment
                                stripeConnectProcessPayment( result, response, data, current_button )
                            })

                        }

                    // Error handling
                    } else if( response.success == false ){

                        var form_type = data.form_type = $('.wppb-register-user .wppb-subscription-plans').length > 0 ? 'wppb' : $('.pms-ec-register-form').length > 0 ? 'pms_email_confirmation' : 'pms'

                        // Paid Member Subscription forms
                        if (response.data && ( form_type == 'pms' || form_type == 'pms_email_confirmation' ) ){
                            $.pms_form_add_validation_errors( response.data, current_button )
                        // Profile Builder form
                        } else {

                            // Add PMS related errors (Billing Fields)
                            // These are added first because the form will scroll to the error and these
                            // are always placed at the end of the WPPB form
                            if( response.pms_errors && response.pms_errors.length > 0 )
                                $.pms_form_add_validation_errors( response.pms_errors, current_button )

                            // Add WPPB related errors
                            if( typeof response.wppb_errors == 'object' )
                                $.pms_form_add_wppb_validation_errors( response.wppb_errors, current_button )

                        }

                        jQuery(document).trigger( 'pms_checkout_validation_error', response, current_button )

                    } else {
                        console.log( 'something unexpected happened' )
                    }

                }

            })

        })

    }

    // Update Payment Method
    function stripeConnectUpdatePaymentMethod( e, target_button = false ){

        e.preventDefault()

        $.pms_form_remove_errors()

        var current_button = $(this)

        if ( target_button != false )
            current_button = $( target_button )

        //Disable the button
        current_button.attr('disabled', true)

        // Add error if credit card was not completed
        if (cardIsEmpty === true) {
            $.pms_form_add_validation_errors([{ target: 'credit_card', message: pms.invalid_card_details_error }], current_button)
            return
        }

        stripe.confirmSetup({
            elements: elements_setup_intent,
            confirmParams: {
                return_url: pms.stripe_return_url,
                payment_method_data: { billing_details: pms_stripe_get_billing_details() }
            },
            redirect: 'if_required',
        }).then(function (result) {

            let token

            if (result.error && result.error.decline_code && result.error.decline_code == 'live_mode_test_card') {
                let errors = [{ target: 'credit_card', message: result.error.message }]

                $.pms_form_add_validation_errors(errors, current_button)
            } else if (result.error && result.error.type && result.error.type == 'validation_error')
                $.pms_form_reset_submit_button(current_button)
            else {
                if (result.error && result.error.setup_intent)
                    token = { id: result.error.setup_intent.id }
                else if (result.setupIntent)
                    token = { id: result.setupIntent.payment_method }
                else
                    token = ''

                stripeTokenHandler(token, $(current_button).closest('form'))
            }

        })

    }

    function stripeConnectInit(){

        var target_elements_instance      = false
        var target_elements_instance_slug = ''

        // Update Payment Method SetupIntent
        if ( $('#pms-update-payment-method-form #pms-stripe-payment-elements').length > 0 ){
            target_elements_instance = elements_setup_intent
            target_elements_instance_slug = 'setup_intents'
        // SetupIntent
        } else if ( $.pms_checkout_is_setup_intents() ) {
            target_elements_instance      = elements_setup_intent
            target_elements_instance_slug = 'setup_intents'
        // PaymentIntents
        } else {
            target_elements_instance      = elements
            target_elements_instance_slug = 'payment_intents'
        }

        if( target_elements_instance != false ){

            if( $payment_element == '' ){

                $payment_element = target_elements_instance.create("payment", { terms: { card: 'never' } } )
                $payment_element.mount("#pms-stripe-payment-elements")

                // Show credit card form error messages to the user as they happpen
                $payment_element.addEventListener('change', creditCardErrorsHandler )

            } else {

                if( $elements_instance_slug != target_elements_instance_slug ){

                    $payment_element.destroy()

                    $payment_element = target_elements_instance.create("payment", { terms: { card: 'never' } } )
                    $payment_element.mount("#pms-stripe-payment-elements")

                    // Show credit card form error messages to the user as they happpen
                    $payment_element.addEventListener('change', creditCardErrorsHandler )

                }

            }

            $elements_instance_slug = target_elements_instance_slug

            jQuery('#pms-stripe-payment-elements').show()
            jQuery( '#pms-stripe-connect .pms-spinner__holder' ).hide()

            if( typeof paymentSidebarPosition == 'function' ){
                setTimeout( paymentSidebarPosition, 300 )
            }

        }

    }

    async function stripeConnectUpdatePaymentIntent(){

        if( !$client_secret || !( $client_secret.length > 0 ) )
            return

        // Don't make this call when a Free Trial subscription is selected since we use the prepared SetupIntent
        if ( $.pms_checkout_is_setup_intents() || $( '#pms-update-payment-method-form' ).length > 0 )
            return

        var submitButton = $('.pms-form .pms-form-submit, .pms-form input[type="submit"], .pms-form button[type="submit"], .wppb-register-user input[type="submit"], .wppb-register-user button[type="submit"]').not('#pms-apply-discount, .login-submit #wp-submit')

        var data = $.pms_form_get_data( submitButton )

        data.action             = 'pms_update_payment_intent_connect'
        data.pms_nonce          = $('#pms-stripe-ajax-update-payment-intent-nonce').val()
        data.intent_secret      = $client_secret

        data.pmstkn_original = data.form_type == 'pms' ? $('.pms-form #pmstkn').val() : 'wppb_register'
        data.pmstkn          = ''

        return await $.post(pms.ajax_url, data, function (response) {

            if( typeof response == 'undefined' || response == '' )
                return false;

            response = JSON.parse( response )

            if ( response.status == 'requires_payment_method' ) {
                elements.fetchUpdates().then( function(elements_response){
                    if( typeof paymentSidebarPosition == 'function' ){
                        setTimeout( paymentSidebarPosition, 300 )
                    }

                    return true;
                })
            }

            return false;

        })

    }

    function stripeConnectProcessPayment( result, user_data, form_data, target_button ){

        // update nonce
        nonce_data = {}
        nonce_data.action = 'pms_update_nonce'

        $.post(pms.ajax_url, nonce_data, function (response) {

            response = JSON.parse(response)

            data                          = {}
            data.action                   = 'pms_process_payment'
            data.user_id                  = user_data.user_id
            data.payment_id               = user_data.payment_id
            data.subscription_id          = user_data.subscription_id
            data.subscription_plan_id     = user_data.subscription_plan_id
            data.pms_current_subscription = form_data.pms_current_subscription
            data.current_page             = window.location.href
            data.pms_nonce                = response
            data.form_type                = form_data.form_type ? form_data.form_type : ''
            data.pmstkn_original          = form_data.pmstkn ? form_data.pmstkn : ''
            data.setup_intent             = form_data.setup_intent ? form_data.setup_intent : ''

            if( data.setup_intent == '' )
                data.payment_intent = $client_secret_id
            else
                data.payment_intent = $client_secret_setup_id

            // to determine actual location for change subscription
            data.form_action          = form_data.form_action ? form_data.form_action : ''

            // for member data
            data.pay_gate             = form_data.pay_gate ? form_data.pay_gate : ''
            data.subscription_plans   = form_data.subscription_plans ? form_data.subscription_plans : ''

            if( data.subscription_plans )
                data['subscription_price_' + data.subscription_plans] = form_data['subscription_price_' + data.subscription_plans]

            // custom profile builder form name
            data.form_name            = form_data.form_name ? form_data.form_name : ''

            if( form_data.pms_default_recurring )
                data.pms_default_recurring = form_data.pms_default_recurring

            if ( form_data.pms_recurring )
                data.pms_recurring = form_data.pms_recurring

            if ( form_data.discount_code )
                data.discount_code = form_data.discount_code

            if ( form_data.group_name )
                data.group_name = form_data.group_name

            if ( form_data.group_description )
                data.group_description = form_data.group_description

            // add billing details
            if ( form_data.pms_billing_address )
                data.pms_billing_address = form_data.pms_billing_address

            if ( form_data.pms_billing_city )
                data.pms_billing_city = form_data.pms_billing_city
            
            if ( form_data.pms_billing_country )
                data.pms_billing_country = form_data.pms_billing_country

            if ( form_data.pms_billing_state )
                data.pms_billing_state = form_data.pms_billing_state

            if ( form_data.pms_billing_zip )
                data.pms_billing_zip = form_data.pms_billing_zip

            if ( form_data.pms_vat_number )
                data.pms_vat_number = form_data.pms_vat_number

            if ( form_data.wppb_referer_url	 )
                data.wppb_referer_url = form_data.wppb_referer_url

            $.post(pms.ajax_url, data, function (response) {

                response = JSON.parse(response)

                if( typeof response.redirect_url != 'undefined' && response.redirect_url )
                    window.location.replace( response.redirect_url )

            })

        })

    }

    /*
     * Stripe response handler
     *
     */
    function stripeTokenHandler( token, $form = null ) {

        if( $form === null )
            $form = $(payment_buttons).closest('form')

        $form.append( $('<input type="hidden" name="stripe_token" />').val( token.id ) )

        // We have to append a hidden input to the form to simulate that the submit
        // button has been clicked to have it to the $_POST
        var button_name = $form.find('input[type="submit"], button[type="submit"]').not('#pms-apply-discount').not('input[name="pms_redirect_back"]').attr('name')
        var button_value = $form.find('input[type="submit"], button[type="submit"]').not('#pms-apply-discount').not('input[name="pms_redirect_back"]').val()

        $form.append( $('<input type="hidden" />').val( button_value ).attr('name', button_name ) )

        $form.get(0).submit()

    }

    function pms_stripe_get_billing_details() {

        var data = {}

        var email = $( '.pms-form input[name="user_email"], .wppb-user-forms input[name="email"]' ).val()

        if( typeof email == 'undefined' || email == '' )
            data.email = $( '.pms-form input[name="pms_billing_email"]' ).val()

        if( typeof email != 'undefined' && email != '' )
            data.email = email.replace(/\s+/g, '') // remove any whitespace that might be present in the email

        var name = ''

        if( $( '.pms-billing-details input[name="pms_billing_first_name"]' ).length > 0 )
            name = name + $( '.pms-billing-details input[name="pms_billing_first_name"]' ).val() + ' '
        else if( $( '.pms-form input[name="first_name"], .wppb-user-forms input[name="first_name"]' ).length > 0 )
            name = name + $( '.pms-form input[name="first_name"], .wppb-user-forms input[name="first_name"]' ).val() + ' '

        if( $( '.pms-billing-details input[name="pms_billing_last_name"]' ).length > 0 )
            name = name + $( '.pms-billing-details input[name="pms_billing_last_name"]' ).val()
        else if( $( '.pms-form input[name="last_name"], .wppb-user-forms input[name="last_name"]' ).length > 0 )
            name = name + $( '.pms-form input[name="last_name"], .wppb-user-forms input[name="last_name"]' ).val()

        if( name.length > 1 )
            data.name = name

        if( $( '.pms-billing-details ').length > 0 ){

            data.address = {
                city        : $( '.pms-billing-details input[name="pms_billing_city"]' ).val(),
                country     : $( '.pms-billing-details input[name="pms_billing_country"]' ).val(),
                line1       : $( '.pms-billing-details input[name="pms_billing_address"]' ).val(),
                postal_code : $( '.pms-billing-details input[name="pms_billing_zip"]' ).val(),
                state       : $( '.pms-billing-details input[name="pms_billing_state"]' ).val()
            }

        }

        return data

    }

    function creditCardErrorsHandler( event ){

        if( event.complete == true )
            cardIsEmpty = false
        else
            cardIsEmpty = true

        if( typeof paymentSidebarPosition == 'function' ){
            setTimeout( paymentSidebarPosition, 300 )
        }

    }

    async function pms_stripe_get_payment_intents(){

        var data = {
            'action': 'pms_stripe_get_payment_intents'
        }

        return await $.post( pms.ajax_url, data, function( response ) {
            return response;
        })

    }

});
