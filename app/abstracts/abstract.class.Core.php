<?php
/**
 * Data Sync Core
 *
 * Abstract Core
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Core {

	/**
	 * Allow access to sync data.
	 *
	 * @return bool
	 */

	public function access() {

		if ( $this->allowed() ) {

			if ( $referer = $this->referer() ) {

				Log::write( 'access-attempt', "Referer Captured: $referer" );

				if ( $referer === get_option( 'wp_data_sync_api_url' ) ) {

					Log::write( 'access-attempt', "Referer Approved: $referer" );

					if ( $this->private_key() ) {
						return TRUE;
					}

				}

			}

		}

		return FALSE;

	}

	/**
	 * Is access allowed.
	 *
	 * @return bool
	 */

	public function allowed() {
		return 'checked' === get_option( 'wp_data_sync_allowed' );
	}

	/**
	 * Verify the access_key.
	 *
	 * @return bool|string
	 */

	public function access_key( $param ) {

		$access_key = sanitize_key( $param );

		$access_key = empty( $access_key ) ? FALSE : $access_key;

		$local_key = get_option( 'wp_data_sync_access_key' );

		Log::write( 'access-attempt', "Access Key Available" );

		if ( $access_key && $access_key === $local_key ) {

			Log::write( 'access-attempt', "Access Key Approved" );

			return TRUE;

		}

		return FALSE;

	}

	/**
	 * Verify private key.
	 *
	 * @return bool
	 */

	public function private_key() {

		$private_key = sanitize_key( $_SERVER['HTTP_AUTHENTICATION'] );

		$private_key = empty( $private_key ) ? FALSE : $private_key;

		$local_key = get_option( 'wp_data_sync_private_key' );

		Log::write( 'access-attempt', "Private Key Available" );

		if ( $private_key && $private_key === $local_key ) {

			Log::write( 'access-attempt', "Private Key Approved" );

			return TRUE;

		}

		return FALSE;

	}

	/**
	 * Get the HTTP referer header.
	 *
	 * @return bool|string
	 */

	public function referer() {

		$referer = sanitize_text_field( $_SERVER['HTTP_REFERER'] );

		$referer = empty( $referer ) ? FALSE : $referer;

		Log::write( 'access-attempt', "Referer Available: $referer" );

		return $referer;

	}

}