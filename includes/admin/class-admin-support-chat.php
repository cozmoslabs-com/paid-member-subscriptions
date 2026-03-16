<?php
/**
 * Support Chat Widget
 *
 * Displays a chat-like popup showing recent WordPress.org forum topics
 * and encourages users to open support tickets.
 *
 * @package PaidMemberSubscriptions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PMS_Admin_Support_Chat
 *
 * Handles the support chat widget functionality.
 */
class PMS_Admin_Support_Chat {

	/**
	 * RSS feed URL for the plugin support forum.
	 */
	const FEED_URL = 'https://wordpress.org/support/plugin/paid-member-subscriptions/feed';

	/**
	 * Support forum URL.
	 */
	const FORUM_URL = 'https://wordpress.org/support/plugin/paid-member-subscriptions/';

	/**
	 * New topic URL.
	 */
	const NEW_TOPIC_URL = 'https://wordpress.org/support/plugin/paid-member-subscriptions/#new-topic-0';

	/**
	 * Transient key for caching forum posts.
	 */
	const CACHE_KEY = 'pms_support_forum_posts';

	/**
	 * Cache duration in seconds (1 hour).
	 */
	const CACHE_DURATION = HOUR_IN_SECONDS;

	/**
	 * Number of posts to display.
	 */
	const POSTS_COUNT = 5;

	/**
	 * User meta key for last viewed timestamp.
	 */
	const LAST_VIEWED_META_KEY = 'pms_support_chat_last_viewed';

	/**
	 * Class instance.
	 *
	 * @var PMS_Admin_Support_Chat|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @return PMS_Admin_Support_Chat
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {

		// only include the Support Chat in PMS Free version
//		if ( defined( 'PMS_PAID_PLUGIN_DIR' ) ) {
//			return;
//		}

		// include the Support Chat in PMS Free and Dev versions
		if ( defined( 'PMS_PAID_PLUGIN_DIR' ) && ( ! defined( 'PAID_MEMBER_SUBSCRIPTIONS' ) || PAID_MEMBER_SUBSCRIPTIONS !== 'Paid Member Subscriptions Dev' ) ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_footer', array( $this, 'render_chat_widget' ) );
		add_action( 'wp_ajax_pms_get_forum_posts', array( $this, 'ajax_get_forum_posts' ) );
		add_action( 'wp_ajax_pms_mark_forum_posts_read', array( $this, 'ajax_mark_forum_posts_read' ) );
	}

	/**
	 * Check if we should show the chat widget.
	 *
	 * @return bool
	 */
	private function should_show_widget() {
		return $this->is_paid_member_subscriptions_page();
	}

	/**
	 * Check if current page is a PMS admin page.
	 *
	 * @return bool
	 */
	private function is_paid_member_subscriptions_page() {
		if ( ! is_admin() ) {
			return false;
		}

		$current_page    = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$current_subpage = isset( $_GET['subpage'] ) ? sanitize_text_field( wp_unslash( $_GET['subpage'] ) ) : '';

		if ( $current_subpage === 'pms-setup' ) {
			return false;
		}

		$excluded_pages = array(
			'pms-optin-page',
			'pms-uninstall-page',
			'pms-register-page',
		);

		if ( in_array( $current_page, $excluded_pages, true ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		if ( $current_page === 'paid-member-subscriptions' || strpos( $current_page, 'pms-' ) === 0 ) {
			return true;
		}

		if ( ! empty( $screen->post_type ) && strpos( $screen->post_type, 'pms-' ) === 0 ) {
			return true;
		}

		if ( strpos( $screen->id, 'paid-member-subscriptions' ) !== false || strpos( $screen->id, 'pms-' ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Enqueue widget assets.
	 */
	public function enqueue_assets() {
		if ( ! $this->should_show_widget() ) {
			return;
		}

		wp_enqueue_style(
			'pms-support-chat',
			PMS_PLUGIN_DIR_URL . 'assets/css/admin/support-chat.css',
			array(),
			PMS_VERSION
		);

		wp_enqueue_script(
			'pms-support-chat',
			PMS_PLUGIN_DIR_URL . 'assets/js/admin/support-chat.js',
			array(),
			PMS_VERSION,
			true
		);

		$new_count    = 0;
		$last_viewed  = (int) get_user_meta( get_current_user_id(), self::LAST_VIEWED_META_KEY, true );
		$cached_posts = get_transient( self::CACHE_KEY );

		if ( false === $cached_posts ) {
			$cached_posts = $this->get_forum_posts();
		}

		if ( is_array( $cached_posts ) ) {
			foreach ( $cached_posts as $post ) {
				if ( ! empty( $post['timestamp'] ) && $post['timestamp'] > $last_viewed ) {
					$new_count++;
				}
			}
		}

		wp_localize_script(
			'pms-support-chat',
			'pmsSupportChat',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'pms_support_chat' ),
				'forumUrl'    => self::FORUM_URL,
				'newTopicUrl' => self::NEW_TOPIC_URL,
				'newCount'    => $new_count,
				'lastViewed'  => $last_viewed,
				'strings'     => array(
					'title'          => __( 'Need Help?', 'paid-member-subscriptions' ),
					'subtitle'       => __( 'Recent community discussions', 'paid-member-subscriptions' ),
					'loading'        => __( 'Loading...', 'paid-member-subscriptions' ),
					'error'          => __( 'Unable to load forum posts', 'paid-member-subscriptions' ),
					'askQuestion'    => __( 'Ask a Question', 'paid-member-subscriptions' ),
					'viewAll'        => __( 'View All Topics', 'paid-member-subscriptions' ),
					'postedBy'       => __( 'by', 'paid-member-subscriptions' ),
					'encourageTitle' => __( 'Have a question?', 'paid-member-subscriptions' ),
					'encourageText'  => __( 'Get help directly from the plugin developers, suggest improvements, or share your feedback!', 'paid-member-subscriptions' ),
					'tipTitle'       => __( 'Tip for faster help:', 'paid-member-subscriptions' ),
					'tipText'        => __( 'Include what you tried, what you expected, and what happened. Screenshots help!', 'paid-member-subscriptions' ),
				),
			)
		);
	}

	/**
	 * Render chat widget HTML.
	 */
	public function render_chat_widget() {
		if ( ! $this->should_show_widget() ) {
			return;
		}
		?>
		<div id="pms-support-chat-widget" class="pms-support-chat" style="display: none;">
			<button type="button" class="pms-support-chat__toggle" aria-label="<?php esc_attr_e( 'Toggle support chat', 'paid-member-subscriptions' ); ?>">
				<span class="pms-support-chat__toggle-content">
					<span class="pms-support-chat__toggle-icon pms-support-chat__toggle-icon--chat">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
							<path d="M12 2C6.48 2 2 6.04 2 11c0 2.21.89 4.22 2.34 5.75L2 22l5.25-2.34C8.78 20.53 10.35 21 12 21c5.52 0 10-4.04 10-9s-4.48-9-10-9zm0 16c-1.34 0-2.62-.29-3.78-.82l-.37-.18-2.49 1.11.98-2.58-.28-.4C4.74 13.98 4 12.55 4 11c0-3.87 3.59-7 8-7s8 3.13 8 7-3.59 7-8 7z"/>
							<circle cx="8" cy="11" r="1.5"/>
							<circle cx="12" cy="11" r="1.5"/>
							<circle cx="16" cy="11" r="1.5"/>
						</svg>
					</span>
					<span class="pms-support-chat__toggle-icon pms-support-chat__toggle-icon--close">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
							<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
						</svg>
					</span>
					<span class="pms-support-chat__toggle-text">
						<?php esc_html_e( 'Need Help?', 'paid-member-subscriptions' ); ?>
						<span class="pms-support-chat__toggle-subtext"><?php esc_html_e( 'Ask the community', 'paid-member-subscriptions' ); ?></span>
					</span>
				</span>
				<span class="pms-support-chat__badge"></span>
			</button>

			<div class="pms-support-chat__window">
				<div class="pms-support-chat__header">
					<div class="pms-support-chat__header-content">
						<div class="pms-support-chat__avatar">
							<img src="<?php echo esc_url( PMS_PLUGIN_DIR_URL . 'assets/images/pms-logo.svg' ); ?>" alt="Paid Member Subscriptions" width="32" height="32">
						</div>
						<div class="pms-support-chat__header-text">
							<h4 class="pms-support-chat__title"></h4>
							<p class="pms-support-chat__subtitle"></p>
						</div>
					</div>
					<button type="button" class="pms-support-chat__close" aria-label="<?php esc_attr_e( 'Close', 'paid-member-subscriptions' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
							<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
						</svg>
					</button>
				</div>

				<div class="pms-support-chat__body">
					<div class="pms-support-chat__encourage">
						<div class="pms-support-chat__encourage-card">
							<h5 class="pms-support-chat__encourage-title"></h5>
							<p class="pms-support-chat__encourage-text"></p>
						</div>
						<div class="pms-support-chat__tip">
							<strong class="pms-support-chat__tip-title"></strong>
							<p class="pms-support-chat__tip-text"></p>
						</div>
					</div>

					<div class="pms-support-chat__section-label"></div>

					<div class="pms-support-chat__loading">
						<div class="pms-support-chat__spinner"></div>
						<span></span>
					</div>

					<div class="pms-support-chat__posts"></div>
				</div>

				<div class="pms-support-chat__footer">
					<a href="<?php echo esc_url( self::NEW_TOPIC_URL ); ?>" target="_blank" rel="noopener" class="pms-support-chat__btn pms-support-chat__btn--primary">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
							<path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
						</svg>
						<span></span>
					</a>
					<a href="<?php echo esc_url( self::FORUM_URL ); ?>" target="_blank" rel="noopener" class="pms-support-chat__btn pms-support-chat__btn--secondary">
						<span></span>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
							<path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
						</svg>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler to get forum posts.
	 */
	public function ajax_get_forum_posts() {
		check_ajax_referer( 'pms_support_chat', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'paid-member-subscriptions' ) ) );
		}

		$posts = $this->get_forum_posts();

		if ( is_wp_error( $posts ) ) {
			wp_send_json_error( array( 'message' => $posts->get_error_message() ) );
		}

		wp_send_json_success( array( 'posts' => $posts ) );
	}

	/**
	 * AJAX handler to mark forum posts as read.
	 */
	public function ajax_mark_forum_posts_read() {
		check_ajax_referer( 'pms_support_chat', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'paid-member-subscriptions' ) ) );
		}

		update_user_meta( get_current_user_id(), self::LAST_VIEWED_META_KEY, time() );

		wp_send_json_success();
	}

	/**
	 * Get forum posts from RSS feed.
	 *
	 * @return array|WP_Error
	 */
	private function get_forum_posts() {
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_get(
			self::FEED_URL,
			array(
				'timeout'   => 4,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			set_transient( self::CACHE_KEY, array(), self::CACHE_DURATION );
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			set_transient( self::CACHE_KEY, array(), self::CACHE_DURATION );
			return new WP_Error( 'empty_feed', __( 'Empty feed response', 'paid-member-subscriptions' ) );
		}

		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $body );

		if ( false === $xml ) {
			set_transient( self::CACHE_KEY, array(), self::CACHE_DURATION );
			return new WP_Error( 'parse_error', __( 'Unable to parse feed', 'paid-member-subscriptions' ) );
		}

		$posts = array();
		$count = 0;

		if ( isset( $xml->channel->item ) ) {
			foreach ( $xml->channel->item as $item ) {
				if ( $count >= self::POSTS_COUNT ) {
					break;
				}

				$dc = $item->children( 'http://purl.org/dc/elements/1.1/' );

				$title = (string) $item->title;
				$title = strip_tags( $title );
				$title = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );
				$title = trim( $title );

				$posts[] = array(
					'title'     => $title,
					'link'      => (string) $item->link,
					'date'      => $this->format_date( (string) $item->pubDate ),
					'timestamp' => (int) strtotime( (string) $item->pubDate ),
					'author'    => isset( $dc->creator ) ? (string) $dc->creator : '',
				);

				$count++;
			}
		}

		set_transient( self::CACHE_KEY, $posts, self::CACHE_DURATION );

		return $posts;
	}

	/**
	 * Format date for display.
	 *
	 * @param string $date_string Feed date string.
	 *
	 * @return string
	 */
	private function format_date( $date_string ) {
		$timestamp = strtotime( $date_string );
		if ( ! $timestamp ) {
			return '';
		}

		$now  = time();
		$diff = $now - $timestamp;

		if ( $diff < DAY_IN_SECONDS ) {
			$hours = floor( $diff / HOUR_IN_SECONDS );

			if ( $hours < 1 ) {
				return __( 'Just now', 'paid-member-subscriptions' );
			}

			return sprintf(
				/* translators: %d: number of hours */
				_n( '%d hour ago', '%d hours ago', $hours, 'paid-member-subscriptions' ),
				$hours
			);
		}

		if ( $diff < WEEK_IN_SECONDS ) {
			$days = floor( $diff / DAY_IN_SECONDS );

			return sprintf(
				/* translators: %d: number of days */
				_n( '%d day ago', '%d days ago', $days, 'paid-member-subscriptions' ),
				$days
			);
		}

		return date_i18n( get_option( 'date_format' ), $timestamp );
	}
}

add_action( 'admin_init', array( 'PMS_Admin_Support_Chat', 'get_instance' ) );
