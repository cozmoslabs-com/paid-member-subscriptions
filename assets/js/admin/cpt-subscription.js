/*
 * JavaScript for Subscription Plan cpt screen
 *
 */
jQuery( function($) {

    /*
     * When publishing or updating the Subscription Plan must have a name
     *
     */
    $(document).on( 'click', '#publish, #save-post', function() {

        var subscriptionPlanTitle = $('#title').val().trim();

        if( subscriptionPlanTitle == '' ) {

            alert( 'Subscription Plan must have a name.' );

            return false;

        }

    });

    /*
     * Remove the default "Move to Trash button"
     * Remove the "Edit" link for Subscription Plan status
     * Remove the "Visibility" box for discount codes
     * Remove the "Save Draft" button
     * Remove the "Status" div
     * Remove the "Published on.." section
     * Rename metabox "Save Subscription Plan"
     * Change "Publish" button to "Save Subscription"
     *
     */
   $(document).ready( function() {
        $('#delete-action').remove();
        $('.edit-post-status').remove();
        $('#visibility').remove();
        $('#minor-publishing-actions').remove();
        $('div.misc-pub-post-status').remove();
        $('#misc-publishing-actions').hide();
        $('#submitdiv h3 span').html('Save Subscription Plan');
        $('input#publish').val('Save Subscription');
    });

   /**
    * Add Link to PMS Docs next to page title
    * */
   $(document).ready( function () {
       $(function(){
           $('.wp-admin.edit-php.post-type-pms-subscription .wrap .wp-heading-inline').append('<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/subscription-plans/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>');
       });
   });

    /*
      * Move the "Create Pricing Page" button from the admin footer
      * next to the "Add New" button next to the title of the page
      *
      */
    $(document).ready( function() {
        $buttonsWrapper = $('#pms-create-pricing-page-button-wrapper');

        $buttons = $buttonsWrapper.children();

        $('.wrap .page-title-action').first().after( $buttons );

        $buttonsWrapper.remove();

    });

    /*
     * Showing and closing the modal
     */

    $(document).on( 'click', '#pms-popup1', function() {
        $( '.pms-modal' ).show();
        jQuery('.overlay').show();
    });

    $(document).on( 'click', '.pms-button-close', function() {
        $( '.pms-modal' ).hide();
        jQuery('.overlay').hide();
    });

    /*
     * Move the "Add Upgrade" and "Add Downgrade" buttons from the submit box
     * next to the "Add New" button next to the title of the page
     *
     */
    $(document).ready( function() {

        $buttonsWrapper = $('#pms-upgrade-downgrade-buttons-wrapper');

        $buttons = $buttonsWrapper.children();

        $('.wrap h1').first().append( $buttons );

        $buttonsWrapper.remove();

    });

    $(document).on( 'click', '.pms-delete-subscription a', function(e) {

        var pmsDeleteUser = prompt( 'Are you sure you want to delete this plan ? Deleting plans with active subscribers can have unexpected results. \nPlease type DELETE in order to delete this plan:' )

        if( pmsDeleteUser === "DELETE" )
            window.location.replace(pmsGdpr.delete_url);
        else
            return false

    });

    /** Remove success message when showing validation errors */
    if ( $( '#pms-plan-metabox-errors' ).length > 0 ){
        
        if( $( '.updated.notice-success' ).length > 0 )
            $( '.updated.notice-success' ).remove()

        $('#pms-plan-metabox-errors').insertBefore( '.wp-header-end' )
    }

});

/**
 * Pricing Table Designs Feature --> Admin UI
 *
 *  - Activate new Design
 *  - Preview Modal
 *  - Modal Image Slider controls
 *
 * */
jQuery( document ).ready(function(){

    // Activate Design
    jQuery('.pms-pricing-tables-design-activate button.activate').click(function ( element ) {
        let themeID, i, allDesigns;

        themeID = jQuery(element.target).data('theme-id');

        jQuery('#pms-active-pricing-table-design').val(themeID);

        allDesigns = jQuery('.pms-pricing-tables-design');
        for (i = 0; i < allDesigns.length; i++) {
            if ( jQuery(allDesigns[i]).hasClass('active')) {
                jQuery('.pms-pricing-tables-design-title strong', allDesigns[i] ).hide();
                jQuery(allDesigns[i]).removeClass('active');
            }
        }
        jQuery('#pms-pricing-tables-design-browser .pms-forms-design#'+themeID).addClass('active');

    });

    jQuery('.pms-pricing-tables-design-preview').click(function (e) {
        let themeID = e.target.id.replace('-info', '');
        displayPreviewModal(themeID);
    });

    jQuery('.pms-slideshow-button').click(function (e) {
        let themeID = jQuery(e.target).data('theme-id'),
            direction = jQuery(e.target).data('slideshow-direction'),
            currentSlide = jQuery('#pms-modal-' + themeID + ' .pms-pricing-tables-design-preview-image.active'),
            changeSlideshowImage = window[direction+'Slide'];

        changeSlideshowImage(currentSlide,themeID);
    });

});

function displayPreviewModal( themeID ) {
    jQuery('#pms-modal-' + themeID).dialog({
        resizable: false,
        height: 'auto',
        width: 1200,
        modal: true,
        closeOnEscape: true,
        open: function () {
            jQuery('.ui-widget-overlay').bind('click',function () {
                jQuery('#pms-modal-' + themeID).dialog('close');
            })
        },
        close: function () {
            let allImages = jQuery('.pms-pricing-tables-design-preview-image');

            allImages.each( function() {
                if ( jQuery(this).is(':first-child') && !jQuery(this).hasClass('active') ) {
                    jQuery(this).addClass('active');
                }
                else if ( !jQuery(this).is(':first-child') ) {
                    jQuery(this).removeClass('active');
                }
            });

            jQuery('.pms-pricing-tables-design-sildeshow-previous').addClass('disabled');
            jQuery('.pms-pricing-tables-design-sildeshow-next').removeClass('disabled');
        }
    });
    return false;
}

function nextSlide( currentSlide, themeID ){
    if ( currentSlide.next().length > 0 ) {
        currentSlide.removeClass('active');
        currentSlide.next().addClass('active');

        jQuery('#pms-modal-' + themeID + ' .pms-pricing-tables-design-sildeshow-previous').removeClass('disabled');

        if ( currentSlide.next().next().length <= 0 )
            jQuery('#pms-modal-' + themeID + ' .pms-pricing-tables-design-sildeshow-next').addClass('disabled');

    }
}

function previousSlide( currentSlide, themeID ){
    if ( currentSlide.prev().length > 0 ) {
        currentSlide.removeClass('active');
        currentSlide.prev().addClass('active');

        jQuery('#pms-modal-' + themeID + ' .pms-pricing-tables-design-sildeshow-next').removeClass('disabled');

        if ( currentSlide.prev().prev().length <= 0 )
            jQuery('#pms-modal-' + themeID + ' .pms-pricing-tables-design-sildeshow-previous').addClass('disabled');

    }
}