/**
 * JavaScript for Subscriptions Submenu Page
 *
 */
jQuery( function($) {

    // Initialize datepicker
    $(document).on( 'focus', '.datepicker', function() {
        $(this).datepicker({ dateFormat: 'yy-mm-dd'});
    });

    function pmsGetBulkAction( button_id ) {
        return ( button_id === 'doaction2' ? $('#bulk-action-selector-bottom').val() : $('#bulk-action-selector-top').val() );
    }

    function pmsHasSelectedSubscriptions() {
        return $('input[name="member_subscriptions[]"]:checked').length > 0;
    }

    // Validate and confirm bulk actions.
    $(document).off( 'click', '#doaction, #doaction2' ).on( 'click', '#doaction, #doaction2', function(e){
        let action = pmsGetBulkAction( this.id );

        if( action !== 'pms_subscriptions_bulk_delete' && action !== 'pms_subscriptions_bulk_cancel' )
            return true;

        if( ! pmsHasSelectedSubscriptions() ) {
            alert( pms_subscriptions_delete_confirmation_message.no_selection );
            return false;
        }

        if( action === 'pms_subscriptions_bulk_delete' ) {
            let message = pms_subscriptions_delete_confirmation_message.delete_confirmation.split("\\n").join("\n");
            return confirm( message );
        }

        return true;
    });

    // Display confirmation prompt on row delete subscription action
    $(document).off( 'click', '.pms-subscriptions-row-delete-action' ).on( 'click', '.pms-subscriptions-row-delete-action', function(e){
        let message = pms_subscriptions_delete_confirmation_message.delete_confirmation.split("\\n").join("\n");
        return confirm(message);
    });

    /**
     * Clean URL args when filtering, search or bulk action is applied
     *
     */
    function pmsCleanListTableQueryArgs() {
        $('#bulk-action-selector-top, #bulk-action-selector-bottom, input[name="_wpnonce"]').prop('disabled', true);

        $('.pms-subscriptions-filter :input[name]').each(function(){
            if( $(this).val() === '' )
                $(this).prop('disabled', true);
        });

        if( $('input[name="s"]').val() === '' )
            $('input[name="s"]').prop('disabled', true);
    }

    $(document).on( 'click', '#pms-filter-button', function(){
        pmsCleanListTableQueryArgs();
    });

    $(document).on( 'click', '#search-submit', function(){
        pmsCleanListTableQueryArgs();
    });

    $(document).on( 'keydown', '#pms_search_subscriptions-search-input', function(e){
        if( e.key === 'Enter' )
            pmsCleanListTableQueryArgs();
    });

    /**
     * Display clear filters link when at least one filter is active
     *
     */
    function pmsToggleClearFiltersLink() {
        let $filter_fields = $('.pms-subscriptions-filter :input'),
            has_active_filters = false;

        $filter_fields.each(function() {
            if( $(this).val() !== '' ) {
                has_active_filters = true;
                return false;
            }
        });

        $('#pms-filter-clear-filters').css('visibility', has_active_filters ? 'visible' : 'hidden');
    }

    pmsToggleClearFiltersLink();

    $(document).on( 'change', '.pms-subscriptions-filter :input', function(e){
        pmsToggleClearFiltersLink();
    });

    // Show/hide custom Start Date range fields
    if( $('#pms-filter-start-date').val() == 'custom' )
        $('#pms-start-date-interval').show();

    $('#pms-filter-start-date').change(function(e){
        if( $('#pms-filter-start-date').val() == 'custom' )
            $('#pms-start-date-interval').show();
        else
            $('#pms-start-date-interval').hide();
    });

    // Show/hide custom Expiration Date range fields
    if( $('#pms-filter-expiration-date').val() == 'custom' )
        $('#pms-expiration-date-interval').show();

    $('#pms-filter-expiration-date').change(function(e){
        if( $('#pms-filter-expiration-date').val() == 'custom' )
            $('#pms-expiration-date-interval').show();
        else
            $('#pms-expiration-date-interval').hide();
    });

    // Show/hide custom Next Billing Date range fields
    if( $('#pms-filter-next-billing-date').val() == 'custom' )
        $('#pms-next-billing-date-interval').show();

    $('#pms-filter-next-billing-date').change(function(e){
        if( $('#pms-filter-next-billing-date').val() == 'custom' )
            $('#pms-next-billing-date-interval').show();
        else
            $('#pms-next-billing-date-interval').hide();
    });

});
