<?php

class PMS_Login extends ET_Builder_Module {

	public $slug       = 'pms_login';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => 'https://wordpress.org/plugins/paid-member-subscriptions/',
		'author'     => 'Cozmoslabs',
		'author_uri' => 'https://www.cozmoslabs.com/',
	);

	public function init() {
        $this->name = esc_html__( 'PMS Login', 'paid-member-subscriptions' );

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
            'post_type'      => 'page',
            'posts_per_page' => -1
        );

        if( function_exists( 'wc_get_page_id' ) )
            $args['exclude'] = wc_get_page_id( 'shop' );

        $all_pages = get_posts( $args );
        $pages ['default'] = 'Default';

        if( !empty( $all_pages ) ){
            foreach ( $all_pages as $page ){
                $pages [ esc_url( get_page_link( $page->ID ) ) ] = esc_html( $page->post_title );
            }
        }

		return array(
			'register_url'        => array(
				'label'           => esc_html__( 'Registration Page', 'paid-member-subscriptions' ),
				'type'            => 'select',
				'options'         => $pages,
				'default'         => 'default',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Add a link to a Registration Page.', 'paid-member-subscriptions' ),
				'toggle_slug'     => 'main_content',
			),
			'lostpassword_url'        => array(
				'label'           => esc_html__( 'Recover Password Page', 'paid-member-subscriptions' ),
				'type'            => 'select',
				'options'         => $pages,
				'default'         => 'default',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Add a link to a Recover Password Page.', 'paid-member-subscriptions' ),
				'toggle_slug'     => 'main_content',
			),
            'redirect_url'        => array(
                'label'           => esc_html__( 'Redirect After Login', 'paid-member-subscriptions' ),
                'type'            => 'select',
                'options'         => $pages,
                'default'         => 'default',
                'option_category' => 'basic_option',
                'description'     => esc_html__( 'Select a page for an After Login Redirect.', 'paid-member-subscriptions' ),
                'toggle_slug'     => 'main_content',
            ),
            'logout_redirect_url' => array(
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
		    'redirect_url'        => array_key_exists('redirect_url', $attrs)         && $attrs['redirect_url']        !== 'default' ? pms_divi_parse_redirect_url($attrs['redirect_url'])        : '',
		    'logout_redirect_url' => array_key_exists('logout_redirect_url', $attrs)  && $attrs['logout_redirect_url'] !== 'default' ? pms_divi_parse_redirect_url($attrs['logout_redirect_url']) : '',
		    'register_url'        => array_key_exists('register_url', $attrs)         && $attrs['register_url']        !== 'default' ? pms_divi_parse_redirect_url($attrs['register_url'])        : '',
		    'lostpassword_url'    => array_key_exists('lostpassword_url', $attrs)     && $attrs['lostpassword_url']    !== 'default' ? pms_divi_parse_redirect_url($attrs['lostpassword_url'])    : '',
	    ];

        return '<div class="pms-divi-front-end-container">' .
               do_shortcode( '[pms-login redirect_url="'. $atts['redirect_url'] .'" logout_redirect_url="'. $atts['logout_redirect_url'] .'" register_url ="'. $atts['register_url'] .'" lostpassword_url ="'. $atts['lostpassword_url'] .'"]') .
               '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

new PMS_Login;
