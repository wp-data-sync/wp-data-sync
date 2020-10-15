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
define( 'WC_DATA_SYNC_VERSION', '1.2' );

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
	WC_Product_DataSync::instance()->wc_process( $post_id, $data_sync );
}, 10, 2 );

add_filter( 'wp_data_sync_item_request', function( $item_data, $item_id, $item_request ) {
	return WC_Product_ItemRequest::instance()->wc_process( $item_data, $item_id, $item_request );
}, 10, 3 );
