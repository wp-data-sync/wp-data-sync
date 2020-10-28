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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Log {

	/**
	 * Write the log to the file.
	 *
	 * @param $key
	 * @param $data
	 */

	public static function write( $key, $data ) {

		if ( self::is_active() ) {

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

	/**
	 * Is log active.
	 *
	 * @return bool
	 */

	public static function is_active() {
		return Settings::is_checked( 'wp_data_sync_allow_logging' );
	}

	/**
	 * Log file contents.
	 *
	 * @return string
	 */

	public static function log_file() {

		if ( $file_name = get_option('wp_data_sync_log_file') ) {

			$file = WP_DATA_SYNC_LOG_DIR . $file_name;

			if ( file_exists( $file ) ) {
				return file_get_contents( $file );
			}

		}

		return __( 'File does not exist. Please choose a different file and save changes.', 'wp-data-sync' );

	}

	/**
	 * Log files.
	 *
	 * @return array|bool
	 */

	public static function log_files() {

		$files = glob( WP_DATA_SYNC_LOG_DIR . '*.log' );

		if ( is_array( $files ) && ! empty( $files ) ) {
			return array_map( 'basename', $files );
		}

		return FALSE;

	}

}