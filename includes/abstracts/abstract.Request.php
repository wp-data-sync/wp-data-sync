<?php
/**
 * Data Sync Request
 *
 * Abstract Request
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Request {

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

		Log::write( 'access-attempt', "Access Token Provided" );

		if ( $access_token === $local_token ) {

			Log::write( 'access-attempt', "Access token Approved" );

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

		Log::write( 'access-attempt', "Private Token Provided" );

		if ( $private_key === $local_token ) {

			Log::write( 'access-attempt', "Private Token Approved" );

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

	/**
	 * Request data.
	 *
	 * @param $json
	 *
	 * @return mixed|void
	 */

	public function request_data( $json ) {

		Log::write( $this->log_key, 'Sync Request JSON' );
		Log::write( $this->log_key, $json );

		$raw_data = json_decode( $json, TRUE );

		Log::write( $this->log_key, 'Sync Request Raw Data' );
		Log::write( $this->log_key, $raw_data );

		$data     = $this->sanitize_request( $raw_data );

		Log::write( $this->log_key, 'Sync Request Sanitized Data' );
		Log::write( $this->log_key, $data );

		return apply_filters( 'wp_data_sync_data', $data );

	}

	/**
	 * Sanitize request.
	 *
	 * @param $raw_data
	 *
	 * @return array|bool
	 */

	public function sanitize_request( $raw_data ) {

		$data = [];

		if ( ! is_array( $raw_data ) ) {
			die( __( 'A valid array is required!!' ) );
		}

		foreach ( $raw_data as $key => $value ) {

			$key = $this->sanitize_key( $key );

			if ( is_array( $value ) ) {

				$data[ $key ] = $this->sanitize_request( $value );

			} else {

				$sanitize_callback = $this->sanitize_callback( $key );

				$data[ $key ] = $this->sanitize_data( $sanitize_callback, $value );

			}

		}

		unset( $data['access_token'] );

		return $data;

	}

	/**
	 * Sanitize key.
	 *
	 * @param $key
	 *
	 * @return bool|float|int|string
	 */

	public function sanitize_key( $key ) {

		if ( is_string( $key ) ) {
			return $this->sanitize_data( 'string', $key );
		}

		if ( is_int( $key ) ) {
			return intval( $key );
		}

		die( __( 'A valid array is required!!' ) );

	}

	/**
	 * Sanitize callback.
	 *
	 * @param $key
	 *
	 * @return mixed|void
	 */

	public function sanitize_callback( $key ) {

		$sanitize_callback = 'string';

		if ( in_array( $key, [ 'post_content', 'post_excerpt' ] ) ) {
			$sanitize_callback = 'html';
		}

		if ( 'gallery_image_' === substr( $key, 0, 14 ) ) {
			$sanitize_callback = 'url';
		}

		Log::write( 'sanitize-callback', "$key - $sanitize_callback" );

		return apply_filters( 'wp_data_sync_sanitize_callback', $sanitize_callback, $key );

	}

	/**
	 * Sanitize data.
	 *
	 * @param $sanitize_callback
	 * @param $value
	 *
	 * @return bool|float|int|string
	 */

	public function sanitize_data( $sanitize_callback, $value ) {

		$value = trim( $value );

		if ( empty( $value ) ) {
			return '';
		}

		switch ( $sanitize_callback ) {

			case 'bool':
				$clean_value = boolval( $value );
				break;

			case 'float':
				$clean_value = floatval( $value );
				break;

			case 'int':
				$clean_value = intval( $value );
				break;

			case 'numeric':
				$clean_value = sanitize_text_field( $value );
				break;

			case 'email':
				$clean_value = sanitize_email( $value );
				break;

			case 'key':
				$clean_value = sanitize_key( $value );
				break;

			case 'html':
				// If we have some html from an editor, let's use allowed post html.
				// All scripts, videos, etc... will be removed.
				$clean_value = wp_kses_post( $value );
				break;

			case 'url':
				$clean_value = esc_url_raw( $value );
				break;

			case 'title':
				$clean_value = sanitize_title( $value );
				break;

			case 'filename':
				$clean_value = sanitize_file_name( $value );
				break;

			default:
				$clean_value = sanitize_text_field( $value );

		}

		return apply_filters( 'wp_data_sync_clean_value', $clean_value, $sanitize_callback );

	}

}