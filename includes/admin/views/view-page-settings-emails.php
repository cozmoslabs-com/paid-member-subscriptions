<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML Output for the Email Settings tab
 */
?>

<!-- Available Email Merge Tags -->
<?php $available_merge_tags = PMS_Merge_Tags::get_merge_tags(); ?>

<?php $active_sub_tab = ( ! empty( $_GET['nav_sub_tab'] ) ? sanitize_text_field( $_GET['nav_sub_tab'] ) : 'user_emails' ); ?>

<!-- Sub-tab navigation -->
<ul class="subsubsub cozmoslabs-nav-sub-tab-wrapper">
    <li><a data-sub-tab-slug="user_emails"  href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pms-settings-page', 'tab' => 'emails', 'nav_sub_tab' => 'user_emails' ), 'admin.php' ) ) ); ?>" class="nav-sub-tab <?php echo ( $active_sub_tab == 'user_emails' ? 'current' : '' ) ?>"><?php esc_html_e( 'Member Emails', 'paid-member-subscriptions' ); ?></a></li>
    <li><a data-sub-tab-slug="admin_emails" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'pms-settings-page', 'tab' => 'emails', 'nav_sub_tab' => 'admin_emails' ), 'admin.php' ) ) ); ?>" class="nav-sub-tab <?php echo ( $active_sub_tab == 'admin_emails' ? 'current' : '' ) ?>"><?php esc_html_e( 'Administrator Emails', 'paid-member-subscriptions' ); ?></a></li>
</ul>

<div id="pms-settings-emails" class="cozmoslabs-settings">

    <!-- User Emails Sub Tab -->
    <div data-sub-tab-slug="user_emails" class="cozmoslabs-sub-tab cozmoslabs-sub-tab-user <?php echo ( $active_sub_tab == 'user_emails' ? 'tab-active' : '' ); ?>">

        <?php do_action( $this->menu_slug . '_tab_emails_before_user_tab', $this->options ); ?>

        <!-- General Email Options -->
        <?php $email_general_options = PMS_Emails::get_email_general_options(); ?>

        <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-user-emails-general">
            <h4 class="cozmoslabs-subsection-title">
                <?php esc_html_e( 'General Email Options', 'paid-member-subscriptions' ); ?>
                <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/settings/member-emails/?utm_source=pms-emails-settings&utm_medium=client-site&utm_campaign=pms-member-emails-docs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
            </h4>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="email-from-name"><?php esc_html_e( 'From Name', 'paid-member-subscriptions' ) ?></label>
                <input type="text" id="email-from-name" class="widefat" name="pms_emails_settings[email-from-name]" value="<?php echo ( isset($this->options['email-from-name']) ? esc_attr( $this->options['email-from-name'] ) : esc_attr( $email_general_options['email-from-name'] ) ) ?>">
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="email-from-email"><?php esc_html_e( 'From Email', 'paid-member-subscriptions' ) ?></label>
                <input type="text" id="email-from-email" class="widefat" name="pms_emails_settings[email-from-email]" value="<?php echo ( isset($this->options['email-from-email']) ? esc_attr( $this->options['email-from-email'] ) : esc_attr( $email_general_options['email-from-email'] ) ) ?>">
            </div>
        </div>


        <?php $email_actions  = PMS_Emails::get_email_actions(); ?>
        <?php $email_headings = PMS_Emails::get_email_headings(); ?>
        <?php $email_subjects = PMS_Emails::get_default_email_subjects( 'user' ); ?>
        <?php $email_content  = PMS_Emails::get_default_email_content( 'user' ); ?>

        <!-- Register Email -->
        <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-wysiwyg-container" id="cozmoslabs-user-register-email">
            <div class="cozmoslabs-email-heading-wrap">
                <h3 class="cozmoslabs-subsection-title"><?php echo esc_html( $email_headings['register'] ); ?></h3>

                <div class="cozmoslabs-toggle-switch">
                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="register-is-enabled" name="pms_emails_settings[register_is_enabled]" value="yes" <?php echo ( isset( $this->options['register_is_enabled'] ) ? 'checked' : '' ); ?> />
                        <label class="cozmoslabs-toggle-track" for="register-is-enabled"></label>
                    </div>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="email-register-subject"><?php esc_html_e( 'Subject', 'paid-member-subscriptions' ) ?></label>
                <input type="text" id="email-register-subject" class="widefat" name="pms_emails_settings[register_sub_subject]" value="<?php echo ( isset($this->options['register_sub_subject']) ? esc_attr( $this->options['register_sub_subject'] ) : esc_attr( $email_subjects['register'] ) ) ?>">
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper">
                <?php wp_editor( ( isset($this->options['register_sub']) ? $this->options['register_sub'] : $email_content['register'] ), 'emails_register_sub', array( 'textarea_name' => 'pms_emails_settings[register_sub]', 'editor_height' => 180 ) ); ?>

                <?php apply_filters( 'pms_user_register_email_available_tags', $available_merge_tags ); ?>
                <div class="cozmoslabs-available-tags">
                    <h3 class="cozmoslabs-tags-list-heading"><?php esc_html_e( 'Available Tags', 'paid-member-subscriptions' ); ?></h3>

                    <div class="cozmoslabs-tags-list">
                        <?php foreach( $available_merge_tags as $available_merge_tag ):?>
                            <input readonly spellcheck="false" type="text" class="pms-tag input" value="{{<?php echo esc_attr( $available_merge_tag ); ?>}}">
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- Other Emails -->
        <?php if( ( $key = array_search( 'register', $email_actions)) !== false) unset( $email_actions[$key] ); ?>

        <?php foreach( $email_actions as $action ): ?>

        <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-wysiwyg-container" id="cozmoslabs-user-<?php echo esc_attr( $action ); ?>-email">

            <div class="cozmoslabs-email-heading-wrap">
                <h3 class="cozmoslabs-subsection-title"><?php echo esc_html( $email_headings[$action] ); ?></h3>

                <div class="cozmoslabs-toggle-switch">
                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="<?php echo esc_attr( $action ); ?>-is-enabled" name="pms_emails_settings[<?php echo esc_attr( $action ); ?>_is_enabled]" value="yes" <?php echo ( isset( $this->options[$action . '_is_enabled'] ) ? 'checked' : '' ); ?> />
                        <label class="cozmoslabs-toggle-track" for="<?php echo esc_attr( $action ); ?>-is-enabled"></label>
                    </div>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="email-<?php echo esc_attr( $action ); ?>-sub-subject"><?php esc_html_e( 'Subject', 'paid-member-subscriptions' ) ?></label>
                <input type="text" id="email-<?php echo esc_attr( $action ) ?>-sub-subject" class="widefat" name="pms_emails_settings[<?php echo esc_attr( $action ); ?>_sub_subject]" value="<?php echo ( isset($this->options[$action.'_sub_subject']) ? esc_attr( $this->options[$action.'_sub_subject'] ) : esc_attr( $email_subjects[$action] ) ) ?>">
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper">
                <?php wp_editor( ( isset($this->options[$action.'_sub']) ? $this->options[$action.'_sub'] : $email_content[$action] ), 'emails-'. $action .'-sub', array( 'textarea_name' => 'pms_emails_settings['.$action.'_sub]', 'editor_height' => 180 ) ); ?>

                <?php apply_filters( 'pms_user_' . $action . '_email_available_tags', $available_merge_tags ); ?>
                <div class="cozmoslabs-available-tags">
                    <h3 class="cozmoslabs-tags-list-heading"><?php esc_html_e( 'Available Tags', 'paid-member-subscriptions' ); ?></h3>

                    <div class="cozmoslabs-tags-list">
                        <?php foreach( $available_merge_tags as $available_merge_tag ):?>
                            <input readonly spellcheck="false" type="text" class="pms-tag input" value="{{<?php echo esc_attr( $available_merge_tag ); ?>}}">
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>

        <?php endforeach; ?>

        <!-- Payment Failed Email -->
        <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-wysiwyg-container" id="cozmoslabs-user-payment_failed-email">
            <div class="cozmoslabs-email-heading-wrap">
                <h3 class="cozmoslabs-subsection-title"><?php echo esc_html( $email_headings['payment_failed'] ); ?></h3>

                <div class="cozmoslabs-toggle-switch">
                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="payment-failed-is-enabled" name="pms_emails_settings[payment_failed_is_enabled]" value="yes" <?php echo ( isset( $this->options['payment_failed_is_enabled'] ) ? 'checked' : '' ); ?> />
                        <label class="cozmoslabs-toggle-track" for="payment-failed-is-enabled"></label>
                    </div>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="email-payment-failed-subject"><?php esc_html_e( 'Subject', 'paid-member-subscriptions' ) ?></label>
                <input type="text" id="email-payment-failed-subject" class="widefat" name="pms_emails_settings[payment_failed_sub_subject]" value="<?php echo ( isset($this->options['payment_failed_sub_subject']) ? esc_attr( $this->options['payment_failed_sub_subject'] ) : esc_attr( $email_subjects['payment_failed'] ) ) ?>">
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper">
                <?php wp_editor( ( isset($this->options['payment_failed_sub']) ? $this->options['payment_failed_sub'] : $email_content['payment_failed'] ), 'payment_failed_sub', array( 'textarea_name' => 'pms_emails_settings[payment_failed_sub]', 'editor_height' => 180 ) ); ?>

                <?php apply_filters( 'pms_user_payment_failed_email_available_tags', $available_merge_tags ); ?>
                <div class="cozmoslabs-available-tags">
                    <h3 class="cozmoslabs-tags-list-heading"><?php esc_html_e( 'Available Tags', 'paid-member-subscriptions' ); ?></h3>

                    <div class="cozmoslabs-tags-list">
                        <?php foreach( $available_merge_tags as $available_merge_tag ):?>
                            <input readonly spellcheck="false" type="text" class="pms-tag input" value="{{<?php echo esc_attr( $available_merge_tag ); ?>}}">
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Renew Email -->
        <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-wysiwyg-container" id="cozmoslabs-user-renew-email">
            <div class="cozmoslabs-email-heading-wrap">
                <h3 class="cozmoslabs-subsection-title"><?php echo esc_html( $email_headings['renew'] ); ?></h3>

                <div class="cozmoslabs-toggle-switch">
                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="renew-is-enabled" name="pms_emails_settings[renew_is_enabled]" value="yes" <?php echo ( isset( $this->options['renew_is_enabled'] ) ? 'checked' : '' ); ?> />
                        <label class="cozmoslabs-toggle-track" for="renew-is-enabled"></label>
                    </div>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="email-renew-subject"><?php esc_html_e( 'Subject', 'paid-member-subscriptions' ) ?></label>
                <input type="text" id="email-renew-subject" class="widefat" name="pms_emails_settings[renew_sub_subject]" value="<?php echo ( isset($this->options['renew_sub_subject']) ? esc_attr( $this->options['renew_sub_subject'] ) : esc_attr( $email_subjects['renew'] ) ) ?>">
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper">
                <?php wp_editor( ( isset($this->options['renew_sub']) ? $this->options['renew_sub'] : $email_content['renew'] ), 'renew_sub', array( 'textarea_name' => 'pms_emails_settings[renew_sub]', 'editor_height' => 180 ) ); ?>

                <?php apply_filters( 'pms_user_renew_email_available_tags', $available_merge_tags ); ?>
                <div class="cozmoslabs-available-tags">
                    <h3 class="cozmoslabs-tags-list-heading"><?php esc_html_e( 'Available Tags', 'paid-member-subscriptions' ); ?></h3>

                    <div class="cozmoslabs-tags-list">
                        <?php foreach( $available_merge_tags as $available_merge_tag ):?>
                            <input readonly spellcheck="false" type="text" class="pms-tag input" value="{{<?php echo esc_attr( $available_merge_tag ); ?>}}">
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reset Password Email -->
        <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-wysiwyg-container" id="cozmoslabs-user-reset-password-email">
            <div class="cozmoslabs-email-heading-wrap">
                <h3 class="cozmoslabs-subsection-title"><?php echo esc_html( $email_headings['reset_password'] ); ?></h3>

                <div class="cozmoslabs-toggle-switch">
                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="reset-password-is-enabled" name="pms_emails_settings[reset_password_is_enabled]" value="yes" <?php echo ( isset( $this->options['reset_password_is_enabled'] ) ? 'checked' : '' ); ?> />
                        <label class="cozmoslabs-toggle-track" for="reset-password-is-enabled"></label>
                    </div>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="email-reset-password-subject"><?php esc_html_e( 'Subject', 'paid-member-subscriptions' ) ?></label>
                <input type="text" id="email-reset-password-subject" class="widefat" name="pms_emails_settings[reset_password_sub_subject]" value="<?php echo ( isset($this->options['reset_password_sub_subject']) ? esc_attr( $this->options['reset_password_sub_subject'] ) : esc_attr( $email_subjects['reset_password'] ) ) ?>">
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper">
                <?php wp_editor( ( isset($this->options['reset_password_sub']) ? $this->options['reset_password_sub'] : $email_content['reset_password'] ), 'reset_password_sub', array( 'textarea_name' => 'pms_emails_settings[reset_password_sub]', 'editor_height' => 180 ) ); ?>

                <?php apply_filters( 'pms_user_reset_password_email_available_tags', $available_merge_tags ); ?>
                <div class="cozmoslabs-available-tags">
                    <h3 class="cozmoslabs-tags-list-heading"><?php esc_html_e( 'Available Tags', 'paid-member-subscriptions' ); ?></h3>

                    <div class="cozmoslabs-tags-list">
                        <?php foreach( $available_merge_tags as $available_merge_tag ):?>
                            <input readonly spellcheck="false" type="text" class="pms-tag input" value="{{<?php echo esc_attr( $available_merge_tag ); ?>}}">
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>


        <?php do_action( $this->menu_slug . '_tab_emails_after_user_tab', $this->options ); ?>

    </div>


    <!-- Admin Emails Sub Tab -->
    <div data-sub-tab-slug="admin_emails" class="cozmoslabs-sub-tab cozmoslabs-sub-tab-admin <?php echo ( $active_sub_tab == 'admin_emails' ? 'tab-active' : '' ); ?>">

        <?php do_action( $this->menu_slug . '_tab_emails_before_admin_tab', $this->options ); ?>

        <!-- General Email Options -->
        <?php $email_general_options = PMS_Emails::get_email_general_options(); ?>

        <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-admin-emails-general">
            <h3 class="cozmoslabs-subsection-title">
                <?php esc_html_e( 'Enable Administrator Emails', 'paid-member-subscriptions' ); ?>
                <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/settings/administrator-emails/?utm_source=pms-emails-settings&utm_medium=client-site&utm_campaign=pms-admin-emails-docs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
            </h3>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                <label class="cozmoslabs-form-field-label" for="emails-admin-on"><?php esc_html_e( 'Send Administrator Emails', 'paid-member-subscriptions' ) ?></label>

                <div class="cozmoslabs-toggle-container">
                    <input type="checkbox" id="emails-admin-on" name="pms_emails_settings[admin_emails_on]" value="1" <?php echo ( isset( $this->options['admin_emails_on'] ) ? 'checked' : '' ); ?> />
                    <label class="cozmoslabs-toggle-track" for="emails-admin-on"></label>
                </div>
                <div class="cozmoslabs-toggle-description">
                    <label for="emails-admin-on" class="cozmoslabs-description"><?php esc_html_e( 'By checking this option administrator emails are enabled.', 'paid-member-subscriptions' ); ?></label>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="emails-admin"><?php esc_html_e( 'Administrator Emails', 'paid-member-subscriptions' ); ?></label>
                <input type="text" id="emails-admin" class="widefat" name="pms_emails_settings[admin_emails]" value="<?php echo ( isset($this->options['admin_emails']) ? esc_attr( $this->options['admin_emails'] ) : '' ); ?>">
                <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Add a comma-separated list of email addresses to receive member subscription status change notifications.', 'paid-member-subscriptions' ); ?></p>
            </div>
        </div>

        <?php $email_actions  = PMS_Emails::get_email_actions(); ?>
        <?php $email_headings = PMS_Emails::get_email_headings(); ?>
        <?php $email_subjects = PMS_Emails::get_default_email_subjects( 'admin' ); ?>
        <?php $email_content  = PMS_Emails::get_default_email_content( 'admin' ); ?>

        <!-- Register Email -->
        <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-wysiwyg-container" id="cozmoslabs-admin-register-email">
            <div class="cozmoslabs-email-heading-wrap">
                <h3 class="cozmoslabs-subsection-title"><?php echo esc_html( $email_headings['register'] ); ?></h3>

                <div class="cozmoslabs-toggle-switch">
                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="register-admin-is-enabled" name="pms_emails_settings[register_admin_is_enabled]" value="yes" <?php echo ( isset( $this->options['register_admin_is_enabled'] ) ? 'checked' : '' ); ?> />
                        <label class="cozmoslabs-toggle-track" for="register-admin-is-enabled"></label>
                    </div>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="email-register-subject"><?php esc_html_e( 'Subject', 'paid-member-subscriptions' ) ?></label>
                <input type="text" id="email-register-subject" class="widefat" name="pms_emails_settings[register_sub_subject_admin]" value="<?php echo ( isset($this->options['register_sub_subject_admin']) ? esc_attr( $this->options['register_sub_subject_admin'] ) : esc_attr( $email_subjects['register'] ) ) ?>">
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper">
                <?php wp_editor( ( isset( $this->options['register_sub_admin'] ) ? $this->options['register_sub_admin'] : $email_content['register'] ), 'emails_register_sub_admin', array( 'textarea_name' => 'pms_emails_settings[register_sub_admin]', 'editor_height' => 180 ) ); ?>

                <?php apply_filters( 'pms_admin_register_email_available_tags', $available_merge_tags ); ?>
                <div class="cozmoslabs-available-tags">
                    <h3 class="cozmoslabs-tags-list-heading"><?php esc_html_e( 'Available Tags', 'paid-member-subscriptions' ); ?></h3>

                    <div class="cozmoslabs-tags-list">
                        <?php foreach( $available_merge_tags as $available_merge_tag ):?>
                            <input readonly spellcheck="false" type="text" class="pms-tag input" value="{{<?php echo esc_attr( $available_merge_tag ); ?>}}">
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Other Emails -->
        <?php if( ( $key = array_search( 'register', $email_actions)) !== false) unset( $email_actions[$key] ); ?>

        <?php foreach( $email_actions as $action ): ?>

            <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-wysiwyg-container" id="cozmoslabs-admin-<?php echo esc_attr( $action ); ?>-email">
                <div class="cozmoslabs-email-heading-wrap">
                    <h3 class="cozmoslabs-subsection-title"><?php echo esc_html( $email_headings[$action] ); ?></h3>

                    <div class="cozmoslabs-toggle-switch">
                        <div class="cozmoslabs-toggle-container">
                            <input type="checkbox" id="<?php echo esc_attr( $action ); ?>-admin-is-enabled" name="pms_emails_settings[<?php echo esc_attr( $action ); ?>_admin_is_enabled]" value="yes" <?php echo ( isset( $this->options[$action . '_admin_is_enabled'] ) ? 'checked' : '' ); ?> />
                            <label class="cozmoslabs-toggle-track" for="<?php echo esc_attr( $action ); ?>-admin-is-enabled"></label>
                        </div>
                    </div>
                </div>

                <div class="cozmoslabs-form-field-wrapper">
                    <label class="cozmoslabs-form-field-label" for="email-<?php echo esc_attr( $action ); ?>-sub-subject-admin"><?php esc_html_e( 'Subject', 'paid-member-subscriptions' ) ?></label>
                    <input type="text" id="email-<?php echo esc_attr( $action ); ?>-sub-subject-admin" class="widefat" name="pms_emails_settings[<?php echo esc_attr( $action ); ?>_sub_subject_admin]" value="<?php echo ( isset($this->options[$action.'_sub_subject_admin']) ? esc_attr( $this->options[$action.'_sub_subject_admin'] ) : esc_attr( $email_subjects[$action] ) ); ?>">
                </div>

                <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper">
                    <?php wp_editor( ( isset($this->options[$action.'_sub_admin']) ? $this->options[$action.'_sub_admin'] : $email_content[$action] ), 'emails-'. $action .'-sub-admin', array( 'textarea_name' => 'pms_emails_settings['.$action.'_sub_admin]', 'editor_height' => 180 ) ); ?>

                    <?php apply_filters( 'pms_admin_' . $action . '_email_available_tags', $available_merge_tags ); ?>
                    <div class="cozmoslabs-available-tags">
                        <h3 class="cozmoslabs-tags-list-heading"><?php esc_html_e( 'Available Tags', 'paid-member-subscriptions' ); ?></h3>

                        <div class="cozmoslabs-tags-list">
                            <?php foreach( $available_merge_tags as $available_merge_tag ):?>
                                <input readonly spellcheck="false" type="text" class="pms-tag input" value="{{<?php echo esc_attr( $available_merge_tag ); ?>}}">
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>

        <!-- Renew Email -->
        <div class="cozmoslabs-form-subsection-wrapper cozmoslabs-wysiwyg-container" id="cozmoslabs-admin-renew-email">
            <div class="cozmoslabs-email-heading-wrap">
                <h3 class="cozmoslabs-subsection-title"><?php echo esc_html( $email_headings['renew'] ); ?></h3>

                <div class="cozmoslabs-toggle-switch">
                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" id="renew-admin-is-enabled" name="pms_emails_settings[renew_admin_is_enabled]" value="yes" <?php echo ( isset( $this->options['renew_admin_is_enabled'] ) ? 'checked' : '' ); ?> />
                        <label class="cozmoslabs-toggle-track" for="renew-admin-is-enabled"></label>
                    </div>
                </div>
            </div>

            <div class="cozmoslabs-form-field-wrapper">
                <label class="cozmoslabs-form-field-label" for="email-renew-subject"><?php esc_html_e( 'Subject', 'paid-member-subscriptions' ) ?></label>
                <input type="text" id="email-renew-subject" class="widefat" name="pms_emails_settings[renew_sub_subject_admin]" value="<?php echo ( isset($this->options['renew_sub_subject_admin']) ? esc_attr( $this->options['renew_sub_subject_admin'] ) : esc_attr( $email_subjects['renew'] ) ) ?>">
            </div>

            <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper">
                <?php wp_editor( ( isset( $this->options['renew_sub_admin'] ) ? $this->options['renew_sub_admin'] : $email_content['renew'] ), 'emails_renew_sub_admin', array( 'textarea_name' => 'pms_emails_settings[renew_sub_admin]', 'editor_height' => 180 ) ); ?>

                <?php apply_filters( 'pms_admin_renew_email_available_tags', $available_merge_tags ); ?>
                <div class="cozmoslabs-available-tags">
                    <h3 class="cozmoslabs-tags-list-heading"><?php esc_html_e( 'Available Tags', 'paid-member-subscriptions' ); ?></h3>

                    <div class="cozmoslabs-tags-list">
                        <?php foreach( $available_merge_tags as $available_merge_tag ):?>
                            <input readonly spellcheck="false" type="text" class="pms-tag input" value="{{<?php echo esc_attr( $available_merge_tag ); ?>}}">
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php do_action( $this->menu_slug . '_tab_emails_after_admin_tab', $this->options ); ?>

    </div>

    <?php do_action( $this->menu_slug . '_tab_emails_after_content', $this->options ); ?>
</div>
