/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from "@wordpress/block-editor";
import { RawHTML } from "@wordpress/element";

/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#save
 *
 * @return {WPElement} Element to render.
 */
const Save = ({ attributes }) => {
    const { pmsContentRestriction } = attributes;

    let shortcode = "";
    switch (pmsContentRestriction.display_to) {
        case "all":
            break;
        case "":
            shortcode = "[pms-restrict";
            if ( pmsContentRestriction.not_subscribed ){
                shortcode += ' display_to="not_subscribed"';
            }
            if (
                pmsContentRestriction.subscription_plans &&
                pmsContentRestriction.subscription_plans.length !== 0
            ) {
                shortcode += ' subscription_plans="';
                pmsContentRestriction.subscription_plans.map((id) => {
                    shortcode += id + ", ";
                });
                shortcode = shortcode.slice(0, -2);
                shortcode += '"';
            }
            if (
                pmsContentRestriction.enable_message_logged_in &&
                pmsContentRestriction.message_logged_in.length !== 0
            ) {
                shortcode += ' message="';
                shortcode += pmsContentRestriction.message_logged_in;
                shortcode += '"';
            }
            shortcode += "]";
            break;
        case "not_logged_in":
            shortcode = '[pms-restrict display_to="not_logged_in"';
            if (
                pmsContentRestriction.enable_message_logged_out &&
                pmsContentRestriction.message_logged_out.length !== 0
            ) {
                shortcode += ' message="';
                shortcode += pmsContentRestriction.message_logged_out;
                shortcode += '"';
            }
            shortcode += "]";
            break;
        default:
            break;
    }
    return <RawHTML {...useBlockProps.save()}>{shortcode}</RawHTML>;
};

export default Save;
