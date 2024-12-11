<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML Output for the Content Restriction tab
 */
?>

<!-- Type of Restriction -->
<div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-restriction-type">

    <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'General', 'paid-member-subscriptions' ); ?></h4>

    <div class="cozmoslabs-form-field-wrapper">

        <label class="cozmoslabs-form-field-label">
            <?php esc_html_e( 'Type of Restriction', 'paid-member-subscriptions' ); ?>
        </label>

        <div class="cozmoslabs-radio-inputs-row">
            <?php
                $content_restrict_types = apply_filters( 'pms_general_content_restrict_types', array( 'message' => esc_html__( 'Message', 'paid-member-subscriptions' ), 'redirect' => esc_html__( 'Redirect', 'paid-member-subscriptions' ), 'template' => esc_html__( 'Template', 'paid-member-subscriptions' ) ) );

                $current_type = 0;

                foreach( $content_restrict_types as $type_slug => $type_label ): ?>

                    <label for="pms-content-restrict-type-<?php echo esc_attr( $type_slug ); ?>">

                         <input type="radio" id="pms-content-restrict-type-<?php echo esc_attr( $type_slug ); ?>" value="<?php echo esc_attr( $type_slug ); ?>" <?php if( ( $current_type == 0 && empty( $this->options['content_restrict_type'] ) ) || ( ! empty( $this->options['content_restrict_type'] ) && $this->options['content_restrict_type'] == $type_slug ) ) echo 'checked="checked"'; ?> name="pms_content_restriction_settings[content_restrict_type]">
                         <?php echo esc_html( $type_label ); ?>

                    </label>

                    <?php
                    $current_type++;

                 endforeach;
            ?>
        </div>

        <p class="cozmoslabs-description cozmoslabs-description-space-left" style="margin-top: 10px;"><?php esc_html_e( 'If you select "Messages" the post\'s content will be protected by being replaced with a custom message.', 'paid-member-subscriptions' ); ?></p>
        <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'If you select "Redirect" the post\'s content will be protected by redirecting the user to the URL you specify. The redirect happens only when accessing a single post. On archive pages the restriction message will be displayed, instead of the content.', 'paid-member-subscriptions' ); ?></p>
        <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'If you select "Template" the template for the restricted post/page will change to the selected template in the Restriction Template section below.', 'paid-member-subscriptions' ); ?></p>

    </div>
    <?php do_action('pms_content_restriction_extra_settings', $this->options ); ?>
</div>

<!-- Redirect URL -->
<div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-restriction-redirect">

    <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Restriction Redirect', 'paid-member-subscriptions' ); ?></h4>

    <div class="cozmoslabs-form-field-wrapper">

        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Redirect URL', 'paid-member-subscriptions' ); ?></label>
        <input type="text" class="widefat" name="pms_content_restriction_settings[content_restrict_redirect_url]" value="<?php echo ( ! empty( $this->options['content_restrict_redirect_url'] ) ? esc_url( $this->options['content_restrict_redirect_url'] ) : '' ); ?>" />

        <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Redirect users accessing restricted pages to the specified URL.', 'paid-member-subscriptions' ); ?></p>

    </div>

    <div class="cozmoslabs-form-field-wrapper">

        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Non-Member Redirect URL', 'paid-member-subscriptions' ); ?></label>
        <input type="text" class="widefat" name="pms_content_restriction_settings[content_restrict_non_member_redirect_url]" value="<?php echo ( ! empty( $this->options['content_restrict_non_member_redirect_url'] ) ? esc_url( $this->options['content_restrict_non_member_redirect_url'] ) : '' ); ?>" />

        <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'If this field is not empty, logged-in non-members are redirected to the specified URL.', 'paid-member-subscriptions' ); ?></p>
        <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'If not specified, all users accessing a restricted page will be redirected to the "Redirect URL" from above.', 'paid-member-subscriptions' ); ?></p>

    </div>
</div>

<!-- Restrict Messages -->
<div class="cozmoslabs-form-subsection-wrapper cozmoslabs-wysiwyg-container" id="cozmoslabs-restriction-messages">

    <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Restriction Messages', 'paid-member-subscriptions' ); ?></h4>

    <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">
        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Messages for logged-out users', 'paid-member-subscriptions' ); ?></label>
        <?php wp_editor( pms_get_restriction_content_message( 'logged_out' ), 'messages_logged_out', array( 'textarea_name' => 'pms_content_restriction_settings[logged_out]', 'editor_height' => 180 ) ); ?>
    </div>

    <div class="cozmoslabs-form-field-wrapper cozmoslabs-wysiwyg-wrapper cozmoslabs-wysiwyg-indented">
        <label class="cozmoslabs-form-field-label"><?php esc_html_e( 'Messages for logged-in non-member users', 'paid-member-subscriptions' ); ?></label>
        <?php wp_editor( pms_get_restriction_content_message( 'non_members' ), 'messages_non_members', array( 'textarea_name' => 'pms_content_restriction_settings[non_members]', 'editor_height' => 180 ) ); ?>
    </div>
</div>
    <!-- Other restrict messages -->
    <?php do_action( $this->menu_slug . '_tab_content_restriction_restrict_messages_bottom', $this->options); ?>


<!-- Template Restrict -->
<div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-restriction-template">
    <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Restriction Template', 'paid-member-subscriptions' ); ?></h4>

    <div class="cozmoslabs-form-field-wrapper">
        <label for="pms-content-restriction-template-select" class="cozmoslabs-form-field-label"><?php esc_html_e( 'Template', 'paid-member-subscriptions' ); ?></label>
        <select id="pms-content-restriction-template-select" name="pms_content_restriction_settings[content_restrict_template]">
            <option value=''><?php esc_html_e( 'Default', 'paid-member-subscriptions' ) ?></option>
            <?php
            $selected_template = ( ! empty( $this->options['content_restrict_template'] ) ? esc_attr( $this->options['content_restrict_template'] ) : '' );
            $templates = get_page_templates( null, 'page' );

            //add the single.php template if it exists
            $single_template = locate_template(array('single.php'));
            if (!empty($single_template)) {
                $templates['Single'] = 'single.php';
            }

            ksort( $templates );
            foreach ( array_keys( $templates ) as $template ) {
                $selected = selected( $selected_template, $templates[ $template ], false );
                echo "\n\t<option value='" . esc_attr( $templates[ $template ] ) . "'" . esc_attr( $selected ) . ">" . esc_html( $template ) . "</option>";
            }

            ?>
        </select>
        <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php esc_html_e( 'Select which template should load instead of the default one when a post is restricted and the restriction type is set to "Template".', 'paid-member-subscriptions' ); ?></p>
    </div>
</div>

<!-- Misc -->
<div class="cozmoslabs-form-subsection-wrapper" id="cozmoslabs-restriction-misc">
    <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'Misc', 'paid-member-subscriptions' ); ?></h4>

    <!-- Restrict Comments -->
    <div class="cozmoslabs-form-field-wrapper cozmoslabs-column-radios-wrapper">
        <label class="cozmoslabs-form-field-label" for="restricted-posts-preview"><?php esc_html_e( 'Restrict Comments', 'paid-member-subscriptions' ) ?></label>

        <div class="cozmoslabs-radio-inputs-column">
            <label>
                <input type="radio" name="pms_content_restriction_settings[comments_restriction][option]" value="off" <?php echo ( !isset( $this->options['comments_restriction']['option'] ) || $this->options['comments_restriction']['option'] == 'off' ? 'checked' : '' ); ?> />
                <span><?php esc_html_e( 'Off', 'paid-member-subscriptions' ); ?></span>
            </label>

            <label>
                <input type="radio" name="pms_content_restriction_settings[comments_restriction][option]" value="restrict-replies" <?php echo ( isset( $this->options['comments_restriction']['option'] ) && $this->options['comments_restriction']['option'] == 'restrict-replies' ? 'checked' : '' ); ?> />
                <span><?php esc_html_e( 'Restrict replying, but allow users to view comments', 'paid-member-subscriptions' ); ?></span>
            </label>

            <label>
                <input type="radio" name="pms_content_restriction_settings[comments_restriction][option]" value="restrict-everything" <?php echo ( isset( $this->options['comments_restriction']['option'] ) && $this->options['comments_restriction']['option'] == 'restrict-everything' ? 'checked' : '' ); ?> />
                <span><?php esc_html_e( 'Restrict everything', 'paid-member-subscriptions' ); ?></span>
            </label>
        </div>

        <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Restrict comments if they are enabled.', 'paid-member-subscriptions' ); ?></p>
    </div>

    <!-- Restricted Posts Preview -->
    <div class="cozmoslabs-form-field-wrapper cozmoslabs-column-radios-wrapper">
        <label class="cozmoslabs-form-field-label" for="restricted-posts-preview"><?php esc_html_e( 'Restricted Posts Preview', 'paid-member-subscriptions' ) ?></label>

        <div class="cozmoslabs-radio-inputs-column">
            <label>
                <input type="radio" name="pms_content_restriction_settings[restricted_post_preview][option]" value="none" <?php echo ( !isset( $this->options['restricted_post_preview']['option'] ) || $this->options['restricted_post_preview']['option'] == 'none' ? 'checked' : '' ); ?> />
                <span><?php esc_html_e( 'None', 'paid-member-subscriptions' ); ?></span>
            </label>

            <label>
                <input type="radio" name="pms_content_restriction_settings[restricted_post_preview][option]" value="trim-content" <?php echo ( isset( $this->options['restricted_post_preview']['option'] ) && $this->options['restricted_post_preview']['option'] == 'trim-content' ? 'checked' : '' ); ?> />
                <span>
                    <?php echo sprintf( esc_html__( 'Show the first %s words of the post\'s content', 'paid-member-subscriptions' ), '<input name="pms_content_restriction_settings[restricted_post_preview][trim_content_length]" type="text" value="' . ( isset( $this->options['restricted_post_preview']['trim_content_length'] ) ? esc_attr( $this->options['restricted_post_preview']['trim_content_length'] ) : 20 ) . '" style="width: 50px;" />' ); ?>
                </span>
            </label>

            <label>
                <input type="radio" name="pms_content_restriction_settings[restricted_post_preview][option]" value="more-tag" <?php echo ( isset( $this->options['restricted_post_preview']['option'] ) && $this->options['restricted_post_preview']['option'] == 'more-tag' ? 'checked' : '' ); ?> />
                <span><?php esc_html_e( 'Show the content before the "more" tag', 'paid-member-subscriptions' ); ?></span>
            </label>
        </div>

        <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Show a portion of the restricted post to logged-out users or users that are not subscribed to a plan.', 'paid-member-subscriptions' ); ?></p>
    </div>

    <!-- Exclude Restricted Posts from Queries -->
    <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
        <label class="cozmoslabs-form-field-label" for="pms_includeRestrictedPosts"><?php esc_html_e( 'Include Restricted Posts in Queries', 'paid-member-subscriptions' ); ?></label>

        <div class="cozmoslabs-toggle-container">
            <input type="checkbox" name="pms_content_restriction_settings[pms_includeRestrictedPosts]" id="pms_includeRestrictedPosts" value="yes" <?php echo ( isset( $this->options['pms_includeRestrictedPosts'] ) && $this->options['pms_includeRestrictedPosts'] == 'yes' ) ? 'checked' : ''; ?> >
            <label class="cozmoslabs-toggle-track" for="pms_includeRestrictedPosts"></label>
        </div>

        <div class="cozmoslabs-toggle-description">
            <label for="pms_includeRestrictedPosts" class="cozmoslabs-description">
                <?php esc_html_e( 'Include restricted posts in queries like arhive pages, search etc.', 'paid-member-subscriptions' ); ?><br>
                <?php esc_html_e( 'This can potentially improve performance for very large websites but with a security risk.', 'paid-member-subscriptions' ); ?>
            </label>
        </div>
    </div>
</div>
