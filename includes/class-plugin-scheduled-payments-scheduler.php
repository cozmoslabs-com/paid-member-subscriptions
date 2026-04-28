<?php
/**
 * Opt-in Action Scheduler pipeline for recurring subscription renewals (Misc → Payments).
 *
 * @package Paid Member Subscriptions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the recurring tick, enqueues per-subscription jobs, and coordinates with legacy WP-Cron when disabled.
 */
class PMS_Plugin_Scheduled_Payments_Scheduler {

	const GROUP     = 'pms_recurring_payments';
	const TICK_HOOK = 'pms_recurring_payments_tick';
	const JOB_HOOK  = 'pms_process_member_subscription_renewal';

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	private function __construct() {

		add_action( self::TICK_HOOK, array( $this, 'tick' ) );
		add_action( self::JOB_HOOK, array( $this, 'run_subscription_job' ), 10, 1 );
		add_action( 'init', array( $this, 'maybe_sync_on_init' ), 20 );
		add_action( 'update_option_pms_misc_settings', array( $this, 'on_misc_settings_updated' ), 10, 2 );
	}

	/**
	 * If Action Scheduler mode is on, clear stray legacy renewal cron and ensure the recurring action exists.
	 *
	 * @return void
	 */
	public function maybe_sync_on_init() {

		if ( ! pms_is_scheduled_payments_action_scheduler_enabled() ) {
			return;
		}

		if ( wp_next_scheduled( 'pms_cron_process_member_subscriptions_payments' ) ) {
			wp_clear_scheduled_hook( 'pms_cron_process_member_subscriptions_payments' );
		}

		$this->ensure_recurring_action_scheduled();
	}

	/**
	 * @return void
	 */
	public function ensure_recurring_action_scheduled() {

		if ( ! pms_is_scheduled_payments_action_scheduler_enabled() || ! function_exists( 'as_has_scheduled_action' ) || ! function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}

		if ( ! as_has_scheduled_action( self::TICK_HOOK, null, self::GROUP ) ) {
			$this->force_reschedule_recurring();
		}
	}

	/**
	 * Drop all actions in the renewal group and schedule a fresh recurring tick.
	 *
	 * @return void
	 */
	public function force_reschedule_recurring() {

		if ( ! function_exists( 'as_unschedule_all_actions' ) || ! function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}

		as_unschedule_all_actions( '', array(), self::GROUP );

		$interval = (int) apply_filters( 'pms_recurring_payments_tick_interval', HOUR_IN_SECONDS );
		$interval = max( 60, $interval );

		as_schedule_recurring_action( time(), $interval, self::TICK_HOOK, array(), self::GROUP );
	}

	/**
	 * Enable Action Scheduler mode: stop legacy WP-Cron renewal event and schedule the recurring tick.
	 *
	 * @return void
	 */
	public function enable_action_scheduler_mode() {

		wp_clear_scheduled_hook( 'pms_cron_process_member_subscriptions_payments' );
		$this->force_reschedule_recurring();
	}

	/**
	 * Disable Action Scheduler mode: cancel renewal queue actions and restore legacy daily cron.
	 *
	 * @return void
	 */
	public function disable_action_scheduler_mode() {

		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( '', array(), self::GROUP );
		}

		pms_maybe_schedule_legacy_renewal_cron();
	}

	/**
	 * @param mixed $old_value Previous option value.
	 * @param mixed $value     New option value.
	 * @return void
	 */
	public function on_misc_settings_updated( $old_value, $value ) {

		$old_on = is_array( $old_value ) && ! empty( $old_value['payments']['use_action_scheduler_for_renewals'] );
		$new_on = is_array( $value ) && ! empty( $value['payments']['use_action_scheduler_for_renewals'] );

		if ( $old_on === $new_on ) {
			return;
		}

		if ( $new_on ) {
			$this->enable_action_scheduler_mode();
		} else {
			$this->disable_action_scheduler_mode();
		}
	}

	/**
	 * Query due subscriptions and enqueue unique async renewal jobs.
	 *
	 * @return void
	 */
	public function tick() {

		if ( ! pms_is_scheduled_payments_action_scheduler_enabled() || ! function_exists( 'as_enqueue_async_action' ) ) {
			return;
		}

		if ( defined( 'PMS_DEV_ENVIRONMENT' ) && PMS_DEV_ENVIRONMENT === true ) {
			return;
		}

		if ( pms_website_was_previously_initialized() ) {
			return;
		}

		$batch_size = (int) apply_filters( 'pms_recurring_payments_tick_batch_size', 100 );
		$batch_size = max( 1, $batch_size );

		$base_args = array(
			'status'                      => 'active',
			'billing_next_payment_after'  => date( 'Y-m-d H:i:s', time() - MONTH_IN_SECONDS ),
			'billing_next_payment_before' => date( 'Y-m-d H:i:s' ),
			'cron_query'                  => true,
			'orderby'                     => 'billing_next_payment',
			'order'                       => 'ASC',
			'number'                      => $batch_size,
		);

		$offset = 0;

		while ( true ) {
			$args           = $base_args;
			$args['offset'] = $offset;

			$subscriptions = pms_get_member_subscriptions( $args );

			if ( empty( $subscriptions ) ) {
				break;
			}

			foreach ( $subscriptions as $subscription ) {
				if ( empty( $subscription->id ) ) {
					continue;
				}

				as_enqueue_async_action(
					self::JOB_HOOK,
					array( (int) $subscription->id ),
					self::GROUP,
					false
				);
			}

			if ( count( $subscriptions ) < $batch_size ) {
				break;
			}

			$offset += $batch_size;
		}
	}

	/**
	 * @param int $subscription_id Member subscription ID.
	 * @return void
	 */
	public function run_subscription_job( $subscription_id ) {

		$subscription_id = (int) $subscription_id;

		if ( $subscription_id < 1 ) {
			return;
		}

		if ( ! pms_is_scheduled_payments_action_scheduler_enabled() ) {
			return;
		}

		if ( defined( 'PMS_DEV_ENVIRONMENT' ) && PMS_DEV_ENVIRONMENT === true ) {
			return;
		}

		if ( pms_website_was_previously_initialized() ) {
			return;
		}

		$subscription = pms_get_member_subscription( $subscription_id );

		if ( empty( $subscription->id ) ) {
			return;
		}

		pms_maybe_process_member_subscription_renewal( $subscription );
	}

}

add_action(
	'plugins_loaded',
	static function () {
		PMS_Plugin_Scheduled_Payments_Scheduler::instance();
	},
	15
);
