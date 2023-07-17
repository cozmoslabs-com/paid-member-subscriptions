/*
 * JavaScript for Labels Edit Submenu Page
 *
 */
jQuery( document ).ready( function() {
    pmsle_chosen();
    pmsle_textarea_option();
    pmsle_edit();
} );

function pmsle_chosen() {
    jQuery( ".pmsle-label-select" ).chosen( {
        disable_search_threshold : 5,
        no_results_text          : "Nothing found!",
        width                    : "80%",
        search_contains          : true
    } );
}

function pmsle_textarea_option() {
    jQuery( document ).on( 'change', '.pmsle-label-select', function() {
        pmsle_textarea( jQuery( this ) );
    } );
}

function pmsle_textarea( $this ) {
    jQuery( '#pmsle-newlabel-textarea' ).text( $this.val() );
}

function pmsle_decode_html(str) {
    var map =
        {
            '&amp;': '&',
            '&lt;': '<',
            '&gt;': '>',
            '&quot;': '"',
            '&#039;': "'"
        };

    return str.replace(/&amp;|&lt;|&gt;|&quot;|&#039;/g, function (m) {
        return map[m];

    });
}

function pmsle_edit(){
    jQuery( jQuery( 'td[id^="pmsle-edit-item-"]' ) ).on( 'click', function() {

        var index = this.id.split('-');
        index = index[index.length - 1];

        var label = jQuery( '#pmsle-label-' + index ).html();
        var newlabel = jQuery( '#pmsle-newlabel-' + index ).html();

        var decoded_label = pmsle_decode_html( label );
        var decoded_newlabel = pmsle_decode_html( newlabel );

        jQuery( '.pmsle-label-select' ).val( decoded_label ).trigger('chosen:updated');
        jQuery( '#pmsle-newlabel-textarea' ).text( decoded_newlabel );

        window.scrollTo({ top: 0, behavior: 'smooth' });

        jQuery( '#pmsle-submit' ).val( pmsle_update_button_text.text );

    });
}