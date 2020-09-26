<?php
/**
 * Stage Existing Order
 *
 * Stage existing orders to be synced with WP Data Sync API.
 *
 * @since   1.4.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\Woo;

add_action( 'update_option_wp_data_sync_existingl_orders', function( $old_value, $value, $option ) {

	$stage_order = WC_Order_StageOrder::instance();

	if ( 'checked' === $value ) {

		$stage_order->set_cron();

		$orders = wc_get_orders( [
			'limit'  => -1
		] );

		foreach ( $orders as $order ) {
			$stage_order->set( $order );
		}

	}
	else {

		$stage_order->delete_cron();
		$stage_order->truncate_table();

	}

}, 10, 3 );