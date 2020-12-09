<?php
/**
 * WC_Order_DataRequest
 *
 * Request WooCommerce product data
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\Woo;

use WP_DataSync\App\Access;
use WP_DataSync\App\Log;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Order_DataRequest extends Access {

	/**
	 * @var string
	 */

	protected $access_token_key = 'wp_data_sync_access_token';

	/**
	 * @var string
	 */

	protected $private_token_key = 'wp_data_sync_private_token';

	/**
	 * @var WC_Order_DataRequest
	 */

	public static $instance;

	/**
	 * @var integer
	 */

	protected $order_id;

	/**
	 * WC_Order_DataRequest constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * @return WC_Order_DataRequest
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
			'wp-data-sync/' . WP_DATA_SYNC_EP_VERSION,
			'/order-request/(?P<access_token>\S+)/(?P<order_id>\d+)/',
			[
				'methods' => WP_REST_Server::READABLE,
				'args'    => [
					'access_token' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'access_token' ]
					],
					'order_id' => [
						'sanitize_callback' => 'intval',
						'validate_callback' => [ $this, 'order_id' ],
					]
				],
				'permission_callback' => [ $this, 'access' ],
				'callback'            => [ $this, 'request' ],
			]
		);

	}

	/**
	 * Order ID.
	 *
	 * @param $order_id
	 *
	 * @return bool
	 */

	public function order_id( $order_id ) {

		$order_id = intval( $order_id );

		if ( is_int( $order_id ) && 0 < $order_id ) {
			$this->order_id = $order_id;
			return TRUE;
		}

		return FALSE;

	}

	/**
	 * Process Request.
	 */

	public function request() {

		$order_data = WC_Order_Data::instance();

		$responce = $order_data->get( $this->order_id );

		Log::write( 'order-request', $response );

		return rest_ensure_response( $response );

	}

}
