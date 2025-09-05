<?php
/**
 * Log
 *
 * Log plugin errors.
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

use WP_Filesystem_Direct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Log {

	const FILE_KEY = 'wp_data_sync_log_file';
	const ALLOWED_KEY = 'wp_data_sync_allow_logging';

	/**
	 * Write the log to the file.
	 *
	 * @param string              $key
	 * @param string|array|object $data
	 * @param string              $action
	 */

	public static function write( $key, $data, $action = '' ) {

		if ( self::is_active() ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';

            $wp_filesystem = new WP_Filesystem_Direct( false );
			$msg           = self::message( $data, $action );
			$date          = gmdate( 'Y-m-d' );
			$hash          = self::log_hash();
			$error_file    = WPDSYNC_LOG_DIR . "{$key}-{$date}-{$hash}.log";

			// Create the log file dir if we do not already have one.
			if ( ! $wp_filesystem->exists( WPDSYNC_LOG_DIR ) ) {
                $wp_filesystem->mkdir( WPDSYNC_LOG_DIR, 0755, true );
			}

            $current_content = '';

			if ( ! $wp_filesystem->exists( $error_file ) ) {
				// Schedule deletion of the log file in 10 days
				wp_schedule_single_event( time() + ( 10 * DAY_IN_SECONDS ), 'wpds_delete_log_file', [ $error_file ] );
            }
            else {
                $current_content .= $wp_filesystem->get_contents( $error_file );
            }

            $wp_filesystem->put_contents( $error_file, $current_content . $msg, 0644 );

		}

	}

	/**
	 * Log message..
	 *
	 * @param string|array|object $data
	 * @param string              $action
	 *
	 * @return string
	 */

	public static function message( $data, $action ) {

		ob_start();

		$date = gmdate( "F j, Y, g:i a" );

        printf( '[%s] - %s - ', esc_html( $date), esc_html( $action ) );

		if ( is_array( $data ) || is_object( $data ) ) {
			print_r( $data );
		} else {
			echo wp_kses_post( $data );
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
		return Settings::is_checked( Log::ALLOWED_KEY );
	}

	/**
	 * Log file contents.
	 *
	 * @return string
	 */

	public static function log_file() {

		if ( $file_name = get_option( Log::FILE_KEY ) ) {

			if ( $contents = self::contents( $file_name ) ) {
				return $contents;
			}

		}

		return __( 'Please choose a file and save changes.', 'wp-data-sync' );

	}

	/**
	 * Contents.
	 *
	 * @param $file_name
	 *
	 * @return false|string
	 */

	public static function contents( $file_name ) {

		$file = WPDSYNC_LOG_DIR . $file_name;

		if ( file_exists( $file ) ) {
			return file_get_contents( $file );
		}

		return false;

	}

	/**
	 * Log files.
	 *
	 * @return array|bool
	 */

	public static function log_files() {

		$files = glob( WPDSYNC_LOG_DIR . '*.log', GLOB_NOSORT );

		if ( is_array( $files ) && ! empty( $files ) ) {
			return array_map( 'basename', $files );
		}

		return false;

	}

	/**
	 * Log Hash
	 *
	 * Random hash for log file name.
	 *
	 * @return bool|false|mixed|string|void
	 */

	public static function log_hash() {

		if ( ! $log_hash = get_option( 'wp_data_sync_log_hash' ) ) {

			$log_hash = wp_hash( home_url() . wp_rand() );

			add_option( 'wp_data_sync_log_hash', $log_hash );

		}

		return $log_hash;

	}

}