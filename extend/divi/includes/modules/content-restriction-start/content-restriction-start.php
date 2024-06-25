<?php

class PMS_Content_Restriction_Start extends ET_Builder_Module {

	public $slug       = 'pms_content_restriction_start';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => 'https://wordpress.org/plugins/paid-member-subscriptions/',
		'author'     => 'Cozmoslabs',
		'author_uri' => 'https://www.cozmoslabs.com/',
	);

	public function init() {
        $this->name = esc_html__( 'PMS Content Restriction Start', 'paid-member-subscriptions' );

        $this->settings_modal_toggles = array(
            'general' => array(
                'toggles' => array(
                    'main_content' => esc_html__( 'Module Settings', 'paid-member-subscriptions' ),
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

        $fields_list['pms_display_to'] = array(
            'label'              => esc_html__( 'Show content to', 'paid-member-subscriptions' ),
            'description'        => esc_html__( 'The users you wish to see the content.', 'paid-member-subscriptions' ),
            'type'               => 'select',
            'options'            => array(
                'all'            => esc_html__( 'All', 'paid-member-subscriptions' ),
                'logged_in'      => esc_html__( 'Logged in', 'paid-member-subscriptions' ),
                'not_logged_in'  => esc_html__( 'Not logged in', 'paid-member-subscriptions' ),
            ),
            'default'            => 'all',
            'option_category'    => 'basic_option',
            'toggle_slug'        => 'main_content',
        );
        $fields_list['pms_subscriptions'] = array(
            'label'              => esc_html__( 'Required Subscriptions', 'paid-member-subscriptions' ),
            'description'        => esc_html__( 'The desired valid subscriptions. Select none to display the content to all logged in users.', 'paid-member-subscriptions' ),
            'type'               => 'pms_multiple_checkboxes_with_ids',
            'options'            => $plans,
            'option_category'    => 'basic_option',
            'toggle_slug'        => 'main_content',
            'show_if'            => array(
                'pms_display_to'     => 'logged_in',
            ),
        );
		$fields_list['pms_toggle_not_subscribed'] = array(
			'label'              => esc_html__( 'Show to Not Subscribed', 'paid-member-subscriptions' ),
			'description'        => esc_html__( 'Show the content only to users that do not have an active subscription.', 'paid-member-subscriptions' ),
			'type'               => 'yes_no_button',
			'options'            => array(
				'on'             => esc_html__( 'Yes', 'paid-member-subscriptions'),
				'off'            => esc_html__( 'No', 'paid-member-subscriptions'),
			),
			'option_category'    => 'basic_option',
			'toggle_slug'        => 'main_content',
			'show_if'        => array(
				'pms_display_to'     => 'logged_in',
			),
		);
        $fields_list['pms_toggle_message'] = array(
            'label'              => esc_html__( 'Enable Message', 'paid-member-subscriptions' ),
            'description'        => esc_html__( 'Show the Message defined in the Paid Member Subscriptions Settings.', 'paid-member-subscriptions' ),
            'type'               => 'yes_no_button',
            'options'            => array(
                'on'             => esc_html__( 'Yes', 'paid-member-subscriptions'),
                'off'            => esc_html__( 'No', 'paid-member-subscriptions'),
            ),
            'option_category'    => 'basic_option',
            'toggle_slug'        => 'main_content',
            'show_if_not'        => array(
                'pms_display_to'     => 'all',
            ),
        );
        $fields_list['pms_toggle_custom_message'] = array(
            'label'              => esc_html__( 'Custom Message', 'paid-member-subscriptions' ),
            'description'        => esc_html__( 'Enable Custom Message.', 'paid-member-subscriptions' ),
            'type'               => 'yes_no_button',
            'options'            => array(
                'on'             => esc_html__( 'Yes', 'paid-member-subscriptions'),
                'off'            => esc_html__( 'No', 'paid-member-subscriptions'),
            ),
            'option_category'    => 'basic_option',
            'toggle_slug'        => 'main_content',
            'show_if_not'        => array(
                'pms_display_to'     => 'all',
            ),
            'show_if'            => array(
                'pms_toggle_message' => 'on',
            ),
        );
        $fields_list['pms_message_logged_in'] = array(
            'label'              => esc_html__( 'Custom message', 'paid-member-subscriptions' ),
            'description'        => esc_html__( 'Enter the custom message you wish the restricted users to see.', 'paid-member-subscriptions' ),
            'type'               => 'text',
            'option_category'    => 'basic_option',
            'toggle_slug'        => 'main_content',
            'show_if'            => array(
                'pms_toggle_message'        => 'on',
                'pms_toggle_custom_message' => 'on',
                'pms_display_to'            => 'logged_in',
            ),
        );
        $fields_list['pms_message_logged_out'] = array(
            'label'              => esc_html__( 'Custom message', 'paid-member-subscriptions' ),
            'description'        => esc_html__( 'Custom message for logged-out users.', 'paid-member-subscriptions' ),
            'type'               => 'text',
            'option_category'    => 'basic_option',
            'toggle_slug'        => 'main_content',
            'show_if'            => array(
                'pms_toggle_message'        => 'on',
                'pms_toggle_custom_message' => 'on',
                'pms_display_to'            => 'not_logged_in',
            ),
        );
        return $fields_list;
	}

    public function render( $attrs, $content, $render_slug ) {
        return;
    }
}

new PMS_Content_Restriction_Start;
