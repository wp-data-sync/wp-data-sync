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

abstract class Core {

	/**
	 * Allow access to sync data.
	 *
	 * @return bool
	 */

	public function access() {

		if ( $referer = $this->referer() ) {

			Log::write( 'access-attempt', "Referer Captured: $referer" );

			if ( $referer === get_option( 'wp_data_sync_api_url' ) ) {

				Log::write( 'access-attempt', "Referer Approved: $referer" );

				if ( $access_token = $this->access_token() ) {

					Log::write( 'access-attempt', "Access Token Captured: $access_token" );

					if ( $access_token === get_option( 'wp_data_sync_access_secret' ) ) {

						Log::write( 'access-attempt', "Access Token Approved: $access_token" );

						return TRUE;

					}

				}

			}

		}

		return FALSE;

	}

	/**
	 * Get the HTTP access_token header.
	 *
	 * @return bool|string
	 */

	public function access_token() {

		$access_token = sanitize_text_field( $_SERVER['HTTP_AUTHENTICATION'] );

		$access_token = empty( $access_token ) ? FALSE : $access_token;

		Log::write( 'access-attempt', "Access Token Available: $access_token" );

		return $access_token;

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