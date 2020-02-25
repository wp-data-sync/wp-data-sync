<?php
/**
 * Log
 *
 * Log plugin errors.
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

class Log {

	/**
	 * Write the log to the file.
	 *
	 * @param $key
	 * @param $data
	 */

	public static function write( $key, $data ) {

		if ( 'checked' === get_option( 'wp_data_sync_allow_logging' ) ) {

			$msg        = self::message( $data );
			$date       = date( 'Y-m-d' );
			$error_file = WP_DATA_SYNC_LOG_DIR . "{$key}-{$date}.log";

			// Create the log file dir if we do not already have one.
			if ( ! file_exists( WP_DATA_SYNC_LOG_DIR ) ) {
				mkdir( WP_DATA_SYNC_LOG_DIR, 0755, TRUE );
			}

			if ( ! file_exists( $error_file ) ) {
				fopen( $error_file, 'w' );
			}

			error_log( $msg, 3, $error_file );

		}

	}

	/**
	 * Log message..
	 *
	 * @param $data
	 *
	 * @return string
	 */

	public function message( $data ) {

		ob_start();

		$date = date( "F j, Y, g:i a" );

		echo "[{$date}] - ";

		if ( is_array( $data ) || is_object( $data ) ) {
			print_r( $data );
		} else {
			echo $data;
		}

		echo "\n";
		echo '__________________________________________________________________________';
		echo "\n";

		return ob_get_clean();

	}

}