jQuery( document ).on( 'change', '#pms-dashboard-stats-select', function(){

    let value = jQuery( this ).val()
    let nonce = jQuery( '#pms-dashboard-stats-select__nonce' ).val()

    jQuery.post( ajaxurl, { action: 'get_dashboard_stats', interval: value, '_wpnonce': nonce }, function( response ) {

        response = JSON.parse( response )

        // if( response.data.earnings )
        if( response.data.earnings !== undefined && response.data.earnings !== null )
            jQuery('.pms-dashboard-box.earnings .value').html( response.data.earnings )

        if( response.data.new_subscriptions )
            jQuery('.pms-dashboard-box.new_subscriptions .value').html( response.data.new_subscriptions )

        if( response.data.new_paid_subscriptions )
            jQuery('.pms-dashboard-box.new_paid_subscriptions .value').html( response.data.new_paid_subscriptions )

        if( response.data.payments_count )
            jQuery('.pms-dashboard-box.payments_count .value').html( response.data.payments_count )

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

