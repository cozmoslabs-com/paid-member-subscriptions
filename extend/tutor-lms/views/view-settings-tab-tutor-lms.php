<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * HTML Output for the settings page, WooCommerce Integration tab
 */

?>

<div id="pms-settings-tutor-lms" class="pms-tab cozmoslabs-settings <?php echo ( isset( $active_tab ) && $active_tab === 'tutor_lms' ? 'tab-active' : '' ); ?>">

    <?php
    if ( isset( $options ))
        do_action( 'pms-settings-page_tab_tutor_lms_before_content', $options );
    ?>

    <div class="cozmoslabs-form-subsection-wrapper">

        <h3 class="cozmoslabs-subsection-title">
            <?php esc_html_e( 'Restriction Settings', 'paid-member-subscriptions' ); ?>
            <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/integration-with-other-plugins/tutor-lms/?utm_source=pms-tutor-lms-settings&utm_medium=client-site&utm_campaign=pms-tutor-lms-docs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
        </h3>

        <!-- Restriction Type -->
        <div class="cozmoslabs-form-field-wrapper" id="tutor-lms-restriction-type">
            <label class="cozmoslabs-form-field-label" for="restriction-type"><?php esc_html_e( 'Restriction Type', 'paid-member-subscriptions' ); ?></label>

            <select id="restriction-type" name="pms_tutor_lms_settings[restriction_type]">
                <option value="full_courses" <?php echo ( isset( $options['restriction_type'] ) && $options['restriction_type'] === 'full_courses' ) ? 'selected' : ''; ?>><?php esc_html_e( 'Full Courses Restriction', 'paid-member-subscriptions' ); ?></option>
                <option value="category" <?php echo ( isset( $options['restriction_type'] ) && $options['restriction_type'] === 'category' ) ? 'selected' : ''; ?>><?php esc_html_e( 'Category Restriction', 'paid-member-subscriptions' ); ?></option>
                <option value="individual" <?php echo ( isset( $options['restriction_type'] ) && $options['restriction_type'] === 'individual' ) ? 'selected' : ''; ?>><?php esc_html_e( 'Individual Restriction', 'paid-member-subscriptions' ); ?></option>
            </select>

            <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Choose the restriction type that best aligns with your needs.', 'paid-member-subscriptions' ); ?></p>

            <p class="cozmoslabs-description cozmoslabs-description-space-left" id="full-course-description"><?php esc_html_e( 'Full Course restriction enables access for members of any Subscription Plan or those specifically selected below.', 'paid-member-subscriptions' ); ?></p>
            <p class="cozmoslabs-description cozmoslabs-description-space-left" id="category-description"><?php esc_html_e( 'Category restriction enables access for members of a Subscription Plan which grants access to a category the Course is in.', 'paid-member-subscriptions' ); ?></p>
            <p class="cozmoslabs-description cozmoslabs-description-space-left" id="individual-description"><?php esc_html_e( 'Individual restriction enables access for members of specific Subscription Plans.', 'paid-member-subscriptions' ); ?></p>
        </div>

        <!-- Access Type -->
        <div class="cozmoslabs-form-field-wrapper" id="tutor-lms-access-type">

            <label class="cozmoslabs-form-field-label">
                <?php esc_html_e( 'Access Type', 'paid-member-subscriptions' ); ?>
            </label>

            <div class="cozmoslabs-radio-inputs-column">

                <label class="access-type" for="any-member">
                    <input type="radio" id="any-member" value="any_member" <?php echo  ( empty( $options['access_type'] ) || ( !empty( $options['access_type'] ) && $options['access_type'] === 'any_member' ) ) ? 'checked="checked"' : ''; ?> name="pms_tutor_lms_settings[access_type]">
                    <?php esc_html_e( 'Allow any member to access courses', 'paid-member-subscriptions' ); ?>
                </label>

                <label class="access-type" for="subscribed-member">
                    <input type="radio" id="subscribed-member" value="subscribed_member" <?php echo  ( !empty( $options['access_type'] ) && $options['access_type'] === 'subscribed_member' ) ? 'checked="checked"' : ''; ?> name="pms_tutor_lms_settings[access_type]">
                    <?php esc_html_e( 'Allow members of the selected Subscription Plans to access courses', 'paid-member-subscriptions' ); ?>
                </label>

            </div>

            <p class="cozmoslabs-description cozmoslabs-description-space-left" style="margin-top: 10px;"><?php esc_html_e( 'Choose if any members or only members of selected Subscription Plans can access courses.', 'paid-member-subscriptions' ); ?></p>

        </div>

        <!-- Subscription Plans -->
        <div class="cozmoslabs-form-field-wrapper" id="tutor-lms-subscription-plans">

            <label class="cozmoslabs-form-field-label" for="subscription-plans"><?php esc_html_e( 'Subscription Plans', 'paid-member-subscriptions' ); ?></label>

            <?php $subscription_plans = pms_get_subscription_plans_list(); ?>
            <?php if ( !empty( $subscription_plans ) ) : ?>
                <select name="pms_tutor_lms_settings[subscription_plans][]" id="subscription-plans" class="pms-chosen" multiple data-placeholder="<?php esc_html_e( 'Select Subscription Plans...', 'paid-member-subscriptions' ); ?>" >
                    <?php foreach ( $subscription_plans as $subscription_plan_id => $subscription_plan_name ): ?>
                        <option value="<?php echo esc_attr( $subscription_plan_id ); ?>"  <?php echo ( isset( $options['subscription_plans'] ) && in_array( $subscription_plan_id, $options['subscription_plans'] ) ) ? 'selected' : ''; ?>><?php echo esc_html( $subscription_plan_name ); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Select the Subscription Plans users must be members of to access Courses.', 'paid-member-subscriptions' ); ?></p>
            <?php else : ?>
                <label><?php  esc_html_e('There are no active Subscription Plans!', 'paid-member-subscriptions'); ?></label>
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo sprintf( esc_html__( 'Go to %1$s Paid Member Subscriptions --> Subscription Plans %2$s and activate or create a Plan.', 'paid-member-subscriptions' ), '<a href="'.esc_url( home_url( 'wp-admin/edit.php?post_type=pms-subscription' ) ).'">', '</a>', '<strong>', '</strong>' ); ?></p>
            <?php endif; ?>

        </div>

        <!-- Auto-enroll Courses -->
        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch" id="tutor-lms-auto-enroll">
            <label class="cozmoslabs-form-field-label" for="auto-enroll-courses"><?php esc_html_e( 'Auto Enroll', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="auto-enroll-courses" name="pms_tutor_lms_settings[auto_enroll]" value="yes" <?php echo ( isset( $options['auto_enroll'] ) ? checked( $options['auto_enroll'], 'yes', false ) : '' ); ?> />
                <label class="cozmoslabs-toggle-track" for="auto-enroll-courses"></label>
            </div>

            <div class="cozmoslabs-toggle-description">
                <label for="auto-enroll-courses" class="cozmoslabs-description"><?php esc_html_e( 'Automatically enroll users to Courses when a membership is created, updated or changed.', 'paid-member-subscriptions' ); ?></label>
            </div>
        </div>

        <div class="cozmoslabs-form-field-wrapper pms-restriction-type-information" id="individual-information">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Information', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-description">
                <p><?php esc_html_e( 'Select the required Subscription Plans for a specific Course:', 'paid-member-subscriptions' ); ?></p>

                <ul>
                    <li><?php echo sprintf( esc_html__( 'Go to the Course edit page and look for the %1$s Content Restriction %2$s meta-box.', 'paid-member-subscriptions' ), '<strong>', '</strong>' ); ?></li>
                    <li><?php echo sprintf( esc_html__( 'Select the %1$s Type Of Restriction%2$s: Message.', 'paid-member-subscriptions' ), '<strong>', '</strong>' ); ?></li>
                    <li><?php echo sprintf( esc_html__( 'Select the required %1$s Subscription Plans %2$s in the %1$s Display For %2$s field.', 'paid-member-subscriptions' ), '<strong>', '</strong>' ); ?></li>
                </ul>

            </div>
        </div>

        <div class="cozmoslabs-form-field-wrapper pms-restriction-type-information" id="category-information">
            <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Information', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-description">
                <p><?php esc_html_e( 'Associate TurorLMS Categories with PMS Subscription Plans:', 'paid-member-subscriptions' ); ?></p>

                <ul>
                    <li><?php echo sprintf( esc_html__( 'Go to the Subscription Plan edit page and look for the %1$s Tutor LMS Categories %2$s settings field.', 'paid-member-subscriptions' ), '<strong>', '</strong>' ); ?></li>
                    <li><?php echo sprintf( esc_html__( 'Select the %1$s Tutor LMS Categories %2$s you want to associate with this Subscription Plan.', 'paid-member-subscriptions' ), '<strong>', '</strong>' ); ?></li>
                    <li><?php echo sprintf( esc_html__( 'Members of this Subscription Plan will be able to access Courses within the %1$s selected categories%2$s.', 'paid-member-subscriptions' ), '<strong>', '</strong>' ); ?></li>
                </ul>

            </div>
        </div>

    </div>
</div>