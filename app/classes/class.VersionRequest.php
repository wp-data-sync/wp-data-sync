<?php
/**
 * Version Request
 *
 * Request plugin version.
 *
 * @since   1.4.2
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VersionRequest extends Core {

	/**
	 * @var VersionRequest
	 */

	public static $instance;

	/**
	 * VersionRequest constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * Instance.
	 *
	 * @return VersionRequest
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
			'wp-data-sync/' . WP_DATA_SYNC_EP_VERSION . '/',
			'get-version/(?P<access_token>\S+)/',
			[
				'methods' => WP_REST_Server::READABLE,
				'args'    => [
					'access_token' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'access_key' ]
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

	public function request() {

		$response = WP_DATA_SYNC_VERSION;

		return rest_ensure_response( $response );

	}

}