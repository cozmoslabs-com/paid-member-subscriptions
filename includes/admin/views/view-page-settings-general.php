<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML Output for the General Settings tab
 */
?>

<div class="pms-form-fields cozmoslabs-settings">
    <!-- Form Styles -->
    <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-form-styles">
        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Design & User Experience', 'paid-member-subscriptions' ); ?></h4>
        <p class="cozmoslabs-description" style="margin-bottom: 5px;"><?php esc_html_e( 'Choose a style that better suits your website.', 'paid-member-subscriptions' ); ?></p>
        <p class="cozmoslabs-description"><?php esc_html_e( 'The default style is there to let you customize the CSS and in general will receive the look and feel from your own themes styling.', 'paid-member-subscriptions' ); ?></p>

        <div class="cozmoslabs-form-field-wrapper">
            <?php
            if ( defined( 'PMS_PAID_PLUGIN_DIR' ) && file_exists( PMS_PAID_PLUGIN_DIR . '/add-ons-basic/form-designs/form-designs.php' ) ) {

                 echo pms_render_forms_design_selector(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            } elseif ( PAID_MEMBER_SUBSCRIPTIONS === 'Paid Member Subscriptions' ) {

                echo pms_display_form_designs_preview(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                printf( esc_html__( '%3$sBeautify your forms using pre-made templates. Enable Form Designs by upgrading to %1$sBasic or PRO versions%2$s.%4$s', 'paid-member-subscriptions' ),'<a href="https://www.cozmoslabs.com/wordpress-paid-member-subscriptions/?utm_source=pms-general-settings&utm_medium=client-site&utm_campaign=pms-form-design-templates#pricing" target="_blank">', '</a>', '<p class="cozmoslabs-description cozmoslabs-description-upsell">', '</p>' );
                    
            }
            ?>
        </div>
    </div>


    <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-login-registration-flow">
        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Optimize The login and Registration flow for your members', 'paid-member-subscriptions' ); ?></h4>

    <!-- Automatically Log In -->
        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="automatically-log-in"><?php esc_html_e( 'Automatically Log In', 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="automatically-log-in" name="pms_general_settings[automatically_log_in]" value="1" <?php echo isset( $this->options['automatically_log_in'] ) ? 'checked' : '' ?> />
                <label class="cozmoslabs-toggle-track" for="automatically-log-in"></label>
            </div>
            <div class="cozmoslabs-toggle-description">
                <label for="automatically-log-in" class="cozmoslabs-description"><?php esc_html_e( 'Select "Yes" to automatically log in new members after successful registration.', 'paid-member-subscriptions' ); ?></label>
            </div>
        </div>


        <!-- Prevent Account Sharing -->
        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="prevent-account-sharing"><?php esc_html_e( 'Prevent Account Sharing' , 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="prevent-account-sharing" name="pms_general_settings[prevent_account_sharing]" value="1" <?php echo ( isset( $this->options['prevent_account_sharing'] ) ? 'checked' : '' ); ?> />
                <label class="cozmoslabs-toggle-track" for="prevent-account-sharing"></label>
            </div>
            <div class="cozmoslabs-toggle-description">
                <label for="prevent-account-sharing" class="cozmoslabs-description"><?php esc_html_e( 'Prevent users from being logged in with the same account from multiple places at the same time. ', 'paid-member-subscriptions' ); ?></label>
            </div>
            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'If the current user\'s session has been taken over by a newer session, we will log him out and he will have to login again. This will make it inconvenient for members to share their login credentials.', 'paid-member-subscriptions' ); ?></p>
        </div>

        <!-- Redirect Default WordPress Pages -->
        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="redirect-default-wp"><?php esc_html_e( 'Redirect Default WordPress Pages' , 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="redirect-default-wp" name="pms_general_settings[redirect_default_wp]" value="1" <?php echo ( isset( $this->options['redirect_default_wp'] ) ? 'checked' : '' ); ?> />
                <label class="cozmoslabs-toggle-track" for="redirect-default-wp"></label>
            </div>
            <div class="cozmoslabs-toggle-description">
                <label for="redirect-default-wp" class="cozmoslabs-description"><?php esc_html_e( 'Redirect users from the default WordPress login ( wp-login.php ), register and lost password forms to the front-end ones created with Paid Member Subscriptions.', 'paid-member-subscriptions' ); ?></label>
            </div>
            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php printf( esc_html__( 'This option can be bypassed by adding the %s parameter to your login page URL: %s', 'paid-member-subscriptions' ), '<strong>pms_force_wp_login=true</strong>', '<a href="'.esc_url( home_url( 'wp-login.php?pms_force_wp_login=true' ) ).'">'. esc_url( home_url( 'wp-login.php?pms_force_wp_login=true' ) ) .'</a>' ) ?></p>
        </div>


        <!-- Load CSS -->
        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="use-pms-css"><?php esc_html_e( 'Load CSS' , 'paid-member-subscriptions' ) ?></label>

            <div class="cozmoslabs-toggle-container">
                <input type="checkbox" id="use-pms-css" name="pms_general_settings[use_pms_css]" value="1" <?php echo ( isset( $this->options['use_pms_css'] ) ? 'checked' : '' ); ?> >
                <label class="cozmoslabs-toggle-track" for="use-pms-css"></label>
            </div>
            <div class="cozmoslabs-toggle-description">
                <label for="use-pms-css" class="cozmoslabs-description"><?php esc_html_e( 'Use Paid Member Subscriptions\'s own CSS in the front-end.', 'paid-member-subscriptions' ); ?></label>
            </div>
        </div>

    </div>

    <div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-subsection-membership-pages">
        <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Membership Pages', 'paid-member-subscriptions' ); ?></h4>
        <p class="cozmoslabs-description"><?php esc_html_e( 'These pages need to be set so that Paid Member Subscriptions knows where to send users.', 'paid-member-subscriptions' ); ?></p>

        <!-- Register Page -->
        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="register-page">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M15.5 9.5C16.0523 9.5 16.5 9.05228 16.5 8.5C16.5 7.94772 16.0523 7.5 15.5 7.5C14.9477 7.5 14.5 7.94772 14.5 8.5C14.5 9.05228 14.9477 9.5 15.5 9.5ZM15.5 11C16.8807 11 18 9.88071 18 8.5C18 7.11929 16.8807 6 15.5 6C14.1193 6 13 7.11929 13 8.5C13 9.88071 14.1193 11 15.5 11ZM13.25 17V15C13.25 13.4812 12.0188 12.25 10.5 12.25H6.5C4.98122 12.25 3.75 13.4812 3.75 15V17H5.25V15C5.25 14.3096 5.80964 13.75 6.5 13.75H10.5C11.1904 13.75 11.75 14.3096 11.75 15V17H13.25ZM20.25 15V17H18.75V15C18.75 14.3096 18.1904 13.75 17.5 13.75H15V12.25H17.5C19.0188 12.25 20.25 13.4812 20.25 15ZM9.5 8.5C9.5 9.05228 9.05228 9.5 8.5 9.5C7.94772 9.5 7.5 9.05228 7.5 8.5C7.5 7.94772 7.94772 7.5 8.5 7.5C9.05228 7.5 9.5 7.94772 9.5 8.5ZM11 8.5C11 9.88071 9.88071 11 8.5 11C7.11929 11 6 9.88071 6 8.5C6 7.11929 7.11929 6 8.5 6C9.88071 6 11 7.11929 11 8.5Z" fill="#1E1E1E"/>
                </svg>

                <?php esc_html_e( 'Registration', 'paid-member-subscriptions' ) ?>
            </label>

            <select id="register-page" name="pms_general_settings[register_page]" class="widefat">
                <option value="-1"><?php esc_html_e( 'Choose...', 'paid-member-subscriptions' ) ?></option>

                <?php
                foreach( get_pages() as $page )
                    echo '<option value="' . esc_attr( $page->ID ) . '"' . ( isset( $this->options['register_page'] ) ? selected( $this->options['register_page'], $page->ID, false ) : '') . '>' . esc_html( $page->post_title ) . ' ( ID: ' . esc_attr( $page->ID ) . ')' . '</option>';
                ?>
            </select>

            <?php if ( isset( $this->options['register_page'] ) && $this->options['register_page'] != -1  ) : ?>
                <a class="button button-secondary" href="<?php echo esc_url( get_permalink( $this->options['register_page'] ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'paid-member-subscriptions' ); ?></a>
                <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'post.php?post='. $this->options['register_page'] .'&action=edit' ) ); ?>" target="_blank"><?php esc_html_e( 'Edit', 'paid-member-subscriptions' ); ?></a>
            <?php endif; ?>

            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo wp_kses_post( __( 'Select the page containing the <strong>[pms-register]</strong> shortcode.', 'paid-member-subscriptions' ) ); ?></p>
        </div>


        <!-- Login Page -->
        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="login-page">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_7_1705)">
                        <mask id="path-1-inside-1_7_1705" fill="white">
                            <rect x="6" y="10" width="12" height="10" rx="1"/>
                        </mask>
                        <rect x="6" y="10" width="12" height="10" rx="1" stroke="#1E1E1E" stroke-width="3" mask="url(#path-1-inside-1_7_1705)"/>
                        <path d="M15 10V7C15 5.34315 13.6569 4 12 4V4C10.3431 4 9 5.34315 9 7V10" stroke="#1E1E1E" stroke-width="1.5"/>
                    </g>
                    <defs>
                        <clipPath id="clip0_7_1705">
                            <rect width="24" height="24" fill="white"/>
                        </clipPath>
                    </defs>
                </svg>

                <?php esc_html_e( 'Login', 'paid-member-subscriptions' ) ?>
            </label>

            <select id="login-page" name="pms_general_settings[login_page]" class="widefat">
                <option value="-1"><?php esc_html_e( 'Choose...', 'paid-member-subscriptions' ) ?></option>

                <?php
                foreach( get_pages() as $page )
                    echo '<option value="' . esc_attr( $page->ID ) . '"' . ( isset( $this->options['login_page'] ) ? selected( $this->options['login_page'], $page->ID, false ) : '') . '>' . esc_html( $page->post_title ) . ' ( ID: ' . esc_attr( $page->ID ) . ')' . '</option>';
                ?>
            </select>

            <?php if ( isset( $this->options['login_page'] ) && $this->options['login_page'] != -1 ) : ?>
                <a class="button button-secondary" href="<?php echo esc_url( get_permalink( $this->options['login_page'] ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'paid-member-subscriptions' ); ?></a>
                <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'post.php?post='. $this->options['login_page'] .'&action=edit' ) ); ?>" target="_blank"><?php esc_html_e( 'Edit', 'paid-member-subscriptions' ); ?></a>
            <?php endif; ?>

            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo wp_kses_post( __( 'Select the page containing the <strong>[pms-login]</strong> shortcode.', 'paid-member-subscriptions' ) ); ?></p>
        </div>

        <!-- Account -->
        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="account-page">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M14 20H20V7.86207L12 4L4 7.86207V20H10H14ZM14 18.5H18.5V8.80358L12 5.66565L5.5 8.80358V18.5H10V13H14V18.5Z" fill="#1E1E1E"/>
                </svg>


                <?php esc_html_e( 'Account', 'paid-member-subscriptions' ) ?>
            </label>

            <select id="account-page" name="pms_general_settings[account_page]" class="widefat">
                <option value="-1"><?php esc_html_e( 'Choose...', 'paid-member-subscriptions' ) ?></option>

                <?php
                foreach( get_pages() as $page )
                    echo '<option value="' . esc_attr( $page->ID ) . '"' . ( isset( $this->options['account_page'] ) ? selected( $this->options['account_page'], $page->ID, false ) : '') . '>' . esc_html( $page->post_title ) . ' ( ID: ' . esc_attr( $page->ID ) . ')' . '</option>';
                ?>
            </select>

            <?php if ( isset( $this->options['account_page'] ) && $this->options['account_page'] != -1 ) : ?>
                <a class="button button-secondary" href="<?php echo esc_url( get_permalink( $this->options['account_page'] ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'paid-member-subscriptions' ); ?></a>
                <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'post.php?post='. $this->options['account_page'] .'&action=edit' ) ); ?>" target="_blank"><?php esc_html_e( 'Edit', 'paid-member-subscriptions' ); ?></a>
            <?php endif; ?>

            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo wp_kses_post( __( 'Select the page containing the <strong>[pms-account]</strong> shortcode.', 'paid-member-subscriptions' ) ); ?></p>
        </div>

        <!-- Lost Password -->
        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="lost-password-page">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="9" cy="12" r="2.75" stroke="#1E1E1E" stroke-width="2.5"/>
                    <rect x="11" y="10.75" width="8" height="2.5" fill="#1E1E1E"/>
                    <rect x="15" y="12" width="2.5" height="4" fill="#1E1E1E"/>
                </svg>


                <?php esc_html_e( 'Password Reset', 'paid-member-subscriptions' ) ?>
            </label>

            <select id="lost-password-page" name="pms_general_settings[lost_password_page]" class="widefat">
                <option value="-1"><?php esc_html_e( 'Choose...', 'paid-member-subscriptions' ) ?></option>

                <?php
                foreach( get_pages() as $page )
                    echo '<option value="' . esc_attr( $page->ID ) . '"' . ( isset( $this->options['lost_password_page'] ) ? selected( $this->options['lost_password_page'], $page->ID, false ) : '') . '>' . esc_html( $page->post_title ) . ' ( ID: ' . esc_attr( $page->ID ) . ')' . '</option>';
                ?>
            </select>

            <?php if ( isset( $this->options['lost_password_page'] ) && $this->options['lost_password_page'] != -1 ) : ?>
                <a class="button button-secondary" href="<?php echo esc_url( get_permalink( $this->options['lost_password_page'] ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'paid-member-subscriptions' ); ?></a>
                <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'post.php?post='. $this->options['lost_password_page'] .'&action=edit' ) ); ?>" target="_blank"><?php esc_html_e( 'Edit', 'paid-member-subscriptions' ); ?></a>
            <?php endif; ?>

            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo wp_kses_post( __( 'Select the page containing the <strong>[pms-recover-password]</strong> shortcode.', 'paid-member-subscriptions' ) ); ?></p>
        </div>

        <!-- Register Success Page -->
        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="register-success-page">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11.5347 15.3973L11.8672 16.0299L11.5347 15.3973L8.29382 17.1011L8.91276 13.4924C8.9684 13.168 8.86085 12.837 8.62517 12.6073L6.00327 10.0515L9.62664 9.52504C9.95235 9.47771 10.2339 9.27314 10.3796 8.97799L12 5.69466L13.6204 8.978C13.7661 9.27314 14.0476 9.47771 14.3734 9.52504L17.9967 10.0515L15.3748 12.6073L15.8983 13.1443L15.3748 12.6073C15.1392 12.837 15.0316 13.168 15.0872 13.4924L15.7062 17.1011L12.4653 15.3973C12.174 15.2442 11.826 15.2442 11.5347 15.3973Z" stroke="#1E1E1E" stroke-width="1.5"/>
                </svg>

                <?php esc_html_e( 'Registration Success Page', 'paid-member-subscriptions' ) ?>
            </label>

            <select id="register-success-page" name="pms_general_settings[register_success_page]" class="widefat">
                <option value="-1"><?php esc_html_e( 'Choose...', 'paid-member-subscriptions' ) ?></option>

                <?php
                foreach( get_pages() as $page )
                    echo '<option value="' . esc_attr( $page->ID ) . '"' . ( isset( $this->options['register_success_page'] ) ? selected( $this->options['register_success_page'], $page->ID, false ) : '') . '>' . esc_html( $page->post_title ) . ' ( ID: ' . esc_attr( $page->ID ) . ')' . '</option>';
                ?>
            </select>

            <?php if ( isset( $this->options['register_success_page'] ) && $this->options['register_success_page'] != -1 ) : ?>
                <a class="button button-secondary" href="<?php echo esc_url( get_permalink( $this->options['register_success_page'] ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'paid-member-subscriptions' ); ?></a>
                <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'post.php?post='. $this->options['register_success_page'] .'&action=edit' ) ); ?>" target="_blank"><?php esc_html_e( 'Edit', 'paid-member-subscriptions' ); ?></a>
            <?php endif; ?>

            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Select the page where you wish to redirect your newly registered members.', 'paid-member-subscriptions' ); ?></p>
        </div>

        <!-- Edit Profile -->
        <?php
            // make sure PB is active.
            if ( defined('PROFILE_BUILDER') ) :
        ?>
        <div class="cozmoslabs-form-field-wrapper">
            <label class="cozmoslabs-form-field-label" for="edit-profile-shortcode">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M15.5 9.5C16.0523 9.5 16.5 9.05228 16.5 8.5C16.5 7.94772 16.0523 7.5 15.5 7.5C14.9477 7.5 14.5 7.94772 14.5 8.5C14.5 9.05228 14.9477 9.5 15.5 9.5ZM15.5 11C16.8807 11 18 9.88071 18 8.5C18 7.11929 16.8807 6 15.5 6C14.1193 6 13 7.11929 13 8.5C13 9.88071 14.1193 11 15.5 11ZM13.25 17V15C13.25 13.4812 12.0188 12.25 10.5 12.25H6.5C4.98122 12.25 3.75 13.4812 3.75 15V17H5.25V15C5.25 14.3096 5.80964 13.75 6.5 13.75H10.5C11.1904 13.75 11.75 14.3096 11.75 15V17H13.25ZM20.25 15V17H18.75V15C18.75 14.3096 18.1904 13.75 17.5 13.75H15V12.25H17.5C19.0188 12.25 20.25 13.4812 20.25 15ZM9.5 8.5C9.5 9.05228 9.05228 9.5 8.5 9.5C7.94772 9.5 7.5 9.05228 7.5 8.5C7.5 7.94772 7.94772 7.5 8.5 7.5C9.05228 7.5 9.5 7.94772 9.5 8.5ZM11 8.5C11 9.88071 9.88071 11 8.5 11C7.11929 11 6 9.88071 6 8.5C6 7.11929 7.11929 6 8.5 6C9.88071 6 11 7.11929 11 8.5Z" fill="#1E1E1E"/>
                </svg>

                <?php esc_html_e( 'Edit Profile Form', 'paid-member-subscriptions' ) ?>
            </label>

            <select id="edit-profile-shortcode" name="pms_general_settings[edit_profile_shortcode]" class="widefat">
                <option value="-1"><?php esc_html_e( 'Default Paid Member Subscriptions', 'paid-member-subscriptions' ) ?></option>
                <option value="wppb-default-edit-profile" <?php if ( isset($this->options['edit_profile_shortcode']) && $this->options['edit_profile_shortcode'] == 'wppb-default-edit-profile' ) echo 'selected'; ?>><?php esc_html_e( 'Default Profile Builder', 'paid-member-subscriptions' ); ?></option>
                <?php
                $args = array(
                    'post_type' => 'wppb-epf-cpt',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'orderby' => 'date',
                    'order' => 'DESC'
                );
                $edit_profile_forms = get_posts( $args );

                foreach ( $edit_profile_forms as $key => $value ){
                    echo '<option value="'. esc_attr( $value->post_title ) .'"';
                    if ( isset($this->options['edit_profile_shortcode']) && $this->options['edit_profile_shortcode'] == $value->post_title )
                        echo ' selected';

                    echo '>' . esc_html( $value->post_title ) . '</option>';
                }
                ?>

            </select>

            <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo wp_kses_post( __( '<b>Profile Builder</b> is enabled. <b>You can replace the edit profile in the [pms-account] page</b> with the Profile Builder alternative.', 'paid-member-subscriptions' ) ); ?></p>
        </div>

        <?php endif;?>
    </div>

</div>
