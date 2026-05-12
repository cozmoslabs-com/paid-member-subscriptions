import { Fragment } from "react";
import { __ } from "@wordpress/i18n";
import { decodeEntities } from "@wordpress/html-entities";
import {
    BaseControl,
    CheckboxControl,
    SelectControl,
    TextareaControl,
    ToggleControl,
    __experimentalInputControl as InputControl,
    __experimentalToggleGroupControl as ToggleGroupControl,
    __experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from "@wordpress/components";

export const getRestrictionHelpMessage = (pmsContentRestriction, subscriptionPlans) => {
    if (!pmsContentRestriction) return "";

    let helpMessage = "";

    switch ( pmsContentRestriction.display_to ) {
        case "all":
            helpMessage = __(
                "This content is not restricted and can be seen by all users.",
                "paid-member-subscriptions",
            );
            break;
        case "":
            helpMessage = __(
                "This content is restricted and can only be seen by logged in users",
                "paid-member-subscriptions",
            );
            if (
                pmsContentRestriction.subscription_plans &&
                pmsContentRestriction.subscription_plans.length !== 0
            ) {
                if ( pmsContentRestriction.not_subscribed ) {
                    helpMessage += __(
                        " that do not have",
                        "paid-member-subscriptions",
                    );
                } else {
                    helpMessage += __(
                        " that have",
                        "paid-member-subscriptions",
                    );
                }
                helpMessage += __(
                    " the following subscriptions: ",
                    "paid-member-subscriptions",
                );
                pmsContentRestriction.subscription_plans.forEach((id) => {
                    subscriptionPlans?.forEach( subscriptionPlan => {
                        if (subscriptionPlan.id == id) {
                            helpMessage += decodeEntities( subscriptionPlan.name ) + ", ";
                        }
                    });
                });
                helpMessage = helpMessage.slice(0, -2);
            }
            helpMessage += ".";
            break;
        case "not_logged_in":
            helpMessage = __(
                "This content is restricted and can only be seen by logged out users.",
                "paid-member-subscriptions",
            );
            break;
        default:
            helpMessage = __("Please select an option.", "paid-member-subscriptions");
    }
    return helpMessage;
};

export default function PMSBlockContentRestrictionControlsCommon(props) {
    const { name, attributes, setAttributes } = props;

    // Abort if the block type does not have the pmsContentRestriction attribute registered
    if ( !attributes || !("pmsContentRestriction" in attributes) ) {
        return null;
    }

    const { pmsContentRestriction } = attributes;

    const subscriptionPlans = pmsBlockEditorDataBlockContentRestriction.subscriptionPlans;

    // Check if this is one of the Content Restriction blocks so that the 'All Users' option can be hidden
    let contentRestrictionBlock = false;
    if (
        [
            "pms/content-restriction-start",
            "pms/content-restriction-end",
        ].includes(name)
    ) {
        contentRestrictionBlock = true;
    }

    let helpMessage = getRestrictionHelpMessage(pmsContentRestriction, subscriptionPlans);
    return (
        <>
            <p>{helpMessage}</p>
            <br />
            <ToggleGroupControl
                isBlock
                label={__("Show content to", "paid-member-subscriptions")}
                value={pmsContentRestriction.display_to}
                onChange={(value) =>
                    setAttributes({
                        pmsContentRestriction: {
                            ...pmsContentRestriction,
                            display_to: value,
                        },
                    })
                }
            >
                {!contentRestrictionBlock && (
                    <ToggleGroupControlOption
                        label={__("All Users", "paid-member-subscriptions")}
                        value="all"
                    />
                )}
                <ToggleGroupControlOption
                    label={__("Logged In Users", "paid-member-subscriptions")}
                    value=""
                />
                <ToggleGroupControlOption
                    label={__("Logged Out Users", "paid-member-subscriptions")}
                    value="not_logged_in"
                />
            </ToggleGroupControl>
            {pmsContentRestriction.display_to == "all" && <p></p>}
            {pmsContentRestriction.display_to == "" && (
                <div>
                    <BaseControl 
                        label={__("Subscriptions", "paid-member-subscriptions")}
                        help={__(
                            "The desired valid subscriptions. Select none to display the content to all logged in users.",
                            "paid-member-subscriptions",
                        )}
                    >
                        <div style={{ maxHeight: '380px', overflowY: 'auto', border: '1px solid #8c8f94', padding: '8px 12px', borderRadius: '2px', marginBottom: '16px', backgroundColor: '#fff' }}>
                            <style>
                                {`
                                    .pms-subscription-plan-checkbox {
                                        margin-bottom: 4px !important;
                                    }
                                    .pms-subscription-plan-checkbox:last-child {
                                        margin-bottom: 0 !important;
                                    }
                                    .pms-subscription-plan-checkbox .components-checkbox-control__label {
                                        font-size: 13px !important;
                                        line-height: 1.4 !important;
                                    }
                                    .pms-subscription-plan-checkbox .components-checkbox-control__input-container {
                                        margin-right: 8px !important;
                                    }
                                `}
                            </style>
                            { subscriptionPlans?.map( subscriptionPlan => {
                                const planIdStr = subscriptionPlan.id.toString();
                                const isChecked = (pmsContentRestriction.subscription_plans || []).map(String).includes(planIdStr);
                                
                                return (
                                    <CheckboxControl
                                        key={ subscriptionPlan.id }
                                        label={ decodeEntities( subscriptionPlan.name ) }
                                        checked={ isChecked }
                                        onChange={ ( checked ) => {
                                            let newPlans = (pmsContentRestriction.subscription_plans || []).map(String);
                                            if ( checked ) {
                                                if ( !newPlans.includes(planIdStr) ) {
                                                    newPlans.push( planIdStr );
                                                }
                                            } else {
                                                newPlans = newPlans.filter( id => id !== planIdStr );
                                            }
                                            setAttributes({
                                                pmsContentRestriction: {
                                                    ...pmsContentRestriction,
                                                    subscription_plans: newPlans,
                                                },
                                            });
                                        } }
                                        className="pms-subscription-plan-checkbox"
                                    />
                                );
                            }) }
                        </div>
                        <ToggleControl
                            label={__(
                                "Show to Not Subscribed",
                                "paid-member-subscriptions",
                            )}
                            checked={
                                pmsContentRestriction.not_subscribed
                                    ? pmsContentRestriction.not_subscribed
                                    : false
                            }
                            onChange={() =>
                                setAttributes({
                                    pmsContentRestriction: {
                                        ...pmsContentRestriction,
                                        not_subscribed: !pmsContentRestriction.not_subscribed,
                                    },
                                })
                            }
                        />
                    </BaseControl>
                    <Fragment>
                        <ToggleControl
                            label={__(
                                "Enable Custom Message",
                                "paid-member-subscriptions",
                            )}
                            checked={
                                pmsContentRestriction.enable_message_logged_in
                                    ? pmsContentRestriction.enable_message_logged_in
                                    : false
                            }
                            onChange={() =>
                                setAttributes({
                                    pmsContentRestriction: {
                                        ...pmsContentRestriction,
                                        enable_message_logged_in: !pmsContentRestriction.enable_message_logged_in,
                                    },
                                })
                            }
                        />
                        {pmsContentRestriction.enable_message_logged_in && (
                            <TextareaControl
                                help={__(
                                    "Custom message for logged-in users.",
                                    "paid-member-subscriptions",
                                )}
                                value={pmsContentRestriction.message_logged_in}
                                onChange={(value) =>
                                    setAttributes({
                                        pmsContentRestriction: {
                                            ...pmsContentRestriction,
                                            message_logged_in: value,
                                        },
                                    })
                                }
                            />
                        )}
                    </Fragment>
                </div>
            )}
            {pmsContentRestriction.display_to == "not_logged_in" && (
                <Fragment>
                    <ToggleControl
                        label={__("Enable Custom Message", "paid-member-subscriptions")}
                        checked={
                            pmsContentRestriction.enable_message_logged_out
                                ? pmsContentRestriction.enable_message_logged_out
                                : false
                        }
                        onChange={() =>
                            setAttributes({
                                pmsContentRestriction: {
                                    ...pmsContentRestriction,
                                    enable_message_logged_out: !pmsContentRestriction.enable_message_logged_out,
                                },
                            })
                        }
                    />
                    {pmsContentRestriction.enable_message_logged_out && (
                        <TextareaControl
                            help={__(
                                "Custom message for logged-out users",
                                "paid-member-subscriptions",
                            )}
                            value={pmsContentRestriction.message_logged_out}
                            onChange={(value) =>
                                setAttributes({
                                    pmsContentRestriction: {
                                        ...pmsContentRestriction,
                                        message_logged_out: value,
                                    },
                                })
                            }
                        />
                    )}
                </Fragment>
            )}
        </>
    );
}
