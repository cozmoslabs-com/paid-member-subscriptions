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

        var subscription_plan_price = $('#pms-subscription-plan-price').val().trim();

        if( parseFloat( subscription_plan_price ) < 1 ) {

            alert( 'Subscription Plan price must be at least 1.' );

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
});