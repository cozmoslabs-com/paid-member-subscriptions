/** JavaScript for PMS Settings Tutor-LMS Tab */

jQuery(document).ready(function() {

    // Initial fields visibility
    updateVisibility();

    // Update visibility on Restriction Type change
    jQuery('#restriction-type').change(function(e) {
        updateVisibility();
    });

    // Update visibility on Access Type change
    jQuery('.access-type input[type="radio"]').change(function(e) {
        toggleSubscriptionPlans();
    });

});

function updateVisibility() {
    let restrictionType = jQuery('#restriction-type').val();

    if (restrictionType === 'full_courses') {
        jQuery('#full-course-description').show();
        jQuery('#tutor-lms-access-type').css( 'display', 'flex' );
        toggleSubscriptionPlans();
    } else {
        jQuery('#tutor-lms-access-type, #tutor-lms-subscription-plans, #full-course-description').hide();
    }

    if (restrictionType === 'category') {
        jQuery('#category-description').show();
        jQuery('#category-information').css( 'display', 'flex' );
    }
    else {
        jQuery('#category-description, #category-information').hide();
    }

    if (restrictionType === 'individual') {
        jQuery('#individual-description').show();
        jQuery('#tutor-lms-auto-enroll, #individual-information').css( 'display', 'flex' );
    }
    else {
        jQuery('#individual-description, #individual-information, #tutor-lms-auto-enroll').hide();
    }
}

function toggleSubscriptionPlans() {
    if (jQuery('#subscribed-member').is(':checked')) {
        jQuery('#tutor-lms-subscription-plans').css( 'display', 'flex' );
    } else {
        jQuery('#tutor-lms-subscription-plans').hide();
    }
}
