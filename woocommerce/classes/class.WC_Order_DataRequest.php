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

use WP_DataSync\App\Core;
use WP_DataSync\App\Log;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Order_DataRequest extends Core {

	/**
	 * @var WC_Order_DataRequest
	 */

	public static $instance;

	/**
	 * @var integer
	 */

	private $order_id;

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
			'wp-data-sync/1.0/',
			'order-request/(?P<access_token>\S+)/(?P<order_id>\d+)/',
			[
				'methods' => WP_REST_Server::READABLE,
				'args'    => [
					'access_token' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'access_key' ]
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

		$responce = [];

		if ( $order = wc_get_order( $this->order_id ) ) {

			$response          = $order->get_data();
			$response['items'] = $this->get_items( $order );

		}

		Log::write( 'order-request', $response );

		return rest_ensure_response( $response );

	}

	/**
	 * @param $order \WC_Order
	 *
	 * @return array
	 */

	public function get_items( $order ) {

		$order_items = [];
		$i = 0;

		foreach ( $order->get_items() as $item ) {

			$product = wc_get_product( $item->get_product_id() );

			$order_items[ $i ]        = $item->get_data();
			$order_items[ $i ]['sku'] = $product->get_sku();

			$i++;

		}

		return apply_filters( 'wp_data_sync_order_items', $order_items, $order );

	}

}