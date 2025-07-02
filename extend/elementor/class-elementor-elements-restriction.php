<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

class PMS_Elementor_Content_Restriction extends PMS_Elementor {
	protected function content_restriction() {
		// Setup controls
		$this->register_controls();

		// Filter widget content
		add_filter( 'elementor/widget/render_content', array( $this, 'widget_render' ), 10, 2 );

		// Filter sections display & add custom messages
		add_action('elementor/frontend/section/should_render', array( $this, 'section_render' ), 10, 2 );
		add_action('elementor/frontend/section/after_render', array( $this, 'section_custom_messages' ), 10, 2 );

        // Filter container display & add custom messages
        add_action( 'elementor/frontend/container/should_render', array( $this, 'section_render' ), 10, 2 );
        add_action( 'elementor/frontend/container/after_render', array( $this, 'section_custom_messages' ), 10, 2 );

		// Filter elementor the_content hook
		add_action( 'elementor/frontend/the_content', array( $this, 'filter_elementor_templates' ), 20 );

		// Disable Element Cache when Content Restriction rules are setup for an element
		add_filter( 'elementor/element/is_dynamic_content', array( $this, 'are_content_restriction_rules_setup' ), 20, 3 );

		// Add Elementor templates to the list of content restriction templates templates
		add_filter( 'pms_content_restriction_templates', array( $this, 'add_elementor_templates' ), 10, 1 );
	}

	// Register controls to sections and widgets
	protected function register_controls() {
		foreach( $this->locations as $where )
			add_action('elementor/element/'.$where['element'].'/'.$this->section_name.'/before_section_end', array( $this, 'add_controls' ), 10, 2 );
	}

	// Define controls
	public function add_controls( $element, $args ) {
		$element_type = $element->get_type();

		$element->add_control(
			'pms_restriction_loggedin_users', array(
				'label'       => __( 'Restrict to logged in users', 'paid-member-subscriptions' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => __( 'Allow only logged in users to see this content.', 'paid-member-subscriptions' ),
                'condition'   => array(
                    'pms_restriction_loggedout_users!' => 'yes'
                ),
			)
		);

        $element->add_control(
            'pms_restriction_loggedout_users', array(
                'label'       => __( 'Restrict to logged out users', 'paid-member-subscriptions' ),
                'type'        => Controls_Manager::SWITCHER,
                'description' => __( 'Allow only logged out users to see this content.', 'paid-member-subscriptions' ),
                'condition'   => array(
                    'pms_restriction_loggedin_users!' => 'yes',
                    'pms_restriction_display_to_non_subscribers!' => 'yes',
                ),
            )
        );

        $element->add_control(
            'pms_restriction_display_to_non_subscribers', array(
                'label'       => __( 'Display to non subscribers', 'paid-member-subscriptions' ),
                'type'        => Controls_Manager::SWITCHER,
                'description' => __( 'Allow only non-subscribed users to see this content.', 'paid-member-subscriptions' ),
                'condition'   => array(
                    'pms_restriction_loggedout_users!' => 'yes'
                ),
            )
        );

		$element->add_control(
			'pms_restriction_subscription_plans_heading', array(
				'label'     => __( 'Restrict by Subscription Plans', 'paid-member-subscriptions' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
                'condition'   => array(
                    'pms_restriction_display_to_non_subscribers!' => 'yes',
                    'pms_restriction_loggedout_users!' => 'yes'
                ),

			)
		);

        $element->add_control(
            'pms_restriction_subscription_plans_non_members_heading', array(
                'label'     => __( 'Restrict to Non-Members by Subscription Plans', 'paid-member-subscriptions' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition'   => array(
                    'pms_restriction_display_to_non_subscribers' => 'yes',
                    'pms_restriction_loggedout_users!' => 'yes'
                ),
            )
        );

        $pms_restrict_subscription_list = pms_get_subscription_plans_list();
        $pms_restrict_subscription_list = [ 'all' => 'All Subscriptions' ] + $pms_restrict_subscription_list;

		$element->add_control(
            'pms_restriction_subscription_plans', array(
                'type'        => Controls_Manager::SELECT2,
                'options'     => $pms_restrict_subscription_list,
                'multiple'    => 'true',
				'label_block' => 'true',
                'condition'   => array(
                    'pms_restriction_loggedout_users!' => 'yes'
                ),
            )
        );

        $element->add_control(
            'pms_restriction_subscription_plans_description', array(
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<div class="elementor-control-field-description" style="margin-top: -5px">' . sprintf( __( 'Allow only %1$s MEMBERS %2$s of the selected plans to see this content.', 'paid-member-subscriptions' ), '<strong>', '</strong>' ) . '</div>',
                'condition'   => array(
                    'pms_restriction_display_to_non_subscribers!' => 'yes',
                    'pms_restriction_loggedout_users!' => 'yes'
                ),

            )
        );

        $element->add_control(
            'pms_restriction_subscription_plans_non_members_description', array(
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<div class="elementor-control-field-description" style="margin-top: -5px">' . sprintf( __( 'Allow only %1$s NON-MEMBERS %2$s of the selected plans to see this content.', 'paid-member-subscriptions' ), '<strong>', '</strong>' ) . '</div>',
                'condition'   => array(
                    'pms_restriction_display_to_non_subscribers' => 'yes',
                    'pms_restriction_loggedout_users!' => 'yes'
                ),

            )
        );

		$element->add_control(
			'pms_restriction_custom_messages_heading', array(
				'label'     => __( 'Restriction Messages', 'paid-member-subscriptions' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$element->add_control(
			'pms_restriction_default_messages', array(
				'label'       => __( 'Enable Restriction Messages', 'paid-member-subscriptions' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => __( 'Replace hidden content with the default messages from PMS -> Settings -> Content Restriction, a custom message or an Elementor Template.', 'paid-member-subscriptions' ),
			)
		);

		$element->add_control(
			'pms_restriction_custom_messages', array(
				'label'       => __( 'Enable Custom Messages', 'paid-member-subscriptions' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => __( 'Add a custom message or template.', 'paid-member-subscriptions' ),
				'condition'   => array(
					'pms_restriction_default_messages' => 'yes'
				)
			)
		);

		$element->add_control(
			'pms_restriction_custom_messages_type', array(
				'label'   => __( 'Content type', 'paid-member-subscriptions' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => array(
					'text' => array(
						'title' => __( 'Text', 'paid-member-subscriptions' ),
						'icon'  => 'fa fa-align-left',
					),
					'template' => array(
						'title' => __( 'Template', 'paid-member-subscriptions' ),
						'icon'  => 'fa fa-th-large',
					)
				),
				'default'   => 'text',
				'condition' => array(
					'pms_restriction_default_messages' => 'yes',
					'pms_restriction_custom_messages'  => 'yes'
				),
			)
		);

		//DCE_HELPER::get_all_template()
		$element->add_control(
			'pms_restriction_fallback_template', array(
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->get_elementor_templates(),
				'label'       => __( 'Select Template', 'paid-member-subscriptions' ),
				'default'     => '',
				'label_block' => 'true',
				'condition'   => array(
					'pms_restriction_default_messages'     => 'yes',
					'pms_restriction_custom_messages'      => 'yes',
					'pms_restriction_custom_messages_type' => 'template'
				),
			)
		);

		$element->add_control(
			'pms_restriction_fallback_text', array(
				'type'        => Controls_Manager::WYSIWYG,
				'default'     => 'You need to register before accessing this content.',
				'condition'   => array(
					'pms_restriction_default_messages'     => 'yes',
					'pms_restriction_custom_messages'      => 'yes',
					'pms_restriction_custom_messages_type' => 'text'
				),
			)
		);

		do_action( 'pms_elementor_add_controls', $element );

	}

	// Verifies is element is hidden
	public function is_hidden( $element ) {

		$settings = $element->get_settings();
		$hidden   = false;

        if( is_user_logged_in() && !current_user_can( 'manage_options' ) ) {

            if ( $settings['pms_restriction_loggedout_users'] == 'yes' ) {
                $hidden = true;
            }
            elseif ( $settings['pms_restriction_display_to_non_subscribers'] == 'yes' ) {

                if ( ( empty( $settings['pms_restriction_subscription_plans'] ) && pms_is_member( get_current_user_id() ) ) || ( !empty( $settings['pms_restriction_subscription_plans'] ) && pms_is_member_of_plan( $settings['pms_restriction_subscription_plans'] ) ) )
                    $hidden = true;

            }
            elseif ( !empty( $settings['pms_restriction_subscription_plans'] ) && !pms_is_member_of_plan( $settings['pms_restriction_subscription_plans'] ) && ( !in_array( 'all', $settings['pms_restriction_subscription_plans']) || !pms_is_member() ) )
                $hidden = true;

        }
        elseif ( !is_user_logged_in() && ( $settings['pms_restriction_loggedin_users'] == 'yes' || $settings['pms_restriction_display_to_non_subscribers'] == 'yes' || !empty( $settings['pms_restriction_subscription_plans'] )  ) )
            $hidden = true;

		return apply_filters( 'pms_elementor_elements_restriction_element_is_hidden', $hidden, $element, $settings );

	}

	// Retrieves custom element message or the default message from PMS settings
	private function get_custom_message( $element ) {
		$settings = $element->get_settings();

		if( $settings['pms_restriction_default_messages'] != 'yes' )
			return false;

		if( $settings['pms_restriction_custom_messages'] == 'yes' ) {

			if( $settings['pms_restriction_custom_messages_type'] == 'text' )
				return $settings['pms_restriction_fallback_text'];
			elseif( $settings['pms_restriction_custom_messages_type'] == 'template' ) {
				return $this->render_template( $settings['pms_restriction_fallback_template'] );
			}
		} else {
			if( is_user_logged_in() )
				return pms_get_restriction_content_message( 'non_members' );
			else
				return pms_get_restriction_content_message( 'logged_out' );
		}
	}

	// Widget display & custom messages
	public function widget_render( $content, $widget ) {
		if( $this->is_hidden( $widget ) ) {

			if( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				$widget->add_render_attribute( '_wrapper', 'class', 'pms-visibility-hidden' );

				return $content;
			}

			if( $message = $this->get_custom_message( $widget ) ) {
				return $message;
			}

			return '<style>' . $widget->get_unique_selector() . '{ display: none; }</style>';
		}

		return $content;
	}

	// Section display
	public function section_render( $should_render, $element ) {
		if( $this->is_hidden( $element ) === true )
			return false;

		return $should_render;
	}

	// Section custom messages
	public function section_custom_messages( $element ) {
		if( $this->is_hidden( $element ) && ( $message = $this->get_custom_message( $element ) ) ) {

			$element->add_render_attribute(
				'_wrapper', 'class', array(
					'elementor-section',
				)
			);

			$element->before_render();
				echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$element->after_render();
		}
	}

	// Render an Elementor template based on ID
	// Based on Elementor Pro template shortcode
	public function render_template( $id ) {
		return Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $id, true );
	}

	// Retrieve defined Elementor templates
	private function get_elementor_templates() {
		$templates = array();

		foreach( \Elementor\Plugin::instance()->templates_manager->get_source('local')->get_items() as $template ) {
			$templates[$template['template_id']] = $template['title'] . ' (' . $template['type'] . ')';
		}

		return $templates;
	}

	public function filter_elementor_templates( $content ){
		$document = \Elementor\Plugin::$instance->documents->get_current();

		return pms_filter_content( $content, $document->get_post() );
	}

	public function are_content_restriction_rules_setup( $are_rules_setup, $data, $element ){

		if( empty( $data['settings'] ) )
			return $are_rules_setup;

		if( isset( $data['settings']['pms_restriction_loggedin_users'] ) && $data['settings']['pms_restriction_loggedin_users'] == 'yes' )
			return true;

		if( isset( $data['settings']['pms_restriction_loggedout_users'] ) && $data['settings']['pms_restriction_loggedout_users'] == 'yes' )
			return true;

		if( isset( $data['settings']['pms_restriction_display_to_non_subscribers'] ) && $data['settings']['pms_restriction_display_to_non_subscribers'] == 'yes' )
			return true;

		if( isset( $data['settings']['pms_restriction_subscription_plans'] ) && !empty( $data['settings']['pms_restriction_subscription_plans'] ) )
			return true;

		return $are_rules_setup;

	}

	public function add_elementor_templates( $templates ){

		$elementor_templates = $this->get_elementor_templates();

		if( !empty( $elementor_templates ) ) {
			foreach( $elementor_templates as $id => $title ) {
				$templates[$title] = 'elementor_template_' . $id;
			}
		} 

		return $templates;

	}
}

new PMS_Elementor_Content_Restriction;
