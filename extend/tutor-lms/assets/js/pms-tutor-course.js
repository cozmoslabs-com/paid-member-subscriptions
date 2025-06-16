/**
 * Handle Content Restriction settings update for TutorLMS Course
 * - with the new TutorLMS Course Builder the Content Restriction default functionality is not working anymore
 *
 */
jQuery(document).ready(function() {
    jQuery('[data-cy="course-builder-submit-button"]').on('click', function() {

        // build Content Restriction settings data
        let data = {
            'action':                                              'pms_tutor_course_cr_data_save',
            'security':                                            pmsTutorCourse.nonce,
            'course_id':                                           pmsTutorCourse.course_id,
            'pmstkn':                                              jQuery('#pmstkn').val(),
            'pms-content-restrict-type':                           jQuery('input[name="pms-content-restrict-type"]:checked').val(),
            'pms-content-restrict-custom-redirect-url':            jQuery('#pms-content-restrict-custom-redirect-url').val(),
            'pms-content-restrict-custom-non-member-redirect-url': jQuery('#pms-content-restrict-custom-non-member-redirect-url').val(),
            'pms-content-restrict-message-logged_out':             (typeof tinyMCE !== 'undefined' && tinyMCE.get('messages_logged_out')) ? tinyMCE.get('messages_logged_out').getContent() : jQuery('#messages_logged_out').val(),
            'pms-content-restrict-message-non_members':            (typeof tinyMCE !== 'undefined' && tinyMCE.get('messages_non_members')) ? tinyMCE.get('messages_non_members').getContent() : jQuery('#messages_non_members').val()
        };

        // only add the following settings data if selected
        let userStatus = jQuery('#pms-content-restrict-user-status'),
            allPlans = jQuery('#pms-content-restrict-all-subscription-plans'),
            customRedirect = jQuery('#pms-content-restrict-custom-redirect-url-enabled'),
            customMessages = jQuery('#pms-content-restrict-messages-enabled'),
            selectedPlans = [];

        jQuery('input[name="pms-content-restrict-subscription-plan[]"]:checked').each(function() {
            selectedPlans.push(jQuery(this).val());
        });

        if (userStatus.length && userStatus.is(':checked') && userStatus.val())
            data['pms-content-restrict-user-status'] = userStatus.val();

        if (allPlans.length && allPlans.is(':checked') && allPlans.val())
            data['pms-content-restrict-all-subscription-plans'] = allPlans.val();

        if (selectedPlans.length > 0)
            data['pms-content-restrict-subscription-plan'] = selectedPlans;

        if (customRedirect.length && customRedirect.is(':checked') && customRedirect.val())
            data['pms-content-restrict-custom-redirect-url-enabled'] = customRedirect.val();

        if (customMessages.length && customMessages.is(':checked') && customMessages.val())
            data['pms-content-restrict-messages-enabled'] = customMessages.val();

        // handle Content Restrictions settings data
        jQuery.ajax({
                        url: pmsTutorCourse.ajax_url,
                        method: 'POST',
                        dataType: 'json',
                        data: data
                    });
    });
});
