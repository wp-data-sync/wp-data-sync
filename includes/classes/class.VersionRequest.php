<?php
/**
 * Version Request
 *
 * Request plugin version.
 *
 * @since   1.4.2
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

use WP_Error;
use WP_REST_Server;
use WP_HTTP_Response;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VersionRequest extends Request {

	/**
	 * @var string
	 */

	protected string $access_token_key = 'wp_data_sync_access_token';

	/**
	 * @var string
	 */

	protected string $private_token_key = 'wp_data_sync_private_token';

	/**
	 * @var string
	 */

	protected string $permissions_key = 'wp_data_sync_allowed';

	/**
	 * Instance.
	 *
	 * @return VersionRequest
	 */

	public static function instance(): VersionRequest {
		return new self();
	}

	/**
	 * Register the route.
	 *
	 * @since 1.0.0
	 *        2.0.0 Require version as any 2-character string
     *
     * @return void
	 */

	public function register_route(): void {

		register_rest_route(
			$this->namespace,
			"/$this->ep_version/get-version/(?P<access_token>\S+)/(?P<cache_buster>\S+)/",
			[
				'methods' => WP_REST_Server::READABLE,
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
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */

	public function request() {

        Log::write();

		return rest_ensure_response( [
			'version' => WPDSYNC_VERSION
		] );

	}

}
