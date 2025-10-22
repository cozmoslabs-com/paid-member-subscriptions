<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for the reports admin page
 */
?>

<div class="wrap cozmoslabs-wrap">

    <h1></h1>
    <!-- WordPress Notices are added after the h1 tag -->

    <div class="cozmoslabs-page-header">
        <div class="cozmoslabs-section-title">
            <h3 class="cozmoslabs-page-title"><?php echo esc_html( $this->page_title ); ?></h3>
            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/reports/?utm_source=pms-reports&utm_medium=client-site&utm_campaign=pms-reports-docs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
        </div>
    </div>

    <h2 class="cozmoslabs-nav-tab-wrapper">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=pms-reports-page' ) ); ?>" class="nav-tab <?php echo $active_tab == 'pms-reports-page' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Reports', 'paid-member-subscriptions' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=pms-export-page' ) ); ?>"  class="nav-tab <?php echo $active_tab == 'pms-export-page' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Export', 'paid-member-subscriptions' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=pms-import-page' ) ); ?>"  class="nav-tab <?php echo $active_tab == 'pms-import-page' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Import', 'paid-member-subscriptions' ); ?></a>
        <?php do_action( 'pms_reports_tab' ); ?>
    </h2>

    <form id="pms-form-reports" class="pms-form" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get">
        <input type="hidden" name="page" value="pms-reports-page" />

        <!-- Filter box -->
        <div class="postbox cozmoslabs-form-subsection-wrapper">
            <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Filters', 'paid-member-subscriptions' ); ?></h4>
            <div class="inside">
                    <div class="" style="margin-top: 20px !important; margin-bottom: 20px !important;">

                    <?php do_action( 'pms_reports_filters' ); ?>

                    </div>
                <button name="pms-action" type="submit" class="button-secondary" value="filter_results"><?php echo esc_html__( 'Filter', 'paid-member-subscriptions' ); ?></button>
            </div>
        </div>

        <!-- Chart and details -->
        <div class="postbox cozmoslabs-form-subsection-wrapper">
            <div class="inside"">
            <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Sales and earnings', 'paid-member-subscriptions' ); ?></h4>
                <div class="cozmoslabs-form-field-wrapper">
                    <canvas id="<?php echo esc_html( apply_filters( 'pms_reports_chart_id', 'payment-report-chart' ) ); ?>" width="1000" height="250"></canvas>
                </div>
            </div>
        </div>

        <?php do_action( 'pms_reports_form_bottom' ); ?>

        <?php wp_nonce_field( 'pms_reports_nonce', '_wpnonce', false ); ?>

    </form>

    <?php do_action( 'pms_reports_page_bottom' ); ?>

</div>
