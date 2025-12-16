/*
 * JavaScript for Payments Submenu Page
 *
 */
jQuery( function($) {

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

        $.post( ajaxurl, { action: 'check_payment_username', username: $(this).val() }, function( response ) {

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
     * Initialize datepicker
     */
    $(document).on( 'focus', '.datepicker', function() {
        $(this).datepicker({
            dateFormat : 'yy-mm-dd',

            // Maintain the Time when switching dates
            onSelect   : function( dateText, inst ) {

                date = inst.lastVal.split(" ");
                dateTime = ( date[1] ? date[1] : '' );

                $(this).val( dateText + " " + dateTime );

            }

        });
    });

    /**
     * Chosen
     */
    if( $.fn.chosen != undefined ) {

        $('.pms-chosen').chosen({ search_contains: true });

    }


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


    /**
     * Automatically populate the subscription price based on selected subscription when adding a new payment
     * with the expiration date calculated from the duration of the subscription plan selected
     */
    $(document).on('change', '#pms-form-add-payment select[name=pms-payment-subscription-id]', function() {

        $subscriptionSelect = $(this);
        $amountInput = $('#pms-form-add-payment input[name=pms-payment-amount]');

        if ( $subscriptionSelect.val() == 0 )
            return false;

        // De-focus the subscription plan select
        $subscriptionSelect.blur();

        // Add the spinner
        $amountInput.pms_addSpinner( 200 );

        $amountInputSpinner = $amountInput.siblings('.spinner');
        $amountInputSpinner.animate({opacity: 1}, 200);

        // Disable the amount input
        $amountInput.attr( 'disabled', true );

        // Get the subscription plan price and populate the Amount field
        $.post( ajaxurl, { action: 'populate_subscription_price', subscription_plan_id: $subscriptionSelect.val() }, function( response ) {

            // Populate the amount field
            $amountInput.val( response );

            // Remove spinner and enable the amount field
            $amountInput.pms_removeSpinner( 100 );
            $amountInput.attr( 'disabled', false).trigger('change');

        });

    });


    $(document).on( 'click', '#payment-log-details', function() {
        var row = $(this).closest( 'tr' )

        $( '.pms-modal__holder' ).html( '<a class="pms-modal__close"></a>' )
        $( '.pms-modal__holder' ).append( $( 'td.column-modal_data', row ).html() )
        $( '.pms-modal' ).show()
    });

    $(document).on( 'click', '.pms-modal__close', function() {
        $( '.pms-modal' ).hide()
    });

    // Display confirmation prompt on bulk delete payments
    $(document).off( 'click', '#doaction' ).on( 'click', '#doaction', function(e){
        message = pms_delete_payments_confirmation_message.message.split("\\n").join("\n");
        if ( $('#bulk-action-selector-top').val() == 'pms_bulk_delete_payments' || $('#bulk-action-selector-bottom').val() == 'pms_bulk_delete_payments' ){
            return confirm(message);
        }
    });

    $(document).off( 'click', '#doaction2' ).on( 'click', '#doaction2', function(e){
        message = pms_delete_payments_confirmation_message.message.split("\\n").join("\n");
        if ( $('#bulk-action-selector-top').val() == 'pms_bulk_delete_payments' || $('#bulk-action-selector-bottom').val() == 'pms_bulk_delete_payments' ){
            return confirm(message);
        }
    });

    // Handle the display of datepicker when Custom intervals are selected on Members page
    if( $('#pms-filter-date').val() == 'custom' )
        $('#pms-date-interval').show();

    $('#pms-filter-date').change(function(e){
        if( $('#pms-filter-date').val() == 'custom' )
            $('#pms-date-interval').show();
        else
            $('#pms-date-interval').hide();
    });

    $('#pms-filter-date, #pms-filter-subscription-plan, #pms-filter-payment-type, #pms-filter-payment-gateway').change(function(e){
        $('#pms-filter-clear-filters').css('visibility', 'visible');
    });


    /**
     * Handle payment refund modal
     * - render and display modal
     * - submit modal form
     */

    $(document).off('click.pms_refund', '.pms-refund-payment').on('click.pms_refund', '.pms-refund-payment', function(event) {
        event.preventDefault();

        if( $(event.target).hasClass('pms-refund-payment') ) {
            let target = $(event.target).closest('#payment-actions')
            $(target).append('<div class="spinner" style="visibility: visible; float:none ; margin-top: 0px;"></div>');
        } else {
            let target = $(event.target).closest('.row-actions')
            $(target).before('<div class="spinner" style="visibility: visible; float:none ; margin-top: 0px;"></div>');
        }


        // Handle the refund modal for a specific payment
        renderPaymentRefundModal( jQuery(this).data('payment-id') );
    });

    jQuery(document).off('submit.pms_refund_form', '#pms-payment-refund-form').on('submit.pms_refund_form', '#pms-payment-refund-form', function (submitEvent) {
        submitEvent.preventDefault();

        let target = $('.pms-modal__button-group', submitEvent.target)

        $(target).append('<div class="spinner" style="visibility: visible; float:none ; margin: 0px !important;"></div>');

        // Handle payment refund process
        handlePaymentRefundProcess( new FormData(this) );
    });

});

/**
 * Render and display the Payment Refund modal
 *
 * @param paymentID - the ID of the selected Payment
 */
function renderPaymentRefundModal( paymentID ){

    let data = {
        action: 'render_modal_payment_refund',
        pms_payment_id: paymentID
    }

    jQuery.post(ajaxurl, data, function(response) {

        if (response.success) {

            // Remove existing modal
            jQuery('#pms-modal-payment-refund').remove();

            // Append the rendered modal
            jQuery('body').append(response.data.html);

            // Display the modal
            displayRefundModal();

            jQuery('.spinner').remove();

        } else {
            alert(response.data.message || 'Error loading modal.');

            jQuery('.spinner').remove();
        }

    });
}

/**
 * Display the Payment Refund modal
 *
 * @returns {boolean}
 */
function displayRefundModal() {

    jQuery('#pms-modal-payment-refund').dialog({
        resizable    : false,
        height       : 'auto',
        width        : 800,
        modal        : true,
        closeOnEscape: true,
        open         : function () {
            jQuery('.ui-widget-overlay, .pms-modal__cancel').bind('click',function () {
            jQuery('#pms-modal-payment-refund').dialog('close');
            })
        },
        close: function () {}
    });

    return false;
}

/**
 * Handle payment refund process
 *
 * @param refundData
 */
function handlePaymentRefundProcess( refundData ) {

    // Disable modal buttons until AJAX finishes
    jQuery('.pms-modal__button-group button').prop('disabled', true).addClass('disabled');

    refundData.append('action', 'pms_process_refund');

    jQuery.ajax({
        url: ajaxurl,
        method: 'POST',
        data: refundData,
        processData: false,
        contentType: false,

        success: function (response) {

            // Enable modal buttons after AJAX finishes
            jQuery('.pms-modal__button-group button').prop('disabled', false).removeClass('disabled');

            jQuery('.spinner').remove();

            // Close the modal
            jQuery('#pms-modal-payment-refund').dialog('close');

            if (response.success) {

                // Update UI elements after refund
                updatePaymentUI( response );

            } else {

                // Display admin notice
                if ( response.data && response.data.message )
                    displayRefundNotice( response.data.message, 'error' );
                else
                    displayRefundNotice( 'Payment refund failed!', 'error' );

            }
        },

        error: function (response) {

            // Display admin notice
            if ( response.data && response.data.message )
                displayRefundNotice( response.data.message, 'error' );
            else
                displayRefundNotice( 'Payment refund failed!', 'error' );

        }

    });

}

/**
 * Update payment UI after successful refund
 * - hide payment action buttons (refund, download invoice)
 * - update payment status
 *
 * @param response - payment refund success response
 */
function updatePaymentUI ( response ) {
    let paymentID = response.data.payment_id,
        listPayment = jQuery('.row-actions .refund .pms-refund-payment[data-payment-id="' + paymentID + '"]'), // Refund link in payments list
        singlePayment = jQuery('#payment-actions .pms-refund-payment[data-payment-id="' + paymentID + '"]'), // Refund button in single payment view
        downloadInvoice = jQuery('.pms-download-invoice[data-payment-id="' + paymentID + '"]'); // Download invoice link/button

    // Detect view and update the UI accordingly
    if ( listPayment.length > 0 ) {

        // List table view: hide refund and download invoice actions
        listPayment.parent().hide();
        downloadInvoice.parent().hide();

        let statusDot = jQuery('span.pms-status-dot[data-payment-id="' + paymentID + '"]');

        // Update the status dot and label text
        if ( statusDot.length > 0 ) {
            statusDot.removeClass().addClass('pms-status-dot refunded');
            statusDot[0].nextSibling.nodeValue = 'Refunded';
        }

        // Display admin notice
        if ( response.data && response.data.message )
            displayRefundNotice( response.data.message, 'success' );
        else
            displayRefundNotice( 'Payment refunded successfully!', 'error' );
    }
    else if ( singlePayment.length > 0 ) {
        let currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('pms_payment_refund', 'success');

        // Reload the page to display the correct UI data
        window.location.href = currentUrl.toString();
    }
}

/**
 * Handle the admin notice after the refund intent
 *
 * @param message - refund response message
 * @param type - admin notice type: success | error
 */
function displayRefundNotice( message, type ) {

    // Display admin notice
    jQuery('.wrap').prepend('<div id="pms-payment-refund" class="pms-notice notice notice-' + type + ' is-dismissible"><p>' + message + '</p><span class="notice-dismiss"></span></div>');

    // Scroll to admin notice
    jQuery('html').animate({
        scrollTop: jQuery('#pms-payment-refund').offset().top - 50
    }, 300);

    // Hide admin notice when close button is clicked
    jQuery('#pms-payment-refund .notice-dismiss').on( 'click', function ( event ) {
        jQuery('#pms-payment-refund').hide();
    } );

}

/**
 * Copy gift activation link to clipboard
 */
jQuery(document).on('click', '.pms-copy-activation-link', function(e) {
    e.preventDefault();
    
    var button = jQuery(this);
    var targetInput = jQuery(button.data('clipboard-target'));
    
    if (targetInput.length === 0) {
        return;
    }
    
    // Select the input text
    targetInput[0].select();
    targetInput[0].setSelectionRange(0, 99999); // For mobile devices
    
    // Copy to clipboard
    try {
        // Try modern clipboard API first
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(targetInput.val()).then(function() {
                showCopyFeedback(button);
            }).catch(function() {
                // Fallback to execCommand if clipboard API fails
                fallbackCopyToClipboard(targetInput, button);
            });
        } else {
            // Use fallback for older browsers
            fallbackCopyToClipboard(targetInput, button);
        }
    } catch (err) {
        console.error('Failed to copy text: ', err);
    }
});

/**
 * Fallback copy method using execCommand
 */
function fallbackCopyToClipboard(targetInput, button) {
    try {
        var successful = document.execCommand('copy');
        if (successful) {
            showCopyFeedback(button);
        }
    } catch (err) {
        console.error('Fallback: Failed to copy text: ', err);
    }
}

/**
 * Show visual feedback when link is copied
 */
function showCopyFeedback(button) {
    var originalText = button.text();
    
    // Change button text temporarily
    button.text('Copied!');
    button.addClass('pms-copied');
    
    // Reset after 2 seconds
    setTimeout(function() {
        button.text(originalText);
        button.removeClass('pms-copied');
    }, 2000);
}