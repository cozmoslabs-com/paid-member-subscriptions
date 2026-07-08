<?php
/**
 * Batch Export Class
 *
 * This is the base class for all batch export methods. Each data export type (members, payments, etc) extend this class
 *
 * @package     Paid Member Subscriptions
 * @subpackage  Admin/Export
 * @copyright   Copyright (c) 2018, Cristian Antohe. Initial code extracted from Easy Digital Downloads by Pippin Williamson.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.7.6
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'PMS_Export', false ) ) {
	require_once PMS_PLUGIN_DIR_PATH . 'includes/admin/export/class-export.php';
}

/**
 * PMS_Export Class
 *
 * @since 1.7.6
 */
class PMS_Batch_Export extends PMS_Export {

	/**
	 * Load export class dependencies.
	 */
	public static function load_dependencies() {
		require_once PMS_PLUGIN_DIR_PATH . 'includes/admin/export/class-export.php';
		require_once PMS_PLUGIN_DIR_PATH . 'includes/admin/export/class-batch-export.php';
		require_once PMS_PLUGIN_DIR_PATH . 'includes/admin/export/class-batch-export-members.php';
		require_once PMS_PLUGIN_DIR_PATH . 'includes/admin/export/class-batch-export-payments.php';
	}

	/**
	 * Export class names permitted for batch export handlers.
	 *
	 * @return string[]
	 */
	public static function get_allowed_class_names() {
		return apply_filters( 'pms_allowed_export_classes', array(
			'PMS_Batch_Export_Members',
			'PMS_Batch_Export_Payments',
		) );
	}

	/**
	 * Instantiate an allowed batch export class.
	 *
	 * @param string   $class_name
	 * @param int|null $step
	 * @return PMS_Batch_Export|null
	 */
	public static function create( $class_name, $step = null ) {
		if ( ! in_array( $class_name, self::get_allowed_class_names(), true ) ) {
			return null;
		}

		self::load_dependencies();

		return null === $step ? new $class_name() : new $class_name( $step );
	}

	/**
	 * The file the data is stored in
	 *
	 * @since 1.7.6
	 */
	protected $file;

	/**
	 * The name of the file the data is stored in
	 *
	 * @since 1.7.6
	 */
	public $filename;

	/**
	 * The file type, typically .csv
	 *
	 * @since 1.7.6
	 */
	public $filetype;

	/**
	 * The current step being processed
	 *
	 * @since 1.7.6
	 */
	public $step;

	/**
	 * Start date, Y-m-d H:i:s
	 *
	 * @since 1.7.6
	 */
	public $start;

	/**
	 * End date, Y-m-d H:i:s
	 *
	 * @since 1.7.6
	 */
	public $end;

	/**
	 * Status to export
	 *
	 * @since 1.7.6
	 */
	public $status;

	/**
	 * Download to export data for
	 *
	 * @since 1.7.6
	 */
	public $download = null;

	/**
	 * Download Price ID to export data for
	 *
	 * @since 1.7.6
	 */
	public $price_id = null;

	/**
	 * Is the export file writable
	 *
	 * @since 1.7.6
	 */
	public $is_writable = true;

	/**
	 *  Is the export file empty
	 *
	 * @since 1.7.6
	 */
	public $is_empty = false;

	/**
	 *  Is export finished
	 *
	 * @since 1.7.6
	 */
	public $done = false;

	/**
	 * Get things started
	 *
	 * @param $_step int The step to process
	 * @since 1.7.6
	 */
	public function __construct( $_step = 1 ) {

		$this->filetype = '.csv';
		$this->step     = $_step;
	}

	/**
	 * Transient key for the current user's in-progress export file path.
	 *
	 * @return string
	 */
	protected function get_export_transient_key() {
		return 'pms_batch_export_' . get_current_user_id() . '_' . $this->export_type;
	}

	/**
	 * Create or resume the staged export file for the current batch step.
	 *
	 * @return bool
	 */
	protected function ensure_batch_export_file() {

		if ( $this->step < 2 ) {
			return $this->create_staged_export_file();
		}

		return $this->load_staged_export_file();
	}

	/**
	 * Load the staged export file path from the current user's transient.
	 *
	 * @param bool $require_exists Whether the file must already exist on disk.
	 * @return bool
	 */
	protected function load_staged_export_file( $require_exists = true ) {

		$this->file = get_transient( $this->get_export_transient_key() );

		if ( ! $this->is_valid_export_file_path( $this->file ) ) {
			$this->is_writable = false;
			return false;
		}

		if ( $require_exists && ! file_exists( $this->file ) ) {
			$this->is_writable = false;
			return false;
		}

		$this->filename = basename( $this->file );

		return true;
	}

	/**
	 * Create a new private temp export file for step 1.
	 *
	 * @return bool
	 */
	protected function create_staged_export_file() {

		$this->cleanup_legacy_public_export_file();
		$this->teardown_staged_export_file();

		$this->file = wp_tempnam( 'pms-export-' . $this->export_type );

		if ( false === $this->file ) {
			$this->is_writable = false;
			return false;
		}

		set_transient( $this->get_export_transient_key(), $this->file, HOUR_IN_SECONDS );

		$this->filename = basename( $this->file );

		$directory = dirname( $this->file );

		if ( ! is_writeable( $directory ) || ! is_writeable( $this->file ) ) {
			$this->is_writable = false;
			return false;
		}

		return true;
	}

	/**
	 * Ensure an export path points to a file inside the system temp directory.
	 *
	 * @param mixed $path
	 * @return bool
	 */
	protected function is_valid_export_file_path( $path ) {

		if ( empty( $path ) || ! is_string( $path ) ) {
			return false;
		}

		$real_path = realpath( $path );

		if ( false === $real_path ) {
			return false;
		}

		$temp_dir = realpath( get_temp_dir() );

		if ( false === $temp_dir ) {
			return false;
		}

		return 0 === strpos( $real_path, trailingslashit( $temp_dir ) );
	}

	/**
	 * Remove predictable legacy export files from the public uploads directory.
	 */
	protected function cleanup_legacy_public_export_file() {

		$upload_dir  = wp_upload_dir();
		$legacy_file = trailingslashit( $upload_dir['basedir'] ) . 'pms-' . $this->export_type . $this->filetype;

		if ( file_exists( $legacy_file ) ) {
			@unlink( $legacy_file );
		}
	}

	/**
	 * Delete the staged export file and clear its transient.
	 */
	protected function teardown_staged_export_file() {

		$stored_file = get_transient( $this->get_export_transient_key() );

		if ( $this->is_valid_export_file_path( $stored_file ) && file_exists( $stored_file ) ) {
			@unlink( $stored_file );
		}

		delete_transient( $this->get_export_transient_key() );
	}

	/**
	 * Process a step
	 *
	 * @since 1.7.6
	 * @return bool
	 */
	public function process_step() {

		if ( ! $this->can_export() ) {
			wp_die( esc_html__( 'You do not have permission to export data.', 'paid-member-subscriptions' ), esc_html__( 'Error', 'paid-member-subscriptions' ), array( 'response' => 403 ) );
		}

		if ( ! $this->ensure_batch_export_file() ) {
			return 'error';
		}

		if( $this->step < 2 ) {
			$this->print_csv_cols();
		}

		$rows = $this->print_csv_rows();

		if( $rows ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Output the CSV columns
	 *
	 * @since 1.7.6
	 * @uses PMS_Export::get_csv_cols()
	 * @return string
	 */
	public function print_csv_cols() {

		$col_data = '';
		$cols = $this->get_csv_cols();
		$i = 1;
		foreach( $cols as $col_id => $column ) {
			$column   = $this->sanitize_csv_cell( $column );
			$col_data .= '"' . addslashes( $column ) . '"';
			$col_data .= $i == count( $cols ) ? '' : ',';
			$i++;
		}
		$col_data .= "\r\n";

		$this->stash_step_data( $col_data );

		return $col_data;

	}

	/**
	 * Print the CSV rows for the current step
	 *
	 * @since 1.7.6
	 * @return string|false
	 */
	public function print_csv_rows() {

		$row_data = '';
		$data     = $this->get_data();
		$cols     = $this->get_csv_cols();

		if( $data ) {

			// Output each row
			foreach ( $data as $row ) {
				$i = 1;
				foreach ( $row as $col_id => $column ) {

					// Make sure the column is valid
					if ( array_key_exists( $col_id, $cols ) ) {
					    if( !is_array( $column ) ){

							if( !is_null( $column ) ) {
								$column = $this->sanitize_csv_cell( $column );
								$column = addslashes( preg_replace( "/\"/","'", $column ) );
							}

                            $row_data .= '"' . $column . '"';
                            $row_data .= $i == count( $cols ) ? '' : ',';
                            $i++;
                        }
					}
				}
				$row_data .= "\r\n";
			}

			$this->stash_step_data( $row_data );

			return $row_data;
		}

		return false;
	}

	/**
	 * Return the calculated completion percentage
	 *
	 * @since 1.7.6
	 * @return int
	 */
	public function get_percentage_complete() {
		return 100;
	}

	/**
	 * Retrieve the file data is written to
	 *
	 * @since 1.7.6
	 * @return string
	 */
	protected function get_file() {

		$file = '';

		if ( @file_exists( $this->file ) ) {

			if ( ! is_writeable( $this->file ) ) {
				$this->is_writable = false;
			}

			$file = @file_get_contents( $this->file );

		} else {

			@file_put_contents( $this->file, '' );
			@chmod( $this->file, 0664 );

		}

		return $file;
	}

	/**
	 * Append data to export file
	 *
	 * @since 1.7.6
	 * @param $data string The data to add to the file
	 * @return void
	 */
	protected function stash_step_data( $data = '' ) {

		$file = $this->get_file();
		$file .= $data;
		@file_put_contents( $this->file, $file );

		// If we have no rows after this step, mark it as an empty export
		$file_rows    = file( $this->file, FILE_SKIP_EMPTY_LINES);
		$default_cols = $this->get_csv_cols();
		$default_cols = empty( $default_cols ) ? 0 : 1;

		$this->is_empty = count( $file_rows ) == $default_cols ? true : false;

	}

	/**
	 * Perform the export
	 *
	 * @since 1.7.6
	 * @return void
	 */
	public function export() {

		if ( ! $this->can_export() ) {
			wp_die( esc_html__( 'You do not have permission to export data.', 'paid-member-subscriptions' ), esc_html__( 'Error', 'paid-member-subscriptions' ), array( 'response' => 403 ) );
		}

		if ( ! $this->load_staged_export_file() ) {
			wp_die( esc_html__( 'Export file not found or expired.', 'paid-member-subscriptions' ), esc_html__( 'Error', 'paid-member-subscriptions' ), array( 'response' => 404 ) );
		}

		// Set headers
		$this->headers();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $this->file );

		$this->teardown_staged_export_file();

        die();
	}

	/*
	 * Set the properties specific to the export
	 *
	 * @since 1.7.6.2
	 * @param array $request The Form Data passed into the batch processing
	 */
	public function set_properties( $request ) {}

	/**
	 * Allow for prefetching of data for the remainder of the exporter
	 *
	 * @since  1.7.6
	 * @return void
	 */
	public function pre_fetch() {}

    /**
     * Get all available meta keys from pms_member_subscriptionmeta
     *
     * @since 1.7.6
     * @return array
     */
    public function get_all_pms_meta_keys( $table_name, $include_sensitive = false ) {
		global $wpdb;
		$list_of_meta_keys = array();

        if ( $include_sensitive ) {
            $forbidden_keys = array( '_paypal_billing_agreement_id', 'logs', 'pms_checkout_data' );
        }
        else {
            $forbidden_keys = array( '_paypal_billing_agreement_id', '_stripe_card_id', '_stripe_customer_id', 'logs', 'pms_checkout_data' );
        }

		$result = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}{$table_name}", ARRAY_A );
		if( $result ){
			foreach ( $result as $meta ){
				if( !in_array( $meta['meta_key'], $forbidden_keys ) && strpos( $meta['meta_key'], 'pms_gm_invited_emails' ) === false )
					$list_of_meta_keys[] = $meta['meta_key'];
			}
		}

		return $list_of_meta_keys;
    }

}
