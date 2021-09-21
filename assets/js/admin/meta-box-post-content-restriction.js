jQuery( function(){
    /* Display custom redirect URL section if type of restriction is "Redirect" */
    jQuery( 'input[type=radio][name=pms-content-restrict-type]' ).click( function() {
        if( jQuery(this).is(':checked') && jQuery(this).val() == 'redirect' )
            jQuery('#pms-meta-box-fields-wrapper-restriction-redirect-url').addClass('pms-enabled');
        else
            jQuery('#pms-meta-box-fields-wrapper-restriction-redirect-url').removeClass('pms-enabled');
    });

    /* Display custom redirect URL field */
    jQuery( '#pms-content-restrict-custom-redirect-url-enabled' ).click( function() {
        if( jQuery(this).is(':checked') )
            jQuery('.pms-meta-box-field-wrapper-custom-redirect-url').addClass('pms-enabled');
        else
            jQuery('.pms-meta-box-field-wrapper-custom-redirect-url').removeClass('pms-enabled');
    });

    /* Display custom messages editors */
    jQuery( '#pms-content-restrict-messages-enabled' ).click( function() {
    	if( jQuery(this).is(':checked') )
    		jQuery('.pms-meta-box-field-wrapper-custom-messages').addClass('pms-enabled');
    	else
    		jQuery('.pms-meta-box-field-wrapper-custom-messages').removeClass('pms-enabled');
    });

    /* Automatically check all plans if All Subscription Plans checkbox is checked */
    jQuery( '#pms-content-restrict-all-subscription-plans' ).click( function() {
        if( jQuery(this).is(':checked') )
            jQuery('[id^=pms-content-restrict-subscription-plan-]').prop('checked', true);
    });

    /* Automatically uncheck All Subscriptions Plans checkbox if one of the plans is unchecked */
    jQuery( '[id^=pms-content-restrict-subscription-plan-]' ).click( function() {
        if( !jQuery(this).is(':checked') && jQuery( '#pms-content-restrict-all-subscription-plans' ).is(':checked') )
            jQuery( '#pms-content-restrict-all-subscription-plans').prop('checked', false);
    });

    /* Automatically check All Subscription Plans checkbox if all plans are checked */
    jQuery( '[id^=pms-content-restrict-subscription-plan-]' ).click( function() {
        if( jQuery(this).is(':checked') && !jQuery( '#pms-content-restrict-all-subscription-plans' ).is(':checked') && jQuery( '[id^=pms-content-restrict-subscription-plan-]' ).length == jQuery( '[id^=pms-content-restrict-subscription-plan-]:checked' ).length )
            jQuery( '#pms-content-restrict-all-subscription-plans').prop('checked', true);
    });

});
