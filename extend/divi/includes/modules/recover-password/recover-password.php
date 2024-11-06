<?php

class PMS_RecoverPassword extends ET_Builder_Module {

	public $slug       = 'pms_recover_password';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => 'https://wordpress.org/plugins/paid-member-subscriptions/',
		'author'     => 'Cozmoslabs',
		'author_uri' => 'https://www.cozmoslabs.com/',
	);

	public function init() {
        $this->name = esc_html__( 'PMS Recover Password', 'paid-member-subscriptions' );

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
			'redirect_url'        => array(
				'label'           => esc_html__( 'Redirect After Password Recovery', 'paid-member-subscriptions' ),
				'type'            => 'select',
				'options'         => $pages,
				'default'         => 'default',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Select a page for an After Password Recovery Redirect.', 'paid-member-subscriptions' ),
				'toggle_slug'     => 'main_content',
			),
		);
	}

    public function render( $attrs, $render_slug, $content = null ) {

	    if ( !is_array( $attrs ) ) {
		    return;
	    }

	    $atts = [
		    'redirect_url' => array_key_exists('redirect_url', $attrs) && $attrs['redirect_url'] !== 'default' ? pms_divi_parse_redirect_url($attrs['redirect_url']) : '',
	    ];

        return '<div class="pms-divi-front-end-container">' .
               do_shortcode( '[pms-recover-password redirect_url='. $atts['redirect_url'] .']') .
               '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

new PMS_RecoverPassword;
