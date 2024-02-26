<?php

class PMS_EditProfile extends ET_Builder_Module {

	public $slug       = 'pms_edit_profile';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => 'https://wordpress.org/plugins/paid-member-subscriptions/',
		'author'     => 'Cozmoslabs',
		'author_uri' => 'https://www.cozmoslabs.com/',
	);

	public function init() {
        $this->name = esc_html__( 'PMS Account', 'paid-member-subscriptions' );

        $this->settings_modal_toggles = array(
            'general' => array(
                'toggles' => array(
                    'main_content' => esc_html__( 'Form Settings', 'paid-member-subscriptions' ),
                ),
            ),
        );

        $this->advanced_fields = array(
            'link_options' => false,
            'background'   => false,
            'admin_label'  => false,
        );
	}

	public function get_fields() {
		$args = array(
			'post_type'         => 'page',
			'posts_per_page'    => -1
		);

		if( function_exists( 'wc_get_page_id' ) )
			$args['exclude'] = wc_get_page_id( 'shop' );

		$all_pages = get_posts( $args );
        $pages ['default'] = 'None';

        if( !empty( $all_pages ) ){
            foreach ( $all_pages as $page ){
                $pages [ esc_url( get_page_link( $page->ID ) ) ] = esc_html( $page->post_title );
            }
        }

		return array(
			'hide_tabs'           => array(
				'label'           => esc_html__( 'Hide Tabs', 'paid-member-subscriptions' ),
				'type'            => 'yes_no_button',
				'options'         => array(
					'on'          => esc_html__( 'Yes', 'paid-member-subscriptions'),
					'off'         => esc_html__( 'No', 'paid-member-subscriptions'),
				),
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Select whether to hide the Account form tabs.', 'paid-member-subscriptions' ),
				'toggle_slug'     => 'main_content',
			),
            'redirect_url'        => array(
                'label'           => esc_html__( 'Redirect After Logout', 'paid-member-subscriptions' ),
                'type'            => 'select',
                'options'         => $pages,
                'default'         => 'default',
                'option_category' => 'basic_option',
                'description'     => esc_html__( 'Select a page for an After Logout Redirect.', 'paid-member-subscriptions' ),
                'toggle_slug'     => 'main_content',
            ),
		);
	}

    public function render( $attrs, $render_slug, $content = null ) {

        if ( !is_array( $attrs ) ) {
            return;
        }

	    $atts = [
		    'hide_tabs'    => array_key_exists('hide_tabs', $attrs)    && $attrs['hide_tabs']    === 'on'      ? 'show_tabs="no" '                 : '',
		    'redirect_url' => array_key_exists('redirect_url', $attrs) && $attrs['redirect_url'] !== 'default' ? esc_attr($attrs['redirect_url']) : '',
	    ];

        return '<div class="pms-divi-front-end-container">' .
               do_shortcode( '[pms-account '. $atts['hide_tabs'] .'logout_redirect_url='. $atts['redirect_url'] .']') .
               '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

new PMS_EditProfile;
