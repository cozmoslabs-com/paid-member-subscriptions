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
            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/reports/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
        </div>
    </div>

    <div class="cozmoslabs-nav-tab-wrapper">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=pms-reports-page' ) ); ?>" class="nav-tab <?php echo $active_tab == 'pms-reports-page' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Reports', 'paid-member-subscriptions' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=pms-export-page' ) ); ?>"  class="nav-tab <?php echo $active_tab == 'pms-export-page' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Export', 'paid-member-subscriptions' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=pms-import-page' ) ); ?>"  class="nav-tab <?php echo $active_tab == 'pms-import-page' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Import', 'paid-member-subscriptions' ); ?></a>
        <?php do_action( 'pms_reports_tab' ); ?>
    </div>

    <div id="dashboard-widgets-wrap">
        <div class="metabox-holder">
            <div id="post-body">
                <div id="post-body-content">

                    <div class="postbox pms-import cozmoslabs-form-subsection-wrapper" id="cozmoslabs-members-import">
                        <h3 class="cozmoslabs-subsection-title"><span><?php esc_html_e( 'Members Import', 'paid-member-subscriptions' ); ?></span></h3>
                        <p class="cozmoslabs-description"><?php esc_html_e( 'Upload a CSV with your user subscriptions.', 'paid-member-subscriptions' ); ?></p>
                        <div class="inside">
                            <form id="pms-import" class="pms-import-form " method="post">
                                <?php wp_nonce_field( 'pms_ajax_import', 'pms_ajax_import' ); ?>
                                <input type="hidden" name="pms-import-class" value="PMS_Batch_Import_Members"/>

                                <div class="cozmoslabs-form-field-wrapper">
                                    <label class="cozmoslabs-form-field-label" for="pms-plan-to-import-status"><?php esc_html_e( 'Members File', 'paid-member-subscriptions' ) ?></label>

                                    <input type="file" id="subscriptionscsv" name="subscriptionscsv" accept=".csv">

                                    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Choose the file you wish to upload', 'paid-member-subscriptions' ); ?></p>
                                </div>

                                <div>
                                    <div style="color: red;"><strong>Attention</strong><br>
                                    It is strongly advised to perform a database backup that can be used for a restore in case something goes wrong.<br>
                                    This tool will add or edit membership data and the changes cannot be reverted without a backup.</div>

                                    <h3>How it works</h3>

                                    <p>Through this functionality you can migrate, import or update members on your website.</p>

                                    <h4>Migrate Members</h4>
                                    <p>This functionality is able to import a file that was exported from a different website through the Export Members functionality. The file will work as is without any changes but you can also alter some data.</p>

                                    <h4>Import Members</h4>
                                    <p>To import new members to a website the file needs to contain:</p>
                                    <ul>
                                        <li>* a <strong>subscription_plan_id</strong> column with an ID for a valid plan which needs to be created beforehand if it doesn't exist</li>
                                        <li>* a <strong>user_email</strong> column that will either be used to match existing users to create subscriptions for them or create new users</li>
                                    </ul>

                                    <p>Existing users can also be matched by adding a <strong>subscription_user_id</strong> column to the csv file.</p>

                                    <p>You can download a simple sample file from <a href="<?php echo esc_url( PMS_PLUGIN_DIR_URL . 'assets/sample-data/sample-file-import-subscriptions-for-existing-users.csv' ); ?>">here</a>.</p>

                                    <h4>Update Members</h4>
                                    <p>Through this functionality you can also bulk update existing members.</p>
                                    
                                    <p>The rules are the same as above, the file needs a <strong>subscription_plan_id</strong> and either a <strong>user_email</strong> or <strong>subscription_user_id</strong> in order to match the existing user.</p>

                                    <p>You can read more information on <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/export-and-import" target="_blank">our documentation</a> page.</p>

                                    <p><strong>Note:</strong> please take into account recurring subscriptions. <br>Currently with PayPal, no changes can be done to existing agreements so you shouldn't use this tool to try to manipulate those subscriptions. The changes will only happen on your website.</p>
                                    <p>Stripe allows changes so you could use this tool to perform Bulk Cancelations or Bulk Change Renewal Dates as an example.</p>
                                </div>

                                <div>
									<input type="submit" class="button-primary" value="<?php esc_html_e( 'Upload CSV', 'paid-member-subscriptions' ); ?>"/>
									<span class="spinner"></span>
								</div>
                            </form>
                        </div><!-- .inside -->
                    </div><!-- .postbox -->


                </div><!-- .post-body-content -->
            </div><!-- .post-body -->
        </div><!-- .metabox-holder -->

    </div><!-- #dashboard-widgets-wrap -->

    <?php do_action( 'pms_import_page_bottom' ); ?>

</div>
