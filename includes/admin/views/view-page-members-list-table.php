<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for the members admin page
 */
?>

<div class="wrap">

    <h1 class="wp-heading-inline">
        <?php echo esc_html( $this->page_title ); ?>

        <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/member-management/?utm_source=pms-members&utm_medium=client-site&utm_campaign=pms-members-docs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>

        <a href="<?php echo esc_url( add_query_arg( array( 'page' => $this->menu_slug, 'subpage' => 'add_subscription' ), admin_url( 'admin.php' ) ) ); ?>" class="add-new-h2 page-title-action"><?php echo esc_html__( 'Add New', 'paid-member-subscriptions' ); ?></a>
        <a href="<?php echo esc_url( add_query_arg( array( 'page' => $this->menu_slug, 'subpage' => 'add_new_members_bulk' ), admin_url( 'admin.php' ) ) ); ?>" class="add-new-h2 page-title-action"><?php echo esc_html__( 'Bulk Add New', 'paid-member-subscriptions' ); ?></a>
    </h1>
    <form method="get">
        <input type="hidden" name="page" value="pms-members-page" />
        <?php
            $this->list_table->prepare_items();
            $this->list_table->views();
            $this->list_table->search_box( esc_html__( 'Search Members', 'paid-member-subscriptions' ), 'pms_search_members' );
        ?>
        <div id="poststuff">

            <div id="post-body" class="metabox-holder columns-2">

                <div id="post-body-content">
                    <?php
                    $this->list_table->display();
                    ?>
                </div>

                <div id="postbox-container-1" class="postbox-container filter-members-sidebox">

                    <div id="side-sortables" class="meta-box-sortables ui-sortable">
                        <div class="postbox">

                            <!-- Meta-box Title -->
                            <h2 class="hndle">
                                <span>
                                    <?php esc_html_e( 'Filter by', 'paid-member-subscriptions' ); ?>
                                </span>
                            </h2>

                            <div class="submitbox">
                                <div id="major-publishing-actions">
                                    <div>
                                        <?php
                                        /*
                                         * Add a custom select box to filter the list by Subscription Plans
                                         */
                                        $subscription_plans = pms_get_subscription_plans( false );
                                        ?>
                                        <select name="pms-filter-subscription-plan" class="pms-filter-select" id="pms-filter-subscription-plan">
                                            <option value=""><?php esc_html_e( 'Subscription Plan...', 'paid-member-subscriptions' ); ?></option>
                                            <?php foreach( $subscription_plans as $subscription_plan ) : ?>
                                                <option value="<?php echo esc_attr( $subscription_plan->id ); ?>" <?php echo !empty( $_GET['pms-filter-subscription-plan'] ) ? selected( $subscription_plan->id, sanitize_text_field( $_GET['pms-filter-subscription-plan'] ), false ) : ''; ?>><?php echo esc_html( $subscription_plan->name ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <?php
                                    /**
                                     * Action to add more filters
                                     */
                                    do_action( 'pms_members_list_extra_table_nav', 'top' );

                                    $payment_gateways = pms_get_payment_gateways();
                                    $payment_gateways_keys = array_keys( $payment_gateways );
                                    ?>

                                    <div>
                                        <select name="pms-filter-payment-gateway" class="pms-filter-select" id="pms-filter-payment-gateway">
                                            <option value=""><?php esc_html_e( 'Payment Gateway...', 'paid-member-subscriptions' ); ?></option>
                                            <?php 
                                            $i = 0;
                                            foreach( $payment_gateways as $payment_gateway ) :
                                                if( isset( $payment_gateway['display_name_admin'] ) ) : ?>
                                                    <option value="<?php echo esc_attr( $payment_gateways_keys[$i] ); ?>" <?php echo !empty( $_GET['pms-filter-payment-gateway'] ) ? selected( $payment_gateways_keys[$i], sanitize_text_field( $_GET['pms-filter-payment-gateway'] ), false ) : ''; ?>><?php echo esc_html( $payment_gateway['display_name_admin'] ); ?></option>
                                                <?php endif;
                                                $i++;
                                            endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <select name="pms-filter-start-date" class="pms-filter-select" id="pms-filter-start-date">
                                            <option value=""><?php esc_html_e( 'Start Date...', 'paid-member-subscriptions' ); ?></option>
                                            <option value="last_week" <?php echo !empty( $_GET['pms-filter-start-date'] ) ? selected( "last_week", sanitize_text_field( $_GET['pms-filter-start-date'] ), false ) : ''; ?>><?php esc_html_e( 'Last 7 Days', 'paid-member-subscriptions' ); ?></option>
                                            <option value="last_month" <?php echo !empty( $_GET['pms-filter-start-date'] ) ? selected( "last_month", sanitize_text_field( $_GET['pms-filter-start-date'] ), false ) : ''; ?>><?php esc_html_e( 'Last 30 Days', 'paid-member-subscriptions' ); ?></option>
                                            <option value="last_year" <?php echo !empty( $_GET['pms-filter-start-date'] ) ? selected( "last_year", sanitize_text_field( $_GET['pms-filter-start-date'] ), false ) : ''; ?>><?php esc_html_e( 'Last Year', 'paid-member-subscriptions' ); ?></option>
                                            <option value="custom" <?php echo !empty( $_GET['pms-filter-start-date'] ) ? selected( "custom", sanitize_text_field( $_GET['pms-filter-start-date'] ), false ) : ''; ?>><?php esc_html_e( 'Custom', 'paid-member-subscriptions' ); ?></option>
                                        </select>
                                    </div>

                                    <div class="cozmoslabs-custom-interval" id="pms-start-date-interval" style="display: none;">
                                        <label id="pms-label-start-date-beginning" for="pms-datepicker-start-date-beginning"><?php esc_html_e( 'Start of Interval', 'paid-member-subscriptions' ); ?></label>
                                        <input id="pms-datepicker-start-date-beginning" type="text" name="pms-datepicker-start-date-beginning" class="datepicker" value="<?php echo !empty( $_GET['pms-datepicker-start-date-beginning'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-start-date-beginning'] ) ) : ''; ?>">

                                        <label id="pms-label-start-date-end" for="pms-datepicker-start-date-end"><?php esc_html_e( 'End of Interval', 'paid-member-subscriptions' ); ?></label>
                                        <input id="pms-datepicker-start-date-end" type="text" name="pms-datepicker-start-date-end" class="datepicker" value="<?php echo !empty( $_GET['pms-datepicker-start-date-end'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-start-date-end'] ) ) : ''; ?>">
                                    </div>

                                    <div>
                                        <select name="pms-filter-expiration-date" class="pms-filter-select" id="pms-filter-expiration-date">
                                            <option value=""><?php esc_html_e( 'End Date...', 'paid-member-subscriptions' ); ?></option>
                                            <option value="today" <?php echo !empty( $_GET['pms-filter-expiration-date'] ) ? selected( "today", sanitize_text_field( $_GET['pms-filter-expiration-date'] ), false ) : ''; ?>><?php esc_html_e( 'Today', 'paid-member-subscriptions' ); ?></option>
                                            <option value="tomorrow" <?php echo !empty( $_GET['pms-filter-expiration-date'] ) ? selected( "tomorrow", sanitize_text_field( $_GET['pms-filter-expiration-date'] ), false ) : ''; ?>><?php esc_html_e( 'Tomorrow', 'paid-member-subscriptions' ); ?></option>
                                            <option value="this_week" <?php echo !empty( $_GET['pms-filter-expiration-date'] ) ? selected( "this_week", sanitize_text_field( $_GET['pms-filter-expiration-date'] ), false ) : ''; ?>><?php esc_html_e( 'This Week', 'paid-member-subscriptions' ); ?></option>
                                            <option value="this_month" <?php echo !empty( $_GET['pms-filter-expiration-date'] ) ? selected( "this_month", sanitize_text_field( $_GET['pms-filter-expiration-date'] ), false ) : ''; ?>><?php esc_html_e( 'This Month', 'paid-member-subscriptions' ); ?></option>
                                            <option value="custom" <?php echo !empty( $_GET['pms-filter-expiration-date'] ) ? selected( "custom", sanitize_text_field( $_GET['pms-filter-expiration-date'] ), false ) : ''; ?>><?php esc_html_e( 'Custom', 'paid-member-subscriptions' ); ?></option>
                                        </select>
                                    </div>

                                    <div class="cozmoslabs-custom-interval" id="pms-expiration-date-interval" style="display: none;">
                                        <label id="pms-label-expiration-date-beginning" for="pms-datepicker-expiration-date-beginning"><?php esc_html_e( 'Start of Interval', 'paid-member-subscriptions' ); ?></label>
                                        <input id="pms-datepicker-expiration-date-beginning" type="text" name="pms-datepicker-expiration-date-beginning" class="datepicker" value="<?php echo !empty( $_GET['pms-datepicker-expiration-date-beginning'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-expiration-date-beginning'] ) ) : ''; ?>">

                                        <label id="pms-label-expiration-date-end" for="pms-datepicker-expiration-date-end"><?php esc_html_e( 'End of Interval', 'paid-member-subscriptions' ); ?></label>
                                        <input id="pms-datepicker-expiration-date-end" type="text" name="pms-datepicker-expiration-date-end" class="datepicker" value="<?php echo !empty( $_GET['pms-datepicker-expiration-date-end'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-expiration-date-end'] ) ) : ''; ?>">
                                    </div>

                                    <a href="<?php echo esc_url( add_query_arg( array( 'page' => $this->menu_slug ), admin_url( 'admin.php' ) ) ); ?>" style="visibility:hidden;margin-left:auto;" id="pms-filter-clear-filters"><?php esc_html_e( 'Clear Filters', 'paid-member-subscriptions' ); ?></a>

                                    <?php
                                        /**
                                         * Action to add more filters
                                         */
                                        do_action( 'pms_members_list_extra_table_nav', 'bottom' );
                                    ?>

                                    <input class="button button-secondary" id="pms-filter-button" type="submit" value="<?php esc_html_e( 'Filter', 'paid-member-subscriptions' ); ?>" />

                                    <div class="clear"></div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

        </div>
    </form>

</div>
