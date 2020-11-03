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

abstract class WC_Order_Data {

	/**
	 * Order array.
	 *
	 * @return array
	 */

	public function order() {

		$_order = [];

		if ( $order = wc_get_order( $this->order_id ) ) {

			$_order          = $order->get_data();
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