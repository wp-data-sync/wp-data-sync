<?php
/**
 * Sync Request
 *
 * Process the DataSync Request.
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SyncRequest extends Access {

	/**
	 * @var string
	 */

	protected $access_token_key = 'wp_data_sync_access_token';

	/**
	 * @var string
	 */

	protected $private_token_key = 'wp_data_sync_private_token';

	/**
	 * @var string
	 */

	protected $permissions_key = 'wp_data_sync_allowed';

	/**
	 * @var SyncRequest
	 */

	public static $instance;

	/**
	 * SyncRequest constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * Instance.
	 *
	 * @return SyncRequest
	 */

	public static function instance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Register the route.
	 *
	 * @link https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 */

	public function register_route() {

		register_rest_route(
			'wp-data-sync/' . WPDSYNC_EP_VERSION,
			'/sync/(?P<access_token>\S+)/(?P<cache_buster>\S+)/',
			[
				'methods' => WP_REST_Server::CREATABLE,
				'args'    => [
					'access_token' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'access_token' ]
					],
					'cache_buster' => [
						'validate_callback' => function( $param ) {
							return is_string( $param );
						}
					]
				],
				'permission_callback' => [ $this, 'access' ],
				'callback'            => [ $this, 'request' ],
			]
		);

	}

	/**
	 * Process the request.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|\WP_REST_Response
	 */

	public function request( WP_REST_Request $request ) {

		$start_request = microtime();
		$response      = [];
		$data_sync     = DataSync::instance();
		$json          = $request->get_body();
		$data          = $this->request_data( $json );

		if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {

			foreach ( $data['items'] as $key => $data ) {

				$data_sync->set_properties( $data );

				$response['items'][ $key ] = $data_sync->process();

			}

		}
		else {

			$data_sync->set_properties( $data );

			$response['items'][] = $data_sync->process();

		}

		$response['request_time'] = microtime() - $start_request;
		Log::write( 'sync-request-response', $response );

		return rest_ensure_response( $response );

	}

	/**
	 * Request data.
	 *
	 * @param $json
	 *
	 * @return mixed|void
	 */

	public function request_data( $json ) {

		Log::write( 'sync-request-data', 'Sync Request JSON' );
		Log::write( 'sync-request-data', $json );

		$raw_data = json_decode( $json, TRUE );

		Log::write( 'sync-request-data', 'Sync Request Raw Data' );
		Log::write( 'sync-request-data', $raw_data );

		$data     = $this->sanitize_request( $raw_data );

		Log::write( 'sync-request-data', 'Sync Request Sanitized Data' );
		Log::write( 'sync-request-data', $data );

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

				$data[$key] = $this->sanitize_request( $value );

			} else {

				$sanitize_callback = $this->sanitize_callback( $key );

				$data[$key] = $this->sanitize_data( $sanitize_callback, $value );

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

		return apply_filters( 'wp_data_sync_clean_value', $clean_value );

	}

}
