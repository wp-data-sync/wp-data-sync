<?php
/**
 * WooCommerce Price
 *
 * Set WooCommerce price.
 *
 * @since   2.1.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\Woo;

use WP_DataSync\App\DataSync;
use WP_DataSync\App\Log;
use WC_Cache_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set the product price.
 *
 * @param int      $product_id
 * @param DataSync $data_sync
 *
 * @return viod
 */

add_action( 'wp_data_sync_after_process', function( $product_id, $data_sync ) {

	$post_meta = $data_sync->get_post_meta();

	if ( ! isset( $post_meta['_regular_price'] ) && ! isset( $post_meta['_sale_price'] ) ) {
		return;
	}

	if ( $product = wc_get_product( $product_id ) ) {

		if ( $product->is_on_sale() ) {
			$current_price = $product->get_sale_price();
		}
		else {
			$current_price = $product->get_regular_price();
		}

		Log::write( 'set-price', $current_price, 'Current Price' );

		update_post_meta( $product_id, '_price', $current_price );

		WC_Cache_Helper::get_transient_version( 'product', true );
		delete_transient( 'wc_products_onsale' );

	}

}, 999, 2 );
