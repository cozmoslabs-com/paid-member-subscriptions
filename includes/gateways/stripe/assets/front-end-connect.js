jQuery( async function( $ ) {

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

    var elements                     = false
    var elements_setup_intent        = false
    var update_elements_setup_intent = false
    var stripe                       = false

    var $payment_element        = ''
    var $elements_instance_slug = ''

    var cardIsEmpty = true

    var subscription_plan_selector = 'input[name=subscription_plans]'
    var paygate_selector           = 'input.pms_pay_gate'

    var stripe_appearance = {}

    if( pms.pms_elements_appearance_api )
        stripe_appearance = pms.pms_elements_appearance_api

    var StripeData = {
        stripeAccount: pms.stripe_connected_account
    }

    if( pms.stripe_locale )
        StripeData.locale = pms.stripe_locale

    stripe = Stripe( stripe_pk, StripeData )

    var stripe_payment_intent_options = {
        mode                 : 'payment',
        currency             : pms.currency,
        amount               : 1099,
        paymentMethodCreation: 'manual',
        setupFutureUsage     : 'off_session',
        appearance           : stripe_appearance,
    }

    var stripe_setup_intent_options = {
        mode                 : 'setup',
        currency             : pms.currency,
        paymentMethodCreation: 'manual',
        setupFutureUsage     : 'off_session',
        appearance           : stripe_appearance,
    }

    if( pms.pms_customer_session ){
        stripe_payment_intent_options.customerSessionClientSecret = pms.pms_customer_session
        stripe_setup_intent_options.customerSessionClientSecret   = pms.pms_customer_session
    }

    elements              = stripe.elements( stripe_payment_intent_options )
    elements_setup_intent = stripe.elements( stripe_setup_intent_options )

    stripeConnectInit()

    // Validate currency for currently selected subscription plan if MC add-on is active
    if ( pms.pms_mc_addon_active )
        await pms_stripe_validate_sdk_checkout_currency( $( $pms_checked_subscription ) );

    // Declare reCaptcha callback as already executed
    if( typeof pms_initialize_recaptcha_v3 == 'function' ){
        window.pmsRecaptchaCallbackExecuted = true
    }

    // Update Stripe Payment Intent on subscription plan change
    $(document).on('click', subscription_plan_selector, async function ( event ) {

        if ( pms.pms_mc_addon_active ){
            await pms_stripe_validate_sdk_checkout_currency( $(this) );
        }

        stripeConnectInit()

    })

    // Discount applied
    $(document).on('pms_discount_success', stripeConnectInit )
    $(document).on('pms_discount_error', stripeConnectInit )
    
    // Update elements price when taxes are applied or removed
    $(document).on('pms_tax_applied', function ( event, data ) {

        if( data.total > 0 ){
            elements.update( { amount: pms_stripe_convert_amount_to_cents( data.total ) } );
        }

    })

    $(document).on('pms_tax_removed', function ( event ) {

        if( typeof $pms_checked_subscription != 'undefined' ){

            let price = $pms_checked_subscription.data( 'price' )

            if( price > 0 ){
                elements.update( { amount: pms_stripe_convert_amount_to_cents( price ) } );
            }
        }

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
    // $(document).on( 'wppb_v3_recaptcha_success', stripeConnectPaymentGatewayHandler )

    $(document).on('submit', '.pms-form', async function (e) {

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

    async function stripeConnectPaymentGatewayHandler( e, target_button = false ){

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

        return pms_stripe_maybe_validate_recaptcha( current_button, e ).then( async function( recaptcha_response ){

            let target_elements = elements

            if( $.pms_checkout_is_setup_intents() )
                target_elements = elements_setup_intent

            const {error: submitError} = await target_elements.submit()

            if (submitError) {
                let message = ''

                if( submitError.message && submitError.message != '' ){
                    message = submitError.message
                } else {
                    message = 'An error occurred while processing your payment. Please try again.'
                }

                $.pms_stripe_add_credit_card_error( message )
                $.pms_form_scrollTo( '#pms-paygates-wrapper', current_button )
                return false
            }

            // Create the ConfirmationToken using the details collected by the Payment Element
            const {error, confirmationToken} = await stripe.createConfirmationToken({
                elements : target_elements,
            });

            if (error) {

                let message = ''

                if( error.message && error.message != '' ){
                    message = error.message
                } else {
                    message = 'An error occurred while processing your payment. Please try again.'
                }

                $.pms_stripe_add_credit_card_error( message )
                $.pms_form_scrollTo( '#pms-paygates-wrapper', current_button )
                return false
            }

            return pms_stripe_process_checkout( current_button, confirmationToken.id ).then( async function( response ){

                // Handle validation errors
                if( response.success == false && ( typeof response.data != 'undefined' || typeof response.wppb_errors != 'undefined' || typeof response.pms_errors != 'undefined' ) ){
                    pms_stripe_handle_validation_errors( response, current_button )

                    return false
                } else if( response.success == false && typeof response.type != 'undefined' && response.type == 'use_stripe_sdk' ){

                    var { error, paymentIntent } = await stripe.handleNextAction({
                        clientSecret: response.client_secret
                    });

                    if( error && error.payment_intent ){
                        paymentIntent = error.payment_intent
                    }

                    // Process the payment on the server
                    const server_response = await pms_stripe_process_payment( paymentIntent, response, current_button )

                    if ( typeof server_response.redirect_url != 'undefined' && server_response.redirect_url ){
                        window.location.replace( server_response.redirect_url )

                        return true
                    }

                    return false

                }

                //console.log( response );

                // Redirect to the URL if the response contains a redirect URL
                if ( typeof response.redirect_url != 'undefined' && response.redirect_url ){
                    window.location.replace( response.redirect_url )
                    return true
                }

                console.log( '[PMS Stripe] Something unexpected happened. Response: ' + response )

                return false;

            }) 

        })
    }

    async function pms_stripe_process_checkout( current_button, confirmationToken ){

        // grab all data from the form
        var data = await $.pms_form_get_data( current_button, true )

        if( confirmationToken ){
            data['stripe_confirmation_token'] = confirmationToken
        }

        if( data == false )
            return

        // prepare data
        var form_data = new FormData()

        for (var key in data) {
            form_data.append(key, data[key])
        }

        return fetch( pms.ajax_url, {
            method     : 'post',
            credentials: 'same-origin',   // Required for WordPress cookie authentication
            body       : form_data
        }).then(function (res) {
            return res.json()
        }).catch(error => {
            console.error('Something went wrong:', error)
            throw error
        })

    }

    async function pms_stripe_process_payment( payment_intent, user_data, target_button ){

        // update nonce
        nonce_data = {}
        nonce_data.action = 'pms_update_nonce'

        // Update nonce
        const nonce = await fetch(pms.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'pms_update_nonce'
            })
        }).then(function (res) {
            return res.json()
        }).catch(error => {
            console.error('Something went wrong:', error)
            throw error
        })

        // get form data
        var form_data = await $.pms_form_get_data( target_button )

        form_data.pmstkn_original = form_data.pmstkn
        form_data.pmstkn          = ''

        const data = {
            ...form_data,
            action              : 'pms_process_payment',
            user_id             : user_data.user_id,
            payment_id          : user_data.payment_id,
            subscription_id     : user_data.subscription_id,
            subscription_plan_id: user_data.subscription_plan_id,
            payment_intent      : payment_intent.id,
            current_page        : window.location.href,
            pms_nonce           : nonce
        };

        // Add subscription price if plans exist
        if ( data.subscription_plans ) {
            const priceKey = `subscription_price_${data.subscription_plans}`;

            if ( form_data[priceKey] ) {
                data[priceKey] = form_data[priceKey];
            }
        }

        // prepare data
        var request_data = new FormData()

        for (var key in data) {
            request_data.append(key, data[key])
        }

        // Process payment
        return await fetch(pms.ajax_url, {
            method     : 'POST',
            credentials: 'same-origin',
            body       : request_data
        }).then(function (res) {
            return res.json()
        }).catch(error => {
            console.error('Something went wrong:', error)
            throw error
        })

    }

    // Update Payment Method
    function stripeConnectInitUpdatePaymentMethod(){

        var $client_secret_setup_intent = $('.pms-form input[name="pms_stripe_connect_setup_intent"]').val()

        if( $client_secret_setup_intent && $client_secret_setup_intent.length > 0 ){

            update_elements_setup_intent = stripe.elements({ clientSecret: $client_secret_setup_intent, appearance: stripe_appearance })

            $update_element = update_elements_setup_intent.create( "payment" )
            $update_element.mount("#pms-stripe-payment-elements")

            // Show credit card form error messages to the user as they happpen
            $update_element.addEventListener('change', creditCardErrorsHandler )

            pms_stripe_hide_spinner()
        }

    }

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
            elements: update_elements_setup_intent,
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
            stripeConnectInitUpdatePaymentMethod();
            return false;
        // SetupIntent
        } else if ( $.pms_checkout_is_setup_intents() ) {
            target_elements_instance      = elements_setup_intent
            target_elements_instance_slug = 'setup_intents'
        // PaymentIntents
        } else {
            target_elements_instance      = elements
            target_elements_instance_slug = 'payment_intents'
        }

        let selected_subscription = jQuery( subscription_plan_selector + '[type=radio]' ).length > 0 ? jQuery( subscription_plan_selector + '[type=radio]:checked' ) : jQuery( subscription_plan_selector + '[type=hidden]' )

        if( target_elements_instance != false ){

            if( $payment_element == '' ){

                if( typeof selected_subscription != 'undefined' && target_elements_instance_slug == 'payment_intents' ){
                    
                    let price = selected_subscription.data( 'price' )

                    if( price > 0 ){
                        target_elements_instance.update( { amount: pms_stripe_convert_amount_to_cents( price ) } );
                    }
                }

                $payment_element = target_elements_instance.create( "payment" )
                $payment_element.mount("#pms-stripe-payment-elements")

                // Show credit card form error messages to the user as they happpen
                $payment_element.addEventListener('change', creditCardErrorsHandler )

            } else {

                // Update the amount of the payment element
                if( typeof selected_subscription != 'undefined' && target_elements_instance_slug == 'payment_intents'  ){
                    let price = selected_subscription.data( 'price' )

                    if( price > 0 ){
                        target_elements_instance.update( { amount: pms_stripe_convert_amount_to_cents( price ) } );
                    }
                }

                if( $elements_instance_slug != target_elements_instance_slug ){

                    $payment_element.destroy()

                    $payment_element = target_elements_instance.create( "payment" )
                    $payment_element.mount("#pms-stripe-payment-elements")

                    // Show credit card form error messages to the user as they happpen
                    $payment_element.addEventListener('change', creditCardErrorsHandler )

                }

            }

            $elements_instance_slug = target_elements_instance_slug

            pms_stripe_hide_spinner()

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

        if( updating_payment_intent )
            return

        updating_payment_intent = true

        var submitButton = $('.pms-form .pms-form-submit, .pms-form input[type="submit"], .pms-form button[type="submit"], .wppb-register-user input[type="submit"], .wppb-register-user button[type="submit"]').not('#pms-apply-discount, .login-submit #wp-submit')

        var data = $.pms_form_get_data( submitButton )

        data.action             = 'pms_update_payment_intent_connect'
        data.pms_nonce          = $('#pms-stripe-ajax-update-payment-intent-nonce').val()
        data.intent_secret      = $client_secret

        data.pmstkn_original = data.form_type == 'pms' ? $('.pms-form #pmstkn').val() : 'wppb_register'
        data.pmstkn          = ''

        return await $.post(pms.ajax_url, data, function (response) {

            if( typeof response == 'undefined' || response == '' ){
                updating_payment_intent = false
                return false;
            }

            response = JSON.parse( response )

            if ( response.status == 'requires_payment_method' ) {
                updating_payment_intent = false

                elements.fetchUpdates().then( function(elements_response){
                    if( typeof paymentSidebarPosition == 'function' ){
                        setTimeout( paymentSidebarPosition, 300 )
                    }

                    return true;
                })
            }

            updating_payment_intent = false

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
            data.user_consent_logged_in   = form_data.user_consent_logged_in ? form_data.user_consent_logged_in : ''

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

    function pms_stripe_handle_validation_errors( response, current_button ){

        var form_type = $('.wppb-register-user .wppb-subscription-plans').length > 0 ? 'wppb' : $('.pms-ec-register-form').length > 0 ? 'pms_email_confirmation' : 'pms'

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

    }

    async function pms_stripe_maybe_validate_recaptcha( current_button, event = null ){

        if( typeof pms_initialize_recaptcha_v3 == 'function' ){

            let form = current_button.closest('form')

            var recaptcha_field = jQuery('.pms-recaptcha', form )

            if( recaptcha_field.length > 0 ){

                return await pms_initialize_recaptcha_v3( event, form )
    
            }

        }
        
        let wppb_form = current_button.closest('.wppb-register-user')

        if( wppb_form.length > 0 && wppb_form[0].length > 0 && typeof wppbInitializeRecaptchaV3 == 'function' ){

            let wppb_recaptcha_field = jQuery('.wppb-recaptcha .wppb-recaptcha-element', wppb_form )

            if( wppb_recaptcha_field.length > 0 ){
                return await wppbInitializeRecaptchaV3( event, wppb_form )
            }
            
        }

        return true

    }

    function pms_stripe_convert_amount_to_cents( amount ){

        let currency = pms.currency

        // List of zero-decimal currencies
        const zero_decimal_currencies = [
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 
            'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'
        ];

        // If currency is not in zero-decimal list, multiply by 100
        if ( !zero_decimal_currencies.includes( currency ) ) {
            amount = amount * 100;
        }

        // Round to ensure we have a whole number
        return Math.round( amount );
    }

    /**
     * Validate custom currency
     *
     * @param subscriptionPlan
     * @returns {Promise<*>}
     */
    async function pms_stripe_validate_sdk_checkout_currency( subscriptionPlan ) {

        pms_stripe_show_spinner()

        return $.ajax({
            url: pms.ajax_url,
            type: 'POST',
            data: {
                action              : 'pms_validate_sdk_currency',
                pms_nonce           : pms.pms_validate_currency_nonce,
                subscription_plan_id: subscriptionPlan.val(),
                pms_mc_currency     : subscriptionPlan.data('mc_currency'),
                pay_gate            : 'stripe_connect'
            },
            dataType: 'json',
        }).done( function ( response ) {

            if ( response && response.success && response.currency )
                elements.update( { currency: response.currency.toLowerCase() } )

            pms_stripe_hide_spinner()

            return;

        });
    }

    function pms_stripe_hide_spinner(){

        jQuery('#pms-stripe-payment-elements').show()
        jQuery( '#pms-stripe-connect .pms-spinner__holder' ).hide()

    }

    function pms_stripe_show_spinner(){

        jQuery('#pms-stripe-payment-elements').hide()
        jQuery( '#pms-stripe-connect .pms-spinner__holder' ).show()

    }

});
