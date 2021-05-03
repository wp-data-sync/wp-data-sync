<?php
/**
 * Stage Order Event.
 *
 * Stage order ID with WP Data Sync API.
 *
 * @since   1.4.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\Woo;

add_action( 'wp_data_sync_stage_order_event', function() {

	if ( ! Settings::is_checked( 'wp_data_sync_order_sync_active' ) ) {
		return;
	}

	$stage_order = WC_Order_StageOrder::instance();

	if ( $order_id = $stage_order->get() ) {

		if ( $stage_order->push( $order_id ) ) {
			$stage_order->delete( $order_id );
		}

	}
	else {
		// If no more orders to stage.
		// Unset stage existing orders.
		// This will trigger truncate table and delete cron event.
		update_option( 'wp_data_sync_order_sync_existing', '' );
	}

} );