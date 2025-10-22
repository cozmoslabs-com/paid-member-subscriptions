/*
 * JavaScript for Discount Codes cpt screen
 *
 */
jQuery( function($) {

    /*
     * When publishing or updating the Discount Code must have a title
     *
     */
    $(document).on( 'click', '#publish, #save-post', function() {

        var discountCodeTitle = $('#title').val().trim();

        if( discountCodeTitle == '' ) {

            alert( 'Discount code must have a name.' );

            return false;

        }

    });


    /*
     * Date picker for discount start and expiration date
     * Remove the default "Move to Trash button"
     * Remove the "Edit" link for Discount Code status
     * Remove the "Visibility" box for discount codes
     * Remove the "Save Draft" button
     * Remove the "Status" div
     * Remove the "Published on.." section
     * Rename metabox "Save Discount Code"
     * Change "Publish" button to "Save discount"
     */
    $(document).ready( function() {
        $("input.pms_datepicker").datepicker({dateFormat: 'yy-mm-dd'});
        $('#delete-action').remove();
        $('.edit-post-status').remove();
        $('#visibility').remove();
        $('#minor-publishing-actions').remove();
        $('div.misc-pub-post-status').remove();
        $('#misc-publishing-actions').hide();
        $('#submitdiv h3 span').html('Save Discount Code');
        $('input#publish').val('Save discount');

        // Select discount code on click
        jQuery('.pms-discount-code').click( function() {
            this.select();
        });

        // Display currency name only when discount type is "fixed amount" (not percent)
        $('#pms-discount-type').click(function() {

            if ($(this).attr("value") == "fixed") {
                $(".pms-discount-currency").toggle();
            }
        });

        /*
        * Move the "Bulk Add Discount Codes" button from the submit box
        * next to the "Add New" button next to the title of the page
        *
        */
        $buttonsWrapper = $('#pms-bulk-add-discounts-wrapper');
        $buttons = $buttonsWrapper.children();
        
        $('.wrap .page-title-action').first().after( $buttons );
        $buttonsWrapper.remove();

    });

    /**
     * Add Link to PMS Docs next to page title
     * */
    $(document).ready( function () {
        $(function(){
            $('.wp-admin.edit-php.post-type-pms-discount-codes .wrap .wp-heading-inline').append('<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/discount-codes/?utm_source=pms-discount-codes&utm_medium=client-site&utm_campaign=pms-discount-codes-docs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>');
        });
    });

});

/**
 *  Function that copies the shortcode from an input
 *
 * */
jQuery(document).ready(function() {
    jQuery('.pms-shortcode_copy').click(function (e) {

        e.preventDefault();

        navigator.clipboard.writeText(jQuery(this).val());

        // Show copy message
        var copyMessage = jQuery(this).next('.pms-copy-message');
        copyMessage.fadeIn(400).delay(2000).fadeOut(400);

    })
});

/**
 * Extra Subscription and Discount Options add-on --> Extra Options dropdown + other validations
 * */

jQuery(document).ready(function($) {

    var $checkbox = $('#pms-discount-limited-usage');
    var $panel    = $('.pms-limited-window');

    function showLimitedWindow() {
        $panel.toggle($checkbox.is(':checked'));
    }

    showLimitedWindow();

    $checkbox.on('change', showLimitedWindow);

    // When a only option is active, uncheck other only options
    const $newUsersOnly = $('#pms-discount-new-users-only');
    const $upgradesOnly = $('#pms-discount-available-upgrades');
    const $expiredOnly = $('#pms-discount-available-exp_subs');

    function uncheckOthers(checkedBox, others) {
        if (checkedBox.is(':checked')) {
            others.forEach(el => el.prop('checked', false));
        }
    }

    $newUsersOnly.on('change', function() {
        uncheckOthers($newUsersOnly, [ $upgradesOnly, $expiredOnly ]);
    });

    $upgradesOnly.on('change', function() {
        uncheckOthers($upgradesOnly, [ $newUsersOnly, $expiredOnly ]);
    });

    $expiredOnly.on('change', function() {
        uncheckOthers($expiredOnly, [ $newUsersOnly, $upgradesOnly ]);
    });
});