jQuery(function () {
    if (typeof jQuery.fn.dialog !== 'function') {
        return;
    }

    const $popup = jQuery('#pms-deactivation-popup');

    if ($popup.length === 0) {
        return;
    }

    const pluginBasename = $popup.data('plugin');
    let deactivateLink = '';

    $popup.dialog({
        autoOpen: false,
        modal: true,
        draggable: false,
        resizable: false,
        width: 480
    });

    jQuery(document).on('click', 'tr[data-plugin="' + pluginBasename + '"] .deactivate a', function (e) {
        e.preventDefault();
        e.stopPropagation();

        deactivateLink = jQuery(this).attr('href');
        $popup.dialog('open');
    });

    $popup.on('click', '.pms-deactivation-popup-confirm', function (e) {
        e.preventDefault();

        if (deactivateLink) {
            window.location.href = deactivateLink;
        }
    });

    $popup.on('click', '.pms-deactivation-popup-support', function () {
        $popup.dialog('close');
    });
});
