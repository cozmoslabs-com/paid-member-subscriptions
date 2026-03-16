<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * HTML output for the subscriptions admin page
 */
?>

<div class="wrap">

    <h1 class="wp-heading-inline">
        <?php echo esc_html( $this->page_title ); ?>
    </h1>

    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr( $this->menu_slug ); ?>" />

        <?php
            $this->list_table->prepare_items();
            $this->list_table->views();
            $this->list_table->search_box( esc_html__( 'Search Subscriptions', 'paid-member-subscriptions' ), 'pms_search_subscriptions' );
        ?>

        <div id="poststuff">

            <div id="post-body" class="metabox-holder columns-2">

                <!-- Subscription Table -->
                <div id="post-body-content">

                    <?php $this->list_table->display(); ?>

                </div>

                <!-- Filters Meta-Box -->
                <div id="postbox-container-1" class="postbox-container filter-subscriptions-sidebox">

                    <div id="side-sortables" class="meta-box-sortables ui-sortable">

                        <div class="postbox">

                            <h2 class="hndle">
                                <span>
                                    <?php esc_html_e( 'Filter by', 'paid-member-subscriptions' ); ?>
                                </span>
                            </h2>

                            <div class="submitbox">

                                <div id="major-publishing-actions">

                                    <!-- Subscription Plans -->
                                    <div class="pms-subscriptions-filter">
                                        <?php $subscription_plans = pms_get_subscription_plans( false ); ?>

                                        <select name="pms-filter-subscription-plan" class="pms-filter-select" id="pms-filter-subscription-plan">
                                            <option value=""><?php esc_html_e( 'Subscription Plan...', 'paid-member-subscriptions' ); ?></option>
                                            <?php foreach( $subscription_plans as $subscription_plan ) : ?>
                                                <option value="<?php echo esc_attr( $subscription_plan->id ); ?>" <?php echo !empty( $_GET['pms-filter-subscription-plan'] ) ? selected( $subscription_plan->id, sanitize_text_field( $_GET['pms-filter-subscription-plan'] ), false ) : ''; ?>><?php echo esc_html( $subscription_plan->name ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Payment Gateways -->
                                    <div class="pms-subscriptions-filter">
                                        <?php
                                        $payment_gateways      = pms_get_payment_gateways();
                                        $payment_gateway_slugs = array_keys( $payment_gateways );
                                        ?>

                                        <select name="pms-filter-payment-gateway" class="pms-filter-select" id="pms-filter-payment-gateway">
                                            <option value=""><?php esc_html_e( 'Payment Gateway...', 'paid-member-subscriptions' ); ?></option>

                                            <?php

                                            $i = 0;

                                            foreach( $payment_gateways as $payment_gateway ) :

                                                if( isset( $payment_gateway['display_name_admin'] ) ) : ?>

                                                    <option value="<?php echo esc_attr( $payment_gateway_slugs[$i] ); ?>" <?php echo !empty( $_GET['pms-filter-payment-gateway'] ) ? selected( $payment_gateway_slugs[$i], sanitize_text_field( $_GET['pms-filter-payment-gateway'] ), false ) : ''; ?>><?php echo esc_html( $payment_gateway['display_name_admin'] ); ?></option>

                                                <?php
                                                endif;

                                                $i++;

                                            endforeach;

                                            ?>
                                        </select>
                                    </div>

                                    <!-- Currency -->
                                    <?php if( apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-multiple-currencies/index.php' ) ) : ?>
                                        <div class="pms-subscriptions-filter">
                                            <?php
                                            $currencies      = pms_get_currencies();
                                            $used_currencies = $this->list_table->get_used_subscription_currencies();
                                            ?>

                                            <select name="pms-filter-currency" class="pms-filter-select" id="pms-filter-currency">
                                                <option value=""><?php esc_html_e( 'Currency...', 'paid-member-subscriptions' ); ?></option>

                                                <?php foreach( $used_currencies as $currency ) : ?>
                                                    <option value="<?php echo esc_attr( $currency ); ?>" <?php echo !empty( $_GET['pms-filter-currency'] ) ? selected( $currency, strtoupper( sanitize_text_field( $_GET['pms-filter-currency'] ) ), false ) : ''; ?>>
                                                        <?php echo !empty( $currencies[ $currency ] ) ? esc_html( $currencies[ $currency ] . ' (' . $currency . ')' ) : esc_html( $currency ); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Group -->
                                    <?php if( apply_filters( 'pms_add_on_is_active', false, 'pms-add-on-group-memberships/index.php' ) ) : ?>
                                        <div class="pms-subscriptions-filter">
                                            <?php $groups = $this->list_table->get_active_group_names(); ?>

                                            <select name="pms-filter-group" class="pms-filter-select" id="pms-filter-group">
                                                <option value=""><?php esc_html_e( 'Group...', 'paid-member-subscriptions' ); ?></option>

                                                <?php foreach( $groups as $group ) : ?>
                                                    <option value="<?php echo esc_attr( $group['member_subscription_id'] ); ?>" <?php echo !empty( $_GET['pms-filter-group'] ) ? selected( $group['member_subscription_id'], sanitize_text_field( $_GET['pms-filter-group'] ), false ) : ''; ?>><?php echo esc_html( $group['meta_value'] ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Start Date -->
                                    <div class="pms-subscriptions-filter">
                                        <select name="pms-filter-start-date" class="pms-filter-select" id="pms-filter-start-date">
                                            <option value=""><?php esc_html_e( 'Start Date...', 'paid-member-subscriptions' ); ?></option>
                                            <option value="last_week" <?php echo !empty( $_GET['pms-filter-start-date'] ) ? selected( 'last_week', sanitize_text_field( $_GET['pms-filter-start-date'] ), false ) : ''; ?>><?php esc_html_e( 'Last Week', 'paid-member-subscriptions' ); ?></option>
                                            <option value="last_month" <?php echo !empty( $_GET['pms-filter-start-date'] ) ? selected( 'last_month', sanitize_text_field( $_GET['pms-filter-start-date'] ), false ) : ''; ?>><?php esc_html_e( 'Last Month', 'paid-member-subscriptions' ); ?></option>
                                            <option value="last_year" <?php echo !empty( $_GET['pms-filter-start-date'] ) ? selected( 'last_year', sanitize_text_field( $_GET['pms-filter-start-date'] ), false ) : ''; ?>><?php esc_html_e( 'Last Year', 'paid-member-subscriptions' ); ?></option>
                                            <option value="custom" <?php echo !empty( $_GET['pms-filter-start-date'] ) ? selected( 'custom', sanitize_text_field( $_GET['pms-filter-start-date'] ), false ) : ''; ?>><?php esc_html_e( 'Custom', 'paid-member-subscriptions' ); ?></option>
                                        </select>
                                    </div>

                                    <!-- Start Date - custom intervals -->
                                    <div class="cozmoslabs-custom-interval pms-subscriptions-filter" id="pms-start-date-interval" style="display: none;">
                                        <label id="pms-label-start-date-beginning" for="pms-datepicker-start-date-beginning"><?php esc_html_e( 'Start of Interval', 'paid-member-subscriptions' ); ?></label>
                                        <input id="pms-datepicker-start-date-beginning" type="text" name="pms-datepicker-start-date-beginning" class="datepicker" value="<?php echo !empty( $_GET['pms-datepicker-start-date-beginning'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-start-date-beginning'] ) ) : ''; ?>">

                                        <label id="pms-label-start-date-end" for="pms-datepicker-start-date-end"><?php esc_html_e( 'End of Interval', 'paid-member-subscriptions' ); ?></label>
                                        <input id="pms-datepicker-start-date-end" type="text" name="pms-datepicker-start-date-end" class="datepicker" value="<?php echo !empty( $_GET['pms-datepicker-start-date-end'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-start-date-end'] ) ) : ''; ?>">
                                    </div>

                                    <!-- Expiration Date -->
                                    <div class="pms-subscriptions-filter">
                                        <select name="pms-filter-expiration-date" class="pms-filter-select" id="pms-filter-expiration-date">
                                            <option value=""><?php esc_html_e( 'Expiration Date...', 'paid-member-subscriptions' ); ?></option>
                                            <option value="today" <?php echo !empty( $_GET['pms-filter-expiration-date'] ) ? selected( 'today', sanitize_text_field( $_GET['pms-filter-expiration-date'] ), false ) : ''; ?>><?php esc_html_e( 'Today', 'paid-member-subscriptions' ); ?></option>
                                            <option value="tomorrow" <?php echo !empty( $_GET['pms-filter-expiration-date'] ) ? selected( 'tomorrow', sanitize_text_field( $_GET['pms-filter-expiration-date'] ), false ) : ''; ?>><?php esc_html_e( 'Tomorrow', 'paid-member-subscriptions' ); ?></option>
                                            <option value="this_week" <?php echo !empty( $_GET['pms-filter-expiration-date'] ) ? selected( 'this_week', sanitize_text_field( $_GET['pms-filter-expiration-date'] ), false ) : ''; ?>><?php esc_html_e( 'This Week', 'paid-member-subscriptions' ); ?></option>
                                            <option value="this_month" <?php echo !empty( $_GET['pms-filter-expiration-date'] ) ? selected( 'this_month', sanitize_text_field( $_GET['pms-filter-expiration-date'] ), false ) : ''; ?>><?php esc_html_e( 'This Month', 'paid-member-subscriptions' ); ?></option>
                                            <option value="custom" <?php echo !empty( $_GET['pms-filter-expiration-date'] ) ? selected( 'custom', sanitize_text_field( $_GET['pms-filter-expiration-date'] ), false ) : ''; ?>><?php esc_html_e( 'Custom', 'paid-member-subscriptions' ); ?></option>
                                        </select>
                                    </div>

                                    <!-- Expiration Date - custom intervals -->
                                    <div class="cozmoslabs-custom-interval pms-subscriptions-filter" id="pms-expiration-date-interval" style="display: none;">
                                        <label id="pms-label-expiration-date-beginning" for="pms-datepicker-expiration-date-beginning"><?php esc_html_e( 'Start of Interval', 'paid-member-subscriptions' ); ?></label>
                                        <input id="pms-datepicker-expiration-date-beginning" type="text" name="pms-datepicker-expiration-date-beginning" class="datepicker" value="<?php echo !empty( $_GET['pms-datepicker-expiration-date-beginning'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-expiration-date-beginning'] ) ) : ''; ?>">

                                        <label id="pms-label-expiration-date-end" for="pms-datepicker-expiration-date-end"><?php esc_html_e( 'End of Interval', 'paid-member-subscriptions' ); ?></label>
                                        <input id="pms-datepicker-expiration-date-end" type="text" name="pms-datepicker-expiration-date-end" class="datepicker" value="<?php echo !empty( $_GET['pms-datepicker-expiration-date-end'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-expiration-date-end'] ) ) : ''; ?>">
                                    </div>

                                    <!-- Next Billing Date -->
                                    <div class="pms-subscriptions-filter">
                                        <select name="pms-filter-next-billing-date" class="pms-filter-select" id="pms-filter-next-billing-date">
                                            <option value=""><?php esc_html_e( 'Next Billing Date...', 'paid-member-subscriptions' ); ?></option>
                                            <option value="tomorrow" <?php echo !empty( $_GET['pms-filter-next-billing-date'] ) ? selected( 'tomorrow', sanitize_text_field( $_GET['pms-filter-next-billing-date'] ), false ) : ''; ?>><?php esc_html_e( 'Tomorrow', 'paid-member-subscriptions' ); ?></option>
                                            <option value="this_week" <?php echo !empty( $_GET['pms-filter-next-billing-date'] ) ? selected( 'this_week', sanitize_text_field( $_GET['pms-filter-next-billing-date'] ), false ) : ''; ?>><?php esc_html_e( 'This Week', 'paid-member-subscriptions' ); ?></option>
                                            <option value="this_month" <?php echo !empty( $_GET['pms-filter-next-billing-date'] ) ? selected( 'this_month', sanitize_text_field( $_GET['pms-filter-next-billing-date'] ), false ) : ''; ?>><?php esc_html_e( 'This Month', 'paid-member-subscriptions' ); ?></option>
                                            <option value="this_year" <?php echo !empty( $_GET['pms-filter-next-billing-date'] ) ? selected( 'this_year', sanitize_text_field( $_GET['pms-filter-next-billing-date'] ), false ) : ''; ?>><?php esc_html_e( 'This Year', 'paid-member-subscriptions' ); ?></option>
                                            <option value="custom" <?php echo !empty( $_GET['pms-filter-next-billing-date'] ) ? selected( 'custom', sanitize_text_field( $_GET['pms-filter-next-billing-date'] ), false ) : ''; ?>><?php esc_html_e( 'Custom', 'paid-member-subscriptions' ); ?></option>
                                        </select>
                                    </div>

                                    <!-- Next Billing Date - custom intervals -->
                                    <div class="cozmoslabs-custom-interval pms-subscriptions-filter" id="pms-next-billing-date-interval" style="display: none;">
                                        <label id="pms-label-next-billing-date-beginning" for="pms-datepicker-next-billing-date-beginning"><?php esc_html_e( 'Start of Interval', 'paid-member-subscriptions' ); ?></label>
                                        <input id="pms-datepicker-next-billing-date-beginning" type="text" name="pms-datepicker-next-billing-date-beginning" class="datepicker" value="<?php echo !empty( $_GET['pms-datepicker-next-billing-date-beginning'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-next-billing-date-beginning'] ) ) : ''; ?>">

                                        <label id="pms-label-next-billing-date-end" for="pms-datepicker-next-billing-date-end"><?php esc_html_e( 'End of Interval', 'paid-member-subscriptions' ); ?></label>
                                        <input id="pms-datepicker-next-billing-date-end" type="text" name="pms-datepicker-next-billing-date-end" class="datepicker" value="<?php echo !empty( $_GET['pms-datepicker-next-billing-date-end'] ) ? esc_attr( sanitize_text_field( $_GET['pms-datepicker-next-billing-date-end'] ) ) : ''; ?>">
                                    </div>

                                    <a href="<?php echo esc_url( add_query_arg( array( 'page' => $this->menu_slug ), admin_url( 'admin.php' ) ) ); ?>" style="visibility:hidden;margin-left:auto;" id="pms-filter-clear-filters"><?php esc_html_e( 'Clear Filters', 'paid-member-subscriptions' ); ?></a>
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
