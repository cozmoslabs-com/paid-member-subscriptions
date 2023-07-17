<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for the labels edit admin page
 */
?>

<div class="wrap cozmoslabs-wrap">

    <h1></h1>
    <!-- WordPress Notices are added after the h1 tag -->

    <div class="cozmoslabs-page-header">
        <div class="cozmoslabs-section-title">

            <h3 class="cozmoslabs-page-title">
                <?php echo esc_html( $this->page_title ); ?>
                <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/labels-edit/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
            </h3>

        </div>
    </div>

    <input type="hidden" name="page" value="pms-labels-edit" />

        <div id="pmsle-rescan" class="cozmoslabs-form-subsection-wrapper">

            <h2 class="cozmoslabs-subsection-title"><?php echo esc_html__( 'Rescan Labels', 'paid-member-subscriptions' ); ?></h2>

            <?php $this->rescan_metabox(); ?>

        </div>

        <div id="pmsle-info" class="cozmoslabs-form-subsection-wrapper">

            <h2 class="cozmoslabs-subsection-title"><?php echo esc_html__( 'Information', 'paid-member-subscriptions' ); ?></h2>

            <?php $this->info_metabox(); ?>

        </div>

    <div id="pmsle" class="cozmoslabs-form-subsection-wrapper">

        <h2 class="cozmoslabs-subsection-title"><?php echo esc_html__( 'Edit Labels', 'paid-member-subscriptions' ); ?></h2>

        <?php $this->edit_labels_metabox(); ?>

    </div>

    <div id="pmsle-import-export" class="cozmoslabs-form-subsection-wrapper">

        <h2 class="cozmoslabs-subsection-title"><?php echo esc_html__( 'Import and Export Labels', 'paid-member-subscriptions' ); ?></h2>

        <?php $this->import_export_metabox(); ?>

    </div>

</div>
