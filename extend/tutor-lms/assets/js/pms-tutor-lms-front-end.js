/** JavaScript for  */

jQuery(document).ready(function() {

    jQuery('input.pms-subscription-plan:checked').each(function() {
        jQuery(this).closest('.pms-subscription-plan-container').addClass('selected-plan');
    });

    jQuery('input.pms-subscription-plan').change(function() {

        jQuery('input.pms-subscription-plan').each(function() {

            if (jQuery(this).is(':checked')) {
                jQuery(this).closest('.pms-subscription-plan-container').addClass('selected-plan');
            }
            else {
                jQuery(this).closest('.pms-subscription-plan-container').removeClass('selected-plan');
            }

        });
    });

});