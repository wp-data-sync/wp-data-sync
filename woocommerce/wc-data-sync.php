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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Used to handle WooCommerce integration versions.
define( 'WC_DATA_SYNC_VERSION', '1.0' );

// Load WooCommerce scripts
foreach ( glob( plugin_dir_path( __FILE__ ) . '**/*.php' ) as $file ) {
	require_once $file;
}

/**
 * Process WooCommerce.
 */

add_action( 'wp_data_sync_after_process', function ( $post_id, $data_sync ) {
	WC_Product_DataSync::instance()->wc_process( $post_id, $data_sync );
}, 10, 2 );

add_filter( 'wp_data_sync_item_request', function( $item_data, $item_id, $item_request ) {
	return WC_Product_ItemRequest::instance()->wc_process( $item_data, $item_id, $item_request );
}, 10, 3 );
