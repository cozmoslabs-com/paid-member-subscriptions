/*
 * JavaScript for Members Submenu Page
 *
 */
jQuery( function($) {

    /**
     * Adds a spinner after the element
     */
    $.fn.pms_addSpinner = function( animation_speed ) {

        if( typeof animation_speed == 'undefined' )
            animation_speed = 100;

        $this = $(this);

        if( $this.siblings('.spinner').length == 0 )
            $this.after('<div class="spinner"></div>');

        $spinner = $this.siblings('.spinner');
        $spinner.css('visibility', 'visible').animate({opacity: 1}, animation_speed );

    };


    /**
     * Removes the spinners next to the element
     */
    $.fn.pms_removeSpinner = function( animation_speed ) {

        if( typeof animation_speed == 'undefined' )
            animation_speed = 100;

        if( $this.siblings('.spinner').length > 0 ) {

            $spinner = $this.siblings('.spinner');
            $spinner.animate({opacity: 0}, animation_speed );

            setTimeout( function() {
                $spinner.remove();
            }, animation_speed );

        }

    };


    if( $.fn.chosen != undefined ) {

        $('.pms-chosen').chosen();

    }


    /*
     * Function that checks to see if any field from a row is empty
     *
     */
    function checkEmptyRow( $field_wrapper ) {

        is_field_empty = false;

        $field_wrapper.find('.pms-subscription-field').each( function() {

            $field = $(this);

            if( typeof $field.attr('required') == 'undefined' )
                return true;

            var field_value = $field.val().trim();

            if( $field.is('select') && field_value == 0 )
                field_value = '';

            if( field_value == '' ) {
                $field.addClass('pms-field-error');
                is_field_empty = true;
            } else {
                $field.removeClass('pms-field-error');
            }

        });

        return is_field_empty;

    }


    var validation_errors = [];

    /**
     * Displays any errors as an admin notice under the page's title
     *
     */
    function displayErrors() {

        if( validation_errors.length == 0 )
            return false;

        errors_output = '';
        for( var i = 0; i < validation_errors.length; i++ ) {
            errors_output += '<p>' + validation_errors[i] + '</p>';
        }

        if( $('.wrap h2').first().siblings('.pms-admin-notice').length > 0 ) {

            $('.wrap h2').first().siblings('.pms-admin-notice').html( errors_output );

        } else {
            $('.wrap h2').first().after( '<div class="error pms-admin-notice">' + errors_output + '</div>' )
        }

    }


    /**
     * Initialize datepicker
     *
     */
    $(document).on( 'focus', '.datepicker', function() {
        $(this).datepicker({ dateFormat: 'yy-mm-dd'});
    });


    /**
     * Populate the expiration date field when changing the subscription plan field
     * with the expiration date calculated from the duration of the subscription plan selected
     */
    $(document).on( 'change', '#pms-form-add-member-subscription select[name=subscription_plan_id]', function() {

        $subscriptionPlanSelect = $(this);
        $expirationDateInput    = $subscriptionPlanSelect.closest('.pms-meta-box-field-wrapper').siblings('.pms-meta-box-field-wrapper').find('input[name=expiration_date]');

        // Exit if no subscription plan was selected
        if( $subscriptionPlanSelect.val() == 0 )
            return false;

        // De-focus the subscription plan select
        $subscriptionPlanSelect.blur();

        // Add the spinner
        $expirationDateInput.pms_addSpinner( 200 );

        $expirationDateSpinner = $expirationDateInput.siblings('.spinner');
        $expirationDateSpinner.animate({opacity: 1}, 200);

        // Disable the datepicker
        $expirationDateInput.attr( 'disabled', true );

        // Show/Hide Group Name and Description Fields
        $.post( ajaxurl, { action: 'determine_subscription_type', subscription_plan_id: $subscriptionPlanSelect.val() }, function( response ) {
            if( response == 'group' ) {
                jQuery('.pms-group-memberships-field').css('display', 'flex');
                jQuery('#pms_group_name').attr('required', true);
            } else {
                jQuery('.pms-group-memberships-field').hide();
                jQuery('#pms_group_name').attr('required', false);
            }
        });

        // Get the expiration date and set it the expiration date field
        $.post( ajaxurl, { action: 'populate_expiration_date', subscription_plan_id: $subscriptionPlanSelect.val() }, function( response ) {

            // Populate expiration date field
            $expirationDateInput.val( response );

            // Remove spinner and enable the expiration date field
            $expirationDateInput.pms_removeSpinner( 100 );
            $expirationDateInput.attr( 'disabled', false).trigger('change');

        });

    });

    $('#pms-form-add-member-subscription select[name=subscription_plan_id]').trigger('change')


    /**
     * Shows / hides the payment gateway's extra fields when changing the payment gateway
     *
     */
    $(document).on( 'change', 'input[name=payment_gateway]', function() {

        /**
         * Display fields from Stripe gateway for Stripe Payment Intents
         */
        var value = $(this).val()

        $('#pms-meta-box-fields-wrapper-payment-gateways > div').hide();
        $('#pms-meta-box-fields-wrapper-payment-gateways > div[data-payment-gateway=' + value + ']').show();

    });

    $('input[name=payment_gateway]').trigger('change');


    /**
     * Selecting the username
     *
     */
    $(document).on( 'change', '#pms-member-username', function() {

        $select = $(this);

        if( $select.val().trim() == '' )
            return false;

        var user_id = $select.val().trim();

        $('#pms-member-user-id').val( user_id );

    });

    /**
     * Fired when an username is entered manually by the admin
     */
    $(document).on( 'change', '#pms-member-username-input', function() {

        $( '.pms-member-details-error' ).remove()

        if( $(this).val().trim() == '' )
            return

        $( '#pms-member-username-input' ).pms_addSpinner()

        $.post( ajaxurl, { action: 'check_username', username: $(this).val() }, function( response ) {

            if( response != 0 ) {

                $('#pms-member-user-id').val( response )
                $('#pms-member-username-input').pms_removeSpinner()

            } else {
                $('#pms-member-username-input').after('<span class="pms-member-details-error">Invalid username</span>')
                $('#pms-member-username-input').pms_removeSpinner()
            }

        });

    });


    /**
     * Validate empty fields
     *
     */
    $(document).on( 'click', '.pms-edit-subscription-details', function(e) {
        e.preventDefault();

        $button = $(this);

        if( !$button.hasClass('button-primary') )
            return false;

        $row = $button.parents('tr');

        is_field_empty = checkEmptyRow( $row );

        if( is_field_empty )
            $row.addClass('pms-field-error');
        else
            $row.removeClass('pms-field-error');

    });


    /*
     * Validate form before submitting
     *
     */
    $('.pms-form input[type=submit]').click( function(e) {

        var errors = false;
        validation_errors = [];

        // Check to see if the user id exists
        if( $('#pms-member-user-id').length > 0 && $('#pms-member-user-id').val().trim() == 0 ) {
            errors = true;
            validation_errors.push( 'Please select a user.' );
        }

        // If no subscription plan is to be found return
        if( $('#pms-member-subscription-details select[name=subscription_plan_id]').val() == 0 ) {
            errors = true;
            validation_errors.push( 'Please select a subscription plan.' );
        }


        // Check to see if any fields are left empty and return if so
        is_empty = false;
        $('#pms-member-subscription-details .pms-meta-box-field-wrapper').each( function() {
            if( checkEmptyRow( $(this) ) == true )
                is_empty = true;
        });

        if( is_empty ) {
            errors = true;
            validation_errors.push( 'Please fill all the required fields.' );
        }


        if( errors ) {
            displayErrors();
            return false;
        }

    });


    /**
     * When adding a new member subscription populate the member subscription data
     * when an admin selects the subscription plan.
     *
     */
    $(document).on( 'change', '#pms-form-add-edit-member-subscription select[name=subscription_plan_id]', function() {

        if( $('input[name=action]').val() != 'add_subscription' )
            return false;

        if( $(this).val() == 0 )
            return false;

        // Cache form elements
        $this        = $(this);
        $form        = $this.closest( 'form' );
        $form_fields = $form.find( 'input, select, textarea' );
        $spinner     = $this.siblings( '.spinner' );

        // Disable all fields
        $form_fields.attr( 'disabled', true );
        $spinner.css( 'visibility', 'visible' );


        $.post( ajaxurl, { action: 'populate_member_subscription_fields', subscription_plan_id: $this.val() }, function( response ) {

            if( response != 0 ) {

                fields = JSON.parse( response );

                // Populate fields with returned values
                for( var key in fields ) {

                    $field = $form.find('[name=' + key + ']');

                    if( $field.is( 'select' ) ) {
                        $field.find( 'option[value=' + fields[key] + ']' ).attr( 'selected', true );
                    }

                    if( $field.is( 'input' ) ) {
                        $field.val( fields[key] );
                    }

                }

                // Re-enable all fields
                $form_fields.attr( 'disabled', false );
                $spinner.css( 'visibility', 'hidden' );

            }

        });

    });

    // Add log entry manually
    $(document).on( 'click', '#pms_add_log_entry', function(e) {
        e.preventDefault()
        e.stopImmediatePropagation();
        pms_add_log_entry()
    });

    $(document).on('keypress', 'input', function (e) {
        if (e.which == 13 && document.activeElement && document.activeElement.name == 'pms_admin_log' ) {
            e.preventDefault();
            e.stopImmediatePropagation();
            pms_add_log_entry()
        }
    });

    function pms_add_log_entry(){
        var subscription_id = jQuery('#pms-member-subscription-logs input[name="pms_subscription_id"]').val(),
            log             = jQuery('#pms-member-subscription-logs input[name="pms_admin_log"]').val(),
            nonce           = jQuery('#pms-member-subscription-logs input[name="pms_nonce"]').val()

        if( subscription_id && log ){
            jQuery('#pms_add_log_entry').pms_addSpinner( 200 )
            jQuery('#pms_add_log_entry').attr( 'disabled', true )

            $.post( ajaxurl, {
                action: 'add_log_entry',
                nonce: nonce,
                subscription_id: subscription_id,
                log: log }, function( response ) {

                    response = JSON.parse( response )

                    if( response.status && response.status == 'success' )
                        jQuery('#pms-member-subscription-logs .pms-logs-holder' ).html( response.data )

                jQuery('#pms-member-subscription-logs input[name="pms_admin_log"]').val('')

                jQuery('#pms_add_log_entry').attr( 'disabled', false )

                jQuery('#pms_add_log_entry').pms_removeSpinner( 200 )

            })
        }
    }

    // Billing Details
    $(document).on( 'click', '#pms-member-billing-details #edit', function(e) {
        e.preventDefault()

        $('#pms-member-billing-details .billing-details').hide()
        $('#pms-member-billing-details .form').show()

        if( !PMS_States )
            return

        pms_handle_billing_state_field_display()

        if( $.fn.chosen != undefined ){
            $('#pms-member-billing-details .form #pms_billing_country').chosen( { search_contains: true } )

            if( $('#pms-member-billing-details .form #pms_billing_state option').length > 0 )
                $('#pms-member-billing-details .form #pms_billing_state').chosen( { search_contains: true } )
        }

    })

    $(document).on( 'change', '#pms_billing_country', function() {

        pms_handle_billing_state_field_display()

    })

    $(document).on( 'click', '#pms-member-billing-details #save', function(e) {
        e.preventDefault()

        jQuery(this).pms_addSpinner( 200 )

        if( !pms_billing_details || !pms_billing_details.fields )
            return;

        var data = {}
            data.action     = 'pms_edit_member_billing_details'
            data.security   = pms_billing_details.edit_member_details_nonce
            data.member_id  = jQuery( 'input[name=pms_member_id]' ).val()

        pms_billing_details.fields.forEach( function( field ){
            data[field] = jQuery( 'input[name=' + field + ']' ).val()
        })

        if( jQuery( 'select[name=pms_billing_country]' ).length > 0 )
            data.pms_billing_country = jQuery( 'select[name=pms_billing_country]' ).val()

        if( PMS_States && PMS_States[data.pms_billing_country] )
            data.pms_billing_state = jQuery( '.pms-billing-state__select' ).val()

        $.post( ajaxurl, data, function( response ){

            response = JSON.parse( response )

            if( response.status && response.status == 'success' && response.address_output ){

                jQuery('#pms-member-billing-details .billing-details .billing-details__data').html( response.address_output )

                jQuery( '.billing-details__action span' ).show().fadeOut( 3500 )

            }
        })

        jQuery(this).pms_removeSpinner( 200 )

        $('#pms-member-billing-details .form').hide()
        $('#pms-member-billing-details .billing-details').show()

    })

    function pms_handle_billing_state_field_display(){

        var country = $('#pms-member-billing-details .form #pms_billing_country').val()

        if( PMS_States[country] ){

            if( $.fn.chosen != undefined )
                $('.pms-billing-state__select').chosen('destroy')

            $('.pms-billing-state__select option').remove()
            $('.pms-billing-state__select').append('<option value=""></option>');

            for( var key in PMS_States[country] ){
                if( PMS_States[country].hasOwnProperty(key) )
                    $('.pms-billing-state__select').append('<option value="'+ key +'">'+ PMS_States[country][key] +'</option>');
            }

            var prevValue = $('.pms-billing-state__input').val()

            if( prevValue != '' )
                $('.pms-billing-state__select').val( prevValue )

            $('.pms-billing-state__input').removeAttr('name').removeAttr('id').hide()
            $('.pms-billing-state__select').attr('name','pms_billing_state').attr('id','pms_billing_state').show()

            if( $.fn.chosen != undefined )
                $('#pms-member-billing-details .form .pms-billing-state__select').chosen( { search_contains: true } )

        } else {

            if( $.fn.chosen != undefined )
                $('.pms-billing-state__select').chosen('destroy')

            $('.pms-billing-state__select').removeAttr('name').removeAttr('id').hide()
            $('.pms-billing-state__input').attr('name','pms_billing_state').attr('id','pms_billing_state').show()

        }

    }

    // Display confirmation prompt on bulk delete members
    $(document).off( 'click', '#doaction' ).on( 'click', '#doaction', function(e){
        message = pms_confirmation_message.message.split("\\n").join("\n");
        if ( $('#bulk-action-selector-top').val() == 'pms-delete-subscriptions' || $('#bulk-action-selector-bottom').val() == 'pms-delete-subscriptions' ){
            return confirm(message);
        }

    });

    $(document).off( 'click', '#doaction2' ).on( 'click', '#doaction2', function(e){
        message = pms_confirmation_message.message.split("\\n").join("\n");
        if ( $('#bulk-action-selector-top').val() == 'pms-delete-subscriptions' || $('#bulk-action-selector-bottom').val() == 'pms-delete-subscriptions' ){
            return confirm(message);
        }

    });


    //Handle the display of datepicker when Custom intervals are selected on Members page
    $('#pms-start-date-interval').hide();
    $('#pms-expiration-date-interval').hide();
    if( $('#pms-filter-start-date').val() == 'custom' ){
        $('#pms-start-date-interval').show();
    }
    if( $('#pms-filter-expiration-date').val() == 'custom' ){
        $('#pms-expiration-date-interval').show();
    }

    $('#pms-filter-start-date').change(function(e){
        if( $('#pms-filter-start-date').val() == 'custom' ){
            $('#pms-start-date-interval').show();
        }
        else{
            $('#pms-start-date-interval').hide();
        }
    });
    $('#pms-filter-expiration-date').change(function(e){
        if( $('#pms-filter-expiration-date').val() == 'custom' ){
            $('#pms-expiration-date-interval').show();
        }
        else{
            $('#pms-expiration-date-interval').hide();
        }
    });

});


/**
 * Reposition the Publish Box/Button in Admin Dashboard --> PMS CPTs & Custom Pages
 */

jQuery( document ).ready(setTimeout(function () {
    let smallMediumScreen  = window.matchMedia("(max-width: 1401px)"),
        largeScreen  = window.matchMedia("(min-width: 1402px)"),
        pageBody = jQuery('body');

    if (pageBody.is('[class*="post-type-pms"]')) {
        if (smallMediumScreen.matches)
            pmsRepositionCptPublishButton();
        else pmsRepositionCptPublishBox();
    }
    else if (pageBody.is('[class*="paid-member-subscriptions_page"]')) {
        if (largeScreen.matches)
            pmsRepositionPagePublishBox();
        else pmsRepositionPagePublishButton();
    }

}, 1000));

function pmsRepositionCptPublishBox() {
    let buttonWrapperContainer = jQuery('#side-sortables'),
        containerOffsetTop = buttonWrapperContainer.length > 0 ? buttonWrapperContainer.offset().top : 0;

    // set initial position
    pmsSetCptPublishBoxPosition();

    // reposition on scroll
    jQuery(window).on('scroll', function() {
        pmsSetCptPublishBoxPosition();
    });

    // position the Publish Box
    function pmsSetCptPublishBoxPosition() {
        if ( jQuery(window).scrollTop() >= (containerOffsetTop - 32) )
            buttonWrapperContainer.addClass('cozmoslabs-publish-metabox-fixed');
        else buttonWrapperContainer.removeClass('cozmoslabs-publish-metabox-fixed');
    }
}

function pmsRepositionCptPublishButton() {
    let buttonWrapper = jQuery('#side-sortables #submitdiv');

    if ( buttonWrapper.length > 0 ) {
        // set initial position
        pmsSetCptPublishButtonPosition();

        // reposition on scroll
        jQuery(window).on('scroll', function() {
            pmsSetCptPublishButtonPosition();
        });
    }

    // position the Publish Button
    function pmsSetCptPublishButtonPosition() {
        let button = jQuery('#side-sortables #submitdiv input[type="submit"]'),
            buttonWrapperContainer = jQuery('#side-sortables');

        if (pmsElementInViewport(buttonWrapper)) {
            buttonWrapperContainer.removeClass('cozmoslabs-publish-button-fixed');

            button.css({
                'max-width': 'unset',
                'left': 'unset',
            });
        } else {
            let containerOffsetLeft = buttonWrapper.offset().left;

            buttonWrapperContainer.addClass('cozmoslabs-publish-button-fixed');

            button.css({
                'max-width': buttonWrapper.outerWidth() + 'px',
                'left': containerOffsetLeft + 'px',
            });
        }
    }
}

/**
 *  Reposition Publish Box (large screens)
 *
 * */
function pmsRepositionPagePublishBox() {
    let topBox = jQuery('#pms-member-details, .cozmoslabs-wrap .cozmoslabs-nav-tab-wrapper'),
        buttonWrapper = jQuery('.cozmoslabs-wrap div.submit');

    if ( topBox.length > 0 && buttonWrapper.length > 0 ) {

        let cozmoslabsWrapper = jQuery('.cozmoslabs-wrap');

        cozmoslabsWrapper.addClass('cozmoslabs-publish-box-fixed');

        let bannerHeight = jQuery('.cozmoslabs-banner').outerHeight(),
            topBoxOffsetTop = topBox.offset().top;

        buttonWrapper.css({
            'top': topBoxOffsetTop - bannerHeight - 62 + 'px',  // 32px is the admin bar height + 30px cozmoslabs-wrap margin top
        });

        let cozmoslabsWrapperWidth = cozmoslabsWrapper.outerWidth();

        if (cozmoslabsWrapperWidth < 1200)
            cozmoslabsWrapper.css({
                'margin': '30px 10px',
            });

        // set initial position
        pmsSetPagePublishBoxPosition();

        // reposition on scroll
        jQuery(window).scroll(function () {
            pmsSetPagePublishBoxPosition();
        });

        // position the Publish Box
        function pmsSetPagePublishBoxPosition() {
            let distanceToTop = pmsCalculateDistanceToTop(topBox);

            if (distanceToTop < 50) {
                let buttonOffsetLeft = buttonWrapper.offset().left;

                buttonWrapper.css({
                    'position': 'fixed',
                    'top': '50px',
                    'left': buttonOffsetLeft,
                    'box-shadow': '0 3px 10px 0 #aaa',
                });
            } else {
                buttonWrapper.css({
                    'position': 'absolute',
                    'top': topBoxOffsetTop - bannerHeight - 62 + 'px', // 32px is the admin bar height + 30px cozmoslabs-wrap margin top
                    'left': 'auto',
                    'box-shadow': 'none',
                });
            }
        }
    }

}


/**
 *  Reposition Publish Button (small/medium screens)
 *
 * */
function pmsRepositionPagePublishButton() {
    let buttonWrapper = jQuery('.cozmoslabs-wrap div.submit'),
        button = jQuery('.cozmoslabs-wrap div.submit input[type="submit"]'),
        cozmoslabsWrapper = jQuery('.cozmoslabs-wrap');

    if ( buttonWrapper.length > 0 ) {
        // set initial position
        pmsSetPagePublishButtonPosition();

        // reposition on scroll
        jQuery(window).on('scroll', function() {
            pmsSetPagePublishButtonPosition();
        });
    }

    // position the Publish Button
    function pmsSetPagePublishButtonPosition() {
        if (pmsElementInViewport(buttonWrapper)) {
            cozmoslabsWrapper.removeClass('cozmoslabs-publish-button-fixed');

            button.css({
                'max-width': 'unset',
                'margin-left': 'unset',
            });
        } else {
            cozmoslabsWrapper.addClass('cozmoslabs-publish-button-fixed');

            button.css({
                'max-width': buttonWrapper.outerWidth() + 'px',
                'margin-left': '-10px',
            });
        }
    }
}


/**
 *  Calculate the distance to Top for a specific element
 *
 * */
function pmsCalculateDistanceToTop(element) {
    let scrollTop = jQuery(window).scrollTop(),
        elementOffset = element.offset().top;

    return elementOffset - scrollTop;
}


/**
 *  Check if a specific element is visible on screen
 *
 * */
function pmsElementInViewport(element) {
    let elementTop = element.offset().top,
        elementBottom = elementTop + element.outerHeight(),
        viewportTop = jQuery(window).scrollTop(),
        viewportBottom = viewportTop + jQuery(window).height();

    return elementBottom > viewportTop && elementTop < viewportBottom;
}


/**
 *  Set PMS Tables content width on smaller screens
 *
 * */
jQuery( document ).ready(function(){
    let tableElementWrapper = jQuery('body[class*="post-type-pms-"] .wp-list-table'),
        tableElement = jQuery('body[class*="post-type-pms-"] .wp-list-table tbody'),
        smallScreen  = window.matchMedia("(max-width: 782px)");

    if (tableElement.length > 0 && smallScreen.matches) {
        tableElement.css({
            'width': tableElementWrapper.outerWidth() - 2 + 'px',
        });
    }

});


/**
 *  Display initially hidden admin notices, after the scripts have loaded
 *
 * */
jQuery( document ).ready(function(){

    let noticeTypes = [
        ".error",
        ".notice"
    ];

    noticeTypes.forEach(function(notice){
        let selector = "body[class*='paid-member-subscriptions_page_'] " + notice + ", " + "body[class*='post-type-pms-'] " + notice;

        jQuery(selector).each(function () {
            jQuery(this).css('display', 'block');
        });
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