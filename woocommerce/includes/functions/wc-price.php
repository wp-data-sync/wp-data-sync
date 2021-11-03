<?php
/**
 * WC Price
 *
 * Set the WooCommerce prices.
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\Woo;

use WP_DataSync\App\DataSync;
use WP_DataSync\App\Log;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set the WooCommerce prices.
 *
 * @param int      $product_id
 * @param array    $post_meta
 * @param DataSync $data_sync
 *
 * @return void
 */

add_action( 'wp_data_sync_post_meta', function( $product_id, $post_meta, $data_sync ) {

	if ( ! is_array( $post_meta ) || empty( $post_meta ) ) {
		return;
	}

	extract( $post_meta );

	if ( isset( $_regular_price ) ) {

		LOg::write( 'product-price', "Product ID: $product_id Regular Price: $_regular_price" );

		$data_sync->set_post_meta( [ '_regular_price' => $_regular_price ] );
		$data_sync->post_meta();

		if ( ! empty( $_regular_price ) ) {
			$data_sync->set_post_meta( [ '_price' => $_regular_price ] );
			$data_sync->post_meta();
		}

	}

	if ( isset( $_sale_price ) ) {

		LOg::write( 'product-price', "Product ID: $product_id Sale Price: $_sale_price" );

		$data_sync->set_post_meta( [ '_sale_price' => $_sale_price ] );
		$data_sync->post_meta();

		if ( ! empty( $_sale_price ) ) {
			$data_sync->set_post_meta( [ '_price' => $_sale_price ] );
			$data_sync->post_meta();
		}

	}

	if ( isset( $_price ) ) {

		LOg::write( 'product-price', "Product ID: $product_id Price: $_price" );

		if ( ! empty( $_price ) ) {
			$data_sync->set_post_meta( [ '_price' => $_price ] );
			$data_sync->post_meta();
		}

	}

}, 10, 3 );
