/*
 * JavaScript for Reports Submenu Page
 *
 */
jQuery( function($) {

    var ctx = $('#payment-report-chart');

    if ( ctx.length > 0 ) {

        var axis_id = 0, datasets = [], hidden = true

        for ( var currency in pms_chart_data ) {

            if( currency === pms_default_currency )
                hidden = false
            else
                hidden = true

            datasets.push( {
                label               : 'Earnings ' + currency,
                yAxisID             : 'y',
                borderColor         : 'rgba(39,174,96,0.5)',
                backgroundColor     : 'rgba(39,174,96,0.1)',
                pointBackgroundColor: 'rgba(39,174,96,1)',
                lineTension         : 0,
                data                : pms_chart_data[currency]['earnings'],
                hidden              : hidden,
            } )

            axis_id = axis_id + 1

            datasets.push( {
                label               : 'Payments ' + currency,
                yAxisID             : 'y1',
                borderColor         : 'rgba(230,126,34,0.5)',
                backgroundColor     : 'rgba(230,126,34,0.1)',
                pointBackgroundColor: 'rgba(230,126,34,1)',
                lineTension         : 0,
                data                : pms_chart_data[currency]['payments'],
                hidden              : hidden,
            } )

        }


        var paymentReportsChart = new Chart( ctx, {
            type : 'line',
            data : {
                ...(typeof pms_chart_labels !== 'undefined' ? { labels: pms_chart_labels } : {}),
                datasets : datasets
            },
            options : {

                responsive : true,

                // Tooltips
                tooltips : {
                    mode : 'x-axis',
                    callbacks : {
                        label : function( tooltipItem, data ) {

                            if( tooltipItem.datasetIndex == 0 )
                                return data.datasets[0].label + ' (' + pms_default_currency_symbol + ')' +  ' : ' + data.datasets[0].data[tooltipItem.index];

                            return data.datasets[tooltipItem.datasetIndex].label + ' : ' + data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];

                        }
                    }
                },

                // Legend
                legend : {
                    position : 'bottom',
                    labels : {
                        padding: 40,
                        boxWidth : 30
                    }
                },

                // Two y-axes for the revenue and for the payments count
                scales : {
                    y : {
                        display : true,
                        type : 'linear',
                        position: 'right',
                        id : 'y-axis-earnings',
                        ticks : {
                            beginAtZero : true
                        }
                    },
                    y1 : {
                        display : true,
                        type : 'linear',
                        position: 'left',
                        id : 'y-axis-payments',
                        ticks : {
                            beginAtZero : true,
                            stepSize : 1
                        },
                        grid : {
                            drawOnChartArea : false
                        }
                    }
                }
            }
        });

    }


    $('#pms-reports-filter-month').on('change', function(){
        if ( $(this).val() === 'custom_date' )
            $('.pms-custom-date-range-options').show();
        else
            $('.pms-custom-date-range-options').hide();
    });

    // Date picker for report start and expiration date

    $(document).ready( function() {
        $("input.pms_datepicker").datepicker({dateFormat: 'yy-mm-dd'});
    });

    /*
   * Initialise chosen
   *
   */
    if( $.fn.chosen != undefined ) {
        $('.pms-chosen').chosen();
    }

    // General and Subscription Plans links

    // $('.pms-subscription-plans-section').hide();
    // $('.pms-subscription-plans-section-previous').hide();
    //
    // $('.pms-discount-codes-section').hide();
    // $('.pms-discount-codes-section-previous').hide();
    //
    // $('.pms-other-currencies-section').hide();
    // $('.pms-other-currencies-section-previous').hide();

    $('#pms-general-link').addClass('active');
    $('#pms-general-link-previous').addClass('active');

    $('#pms-general-link').click(function(){

        $('.present .inside a').removeClass('active');
        $(this).addClass('active');

        $('.pms-subscription-plans-section').hide();
        $('.pms-discount-codes-section').hide();
        $('.pms-other-currencies-section').hide();
        $('.pms-general-section').show();
    });

    $('#pms-general-link-previous').click(function(){

        $('.previous .inside a').removeClass('active');
        $(this).addClass('active');

        $('.pms-subscription-plans-section-previous').hide();
        $('.pms-discount-codes-section-previous').hide();
        $('.pms-other-currencies-section-previous').hide();
        $('.pms-general-section-previous').show();
    });

    $('#pms-subscription-plans-link').click(function(){

        $('.present .inside a').removeClass('active');
        $(this).addClass('active');

        $('.pms-general-section').hide();
        $('.pms-discount-codes-section').hide();
        $('.pms-other-currencies-section').hide();
        $('.pms-subscription-plans-section').show();
    });

    $('#pms-subscription-plans-link-previous').click(function(){

        $('.previous .inside a').removeClass('active');
        $(this).addClass('active');

        $('.pms-general-section-previous').hide();
        $('.pms-discount-codes-section-previous').hide();
        $('.pms-other-currencies-section-previous').hide();
        $('.pms-subscription-plans-section-previous').show();
    });

    $('#pms-discount-codes-link').click(function(){
        $('.present .inside a').removeClass('active');
        $(this).addClass('active');

        $('.pms-general-section').hide();
        $('.pms-subscription-plans-section').hide();
        $('.pms-other-currencies-section').hide();
        $('.pms-discount-codes-section').show();
    });

    $('#pms-discount-codes-link-previous').click(function(){
        $('.previous .inside a').removeClass('active');
        $(this).addClass('active');

        $('.pms-general-section-previous').hide();
        $('.pms-subscription-plans-section-previous').hide();
        $('.pms-other-currencies-section-previous').hide();
        $('.pms-discount-codes-section-previous').show();
    });

    $('#pms-other-currencies-link').click(function(){
        $('.present .inside a').removeClass('active');
        $(this).addClass('active');

        $('.pms-general-section').hide();
        $('.pms-subscription-plans-section').hide();
        $('.pms-discount-codes-section').hide();
        $('.pms-other-currencies-section').show();
    });

    $('#pms-other-currencies-link-previous').click(function(){
        $('.previous .inside a').removeClass('active');
        $(this).addClass('active');

        $('.pms-general-section-previous').hide();
        $('.pms-subscription-plans-section-previous').hide();
        $('.pms-discount-codes-section-previous').hide();
        $('.pms-other-currencies-section-previous').show();
    });
});