jQuery(function ($) {

    var paypal_sdk_checkout = {
        "client-id"             : pms_paypal.paypal_client_id,
        "merchant-id"           : pms_paypal.paypal_merchant_id,
        "components"            : "buttons",
        "disable-funding"       : "card,credit,paylater,bancontact,blik,eps,giropay,ideal,mercadopago,mybank,p24,sepa,sofort,venmo",
        "currency"              : pms_paypal.paypal_currency,
        "intent"                : 'capture',
        "commit"                : true,
        dataPartnerAttributionId: pms_paypal.paypal_partner_attribution_id,
    }

    var paypal_sdk_setup_intents = {
        "client-id"             : pms_paypal.paypal_client_id,
        "merchant-id"           : pms_paypal.paypal_merchant_id,
        "components"            : "buttons",
        "disable-funding"       : "card,credit,paylater,bancontact,blik,eps,giropay,ideal,mercadopago,mybank,p24,sepa,sofort,venmo",
        dataPartnerAttributionId: pms_paypal.paypal_partner_attribution_id,
    }

    var subscription_plan_selector = 'input[name=subscription_plans]'
    var paygate_selector           = 'input.pms_pay_gate'
    var paypal_initialized_type    = false

    $(document).ready( async function () {

        // Don't initialize the SDK if the necessary data is missing
        if( typeof pms_paypal === 'undefined' || typeof window.paypalLoadScript === 'undefined' ){
            console.log( 'PPCP: Necessary data is missing.' );
            return
        }

        if ( pms_paypal.paypal_client_id == '' || pms_paypal.paypal_merchant_id == '' ){
            console.log( 'PPCP: Necessary data is missing.' );
            return 
        }

        // Move the PayPal Connect button placeholder just before the submit button of the form
        await pms_ppcp_move_paypal_connect_button()

        // Add Customer ID to the PayPal SDK configuration if user is logged in
        if( jQuery('body').hasClass( 'logged-in') ){

            let customer_id = await pms_ppcp_generate_client_token()

            if( customer_id ){
                paypal_sdk_checkout['dataUserIdToken'] = customer_id
            }

        }

        // Initialize the SDK with the correct configuration based on the current checkout
        if ( $.pms_checkout_is_setup_intents() || $( '#pms-paypal-update-payment-method-nonce' ).length > 0 )
            await pms_ppcp_initialize_sdk( paypal_sdk_setup_intents, 'setup_intents' )
        else 
            await pms_ppcp_initialize_sdk( paypal_sdk_checkout, 'checkout' )

        // Show PayPal Gateway extra fields on the update payment method form
        if ( $( 'input[name="pms_update_payment_method"]' ).length > 0 && $( '.pms-paygate-extra-fields-paypal_connect' ).length > 0 ){
            $( '.pms-paygate-extra-fields-paypal_connect' ).show()
            $( '#pms-update-payment-method-form input[type="submit"]:not([name="pms_redirect_back"]):not([id="pms-apply-discount"]), #pms-update-payment-method-form button[type="submit"], .wppb-edit-user input[name="pms_update_payment_method"]' ).last().hide()
        }

        // Show PayPal subscribe button on page load when PayPal is the selected payment gateway
        pms_ppcp_maybe_show_paypal_subscribe_button()

        // Switch the form submit button with the PayPal Connect subscribe button
        $(document).on('click', paygate_selector, function () {

            let form = $(this).closest( '.pms-form, .wppb-register-user' )

            if ( $(this).is(':checked') && $(this).val() == 'paypal_connect' ) 
                $('input[type="submit"]:not([name="pms_redirect_back"]):not([id="pms-apply-discount"]), button[type="submit"]', form).last().hide()
            else
                $('input[type="submit"], button[type="submit"]', form).show()

        })

        // Re-initialize the SDK when the subscription plan is changed
        $(document).on('click', subscription_plan_selector + '[type=radio], ' + subscription_plan_selector + '[type="hidden"]', async function () {

            pms_ppcp_maybe_show_paypal_subscribe_button()

            pms_ppcp_reinitialize_sdk()

        })

        $( document ).on( 'click', paygate_selector, async function() {

            if ( pms_paypal.pms_ppcp_mc_addon_active ){
                if( ($('input[type=hidden][name=pay_gate]').val() == 'paypal_connect' || $('input[type=radio][name=pay_gate]:checked').val() == 'paypal_connect') 
                    && !$('input[type=hidden][name=pay_gate]').is(':disabled') && !$('input[type=radio][name=pay_gate]:checked').is(':disabled') ) {
                    await pms_ppcp_validate_sdk_checkout_currency( $(this) );
                }
            }
    
        })

        // Compatibility with "Automatically renew subscription" option
        $(document).on('change', '.pms-subscription-plan-auto-renew input[name="pms_recurring"]', pms_ppcp_maybe_show_paypal_subscribe_button )

        // Compatibility with Multi-Step Forms
        $(document).on('wppb_msf_next_step', pms_ppcp_maybe_show_paypal_subscribe_button )
        $(document).on('wppb_msf_previous_step', pms_ppcp_maybe_show_paypal_subscribe_button )

        // Re-initialize the SDK when a discount is applied
        $(document).on( 'pms_discount_success', pms_ppcp_reinitialize_sdk )
        $(document).on( 'pms_discount_error', pms_ppcp_reinitialize_sdk )

    })

    /**
     * Initialize the PayPal SDK with the given configuration. If the SDK is already loaded, it will be re-rendered with the new configuration.
     * 
     * @param   {Object} data  PayPal SDK configuration
     * @param   {String} type  The type of PayPal SDK to initialize
     * @returns {Promise}      Resolves with PayPal object when initialization is complete
     */
    async function pms_ppcp_initialize_sdk( data, type ) {

        pms_ppcp_show_spinner()

        // Return if already initialized and the currency was not changed for paypal_sdk_checkout
        if( type === paypal_initialized_type && ( type !== 'checkout' || data.currency === pms_paypal.paypal_currency ) ){
            pms_ppcp_hide_spinner()
            return
        }

        // If the currency changes remove existing button
        if( data.currency !== pms_paypal.paypal_currency ){
            $( '#pms-paygate-extra-fields-paypal_connect__placeholder' ).html('')
            pms_paypal.paypal_currency = data.currency
        }

        // Load SDK with new arguments
        const paypal = await window.paypalLoadScript( data )

        // Re-render buttons if placeholder exists
        if ( $( '#pms-paygate-extra-fields-paypal_connect__placeholder' ).length > 0 ) {

            if ( $.pms_checkout_is_setup_intents() ) {

                paypal_initialized_type = 'setup_intents'

                paypal.Buttons({
                    style                : pms_paypal.paypal_button_styles,
                    createVaultSetupToken: pms_ppcp_create_vault_setup_token,
                    onApprove            : pms_ppcp_process_checkout,
                    onCancel             : function (data, actions) {
                        
                        $.pms_form_reset_submit_button( $( '#pms-paygate-extra-fields-paypal_connect__placeholder' ).closest( '.pms-form, .wppb-register-user' ).find( 'input[type="submit"], button[type="submit"]' ).last() )

                    },
                    onError: function () {
                        
                        $.pms_form_reset_submit_button( $( '#pms-paygate-extra-fields-paypal_connect__placeholder' ).closest( '.pms-form, .wppb-register-user' ).find( 'input[type="submit"], button[type="submit"]' ).last() )

                    }
                }).render('#pms-paygate-extra-fields-paypal_connect__placeholder')

            } else if ( $( '#pms-paypal-update-payment-method-nonce' ).length > 0 ) {

                paypal_initialized_type = 'update_payment_method'

                // On the Update payment method form, leave the label of the field as just "PayPal"
                if ( $( '#pms-update-payment-method-form #pms-paypal-connect' ).length > 0 ) {
                    pms_paypal.paypal_button_styles.label = 'paypal'
                }

                paypal.Buttons({
                    style                : pms_paypal.paypal_button_styles,
                    createVaultSetupToken: pms_ppcp_update_payment_method_create_vault_setup_token,
                    onApprove            : pms_ppcp_process_checkout,
                    onCancel             : function (data, actions) {
                        
                        $.pms_form_reset_submit_button( $( '#pms-paygate-extra-fields-paypal_connect__placeholder' ).closest( '.pms-form, .wppb-register-user' ).find( 'input[type="submit"], button[type="submit"]' ).last() )

                    },
                    onError: function () {
                        
                        $.pms_form_reset_submit_button( $( '#pms-paygate-extra-fields-paypal_connect__placeholder' ).closest( '.pms-form, .wppb-register-user' ).find( 'input[type="submit"], button[type="submit"]' ).last() )

                    }
                }).render('#pms-paygate-extra-fields-paypal_connect__placeholder')

            } else {

                paypal_initialized_type = 'checkout'

                paypal.Buttons({
                    style      : pms_paypal.paypal_button_styles,
                    createOrder: pms_ppcp_create_order,
                    onApprove  : pms_ppcp_process_checkout,
                    onCancel   : function (data, actions) {
                        
                        $.pms_form_reset_submit_button( $( '#pms-paygate-extra-fields-paypal_connect__placeholder' ).closest( '.pms-form, .wppb-register-user' ).find( 'input[type="submit"], button[type="submit"]' ).last() )

                    },
                    onError: function () {
                        
                        $.pms_form_reset_submit_button( $( '#pms-paygate-extra-fields-paypal_connect__placeholder' ).closest( '.pms-form, .wppb-register-user' ).find( 'input[type="submit"], button[type="submit"]' ).last() )

                    }
                }).render('#pms-paygate-extra-fields-paypal_connect__placeholder')

            }

        }

        pms_ppcp_hide_spinner()

        return paypal

    }

    async function pms_ppcp_reinitialize_sdk(){

        if( !$.pms_checkout_is_setup_intents() ) {

            if ( pms_paypal.pms_ppcp_mc_addon_active )
                await pms_ppcp_validate_sdk_checkout_currency( $pms_checked_subscription );

            pms_ppcp_initialize_sdk( paypal_sdk_checkout, 'checkout' )
        }
        else
            pms_ppcp_initialize_sdk( paypal_sdk_setup_intents, 'setup_intents' )

    }

    /**
     * Create an order
     * 
     * @param   {Object} data   Data object
     * @param   {Object} actions PayPal actions object
     */
    async function pms_ppcp_create_order( data, actions ) {

        if ( $('input[type=hidden][name=pay_gate]').val() != 'paypal_connect' && $('input[type=radio][name=pay_gate]:checked').val() != 'paypal_connect' )
            return false

        if ( $('input[type=hidden][name=pay_gate]').is(':disabled') || $('input[type=radio][name=pay_gate]:checked').is(':disabled') )
            return false

        $.pms_form_remove_errors()

        // Find the form that contains the active PayPal Connect payment gateway
        let paygate_input  = $('input[type=hidden][name=pay_gate][value="paypal_connect"]').length > 0 ? $('input[type=hidden][name=pay_gate][value="paypal_connect"]') : $('input[type=radio][name=pay_gate][value="paypal_connect"]:checked')
        let target_form    = paygate_input.closest('.pms-form, .wppb-register-user')
        let current_button = target_form.find('.pms-form-submit, input[type="submit"], button[type="submit"]').not('#pms-apply-discount').not('input[name="pms_redirect_back"]')

        if ( !( current_button.length > 0 ) )
            return false

        // Disable the button
        current_button.attr( 'disabled', true )

        let pms_checked_subscription = jQuery(subscription_plan_selector + '[type=radio]').length > 0 ? jQuery(subscription_plan_selector + '[type=radio]:checked') : jQuery(subscription_plan_selector + '[type=hidden]')

        let extra_data = {
            'pay_gate'           : $('input[type=hidden][name=pay_gate]').length > 0 ? $('input[type=hidden][name=pay_gate]').val(): $('input[type=radio][name=pay_gate]:checked').val(),
            'subscription_plans' : pms_checked_subscription.val(),
            'paypal_action_nonce': pms_paypal.pms_ppcp_create_order_nonce
        }

        return pms_ppcp_maybe_validate_recaptcha( current_button ).then( function( recaptcha_response ){

            return pms_ppcp_validate_checkout( current_button, extra_data ).then( function (response) {

                if( response ) {
                    if( response.success == true && response.order_id ) {
    
                        return response.order_id
    
                    }
                }
    
                return false
    
            })

        })

    }   

    /**
     * Create a vault setup token
     * 
     * @param   {Object} data   Data object
     * @param   {Object} actions PayPal actions object
     */
    function pms_ppcp_create_vault_setup_token( data, actions ) {

        if ( $('input[type=hidden][name=pay_gate]').val() != 'paypal_connect' && $('input[type=radio][name=pay_gate]:checked').val() != 'paypal_connect' )
            return false

        if ( $('input[type=hidden][name=pay_gate]').is(':disabled') || $('input[type=radio][name=pay_gate]:checked').is(':disabled') )
            return false

        $.pms_form_remove_errors()

        // Find the form that contains the active PayPal Connect payment gateway
        let paygate_input  = $('input[type=hidden][name=pay_gate][value="paypal_connect"]').length > 0 ? $('input[type=hidden][name=pay_gate][value="paypal_connect"]') : $('input[type=radio][name=pay_gate][value="paypal_connect"]:checked')
        let target_form    = paygate_input.closest('.pms-form, .wppb-register-user')
        let current_button = target_form.find('.pms-form-submit, input[type="submit"], button[type="submit"]').not('#pms-apply-discount').not('input[name="pms_redirect_back"]')

        if ( !( current_button.length > 0 ) )
            return false

        // Disable the button
        current_button.attr('disabled', true)

        let pms_checked_subscription = jQuery(subscription_plan_selector + '[type=radio]').length > 0 ? jQuery(subscription_plan_selector + '[type=radio]:checked') : jQuery(subscription_plan_selector + '[type=hidden]')

        let extra_data = {
            'pay_gate'           : $('input[type=hidden][name=pay_gate]').length > 0 ? $('input[type=hidden][name=pay_gate]').val(): $('input[type=radio][name=pay_gate]:checked').val(),
            'subscription_plans' : pms_checked_subscription.val(),
            'paypal_action_nonce': pms_paypal.pms_ppcp_create_setup_token_nonce
        }

        return pms_ppcp_validate_checkout( current_button, extra_data ).then(function (response) {

            if( response ) {
                if( response.success == true && response.setup_token_id ) {

                    return response.setup_token_id

                }
            }

            return false

        })

    }

    /**
     * Create a vault setup token
     * 
     * @param   {Object} data   Data object
     * @param   {Object} actions PayPal actions object
     * 
     * @returns {Promise} The vault setup token
     */
    function pms_ppcp_update_payment_method_create_vault_setup_token( data, actions ) {

        // prepare data
        var form_data = new FormData()

        form_data.append( 'action', 'pms_ppcp_create_setup_token' )
        form_data.append( 'nonce', pms_paypal.pms_ppcp_create_setup_token_nonce )

        return fetch( pms_paypal.ajax_url, {
            method     : 'post',
            credentials: 'same-origin',
            body       : form_data
        }).then(function (res) {
            return res.json()
        }).then(function (response) {

            if ( response && response.success && response.setup_token_id ) {
                return response.setup_token_id
            }
        
            return false

        }).catch(error => {
            console.error('Something went wrong:', error)
            throw error
        })

    }

    /**
     * Process the checkout
     * 
     * @param   {Object} response Response from PayPal
     * @param   {Object} actions  PayPal actions object
     */
    async function pms_ppcp_process_checkout( response, actions ) {

        if ( !response )
            return

        // Find the form that contains the active PayPal Connect payment gateway
        let paygate_input = $('input[type=hidden][name=pay_gate][value="paypal_connect"]').length > 0 ? $('input[type=hidden][name=pay_gate][value="paypal_connect"]') : $('input[type=radio][name=pay_gate][value="paypal_connect"]:checked')
        let target_form = paygate_input.closest('.pms-form, .wppb-register-user')
        let current_button = target_form.find('.pms-form-submit, input[type="submit"], button[type="submit"]').not('#pms-apply-discount').not('input[name="pms_redirect_back"]')

        if ( !( current_button.length > 0 ) )
            return;

        // grab all data from the form
        var data = await $.pms_form_get_data( current_button, false )

        if ( data == false )
            return

        if( response.vaultSetupToken ){
            data.paypal_vault_setup_token = response.vaultSetupToken
        } else {
            data.paypal_order_id = response.orderID
        }

        // prepare data
        var form_data = new FormData();

        for (var key in data) {
            form_data.append(key, data[key])
        }

        return fetch( pms_paypal.ajax_url, {
            method: 'post',
            credentials: 'same-origin',   // Required for WordPress cookie authentication
            body: form_data
        }).then(function (res) {
            return res.json()
        }).then(function (response) {

            if (typeof response.redirect_url != 'undefined' && response.redirect_url)
                window.location.replace(response.redirect_url)

        }).catch(error => {
            console.error('Something went wrong:', error);
            throw error;
        });

    }

    /**
     * Validate custom currency
     *
     * @param subscriptionPlan
     * @returns {Promise<*>}
     */
    async function pms_ppcp_validate_sdk_checkout_currency( subscriptionPlan ) {

        pms_ppcp_show_spinner()

        return $.ajax({
            url: pms_paypal.ajax_url,
            type: 'POST',
            data: {
                action              : 'pms_validate_sdk_currency',
                pms_nonce           : pms_paypal.pms_validate_currency_nonce,
                subscription_plan_id: subscriptionPlan.val(),
                pms_mc_currency     : subscriptionPlan.data('mc_currency'),
                pay_gate            : 'paypal_connect'
            },
            dataType: 'json',
        }).done( function ( response ) {

            pms_ppcp_hide_spinner()

            if ( response && response.success ) {
                paypal_sdk_checkout.currency = response.currency;
            }
        });
    }

    /**
     * Validate the checkout
     * 
     * @param   {Object} current_button The submit button element
     * @param   {Object} extra_data     Additional data to send with the validation request
     * @returns {Boolean} True if the checkout is valid, false otherwise
     */
    async function pms_ppcp_validate_checkout( current_button, extra_data = {} ) {

        if (current_button.length == 0)
            return false

        var data = await $.pms_form_get_data( current_button )

        // Same data as a Process Checkout request, we only switch the action
        data.action = 'pms_validate_checkout'

        // Merge extra data with the data object
        if ( extra_data ) {
            for ( var key in extra_data ) {
                data[key] = extra_data[key]
            }
        }

        // prepare data
        var form_data = new FormData()

        for (var key in data) {
            form_data.append(key, data[key])
        }

        return fetch( pms_paypal.ajax_url, {
            method: 'post',
            credentials: 'same-origin',   // Required for WordPress cookie authentication
            body: form_data
        }).then(function (res) {
            return res.json()
        }).then(function (response) {

            if ( response ) {

                if ( response.success == false ) {

                    pms_ppcp_handle_validation_errors( response, current_button )

                    return false

                } else if( response.success == true ){

                    return response

                }

            }
        
            return false

        }).catch(error => {
            console.error('Something went wrong:', error)
            throw error
        })

    }

    async function pms_ppcp_maybe_validate_recaptcha( current_button ){

        if( typeof pms_initialize_recaptcha_v3 == 'function' ){

            let form = current_button.closest('form')

            var recaptcha_field = jQuery('.pms-recaptcha', form )
    
            if( recaptcha_field.length > 0 ){
    
                return await pms_initialize_recaptcha_v3( null, form )
    
            }

        }
        
        let wppb_form = current_button.closest('.wppb-register-user')

        if( wppb_form.length > 0 && wppb_form[0].length > 0 && typeof wppbInitializeRecaptchaV3 == 'function' ){

            let wppb_recaptcha_field = jQuery('.wppb-recaptcha .wppb-recaptcha-element', wppb_form )

            if( wppb_recaptcha_field.length > 0 ){
                return await wppbInitializeRecaptchaV3( null, wppb_form )
            }
            
        }

        return true

    }

    /**
     * Handle validation errors
     * 
     * @param   {Object} response      Validation response
     * @param   {Object} current_button Current button
     */
    function pms_ppcp_handle_validation_errors(response, current_button) {

        var form_type = $('.wppb-register-user .wppb-subscription-plans').length > 0 ? 'wppb' : $('.pms-ec-register-form').length > 0 ? 'pms_email_confirmation' : 'pms'

        // Paid Member Subscription forms
        if (response.data && (form_type == 'pms' || form_type == 'pms_email_confirmation')) {
            $.pms_form_add_validation_errors(response.data, current_button)
            // Profile Builder form
        } else {

            // Add PMS related errors (Billing Fields)
            // These are added first because the form will scroll to the error and these
            // are always placed at the end of the WPPB form
            if (response.pms_errors && response.pms_errors.length > 0)
                $.pms_form_add_validation_errors(response.pms_errors, current_button)

            // Add WPPB related errors
            if (typeof response.wppb_errors == 'object')
                $.pms_form_add_wppb_validation_errors(response.wppb_errors, current_button)

            jQuery(document).trigger('pms_checkout_validation_error', response, current_button)

        }

    }

    /**
     * Move the PayPal Connect button to the correct position
     */
    async function pms_ppcp_move_paypal_connect_button() {

        if( $( '#pms-paypal-connect' ).length == 0 )
            return

        // Logged out PMS register form
        if ( $( '.pms-form .pms-form-submit' ).length > 0 ) {

            $( '.pms-form #pms-paypal-connect' ).insertBefore( '.pms-form .pms-form-submit' )

        // Logged in PMS payment form
        } else if( $( '.pms-form' ).length > 0 ){

            // Some themes switch the submit button with a button element
            if( $( '.pms-form button[type="submit"]' ).length > 0 ){
                $( '.pms-form #pms-paypal-connect' ).insertBefore( '.pms-form button[type="submit"]' )
            } else {
                $( '.pms-form #pms-paypal-connect' ).insertBefore(
                    $( '.pms-form input[type="submit"]:not([name="pms_redirect_back"]):not([id="pms-apply-discount"])' )
                )
            }

        // Profile Builder register form
        } else if ( $( '.wppb-register-user' ).length > 0 ) {

            $( '.wppb-register-user #pms-paypal-connect' ).insertBefore( '.wppb-register-user .form-submit' )

        }

    }

    /**
     * Generate a client token
     * 
     * @returns {Promise} The client token
     */
    async function pms_ppcp_generate_client_token(){

        // prepare data
        var form_data = new FormData()

        form_data.append( 'action', 'pms_ppcp_generate_client_token' )
        form_data.append( 'nonce', pms_paypal.pms_ppcp_generate_client_token_nonce )

        return fetch( pms_paypal.ajax_url, {
            method     : 'post',
            credentials: 'same-origin',
            body       : form_data
        }).then(function (res) {
            return res.json()
        }).then(function (response) {

            if ( response && response.success && response.client_token ) {
                return response.client_token
            }
        
            return false

        }).catch(error => {
            console.error('Something went wrong:', error)
            throw error
        })

    }

    function pms_ppcp_show_spinner(){
        jQuery( '#pms-paypal-connect .pms-spinner__holder' ).show()
        jQuery( '#pms-paypal-connect #pms-paygate-extra-fields-paypal_connect__placeholder' ).hide()
    }

    function pms_ppcp_hide_spinner(){

        setTimeout( function(){

            jQuery( '#pms-paypal-connect .pms-spinner__holder' ).hide()
            jQuery( '#pms-paypal-connect #pms-paygate-extra-fields-paypal_connect__placeholder' ).show()

        }, 100 )

    }

    function pms_ppcp_maybe_show_paypal_subscribe_button(){

        if ( ( $( 'input[type=radio][name=pay_gate]:checked' ).val() == 'paypal_connect' || $('input[type=hidden][name=pay_gate]').val() == 'paypal_connect' ) && 
            ( !$( 'input[type=radio][name=pay_gate]:checked' ).is(':disabled') || !$('input[type=hidden][name=pay_gate]').is(':disabled') ) 
        ){

            // Also verify that the selected plan is not free
            if( $pms_checked_subscription.data('price') > 0 ){

                let show_button = true

                if( jQuery('.wppb-register-user .form-submit input[type="submit"], .wppb-register-user.form-submit button[type="submit"]').length > 0 ){
                    
                    let wppb_form = jQuery('.wppb-register-user .form-submit input[type="submit"], .wppb-register-user.form-submit button[type="submit"]').closest('.wppb-register-user')

                    // If Multi-Step Forms is enabled, we only need to show the button on the last step
                    if( jQuery( '.wppb-msf-step', wppb_form ).length > 0 ){

                        let step_count = jQuery( '.wppb-msf-step', wppb_form ).length
                            step_count = step_count - 1
                        
                        if( !jQuery( '#wppb-msf-step-' + step_count ).is(':visible') ){
                            show_button = false

                            $( '.pms-paygate-extra-fields-paypal_connect' ).hide()
                        }

                    }

                } 
                
                if( show_button ) {
                    $( '.pms-paygate-extra-fields-paypal_connect' ).show()
                    $( '.pms-form input[type="submit"]:not([name="pms_redirect_back"]):not([id="pms-apply-discount"]), .pms-form button[type="submit"], .wppb-register-user .form-submit input[type="submit"], .wppb-register-user.form-submit button[type="submit"]' ).last().hide()
                }
                
            } else {

                $( '.pms-paygate-extra-fields-paypal_connect' ).hide()
                $( '.pms-form input[type="submit"]:not([name="pms_redirect_back"]):not([id="pms-apply-discount"]), .pms-form button[type="submit"], .wppb-register-user .form-submit input[type="submit"], .wppb-register-user.form-submit button[type="submit"]' ).last().show()

            }
        }
        
    }

})
