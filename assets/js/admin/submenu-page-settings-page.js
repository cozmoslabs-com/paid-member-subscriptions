/*
 * JavaScript for Settings Submenu Page
 *
 */
jQuery( function($) {


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


    /*
     * Adds a argument name, value pair to a given URL string
     *
     */
    function pms_add_query_arg( key, value, sourceURL ) {

        return sourceURL + '&' + key + '=' + value;

    }

    if( $.fn.chosen != undefined )
        $('.pms-chosen').chosen( { search_contains: true } )



    /*
     * Change settings sub-tabs when clicking on navigation sub-tabs
     */
    $(document).ready( function() {

        $('.nav-sub-tab').click( function(e) {
            e.preventDefault();

            $navTab = $(this);
            $navTab.blur();

            $('.nav-sub-tab').removeClass('current');
            $navTab.addClass('current');

            // Update the http referer with the current tab info
            $_wp_http_referer = $('input[name=_wp_http_referer]');

            var _wp_http_referer = $_wp_http_referer.val();
            _wp_http_referer = pms_remove_query_arg( 'nav_sub_tab', _wp_http_referer );
            _wp_http_referer = pms_add_query_arg( 'message', 1, _wp_http_referer );
            $_wp_http_referer.val( pms_add_query_arg( 'nav_sub_tab', $navTab.data('sub-tab-slug'), _wp_http_referer ) );

            $('.cozmoslabs-sub-tab').removeClass('tab-active');
            $('.cozmoslabs-sub-tab[data-sub-tab-slug="' + $navTab.data('sub-tab-slug') + '"]').addClass('tab-active');

        });

        $('#scripts-on-specific-pages').on('change', function(){
            if ($(this).is(':checked') )
                $('.pms-scripts-on-specific-pages').show()
            else
                $('.pms-scripts-on-specific-pages').hide()
        })

        $('#functions-password-strength-checkbox').on('change', function(){
            if ($(this).is(':checked') )
                $('.functions-password-strength-checkbox').show()
            else
                $('.functions-password-strength-checkbox').hide()
        })

    });


    /*
     * Handle default payment gateways select options
     *
     */
    $activePaymetGateways = $('.pms-form-field-active-payment-gateways input[type=checkbox]');

    if( $activePaymetGateways.length > 0 ) {

        $(document).ready( function() {
            activateDefaultPaymentGatewayOptions();
        });

        $activePaymetGateways.click( function() {
            activateDefaultPaymentGatewayOptions();
        });

        /*
         * Activates the correct default payment gateway options in the select field
         * based on the active payment gateways
         *
         */
        function activateDefaultPaymentGatewayOptions() {
            var activeGateways = [];

            setTimeout( function() {

                $('.pms-form-field-active-payment-gateways input[type=checkbox]:checked').each( function() {
                    activeGateways.push( $(this).val() );
                });

                $('#default-payment-gateway').find('option').each( function() {
                    if( activeGateways.indexOf( $(this).val() ) == -1 )
                        $(this).attr('disabled', true);
                    else
                        $(this).attr('disabled', false);
                });

            }, 200 );
        }
    }


    /*
     * Position the Available tags div from the e-mail settings tab
     *
     */
    function positionAvailableTags() {
        $availableTags   = $('#pms-available-tags');
        $emailsTabs      = $('#pms-settings-emails');
        $formTabsWrapper = $emailsTabs.closest('form');

        if ( $emailsTabs.length > 0 ) {
            $availableTags.css( 'top', $formTabsWrapper.offset().top + 60 );
            $availableTags.css( 'left', $emailsTabs.closest('.wrap').offset().left + $formTabsWrapper.width() - 280 );
        }

    }


    /**
     * Open/Close the Available Tags List for each email
     */
    $(document).ready(function() {

        $('.cozmoslabs-tags-list-heading').on('click', function() {
            let tagsList = $(this).siblings('.cozmoslabs-tags-list');

            // Hide/Show Tags List
            if (tagsList.css('display') === 'none') {
                tagsList.css('display', 'grid');
            } else {
                tagsList.css('display', 'none');
            }

            // Mark the Heading if the Tags List is opened
            $(this).toggleClass('cozmoslabs-tags-list-open', tagsList.is(':visible'));
        });

        $('.cozmoslabs-tags-list input').click( function() {
            this.select();
        });


        $('.cozmoslabs-email-heading-wrap input').each( function () {
            checkEmailField(this);
        });


        if ( $('input[name="pms_emails_settings[admin_emails_on]"]').prop('checked') ) {
            $('.cozmoslabs-sub-tab-admin .cozmoslabs-email-heading-wrap input').prop('disabled', false);
        }
        else {
            $('.cozmoslabs-sub-tab-admin .cozmoslabs-email-heading-wrap input').prop('disabled', true);
        }

    });



    $(document).on( 'change', '#emails-admin-on', function () {
        let adminEmailField = $('.cozmoslabs-sub-tab-admin .cozmoslabs-email-heading-wrap input');
        if (this.checked) {
            adminEmailField.prop('disabled', false);
            adminEmailField.each( function () {
                checkEmailField(this);
            });

        }
        else {
            adminEmailField.prop('disabled', true);
            $('.cozmoslabs-sub-tab-admin .cozmoslabs-wysiwyg-container .cozmoslabs-form-field-wrapper').hide();
        }
    });

    $(document).on( 'change', '.cozmoslabs-email-heading-wrap input', function () {
        if (this.checked) {
            $(this).closest('.cozmoslabs-wysiwyg-container').find('.cozmoslabs-form-field-wrapper').show();
        }
        else {
            $(this).closest('.cozmoslabs-wysiwyg-container').find('.cozmoslabs-form-field-wrapper').hide();
        }
    });


    function checkEmailField(element) {
        if (element.checked) {
            $(element).closest('.cozmoslabs-wysiwyg-container').find('.cozmoslabs-form-field-wrapper').show();
        }
        else {
            $(element).closest('.cozmoslabs-wysiwyg-container').find('.cozmoslabs-form-field-wrapper').hide();
        }
    }

    $(document).on( 'change', '#recaptcha-site-v3', function ( e ) {
        let v2Inputs = $( '.recaptchav2-fields' );
        let v3Inputs = $( '.recaptchav3-fields' );

        if(e.target.checked === true) {
            v2Inputs.hide();
            v3Inputs.show();
        } else {
            v2Inputs.show();
            v3Inputs.hide();
        }
    });
});


/**
 *  PayPal IPN URL && Stripe Webhooks URL - Copy Button functionality
 *
 * */
jQuery( document ).ready(function(){
    jQuery('.paypal-connect__copy, .stripe-connect__copy').click(function (e) {
        e.preventDefault();

        var inputId = jQuery(this).data('id');
        var inputValue = jQuery('#' + inputId).val();

        navigator.clipboard.writeText(inputValue);

        jQuery(this).text('Copied!');

        var clickTarget = jQuery(this);

        setTimeout(function () {
            clickTarget.text('Copy');
        }, 2500);
    });
});


/**
 * Form Designs Feature --> Admin UI
 *
 *  - Activate new Design
 *  - Preview Modal
 *  - Modal Image Slider controls
 *
 * */

jQuery( document ).ready(function(){

    // Activate Design
    jQuery('.pms-forms-design-activate button.activate').click(function ( element ) {
        let themeID, i, allDesigns;

        themeID = jQuery(element.target).data('theme-id');

        jQuery('#pms-active-form-design').val(themeID);

        allDesigns = jQuery('.pms-forms-design');
        for (i = 0; i < allDesigns.length; i++) {
            if ( jQuery(allDesigns[i]).hasClass('active')) {
                jQuery('.pms-forms-design-title strong', allDesigns[i] ).hide();
                jQuery(allDesigns[i]).removeClass('active');
            }
        }
        jQuery('#pms-forms-design-browser .pms-forms-design#'+themeID).addClass('active');

    });

    jQuery('.pms-forms-design-preview').click(function (e) {
        let themeID = e.target.id.replace('-info', '');
        displayPreviewModal(themeID);
    });

    jQuery('.pms-slideshow-button').click(function (e) {
        let themeID = jQuery(e.target).data('theme-id'),
            direction = jQuery(e.target).data('slideshow-direction'),
            currentSlide = jQuery('#pms-modal-' + themeID + ' .pms-forms-design-preview-image.active'),
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
            let allImages = jQuery('.pms-forms-design-preview-image');

            allImages.each( function() {
                if ( jQuery(this).is(':first-child') && !jQuery(this).hasClass('active') ) {
                    jQuery(this).addClass('active');
                }
                else if ( !jQuery(this).is(':first-child') ) {
                    jQuery(this).removeClass('active');
                }
            });

            jQuery('.pms-forms-design-sildeshow-previous').addClass('disabled');
            jQuery('.pms-forms-design-sildeshow-next').removeClass('disabled');
        }
    });
    return false;
}

function nextSlide( currentSlide, themeID ){
    if ( currentSlide.next().length > 0 ) {
        currentSlide.removeClass('active');
        currentSlide.next().addClass('active');

        jQuery('#pms-modal-' + themeID + ' .pms-forms-design-sildeshow-previous').removeClass('disabled');

        if ( currentSlide.next().next().length <= 0 )
            jQuery('#pms-modal-' + themeID + ' .pms-forms-design-sildeshow-next').addClass('disabled');

    }
}

function previousSlide( currentSlide, themeID ){
    if ( currentSlide.prev().length > 0 ) {
        currentSlide.removeClass('active');
        currentSlide.prev().addClass('active');

        jQuery('#pms-modal-' + themeID + ' .pms-forms-design-sildeshow-next').removeClass('disabled');

        if ( currentSlide.prev().prev().length <= 0 )
            jQuery('#pms-modal-' + themeID + ' .pms-forms-design-sildeshow-previous').addClass('disabled');

    }
}