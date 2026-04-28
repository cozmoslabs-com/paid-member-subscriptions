<?php

/**
 * Class ActionScheduler_wpPostStore_PostTypeRegistrar
 *
 * @codeCoverageIgnore
 */
class ActionScheduler_wpPostStore_PostTypeRegistrar {
	/**
	 * Registrar.
	 */
	public function register() {
		register_post_type( ActionScheduler_wpPostStore::POST_TYPE, $this->post_type_args() );
	}

	/**
	 * Build the args array for the post type definition
	 *
	 * @return array
	 */
	protected function post_type_args() {
		$args = array(
			'label'        => esc_html__('Scheduled Actions', 'paid-member-subscriptions' ),
			'description'  => esc_html__('Scheduled actions are hooks triggered on a certain date and time.', 'paid-member-subscriptions' ),
			'public'       => false,
			'map_meta_cap' => true,
			'hierarchical' => false,
			'supports'     => array( 'title', 'editor', 'comments' ),
			'rewrite'      => false,
			'query_var'    => false,
			'can_export'   => true,
			'ep_mask'      => EP_NONE,
			'labels'       => array(
				'name'               => esc_html__('Scheduled Actions', 'paid-member-subscriptions' ),
				'singular_name'      => esc_html__('Scheduled Action', 'paid-member-subscriptions' ),
				'menu_name'          => _x( 'Scheduled Actions', 'Admin menu name', 'paid-member-subscriptions' ),
				'add_new'            => esc_html__('Add', 'paid-member-subscriptions' ),
				'add_new_item'       => esc_html__('Add New Scheduled Action', 'paid-member-subscriptions' ),
				'edit'               => esc_html__('Edit', 'paid-member-subscriptions' ),
				'edit_item'          => esc_html__('Edit Scheduled Action', 'paid-member-subscriptions' ),
				'new_item'           => esc_html__('New Scheduled Action', 'paid-member-subscriptions' ),
				'view'               => esc_html__('View Action', 'paid-member-subscriptions' ),
				'view_item'          => esc_html__('View Action', 'paid-member-subscriptions' ),
				'search_items'       => esc_html__('Search Scheduled Actions', 'paid-member-subscriptions' ),
				'not_found'          => esc_html__('No actions found', 'paid-member-subscriptions' ),
				'not_found_in_trash' => esc_html__('No actions found in trash', 'paid-member-subscriptions' ),
			),
		);

		$args = apply_filters( 'action_scheduler_post_type_args', $args );
		return $args;
	}
}
