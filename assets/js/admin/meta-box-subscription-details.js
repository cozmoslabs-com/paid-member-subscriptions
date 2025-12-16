/*
 * JavaScript for Subscription Plan Details meta-box that is attached to the
 * Subscription Plan custom post type
 *
 */
jQuery( function($) {

    $(document).ready( function(){
        if( $('.datepicker').length > 0 ){
            $('.datepicker').datepicker({
                dateFormat: 'mm/dd/yy',
            })
            pms_handle_fixed_membership_display();
        }
    });

    /*
     * Validates the duration value introduced, this value must be a whole number
     *
     */
    $(document).on( 'click', '#publish', function() {

        var subscription_plan_duration = $('#pms-subscription-plan-duration').val().trim();

        if( ( parseInt( subscription_plan_duration ) != subscription_plan_duration ) || ( parseFloat( subscription_plan_duration ) == 0 && subscription_plan_duration.length > 1 ) ) {

            alert( 'Subscription Plan duration must be a whole number.' );

            return false;
        }

    });

    /*
     * Function that controls the display of duration and fixed membership datepicker fields accordingly
     *
     */
    function pms_handle_fixed_membership_display(){
        if( $('#pms-subscription-plan-fixed-membership:checked').length > 0 ){
            $('#pms-subscription-plan-duration-field').hide();
            $('.pms-subscription-plan-fixed-membership-field').show();
            pms_handle_renewal_options_display();
        }
        else{
            $('#pms-subscription-plan-duration-field').show();
            $('#pms-subscription-plan-renewal-option-field').show();
            $('.pms-subscription-plan-fixed-membership-field').hide();
        }
    }

    /*
     * Function that controls the display of renewal options for fixed memberships when Allow renewal checkbox is checked
     *
     */
    function pms_handle_renewal_options_display(){
        if( $('#pms-subscription-plan-renewal-option-field') !== undefined ){
            if( $('#pms-subscription-plan-allow-renew:checked').length > 0 ){
                $('#pms-subscription-plan-renewal-option-field').show();
            }
            else{
                $('#pms-subscription-plan-renewal-option-field').hide();
            }
        }
    }

    /*
     * Displays a datepicker instead of the duration field if Fixed Membership is checked
     *
     */
    $(document).on( 'click', '#pms-subscription-plan-fixed-membership', function() {
        pms_handle_fixed_membership_display();
    });

    /*
     * Displays Renewal options for Fixed Membership if Allow plan renewal is checked
     *
     */
    $(document).on( 'click', '#pms-subscription-plan-allow-renew', function() {
        pms_handle_renewal_options_display();
    });

    /*
     * Handles Renewal options displayed according to Fixed Membership and Allow plan renewal
     *
     */
    if( $('#pms-subscription-plan-renewal-option-field') !== undefined && $('#pms-subscription-plan-fixed-membership:checked').length > 0 && $('#pms-subscription-plan-allow-renew:checked').length <= 0 ){
        $('#pms-subscription-plan-renewal-option-field').hide();
    }
});


jQuery(document).ready(function($) {

    // Show/Hide upgrade notice in PMS Free Version
    $('#pms-plan-type').change(function (e) {
        if ( this.value === 'group' )
            $('#pms-group-memberships-addon-notice').show();
        else $('#pms-group-memberships-addon-notice').hide();
    });

    /*
     * Initialise chosen
     *
     */
    if( $.fn.chosen !== undefined ) {
        $('.pms-chosen').chosen();
    }

    // Handle Payment Installments feature settings visibility
    pms_handle_payment_cycle_options();

    // Handle the Payment Installments feature subscription payment related details notice
    pms_handle_payment_cycle_notice();

    pms_handle_gift_subscription_toggle();
});


/**
 * Handle Payment Installments feature settings visibility
 *
 */
function pms_handle_payment_cycle_options() {
    
    // handle Payment Cycles visibility
    let fixedPeriod = jQuery('#pms-subscription-plan-fixed-membership'),
        paymentCycles = jQuery('#payment-cycles');

    toggle_payment_cycles_field();

    fixedPeriod.on('change', toggle_payment_cycles_field);

    function toggle_payment_cycles_field() {
        let isFixedPeriod = fixedPeriod.is(':checked');

        if ( isFixedPeriod ) {
            paymentCycles.hide();
        } else {
            paymentCycles.show();
        }
    }


    // handle Payment Cycles & Renewal options and descriptions visibility
    let limitCycles = jQuery('#pms-limit-payment-cycles'),
        cycleOptions = jQuery('#pms-payment-cycle-options'),
        renewalOption = jQuery('#pms-subscription-plan-recurring'),
        renewalDescription = jQuery('#pms-renewal-description'),
        renewalCyclesDescription = jQuery('#pms-renewal-cycles-description');

    toggle_payment_cycle_options();

    limitCycles.on('change', toggle_payment_cycle_options);

    function toggle_payment_cycle_options() {
        if ( limitCycles.is(':checked') ) {
            cycleOptions.show();
            renewalOption.hide();
            renewalDescription.hide();
            renewalCyclesDescription.show();
        }
        else {
            cycleOptions.hide();
            renewalOption.show();
            renewalDescription.show();
            renewalCyclesDescription.hide();
        }
    }


    // handle Expire After Last Cycle options visibility
    let statusAfter = jQuery('#pms-subscription-plan-status-after-last-cycle'),
        expireOptions = jQuery('#pms-subscription-plan-expire-after-field');

    toggle_expire_after_options();

    statusAfter.on('change', toggle_expire_after_options);

    function toggle_expire_after_options() {
        if ( statusAfter.length > 0 && statusAfter.val() === 'expire_after' ) {
            expireOptions.show();
        }
        else {
            expireOptions.hide();
        }
    }

}


/**
 * Handle the Payment Installments feature subscription payment related details notice
 *
 */
function pms_handle_payment_cycle_notice() {

    handle_note_output();

    jQuery(document).on('change', '#pms-subscription-plan-duration, #pms-subscription-plan-duration-unit, #pms-subscription-plan-price, #pms-subscription-plan-number-of-payments, #pms-limit-payment-cycles', handle_note_output )

    function handle_note_output() {
        jQuery('#pms-payment-cycles-note').remove();

        let $limitCycles = jQuery('#pms-limit-payment-cycles'),
            price = jQuery('#pms-subscription-plan-price').val();

        if ( ! price || ! $limitCycles.is(':checked') )
            return;

        let $fieldWrapper = jQuery('#pms-subscription-plan-number-of-payments-field'),
            duration      = jQuery('#pms-subscription-plan-duration').val(),
            durationUnit  = jQuery('#pms-subscription-plan-duration-unit').val(),
            currency      = jQuery('#pms-default-currency').text().trim(),
            payments      = jQuery('#pms-subscription-plan-number-of-payments').val(),
            note          = 'Subscribers will pay <strong>' + price + ' ' + currency + '</strong>';

        if ( duration && durationUnit )
            note += ' every <strong>' + duration + ' ' + durationUnit + '(s)</strong>';

        if ( payments )
            note += ' for a total of  <strong>' + payments + ' payments</strong>';

        note += '.';

        $fieldWrapper.append('<p class="cozmoslabs-description cozmoslabs-description-space-left" id="pms-payment-cycles-note" style="color: #1E1E1E !important; margin-top: 10px;"><span style="padding: 10px 20px; background: #FDFBE6;">'+ note +'</span></p>');
    }

}

function pms_handle_gift_subscription_toggle() {

    let giftSubscription     = jQuery('.pms-subscription-plan-gift-subscription-field');
    let giftExpiration       = jQuery('.pms-subscription-plan-gift-expiration-field');
    let subscriptionPlanType = jQuery('#pms-subscription-plan-type, #pms-plan-type');
    let allowGiftingCheckbox = jQuery('#pms-subscription-plan-allow-gifting');

    subscriptionPlanType.on('change', function() {
        if ( this.value != 'regular' ) {
            giftSubscription.addClass( 'disabled' );
            jQuery('#pms-subscription-plan-allow-gifting').prop( 'checked', false );
            giftExpiration.hide();
        } else {
            giftSubscription.removeClass( 'disabled' );
            if ( allowGiftingCheckbox.is(':checked') ) {
                giftExpiration.show();
            } else {
                giftExpiration.hide();
            }
        }
    });

    if ( subscriptionPlanType.val() != 'regular' ) {
        giftSubscription.addClass( 'disabled' );
        jQuery('#pms-subscription-plan-allow-gifting').prop( 'checked', false );
        giftExpiration.hide();
    } else {
        giftSubscription.removeClass( 'disabled' );
        if ( allowGiftingCheckbox.is(':checked') ) {
            giftExpiration.show();
        } else {
            giftExpiration.hide();
        }
    }

    allowGiftingCheckbox.on('change', function() {
        if ( jQuery(this).is(':checked') && subscriptionPlanType.val() == 'regular' ) {
            giftExpiration.show();
        } else {
            giftExpiration.hide();
        }
    });

}