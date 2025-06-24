/**
 * Define global variables so that the core plugin and 3rd party plugins can use them
 *
 */

// Paid Member Subscription submit buttons
var pms_payment_buttons

// Field wrappers
var $pms_auto_renew_field

// Checked Subscription
var $pms_checked_subscription
var $pms_checked_paygate

// Unavailable gateways message
var $pms_gateways_not_available

// Text placeholder for the payment buttons while processing
var pms_payment_button_loading_placeholder_text

// Form object
var $pms_form

// WPPB Email Confirmation
var is_pb_email_confirmation_on

// Billing Fields
var $pms_section_billing_details

/**
 * Core plugin
 *
 */
jQuery( function($) {

    if( window.history.replaceState ) {

        currentURL = window.location.href;

        currentURL = pms_remove_query_arg( 'pmsscscd', currentURL );
        currentURL = pms_remove_query_arg( 'pmsscsmsg', currentURL );
        currentURL = pms_remove_query_arg( 'pms_gateway_payment_action', currentURL );
        currentURL = pms_remove_query_arg( 'pms_gateway_payment_id', currentURL );
        currentURL = pms_remove_query_arg( 'pms_wppb_custom_success_message', currentURL );
        currentURL = pms_remove_query_arg( 'redirect_to', currentURL );

        if ( currentURL != window.location.href )
            window.history.replaceState( null, null, currentURL );
    }


    /*
     * Strips one query argument from a given URL string
     *
     */
    function pms_remove_query_arg( key, sourceURL ) {

        var rtn = sourceURL.split("?")[0],
            param,
            params_arr = [],
            queryString = (sourceURL.indexOf("?") !== -1) ? sourceURL.split("?")[1] : "";

        if (queryString !== "") {
            params_arr = queryString.split("&");
            for (var i = params_arr.length - 1; i >= 0; i -= 1) {
                param = params_arr[i].split("=")[0];
                if (param === key) {
                    params_arr.splice(i, 1);
                }
            }

            rtn = rtn + "?" + params_arr.join("&");

        }

        if(rtn.split("?")[1] == "") {
            rtn = rtn.split("?")[0];
        }

        return rtn;
    }

    // Paid Member Subscriptions submit buttons
    pms_payment_buttons  = 'input[name=pms_register], '
    pms_payment_buttons += 'input[name=pms_new_subscription], '
    pms_payment_buttons += 'input[name=pms_change_subscription], '
    pms_payment_buttons += 'input[name=pms_upgrade_subscription], '
    pms_payment_buttons += 'input[name=pms_renew_subscription], '
    pms_payment_buttons += 'input[name=pms_confirm_retry_payment_subscription], '
    pms_payment_buttons += 'input[name=pms_update_payment_method], '
    pms_payment_buttons += '#pms-paypal-express-confirmation-form input[type="submit"], '

    // Profile Builder submit buttons
    pms_payment_buttons += '.wppb-register-user input[name=register]'

    // Subscription plans and payment gateway selectors
    var subscription_plan_selector = 'input[name=subscription_plans]'
    var paygate_selector           = 'input.pms_pay_gate'

    var settings_recurring = $('input[name="pms_default_recurring"]').val()

    $pms_section_billing_details = $('.pms-section-billing-details')
    is_pb_email_confirmation_on  = $pms_section_billing_details.siblings('.pms-email-confirmation-payment-message').length > 0 ? true : false

    // Field wrappers
    $pms_auto_renew_field = jQuery( '.pms-subscription-plan-auto-renew' )

    // Checked Subscription
    $pms_checked_subscription = jQuery( subscription_plan_selector + '[type=radio]' ).length > 0 ? jQuery( subscription_plan_selector + '[type=radio]:checked' ) : jQuery( subscription_plan_selector + '[type=hidden]' )
    $pms_checked_paygate      = jQuery( paygate_selector + '[type=radio]' ).length > 0 ? jQuery( paygate_selector + '[type=radio]:checked' ) : jQuery( paygate_selector + '[type=hidden]' )

    // Unavailable gateways message
    $pms_gateways_not_available = jQuery( '#pms-gateways-not-available' )

    pms_payment_button_loading_placeholder_text = $('#pms-submit-button-loading-placeholder-text').text()

    /*
     * Hide "automatically renew subscription" checkbox for manual payment gateway
     *
     */
    jQuery(document).ready( function() {

        /**
         * Set checked payment gateway when clicking on a payment gateway radio
         *
         */
        $( document ).on( 'click', paygate_selector, function() {

            if( $(this).is(':checked') )
                $pms_checked_paygate = $(this)

            // Show / hide extra payment gateway fields
            if( $pms_checked_paygate.data('type') == 'extra_fields' ){
                $('.pms-paygate-extra-fields').hide()
                $('.pms-paygate-extra-fields-' + $pms_checked_paygate.val() ).show()
            } else
                $('.pms-paygate-extra-fields' ).hide()

            // Show billing fields
            handle_billing_fields_display()

            // Show/hide billing cycles
            handle_billing_cycles_display( $pms_checked_paygate.val() )

        })


        /**
         * Handle auto-renew checkbox and payment gateways display when clicking on a subscription plan
         *
         */
        $( document ).on( 'click', subscription_plan_selector + '[type=radio], ' + subscription_plan_selector + '[type="hidden"]', function() {

            if( $(this).is(':checked') )
                $pms_checked_subscription = $(this)

            if( typeof $pms_form == 'undefined' )
                $pms_form = $(this).closest('form')

            handle_auto_renew_field_display()
            handle_payment_gateways_display()

            // Show billing fields
            handle_billing_fields_display()

        })

        /** Billing fields PWYW compatibility */
        $(document).on('change', '.pms_pwyw_pricing', handle_billing_fields_display )
        $(document).on('keyup', '.pms_pwyw_pricing', handle_billing_fields_display )

        /**
         * Handle the auto renew checkbox field display in the page
         *
         */
        function handle_auto_renew_field_display() {

            if ( $pms_checked_subscription.data('recurring') == 1 && $pms_checked_paygate.data('recurring') != 'undefined' )
                $pms_auto_renew_field.show()
            else
                $pms_auto_renew_field.hide()


            if ($pms_checked_subscription.data('recurring') == 0) {

                if (settings_recurring == 1)
                    $pms_auto_renew_field.show()

            }

            if ( ( $pms_checked_subscription.data('fixed_membership') == 'on' && $pms_checked_subscription.data('allow_renew') != 'on' ) || $pms_checked_subscription.data('recurring') == 2 || $pms_checked_subscription.data('recurring') == 3 ) {
                $pms_auto_renew_field.hide()
            }

            if ( ( $pms_checked_subscription.data('fixed_membership') != 'on' && $pms_checked_subscription.data('duration') == 0 ) || ( $pms_checked_subscription.data('price') == 0 && !( $pms_checked_subscription.data('sign_up_fee') > 0 ) ) ) {

                if ( typeof $pms_checked_subscription.data('discountedPrice') == 'undefined' )
                    $pms_auto_renew_field.hide()
                else if ( typeof $pms_checked_subscription.data('isFullDiscount') != 'undefined' && $pms_checked_subscription.data('isFullDiscount') == true && $pms_checked_subscription.data('discountRecurringPayments') == 1 )
                    $pms_auto_renew_field.hide()

            }

            // show auto-renew checkbox for pro-rated plans that recur
            if ( $pms_checked_subscription.data('recurring') != 'undefined' && $pms_checked_subscription.data('recurring') != 3 && $pms_checked_subscription.data('recurring') != 2 ) {

                if ( $pms_checked_subscription.data('fixed_membership') != 'on' || ( $pms_checked_subscription.data('fixed_membership') == 'on' && $pms_checked_subscription.data('allow_renew') == 'on' ) ){

                    if ( typeof $pms_checked_subscription.data('prorated_discount') != 'undefined' && $pms_checked_subscription.data('prorated_discount') > 0 )
                        $pms_auto_renew_field.show()

                }

            }

        }


        /**
         * Handle the payment gateways radio buttons field display in the page
         *
         */
        function handle_payment_gateways_display() {

            // Before anything we display all gateways
            $('#pms-paygates-wrapper').show()
            $(paygate_selector).removeAttr('disabled')
            $(paygate_selector).closest('label').show()


            // Support for "trial"
            if ( $.pms_plan_has_trial() ) {
                $(paygate_selector + ':not([data-trial])').attr('disabled', true);
                $(paygate_selector + ':not([data-trial])').closest('label').hide();

            }


            // Support for "sign_up_fee"
            if ( $.pms_plan_has_signup_fee() ) {

                $(paygate_selector + ':not([data-sign_up_fee])').attr('disabled', true);
                $(paygate_selector + ':not([data-sign_up_fee])').closest('label').hide();

            }


            // Support for "recurring"
            if ($pms_checked_subscription.data('recurring') == 2) {

                $(paygate_selector + ':not([data-recurring])').attr('disabled', true);
                $(paygate_selector + ':not([data-recurring])').closest('label').hide();


            } else if ($pms_checked_subscription.data('recurring') == 1) {

                if ($pms_auto_renew_field.find('input[type=checkbox]').is(':checked')) {
                    $(paygate_selector + ':not([data-recurring])').attr('disabled', true);
                    $(paygate_selector + ':not([data-recurring])').closest('label').hide();
                }

            } else if (!$pms_checked_subscription.data('recurring')) {

                if (settings_recurring == 1) {
                    if ($pms_auto_renew_field.find('input[type=checkbox]').is(':checked')) {
                        $(paygate_selector + ':not([data-recurring])').attr('disabled', true);
                        $(paygate_selector + ':not([data-recurring])').closest('label').hide();
                    }
                } else if (settings_recurring == 2) {

                    $(paygate_selector + ':not([data-recurring])').attr('disabled', true);
                    $(paygate_selector + ':not([data-recurring])').closest('label').hide();

                }

            }


            // Select the first first available payment gateway by default after hiding the gateways
            if ($(paygate_selector + ':not([disabled]):checked').length == 0)
                $(paygate_selector + ':not([disabled])').first().trigger('click');



            if ($(paygate_selector).length > 0) {

                /**
                 * Handle case where no payment gateways are available
                 *
                 */
                if ($(paygate_selector + ':not([disabled])').length == 0) {

                    // Display the "no payment gateways are available" message
                    $pms_gateways_not_available.show();

                    // Hide credit card fields
                    $('.pms-paygate-extra-fields' ).hide()

                    // Disable submit button
                    if ($pms_checked_subscription.data('price') != 0) {

                        if ($pms_checked_subscription.length != 0)
                            $(pms_payment_buttons).attr('disabled', true).addClass('pms-submit-disabled');

                    }

                    /**
                     * Handle case where payment gateways are available for selection
                     *
                     */
                } else {

                    // Hide the "no payment gateways are available" message
                    $pms_gateways_not_available.hide();

                    // Show credit card fields if the selected payment gateway supports credit cards
                    if ( $(paygate_selector + ':not([disabled]):checked[data-type="extra_fields"]').length > 0 ) {
                        $('.pms-paygate-extra-fields').hide()
                        $('.pms-paygate-extra-fields-' + $(paygate_selector + ':not([disabled]):checked[data-type="extra_fields"]').val() ).show()
                    }

                    // Enable submit button
                    if ($pms_checked_subscription.length != 0)
                        $(pms_payment_buttons).attr('disabled', false).removeClass('pms-submit-disabled');

                }

            }


            // Hide credit card fields if it's a free plan
            if ( $pms_checked_subscription.data('price') == 0 && !$.pms_plan_has_signup_fee() ) {

                if ( $.pms_plan_is_prorated() ){

                    if ( $.pms_checkout_is_recurring() ){

                        if( typeof $pms_form != 'undefined' )
                            $.pms_show_payment_fields( $pms_form )

                        return
                    }

                }

                $('#pms-paygates-wrapper').hide()
                $(paygate_selector).attr('disabled', true)
                $(paygate_selector).closest('label').hide()

                $('.pms-paygate-extra-fields').hide()
                $('.pms-billing-details').hide()

            }

        }

        /**
         * Handle the display of recurring period information for subscription plans
         * e.g. pro-rate scenario with free time for a subscription that needs to recur
         *
         */
        function handle_plan_recurring_duration_display() {

            if ( !( $( '#pms-change-subscription-form' ).length > 0 ) )
                return

            $( 'input[name="subscription_plans"]' ).each( function( index, plan ){

                // don't do anything for plans that do not recur or if they don't have a prorated discount
                if ( $(plan).data('recurring') == 3 || ( typeof $(plan).data('prorated_discount') == 'undefined' || $(plan).data('prorated_discount') == 0 ) )
                    return

                // show recurring data for plans that always recur
                if ( ( $(plan).data('recurring') == 2 || settings_recurring == 2 || $('input[name="pms_recurring"]', $pms_auto_renew_field).prop('checked') ) && $( '.pms-subscription-plan-price__recurring', $(plan).parent() ) )
                    $( '.pms-subscription-plan-price__recurring', $(plan).parent() ).show()
                else
                    $( '.pms-subscription-plan-price__recurring', $(plan).parent() ).hide()

            })

        }

        /**
         * Show billing fields if necessary
         */
        function handle_billing_fields_display(){

            if( !( $pms_section_billing_details.length > 0 ) )
                return

            if ( $pms_checked_subscription.length > 0 && !is_pb_email_confirmation_on && ( $pms_checked_subscription.data('price') != 0 || $.pms_plan_has_signup_fee( $pms_checked_subscription ) ) )
                $('.pms-billing-details').attr('style', 'display: flex;');

        }

        /**
         * Show/Hide Cycles information in Subscription Plan price section on forms
         */
        function handle_billing_cycles_display( selected_paygate ) {
            let cyclesText = jQuery('.pms-subscription-plan-billing-cycles');

            let gateways = ['manual', 'stripe_connect', 'paypal_connect'];

            if ( gateways.includes(selected_paygate) )
                cyclesText.show();
            else
                cyclesText.hide();

        }


        /**
         * Disable the form submit button when the form is submitted
         *
         */
        jQuery(document).on( 'submit', '.pms-form', disable_form_submit_button )

        if( jQuery( '.wppb-register-user' ).length > 0 && jQuery( '.wppb-register-user .wppb-subscription-plans' ).length > 0 )
            jQuery(document).on('submit', '.wppb-register-user', disable_form_submit_button)

        window.disable_form_submit_button = disable_form_submit_button;
        function disable_form_submit_button( e ){

            if (jQuery(e.target).is('form')) {
                var form = jQuery(e.target)
            } else {
                var form = jQuery(e)
            }

            var target_button = jQuery( 'input[type="submit"], button[type="submit"]', form ).not('#pms-apply-discount').not('input[name="pms_redirect_back"]')[0]

            if ( $(target_button).hasClass('pms-submit-disabled') )
                return false

            $(target_button).data('original-value', $(target_button).val())

            // Replace the button text with the placeholder
            if (pms_payment_button_loading_placeholder_text.length > 0) {

                $(target_button).addClass('pms-submit-disabled').val(pms_payment_button_loading_placeholder_text)

                if ($(target_button).is('button'))
                    $(target_button).text(pms_payment_button_loading_placeholder_text)

            }

        }


        /**
         * Trigger a click on the checked subscription plan when checking / unchecking the
         * auto-renew checkbox as this also takes into account whether the auto-renew field
         * is checked, thus hiding the unneeded payment gateways
         *
         */
        $pms_auto_renew_field.click( function() {

            handle_auto_renew_field_display()
            handle_payment_gateways_display()
            handle_plan_recurring_duration_display()

        });


        /**
         * Trigger a click on the selected subscription plan so that
         * the rest of the checkout interfacte changes
         *
         */
        handle_auto_renew_field_display()
        handle_payment_gateways_display()
        handle_plan_recurring_duration_display()
        handle_billing_fields_display()

        /**
         * Show the paygates inner wrapper
         *
         */
        $( '#pms-paygates-inner' ).css( 'visibility', 'visible' );


        /**
         * Show/Hide Subscription Plan billing cycle information
         * - info displayed with the Subscription Plan price on forms
         */
        handle_billing_cycles_display( $pms_checked_paygate.val() )

        /**
         * Compatibility when the form is placed inside an Elementor Popup
         */
        jQuery(document).on('elementor/popup/show', function () {

            if ($('.pms-form', $('.elementor-popup-modal')).length > 0) {
                handle_auto_renew_field_display()
                handle_payment_gateways_display()
                handle_plan_recurring_duration_display()
                handle_billing_fields_display()

                $('#pms-paygates-inner').css('visibility', 'visible');
            }

        })

        /**
         * WPPB Conditional Logic compatibility
         */
        if ( $('.wppb-register-user').length != 0 && $('.wppb-subscription-plans').length != 0 ) {

            // if there are 2 or more plans in the form, since they use the same meta name, only the LAST field from the
            // PB Form Fields interface will have a default values selected, but we have no idea which field is displayed
            // so we need to make sure the visible one has it's default plan selected
            pmsHandleDefaultWPPBFormSelectedPlanOnLoad()
            pmsHandleGatewaysDisplayRemove()

            $(document).on( "wppbRemoveRequiredAttributeEvent", pmsHandleGatewaysDisplayRemove )
            $(document).on( "wppbAddRequiredAttributeEvent", pmsHandleGatewaysDisplayShow )

            function pmsHandleGatewaysDisplayRemove( event = '' ) {

                if( $( '#pms-paygates-wrapper' ).is( ':hidden' ) )
                    return

                if( event != '' ){
                    var element = event.target

                    if ( typeof $(element).attr('conditional-name') == 'undefined' || $(element).attr('conditional-name') != 'subscription_plans' )
                        return
                }

                var visible_plans = false

                $('.wppb-subscription-plans').each( function( index, item ){

                    if( $( item ).is( ':visible' ) ){

                        var only_free_plans = true

                        $( '.pms-subscription-plan input[name="subscription_plans"]', $( item ) ).each( function( index, item ){

                            if( $( item ).data('price') && $( item ).data('price') > 0 ){
                                only_free_plans = false
                                return false
                            }

                        })

                        if( only_free_plans )
                            visible_plans = false
                        else
                            visible_plans = true

                        return false
                    }

                })

                if( visible_plans === false ){

                    $('#pms-paygates-wrapper').hide()
                    $( paygate_selector ).attr( 'disabled', true )
                    $( paygate_selector ).closest( 'label' ).hide()

                    $('.pms-paygate-extra-fields').hide()
                    $('.pms-billing-details').hide()

                    $('.pms-price-breakdown__holder').hide()

                    $('input[type="submit"], button[type="submit"]', $(element).closest( '.pms-form, .wppb-register-user' ) ).show()

                } else {
                    pmsHandleDefaultWPPBFormSelectedPlanOnLoad()
                }

            }

            function pmsHandleGatewaysDisplayShow(event = '') {

                if (event != '') {
                    var element = event.target

                    if (typeof $(element).attr('conditional-name') == 'undefined' || $(element).attr('conditional-name') != 'subscription_plans')
                        return
                }

                var visible_plans = false

                $('.wppb-subscription-plans').each(function (index, item) {

                    if ($(item).is(':visible')) {

                        var only_free_plans = true

                        $('.pms-subscription-plan', $(item)).each(function (index, plan) {

                            if ($('input', $(plan)).data('price') && $('input', $(plan)).data('price') > 0) {
                                only_free_plans = false
                                return false
                            }

                        })

                        if (only_free_plans)
                            visible_plans = false
                        else
                            visible_plans = true

                        return false
                    }

                })

                if ( visible_plans === false ) {

                    $('#pms-paygates-wrapper').hide()
                    $(paygate_selector).attr('disabled', true)
                    $(paygate_selector).closest('label').hide()

                    $('.pms-paygate-extra-fields').hide()
                    $('.pms-billing-details').hide()

                    $('.pms-price-breakdown__holder').hide()

                    $('input[type="submit"], button[type="submit"]', $(element).closest( '.pms-form, .wppb-register-user' ) ).show()

                } else {

                    $('#pms-paygates-wrapper').show()
                    $(paygate_selector).removeAttr('disabled')
                    $(paygate_selector).closest('label').show()

                    $('.pms-paygate-extra-fields').show()
                    $('.pms-billing-details').attr('style', 'display: flex;');

                    $('.pms-price-breakdown__holder').show()

                    // If PayPal Connect is selected, show the PayPal Connect extra fields
                    if ( ( $( 'input[type=radio][name=pay_gate]:checked' ).val() == 'paypal_connect' || $('input[type=hidden][name=pay_gate]').val() == 'paypal_connect' ) && 
                      ( !$( 'input[type=radio][name=pay_gate]:checked' ).is(':disabled') || !$('input[type=hidden][name=pay_gate]').is(':disabled') ) 
                    ){
                        $( '.pms-paygate-extra-fields-paypal_connect' ).show()
                        $( '.wppb-register-user .form-submit input[type="submit"], .wppb-register-user.form-submit button[type="submit"]' ).last().hide()
                    }

                }

            }

            function pmsHandleDefaultWPPBFormSelectedPlanOnLoad() {

                if( !( jQuery( '#wppb-register-user' ).length > 0 ) )
                    return

                // 2 or more plans in the form
                if( !( jQuery( '.wppb-subscription-plans').length > 1 ) )
                    return

                jQuery('.wppb-subscription-plans' ).each( function(){

                    if( jQuery( this ).is( ':visible' ) ){

                        jQuery( this ).find("input[name=\'subscription_plans\']").each(function (index, item) {

                            if ( typeof jQuery(item).data("default-selected") != "undefined" && jQuery(item).data("default-selected") == true ) {
                                jQuery(item).prop("checked", "checked")
                                jQuery(item).trigger("click")
                            }

                        })

                        return
                    }

                })

            }

        }

        /**
         * On the Change Subscription form change the button name based on which plans group the user clicks
         */
        if( $('#pms-change-subscription-form').length > 0 ){

            if ( $pms_checked_subscription.closest('.pms-upgrade__group').hasClass('pms-upgrade__group--upgrade') ){

                $('#pms-change-subscription-form input[name="pms_change_subscription"]').val($('#pms-change-subscription-form input[name="pms_button_name_upgrade"]').val())
                $('#pms-change-subscription-form input[name="form_action"]').val($('#pms-change-subscription-form input[data-name="upgrade_subscription"]').val())

            } else if ( $pms_checked_subscription.closest('.pms-upgrade__group').hasClass('pms-upgrade__group--downgrade') ){

                $('#pms-change-subscription-form input[name="pms_change_subscription"]').val($('#pms-change-subscription-form input[name="pms_button_name_downgrade"]').val())
                $('#pms-change-subscription-form input[name="form_action"]').val($('#pms-change-subscription-form input[data-name="downgrade_subscription"]').val())

            }

            $('#pms-change-subscription-form .pms-upgrade__group--upgrade .pms-subscription-plan input').on('click', function () {

                $('#pms-change-subscription-form input[name="pms_change_subscription"]').val($('#pms-change-subscription-form input[name="pms_button_name_upgrade"]').val())
                $('#pms-change-subscription-form input[name="form_action"]').val($('#pms-change-subscription-form input[data-name="upgrade_subscription"]').val())

            })

            $('#pms-change-subscription-form .pms-upgrade__group--downgrade .pms-subscription-plan input').on('click', function () {

                $('#pms-change-subscription-form input[name="pms_change_subscription"]').val($('#pms-change-subscription-form input[name="pms_button_name_downgrade"]').val())
                $('#pms-change-subscription-form input[name="form_action"]').val($('#pms-change-subscription-form input[data-name="downgrade_subscription"]').val())

            })

            $('#pms-change-subscription-form .pms-upgrade__group--change .pms-subscription-plan input').on('click', function () {

                $('#pms-change-subscription-form input[name="pms_change_subscription"]').val($('#pms-change-subscription-form input[name="pms_button_name_change"]').val())
                $('#pms-change-subscription-form input[name="form_action"]').val('')

            })

        }

    })


    /*
     * Add field error for a given element name
     *
     */
    $.pms_add_field_error = function( error, field_name ) {

        if( error == '' || error == 'undefined' || field_name == '' || field_name == 'undefined' )
            return false;

        $field          = $('[name=' + field_name + ']');
        $field_wrapper  = $field.closest('.pms-field');

        error = '<p>' + error + '</p>';

        if( $field_wrapper.find('.pms_field-errors-wrapper').length > 0 )
            $field_wrapper.find('.pms_field-errors-wrapper').html( error );
        else
            $field_wrapper.append('<div class="pms_field-errors-wrapper pms-is-js">' + error + '</div>');

    }

    $.pms_add_general_error = function( error ){
        if( error == '' || error == 'undefined' )
            return false

        var target = $('.pms-form')

        target.prepend( '<div class="pms_field-errors-wrapper pms-is-js"><p>' + error + '</p></div>' )
    }

    $.pms_add_subscription_plans_error = function( error ){
        if( error == '' || error == 'undefined' )
            return false

        $('<div class="pms_field-errors-wrapper pms-is-js"><p>' + error + '</p></div>').insertBefore( '#pms-paygates-wrapper' )
    }

    $.pms_add_recaptcha_field_error = function( error, payment_button ){

        $field_wrapper = $( '#pms-recaptcha-register-wrapper', $(payment_button).closest('form') )

        error = '<p>' + error + '</p>'

        if ( $field_wrapper.find('.pms_field-errors-wrapper').length > 0 )
            $field_wrapper.find('.pms_field-errors-wrapper').html(error)
        else
            $field_wrapper.append('<div class="pms_field-errors-wrapper pms-is-js">' + error + '</div>')
    }       

    /**
     * Check if a plan has trial enabled
     */
    $.pms_plan_has_trial = function( element = null ) {

        if( element == null )
            element = $pms_checked_subscription

        if (typeof element.data('trial') == 'undefined' || element.data('trial') == '0' )
            return false

        return true

    }

    /**
     * Check if a plan has sign-up fee enabled
     */
    $.pms_plan_has_signup_fee = function( element = null ) {

        if( element == null )
            element = $pms_checked_subscription

        if( typeof element.data('sign_up_fee') == 'undefined' || element.data('sign_up_fee') == '0' )
            return false

        return true

    }

    /**
     * Check if a plan is prorated
     */
    $.pms_plan_is_prorated = function( element = null ) {

        if ( !( $('#pms-change-subscription-form').length > 0 ) )
            return false

        if( element == null )
            element = $pms_checked_subscription

        if ( typeof element.data('prorated_discount') != 'undefined' && element.data('prorated_discount') > 0 )
            return true

        return false

    }

    /**
     * Checks if a given/selected plan plus the current form state create a recurring checkout
     */
    $.pms_checkout_is_recurring = function( element = null ) {

        if( element == null )
            element = $pms_checked_subscription

        if ( ( settings_recurring == '2' || $('input[name="pms_recurring"]', $pms_auto_renew_field).prop('checked') || element.data('recurring') == 2 ) && element.data('recurring') != 3 )
            return true

        return false

    }

    /**
     * Function to hide payment fields
     *
     */
    $.pms_hide_payment_fields = function( form ) {

        if( typeof form == 'undefined' )
            return

        if ( typeof form.pms_paygates_wrapper == 'undefined' )
            form.pms_paygates_wrapper = form.find('#pms-paygates-wrapper').clone()

        form.find('#pms-paygates-wrapper').replaceWith('<span id="pms-paygates-wrapper">')

        form.find('.pms-paygate-extra-fields').hide()

        // When hiding PayPal we need to restore the register button
        if ( form.find('.pms-paygate-extra-fields-paypal_connect').length > 0 ){
            if ( typeof $pms_checked_paygate != 'undefined' && $pms_checked_paygate.val() == 'paypal_connect' ){
                form.find( 'input[type="submit"], button[type="submit"]' ).show()
            }
        }

        if ( typeof PMS_ChosenStrings !== 'undefined' && $.fn.chosen != undefined ) {
            form.find('#pms_billing_country').chosen('destroy')
            form.find('#pms_billing_state').chosen('destroy')
        }

        if ( typeof form.pms_billing_details == 'undefined' ) {
            form.pms_billing_details = form.find('.pms-billing-details').clone()
        }

        form.find('.pms-billing-details').replaceWith('<span class="pms-billing-details">')

    }

    /**
     * Function to show payment fields
     *
     */
    $.pms_show_payment_fields = function( form ) {

        if( typeof form == 'undefined' )
            return

        if ( typeof form.pms_paygates_wrapper != 'undefined' )
            form.find('#pms-paygates-wrapper').replaceWith( form.pms_paygates_wrapper )

        if ( typeof $pms_checked_paygate != 'undefined' && $pms_checked_paygate.data('type') == 'extra_fields' )
            form.find('.pms-paygate-extra-fields-' + $pms_checked_paygate.val() ).show()

        // When showing PayPal we need to hide the register button
        if ( form.find('.pms-paygate-extra-fields-paypal_connect').length > 0 ){
            if ( typeof $pms_checked_paygate != 'undefined' && $pms_checked_paygate.val() == 'paypal_connect' ){
                form.find( 'input[type="submit"]:not([name="pms_redirect_back"]):not([id="pms-apply-discount"]), button[type="submit"]' ).hide()
            }
        }

        if ( typeof form.pms_billing_details != 'undefined' ) {

            form.find('.pms-billing-details').replaceWith(form.pms_billing_details)

            if ( typeof PMS_ChosenStrings !== 'undefined' && $.fn.chosen != undefined ) {

                form.find('#pms_billing_country').chosen(PMS_ChosenStrings)

                if ( $('#pms_billing_state option').length > 0 )
                    form.find('#pms_billing_state').chosen(PMS_ChosenStrings)

            }

        }

    }

    /**
     * Checks if a given/selected plan plus the current form state create a checkout without a payment, we call it setup_intents similar to Stripe
     */
    $.pms_checkout_is_setup_intents = function () {

        let selected_plan = $(subscription_plan_selector + '[type=radio]').length > 0 ? $(subscription_plan_selector + '[type=radio]:checked') : $(subscription_plan_selector + '[type=hidden]')

        if ( typeof selected_plan.data('trial') != 'undefined' && selected_plan.data('trial') == '1' && !$.pms_plan_has_signup_fee( selected_plan ) )
            return true
        // If a 100% discount code is used, initial amount will be 0
        else if ( $('input[name="discount_code"]').length > 0 && $('input[name="discount_code"]').val().length > 0 && typeof selected_plan.data('price') != 'undefined' && selected_plan.data('price') == '0' )
            return true
        // Pro-rated subscriptions
        else if ($.pms_plan_is_prorated(selected_plan) && typeof selected_plan.data('price') != 'undefined' && selected_plan.data('price') == '0')
            return true

        return false

    }

    /**
     * Adds validation errors to the WPPB form
     */
    $.pms_form_add_wppb_validation_errors = function ( errors, current_button ) {

        let scroll = false

        // errors is of the form: FIELD_ID => FIELD_ERROR
        jQuery.each(errors, function (key, value) {

            let field = jQuery('#wppb-form-element-' + key)

            field.addClass('wppb-field-error')
            field.append(value)

            scroll = true

        })

        if ( scroll )
            $.pms_form_scrollTo( '.wppb-register-user', current_button )

    }

    /**
     * Adds validation errors to the Stripe credit card information
     */
    $.pms_stripe_add_credit_card_error = function (error) {

        if (error == '' || error == 'undefined')
            return false

        $field_wrapper = $('.pms-paygate-extra-fields-stripe_connect');

        error = '<p>' + error + '</p>'

        if ($field_wrapper.find('.pms_field-errors-wrapper').length > 0)
            $field_wrapper.find('.pms_field-errors-wrapper').html(error)
        else
            $field_wrapper.append('<div class="pms_field-errors-wrapper pms-is-js">' + error + '</div>')

    }

    $.pms_form_add_validation_errors = function (errors, payment_button) {

        var scrollLocation = '';

        $.each(errors, function (index, value) {

            if (value.target == 'form_general') {
                $.pms_add_general_error(value.message)

                scrollLocation = '.pms-form'
            } else if (value.target == 'subscription_plan' || value.target == 'subscription_plans' || value.target == 'payment_gateway') {
                $.pms_add_subscription_plans_error(value.message)

                if (scrollLocation == '')
                    scrollLocation = '.pms-field-subscriptions'
            } else if (value.target == 'credit_card') {
                $.pms_stripe_add_credit_card_error(value.message)

                if (scrollLocation == '')
                    scrollLocation = '#pms-paygates-wrapper'
            } else if (value.target == 'recaptcha-register') {

                $.pms_add_recaptcha_field_error(value.message, payment_button)

            } else {
                $.pms_add_field_error(value.message, value.target)

                if (scrollLocation == '' && value.target.indexOf('pms_billing') !== -1)
                    scrollLocation = '.pms-billing-details'
                else
                    scrollLocation = '.pms-form'
            }

        })

        if ($(payment_button).attr('name') == 'pms_update_payment_method' && scrollLocation == '#pms-paygates-wrapper')
            scrollLocation = '#pms-stripe-connect';

        $.pms_form_scrollTo(scrollLocation, payment_button)

    }

    $.pms_form_reset_submit_button = function(target) {

        if (!target.data || !target.data('original-value') || typeof target.data('original-value') == undefined) {
            value = target.val()
        } else {
            value = target.data('original-value')
        }

        setTimeout(function () {
            target.attr('disabled', false).removeClass('pms-submit-disabled').val(value).blur()

            if ($(target).is('button'))
                $(target).text(value)

        }, 1)

    }

    $.pms_form_scrollTo = function( scrollLocation, payment_button )  {

        var form = $( scrollLocation )[0]

        if (typeof form == 'undefined') {
            $.pms_form_reset_submit_button( payment_button )
            return
        }

        var coord = form.getBoundingClientRect().top + window.scrollY
        var offset = -170

        window.scrollTo({
            top: coord + offset,
            behavior: 'smooth'
        })

        $.pms_form_reset_submit_button( payment_button )

    }

    $.pms_form_remove_errors = function() {

        $('.pms_field-errors-wrapper').remove()

        if ($('.pms-stripe-error-message').length > 0)
            $('.pms-stripe-error-message').remove()

        if ($('.wppb-register-user').length > 0) {

            $('.wppb-form-error').remove()

            $('.wppb-register-user .wppb-form-field').each(function () {

                $(this).removeClass('wppb-field-error')

            })

        }

    }

    $.pms_form_get_data = async function( current_button, verify_captcha = false ) {

        if (!current_button)
            return false

        var form = $(current_button).closest('form')

        // grab all data from the form
        var data = form.serializeArray().reduce(function (obj, item) {
            obj[item.name] = item.value
            return obj
        }, {})

        // setup our custom AJAX action and add the current page URL
        data.action       = 'pms_process_checkout'
        data.current_page = window.location.href
        data.pms_nonce    = $('#pms-process-checkout-nonce').val()
        data.form_type    = $('.wppb-register-user .wppb-subscription-plans').length > 0 ? 'wppb' : $('.pms-ec-register-form').length > 0 ? 'pms_email_confirmation' : 'pms'

        /**
         * Add the name of the submit button as a key to the request data
         * this is necessary for logged in actions like change, retry or renew subscription
         */
        data[current_button.attr('name')] = true

        /**
         * Add the form_action field from the form necessary for Change Subscription form requests
         */
        if ($('input[name="form_action"]', form) && $('input[name="form_action"]', form).length > 0)
            data.form_action = $('input[name="form_action"]', form).val()

        // add WPPB fields metadata to request if necessary
        if (data.form_type == 'wppb') {
            data.wppb_fields = $.pms_form_get_wppb_fields(current_button)
        }

        // if user is logged in, set form type to current form
        if ($('body').hasClass('logged-in'))
            data.form_type = $('input[type="submit"], button[type="submit"]', form).not('#pms-apply-discount').not('input[name="pms_redirect_back"]').attr('name')

        // This will be used to determine if the checkout involves a payment or not in case of a trial subscription
        if ($.pms_checkout_is_setup_intents())
            data.setup_intent = true

        if (data.pms_current_subscription)
            data.subscription_id = data.pms_current_subscription

        // Recaptcha Compatibility
        // If reCaptcha field was not validated, don't send data to the server
        if (verify_captcha && typeof data['g-recaptcha-response'] != 'undefined' && data['g-recaptcha-response'] == '') {

            if ( data.form_type == 'wppb' )
                $.pms_form_add_wppb_validation_errors({ recaptcha: { field: 'recaptcha', error: '<span class="wppb-form-error">This field is required</span>' } }, current_button)
            else
                $.pms_add_recaptcha_field_error( 'Please complete the reCaptcha.', current_button )

            $.pms_form_reset_submit_button( current_button )

            return false

        }

        return data

    }

    $.pms_form_get_wppb_fields = function( current_button ) {

        var fields = {}

        // Taken from Multi Step Forms
        jQuery('li.wppb-form-field', jQuery(current_button).closest('form')).each(function () {

            if (jQuery(this).attr('class').indexOf('heading') == -1 && jQuery(this).attr('class').indexOf('wppb_billing') == -1
                && jQuery(this).attr('class').indexOf('wppb_shipping') == -1 && jQuery(this).attr('class').indexOf('wppb-shipping') == -1) {

                var meta_name;

                if (jQuery(this).hasClass('wppb-repeater') || jQuery(this).parent().attr('data-wppb-rpf-set') == 'template' || jQuery(this).hasClass('wppb-recaptcha')) {
                    return true;
                }

                if (jQuery(this).hasClass('wppb-send-credentials-checkbox'))
                    return true;

                /* exclude conditional required fields */
                if (jQuery(this).find('[conditional-value]').length !== 0) {
                    return true;
                }

                fields[jQuery(this).attr('id')] = {};
                fields[jQuery(this).attr('id')]['class'] = jQuery(this).attr('class');

                if (jQuery(this).hasClass('wppb-woocommerce-customer-billing-address')) {
                    meta_name = 'woocommerce-customer-billing-address';
                } else if (jQuery(this).hasClass('wppb-woocommerce-customer-shipping-address')) {
                    meta_name = 'woocommerce-customer-shipping-address';

                    if (!jQuery('.wppb-woocommerce-customer-billing-address #woo_different_shipping_address', jQuery(current_button).closest('form')).is(':checked')) {
                        return true;
                    }
                } else {
                    meta_name = jQuery(this).find('label').attr('for');

                    //fields[jQuery( this ).attr( 'id' )]['required'] = jQuery( this ).find( 'label' ).find( 'span' ).attr( 'class' );
                    fields[jQuery(this).attr('id')]['title'] = jQuery(this).find('label').first().text().trim();
                }

                fields[jQuery(this).attr('id')]['meta-name'] = meta_name;

                if (jQuery(this).parent().parent().attr('data-wppb-rpf-meta-name')) {
                    var repeater_group = jQuery(this).parent().parent();

                    fields[jQuery(this).attr('id')]['extra_groups_count'] = jQuery(repeater_group).find('#' + jQuery(repeater_group).attr('data-wppb-rpf-meta-name') + '_extra_groups_count').val();
                }

                if (jQuery(this).hasClass('wppb-woocommerce-customer-billing-address')) {
                    var woo_billing_fields_fields = {};

                    jQuery('ul.wppb-woo-billing-fields li.wppb-form-field', jQuery(current_button).closest('form')).each(function () {
                        if (!jQuery(this).hasClass('wppb_billing_heading')) {
                            woo_billing_fields_fields[jQuery(this).find('label').attr('for')] = jQuery(this).find('label').text();
                        }
                    });

                    fields[jQuery(this).attr('id')]['fields'] = woo_billing_fields_fields;
                }

                if (jQuery(this).hasClass('wppb-woocommerce-customer-shipping-address')) {
                    var woo_shipping_fields_fields = {};

                    jQuery('ul.wppb-woo-shipping-fields li.wppb-form-field', jQuery(current_button).closest('form')).each(function () {
                        if (!jQuery(this).hasClass('wppb_shipping_heading')) {
                            woo_shipping_fields_fields[jQuery(this).find('label').attr('for')] = jQuery(this).find('label').text();
                        }
                    });

                    fields[jQuery(this).attr('id')]['fields'] = woo_shipping_fields_fields;
                }
            }
        })

        return fields

    }

    /*
    * GDPR Delete button
     */
    jQuery("#pms-delete-account").on("click", function (e) {
        e.preventDefault();

        var pmsDeleteUser = prompt(pmsGdpr.delete_text);
        if( pmsDeleteUser === "DELETE" ) {
            window.location.replace(pmsGdpr.delete_url);
        }
        else{
            alert( pmsGdpr.delete_error_text );
        }
    })

})


/*
 * Profile Builder Compatibility
 *
 */
jQuery( function($) {

    $(document).ready( function() {

        /**
         * Hide email confirmation payment message if no subscription plan is checked, or a free subscription is selected
         */

        // Handle on document ready
        if ( ( $('.pms-subscription-plan input[type=radio][data-price="0"]').is(':checked') || $('.pms-subscription-plan input[type=hidden]').attr( 'data-price' ) == '0' ||
            $('.pms-subscription-plan input[type=radio]').prop('checked') == false ) && !$.pms_plan_has_signup_fee() ) {

            $('.pms-email-confirmation-payment-message').hide()
        }

        if( $('.pms-subscription-plan input[type=radio]').length > 0 ) {

            var has_paid_subscription = false

            $('.pms-subscription-plan input[type=radio]').each( function() {
                if( $(this).data('price') != 0 || $.pms_plan_has_signup_fee( $(this) ) )
                    has_paid_subscription = true
            })

            if( !has_paid_subscription )
                $('.pms-email-confirmation-payment-message').hide()

        }

        // Handle clicking on the subscription plans
        $('.pms-subscription-plan input[type=radio]').click(function(){

            if ( $('.pms-subscription-plan input[type=radio][data-price="0"]').is(':checked') && !$.pms_plan_has_signup_fee( $(this) ) )
                $('.pms-email-confirmation-payment-message').hide()
            else
                $('.pms-email-confirmation-payment-message').show()

        })

        $('.wppb-edit-user input[required]').on('invalid', function(e){
            $.pms_reset_submit_button( $('.wppb-edit-user .wppb-subscription-plans input[type="submit"]').first() )
        })

    })

})


/**
 * Billing Fields
 */
jQuery( function($) {

    $(document).ready( function() {

        // States field
        if( typeof PMS_States == 'undefined' || !PMS_States )
            return

        pms_handle_billing_state_field_display()

        $(document).on( 'change', '#pms_billing_country', function() {

            pms_handle_billing_state_field_display()

        })

        if( typeof PMS_ChosenStrings !== 'undefined' && $.fn.chosen != undefined ){
            $('#pms_billing_country').chosen( PMS_ChosenStrings )

            if( $('#pms_billing_state option').length > 0 )
                $('#pms_billing_state').chosen( PMS_ChosenStrings )
        }

        // Autocomplete email address
        $( 'input[name=pms_billing_email], input[name=pms_billing_first_name], input[name=pms_billing_last_name]' ).each(function () {

            if ( $(this).val() != '' )
                $(this).addClass('pms-has-value')

        })

    })

    /**
     * Fill up Email Address, First Name and Last Name based on what the user has type in the other fields
     *
     */
    $(document).on('keyup', '#pms_user_email, .wppb-form-field input[name=email]', function () {

        if ( $(this).closest('form').find('[name=pms_billing_email]').length == 0 )
            return false

        if ( $(this).closest('form').find('[name=pms_billing_email]').hasClass('pms-has-value') )
            return false

        $(this).closest('form').find('[name=pms_billing_email]').val( $(this).val() )

    })

    $(document).on('keyup', '#pms_first_name', function () {

        if ( $(this).closest('form').find('[name=pms_billing_first_name]').length == 0 )
            return false

        if ( $(this).closest('form').find('[name=pms_billing_first_name]').hasClass('pms-has-value') )
            return false

        $(this).closest('form').find('[name=pms_billing_first_name]').val( $(this).val() )

    })

    $(document).on('keyup', '#pms_last_name', function () {

        if ( $(this).closest('form').find('[name=pms_billing_last_name]').length == 0 )
            return false

        if ( $(this).closest('form').find('[name=pms_billing_last_name]').hasClass('pms-has-value') )
            return false

        $(this).closest('form').find('[name=pms_billing_last_name]').val( $(this).val() )

    })

    function pms_handle_billing_state_field_display(){

        var country = $('.pms-billing-details #pms_billing_country').val()

        if( PMS_States[country] ){

            if( typeof PMS_ChosenStrings !== 'undefined' && $.fn.chosen != undefined )
                $('.pms-billing-state__select').chosen('destroy')

            $('.pms-billing-state__select option').remove()
            $('.pms-billing-state__select').append('<option value=""></option>');

            for( var key in PMS_States[country] ){
                if( PMS_States[country].hasOwnProperty(key) )
                    $('.pms-billing-state__select').append('<option value="'+ key +'">'+ PMS_States[country][key] +'</option>')
            }

            var prevValue = $('.pms-billing-state__input').val()

            if( prevValue != '' )
                $('.pms-billing-state__select').val( prevValue )

            $('.pms-billing-state__input').removeAttr('name').removeAttr('id').hide()
            $('.pms-billing-state__select').attr('name','pms_billing_state').attr('id','pms_billing_state').show()

            if( typeof PMS_ChosenStrings !== 'undefined' && $.fn.chosen != undefined )
                $('.pms-billing-state__select').chosen( PMS_ChosenStrings )

        } else {

            if( typeof PMS_ChosenStrings !== 'undefined' && $.fn.chosen != undefined )
                $('.pms-billing-state__select').chosen('destroy')

            $('.pms-billing-state__select').removeAttr('name').removeAttr('id').hide()
            $('.pms-billing-state__input').attr('name','pms_billing_state').attr('id','pms_billing_state').show()

        }

    }

})
