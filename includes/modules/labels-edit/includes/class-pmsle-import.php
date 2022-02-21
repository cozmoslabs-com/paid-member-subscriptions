<?php

class PMSLE_IN_Import {

	protected $args_to_import;
	public $import_messages = array();
	private $j = '0';

	/**
	 * this will take labels that will be imported to database.
	 *
	 * @param array  $args_to_import  labels to import.
	 */
	function __construct( $args_to_import ) {
		$this->args_to_import = $args_to_import;
	}

	/**
	 * this will save imported json.
	 *
	 * @param string  $json_content  imported json.
	 */
	private function json_to_db( $json_content ) {
		/* decode and put json to array */
		$imported_array_from_json = json_decode( $json_content, true );
		if ( $imported_array_from_json !== NULL ) {
			/* import labels to database */
			foreach( $imported_array_from_json as $key => $value ) {
				if( ! empty( $value ) ) {
					update_option( $key, $value );
				}
			}
		} else {
			$this->import_messages[$this->j]['message'] = __( 'Uploaded file is not valid json!', 'paid-member-subscriptions' );
			$this->import_messages[$this->j]['type'] = 'error';
			$this->j++;
		}
	}

	/* upload json file function */
	public function upload_json_file() {
		if( isset( $_POST['pmsle-import'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), 'pmsle_import_nonce' ) ) {
			if( ! empty( $_FILES['pmsle-upload']['tmp_name'] ) ) {
				$json_content = file_get_contents( $_FILES['pmsle-upload']['tmp_name'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$this->json_to_db( $json_content );

				if( empty( $this->import_messages ) ) {
					$this->import_messages[$this->j]['message'] = __( 'Import successfully!', 'paid-member-subscriptions' ) . "</p><p>" . __( 'Page will refresh in 3 seconds...', 'paid-member-subscriptions' ) . '<META HTTP-EQUIV="refresh" CONTENT="3">';
					$this->import_messages[$this->j]['type'] = 'updated';
					$this->j++;
					flush_rewrite_rules( false );
				}
			} else {
				$this->import_messages[$this->j]['message'] = __( 'Please select a .json file to import!', 'paid-member-subscriptions' );
				$this->import_messages[$this->j]['type'] = 'error';
				$this->j++;
			}
		}
	}

	/* messages return function */
	public function get_messages() {
		return $this->import_messages;
	}
}
