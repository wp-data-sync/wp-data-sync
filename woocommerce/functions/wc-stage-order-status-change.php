<?php
/**
 * Stage Order
 *
 * Stage order ID with WP Data Sync API.
 *
 * @since   1.4.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\Woo;

use WP_DataSync\App\Settings;

add_action( 'woocommerce_order_status_changed', function( $order_id, $status_from, $status_to ) {

	if ( ! Settings::is_checked( 'wp_data_sync_order_sync_active' ) ) {
		return;
	}

	if ( ! in_array( $status_to, get_option( 'wp_data_sync_order_sync_on_status', [] ) ) ) {
		return;
	}

	WC_Order_StageOrder::instance()->push( $order_id );

}, 10, 3 );