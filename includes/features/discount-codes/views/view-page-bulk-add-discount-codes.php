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
        <h3 class="cozmoslabs-page-title"><?php echo esc_html( $this->page_title ); ?></h3>
    </div>

    <div id="dashboard-widgets-wrap">
        <div class="metabox-holder">
            <div id="post-body">
                <div id="post-body-content">


                    <div class="postbox cozmoslabs-form-subsection-wrapper">
                        <h3 class="cozmoslabs-subsection-title"><span><?php esc_html_e( 'Import Discount Codes', 'paid-member-subscriptions' ); ?></span></h3>
                        <p class="cozmoslabs-description"><?php esc_html_e( 'Quickly create multiple discount codes by importing them.', 'paid-member-subscriptions' ); ?></p>

                        <div class="inside">
                            <form id="pms-bulk-add-discount-codes" class="pms-bulk-add-discount-codes-form" method="post" enctype="multipart/form-data">
                                <?php wp_nonce_field( 'pms_bulk_add_discount_codes', 'pms_nonce' ); ?>

                                <div class="cozmoslabs-form-field-wrapper">
                                    <label class="cozmoslabs-form-field-label" for="bulk-add-discount-codes"><?php esc_html_e('Upload Discount Codes', 'paid-member-subscriptions'); ?></label>

                                    <input type="file" id="bulk-add-discount-codes" name="pms_bulk_add_discount_codes" />

                                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e('Upload Discount Codes via a CSV file. Use this to select a csv file, then to upload click the "Import Discount Codes" button.', 'paid-member-subscriptions')?> </p>
                                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><a href="<?php echo esc_url( apply_filters( 'pms_bulk_import_discount_codes_sample_file_url', PMS_IN_DC_PLUGIN_DIR_URL . 'sample-data/pms-bulk-import-discount-codes-sample-file.csv' ) ); ?>"> <?php esc_html_e('Download this sample discount codes files', 'paid-member-subscriptions')?></a> <?php esc_html_e(' and modify it by adding your own discounts.','paid-member-subscriptions')?></p>
                                </div>

                                <div>
                                    <input type="submit" class="button-primary" value="<?php esc_html_e( 'Import Discount Codes', 'paid-member-subscriptions' ); ?>"/>
								</div>
                            </form>
                        </div><!-- .inside -->
                    </div><!-- .postbox -->


                </div><!-- .post-body-content -->
            </div><!-- .post-body -->
        </div><!-- .metabox-holder -->

    </div><!-- #dashboard-widgets-wrap -->

    <?php do_action( 'pms_export_page_bottom' ); ?>

</div>
