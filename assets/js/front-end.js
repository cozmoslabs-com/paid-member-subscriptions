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

    // Subscription pland ans payment gateway selectors
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

            // Show / hide the credit card details
            if( $pms_checked_paygate.data('type') == 'credit_card' )
                $('.pms-credit-card-information').show()
            else
                $('.pms-credit-card-information').hide()

            // Show billing fields
            handle_billing_fields_display()

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
                    $('.pms-credit-card-information').hide();
                    $('.pms-billing-details').hide();

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
                    if ($(paygate_selector + ':not([disabled]):checked[data-type="credit_card"]').length > 0) {
                        $('.pms-credit-card-information').show();
                        $('.pms-billing-details').show();
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

                $('.pms-credit-card-information').hide()
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
                $('.pms-billing-details').show()

        }


        /**
         * Disable the form submit button when the form is submitted
         *
         */
        jQuery(document).on( 'submit', '.pms-form', disable_form_submit_button )

        if( jQuery( '.wppb-register-user' ).length > 0 && jQuery( '.wppb-register-user .wppb-subscription-plans' ).length > 0 )
            jQuery(document).on('submit', '.wppb-register-user', disable_form_submit_button)

        function disable_form_submit_button( e ){

            var target_button = jQuery( 'input[type="submit"], button[type="submit"]', jQuery(this) ).not('#pms-apply-discount').not('input[name="pms_redirect_back"]')[0]

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

                    $('.pms-credit-card-information').hide()
                    $('.pms-billing-details').hide()

                    $('.pms-price-breakdown__holder').hide()
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

                if (visible_plans === false) {
                    $('#pms-paygates-wrapper').hide()
                    $(paygate_selector).attr('disabled', true)
                    $(paygate_selector).closest('label').hide()

                    $('.pms-credit-card-information').hide()
                    $('.pms-billing-details').hide()

                    $('.pms-price-breakdown__holder').hide()
                } else {
                    $('#pms-paygates-wrapper').show()
                    $(paygate_selector).removeAttr('disabled')
                    $(paygate_selector).closest('label').show()

                    $('.pms-credit-card-information').show()
                    $('.pms-billing-details').show()

                    $('.pms-price-breakdown__holder').show()
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

    /*
     * Clear all field errors added with js
     *
     */
    $.pms_clean_field_errors = function() {

        $('.pms_field-errors-wrapper.pms-is-js').remove();

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

        form.find('.pms-credit-card-information').hide()

        if ( typeof form.pms_billing_details == 'undefined' ) {

            if ( typeof PMS_ChosenStrings !== 'undefined' && $.fn.chosen != undefined ) {
                form.find('#pms_billing_country').chosen('destroy')
                form.find('#pms_billing_state').chosen('destroy')
            }

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

        if ( typeof $pms_checked_paygate != 'unedfined' && $pms_checked_paygate.data('type') == 'credit_card' )
            form.find('.pms-credit-card-information').show()

        if ( typeof form.pms_billing_details != 'undefined' ) {

            form.find('.pms-billing-details').replaceWith(form.pms_billing_details)

            if ( typeof PMS_ChosenStrings !== 'undefined' && $.fn.chosen != undefined ) {

                form.find('#pms_billing_country').chosen(PMS_ChosenStrings)

                if ( $('#pms_billing_state option').length > 0 )
                    form.find('#pms_billing_state').chosen(PMS_ChosenStrings)

            }

        }

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
            pms_reset_submit_button( $('.wppb-edit-user .wppb-subscription-plans input[type="submit"]').first() )
        })

    })

    function pms_reset_submit_button( target ) {

        setTimeout( function() {

            target.attr( 'disabled', false ).removeClass( 'pms-submit-disabled' ).val( target.data( 'original-value' ) ).blur();

            if( $( target ).is( 'button' ) )
                $( target ).text( target.data('original-value') )

        }, 1 )

    }

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
        $('input[name=pms_billing_email]').each(function () {

            if ( $(this).val() != '' )
                $(this).addClass('pms-has-value')

        })

    })

    /**
     * Fill in billing email address when typing the email address
     *
     */
    $(document).on('keyup', '#pms_user_email, .wppb-form-field input[name=email]', function () {

        if ( $(this).closest('form').find('[name=pms_billing_email]').length == 0 )
            return false

        if ( $(this).closest('form').find('[name=pms_billing_email]').hasClass('pms-has-value') )
            return false

        $(this).closest('form').find('[name=pms_billing_email]').val( $(this).val() )

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

/**
* Move the "Pricing Page Style" button from the admin footer
* next to the "Document Overview" button
*
*/
jQuery(document).ready( function($) {
    $buttonsWrapper = $('#pms-create-pricing-page-style-wrapper');

    setTimeout(function (){

        $buttons = $buttonsWrapper.children();

        $('.edit-post-header-toolbar').after( $buttons );

        $('#pms-popup-style').css({
            'display': 'flex',
            'flex-direction': 'row',
            'align-items': 'center',
            'justify-content': 'center',
            'height': '33px',
            'margin-left': '10px',
            'gap': '7px'
        });

        $buttonsWrapper.remove();
    }, 500);

    /*
    * Showing and closing the modal
    */

    $(document).on( 'click', '#pms-popup-style', function() {
        $( '.pms-modal' ).show();
        jQuery('.overlay').show();
    });

    $(document).on( 'click', '.pms-button-close', function() {
        $( '.pms-modal' ).hide();
        jQuery('.overlay').hide();
    });
});
