<?php

class PMS_Register extends ET_Builder_Module {

	public $slug       = 'pms_register';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => 'https://wordpress.org/plugins/paid-member-subscriptions/',
		'author'     => 'Cozmoslabs',
		'author_uri' => 'https://www.cozmoslabs.com/',
	);

	public function init() {
        $this->name = esc_html__( 'PMS Register', 'paid-member-subscriptions' );

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
		$plans = array();

		$plan_ids = get_posts( array( 'post_type' => 'pms-subscription', 'meta_key' => 'pms_subscription_plan_status', 'meta_value' => 'active', 'numberposts' => -1, 'post_status' => 'any', 'fields' => 'ids' ) );

		if( !empty( $plan_ids ) ) {
			foreach ($plan_ids as $plan_id)
				$plans[$plan_id] = get_the_title($plan_id);
		}

		$plans_d = $plans;
		$plans_d ['default'] = 'Default';

		return array(
			'toggle_show'            => array(
				'label'              => esc_html__( 'Show Subscription Plans', 'paid-member-subscriptions' ),
				'type'               => 'yes_no_button',
				'options'            => ( !empty( $plans ) ) ? array(
					'on'             => esc_html__( 'Yes', 'paid-member-subscriptions'),
					'off'            => esc_html__( 'No', 'paid-member-subscriptions'),
										) : array(
					'off'            => esc_html__( 'No', 'paid-member-subscriptions'),
				),
				/*'default'            => ( !empty( $plans ) ) ? 'on' : 'off',*/
				'option_category'    => 'basic_option',
				'description'        => esc_html__( 'Include Subscription Plans in the form. To do this you need to have at least one active Subscription Plan.', 'paid-member-subscriptions' ),
				'toggle_slug'        => 'main_content',
			),
			'toggle_include'         => array(
				'label'              => esc_html__( 'Include Specific Plans', 'paid-member-subscriptions' ),
				'type'               => 'yes_no_button',
				'options'            => array(
					'on'             => esc_html__( 'Yes', 'paid-member-subscriptions'),
					'off'            => esc_html__( 'No', 'paid-member-subscriptions'),
				),
				'default'            => 'off',
				'option_category'    => 'basic_option',
				'description'        => esc_html__( 'Toggle to include only selected Subscription Plans in the form.', 'paid-member-subscriptions' ),
				'toggle_slug'        => 'main_content',
				'show_if'            => array(
					'toggle_exclude' => 'off',
					'toggle_show'    => 'on',
				),
			),
			'include_plans'          => array(
				'label'              => esc_html__( 'Select Specific Plans', 'paid-member-subscriptions' ),
				'type'               => 'pms_multiple_checkboxes_with_ids',
				'options'            => $plans,
				'description'        => esc_html__( 'Select Subscription Plans to be included.', 'paid-member-subscriptions' ),
				'toggle_slug'        => 'main_content',
				'show_if'            => array(
					'toggle_show'    => 'on',
					'toggle_include' => 'on',
					'toggle_exclude' => 'off',
				),
			),
			'toggle_exclude'         => array(
				'label'              => esc_html__( 'Exclude Specific Plans', 'paid-member-subscriptions' ),
				'type'               => 'yes_no_button',
				'options'            => array(
					'on'             => esc_html__( 'Yes', 'paid-member-subscriptions'),
					'off'            => esc_html__( 'No', 'paid-member-subscriptions'),
				),
				'default'            => 'off',
				'option_category'    => 'basic_option',
				'description'        => esc_html__( 'Toggle to exclude selected Subscription Plans from the form.', 'paid-member-subscriptions' ),
				'toggle_slug'        => 'main_content',
				'show_if'            => array(
					'toggle_include' => 'off',
					'toggle_show'    => 'on',
				),
			),
			'exclude_plans'          => array(
				'label'              => esc_html__( 'Select Specific Plans', 'paid-member-subscriptions' ),
				'type'               => 'pms_multiple_checkboxes_with_ids',
				'options'            => $plans,
				'description'        => esc_html__( 'Select Subscription Plans to be excluded.', 'paid-member-subscriptions' ),
				'toggle_slug'        => 'main_content',
				'show_if'            => array(
					'toggle_show'    => 'on',
					'toggle_include' => 'off',
					'toggle_exclude' => 'on',
				),
			),
            'selected_plan'          => array(
                'label'              => esc_html__( 'Selected Plan', 'paid-member-subscriptions' ),
                'type'               => 'select',
                'options'            => $plans_d,
                'default'            => 'default',
                'option_category'    => 'basic_option',
                'description'        => esc_html__( 'Choose the Subscription Plan that will be selected by default.', 'paid-member-subscriptions' ),
                'toggle_slug'        => 'main_content',
                'show_if'            => array(
	                'toggle_show'    => 'on',
                ),
            ),
            'toggle_plans_position'  => array(
                'label'              => esc_html__( 'Subscription Plans at the Top', 'paid-member-subscriptions' ),
                'type'               => 'yes_no_button',
                'options'            => array(
                    'on'             => esc_html__( 'Yes', 'paid-member-subscriptions'),
                    'off'            => esc_html__( 'No', 'paid-member-subscriptions'),
                ),
                'option_category'    => 'basic_option',
                'description'        => esc_html__( 'Determine the position of the Subscription Plans in the form.', 'paid-member-subscriptions' ),
                'toggle_slug'        => 'main_content',
                'show_if'            => array(
	                'toggle_show'    => 'on',
                ),
            ),
		);
	}

    public function render( $attrs, $render_slug, $content = null ) {

        if ( !is_array( $attrs ) ) {
            return;
        }

	    if ( array_key_exists( 'toggle_show', $attrs ) && $attrs['toggle_show'] === 'on' ) {

		    $atts = [
			    'selected_plan'         => array_key_exists('selected_plan', $attrs)         && $attrs['selected_plan']         !== 'default' ? 'selected="'. esc_attr($attrs['selected_plan']) .'" ' : '',
			    'toggle_plans_position' => array_key_exists('toggle_plans_position', $attrs) && $attrs['toggle_plans_position'] === 'on'      ? 'plans_position="top" '                               : '',
			    'subscription_plans'    => '',
		    ];
		    if ( array_key_exists('toggle_include', $attrs) && $attrs['toggle_include'] === 'on' &&
		         array_key_exists('toggle_exclude', $attrs) && $attrs['toggle_exclude'] === 'off' &&
		         array_key_exists('include_plans', $attrs) && $attrs['include_plans'] !== 'undefined' ){

				$subscription_plans = pms_divi_parse_subscription_plans( $attrs['include_plans'] );

				$atts['subscription_plans'] = 'subscription_plans="' . $subscription_plans . '" ';

		    } elseif ( array_key_exists('toggle_exclude', $attrs) && $attrs['toggle_exclude'] === 'on' &&
		               array_key_exists('toggle_include', $attrs) && $attrs['toggle_include'] === 'off' &&
		               array_key_exists('exclude_plans', $attrs) && $attrs['exclude_plans'] !== 'undefined' ){

				$subscription_plans = pms_divi_parse_subscription_plans( $attrs['exclude_plans'] );

				$atts['subscription_plans'] = 'subscription_plans="' . $subscription_plans . '" ';

		    }

		    return '<div class="pms-divi-front-end-container">' .
			    do_shortcode( '[pms-register '. $atts['subscription_plans'] . $atts['toggle_plans_position'] . $atts['selected_plan'] .']') .
			    '</div>';
	    } else {
		    return '<div class="pms-divi-front-end-container">' .
			    do_shortcode( '[pms-register subscription_plans="none"]') .
			    '</div>';
	    }
    }
}

new PMS_Register;
