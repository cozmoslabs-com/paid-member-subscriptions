/**
 * Keeps the content restriction message editors usable on the taxonomy "Add New" term form.
 *
 * The Add New term form submits over AJAX and only serializes the textareas, so TinyMCE
 * content typed in visual mode never reaches the request. We keep each message editor
 * synced to its textarea on change, and clear the editors after a term is added.
 */
jQuery( function( $ ) {

    var prefix = 'pms-taxonomy-messages-';

    // sync each message editor to its textarea on change so the serialized form carries its content
    $( document ).on( 'tinymce-editor-init', function( event, editor ) {
        if ( editor.id.indexOf( prefix ) === 0 ) {
            editor.on( 'change keyup', function() {
                editor.save();
            } );
        }
    } );

    // reset the whole Content Restriction section after a term is added via AJAX, so the
    // next entry starts from defaults (matches how core clears the rest of the Add form)
    $( document ).ajaxSuccess( function( event, xhr, settings ) {
        if ( ! settings.data || settings.data.indexOf( 'action=add-tag' ) === -1 ) {
            return;
        }

        // only reset on a genuine success: the request returns HTTP 200 even on validation
        // errors (empty/duplicate name), so skip the reset when the response carries errors,
        // matching how core keeps the form filled on failure
        if ( typeof wpAjax === 'undefined' ) {
            return;
        }

        var res = wpAjax.parseAjaxResponse( xhr.responseXML, 'ajax-response' );

        if ( ! res || res.errors ) {
            return;
        }

        var $form = $( '#addtag' );

        // restriction type back to "Settings Default"
        $form.find( 'input[name="pms-content-restrict-type"][value="default"]' ).prop( 'checked', true );

        // uncheck user status, all plans and individual plans
        $form.find( '#pms-content-restrict-user-status, #pms-content-restrict-all-subscription-plans, [id^="pms-content-restrict-subscription-plan-"]' ).prop( 'checked', false );

        // reset the custom redirect URL toggle + fields
        $form.find( '#pms-content-restrict-custom-redirect-url-enabled' ).prop( 'checked', false );
        $form.find( '#pms-content-restrict-custom-redirect-url, #pms-content-restrict-custom-non-member-redirect-url' ).val( '' );

        // reset the custom messages toggle and clear the editors
        $form.find( '#pms-content-restrict-messages-enabled' ).prop( 'checked', false );

        if ( typeof tinymce !== 'undefined' ) {
            $.each( tinymce.editors, function( i, editor ) {
                if ( editor.id.indexOf( prefix ) === 0 ) {
                    editor.setContent( '' );
                    editor.save();
                }
            } );
        }

        // collapse the conditional sub-sections so they match the default (hidden) state
        $form.find( '#pms-meta-box-fields-wrapper-restriction-redirect-url, .pms-meta-box-field-wrapper-custom-redirect-url, .pms-meta-box-field-wrapper-custom-messages' ).removeClass( 'pms-enabled' );
    } );

} );
