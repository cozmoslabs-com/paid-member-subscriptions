jQuery(function ($) {
    window.pmsPrepareSubmitBox = function(options) {
        $('#delete-action').remove();
        $('.edit-post-status').remove();
        $('#visibility').remove();
        $('#minor-publishing-actions').remove();
        $('div.misc-pub-post-status').remove();
        $('#misc-publishing-actions').hide();
        $('#submitdiv .postbox-header .hndle, #submitdiv .postbox-header h2').text(options.headerText);
        $('#submitdiv').removeClass('closed');
        $('#submitdiv .inside').show();
        $('input#publish').val(options.publishLabel);

        if ( $('#pms-delete-action').length > 0 && $('#major-publishing-actions').length > 0 ) {
            $('#major-publishing-actions').append( $('#pms-delete-action') );
        }
    };
});

jQuery(function ($) {
    const docsLinkTitle = 'More info';

    $('a.pms-docs-link').attr('title', docsLinkTitle);

    $(document).on('mouseenter focus', 'a.pms-docs-link', function () {
        $(this).attr('title', docsLinkTitle);
    });
});

jQuery(function () {
    if (!jQuery.fn.dialog) {
        return;
    }

    const $docsLinkPopup = jQuery('#pms-docs-link-popup');

    if (!$docsLinkPopup.length) {
        return;
    }

    $docsLinkPopup.dialog({
        autoOpen: false,
        modal: true,
        draggable: false,
        resizable: false,
        width: 480,
        dialogClass: 'pms-docs-link-popup-dialog'
    });

    jQuery(document).on('click', 'a.pms-docs-link', function (e) {
        const docsUrl = jQuery(this).attr('href');

        if (!docsUrl) {
            return;
        }

        e.preventDefault();

        $docsLinkPopup.find('.pms-docs-link-popup-open-docs').attr('href', docsUrl);
        $docsLinkPopup.dialog('open');
    });

    $docsLinkPopup.on('click', '.pms-docs-link-popup-open-docs, .pms-docs-link-popup-open-wporg', function () {
        $docsLinkPopup.dialog('close');
    });
});
