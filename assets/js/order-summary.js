/**
 * Order Summary frontend pipeline
 *
 * - on every recalculation, the runner builds a fresh summaryData object and passes it through a chain of contributors
 * - contributors are functions registered at a numeric priority; lower priorities run first
 * - each contributor reads what previous contributors wrote and adds its own piece (price, tax, items, etc.)
 * - the final contributor at p100 is the renderer, which is the only place that writes to the summary table in the DOM
 *
 */
( function( $ ) {

    'use strict';


    /**
     * Pipeline runner exposed on window for cross-script registration
     *
     */
    let pmsOrderSummary = {

        handlers: [],


        /**
         * Registers a contributor at the given priority
         *
         * - keeps handlers sorted so callers can rely on execution order
         *
         */
        register: function( priority, fn ) {

            this.handlers.push( { priority: priority, fn: fn } );
            this.handlers.sort( function( a, b ) { return a.priority - b.priority; } );

        },


        /**
         * Builds a fresh summaryData object and runs all contributors in priority order
         *
         */
        recalculate: function( $form ) {

            if( !$form || !$form.length )
                return;

            if( !$form.find( '.pms-price-breakdown__holder' ).length )
                return;

            // The summaryData object is the running state of the calculation
            //
            // - each contributor mutates it; the renderer at p100 paints the final values
            // - properties are populated progressively:
            //   - primaryItem: the selected subscription plan (label, amount, planId, jQuery handle)
            //   - items:       additional line items pushed by add-ons (e.g. order bumps)
            //   - discounts:   negative-amount entries (currently unused; PMS bakes discounts into plan data attrs)
            //   - subtotal:    sum of primaryItem + items - discounts
            //   - tax:         { rate, amount, label, inclusive } populated by the Tax addon when active
            //   - total:       subtotal + tax.amount
            //   - formatPrice: the formatter used when painting amounts
            let summaryData = {
                form:        $form,
                primaryItem: { label: '', amount: 0, planId: 0, $plan: null },
                items:       [],
                discounts:   [],
                subtotal:    0,
                tax:         { rate: 0, amount: 0, label: '', inclusive: false },
                total:       0,
                formatPrice: pmsOrderSummary.formatPrice
            };

            this.handlers.forEach( function( handler ) {
                handler.fn( summaryData, $form );
            } );

        },


        /**
         * Formats a price using the active PMS currency settings
         *
         */
        formatPrice: function( amount ) {

            let decimals        = isZeroDecimalCurrency( pms_order_summary.currency ) ? 0 : 2,
                priceTrimZeroes = pms_order_summary.price_trim_zeroes === 'true',
                separator       = pms_order_summary.currency_position === 'before_with_space' || pms_order_summary.currency_position === 'after_with_space' ? ' ' : '';

            let formatter = new Intl.NumberFormat( pms_order_summary.locale, {
                maximumFractionDigits: decimals,
                minimumFractionDigits: priceTrimZeroes ? 0 : decimals
            } );

            let formatted = formatter.format( amount );

            if( pms_order_summary.currency_position === 'before' || pms_order_summary.currency_position === 'before_with_space' )
                return pms_order_summary.currency_symbol + separator + formatted;

            return formatted + separator + pms_order_summary.currency_symbol;

        }

    };

    window.pmsOrderSummary = pmsOrderSummary;


    /**
     * Returns the selected subscription plan input
     *
     */
    function getSelectedSubscriptionPlan( $form ) {

        let $selectedPlan = $form.find( 'input[name="subscription_plans"][type="radio"]:checked' );

        // some forms render a single available plan as a hidden input
        if( !$selectedPlan.length )
            $selectedPlan = $form.find( 'input[name="subscription_plans"][type="hidden"]' );

        return $selectedPlan;

    }


    /**
     * Returns a numeric value from a plan data attribute
     *
     */
    function getNumericPlanData( $plan, key ) {

        let value = $plan.data( key );

        return typeof value !== 'undefined' && value !== null && value !== '' ? parseFloat( value ) : 0;

    }


    /**
     * Checks if the form is in a checkout-shaped flow (register, new subscription, retry, upgrade)
     *
     * - shared gate for both sign-up fee and trial-amount handling in p16
     *
     */
    function isCheckoutFormLocation( $plan, $form ) {

        let locations                = [ 'pms_register', 'pms_new_subscription', 'pms_confirm_retry_payment_subscription', 'register', 'pms_upgrade_subscription' ],
            submitName               = $form.find( '.pms-form-submit' ).attr( 'name' ),
            newSubscriptionName      = $form.find( 'input[name="pms_new_subscription"]' ).attr( 'name' ),
            profileBuilderSubmitName = $form.closest( '.wppb-user-forms' ).find( '.form-submit input[type="submit"]' ).attr( 'name' ),
            upgradeSubmitName        = $form.find( 'input[name="pms_upgrade_subscription"]' ).attr( 'name' ),
            retryPaymentSubmitName   = $form.find( 'input[name="pms_confirm_retry_payment_subscription"]' ).attr( 'name' ),
            hasCheckoutSubmit        = locations.indexOf( submitName ) !== -1 || locations.indexOf( newSubscriptionName ) !== -1 || locations.indexOf( profileBuilderSubmitName ) !== -1 || locations.indexOf( upgradeSubmitName ) !== -1 || locations.indexOf( retryPaymentSubmitName ) !== -1,
            isUpgradeChangeGroup     = $plan.closest( '.pms-subscription-plan' ).parent().hasClass( 'pms-upgrade__group--change' ) || $plan.closest( '.pms-subscription-plan' ).parent().hasClass( 'pms-upgrade__group--upgrade' );

        return hasCheckoutSubmit || isUpgradeChangeGroup;

    }


    /**
     * Checks if sign-up fees apply in the current checkout flow
     *
     * - matches the existing PMS sign-up fee rules used by Tax and gateway JS
     *
     */
    function shouldApplySignUpFee( $plan, $form ) {

        if( !isCheckoutFormLocation( $plan, $form ) )
            return false;

        if( getNumericPlanData( $plan, 'sign_up_fee' ) <= 0 )
            return false;

        return !$plan.data( 'discounted-price' ) || ( $plan.data( 'discounted-price' ) === 'false' && getNumericPlanData( $plan, 'sign_up_fee' ) > 0 );

    }


    /**
     * Returns the plan label for a summary item row
     *
     * - single-plan flows (retry_payment, single-plan upgrade) render the plan as a hidden input outside the &lt;label&gt; tag, so the closest-label lookup misses the name span; fall back to the .pms-subscription-plan wrapper which always contains it in both layouts
     *
     */
    function getPlanLabel( $plan ) {

        let label = $plan.closest( 'label' ).find( '.pms-subscription-plan-name' ).first().text();

        if( !label )
            label = $plan.closest( '.pms-subscription-plan' ).find( '.pms-subscription-plan-name' ).first().text();

        return label ? label.trim() : pms_order_summary.default_item_label;

    }


    /**
     * Checks if a currency has no decimal places
     *
     */
    function isZeroDecimalCurrency( currency ) {

        if( !pms_order_summary || !pms_order_summary.zero_decimal_currencies )
            return false;

        return pms_order_summary.zero_decimal_currencies.indexOf( String( currency ).toUpperCase() ) !== -1;

    }


    /**
     * Builds a single summary item row
     *
     */
    function buildItemRow( label, amount, summaryData ) {

        return $( '<tr class="pms-order-summary__item"></tr>' )
            .append( $( '<td class="pms-label"></td>' ).text( label ) )
            .append( $( '<td class="pms-value"></td>' ).text( summaryData.formatPrice( amount ) ) );

    }


    /**
     * Renders the summary to the DOM
     *
     * - the only place in the pipeline that writes to the summary table
     *
     */
    function renderSummary( summaryData, $form ) {

        let $summary = $form.find( '.pms-price-breakdown__holder' );

        if( !$summary.length )
            return;

        let $items     = $summary.find( '.pms-order-summary__items' ),
            $subtotal  = $summary.find( '.pms-order-summary__subtotal-row' ),
            $taxRow    = $summary.find( '.pms-order-summary__tax-row' ),
            $totalRow  = $summary.find( '.pms-order-summary__total-row' );

        // rebuild item rows on every render so hidden or deselected plans do not linger
        $items.empty();

        if( summaryData.primaryItem.label )
            $items.append( buildItemRow( summaryData.primaryItem.label, summaryData.primaryItem.amount, summaryData ) );

        summaryData.items.forEach( function( item ) {
            $items.append( buildItemRow( item.label, item.amount, summaryData ) );
        } );

        summaryData.discounts.forEach( function( d ) {
            $items.append( buildItemRow( d.label, -d.amount, summaryData ) );
        } );

        // value cells
        $summary.find( '.pms-subtotal__value' ).text( summaryData.formatPrice( summaryData.subtotal ) );
        $summary.find( '.pms-tax__value' ).text( summaryData.formatPrice( summaryData.tax.amount ) );
        $summary.find( '.pms-total__value' ).text( summaryData.formatPrice( summaryData.total ) );

        // tax contributors can override the label to include rate/name (e.g. "20% VAT:")
        if( summaryData.tax.label )
            $summary.find( '.pms-tax__label' ).text( summaryData.tax.label );

        // visibility rules
        let hasModifiers = summaryData.tax.amount > 0 || summaryData.discounts.length > 0;

        $subtotal.toggle( hasModifiers );
        $taxRow.toggle( summaryData.tax.amount > 0 );
        $totalRow.show();

        $summary.show();

    }


    /*
     * Core handlers
     *
     * - each handler is a function registered at a numeric priority via pmsOrderSummary.register( priority, fn )
     * - the runner sorts by priority ascending, so a handler at p10 always runs before one at p20
     * - the "pXX" prefix in handler docblocks below is just shorthand for "this contributor runs at priority XX"
     * - lower priorities populate base data; higher priorities consume what came before
     *
     * Reserved priority slots:
     * - p10 - p16 : core plan-related (base price, PWYW, prorate, sign-up fee)
     * - p20       : Order Bumps line items (registered by the Order Bumps add-on)
     * - p30       : reserved for discount codes (currently unused; PMS bakes discounts into plan data attrs)
     * - p40       : subtotal aggregation
     * - p50       : Tax addon contributor (registered by the Tax add-on)
     * - p60       : total aggregation
     * - p100      : DOM render (the only place that touches the summary table)
     *
     */


    /**
     * p10 - reads the base price for the selected primary subscription plan
     *
     */
    pmsOrderSummary.register( 10, function( summaryData ) {

        let $plan = getSelectedSubscriptionPlan( summaryData.form );

        if( !$plan.length )
            return;

        summaryData.primaryItem.$plan  = $plan;
        summaryData.primaryItem.planId = parseInt( $plan.val(), 10 ) || 0;
        summaryData.primaryItem.label  = getPlanLabel( $plan );
        summaryData.primaryItem.amount = getNumericPlanData( $plan, 'mc_price' ) || getNumericPlanData( $plan, 'price' );

    } );


    /**
     * p12 - applies Pay-What-You-Want override
     *
     * - replaces the base amount with the user-entered value when the plan is PWYW-enabled
     *
     */
    pmsOrderSummary.register( 12, function( summaryData ) {

        let $plan = summaryData.primaryItem.$plan;

        if( !$plan || !$plan.length )
            return;

        if( !$plan.data( 'mc_pwyw' ) )
            return;

        let pwywValue = summaryData.form.find( 'input[name="subscription_price_' + $plan.val() + '"]' ).val();

        summaryData.primaryItem.amount = parseFloat( pwywValue ) || 0;

    } );


    /**
     * p14 - applies prorate adjustments for upgrade and recurring flows
     *
     * - replaces the amount with discountedPriceValue or original_price when prorate conditions match
     *
     */
    pmsOrderSummary.register( 14, function( summaryData ) {

        let $plan = summaryData.primaryItem.$plan;

        if( !$plan || !$plan.length )
            return;

        if( typeof $.pms_plan_is_prorated !== 'function' || !$.pms_plan_is_prorated( $plan ) )
            return;

        if( typeof $.pms_checkout_is_recurring !== 'function' || !$.pms_checkout_is_recurring( $plan ) )
            return;

        if( getNumericPlanData( $plan, 'original_price' ) <= 0 )
            return;

        if( typeof $plan.data( 'discountedPriceValue' ) !== 'undefined' && typeof $plan.data( 'discountRecurringPayments' ) !== 'undefined' && String( $plan.data( 'discountRecurringPayments' ) ) === '1' ) {
            summaryData.primaryItem.amount = getNumericPlanData( $plan, 'discountedPriceValue' );
            return;
        }

        summaryData.primaryItem.amount = getNumericPlanData( $plan, 'original_price' );

    } );


    /**
     * p16 - applies sign-up fee and trial rules
     *
     * - trial replaces the amount with the sign-up fee (or 0 when no fee), mirroring the server-side initial charge of pms_calculate_payment_amount
     * - no trial adds the fee on top of the existing amount
     *
     */
    pmsOrderSummary.register( 16, function( summaryData ) {

        let $plan = summaryData.primaryItem.$plan;

        if( !$plan || !$plan.length )
            return;

        if( !isCheckoutFormLocation( $plan, summaryData.form ) )
            return;

        let hasTrial  = $plan.data( 'trial' ) && String( $plan.data( 'trial' ) ) === '1',
            signUpFee = shouldApplySignUpFee( $plan, summaryData.form ) ? ( getNumericPlanData( $plan, 'mc_sign_up_fee' ) || getNumericPlanData( $plan, 'sign_up_fee' ) ) : 0;

        if( hasTrial ) {
            summaryData.primaryItem.amount = signUpFee;
            return;
        }

        if( signUpFee > 0 )
            summaryData.primaryItem.amount += signUpFee;

    } );


    /**
     * p40 - sums primary item, additional items, and discounts into the subtotal
     *
     */
    pmsOrderSummary.register( 40, function( summaryData ) {

        let subtotal = parseFloat( summaryData.primaryItem.amount ) || 0;

        summaryData.items.forEach( function( item ) {
            subtotal += parseFloat( item.amount ) || 0;
        } );

        summaryData.discounts.forEach( function( d ) {
            subtotal -= parseFloat( d.amount ) || 0;
        } );

        summaryData.subtotal = subtotal;

    } );


    /**
     * p60 - computes the final total from subtotal and tax
     *
     * - tax contributors that handle inclusive pricing must adjust summaryData.subtotal so this universal formula stays correct
     *
     */
    pmsOrderSummary.register( 60, function( summaryData ) {

        summaryData.total = summaryData.subtotal + ( parseFloat( summaryData.tax.amount ) || 0 );

    } );


    /**
     * p100 - paints the summary to the DOM
     *
     */
    pmsOrderSummary.register( 100, function( summaryData, $form ) {

        renderSummary( summaryData, $form );

    } );


    /*
     * Event triggers
     *
     */

    $( document ).on( 'change', 'input[name="subscription_plans"]', function() {

        pmsOrderSummary.recalculate( $( this ).closest( 'form' ) );

    } );

    $( document ).on( 'keyup change', '.pms_pwyw_pricing', function() {

        pmsOrderSummary.recalculate( $( this ).closest( 'form' ) );

    } );

    $( document ).on( 'pms_discount_success pms_discount_error', function() {

        $( '.pms-price-breakdown__holder' ).each( function() {
            pmsOrderSummary.recalculate( $( this ).closest( 'form' ) );
        } );

    } );

    $( document ).on( 'pms_order_summary_recalc', function( event, $form ) {

        pmsOrderSummary.recalculate( $form );

    } );

    $( function() {

        // populate any summary already on the page
        //
        // - defer one tick so add-on contributors that register inside their own document.ready handlers
        //   (e.g. Tax, Order Bumps) land in the pipeline before this initial recalc runs
        // - without this defer, core OS's ready callback fires first (registered first by load order)
        //   and the initial recalc misses any contributors that register later in the same tick
        setTimeout( function() {
            $( '.pms-price-breakdown__holder' ).each( function() {
                pmsOrderSummary.recalculate( $( this ).closest( 'form' ) );
            } );
        }, 0 );

    } );

} )( jQuery );
