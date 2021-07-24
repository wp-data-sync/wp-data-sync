<?php
/**
 * WC_Order_Data
 *
 * Create an order data array.
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
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
	 * Get order array.
	 *
	 * @param \WC_Order $order
	 *
	 * @return mixed
	 */

	public function get( $order ) {

		$_order                  = $order->get_data();
		$_order['meta_data']     = $this->format_meta( $order );
		$_order['items']         = $this->get_items( $order );
		$_order['shipping_data'] = $this->get_shipping_data( $order );

		Log::write( 'order', $_order );

		return apply_filters( 'wp_data_sync_order_data', $_order, $order );

	}

	/**
	 * @param $order \WC_Order
	 *
	 * @return array
	 */

	public function get_items( $order ) {

		$order_items = [];

		foreach ( $order->get_items() as $i => $item ) {

			$order_items[ $i ]              = $item->get_data();
			$order_items[ $i ]['meta_data'] = $this->format_meta( $item );

			if ( $product = wc_get_product( $item->get_product_id() ) ) {
				$order_items[ $i ]['sku'] = $product->get_sku();
			}
			else {
				$order_items[ $i ]['sku'] = 'NA';
			}

		}

		return apply_filters( 'wp_data_sync_order_items', $order_items, $order );

	}

	/**
	 * Foemat meta.
	 *
	 * @param \WC_Order|\WC_Order_Item $order
	 *
	 * @return array
	 */

	public function format_meta( $order ) {

		$meta_data =  $order->get_meta_data();

		if ( ! is_array( $meta_data ) ) {
			return $meta_data;
		}

		$_meta_data = [];

		foreach ( $meta_data as $meta ) {

			$data = $meta->get_data();

			$_meta_data[ $data['key'] ] = $data['value'];

		}

		return apply_filters( 'wp_data_sync_order_meta', $_meta_data, $order );

	}

	/**
	 * Get shipping data.
	 *
	 * @param $order
	 *
	 * @return mixed|array
	 */

	public function get_shipping_data( $order ) {

		$order_shipping = [];

		foreach( $order->get_items( 'shipping' ) as $shipping_item_obj ){
			$order_shipping = $shipping_item_obj->get_data();
			break;
		}

		return apply_filters( 'wp_data_sync_order_shipping', $order_shipping, $order );

	}

}