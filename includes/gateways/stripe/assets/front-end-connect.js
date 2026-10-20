/**
 * Initialize Stripe Connect payment gateway
 * @param {jQuery} $ - jQuery instance
 */
async function pms_stripe_maybe_load_gateway( $ ) {

    if( !( $('#stripe-pk').length > 0 ) ){
        return false
    }

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
    var recreating_payment_element = false
    var email_change_debounce_timer = null

    var subscription_plan_selector = 'input[name=subscription_plans]'
    var paygate_selector           = 'input.pms_pay_gate'

    var apple_pay_interval_units = [ 'day', 'week', 'month', 'year' ]

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
        appearance           : stripe_appearance,
    }

    var stripe_setup_intent_options = {
        mode                 : 'setup',
        currency             : pms.currency,
        paymentMethodCreation: 'manual',
        appearance           : stripe_appearance,
    }

    if( pms.off_session_payments && pms.off_session_payments == 1 ){
        stripe_payment_intent_options.setupFutureUsage = 'off_session'
        stripe_setup_intent_options.setupFutureUsage   = 'off_session'
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
        
        jQuery('.pms-form').off('submit', pms_initialize_recaptcha_v3 );
    }

    if( typeof wppbInitializeRecaptchaV3 == 'function' ){
        window.wppbRecaptchaCallbackExecuted = true
    }

    // Update Stripe Payment Intent on subscription plan change
    $(document).on('click', subscription_plan_selector, async function ( event ) {

        if ( pms.pms_mc_addon_active ){
            await pms_stripe_validate_sdk_checkout_currency( $(this) );
        }

        stripeConnectInit()

    })

    /**
     * Resolves the Stripe setup_future_usage for a plan input from its recurring config and the customer's renewal choice
     *
     * - 'off_session' when the plan recurs (always-recurring, opt-in with the auto-renew box ticked, or the site default forcing recurring)
     * - null when it does not recur, undefined when the input carries no recurring config so callers leave the current value untouched
     * - shared so the primary plan and add-on items (e.g. Order Bumps) derive setup_future_usage from one rule and stay in agreement with the server-side PaymentIntent
     *
     */
    function pms_stripe_connect_setup_future_usage_for_input( $input ) {

        let recurring = $input.data( 'recurring' )

        if( recurring == 0 || recurring == 1 ){

            let default_recurring = $('input[type="hidden"][name="pms_default_recurring"]').val()

            if( default_recurring == 2 )
                return 'off_session'
            else if( default_recurring == 3 )
                return null
            else
                return $('.pms-subscription-plan-auto-renew input[name="pms_recurring"]').prop('checked') ? 'off_session' : null

        } else if( recurring == 2 ){
            return 'off_session'
        } else if( recurring == 3 ){
            return null
        }

        return undefined

    }

    // expose the rule so add-ons resolve setup_future_usage with the same logic as the primary plan
    $.pms_stripe_connect_setup_future_usage_for_input = pms_stripe_connect_setup_future_usage_for_input

    function pms_stripe_get_selected_subscription() {

        return jQuery( subscription_plan_selector + '[type=radio]' ).length > 0 ? jQuery( subscription_plan_selector + '[type=radio]:checked' ) : jQuery( subscription_plan_selector + '[type=hidden]' )

    }

    function pms_stripe_get_first_payment_amount( $input ) {

        let has_mc_price = $input.data( 'mc_price' ) !== undefined && $input.data( 'mc_price' ) !== null
        let price        = parseFloat( has_mc_price ? $input.data( 'mc_price' ) : $input.data( 'price' ) )

        // A discount response already folds the sign-up fee into the discounted price
        if( !$input.data( 'discounted-price' ) ){

            let has_mc_sign_up_fee = $input.data( 'mc_sign_up_fee' ) !== undefined && $input.data( 'mc_sign_up_fee' ) !== null
            let sign_up_fee        = parseFloat( has_mc_sign_up_fee ? $input.data( 'mc_sign_up_fee' ) : $input.data( 'sign_up_fee' ) )

            // With a free trial only the sign-up fee is charged initially
            if( !isNaN( sign_up_fee ) && sign_up_fee > 0 )
                price = $.pms_plan_has_trial( $input ) ? sign_up_fee : price + sign_up_fee

        }

        // Tax runs its own pms_tax_applied update too, so both must produce the same taxed total whichever handler runs last
        if( typeof $.pms_tax_apply_to_amount == 'function' )
            price = $.pms_tax_apply_to_amount( price, $input )

        return price

    }

    function pms_stripe_get_recurring_billing_for_input( $input ) {

        if( $input.length == 0 || !pms.apple_pay.management_url )
            return null

        // Installment plans are charged off-session even when the checkout is not recurring
        let installments = $input.data( 'limit_payment_cycles' ) == 'yes'

        if( pms_stripe_connect_setup_future_usage_for_input( $input ) !== 'off_session' && !installments )
            return null

        let interval_unit  = 'year'
        let interval_count = 1
        let fixed_period   = $input.data( 'fixed_membership' ) == 'on'

        // Fixed-period plans only renew when renewals are allowed, and then they renew yearly
        if( fixed_period && $input.data( 'allow_renew' ) != 'on' )
            return null

        if( !fixed_period ){
            interval_unit  = $input.data( 'duration_unit' )
            interval_count = Number( $input.data( 'duration' ) )
        }

        if( !Number.isInteger( interval_count ) || interval_count <= 0 || !apple_pay_interval_units.includes( interval_unit ) )
            return null

        // Without a start date Apple Pay shows the recurring amount as "Starting Now"
        let start_date = Number( $input.data( 'billing_start' ) ) > 0 ? new Date( Number( $input.data( 'billing_start' ) ) * 1000 ) : null
        let end_date   = null

        if( installments ){

            // The initial payment is the first installment, unless a free trial replaces it
            let renewals = Number( $input.data( 'number_of_payments' ) ) - ( $.pms_plan_has_trial( $input ) ? 0 : 1 )

            if( !( renewals > 0 ) )
                return null

            if( start_date !== null )
                end_date = renewals == 1 ? start_date : pms_stripe_add_interval( start_date, interval_unit, interval_count * ( renewals - 1 ) )

        }

        let has_mc_price = $input.data( 'mc_price' ) !== undefined && $input.data( 'mc_price' ) !== null
        let price

        // Renewals bill the plan price alone: no sign-up fee, no Pro-Rate credit, and a discount code only when it applies to recurring payments
        if( $input.data( 'discounted-price' ) == true && $input.data( 'discounted-recurring-price' ) !== undefined )
            price = parseFloat( $input.data( 'discounted-recurring-price' ) )
        else if( $input.data( 'discounted-price' ) == true )
            price = parseFloat( has_mc_price ? $input.data( 'mc-price-original' ) : $input.data( 'price-original' ) )
        else
            price = parseFloat( has_mc_price ? $input.data( 'mc_price' ) : $input.data( 'price' ) )

        // Pro-Rate lowers data-price to the first charge, keeping the full price in original_price for free periods
        if( $.pms_plan_is_prorated( $input ) && $input.data( 'discounted-price' ) != true ){

            if( $input.data( 'original_price' ) !== undefined )
                price = parseFloat( $input.data( 'original_price' ) )
            else
                price = pms_stripe_round( price + parseFloat( $input.data( 'prorated_discount' ) ), 2 )

        }

        // Renewals are charged with tax on top, so show the taxed amount when the Tax add-on is active
        if( typeof $.pms_tax_apply_to_amount == 'function' )
            price = $.pms_tax_apply_to_amount( price, $input )

        let amount = pms_stripe_convert_amount_to_cents( price )

        if( !( amount > 0 ) )
            return null

        let label = $input.closest( '.pms-subscription-plan' ).find( '.pms-subscription-plan-name' ).first().text().trim()

        return {
            amount        : amount,
            label         : label || pms.apple_pay.billing_label,
            interval_unit : interval_unit,
            interval_count: interval_count,
            start_date    : start_date,
            end_date      : end_date,
        }

    }

    /**
     * Returns the recurring billing for the whole checkout: the selected plan plus any selected Order Bumps
     *
     * - each recurring item renews as its own subscription, but Apple Pay accepts a single recurring line, so items that share a billing interval are shown as one combined amount
     * - when intervals differ, the primary plan is shown, or the first recurring bump when the primary does not renew; any recurring item is enough for Apple to issue a merchant token
     *
     */
    function pms_stripe_get_recurring_billing_for_checkout() {

        let items = []

        let primary = pms_stripe_get_recurring_billing_for_input( pms_stripe_get_selected_subscription() )

        if( primary !== null )
            items.push( primary )

        $( 'input[name="pms_order_bumps[]"]:checked' ).each( function() {

            let bump = pms_stripe_get_recurring_billing_for_input( $( this ) )

            if( bump !== null )
                items.push( bump )

        })

        if( items.length <= 1 )
            return items.length ? items[0] : null

        let same_interval = items.every( item => item.interval_unit == items[0].interval_unit && item.interval_count == items[0].interval_count )

        if( !same_interval )
            return items[0]

        let start_dates = items.map( item => item.start_date ).filter( date => date !== null )
        let end_dates   = items.map( item => item.end_date )

        return {
            amount        : items.reduce( ( total, item ) => total + item.amount, 0 ),
            label         : pms.apple_pay.billing_label,
            interval_unit : items[0].interval_unit,
            interval_count: items[0].interval_count,
            start_date    : start_dates.length ? new Date( Math.min( ...start_dates ) ) : null,
            // the combined line only ends when every item ends
            end_date      : end_dates.includes( null ) ? null : new Date( Math.max( ...end_dates ) ),
        }

    }

    function pms_stripe_get_recurring_billing_for_update_payment_method() {

        let $input = $( '#pms-stripe-connect-recurring-billing' )

        if( $input.length == 0 || !pms.apple_pay.management_url )
            return null

        // billing_amount is in the subscription's currency, not necessarily the one selected on the page
        let amount         = pms_stripe_convert_amount_to_cents( parseFloat( $input.data( 'amount' ) ), $input.data( 'currency' ) || pms.currency )
        let interval_unit  = $input.data( 'interval_unit' )
        let interval_count = Number( $input.data( 'interval_count' ) )

        if( !( amount > 0 ) || !Number.isInteger( interval_count ) || interval_count <= 0 || !apple_pay_interval_units.includes( interval_unit ) )
            return null

        // Saving a payment method charges nothing, so the recurring amount starts on the subscription's next payment date
        // A past-due subscription is retried right away, so Apple Pay's "Starting Now" is accurate for it
        let start_date = Number( $input.data( 'start_date' ) ) > 0 ? new Date( Number( $input.data( 'start_date' ) ) * 1000 ) : null

        if( start_date !== null && start_date <= new Date() )
            start_date = null

        let end_date           = null
        let remaining_payments = Number( $input.data( 'remaining_payments' ) )

        if( remaining_payments > 0 && start_date !== null )
            end_date = remaining_payments == 1 ? start_date : pms_stripe_add_interval( start_date, interval_unit, interval_count * ( remaining_payments - 1 ) )

        return {
            amount        : amount,
            label         : String( $input.data( 'label' ) || '' ).trim() || pms.apple_pay.billing_label,
            interval_unit : interval_unit,
            interval_count: interval_count,
            start_date    : start_date,
            end_date      : end_date,
        }

    }

    // Returns a new Date moved forward by count units, or null when the interval is invalid
    function pms_stripe_add_interval( date, unit, count ) {

        if( !Number.isInteger( count ) || count <= 0 )
            return null

        let result = new Date( date.getTime() )

        if( unit == 'day' )
            result.setDate( result.getDate() + count )
        else if( unit == 'week' )
            result.setDate( result.getDate() + count * 7 )
        else if( unit == 'month' )
            result.setMonth( result.getMonth() + count )
        else if( unit == 'year' )
            result.setFullYear( result.getFullYear() + count )
        else
            return null

        return result

    }

    // element.update() merges applePay shallowly, so the empty object clears a previously set recurringPaymentRequest
    function pms_stripe_get_apple_pay_options( recurring_billing ) {

        if( recurring_billing === null )
            return { applePay: {} }

        let interval_unit  = recurring_billing.interval_unit
        let interval_count = recurring_billing.interval_count

        // Stripe.js rejects 'week' as an interval unit (it accepts year, month, day, hour, minute), so weekly plans bill as 7-day intervals
        if( interval_unit == 'week' ){
            interval_unit  = 'day'
            interval_count = interval_count * 7
        }

        let regular_billing = {
            amount                       : recurring_billing.amount,
            label                        : recurring_billing.label,
            recurringPaymentIntervalUnit : interval_unit,
            recurringPaymentIntervalCount: interval_count,
        }

        if( recurring_billing.start_date )
            regular_billing.recurringPaymentStartDate = recurring_billing.start_date

        if( recurring_billing.end_date )
            regular_billing.recurringPaymentEndDate = recurring_billing.end_date

        return {
            applePay: {
                recurringPaymentRequest: {
                    paymentDescription: pms.apple_pay.payment_description,
                    managementURL     : pms.apple_pay.management_url,
                    regularBilling    : regular_billing,
                }
            }
        }

    }

    // Re-run mode selection when an addon signals a checkout state change (e.g. Order Bumps toggling line items)
    $(document).on( 'pms_recompute_payment_mode', function() {
        stripeConnectInit()
    })

    $(document).on('click', '.pms-subscription-plan-auto-renew input[name="pms_recurring"]', function ( event ) {

        if( $(this).prop('checked') ){
            elements.update( { setupFutureUsage: 'off_session' } );
        } else {
            elements.update( { setupFutureUsage: null } );
        }

        pms_stripe_refresh_apple_pay_options()

    })

    // Discounts and Order Bumps change the recurring amounts too; Order Bumps writes its per-bump discount data before triggering the summary recalc
    $(document).on( 'pms_tax_applied pms_tax_removed pms_tax_context_changed pms_discount_success pms_discount_error pms_order_summary_recalc', pms_stripe_refresh_apple_pay_options )

    function pms_stripe_refresh_apple_pay_options() {

        if( $payment_element && $payment_element != '' )
            $payment_element.update( pms_stripe_get_apple_pay_options( pms_stripe_get_recurring_billing_for_checkout() ) )

    }

    // This was added for Link support.
    // After the user email is entered, the payment element is destroyed and then recreated using the new parameters.
    $(document).on('change', '.pms-form .pms-user-email-field input[name="user_email"], .wppb-register-user input[name="email"], .pms-form .pms-billing-details input[name="pms_billing_email"]', function ( event ) {

        // Skip if already recreating
        if( recreating_payment_element ){
            return
        }

        if( $('input[type=hidden][name=pay_gate]').val() != 'stripe_connect' && $('input[type=radio][name=pay_gate]:checked').val() != 'stripe_connect' )
            return

        if( $('input[type=hidden][name=pay_gate]').is(':disabled') || $('input[type=radio][name=pay_gate]:checked').is(':disabled') )
            return

        let element = $(this)
        let email = $(this).val()

        // Clear any pending debounce timer
        if( email_change_debounce_timer ){
            clearTimeout( email_change_debounce_timer )
        }

        if( email.length > 0 ){
            
            // Disable the input to prevent multiple submissions
            element.attr('disabled', true)
            
            pms_stripe_show_spinner()

            email_change_debounce_timer = setTimeout( function(){
                
                recreating_payment_element = true

                // Only destroy if element exists
                if( $payment_element && $payment_element != '' ){
                    $payment_element.destroy()
                    $payment_element = ''
                }

                setTimeout( function(){
                    stripeConnectInit()
                    element.attr('disabled', false)
                    recreating_payment_element = false
                }, 300 )

            }, 250 )
        }

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

    $(document).on('pms_tax_removed', function ( event, data ) {

        // The Order Summary passes its untaxed total, which also covers Order Bumps
        let price = data && data.total > 0 ? data.total : pms_stripe_get_first_payment_amount( pms_stripe_get_selected_subscription() )

        if( price > 0 ){
            elements.update( { amount: pms_stripe_convert_amount_to_cents( price ) } );
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

    // WPPB Recaptcha
    $(document).on( 'wppb_invisible_recaptcha_success', stripeConnectPaymentGatewayHandler )

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

        if ( ! ( $( '.wppb-recaptcha .wppb-recaptcha-element', $(e.currentTarget) ).hasClass( 'wppb-invisible-recaptcha' ) ) ) {

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

            // let add-ons escalate setup_future_usage to off_session before the ConfirmationToken is built (e.g. a recurring Order Bump under a non-recurring primary), so the token matches the server-side PaymentIntent
            // - escalate-only: it never downgrades the value the primary plan already required, it only forces off_session when an add-on needs the payment method stored for future renewals
            if( $(document).triggerHandler( 'pms_filter_stripe_connect_setup_future_usage', [ null, current_button.closest( 'form' ) ] ) === 'off_session' )
                target_elements.update( { setupFutureUsage: 'off_session' } )

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
                }

                // Redirect to the URL if the response contains a redirect URL
                if ( typeof response.redirect_url != 'undefined' && response.redirect_url ){
                    window.location.replace( response.redirect_url )
                    return true
                }

                if( response.success == false && typeof response.type != 'undefined' ){

                    let intent 

                    if( $.pms_checkout_is_setup_intents() ){
                        var { error, setupIntent } = await stripe.handleNextAction({
                            clientSecret: response.client_secret
                        });

                        intent = setupIntent

                    } else {
                        var { error, paymentIntent } = await stripe.handleNextAction({
                            clientSecret: response.client_secret
                        });

                        intent = paymentIntent
                    }

                    if( error && error.payment_intent ){
                        intent = error.payment_intent
                    } else if ( error && error.setup_intent ){
                        intent = error.setup_intent
                    }

                    // Process the payment on the server
                    const server_response = await pms_stripe_process_payment( intent, response, current_button )

                    if ( typeof server_response.redirect_url != 'undefined' && server_response.redirect_url ){
                        window.location.replace( server_response.redirect_url )

                        return true
                    }

                    return false

                }

                pms_stripe_checkout_failed( current_button, response )

                return false

            }).catch( function( error ){

                pms_stripe_checkout_failed( current_button, error )

                return false

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

        // expand array values into separate appends so multi-value fields reach the wire as repeated entries
        // - FormData.append stringifies array values via comma-join, collapsing them into a single scalar
        var form_data = new FormData()

        for (var key in data) {
            if ( Array.isArray( data[key] ) ) {
                data[key].forEach( function ( value ) {
                    form_data.append( key, value )
                } )
            } else {
                form_data.append( key, data[key] )
            }
        }

        return fetch( pms.ajax_url, {
            method     : 'post',
            credentials: 'same-origin',   // Required for WordPress cookie authentication
            body       : form_data
        }).then(function (res) {
            return res.json()
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
            pms_nonce           : nonce,
            pms_resume_token    : user_data.resume_token
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

            $update_element = update_elements_setup_intent.create( "payment", pms_stripe_get_apple_pay_options( pms_stripe_get_recurring_billing_for_update_payment_method() ) )
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

        let selected_subscription = pms_stripe_get_selected_subscription()
        let apple_pay_options     = pms_stripe_get_apple_pay_options( pms_stripe_get_recurring_billing_for_checkout() )

        // Handle Setup Future Usage parameter
        let primary_setup_future_usage = pms_stripe_connect_setup_future_usage_for_input( selected_subscription )

        if( typeof primary_setup_future_usage !== 'undefined' )
            elements.update( { setupFutureUsage: primary_setup_future_usage } );

        if( target_elements_instance != false ){

            if( typeof selected_subscription != 'undefined' && target_elements_instance_slug == 'payment_intents' ){

                let price = pms_stripe_get_first_payment_amount( selected_subscription )

                if( price > 0 ){
                    target_elements_instance.update( { amount: pms_stripe_convert_amount_to_cents( price ) } );
                }
            }

            if( $payment_element == '' ){

                $payment_element = target_elements_instance.create( "payment", { ...pms_stripe_get_default_values(), ...apple_pay_options } )
                $payment_element.mount("#pms-stripe-payment-elements")

                // Show credit card form error messages to the user as they happpen
                $payment_element.addEventListener('change', creditCardErrorsHandler )

            } else {

                if( $elements_instance_slug != target_elements_instance_slug ){

                    $payment_element.destroy()

                    $payment_element = target_elements_instance.create( "payment", { ...pms_stripe_get_default_values(), ...apple_pay_options } )
                    $payment_element.mount("#pms-stripe-payment-elements")

                    // Show credit card form error messages to the user as they happpen
                    $payment_element.addEventListener('change', creditCardErrorsHandler )

                } else {
                    $payment_element.update( apple_pay_options )
                }

            }

            $elements_instance_slug = target_elements_instance_slug

            pms_stripe_hide_spinner()

            if( typeof paymentSidebarPosition == 'function' ){
                setTimeout( paymentSidebarPosition, 300 )
            }

        }

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

    function pms_stripe_checkout_failed( current_button, detail ){

        console.error( '[PMS Stripe] Checkout did not complete. Response:', detail )

        var $form = current_button.closest( 'form' )

        $.pms_add_general_error( pms.checkout_unexpected_error, $form )
        $.pms_form_scrollTo( $form, current_button )

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

    /**
     * Rounds half away from zero exactly like the server's PHP round(), so totals don't drift a cent on values such as 35.105 that floats store as 35.10499...
     *
     * - PHP 8.4+ rounds up when the value is at least the float nearest to the decimal half
     * - older PHP pre-rounds the scaled value to 15 significant digits first
     *
     */
    function pms_stripe_round( value, decimals ){

        let multiplier = Math.pow( 10, decimals )
        let magnitude  = Math.abs( value )

        if( pms.php_round_edge_check != 1 )
            return Math.sign( value ) * Math.round( Number( ( magnitude * multiplier ).toPrecision( 15 ) ) ) / multiplier

        let integral = Math.floor( magnitude * multiplier )

        if( integral / multiplier === magnitude )
            return value

        if( magnitude >= ( integral + 0.5 ) / multiplier )
            integral += 1

        return Math.sign( value ) * integral / multiplier

    }

    function pms_stripe_convert_amount_to_cents( amount, currency = pms.currency ){

        // List of zero-decimal currencies
        const zero_decimal_currencies = [
            'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 
            'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'
        ];

        // If currency is not in zero-decimal list, multiply by 100
        // Round to the currency's decimals first, like the server rounds the amount it charges, so unrounded totals don't lose a cent
        if ( !zero_decimal_currencies.includes( currency.toUpperCase() ) ) {
            amount = pms_stripe_round( amount, 2 ) * 100;
        }

        // Round to ensure we have a whole number
        return pms_stripe_round( amount, 0 );
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
                subscription_id     : subscriptionPlan.closest('form').find('input[name="pms_current_subscription"]').val(),
                pay_gate            : 'stripe_connect'
            },
            dataType: 'json',
        }).done( function ( response ) {

            if ( response && response.success && response.currency )
                elements.update( { currency: response.currency.toLowerCase() } )

            pms_stripe_hide_spinner()

            return;

        }).fail( function () {

            pms_stripe_hide_spinner()

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

    function pms_stripe_get_default_values(){

        if( jQuery('body').hasClass( 'logged-in' ) && pms.pms_customer_email ){
            var user_email = pms.pms_customer_email
        } else {
            var user_email = $( '.pms-form input[name="user_email"], .wppb-register-user input[name="email"]' ).val()
        }

        let name = ''

        if( jQuery('body').hasClass( 'logged-in' ) && pms.pms_customer_name ){
            name = pms.pms_customer_name
        } else {
            if( $( '.pms-billing-details input[name="pms_billing_first_name"]' ).length > 0 )
                name = name + $( '.pms-billing-details input[name="pms_billing_first_name"]' ).val() + ' '
            else if( $( '.pms-form input[name="first_name"], .wppb-user-forms input[name="first_name"]' ).length > 0 )
                name = name + $( '.pms-form input[name="first_name"], .wppb-user-forms input[name="first_name"]' ).val() + ' '
    
            if( $( '.pms-billing-details input[name="pms_billing_last_name"]' ).length > 0 )
                name = name + $( '.pms-billing-details input[name="pms_billing_last_name"]' ).val()
            else if( $( '.pms-form input[name="last_name"], .wppb-user-forms input[name="last_name"]' ).length > 0 )
                name = name + $( '.pms-form input[name="last_name"], .wppb-user-forms input[name="last_name"]' ).val()
        }

        if( typeof user_email != 'undefined' && user_email.length > 0 ){
            var default_values = {
                defaultValues: {
                    billingDetails: {
                        email: user_email,
                        name : name.length > 0 ? name : null
                    }
                }
            }
        } else {
            var default_values = {}
        }

        return default_values

    }

}

// Initialize Stripe Connect when document is ready
jQuery( function() {
    try {
        pms_stripe_maybe_load_gateway( jQuery );
    } catch ( err ) {
        console.error( '[PMS Stripe] Error during init:', err );
    }
});

// Maybe initialize Stripe when Elementor popup is shown
jQuery(document).on('elementor/popup/show', function () {
    if ( jQuery('.pms-form #pms-stripe-connect', jQuery('.elementor-popup-modal') ).length > 0 ) {
        pms_stripe_maybe_load_gateway( jQuery )

        // By default, the regular submit event of the form is not triggered when the button is clicked inside the popup.
        // We simulate a submit event to trigger the form submission on the click event.
        document.addEventListener('click', function (ev) {
            const btn = ev.target.closest('input[type="submit"], button[type="submit"]');
            if (!btn) return;
            const form = btn.form || btn.closest('form');
            if (!form) return;
            if (!form.classList.contains('pms-form')) return;
        
            ev.preventDefault();
            ev.stopImmediatePropagation();
        
            const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
            form.dispatchEvent(submitEvent);
          }, true);
    }
});

  
