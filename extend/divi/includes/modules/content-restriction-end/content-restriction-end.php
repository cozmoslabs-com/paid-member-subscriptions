<?php

class PMS_Content_Restriction_End extends ET_Builder_Module {

	public $slug       = 'pms_content_restriction_end';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => 'https://wordpress.org/plugins/paid-member-subscriptions/',
		'author'     => 'Cozmoslabs',
		'author_uri' => 'https://www.cozmoslabs.com/',
	);

	public function init() {
        $this->name = esc_html__( 'PMS Content Restriction End', 'paid-member-subscriptions' );

        $this->advanced_fields = array(
            'link_options' => false,
            'background'   => false,
            'admin_label'  => false,
        );
	}

    public function render( $attrs, $content, $render_slug ) {
        return;
    }
}

new PMS_Content_Restriction_End;
