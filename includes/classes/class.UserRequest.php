<?php
/**
 * UserRequest
 *
 * Process the DataSync User Request.
 *
 * @since   2.0.0
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

class UserRequest extends Request {

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
	 * @var string
	 */

	protected $log_key = 'sync-request-user';

	/**
	 * @var UserRequest
	 */

	public static $instance;

	/**
	 * UserRequest constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * Instance.
	 *
	 * @return UserRequest
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
			'/user/(?P<access_token>\S+)/(?P<cache_buster>\S+)/',
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
				'callback'            => [ $this, 'process' ],
			]
		);

	}

	/**
	 * Process the request.
	 *
	 * @return mixed|\WP_REST_Response
	 */

	public function process() {

		$start_request = microtime();
		$response      = [];
		$user_sync     = UserSync::instance();
		$user_data     = $this->request_data();

		$user_sync->set_properties( $user_data );

		$response['result'] = $user_sync->process();
		$response['request_time'] = microtime() - $start_request;

		Log::write( 'user-request-response', $response );

		return rest_ensure_response( $response );

	}

}
