<?php
/**
 * Data Sync Access
 *
 * Abstract Access
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Access {

	/**
	 * Allow access to sync data.
	 *
	 * @return bool
	 */

	public function access() {

		if ( $this->allowed() && $this->referer() ) {
			return $this->private_key();
		}

		return FALSE;

	}

	/**
	 * Is access allowed.
	 *
	 * @return bool
	 */

	public function allowed() {
		return Settings::is_checked( $this->permissions_key );
	}

	/**
	 * Verify the access_token.
	 *
	 * @return bool|string
	 */

	public function access_token( $param ) {

		$access_token = sanitize_key( $param );

		if ( empty( $access_token ) ) {
			return FALSE;
		}

		if ( ! $local_token = get_option( $this->access_token_key ) ) {
			return FALSE;
		}

		Log::write( 'access-attempt', "Access Key Available" );

		if ( $access_token === $local_token ) {

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

		if ( empty( $private_key ) ) {
			return FALSE;
		}

		if ( ! $local_token = get_option( $this->private_token_key ) ) {
			return FALSE;
		}

		Log::write( 'access-attempt', "Private Key Available" );

		if ( $private_key === $local_token ) {

			Log::write( 'access-attempt', "Private Key Approved" );

			return TRUE;

		}

		return FALSE;

	}

	/**
	 * Get the HTTP referer header.
	 *
	 * @return bool
	 */

	public function referer() {

		$referer = sanitize_text_field( $_SERVER['HTTP_REFERER'] );

		if ( empty( $referer )  ) {
			return FALSE;
		}

		Log::write( 'access-attempt', "Referer: $referer" );

		return TRUE;

	}

}