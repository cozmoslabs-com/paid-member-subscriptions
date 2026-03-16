jQuery( document ).on( 'change', '#pms-dashboard-stats-select', function(){

    let value = jQuery( this ).val()
    let nonce = jQuery( '#pms-dashboard-stats-select__nonce' ).val()

    // Show loading spinners only in the stat boxes that will be updated
    jQuery('.pms-dashboard-totals .pms-dashboard-box.earnings .value').html('<span class="spinner is-active" style="float: none; margin: 0;"></span>')
    jQuery('.pms-dashboard-totals .pms-dashboard-box.new_subscriptions .value').html('<span class="spinner is-active" style="float: none; margin: 0;"></span>')
    jQuery('.pms-dashboard-totals .pms-dashboard-box.new_paid_subscriptions .value').html('<span class="spinner is-active" style="float: none; margin: 0;"></span>')
    jQuery('.pms-dashboard-totals .pms-dashboard-box.payments_count .value').html('<span class="spinner is-active" style="float: none; margin: 0;"></span>')

    jQuery.post( ajaxurl, { action: 'get_dashboard_stats', interval: value, '_wpnonce': nonce }, function( response ) {

        response = JSON.parse( response )

        // if( response.data.earnings )
        if( response.data.earnings !== undefined && response.data.earnings !== null )
            jQuery('.pms-dashboard-totals .pms-dashboard-box.earnings .value').html( response.data.earnings )

        if( response.data.new_subscriptions )
            jQuery('.pms-dashboard-totals .pms-dashboard-box.new_subscriptions .value').html( response.data.new_subscriptions )

        if( response.data.new_paid_subscriptions )
            jQuery('.pms-dashboard-totals .pms-dashboard-box.new_paid_subscriptions .value').html( response.data.new_paid_subscriptions )

        if( response.data.payments_count )
            jQuery('.pms-dashboard-totals .pms-dashboard-box.payments_count .value').html( response.data.payments_count )

    });
});

// Handle Scheduled Payments stats select change
jQuery( document ).on( 'change', '#pms-dashboard-psp-stats-select', function(){

    let value = jQuery( this ).val()
    let nonce = jQuery( '#pms-dashboard-psp-stats-select__nonce' ).val()

    // Show loading spinners in the stat boxes that will be updated
    jQuery('.pms-dashboard-scheduled-payments .pms-dashboard-box.payments_count .value').html('<span class="spinner is-active" style="float: none; margin: 0;"></span>')
    jQuery('.pms-dashboard-scheduled-payments .pms-dashboard-box.revenue_total .value').html('<span class="spinner is-active" style="float: none; margin: 0;"></span>')

    jQuery.post( ajaxurl, { action: 'get_psp_stats', interval: value, '_wpnonce': nonce }, function( response ) {

        response = JSON.parse( response )

        if( response.data.payments_count !== undefined && response.data.payments_count !== null )
            jQuery('.pms-dashboard-scheduled-payments .pms-dashboard-box.payments_count .value').html( response.data.payments_count )

        if( response.data.revenue_total !== undefined && response.data.revenue_total !== null )
            jQuery('.pms-dashboard-scheduled-payments .pms-dashboard-box.revenue_total .value').html( response.data.revenue_total )

    });
});

// Function that copies the shortcode from a text
jQuery(document).ready(function() {
    jQuery('.pms-shortcode_copy-text').click(function (e) {
        e.preventDefault();

        navigator.clipboard.writeText(jQuery(this).text());

        // Show copy message
        var copyMessage = jQuery(this).next('.pms-copy-message');
        copyMessage.fadeIn(400).delay(2000).fadeOut(400);

    })
});

/*
   * Showing and closing the modal
   */

jQuery(document).on( 'click', '#pms-popup2', function(e) {
    e.preventDefault();
    jQuery( '.pms-modal' ).show();
    jQuery('.overlay').show();
});

jQuery(document).on( 'click', '.pms-button-close', function(e) {
    e.preventDefault();
    jQuery( '.pms-modal' ).hide();
    jQuery('.overlay').hide();
});

// Generic handler for dashboard issue buttons
jQuery(document).on('click', '.pms-dashboard-issue-button:not(.pms-issue-button--url)', function(e) {
    e.preventDefault();
    
    var $button = jQuery(this);
    var behavior = $button.data('behavior');
    
    if (behavior === 'ajax') {
        handleAjaxButton($button);
    } else if (behavior === 'dialog') {
        handleDialogButton($button);
    }
});

function handleAjaxButton($button) {
    var ajaxAction = $button.data('ajax-action');
    var nonce = $button.data('nonce');
    
    if (!ajaxAction || !nonce) {
        console.error('Missing AJAX action or nonce');
        return;
    }
    
    // Disable button during request
    $button.prop('disabled', true).css('opacity', '0.6');
    
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'pms_dashboard_issue_action',
            action_name: ajaxAction,
            _ajax_nonce: nonce
        },
        success: function(response) {
            if (response.success) {
                // Remove the issue from DOM
                $button.closest('.pms-dashboard-issue').fadeOut(400, function() {
                    jQuery(this).remove();
                    
                    // Check if any issues remain
                    if (jQuery('.pms-dashboard-issue').length === 0) {
                        jQuery('.pms-dashboard-issues').fadeOut();
                        
                        // Update health status box
                        var $healthStatusBox = jQuery('.pms-dashboard-glance__payments-status');
                        if ($healthStatusBox.length) {
                            // Update box wrapper classes
                            $healthStatusBox.removeClass('pms-payments-status-wrap--needs-attention')
                                           .addClass('pms-payments-status-wrap--healthy');
                            
                            // Update status indicator classes
                            $healthStatusBox.find('.pms-payments-status')
                                           .removeClass('pms-payments-status--needs-attention')
                                           .addClass('pms-payments-status--healthy');
                            
                            // Update text content (the text node after the status indicator)
                            var textNode = $healthStatusBox.contents().filter(function() {
                                return this.nodeType === 3; // Text node
                            }).last();
                            
                            if (textNode.length) {
                                textNode[0].nodeValue = 'Healthy';
                            }
                        }
                    }
                });
            } else {
                alert(response.data.message || 'An error occurred');
                $button.prop('disabled', false).css('opacity', '1');
            }
        },
        error: function() {
            alert('An error occurred while processing your request.');
            $button.prop('disabled', false).css('opacity', '1');
        }
    });
}

function handleDialogButton($button) {
    var dialogConfig = $button.data('dialog-config');
    
    if (!dialogConfig) {
        console.error('Missing dialog configuration');
        return;
    }
    
    // Create a temporary div for the dialog
    var dialogId = 'pms-temp-dialog-' + Date.now();
    var $dialog = jQuery('<div id="' + dialogId + '" style="display:none;"></div>');
    $dialog.html(dialogConfig.content || '');
    jQuery('body').append($dialog);
    
    // Build dialog buttons from config
    var dialogButtons = [];
    
    if (dialogConfig.buttons && Array.isArray(dialogConfig.buttons)) {
        dialogConfig.buttons.forEach(function(btnConfig) {
            var buttonDef = {
                text: btnConfig.text || 'OK',
                class: 'button ' + (btnConfig.type === 'primary' ? 'button-primary' : 'button-secondary')
            };
            
            if (btnConfig.behavior === 'url' && btnConfig.url) {
                buttonDef.click = function() {
                    window.open(btnConfig.url, btnConfig.target || '_self');
                    if (btnConfig.target !== '_blank') {
                        jQuery(this).dialog('close');
                    }
                };
            } else if (btnConfig.behavior === 'close') {
                buttonDef.click = function() {
                    jQuery(this).dialog('close');
                };
            }
            
            dialogButtons.push(buttonDef);
        });
    }
    
    // Initialize and show jQuery UI Dialog
    $dialog.dialog({
        title: dialogConfig.title || 'Information',
        dialogClass: 'wp-dialog',
        modal: true,
        width: 600,
        open: function() {},
        close: function() {
            // Clean up: remove the dialog from DOM when closed
            jQuery(this).dialog('destroy').remove();
        },
        buttons: dialogButtons
    });
}

//Hiding Setup Progress Review
jQuery(document).ready(function($) {
    $('#pms-dismiss-widget').on('click', function() {
        var $closeButton = $(this);
        var $widget = $closeButton.closest('.pms-dashboard-progress');
        var securityNonce = $closeButton.data('nonce');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pms_dismiss_setup_widget',
                nonce: securityNonce
            },
            success: function(response) {
                if (response.success) {
                    $widget.fadeOut();
                }
            }
        });
    });
});

