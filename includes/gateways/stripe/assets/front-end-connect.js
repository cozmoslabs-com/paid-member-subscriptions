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
        $client_secret_setup_intent = intents.setup_intent

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
        stripeConnectUpdatePaymentIntent()

    })

    var $payment_element        = ''
    var $elements_instance_slug = ''

    var cardIsEmpty = true

    var payment_id    = 0
    var form_location = ''

    var subscription_plan_selector = 'input[name=subscription_plans]'
    var pms_checked_subscription   = $( subscription_plan_selector + '[type=radio]' ).length > 0 ? $( subscription_plan_selector + '[type=radio]:checked' ) : $( subscription_plan_selector + '[type=hidden]' )

    // Update Stripe Payment Intent on subscription plan change
    $(document).on('click', subscription_plan_selector, function ( event ) {

        stripeConnectInit()
        stripeConnectUpdatePaymentIntent()

    })

    // Discount applied
    $(document).on('pms_discount_success', function ( event ) {

        stripeConnectInit()
        stripeConnectUpdatePaymentIntent()

    })

    $(document).on('change', '#pms_billing_country, #pms_billing_state, #pms_billing_city, .pms_pwyw_pricing', function () {

        // TODO: this needs some throttling for .pms_pwyw_pricing
        stripeConnectUpdatePaymentIntent()

    })

    $(document).on( 'input', '#pms_vat_number', function(){

        stripeConnectUpdatePaymentIntent()

    })

    // Show credit card details on the update payment method form
    if ( $( '#pms-update-payment-method-form #pms-stripe-payment-elements' ).length > 0 ){
        $('.pms-credit-card-information').show()
        $('.pms-billing-details').show()
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

        removeErrors()

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
        if (cardIsEmpty === true ){
            addValidationErrors([{ target: 'credit_card', message: pms.invalid_card_details_error } ], current_button )

            if( typeof paymentSidebarPosition == 'function' ){
                paymentSidebarPosition()
            }

            return
        }

        // Update Payment Intent
        //stripeConnectUpdatePaymentIntent()

        // grab all data from the form
        var data = stripeConnectGetFormData( current_button, true )

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
                        addValidationErrors( response.data, current_button )
                    // Profile Builder form
                    } else {

                        // Add PMS related errors (Billing Fields)
                        // These are added first because the form will scroll to the error and these
                        // are always placed at the end of the WPPB form
                        if( response.pms_errors && response.pms_errors.length > 0 )
                            addValidationErrors( response.pms_errors, current_button )

                        // Add WPPB related errors
                        if( typeof response.wppb_errors == 'object' )
                            addWPPBValidationErrors( response.wppb_errors, current_button )

                    }

                    jQuery(document).trigger( 'pms_checkout_validation_error', response, current_button )

                } else {
                    console.log( 'something unexpected happened' )
                }

            }

        })

    }

    // Update Payment Method
    function stripeConnectUpdatePaymentMethod( e, target_button = false ){

        e.preventDefault()

        removeErrors()

        var current_button = $(this)

        if ( target_button != false )
            current_button = $( target_button )

        //Disable the button
        current_button.attr('disabled', true)

        // Add error if credit card was not completed
        if (cardIsEmpty === true) {
            addValidationErrors([{ target: 'credit_card', message: pms.invalid_card_details_error }], current_button)
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

                addValidationErrors(errors, current_button)
            } else if (result.error && result.error.type && result.error.type == 'validation_error')
                stripeResetSubmitButton(current_button)
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
        } else if ( pms_stripe_is_setup_intents_checkout() ) {
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
            jQuery( '.pms-spinner__holder' ).hide()

            if( typeof paymentSidebarPosition == 'function' ){
                setTimeout( paymentSidebarPosition, 300 )
            }

        }

    }

    function stripeConnectUpdatePaymentIntent(){

        if( !$client_secret || !( $client_secret.length > 0 ) )
            return

        // Don't make this call when a Free Trial subscription is selected since we use the prepared SetupIntent
        if ( pms_stripe_is_setup_intents_checkout() || $( '#pms-update-payment-method-form' ).length > 0 )
            return

        jQuery('#pms-stripe-payment-elements').hide()
        jQuery( '.pms-spinner__holder' ).show()

        var submitButton = $('.pms-form .pms-form-submit, .pms-form input[type="submit"], .pms-form button[type="submit"], .wppb-register-user input[type="submit"], .wppb-register-user button[type="submit"]').not('#pms-apply-discount, .login-submit #wp-submit')

        var data = stripeConnectGetFormData( submitButton )

        data.action             = 'pms_update_payment_intent_connect'
        data.pms_nonce          = $('#pms-stripe-ajax-update-payment-intent-nonce').val()
        data.intent_secret      = $client_secret

        data.pmstkn_original = data.form_type == 'pms' ? $('.pms-form #pmstkn').val() : 'wppb_register'
        data.pmstkn          = ''

        $.post(pms.ajax_url, data, function (response) {

            if( typeof response == 'undefined' || response == '' )
                return;

            response = JSON.parse( response )

            if ( response.status == 'requires_payment_method' ) {
                elements.fetchUpdates().then( function(elements_response){
                    jQuery('#pms-stripe-payment-elements').show()
                    jQuery( '.pms-spinner__holder' ).hide()

                    if( typeof paymentSidebarPosition == 'function' ){
                        setTimeout( paymentSidebarPosition, 300 )
                    }
                })
            }

        })

    }

    function stripeConnectProcessPayment( result, user_data, form_data, target_button ){

        let payment_intent = ''

        if( result.error ){

            if ( result.error.payment_intent )
                payment_intent = result.error.payment_intent
            else if( result.error.setup_intent )
                payment_intent = result.error.setup_intent

        } else if( result.paymentIntent )
            payment_intent = result.paymentIntent
        else if( result.setupIntent )
            payment_intent = result.setupIntent

        // update nonce
        nonce_data = {}
        nonce_data.action = 'pms_stripe_update_nonce'

        $.post(pms.ajax_url, nonce_data, function (response) {

            response = JSON.parse(response)

            data                          = {}
            data.action                   = 'pms_stripe_connect_process_payment'
            data.user_id                  = user_data.user_id
            data.payment_id               = user_data.payment_id
            data.subscription_id          = user_data.subscription_id
            data.subscription_plan_id     = user_data.subscription_plan_id
            data.pms_current_subscription = form_data.pms_current_subscription
            data.payment_intent           = payment_intent.id
            data.current_page             = window.location.href
            data.pms_nonce                = response
            data.form_type                = form_data.form_type ? form_data.form_type : ''
            data.pmstkn_original          = form_data.pmstkn ? form_data.pmstkn : ''
            data.setup_intent             = form_data.setup_intent ? form_data.setup_intent : ''

            // to determine actual location for change subscription
            data.form_action          = form_data.form_action ? form_data.form_action : ''

            // for member data
            data.pay_gate             = form_data.pay_gate ? form_data.pay_gate : ''
            data.subscription_plans   = form_data.subscription_plans ? form_data.subscription_plans : ''

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

    function stripeConnectGetFormData( current_button, verify_captcha = false ) {

        if( !current_button )
            return false

        var form = $(current_button).closest('form')

        // grab all data from the form
        var data = form.serializeArray().reduce(function (obj, item) {
            obj[item.name] = item.value
            return obj
        }, {})

        // setup our custom AJAX action and add the current page URL
        data.action = 'pms_process_checkout'
        data.current_page = window.location.href
        data.pms_nonce = $('#pms-stripe-ajax-payment-intent-nonce').val()
        data.form_type = $('.wppb-register-user .wppb-subscription-plans').length > 0 ? 'wppb' : $('.pms-ec-register-form').length > 0 ? 'pms_email_confirmation' : 'pms'

        /**
         * Add the name of the submit button as a key to the request data
         * this is necessary for logged in actions like change, retry or renew subscription
         */
        data[current_button.attr('name')] = true

        /**
         * Add the form_action field from the form necessary for Change Subscription form requests
         */
        if  ($('input[name="form_action"]', form) && $('input[name="form_action"]', form).length > 0 )
            data.form_action = $('input[name="form_action"]', form).val()

        // add WPPB fields metadata to request if necessary
        if ( data.form_type == 'wppb' ){
            data.wppb_fields = get_wppb_form_fields( current_button )
        }

        // if user is logged in, set form type to current form
        if ( $('body').hasClass('logged-in') )
            data.form_type = $('input[type="submit"], button[type="submit"]', form).not('#pms-apply-discount').not('input[name="pms_redirect_back"]').attr('name')

        // This will be used to create a Setup Intent instead of a Payment Intent
        // in case of a trial subscription
        if ( pms_stripe_is_setup_intents_checkout() )
            data.setup_intent = true

        if( data.pms_current_subscription )
            data.subscription_id = data.pms_current_subscription

        // Recaptcha Compatibility
        // If reCaptcha field was not validated, don't send data to the server
        if ( verify_captcha && typeof data['g-recaptcha-response'] != 'undefined' && data['g-recaptcha-response'] == '' ) {

            if (data.form_type == 'wppb')
                addWPPBValidationErrors_old({ recaptcha: { field: 'recaptcha', error: '<span class="wppb-form-error">This field is required</span>' } }, current_button)
            else
                addValidationErrors({ 'recaptcha-register': { target: 'recaptcha-register', message: 'Please complete the reCaptcha.' } }, current_button)

            stripeResetSubmitButton(current_button)

            return false

        }

        return data

    }

    function stripeConnectValidateForm( current_button = '' ){

        if( current_button == '' )
            current_button = $('.pms-form .pms-form-submit, .pms-form input[type="submit"], .pms-form button[type="submit"]').not('#pms-apply-discount')

        var data = stripeConnectGetFormData( current_button )

        // cache nonce and change action
        if( data.pmstkn ){
            data.pmstkn_original = data.pmstkn
            data.pmstkn          = ''
        }

        data.action = 'pms_validate_checkout'

        return $.post(pms.ajax_url, data, function (response) {

            if (response) {

                response = JSON.parse(response)

                if (response.success == false) {

                    // Paid Member Subscription forms
                    if (response.data && (data.form_type == 'pms' || data.form_type == 'pms_email_confirmation')) {
                        addValidationErrors(response.data, current_button)
                        // Profile Builder form
                    } else {

                        // Add PMS related errors (Billing Fields)
                        // These are added first because the form will scroll to the error and these
                        // are always placed at the end of the WPPB form
                        if (response.pms_errors.length > 0)
                            addValidationErrors(response.pms_errors, current_button)

                        // Add WPPB related errors
                        if (typeof response.wppb_errors == 'object')
                            addWPPBValidationErrors(response.wppb_errors, current_button)

                    }

                } else {
                    console.log('something unexpected happened')
                }

            }

            return false

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

    function stripeResetSubmitButton( target ) {

        if( !target.data || !target.data( 'original-value' ) || typeof target.data( 'original-value' ) == undefined ){
            value = target.val()
        } else {
            value = target.data( 'original-value' )
        }

        setTimeout( function() {
            target.attr( 'disabled', false ).removeClass( 'pms-submit-disabled' ).val( value ).blur()

            if ( $( target ).is('button') )
                $( target ).text( value )

        }, 1 )

    }

    function handleServerResponse( response, payment_button ){
        //console.log( response )

        if( payment_id == 0 )
            payment_id = response.payment_id

        if( form_location == '' )
            form_location = response.form_location

        if( response.validation_errors )
            addValidationErrors( response.errors, payment_button )
        else if ( response.error ) {
            //console.log( 'error' )

            // If error, redirect to payment failed page
            if( response.redirect_url )
                window.location.replace( response.redirect_url )

        } else if ( response.requires_action ) {
            //console.log( 'requires auth ')

            // Use Stripe.js to handle required card action
            stripe.handleCardAction(
                response.payment_intent_client_secret
            ).then(function(result) {
                if ( result.error ) {
                    //console.log( '3D Secure confirmation failed' )

                    //send error data to server
                    var data                    = {}
                        data.action             = 'pms_failed_payment_authentication'
                        data.payment_id         = payment_id
                        data.form_location      = form_location
                        data.current_page       = window.location.href
                        data.error              = result.error
                        data.subscription_plans = pms_checked_subscription.val()
                        data.pms_recurring      = getAutoRenewalStatus()

                    $.post( pms.ajax_url, data, function( response ) {
                        handleServerResponse( JSON.parse( response ), payment_button );
                    });

                } else {
                    // console.log( 'payment intent needs to be confirmed again on the server' )
                    // console.log( result )

                    var data                    = {}
                        data.action             = 'pms_confirm_payment_intent'
                        data.stripe_token       = result.paymentIntent.id
                        data.payment_id         = payment_id
                        data.form_location      = form_location
                        data.current_page       = window.location.href
                        data.subscription_plans = pms_checked_subscription.val()
                        data.pms_recurring      = getAutoRenewalStatus()

                    $.post( pms.ajax_url, data, function( response ) {
                        handleServerResponse( JSON.parse( response ), payment_button );
                    });
                }
            });
        } else {
            //console.log( 'success' )

            //create a dummy form and submit it
            var redirect_url = ''

            if( !response.redirect_url )
                redirect_url = window.location.href
            else
                redirect_url = response.redirect_url

            var form = $('<form action="' + redirect_url + '" method="post">' +
                        '<input type="text" name="pms_register" value="1" /></form>')

            $('body').append(form)
                form.submit()
        }

    }

    function addValidationErrors( errors, payment_button ){
        var scrollLocation = '';

        $.each( errors, function(index, value){

            if( value.target == 'form_general' ){
                $.pms_add_general_error( value.message )

                scrollLocation = '.pms-form'
            } else if( value.target == 'subscription_plan' || value.target == 'subscription_plans' ){
                $.pms_add_subscription_plans_error( value.message )

                if( scrollLocation == '' )
                    scrollLocation = '.pms-field-subscriptions'
            } else if( value.target == 'credit_card' ){
                $.pms_stripe_add_credit_card_error( value.message )

                if( scrollLocation == '' )
                    scrollLocation = '#pms-paygates-wrapper'
            } else if( value.target == 'recaptcha-register'){

                $field_wrapper = $( '#pms-recaptcha-register-wrapper', $( payment_button ).closest( 'form' ) );

                error = '<p>' + value.message + '</p>';

                if( $field_wrapper.find('.pms_field-errors-wrapper').length > 0 )
                    $field_wrapper.find('.pms_field-errors-wrapper').html( error );
                else
                    $field_wrapper.append('<div class="pms_field-errors-wrapper pms-is-js">' + error + '</div>');


            } else {
                $.pms_add_field_error( value.message, value.target )

                if( scrollLocation == '' && value.target.indexOf('pms_billing') !== -1 )
                    scrollLocation = '.pms-billing-details'
                else
                    scrollLocation = '.pms-form'
            }

        })

        if( $(payment_button).attr('name') == 'pms_update_payment_method' && scrollLocation == '#pms-paygates-wrapper' )
            scrollLocation = '#pms-credit-card-information';

        scrollTo( scrollLocation, payment_button )
    }

    function removeErrors(){
        $('.pms_field-errors-wrapper').remove()

        if ( $('.pms-stripe-error-message').length > 0 )
            $('.pms-stripe-error-message').remove()

        if( $( '.wppb-register-user' ).length > 0 ){

            $( '.wppb-form-error' ).remove()

            $( '.wppb-register-user .wppb-form-field' ).each( function(){

                $(this).removeClass( 'wppb-field-error' )

            })

        }
    }

    function scrollTo( scrollLocation, payment_button ){
        var form = $(scrollLocation)[0]

        if( typeof form == 'undefined' ){
            stripeResetSubmitButton( payment_button )
            return
        }

        var coord  = form.getBoundingClientRect().top + window.pageYOffset
        var offset = -170

        window.scrollTo({
            top      : coord + offset,
            behavior : 'smooth'
        })

        stripeResetSubmitButton( payment_button )

    }

    function getAutoRenewalStatus(){
        if( $( 'input[name="pms_default_recurring"]' ).val() == 2 )
            return 1

        if( pms_checked_subscription.data( 'recurring' ) == 2 )
            return 1

        if( $( 'input[name="pms_recurring"]' ).is(':visible') && $( 'input[name="pms_recurring"]' ).is(':checked') )
            return 1

        if( !$( 'input[name="pms_recurring"]' ).is(':visible') && $( 'input[name="intent_id"]' ).length > 0 )
            return 1

        return 0
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

    $.pms_stripe_add_credit_card_error = function( error ) {

        if( error == '' || error == 'undefined' )
            return false

        $field_wrapper  = $('#pms-credit-card-information');

        error = '<p>' + error + '</p>'

        if( $field_wrapper.find('.pms_field-errors-wrapper').length > 0 )
            $field_wrapper.find('.pms_field-errors-wrapper').html( error )
        else
            $field_wrapper.append('<div class="pms_field-errors-wrapper pms-is-js">' + error + '</div>')

    }

    function pms_stripe_is_setup_intents_checkout(){

        let selected_plan = $( subscription_plan_selector + '[type=radio]' ).length > 0 ? $( subscription_plan_selector + '[type=radio]:checked' ) : $( subscription_plan_selector + '[type=hidden]' )

        if ( typeof selected_plan.data('trial') != 'undefined' && selected_plan.data('trial') == '1' && !$.pms_plan_has_signup_fee( selected_plan ) )
            return true
        // If a 100% discount code is used, initial amount will be 0
        else if ( $( 'input[name="discount_code"]' ).length > 0 && $( 'input[name="discount_code"]' ).val().length > 0 && typeof selected_plan.data('price') != 'undefined' && selected_plan.data('price') == '0' )
            return true
        // Pro-rated subscriptions
        else if ( $.pms_plan_is_prorated(selected_plan) && typeof selected_plan.data('price') != 'undefined' && selected_plan.data('price') == '0' )
            return true

        return false

    }

    function get_wppb_form_fields( current_button ){

        var fields = {}

        // Taken from Multi Step Forms
        jQuery( 'li.wppb-form-field', jQuery(current_button).closest('form') ).each( function() {

            if( jQuery( this ).attr( 'class' ).indexOf( 'heading' ) == -1 && jQuery( this ).attr( 'class' ).indexOf( 'wppb_billing' ) == -1
                && jQuery( this ).attr( 'class' ).indexOf( 'wppb_shipping' ) == -1 && jQuery( this ).attr( 'class' ).indexOf( 'wppb-shipping' ) == -1 ) {

                var meta_name;

                if( jQuery( this ).hasClass( 'wppb-repeater' ) || jQuery( this ).parent().attr( 'data-wppb-rpf-set' ) == 'template' || jQuery( this ).hasClass( 'wppb-recaptcha' ) ) {
                    return true;
                }

                if( jQuery( this ).hasClass( 'wppb-send-credentials-checkbox' ) )
                    return true;

                /* exclude conditional required fields */
                if( jQuery( this ).find('[conditional-value]').length !== 0 ) {
                    return true;
                }

                fields[jQuery( this ).attr( 'id' )] = {};
                fields[jQuery( this ).attr( 'id' )]['class'] = jQuery( this ).attr( 'class' );

                if( jQuery( this ).hasClass( 'wppb-woocommerce-customer-billing-address' ) ) {
                    meta_name = 'woocommerce-customer-billing-address';
                } else if( jQuery( this ).hasClass( 'wppb-woocommerce-customer-shipping-address' ) ) {
                    meta_name = 'woocommerce-customer-shipping-address';

                    if ( !jQuery('.wppb-woocommerce-customer-billing-address #woo_different_shipping_address', jQuery(current_button).closest('form') ).is( ':checked' ) ) {
                        return true;
                    }
                } else {
                    meta_name = jQuery( this ).find( 'label' ).attr( 'for' );

                    //fields[jQuery( this ).attr( 'id' )]['required'] = jQuery( this ).find( 'label' ).find( 'span' ).attr( 'class' );
                    fields[jQuery( this ).attr( 'id' )]['title'] = jQuery( this ).find( 'label' ).first().text().trim();
                }

                fields[jQuery( this ).attr( 'id' )]['meta-name'] = meta_name;

                if( jQuery( this ).parent().parent().attr( 'data-wppb-rpf-meta-name' ) ) {
                    var repeater_group = jQuery( this ).parent().parent();

                    fields[jQuery( this ).attr( 'id' )]['extra_groups_count'] = jQuery( repeater_group ).find( '#' + jQuery( repeater_group ).attr( 'data-wppb-rpf-meta-name' ) + '_extra_groups_count' ).val();
                }

                if( jQuery( this ).hasClass( 'wppb-woocommerce-customer-billing-address' ) ) {
                    var woo_billing_fields_fields = {};

                    jQuery('ul.wppb-woo-billing-fields li.wppb-form-field', jQuery(current_button).closest('form') ).each( function() {
                        if( ! jQuery( this ).hasClass( 'wppb_billing_heading' ) ) {
                            woo_billing_fields_fields[jQuery( this ).find( 'label' ).attr( 'for' )] = jQuery( this ).find( 'label' ).text();
                        }
                    } );

                    fields[jQuery( this ).attr( 'id' )]['fields'] = woo_billing_fields_fields;
                }

                if( jQuery( this ).hasClass( 'wppb-woocommerce-customer-shipping-address' ) ) {
                    var woo_shipping_fields_fields = {};

                    jQuery('ul.wppb-woo-shipping-fields li.wppb-form-field', jQuery(current_button).closest('form') ).each( function() {
                        if( ! jQuery( this ).hasClass( 'wppb_shipping_heading' ) ) {
                            woo_shipping_fields_fields[jQuery( this ).find( 'label' ).attr( 'for' )] = jQuery( this ).find( 'label' ).text();
                        }
                    } );

                    fields[jQuery( this ).attr( 'id' )]['fields'] = woo_shipping_fields_fields;
                }
            }
        } )

        return fields

    }

    function addWPPBValidationErrors( errors, current_button ){

        let scroll = false

        // errors is of the form: FIELD_ID => FIELD_ERROR
        jQuery.each( errors, function( key, value ) {

            let field = jQuery('#wppb-form-element-' + key )

            field.addClass( 'wppb-field-error' )
            field.append( value )

            scroll = true

        })

        if( scroll )
            scrollTo( '.wppb-register-user', current_button )

    }

    // Taken from MultiStep Forms code
    function addWPPBValidationErrors_old( errors, current_button ){

        let form   = $(current_button).closest('form')
        let scroll = false

        jQuery.each( errors, function( key, value ) {

            var meta_name

            if( value['type'] !== undefined && value['type'] == 'woocommerce' )
                meta_name = $( form ).find( '.wppb_' +  value['field'] )
            else
                meta_name = $( form ).find( '.wppb-' +  value['field'] )

            if( meta_name.length > 1 ) {
                jQuery.each( meta_name, function( key2, value2 ) {
                    if ( $( value2, form ).find( 'label' ).attr( 'for' ) == key ) {
                        $( $( value2, form ) ).addClass( 'wppb-field-error' ).append( value['error'] )
                    }
                })
            } else {
                meta_name.addClass( 'wppb-field-error' ).append( value['error'] )
            }

            scroll = true

        })

        if( scroll )
            scrollTo( '.wppb-register-user', current_button )

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
