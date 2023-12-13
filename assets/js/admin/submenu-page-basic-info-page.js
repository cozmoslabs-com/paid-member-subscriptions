// Function that copies the shortcode from a text
jQuery(document).ready(function() {
    jQuery('.pms-shortcode_copy-text').click(function (e) {
        e.preventDefault();

        navigator.clipboard.writeText(jQuery(this).text());

        // Show copy message
        var copyMessage = jQuery(this).next('.pms-copy-message');
        copyMessage.fadeIn(400).delay(2000).fadeOut(400);

    })
});