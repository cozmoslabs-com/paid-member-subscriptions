import {
    isEqual,
    isEmpty
} from 'lodash';

import { store as coreDataStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';
import { useSelect } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';
import { createHigherOrderComponent } from '@wordpress/compose';
import {
    Popover,
    ToolbarButton,
    ToolbarGroup,
    BaseControl,
    Button,
    ButtonGroup,
    ExternalLink,
    PanelBody,
    ToggleControl
} from '@wordpress/components';
import {
    Fragment,
    useState,
} from '@wordpress/element';

/**
 * Get parameter from the URL.
 */
export const getObjectFromQueryString = queryString => {
    if ( -1 < queryString.indexOf( '?' ) ) {
        queryString = queryString.split( '?' )[1];
    }

    const pairs = queryString.split( '&' );
    const result = {};

    pairs.forEach( function( pair ) {
        pair = pair.split( '=' );
        if ( '' !== pair[0]) {
            result[pair[0]] = decodeURIComponent( pair[1] || '' );
        }
    });

    return result;
};

/**
 * Object to Query String.
 */
export const getQueryStringFromObject = params => Object.keys( params ).map( key => key + '=' + params[key]).join( '&' );

// Enable link inserter option on Button blocks
const enableLinkGeneratorOnBlocks = {
    'core/button': {
        link: 'url'
    },
    'themeisle-blocks/button': {
        link: 'link'
    },
    'themeisle-blocks/font-awesome-icons': {
        link: 'link'
    }
};

const LinkGenerator = ({
    props,
    children
}) => {

    const pages = useSelect(
        select =>
            select( coreDataStore ).getEntityRecords(
                "postType",
                "page",
                { per_page: -1 }
            ),
        []
    );

    const subscriptionPlans = pmsBlockEditorDataLinkGenerator.subscriptionPlans;

    const registerPageID = pmsBlockEditorDataLinkGenerator.registerPageID ? pmsBlockEditorDataLinkGenerator.registerPageID.toString() : false;

    const activeAttributes = getObjectFromQueryString( props.attributes[ enableLinkGeneratorOnBlocks[ props.name ].link ] || '' );

    const [ popoverAnchor, setPopoverAnchor ] = useState();
    const [ isOpen, setOpen ] = useState( false );
    const [ attributes, setAttributes ] = useState({ ...activeAttributes });

    if ( registerPageID && isEmpty( attributes ) ) {
        attributes[ 'p' ] = registerPageID;
    }

    const changeAttributes = obj => {
        let attrs = { ...attributes };

        Object.keys( obj ).forEach( o => {
            attrs[ o ] = obj[ o ];
        });

        attrs = Object.fromEntries( Object.entries( attrs ).filter( ([ _, v ]) => ( null !== v && '' !== v && undefined !== v ) ) );

        setAttributes({ ...attrs });
    };

    const { isQueryChild } = useSelect( select => {
        const { getBlockParentsByBlockName } = select( 'core/block-editor' );

        return {
            isQueryChild: 0 < getBlockParentsByBlockName( props.clientId, 'core/query' ).length
        };
    }, []);

    const onChange = () => {
        const attrs = Object.fromEntries( Object.entries( attributes ).filter( ([ _, v ]) => ( null !== v && '' !== v && undefined !== v ) ) );

        if ( isQueryChild ) {
            attrs.context = 'query';
        }

        props.setAttributes({ [ enableLinkGeneratorOnBlocks[ props.name ].link ]: `/?${ getQueryStringFromObject( attrs ) }` });
    };

    const onRemove = () => {
        if ( registerPageID ) {
            setAttributes({ p: registerPageID });
        } else {
            setAttributes({});
        }

        props.setAttributes({ [ enableLinkGeneratorOnBlocks[ props.name ].link ]: undefined });
    };

    return (
        <Fragment>
            { children }

            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        name="pms-link-generator"
                        icon={ () => (
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
                                <path d="m 10.951071,22.655193 c -1.6414171,-0.205916 -3.5758081,-1.068475 -5.0122731,-2.23501 -0.94328,-0.766025 -1.181857,-0.87327 -1.500507,-0.674512 -0.932319,0.581542 -1.215071,0.331936 -1.198567,-1.058062 0.02496,-2.101371 0.241943,-3.142584 0.672136,-3.225194 0.47754,-0.09171 3.561318,1.139933 3.912561,1.562645 0.221719,0.266834 0.160331,0.404101 -0.384871,0.860571 -0.648667,0.543099 -0.648667,0.543099 0.243196,1.224672 4.6571691,3.559068 11.5872441,0.646323 12.5503531,-5.274976 0.27163,-1.670009 1.263047,-2.303337 1.954856,-1.24878 0.255325,0.389205 0.273351,0.709633 0.100298,1.782902 -0.863167,5.353294 -5.822209,8.977595 -11.337182,8.285744 z M 10.624653,19.077156 C 9.3600599,18.722024 9.1681389,18.520771 9.1156939,17.494835 c -0.06004,-1.174372 -0.520684,-1.753317 -1.744921,-2.193015 -1.45253,-0.521691 -1.556673,-0.700925 -1.541162,-2.652348 0.02114,-2.6592954 1.28833,-4.7391761 3.568876,-5.8577071 1.6547741,-0.811611 4.1955171,-0.8400736 5.7487431,-0.064397 1.036772,0.5177592 1.036772,0.5177592 1.14045,1.7807827 0.122329,1.490265 0.320192,1.7756664 1.55849,2.2480054 1.083375,0.413245 1.245457,0.796985 1.084669,2.568038 -0.364707,4.017203 -4.438866,6.839013 -8.306186,5.752962 z m 2.332704,-2.799401 c 0.115111,-0.214828 0.528974,-0.578984 0.919696,-0.809236 1.746836,-1.029414 1.053182,-2.798685 -1.42543,-3.635779 -1.068157,-0.360744 -1.168957,-1.033997 -0.169292,-1.13072 0.494839,-0.04788 0.717321,0.04287 0.888552,0.362431 0.270897,0.505564 1.150246,0.572039 1.331356,0.10065 0.16379,-0.426316 -0.177701,-1.042269 -0.8057,-1.4532524 -0.276652,-0.181051 -0.556849,-0.49863 -0.62266,-0.705731 -0.235263,-0.740349 -1.221071,-0.72629 -1.458475,0.0208 -0.06944,0.218541 -0.398228,0.537815 -0.730629,0.709498 -0.9158861,0.4730514 -1.1538501,1.8528734 -0.454503,2.6353994 0.178573,0.199812 0.874352,0.563472 1.546175,0.808136 1.012558,0.36875 1.210869,0.51916 1.159368,0.879327 -0.09427,0.659247 -1.008195,0.674662 -1.775327,0.02994 -0.645657,-0.542626 -1.16638,-0.535546 -1.3793441,0.01875 -0.145869,0.379673 1.9006671,2.548584 2.4113461,2.555537 0.195565,0.0026 0.449755,-0.170926 0.564867,-0.38575 z M 2.6437629,13.934578 C 2.2586709,13.549952 2.3481649,11.271428 2.8065869,9.7890226 4.9831789,2.7505514 13.773264,0.40495617 19.192807,5.4164334 20.415063,6.546659 20.424413,6.5508128 21.153448,6.2875086 22.222666,5.9013411 22.309284,6.1047613 22.084667,8.4744836 21.858065,10.865172 21.759236,10.97596 20.346792,10.422652 17.299761,9.2290136 16.920196,8.8320916 18.092279,8.0650396 18.652755,7.6982469 18.652755,7.6982469 17.760114,6.9070565 16.096781,5.4327601 14.822308,4.9669839 12.451623,4.9669839 c -1.837407,0 -2.217694,0.066539 -3.3393541,0.5843029 -2.597438,1.1989858 -4.336428,3.7143668 -4.620348,6.6831602 -0.09603,1.004161 -0.256765,1.631153 -0.449347,1.752847 -0.421945,0.266629 -1.104848,0.240892 -1.398811,-0.05271 z" />
                            </svg>
                            ) }
                        title={ __( 'PMS Link Generator', 'paid-member-subscriptions' ) }
                        ref={ setPopoverAnchor }
                        onClick={ () => {
                            setOpen(!isOpen);
                        } }
                    />
                </ToolbarGroup>
            </BlockControls>

            { ( isOpen && props.isSelected ) && (
                <Popover
                    position="bottom right"
                    variant="toolbar"
                    anchor={ popoverAnchor }
                    className="pms-link-generator-popover"
                    onClose={ () => setOpen( false ) }
                >
                    <PanelBody>
                        <p>{ __( 'Generate links to Subscription Plans', 'paid-member-subscriptions' ) }</p>
                        <br/>
                        <br/>
                        <BaseControl
                            label={ __( 'Subscription Plan', 'paid-member-subscriptions' ) }
                            id="pms-link-generator-subscription-plan"
                        >
                            <select
                                value={ attributes.subscription_plan || '' }
                                onChange={ e => changeAttributes({ subscription_plan: e.target.value }) }
                                id="pms-link-generator-subscription-plan-select"
                                className="components-select-control__input"
                            >
                                <option value="none">{ __( 'Select a plan', 'paid-member-subscriptions' ) }</option>
                                { subscriptionPlans?.map( subscriptionPlan => {
                                    return (
                                        <option key={ subscriptionPlan.id } value={ subscriptionPlan.id }>{ decodeEntities( subscriptionPlan.name ) }</option>
                                    );
                                }) }
                            </select>
                        </BaseControl>
                        <BaseControl
                            label={ __( 'Page', 'paid-member-subscriptions' ) }
                            id="pms-link-generator-page"
                        >
                            <select
                                value={ attributes.p || '' }
                                onChange={ e => changeAttributes({ p: e.target.value }) }
                                id="pms-link-generator-page-select"
                                className="components-select-control__input"
                            >
                                { ( () => {
                                    if ( !registerPageID ) {
                                        return (
                                            <option value="none">{ __( 'Select a page', 'paid-member-subscriptions' ) }</option>
                                        );
                                    }
                                } )() }
                                { pages?.map( page => {
                                    return (
                                        <option key={ page.id } value={ page.id }>{ decodeEntities( page.title.rendered ) }</option>
                                    );
                                }) }
                            </select>
                        </BaseControl>
                        <Fragment>
                            <br/>
                            <ToggleControl
                                label={ __( 'Exclude other plans', 'paid-member-subscriptions' ) }
                                checked={ 'yes' === attributes.single_plan || false }
                                onChange={ () => changeAttributes({ single_plan: 'yes' === attributes.single_plan ? undefined : 'yes' }) }
                            />
                        </Fragment>
                        <br/>
                        <ExternalLink
                            target="_blank"
                            rel="noopener noreferrer"
                            href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/shortcodes/#Pre-select_a_subscription_plan_through_an_URL_parameter"
                        >
                            { __( 'Documentation', 'paid-member-subscriptions' ) }
                        </ExternalLink>
                    </PanelBody>
                    <PanelBody>
                        <ButtonGroup>
                            <Button
                                isPrimary
                                variant="primary"
                                disabled={ isEmpty( attributes ) || isEqual( attributes, activeAttributes ) }
                                onClick={ onChange }
                            >
                                { __( 'Apply', 'paid-member-subscriptions' ) }
                            </Button>
                            <Button
                                isDestructive
                                variant="tertiary"
                                onClick={ onRemove }
                            >
                                { __( 'Delete', 'paid-member-subscriptions' ) }
                            </Button>
                        </ButtonGroup>
                    </PanelBody>
                </Popover>
            ) }
        </Fragment>
    );
};

/**
 * Add Custom Button to Paragraph Toolbar
 */
const pmsToolbarButton = createHigherOrderComponent( ( BlockEdit ) => {
    return ( props ) => {

        // If current block is not allowed
        if ( Object.keys( enableLinkGeneratorOnBlocks ).includes( props.name ) ) {
            return (
                <LinkGenerator props={ props }>
                    <BlockEdit { ...props } />
                </LinkGenerator>
            );
        }

        return (
            <BlockEdit { ...props } />
        );
    };
}, 'pmsToolbarButton' );

addFilter(
    'editor.BlockEdit',
    'paid-member-subscriptions/with-toolbar-button',
    pmsToolbarButton
);
