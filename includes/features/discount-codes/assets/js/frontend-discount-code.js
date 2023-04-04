/*
 * JavaScript for front-end discount code display
 *
 */
jQuery(document).ready(function($) {

    // Cache the parent form of the apply discount button
    //var $pms_form;

    // Cache the value of the last checked discount code
    var last_checked_discount_code;

    /*
     * Show / Hide discount code field if a free plan is selected
     *
     */
    toggle_discount_box( $('input[name=subscription_plans][type=radio]').length > 0 ? $('input[name=subscription_plans][type=radio]:checked') : $('input[name=subscription_plans][type=hidden]') )

    /**
     * Trigger automatically "Apply" discount button when the user already entered a discount code and selects another subscription plan, or checks the "Automatically renew subscription" checkbox.
     * This will update the discount message shown below the field.
     *
     */
    $(document).on('click', '.pms-subscription-plan input[type="radio"][name="subscription_plans"]', function(){

        // If subscription is not free and discount code field is not empty
        if (($(this).attr("data-price") > 0) && ($('#pms_subscription_plans_discount_code').length > 0)) {

            $('#pms-apply-discount').trigger('click')

        } else {

            $('#pms-subscription-plans-discount-messages-wrapper').hide()
            $('#pms-subscription-plans-discount-messages').hide()

        }

        toggle_discount_box( $(this) )

    })

    $(document).on('click', '.pms-subscription-plan-auto-renew input[type="checkbox"][name="pms_recurring"]', function () {

        // If discount code field is not empty
        if ( $('#pms_subscription_plans_discount_code').length > 0 ){

            $('#pms-apply-discount').trigger('click')

        } else {

            $('#pms-subscription-plans-discount-messages-wrapper').hide()
            $('#pms-subscription-plans-discount-messages').hide()

        }

        toggle_discount_box( $($pms_checked_subscription) )

    })
    
    /**
     * Toggle discount code box when the subscription plans field is shown from the
     * Profile Builder Conditional Logic functionality
     */
    $(document).on("wppbAddRequiredAttributeEvent", function (e) {
        if ($(e.target).is('#pms_subscription_plans_discount_code'))
            toggle_discount_box($('input[name=subscription_plans][type=radio]').length > 0 ? $('input[name=subscription_plans][type=radio]:checked') : $('input[name=subscription_plans][type=hidden]'))
    })

    /**
     * Handles discount code validation when the user clicks the "Apply" discount button
     *
     */
    $('#pms-apply-discount').click(function(e){

        e.preventDefault();

        // If undefined, cache the parent form
        if( typeof $pms_form == 'undefined' )
            $pms_form = $(this).closest('form');

        var $subscription_plan = '';

        $('.pms-subscription-plan input[type="radio"]').each(function(){
            if($(this).is(':checked')){
                $subscription_plan = $(this);
            }
        });

        if( $subscription_plan == '' ) {
            $subscription_plan = $('input[type=hidden][name=subscription_plans]');
        }

        if( $('#pms_subscription_plans_discount_code').val() == '' ) {
            $('#pms-subscription-plans-discount-messages-wrapper').fadeOut( 350 );
            $('#pms-subscription-plans-discount-messages').fadeOut( 350 )

            $subscription_plan.data( 'discounted-price', false )
            jQuery(document).trigger( 'pms_discount_error' )

            return false;
        }

        // Cache the discount code
        last_checked_discount_code = $('#pms_subscription_plans_discount_code').val();

        pwyw_price = '';

        if ( $('input[name="subscription_price_'+$subscription_plan.val()+'"]').length != 0 )
            pwyw_price = $('input[name="subscription_price_'+$subscription_plan.val()+'"]').val();

        var data = {
            'action'                  : 'pms_discount_code',
            'code'                    : $.trim( $('#pms_subscription_plans_discount_code').val()),
            'subscription'            : $subscription_plan.val(),
            'recurring'               : $('input[name="pms_recurring"]:checked').val(),
            'pwyw_price'              : pwyw_price,
            'pmstkn_original'         : $pms_form.find('input[name="pmstkn"]').val(),
            'pms_current_subscription': $pms_form.find('input[name="pms_current_subscription"]').val(),
            'form_action'             : $pms_form.find('input[name="form_action"]').val(),
        };

        if( data.pmstkn_original === undefined && jQuery( '.wppb-register-user' ).length > 0 )
            data.pmstkn_original = 'pb_form'

        // Make sure it's not an empty discount
        if ( data['code'] !== '' ) {

            $('#pms-subscription-plans-discount-messages').hide()
            $('#pms-subscription-plans-discount-messages-wrapper').show()
            $('#pms-subscription-plans-discount-messages-loading').fadeIn(350)

            // We can also pass the url value separately from ajaxurl for front end AJAX implementations
            jQuery.post(pms_discount_object.ajax_url, data, function (response) {

                if (response.success != undefined) {

                    // Add success message
                    $('#pms-subscription-plans-discount-messages').removeClass('pms-discount-error')
                    $('#pms-subscription-plans-discount-messages').addClass('pms-discount-success')

                    $('#pms-subscription-plans-discount-messages-loading').fadeOut(350, function () {
                        $('#pms-subscription-plans-discount-messages').html(response.success.message).fadeIn(350)
                    })

                    // Hide payment fields
                    if (response.is_full_discount)
                        hide_payment_fields($pms_form)
                    else
                        show_payment_fields($pms_form)

                    $subscription_plan.data( 'price-original', $subscription_plan.data('price') )
                    $subscription_plan.data( 'price', response.discounted_price )
                    $subscription_plan.data( 'discounted-price', true )
                    $subscription_plan.data( 'discounted-price-value', response.original_discounted_price )

                    if( response.is_full_discount == true ){
                        
                        if( response.recurring_payments == 1 )
                            $pms_auto_renew_field.hide()

                        $subscription_plan.data( 'is-full-discount', true )

                    } else
                        $subscription_plan.data( 'is-full-discount', false )

                    $subscription_plan.data( 'discount-recurring-payments', response.recurring_payments )

                    jQuery(document).trigger( 'pms_discount_success' )

                }

                if (response.error != undefined) {

                    // Add error message
                    $('#pms-subscription-plans-discount-messages').removeClass('pms-discount-success')
                    $('#pms-subscription-plans-discount-messages').addClass('pms-discount-error')

                    $('#pms-subscription-plans-discount-messages-loading').fadeOut(350, function () {
                        $('#pms-subscription-plans-discount-messages').html(response.error.message).fadeIn(350)
                    })

                    // Show payment fields
                    show_payment_fields($pms_form)

                    $subscription_plan.data( 'price', $subscription_plan.data('price-original') )
                    $subscription_plan.data( 'discounted-price', false )
                    $subscription_plan.data( 'discounted-price-value', 0 )

                    jQuery(document).trigger( 'pms_discount_error' )

                }

            });
        } else {

            $subscription_plan.data( 'price', $subscription_plan.data('price-original') )
            $subscription_plan.data( 'discounted-price', false )

            jQuery(document).trigger( 'pms_discount_error' )

        }

    })

    /**
     * If there is a discount code value already set on document ready
     * apply it
     *
     */
    if( $('input[name=discount_code]').val() != '' )
        $('#pms-apply-discount').trigger('click')

    /**
     * When losing focus of the discount code field, directly apply the discount
     *
     */
    $('input[name=discount_code]').on( 'blur', function() {

        if( last_checked_discount_code != $('input[name=discount_code]').val() )
            $('#pms-apply-discount').trigger('click');

        if ( $('input[name=discount_code]').val() == '' )
            show_payment_fields( $pms_form );
    })

    /**
     * Clones and caches the wrappers for the payment gateways and the credit card / billing information
     * It replaces these wrappers with empy spans that represent the wrappers
     *
     */
    function hide_payment_fields( $form ) {

        if( typeof $form.pms_paygates_wrapper == 'undefined' )
            $form.pms_paygates_wrapper = $form.find('#pms-paygates-wrapper').clone();

        $form.find('#pms-paygates-wrapper').replaceWith('<span id="pms-paygates-wrapper">');

        $form.find('.pms-credit-card-information').hide()

        if( typeof $form.pms_billing_details == 'undefined' ){

            if( typeof PMS_ChosenStrings !== 'undefined' && $.fn.chosen != undefined ){
                $form.find('#pms_billing_country').chosen('destroy')
                $form.find('#pms_billing_state').chosen('destroy')
            }

            $form.pms_billing_details = $form.find('.pms-billing-details').clone();

        }

        $form.find('.pms-billing-details').replaceWith('<span class="pms-billing-details">');

    }


    /**
     * It replaces the placeholder spans, that represent the payment gateway and the credit card
     * and billing information, with the cached wrappers that contain the actual fields
     *
     */
    function show_payment_fields( $form ) {

        if( typeof $form.pms_paygates_wrapper != 'undefined' )
            $form.find('#pms-paygates-wrapper').replaceWith( $form.pms_paygates_wrapper );

        if( typeof $pms_checked_paygate != 'unedfined' && $pms_checked_paygate.data('type') == 'credit_card' )
            $form.find('.pms-credit-card-information').show()

        if( typeof $form.pms_billing_details != 'undefined' ){

            $form.find('.pms-billing-details').replaceWith( $form.pms_billing_details )

            if( typeof PMS_ChosenStrings !== 'undefined' && $.fn.chosen != undefined ){

                $form.find('#pms_billing_country').chosen( PMS_ChosenStrings )

                if( $('#pms_billing_state option').length > 0 )
                    $form.find('#pms_billing_state').chosen( PMS_ChosenStrings )

            }

        }


    }

    /*
     * Show / Hide discount code field if a free plan is selected
     *
     */
    function toggle_discount_box( $element ) {

        if( !$element )
            return

        var selector = '#pms-subscription-plans-discount';
        
        if( !subscription_has_discount( $element.val() ) )
            $(selector).hide()
        else {
            if ( $element.attr('data-price') == '0' ) {

                if ( $.isFunction( $.pms_plan_is_prorated ) && $.pms_plan_is_prorated( $element ) ) {

                    if ( $('input[name="pms_recurring"]', $('.pms-subscription-plan-auto-renew') ).prop('checked') || $element.data('recurring') == 2 ){
                        $(selector).show()
                        return
                    }

                }

                if ( $.isFunction( $.pms_plan_has_signup_fee ) && $.pms_plan_has_signup_fee( $element ) )
                    $(selector).show()

                $(selector).hide()

            } else {
                $(selector).show()
            }
        }

    }

    function subscription_has_discount( subscription_id ){

        // show the box if we don't have data available (old default)
        if( typeof pms_discount_object == 'undefined' || typeof pms_discount_object.discounted_subscriptions == 'undefined' )
            return true

        let return_value  = false
        let subscriptions = JSON.parse( pms_discount_object.discounted_subscriptions )

        for ( var subscription in subscriptions ){
            if( subscription_id == subscriptions[subscription] )
                return_value = true
        }

        return return_value

    }

});
