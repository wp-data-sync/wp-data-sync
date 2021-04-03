<?php
/**
 * WC_Order_Data
 *
 * Create an order data array.
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\Woo;

use WP_DataSync\App\Log;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Order_Data {

	/**
	 * @var WC_Order_Data
	 */

	public static $instance;

	/**
	 * WC_Order_Data constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * @return WC_Order_Data
	 */

	public static function instance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Get Order Data.
	 *
	 * @return array
	 */

	public function get( $order_id ) {

		$_order = [];

		if ( $order = wc_get_order( $order_id ) ) {

			$_order          = $order->get_data();
			$_order['meta']  = $order->get_meta();
			$_order['items'] = $this->get_items( $order );

		}

		return $_order;

	}
	/**
	 * @param $order \WC_Order
	 *
	 * @return array
	 */

	public function get_items( $order ) {

		$order_items = [];

		foreach ( $order->get_items() as $i => $item ) {

			$order_items[ $i ]             = $item->get_data();
			$order_items[ $i ]['itemmeta'] = $item->get_meta_data();

			if ( $product = wc_get_product( $item->get_product_id() ) ) {
				$order_items[ $i ]['sku'] = $product->get_sku();
			}
			else {
				$order_items[ $i ]['sku'] = 'NA';
			}

		}

		return apply_filters( 'wp_data_sync_order_items', $order_items, $order );

	}

}