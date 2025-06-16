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