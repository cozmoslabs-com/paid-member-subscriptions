/*
 * JavaScript for Subscription Plan cpt screen
 *
 */
jQuery( function($){

    /**
     * Adds a spinner after the element
     */
    $.fn.pms_addSpinner = function(){

        $this = $(this)

        if( $this.siblings('.spinner').length == 0 )
            $this.after('<div class="spinner"></div>')

        $spinner = $this.siblings('.spinner')
        $spinner.css('visibility', 'visible').animate({opacity: 1})

    }


    /**
     * Removes the spinners next to the element
     */
    $.fn.pms_removeSpinner = function(){

        if( $this.siblings('.spinner').length > 0 )
            $this.siblings('.spinner').remove()

    }

    $(document).ready( function(){

        if( $('#pms_gateway_paypal_standard').prop( 'checked' ) )
            $('.pms-setup-gateway-extra.paypal').css( 'display', 'flex' )

        if( $('#pms_gateway_stripe').prop( 'checked' ) )
            $('.pms-setup-gateway-extra.stripe').css( 'display', 'flex' )

        $('label[for="pms_gateway_paypal_standard"]').click( function(){
            var value = $('#pms_gateway_paypal_standard').prop( 'checked' )

            if( value === false )
                $('.pms-setup-gateway-extra.paypal').css( 'display', 'flex' )
            else
                $('.pms-setup-gateway-extra.paypal').css( 'display', 'none' )
        })

        $('label[for="pms_gateway_stripe"]').click( function(){
            var value = $('#pms_gateway_stripe').prop( 'checked' )

            if( value === false )
                $('.pms-setup-gateway-extra.stripe').css( 'display', 'flex' )
            else
                $('.pms-setup-gateway-extra.stripe').css( 'display', 'none' )
        })

        jQuery('.pms-forms-design-preview').click(function (e) {
            let themeID = e.target.id.replace('-info', '');
            displayPreviewModal(themeID);
        });

        jQuery('.pms-slideshow-button').click(function (e) {
            let themeID      = jQuery(e.target).data('theme-id'),
                direction    = jQuery(e.target).data('slideshow-direction'),
                currentSlide = jQuery('#pms-modal-' + themeID + ' .pms-forms-design-preview-image.active')

            if( direction == 'next' )
                nextSlide( currentSlide,themeID )
            else
                previousSlide( currentSlide,themeID )
        });

        jQuery('.pms-setup-newsletter__form a').on('click', function (e) {

            e.preventDefault()

            jQuery( '.pms-setup-newsletter__form input[name="email"]' ).removeClass( 'error' )

            var email = jQuery( '.pms-setup-newsletter__form input[name="email"]').val()

            if ( !validateEmail( email ) ){
                jQuery( '.pms-setup-newsletter__form input[name="email"]' ).addClass( 'error' )
                jQuery( '.pms-setup-newsletter__form input[name="email"]' ).focus()

                return
            }

            if( email != '' ){

                jQuery( '.pms-setup-newsletter__form a' ).html( 'Working...' )

                var data = new FormData()
                    data.append( 'email', email )

                jQuery.ajax({
                    url: 'https://www.cozmoslabs.com/wp-json/cozmos-api/subscribeEmailToNewsletter',
                    type: 'POST',
                    processData: false,
                    contentType: false,
                    data: data,
                    success: function (response) {

                        if( response.message ){

                            jQuery( '.pms-setup-newsletter__form input[name="email"]' ).removeClass( 'error' )
                            jQuery( '.pms-setup-newsletter__form' ).hide()
                            jQuery( '.pms-setup-newsletter__success' ).show()

                            var data = new FormData()
                                data.append( 'action', 'dismiss_newsletter_subscribe' )
                                data.append( 'pms_nonce', jQuery( '.pms-setup-newsletter #pms_nonce' ).val() )

                            jQuery.ajax({
                                url        : ajaxurl,
                                type       : 'POST',
                                processData: false,
                                contentType: false,
                                data       : data,
                                success    : function (response) {

                                },
                                error: function (response) {

                                }
                            })

                        }

                    },
                    error: function (response) {
                        
                        jQuery('.pms-setup-newsletter__form a').html('Sign me up!')

                    }
                })

            }

        })
    })

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

    function validateEmail(email) {

        const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());

    }

})


