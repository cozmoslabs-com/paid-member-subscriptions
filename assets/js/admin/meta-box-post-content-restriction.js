jQuery( function(){
    /* Display custom redirect URL section if type of restriction is "Redirect" */
    jQuery( 'input[type=radio][name=pms-content-restrict-type]' ).click( function() {

        if( jQuery(this).is(':checked') ) {

            if( jQuery(this).val() === 'redirect' )
                jQuery('#pms-meta-box-fields-wrapper-restriction-redirect-url').addClass('pms-enabled');
            else
                jQuery('#pms-meta-box-fields-wrapper-restriction-redirect-url').removeClass('pms-enabled');

            if( jQuery(this).val() === 'message' )
                jQuery('#pms-meta-box-field-learndash').addClass('pms-enabled');
            else
                jQuery('#pms-meta-box-field-learndash').removeClass('pms-enabled');

        }

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

/**
 * Pricing Table Designs Feature --> Admin UI
 *
 *  - Activate new Design
 *  - Preview Modal
 *  - Modal Image Slider controls
 *
 * */
jQuery( document ).ready(function(){

    // Activate Design
    jQuery('.pms-pricing-tables-design-activate button.activate').click(function ( element ) {
        let themeID, i, allDesigns;

        themeID = jQuery(element.target).data('theme-id');

        jQuery('#pms-active-pricing-table-design').val(themeID);

        allDesigns = jQuery('.pms-pricing-tables-design');
        for (i = 0; i < allDesigns.length; i++) {
            if ( jQuery(allDesigns[i]).hasClass('active')) {
                jQuery('.pms-pricing-tables-design-title strong', allDesigns[i] ).hide();
                jQuery(allDesigns[i]).removeClass('active');
            }
        }
        jQuery('#pms-pricing-tables-design-browser .pms-forms-design#'+themeID).addClass('active');

    });

    jQuery('.pms-pricing-tables-design-preview').click(function (e) {
        let themeID = e.target.id.replace('-info', '');
        displayPreviewModal(themeID);
    });

    jQuery('.pms-slideshow-button').click(function (e) {
        let themeID = jQuery(e.target).data('theme-id'),
            direction = jQuery(e.target).data('slideshow-direction'),
            currentSlide = jQuery('#pms-modal-' + themeID + ' .pms-pricing-tables-design-preview-image.active'),
            changeSlideshowImage = window[direction+'Slide'];

        changeSlideshowImage(currentSlide,themeID);
    });

});

function displayPreviewModal( themeID ) {
    jQuery('#pms-modal-' + themeID).dialog({
        resizable: false,
        height: 'auto',
        width: 1200,
        modal: true,
        closeOnEscape: true,
        open: function () {
            jQuery('.ui-widget-overlay').bind('click',function () {
                jQuery('#pms-modal-' + themeID).dialog('close');
            })
        },
        close: function () {
            let allImages = jQuery('.pms-pricing-tables-design-preview-image');

            allImages.each( function() {
                if ( jQuery(this).is(':first-child') && !jQuery(this).hasClass('active') ) {
                    jQuery(this).addClass('active');
                }
                else if ( !jQuery(this).is(':first-child') ) {
                    jQuery(this).removeClass('active');
                }
            });

            jQuery('.pms-pricing-tables-design-sildeshow-previous').addClass('disabled');
            jQuery('.pms-pricing-tables-design-sildeshow-next').removeClass('disabled');
        }
    });
    return false;
}

function nextSlide( currentSlide, themeID ){
    if ( currentSlide.next().length > 0 ) {
        currentSlide.removeClass('active');
        currentSlide.next().addClass('active');

        jQuery('#pms-modal-' + themeID + ' .pms-pricing-tables-design-sildeshow-previous').removeClass('disabled');

        if ( currentSlide.next().next().length <= 0 )
            jQuery('#pms-modal-' + themeID + ' .pms-pricing-tables-design-sildeshow-next').addClass('disabled');

    }
}

function previousSlide( currentSlide, themeID ){
    if ( currentSlide.prev().length > 0 ) {
        currentSlide.removeClass('active');
        currentSlide.prev().addClass('active');

        jQuery('#pms-modal-' + themeID + ' .pms-pricing-tables-design-sildeshow-next').removeClass('disabled');

        if ( currentSlide.prev().prev().length <= 0 )
            jQuery('#pms-modal-' + themeID + ' .pms-pricing-tables-design-sildeshow-previous').addClass('disabled');

    }
}


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
