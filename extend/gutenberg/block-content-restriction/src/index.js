import { addFilter } from "@wordpress/hooks";
import { createHigherOrderComponent } from "@wordpress/compose";
import { __ } from "@wordpress/i18n";
import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody } from "@wordpress/components";

import PMSBlockContentRestrictionControlsCommon from "./controls.js";

/**
 * Add the content restriction inspector controls in the editor
 */
function PMSBlockContentRestrictionControls(props) {
    const { name, attributes, setAttributes } = props;
    const { pmsContentRestriction } = attributes;

    // Abort if content restriction is not enabled, if the block type does not have the
    // pmsContentRestriction attribute registered or if the block is one of the Content Restriction blocks
    if ( !attributes || !("pmsContentRestriction" in attributes) ||
        [
            "pms/content-restriction-start",
            "pms/content-restriction-end",
            "wppb/content-restriction-start",
            "wppb/content-restriction-end",
        ].includes(name)
    ) {
        return null;
    }

    return (
        <InspectorControls>
            <PanelBody
                title={__(
                    "Content Restriction",
                    "paid-member-subscriptions",
                )}
                className="paid-member-subscriptions-content-restriction-settings"
                initialOpen={pmsContentRestriction.panel_open}
                onToggle={(value) =>
                    setAttributes({
                        pmsContentRestriction: {
                            ...pmsContentRestriction,
                            panel_open: !pmsContentRestriction.panel_open,
                        },
                    })
                }
            >
                <PMSBlockContentRestrictionControlsCommon {...props} />
            </PanelBody>
        </InspectorControls>
    );
}

/**
 * Add the content restriction settings attribute
 */
function PMSContentRestrictionAttributes(settings) {
    let contentRestrictionAttributes = {
        pmsContentRestriction: {
            type: "object",
            properties: {
                subscription_plans: {
                    type: "array",
                },
                display_to: {
                    type: "string",
                },
                not_subscribed: {
                    type: "boolean",
                },
                enable_message_logged_in: {
                    type: "boolean",
                },
                enable_message_logged_out: {
                    type: "boolean",
                },
                message_logged_in: {
                    type: "string",
                },
                message_logged_out: {
                    type: "string",
                },
                panel_open: {
                    type: "boolean",
                },
            },
            default: {
                subscription_plans: [],
                display_to: "all",
                not_subscribed: false,
                enable_message_logged_in: false,
                enable_message_logged_out: false,
                message_logged_in: "",
                message_logged_out: "",
                panel_open: false,
            },
        },
    };

    // The Content Restriction Start block should not have an 'All Users' option
    if (settings.attributes.pms_content_restriction_block_start) {
        contentRestrictionAttributes.pmsContentRestriction.default.display_to =
            "";
    }

    // Do not add the content restriction settings attribute for these blocks
    if (
        settings.attributes.pms_content_restriction_block_end ||
        settings.attributes.wppb_content_restriction_block_start ||
        settings.attributes.wppb_content_restriction_block_end
    ) {
        return settings;
    }

    settings.attributes = Object.assign(
        settings.attributes,
        contentRestrictionAttributes,
    );
    return settings;
}
addFilter(
    "blocks.registerBlockType",
    "paid-member-subscriptions/attributes",
    PMSContentRestrictionAttributes,
);

/**
 * Filter the block edit object and add content restriction controls
 */
const blockPMSContentRestrictionControls = createHigherOrderComponent(
    (BlockEdit) => {
        return (props) => {
            return (
                <>
                    <BlockEdit {...props} />
                    <PMSBlockContentRestrictionControls {...props} />
                </>
            );
        };
    },
    "blockPMSContentRestrictionControls",
);
addFilter(
    "editor.BlockEdit",
    "paid-member-subscriptions/inspector-controls",
    blockPMSContentRestrictionControls,
    100, // above Advanced controls
);
