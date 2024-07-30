/**
 * Reposition the Publish Box/Button in Admin Dashboard --> PMS CPTs & Custom Pages
 */

jQuery( document ).ready(setTimeout(function () {
    let smallMediumScreen  = window.matchMedia("(max-width: 1401px)"),
        largeScreen  = window.matchMedia("(min-width: 1402px)"),
        pageBody = jQuery('body');

    if (pageBody.is('[class*="post-type-pms"]')) {
        if (smallMediumScreen.matches)
            pmsRepositionCptPublishButton();
        else pmsRepositionCptPublishBox();
    }
    else if (pageBody.is('[class*="paid-member-subscriptions_page"]')) {
        if (largeScreen.matches)
            pmsRepositionPagePublishBox();
        else pmsRepositionPagePublishButton();
    }

}, 1000));

function pmsRepositionCptPublishBox() {
    let buttonWrapperContainer = jQuery('#side-sortables'),
        containerOffsetTop = buttonWrapperContainer.length > 0 ? buttonWrapperContainer.offset().top : 0;

    // set initial position
    pmsSetCptPublishBoxPosition();

    // reposition on scroll
    jQuery(window).on('scroll', function() {
        pmsSetCptPublishBoxPosition();
    });

    // position the Publish Box
    function pmsSetCptPublishBoxPosition() {
        if ( jQuery(window).scrollTop() >= (containerOffsetTop - 32) )
            buttonWrapperContainer.addClass('cozmoslabs-publish-metabox-fixed');
        else buttonWrapperContainer.removeClass('cozmoslabs-publish-metabox-fixed');
    }
}

function pmsRepositionCptPublishButton() {
    let buttonWrapper = jQuery('#side-sortables #submitdiv');

    if ( buttonWrapper.length > 0 ) {
        // set initial position
        pmsSetCptPublishButtonPosition();

        // reposition on scroll
        jQuery(window).on('scroll', function() {
            pmsSetCptPublishButtonPosition();
        });
    }

    // position the Publish Button
    function pmsSetCptPublishButtonPosition() {
        let button = jQuery('#side-sortables #submitdiv input[type="submit"]'),
            buttonWrapperContainer = jQuery('#side-sortables');

        if (pmsElementInViewport(buttonWrapper)) {
            buttonWrapperContainer.removeClass('cozmoslabs-publish-button-fixed');

            button.css({
                'max-width': 'unset',
                'left': 'unset',
            });
        } else {
            let containerOffsetLeft = buttonWrapper.offset().left;

            buttonWrapperContainer.addClass('cozmoslabs-publish-button-fixed');

            button.css({
                'max-width': buttonWrapper.outerWidth() + 'px',
                'left': containerOffsetLeft + 'px',
            });
        }
    }
}

/**
 *  Reposition Publish Box (large screens)
 *
 * */
function pmsRepositionPagePublishBox() {
    let topBox = jQuery('#pms-member-details, .cozmoslabs-wrap .cozmoslabs-nav-tab-wrapper'),
        buttonWrapper = jQuery('.cozmoslabs-wrap div.submit');

    if ( topBox.length > 0 && buttonWrapper.length > 0 ) {

        let cozmoslabsWrapper = jQuery('.cozmoslabs-wrap');

        cozmoslabsWrapper.addClass('cozmoslabs-publish-box-fixed');

        let bannerHeight = jQuery('.cozmoslabs-banner').outerHeight(),
            topBoxOffsetTop = topBox.offset().top;

        buttonWrapper.css({
            'top': topBoxOffsetTop - bannerHeight - 62 + 'px',  // 32px is the admin bar height + 30px cozmoslabs-wrap margin top
        });

        let cozmoslabsWrapperWidth = cozmoslabsWrapper.outerWidth();

        if (cozmoslabsWrapperWidth < 1200)
            cozmoslabsWrapper.css({
                'margin': '30px 10px',
            });

        // set initial position
        pmsSetPagePublishBoxPosition();

        // reposition on scroll
        jQuery(window).scroll(function () {
            pmsSetPagePublishBoxPosition();
        });

        // position the Publish Box
        function pmsSetPagePublishBoxPosition() {
            let distanceToTop = pmsCalculateDistanceToTop(topBox);

            if (distanceToTop < 50) {
                let buttonOffsetLeft = buttonWrapper.offset().left;

                buttonWrapper.css({
                    'position': 'fixed',
                    'top': '50px',
                    'left': buttonOffsetLeft,
                    'box-shadow': '0 3px 10px 0 #aaa',
                });
            } else {
                buttonWrapper.css({
                    'position': 'absolute',
                    'top': topBoxOffsetTop - bannerHeight - 62 + 'px', // 32px is the admin bar height + 30px cozmoslabs-wrap margin top
                    'left': 'auto',
                    'box-shadow': 'none',
                });
            }
        }
    }

}


/**
 *  Reposition Publish Button (small/medium screens)
 *
 * */
function pmsRepositionPagePublishButton() {
    let buttonWrapper = jQuery('.cozmoslabs-wrap div.submit'),
        button = jQuery('.cozmoslabs-wrap div.submit input[type="submit"]'),
        cozmoslabsWrapper = jQuery('.cozmoslabs-wrap');

    if ( buttonWrapper.length > 0 ) {
        // set initial position
        pmsSetPagePublishButtonPosition();

        // reposition on scroll
        jQuery(window).on('scroll', function() {
            pmsSetPagePublishButtonPosition();
        });
    }

    // position the Publish Button
    function pmsSetPagePublishButtonPosition() {
        if (pmsElementInViewport(buttonWrapper)) {
            cozmoslabsWrapper.removeClass('cozmoslabs-publish-button-fixed');

            button.css({
                'max-width': 'unset',
                'margin-left': 'unset',
            });
        } else {
            cozmoslabsWrapper.addClass('cozmoslabs-publish-button-fixed');

            button.css({
                'max-width': buttonWrapper.outerWidth() + 'px',
                'margin-left': '-10px',
            });
        }
    }
}


/**
 *  Calculate the distance to Top for a specific element
 *
 * */
function pmsCalculateDistanceToTop(element) {
    let scrollTop = jQuery(window).scrollTop(),
        elementOffset = element.offset().top;

    return elementOffset - scrollTop;
}


/**
 *  Check if a specific element is visible on screen
 *
 * */
function pmsElementInViewport(element) {
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
        let selector = "body[class*='paid-member-subscriptions_page_'] " + notice + ", " + "body[class*='post-type-pms-'] " + notice;

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
