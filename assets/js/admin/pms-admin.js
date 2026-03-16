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
