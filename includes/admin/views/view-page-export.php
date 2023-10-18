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

    <div class="cozmoslabs-nav-tab-wrapper">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=pms-reports-page' ) ); ?>" class="nav-tab <?php echo $active_tab == 'pms-reports-page' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Reports', 'paid-member-subscriptions' ); ?></a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=pms-export-page' ) ); ?>"  class="nav-tab <?php echo $active_tab == 'pms-export-page' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Export', 'paid-member-subscriptions' ); ?></a>
        <?php do_action( 'pms_reports_tab' ); ?>
    </div>

    <div id="dashboard-widgets-wrap">
        <div class="metabox-holder">
            <div id="post-body">
                <div id="post-body-content">

                    <div class="postbox pms-export cozmoslabs-form-subsection-wrapper" id="cozmoslabs-members-export">
                        <h3 class="cozmoslabs-subsection-title"><span><?php esc_html_e( 'Members Export', 'paid-member-subscriptions' ); ?></span></h3>
                        <p class="cozmoslabs-description"><?php esc_html_e( 'Download a CSV with your user subscriptions (an user with multiple subscriptions will have a record for each individual one).', 'paid-member-subscriptions' ); ?></p>
                        <div class="inside">
                            <form id="pms-export" class="pms-export-form " method="post">
                                <?php wp_nonce_field( 'pms_ajax_export', 'pms_ajax_export' ); ?>
                                <input type="hidden" name="pms-export-class" value="PMS_Batch_Export_Members"/>

                                <div class="cozmoslabs-form-field-wrapper">
                                    <label class="cozmoslabs-form-field-label" for="pms-plan-to-export"><?php esc_html_e( 'Subscription Plan', 'paid-member-subscriptions' ) ?></label>
                                <?php

                                    $subscription_plans = pms_get_subscription_plans( false );
                                    echo '<select name="pms-filter-subscription-plan" class="pms-export-filter" id="pms-plan-to-export">';
                                    echo '<option value="0">' . esc_html__( 'All Subscriptions', 'paid-member-subscriptions' ) . '</option>';

                                    foreach( $subscription_plans as $subscription_plan )
                                        echo '<option value="' . esc_attr( $subscription_plan->id ) . '">' . esc_html( $subscription_plan->name ) . '</option>';
                                    echo '</select> ';
                                    ?>

                                    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e('Choose the Subscription to export members from', 'paid-member-subscriptions') ?></p>
                                </div>

                                <div class="cozmoslabs-form-field-wrapper">
                                    <label class="cozmoslabs-form-field-label" for="pms-plan-to-export-status"><?php esc_html_e( 'Subscription Plan Status', 'paid-member-subscriptions' ) ?></label>

                                    <select name="pms-filter-member-status" class="pms-export-filter" id="pms-plan-to-export-status">
                                        <option value="0"><?php esc_html_e( 'All Members', 'paid-member-subscriptions' ); ?></option>
                                        <option value="active"><?php esc_html_e( 'Active', 'paid-member-subscriptions' ); ?></option>
                                        <option value="canceled"><?php esc_html_e( 'Canceled', 'paid-member-subscriptions' ); ?></option>
                                        <option value="expired"><?php esc_html_e( 'Expired', 'paid-member-subscriptions' ); ?></option>
                                        <option value="pending"><?php esc_html_e( 'Pending', 'paid-member-subscriptions' ); ?></option>
                                    </select>

                                    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Choose the current subscription status', 'paid-member-subscriptions' ); ?></p>
                                </div>

                                <div id="pms-add-meta-key-wrap">
                                    <h4 class="cozmoslabs-subsection-title"><span><?php esc_html_e( 'User Data', 'paid-member-subscriptions' ); ?></span></h4>
                                    <div id="pms-add-meta-key-container">

                                        <?php
                                        $pms_export_meta = get_user_meta(get_current_user_id(), 'pms_export_meta', true);
                                        $pms_export_meta = (empty($pms_export_meta)) ? [] : $pms_export_meta;

                                        foreach($pms_export_meta as $key => $value){
                                            ?>

                                            <div class="pms-add-meta-key-row cozmoslabs-group-fields-row">

                                                <div class="cozmoslabs-form-field-wrapper">
                                                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Column title', 'paid-member-subscriptions' ); ?></label>
                                                    <input type="text" name="pms-filter-user-meta-title[]" value="<?php echo esc_attr( $value ); ?>">
                                                </div>

                                                <div class="cozmoslabs-form-field-wrapper">
                                                    <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'User meta key', 'paid-member-subscriptions' ); ?></label>
                                                    <select name="pms-filter-user-meta[]" class="pms-export-filter pms-chosen">
                                                        <option value="0"><?php esc_html_e( '...Choose', 'paid-member-subscriptions' ); ?></option>
                                                        <?php
                                                        foreach (PMS_Submenu_Page_Export::get_all_user_meta_keys() as $umeta_key){
                                                            echo "<option ". selected( $key, $umeta_key['meta_key'], true ) ." value='". esc_attr( $umeta_key['meta_key'] ). "'>". esc_html( $umeta_key['meta_key'] ) ."</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <label class="pms-remove-meta-from-export cozmoslabs-remove-item"><span class="dashicons dashicons-no"></span></label>

                                            </div>

                                            <?php
                                        }
                                        ?>

                                    </div>
                                    <template id="pms-add-meta-row-tpl">

                                        <div class="pms-add-meta-key-row cozmoslabs-group-fields-row">
                                            <div class="cozmoslabs-form-field-wrapper">
                                                <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Column title', 'paid-member-subscriptions' ); ?></label>
                                                <input type="text" name="pms-filter-user-meta-title[]">
                                            </div>

                                            <div class="cozmoslabs-form-field-wrapper">
                                                <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'User meta key', 'paid-member-subscriptions' ); ?></label>
                                                <select name="pms-filter-user-meta[]" class="pms-export-filter pms-chosen">
                                                    <option value="0"><?php esc_html_e( '...Choose', 'paid-member-subscriptions' ); ?></option>
                                                    <?php
                                                    foreach (PMS_Submenu_Page_Export::get_all_user_meta_keys() as $umeta_key){
                                                        echo "<option value='". esc_attr( $umeta_key['meta_key'] ). "'>". esc_html( $umeta_key['meta_key'] ) ."</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <label class="pms-remove-meta-from-export cozmoslabs-remove-item"><span class="dashicons dashicons-no"></span></label>
                                        </div>
                                    </template>

                                    <a href="#" class="button-secondary" id="pms-add-meta-button" title="<?php esc_html_e( 'Adds another column to the export containing the information found inside a particular user meta key', 'paid-member-subscriptions' ); ?>">
                                        <?php esc_html_e( 'Add User Meta Column', 'paid-member-subscriptions' ); ?>
                                    </a>
                                </div>

                                <div>
									<input type="submit" class="button-primary" value="<?php esc_html_e( 'Generate CSV', 'paid-member-subscriptions' ); ?>"/>
									<span class="spinner"></span>
								</div>
                            </form>
                        </div><!-- .inside -->
                    </div><!-- .postbox -->


                </div><!-- .post-body-content -->
            </div><!-- .post-body -->
        </div><!-- .metabox-holder -->

        <div class="metabox-holder">
            <div id="post-body">
                <div id="post-body-content">


                    <div class="postbox pms-export cozmoslabs-form-subsection-wrapper" id="cozmoslabs-payments-export">
                        <h3 class="cozmoslabs-subsection-title"><span><?php esc_html_e( 'Payments Export', 'paid-member-subscriptions' ); ?></span></h3>
                        <p class="cozmoslabs-description"><?php esc_html_e( 'Download a CSV with your payments.', 'paid-member-subscriptions' ); ?></p>
                        <div class="inside">
                            <form id="pms-export" class="pms-export-form " method="post">
                                <?php wp_nonce_field( 'pms_ajax_export', 'pms_ajax_export' ); ?>
                                <input type="hidden" name="pms-export-class" value="PMS_Batch_Export_Payments"/>

                                <div class="cozmoslabs-form-field-wrapper">
                                    <label class="cozmoslabs-form-field-label" for="pms-export-payment-status"><?php esc_html_e('Status', 'paid-member-subscriptions') ?></label>

                                    <select name="pms-filter-payment-status" class="pms-export-filter" id="pms-export-payment-status">
                                        <option value="0"><?php esc_html_e( 'All Payments', 'paid-member-subscriptions' ) ?></option>
                                        <option value="completed"><?php esc_html_e( 'Completed', 'paid-member-subscriptions' ) ?></option>
                                        <option value="pending"><?php esc_html_e( 'Pending', 'paid-member-subscriptions' ) ?></option>
                                        <option value="refunded"><?php esc_html_e( 'Refunded', 'paid-member-subscriptions' ) ?></option>
                                        <option value="failed"><?php esc_html_e( 'Failed', 'paid-member-subscriptions' ) ?></option>
                                    </select>

                                    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e('Choose the payment status', 'paid-member-subscriptions') ?></p>
                                </div>

                                <div class="cozmoslabs-form-field-wrapper">
                                    <label class="cozmoslabs-form-field-label" for="pms-export-payment-start-date"><?php esc_html_e('Start Date', 'paid-member-subscriptions'); ?></label>
                                    <input name="pms-filter-start-date" type="date" class="pms-export-filter" id="pms-export-payment-start-date">
                                    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e('Choose export data Start Date', 'paid-member-subscriptions') ?></p>
                                </div>

                                <div class="cozmoslabs-form-field-wrapper">
                                    <label class="cozmoslabs-form-field-label" for="pms-export-payment-end-date"><?php esc_html_e('End Date', 'paid-member-subscriptions'); ?></label>
                                    <input name="pms-filter-end-date" type="date" class="pms-export-filter" id="pms-export-payment-end-date">
                                    <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e('Choose export data End Date', 'paid-member-subscriptions') ?></p>
                                </div>

                                <p class="cozmoslabs-description cozmoslabs-notice-message"><?php esc_html_e( 'NOTE: Leave dates empty for an export of all payments.', 'paid-member-subscriptions' ); ?></p>

                                <div>
									<input type="submit" value="<?php esc_html_e( 'Generate CSV', 'paid-member-subscriptions' ); ?>" class="button-primary"/>
									<span class="spinner"></span>
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
