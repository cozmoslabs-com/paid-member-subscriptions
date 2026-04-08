jQuery(function () {
    if (typeof jQuery.fn.dialog !== 'function') {
        return;
    }

    const $popup = jQuery('#pms-deactivation-popup');
    const $form = $popup.find('.pms-deactivation-popup-form');
    const $error = $popup.find('.pms-deactivation-popup-error');
    const $actionButtons = $popup.find('.pms-deactivation-popup-confirm, .pms-deactivation-popup-skip');

    if ($popup.length === 0) {
        return;
    }

    const pluginBasename = $popup.data('plugin');
    let deactivateLink = '';
    let isRedirecting = false;

    $actionButtons.each(function () {
        const $btn = jQuery(this);
        $btn.data('pmsOriginalText', $btn.text().trim());
    });

    function resetActionButtons() {
        $actionButtons.each(function () {
            const $btn = jQuery(this);
            const original = $btn.data('pmsOriginalText');
            if (original !== undefined) {
                $btn.text(original);
            }
            $btn.prop('disabled', false);
        });
    }

    $popup.dialog({
        autoOpen: false,
        modal: true,
        draggable: false,
        resizable: false,
        width: 480
    });

    function setError(message, extraFieldName) {
        $popup.find('.pms-deactivation-popup-extra').removeClass('error');

        if (!message) {
            $error.hide().text('');
            return;
        }

        $error.text(message).show();

        if (extraFieldName) {
            $form.find('.pms-deactivation-popup-extra[name="' + extraFieldName + '"]').addClass('error');
        }
    }

    function toggleExtraFields() {
        const selectedReason = $form.find('input[name="pms_deactivation_reason"]:checked').val() || '';

        $form.find('.pms-deactivation-popup-extra').each(function () {
            const $input = jQuery(this);
            const shouldShow = $input.data('reason') === selectedReason;

            $input.toggle(shouldShow);

            if (!shouldShow) {
                $input.val('');
            }
        });
    }

    function getPayload(reasonOverride) {
        const payload = {
            action: 'pms_store_deactivation_reason',
            nonce: (window.pmsDeactivationData && window.pmsDeactivationData.deactivationReasonNonce) ? window.pmsDeactivationData.deactivationReasonNonce : '',
            reason: reasonOverride || ($form.find('input[name="pms_deactivation_reason"]:checked').val() || '')
        };

        $form.find('.pms-deactivation-popup-extra').each(function () {
            const $input = jQuery(this);

            if ($input.val()) {
                payload[$input.attr('name')] = $input.val();
            }
        });

        return payload;
    }

    function validatePayload(payload) {
        if (payload.reason === 'skip') {
            return true;
        }

        if (!payload.reason) {
            setError(window.pmsDeactivationData ? window.pmsDeactivationData.deactivationReasonRequired : '');
            return false;
        }

        if (payload.reason === 'switched_to_another_plugin' && !payload.switched_to_another_plugin_reason) {
            setError(window.pmsDeactivationData ? window.pmsDeactivationData.deactivationReasonInput : '', 'switched_to_another_plugin_reason');
            return false;
        }

        if (payload.reason === 'missing_features' && !payload.missing_features_reason) {
            setError(window.pmsDeactivationData ? window.pmsDeactivationData.deactivationReasonInput : '', 'missing_features_reason');
            return false;
        }

        if (payload.reason === 'other' && !payload.other_reason) {
            setError(window.pmsDeactivationData ? window.pmsDeactivationData.deactivationReasonInput : '', 'other_reason');
            return false;
        }

        setError('');
        return true;
    }

    function submitReason(payload, $triggerButton) {
        if (!validatePayload(payload) || !deactivateLink || isRedirecting) {
            return;
        }

        isRedirecting = true;

        if ($triggerButton && $triggerButton.length) {
            $triggerButton.text((window.pmsDeactivationData && window.pmsDeactivationData.deactivating) ? window.pmsDeactivationData.deactivating : 'Deactivating...');
        }
        $actionButtons.prop('disabled', true);

        jQuery.post(ajaxurl, payload)
            .done(function () {
                window.location.href = deactivateLink;
            })
            .fail(function () {
                isRedirecting = false;
                resetActionButtons();
                setError(window.pmsDeactivationData ? window.pmsDeactivationData.deactivationReasonSaveError : '');
            });
    }

    jQuery(document).on('click', 'tr[data-plugin="' + pluginBasename + '"] .deactivate a', function (e) {
        e.preventDefault();
        e.stopPropagation();

        deactivateLink = jQuery(this).attr('href');
        isRedirecting = false;
        resetActionButtons();
        $form[0].reset();
        toggleExtraFields();
        setError('');
        $popup.dialog('open');
    });

    $form.on('change', 'input[name="pms_deactivation_reason"]', function () {
        toggleExtraFields();
        setError('');
    });

    $popup.on('click', '.pms-deactivation-popup-confirm', function (e) {
        e.preventDefault();
        submitReason(getPayload(), jQuery(this));
    });

    $popup.on('click', '.pms-deactivation-popup-skip', function (e) {
        e.preventDefault();
        submitReason(getPayload('skip'), jQuery(this));
    });
});
