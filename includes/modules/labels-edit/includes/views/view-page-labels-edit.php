<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for the labels edit admin page
 */
?>

<div class="wrap">

    <h1>
        <?php echo esc_html( $this->page_title ); ?>
    </h1>
        <input type="hidden" name="page" value="pms-labels-edit" />
        <div id="poststuff">

            <div id="post-body" class="columns-2">

                <div id="post-body-content">

                    <div id="pmsle-id" class="postbox">
                        <div class="pmsle-postbox-header">
                            <h2><?php echo esc_html__( 'Edit Labels', 'paid-member-subscriptions' ); ?></h2>
                        </div>
                        <div class="inside">
                            <?php $this->edit_labels_metabox(); ?>
                        </div>
                    </div>


                </div>

                <div id="postbox-container-1" class="postbox-container">

                    <div>

                        <div id="pmsle-id-side" class="postbox">
                            <div class="pmsle-postbox-header">
                                <h2><?php echo esc_html__( 'Rescan Labels', 'paid-member-subscriptions' ); ?></h2>
                            </div>
                            <div class="inside">
                                <?php $this->rescan_metabox(); ?>
                            </div>
                        </div>

                        <div id="pmsle-id-side-info" class="postbox">
                            <div class="pmsle-postbox-header">
                                <h2><?php echo esc_html__( 'Information', 'paid-member-subscriptions' ); ?></h2>
                            </div>
                            <div class="inside">
                                <?php $this->info_metabox(); ?>
                            </div>
                        </div>

                        <div id="pmsle-id-side-info" class="postbox">
                            <div class="pmsle-postbox-header">
                                <h2><?php echo esc_html__( 'Import and Export Labels', 'paid-member-subscriptions' ); ?></h2>
                            </div>
                            <div class="inside">
                                <?php $this->import_export_metabox(); ?>
                            </div>
                        </div>


                    </div>
                </div>

            </div>

        </div>

</div>
