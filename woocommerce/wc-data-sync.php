<?php
/**
 * WooCommerce WP Data Sync
 *
 * Setup WP Data Sync for WooCommerce
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\Woo;

use WP_DataSync\App\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Used to handle WooCommerce integration versions.
define( 'WC_DATA_SYNC_VERSION', '1.0.3a' );

// Load WooCommerce scripts
foreach ( glob( plugin_dir_path( __FILE__ ) . '**/*.php' ) as $file ) {
	require_once $file;
}

/**
 * Register REST API Routes.
 */

add_action( 'rest_api_init', function() {

	if ( Settings::is_checked( 'wp_data_sync_orders' ) ) {
		WC_Order_DataRequest::instance()->register_route();
	}

} );

/**
 * Process WooCommerce.
 */

add_action( 'wp_data_sync_after_process', function ( $post_id, $data_sync ) {

	if ( 'product' === $data_sync->get_post_type() ) {
		WC_Product_DataSync::instance()->wc_process( $post_id, $data_sync );
	}

}, 10, 2 );

/**
 * Process WooCommece Cross Sells.
 */

add_action( 'wp_data_sync_integration_woo_cross_sells', function( $product_id, $values ) {

	$values['product_id'] = $product_id;
	$values['type']       = 'cross_sells';

	$product_sells = WC_Product_Sells::instance();

	if ( $product_sells->set_properties( $values ) ) {
		$product_sells->save();
	}

}, 10, 2 );

/**
 * Process WooCommece Cross Sells.
 */

add_action( 'wp_data_sync_integration_woo_up_sells', function( $product_id, $values ) {

	$values['product_id'] = $product_id;
	$values['type']       = 'up_sells';

	$product_sells = WC_Product_Sells::instance();

	if ( $product_sells->set_properties( $values ) ) {
		$product_sells->save();
	}

}, 10, 2 );

/**
 * WooCommerce ItemRequest
 */

add_filter( 'wp_data_sync_item_request', function( $item_data, $item_id, $item_request ) {

	if ( 'product' === $item_request->get_post_type() ) {
		return WC_Product_ItemRequest::instance()->wc_process( $item_data, $item_id, $item_request );
	}

	return $item_data;

}, 10, 3 );
