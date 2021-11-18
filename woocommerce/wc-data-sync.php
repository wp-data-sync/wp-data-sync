<?php
/**
 * WooCommerce WP Data Sync
 *
 * Setup WP Data Sync for WooCommerce
 *
 * @since   1.0.1
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\Woo;

use WP_DataSync\App\Log;
use WP_DataSync\App\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Used to handle WooCommerce integration versions.
define( 'WCDSYNC_VERSION', '2.0.0' );
define( 'WCDSYNC_ORDER_SYNC_STATUS', '_wpds_order_synced' );

// Load WooCommerce scripts
foreach ( glob( plugin_dir_path( __FILE__ ) . 'includes/**/*.php' ) as $file ) {
	require_once $file;
}

/**
 * Register REST API Routes.
 */

add_action( 'rest_api_init', function() {

	if ( Settings::is_checked( 'wp_data_sync_order_sync_allowed' ) ) {
		WC_Order_DataRequest::instance()->register_route();
	}

} );

/**
 * Process WooCommerce.
 */

add_action( 'wp_data_sync_after_process', function ( $post_id, $data_sync ) {

	if ( 'product' === $data_sync->get_post_type() ) {
		$wc_product_data_sync = WC_Product_DataSync::instance();
		$wc_product_data_sync->set_product_id( $post_id );
		$wc_product_data_sync->set_data_sync( $data_sync );
		$wc_product_data_sync->wc_process();
	}

}, 10, 2 );

/**
 * Process WooCommece Cross Sells.
 */

add_action( 'wp_data_sync_integration_woo_cross_sells', function( $product_id, $values ) {

	$values['product_id'] = $product_id;
	$values['type']       = '_crosssell_ids';

	Log::write( 'product-sells', $values, 'Cross sells' );

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
	$values['type']       = '_upsell_ids';

	Log::write( 'product-sells', $values, 'Up sells' );

	$product_sells = WC_Product_Sells::instance();

	if ( $product_sells->set_properties( $values ) ) {
		$product_sells->save();
	}

}, 10, 2 );

/**
 * WooCommerce ItemRequest
 */

add_filter( 'wp_data_sync_item_request', function( $item_data, $item_id, $item_request ) {

	$post_type = $item_request->get_post_type();

	if ( 'product' === $post_type ) {
		return WC_Product_ItemRequest::instance()->wc_process( $item_data, $item_id, $item_request );
	}

	return $item_data;

}, 10, 3 );
