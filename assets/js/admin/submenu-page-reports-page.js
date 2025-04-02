/*
 * JavaScript for Reports Submenu Page
 *
 */
jQuery( function($) {

    var ctx = $('#payment-report-chart');

    if ( ctx.length > 0 ) {

        var axis_id = 0, datasets = [], hidden = true

        const earningsColors = {
            border: 'rgba(45, 152, 218, 0.8)',
            background: 'rgba(45, 152, 218, 0.2)',
            point: 'rgba(45, 152, 218, 1)'
        };
        
        const paymentsColors = {
            border: 'rgba(52, 191, 163, 0.8)',
            background: 'rgba(52, 191, 163, 0.2)',
            point: 'rgba(52, 191, 163, 1)'
        };

        for ( var currency in pms_chart_data ) {

            if( currency === pms_default_currency )
                hidden = false
            else
                hidden = true

            datasets.push( {
                label               : 'Earnings ' + currency,
                yAxisID             : 'y',
                borderColor         : earningsColors.border,
                backgroundColor     : earningsColors.background,
                pointBackgroundColor: earningsColors.point,
                pointBorderColor    : '#fff',
                pointRadius         : 4,
                pointHoverRadius    : 6,
                lineTension         : 0.2,
                data                : pms_chart_data[currency]['earnings'],
                hidden              : hidden,
            } )

            axis_id = axis_id + 1

            datasets.push( {
                label               : 'Payments ' + currency,
                yAxisID             : 'y1',
                borderColor         : paymentsColors.border,
                backgroundColor     : paymentsColors.background,
                pointBackgroundColor: paymentsColors.point,
                pointBorderColor    : '#fff',
                pointRadius         : 4,
                pointHoverRadius    : 6,
                lineTension         : 0.2,
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
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(50, 50, 50, 0.9)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        bodySpacing: 4,
                        padding: {
                            x: 12,
                            y: 12
                        },
                        cornerRadius: 4,
                        callbacks: {
                            label: function(context) {
                                const datasetIndex = context.datasetIndex;
                                const value        = context.dataset.data[context.dataIndex + 1];

                                if (datasetIndex === 0)
                                    return datasets[0].label + ' (' + pms_default_currency_symbol + ') : ' + value;
                                    
                                return datasets[datasetIndex].label + ' : ' + value;
                            }
                        }
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 40,
                            boxWidth: 30,
                            usePointStyle: true,
                            color: '#666'
                        }
                    }
                },
                scales: {
                    y: {
                        display: true,
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Earnings'
                        },
                        ticks: {
                            beginAtZero: true
                        },
                        grid: {
                            color: 'rgba(200, 200, 200, 0.15)'
                        }
                    },
                    y1: {
                        display: true,
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Payments'
                        },
                        ticks: {
                            beginAtZero: true,
                            stepSize: 1
                        },
                        grid: {
                            drawOnChartArea: false,
                            color: 'rgba(200, 200, 200, 0.15)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(200, 200, 200, 0.15)'
                        },
                        ticks: {
                            color: '#888'
                        }
                    }
                },
                animation: {
                    duration: 1500
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
