jQuery(window).on('load', function () {
    handlePublishBoxPosition();

    // Reset the scroll event on manual window resize to keep the Publish button scrollable without refreshing
    jQuery(window).on('resize', function() {

        // clear existing scroll events
        jQuery(window).off('scroll');

        handlePublishBoxPosition();
    });
});


/**
 * Reposition the Publish Box/Button in Admin Dashboard
 * - PMS CPTs
 * - Custom Pages
 *
 */
function handlePublishBoxPosition() {
    let largeScreen  = window.matchMedia("(min-width: 1402px)"),
        settingsContainer =  jQuery('#cozmoslabs-subsection-register-version'),
        buttonContainer = jQuery('.cozmoslabs-submit');

    if ( settingsContainer.length === 0 ){
        settingsContainer = jQuery( '.cozmoslabs-settings' );
    }

    // determine if we are on a PMS Custom Page or CPT
    if ( settingsContainer.length === 0 ) {
        settingsContainer = jQuery('#poststuff');
        buttonContainer = jQuery('#submitdiv');
    }

    if ( settingsContainer.length > 0 && buttonContainer.length > 0 ) {

        if ( largeScreen.matches )
            pmsRepositionPublishMetaBox( settingsContainer, buttonContainer );
        else pmsRepositionPublishButton( buttonContainer );

    }
}


/**
 *  Reposition Publish Meta-Box
 * - PMS CPTs
 * - Custom Pages
 *
 *  - works on large screens
 *
 * */
function pmsRepositionPublishMetaBox( settingsContainer, buttonContainer ) {

    if ( buttonContainer.length > 0 ) {

        // set initial position
        pmsSetPublishMetaBoxPosition();

        // reposition on scroll
        jQuery(window).scroll(function () {
            pmsSetPublishMetaBoxPosition();
        });

    }

    /**
     * Position the Publish Meta-Box
     */
    function pmsSetPublishMetaBoxPosition() {
        if ( pmsCalculateDistanceToTop( settingsContainer ) < 50 ) {

            buttonContainer.addClass('cozmoslabs-publish-metabox-fixed');

            buttonContainer.css({
                'display': 'block',
                'left'   : settingsContainer.offset().left + settingsContainer.outerWidth() + 'px',
            });
        } else {

            if( jQuery( '#cozmoslabs-subsection-register-version').length > 0 ){
                buttonContainer.css({
                    'margin-top': -jQuery('#cozmoslabs-subsection-register-version').outerHeight() + 'px',
                });
            }

            buttonContainer.removeClass('cozmoslabs-publish-metabox-fixed');

            buttonContainer.css({
                'display': 'block',
                'left'   : 'unset',
            });
        }
    }

}


/**
 *  Reposition Publish Button
 *  - PMS CPTs
 *  - Custom Pages
 *
 *  - works on small/medium screens
 *
 * */
function pmsRepositionPublishButton( buttonContainer ) {

    if ( buttonContainer.length > 0 ) {

        // set initial position
        pmsSetPublishButtonPosition();

        // reposition on scroll
        jQuery(window).on('scroll', function() {
            pmsSetPublishButtonPosition();
        });

    }

    /**
     * Position the Publish Button
     */
    function pmsSetPublishButtonPosition() {

        let button = buttonContainer.find('input[type="submit"]');

        if ( pmsElementInViewport( buttonContainer ) ) {
            buttonContainer.removeClass('cozmoslabs-publish-button-fixed');

            button.css({
               'max-width': 'unset',
               'left': 'unset',
            });

            buttonContainer.css({
                'display': 'block',
            });

        } else {
            buttonContainer.addClass('cozmoslabs-publish-button-fixed');

            button.css({
               'max-width': buttonContainer.outerWidth() + 'px',
               'left': buttonContainer.offset().left + 'px',
            });

            buttonContainer.css({
                'display': 'block',
            });
        
        }
    }
}


/**
 *  Calculate the distance to Top for a specific element
 *
 * */
function pmsCalculateDistanceToTop( element ) {
    let scrollTop = jQuery(window).scrollTop(),
        elementOffset = element.offset().top;

    return elementOffset - scrollTop;
}


/**
 *  Check if a specific element is visible on screen
 *
 * */
function pmsElementInViewport( element ) {
    let elementTop = element.offset().top,
        elementBottom = elementTop + element.outerHeight(),
        viewportTop = jQuery(window).scrollTop(),
        viewportBottom = viewportTop + jQuery(window).height();

    return elementBottom > viewportTop && elementTop < viewportBottom;
}


/**
 *  Set PMS Tables content width on smaller screens
 *
 * */
jQuery( document ).ready(function(){
    let tableElementWrapper = jQuery('body[class*="post-type-pms-"] .wp-list-table'),
        tableElement = jQuery('body[class*="post-type-pms-"] .wp-list-table tbody'),
        smallScreen  = window.matchMedia("(max-width: 782px)");

    if (tableElement.length > 0 && smallScreen.matches) {
        tableElement.css({
            'width': tableElementWrapper.outerWidth() - 2 + 'px',
        });
    }

    /**
     *  Display initially hidden admin notices, after the scripts have been loaded
     *
     * */

    let noticeTypes = [
        ".error",
        ".notice"
    ];

    noticeTypes.forEach(function(notice){
        let selector = "body[class*='_page_pms-'] " + notice + ", " + "body[class*='post-type-pms-'] " + notice;

        jQuery(selector).each(function () {
            this.style.setProperty( 'display', 'block', 'important' );
        });
    });

    // Remove Lost Connection notice from autosave. Not necessary for us
    // This started appearing around start of 2024, then again in August but this time with !impotant and it couldn't be overwriten through CSS
    if( jQuery( '#lost-connection-notice' ).length > 0 )
        jQuery( '#lost-connection-notice' ).remove()

    if( jQuery( '#local-storage-notice' ).length > 0 )
        jQuery( '#local-storage-notice' ).remove()

});
