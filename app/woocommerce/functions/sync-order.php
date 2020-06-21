<?php
/**
 * Sync Order
 *
 * Sync order details with WP Data Sync API.
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

add_action( 'woocommerce_order_status_completed', function( $order_id ) {

	if ( get_post_meta( $order_id, 'wp_data_sync_completed_order_synced', TRUE ) ) {
		return;
	}

	if ( 'checked' !== get_option( 'wp_data_sync_order_details' ) ) {
		return;
	}

	if ( ! $api_url = get_option( 'wp_data_sync_api_url' ) ) {
		return;
	}

	if ( ! $access_key = get_option( 'wp_data_sync_access_key' ) ) {
		return;
	}

	if ( ! $private_key = get_option( 'wp_data_sync_private_key' ) ) {
		return;
	}

	$order    = wc_get_order( $order_id );
	$endpoint = "$api_url/wp-json/1.0/completed-order/$access_key/$order_id/";
	$args     = [
		'timeout'	=> 10,
		'sslverify' => TRUE,
		'body'      => json_encode( $order ),
		'headers'	=>  [
			'Accept' 		 => 'application/json',
			'Authentication' => $private_key
		]
	];

	$response = wp_remote_post( $endpoint, $args );

	if ( ! is_wp_error( $response ) ) {
		add_post_meta( $order_id, 'wp_data_sync_completed_order_synced', 1 );
	}

	if ( class_exists( 'Log' ) ) {
		Log::write( 'pos-completed-order', $response );
	}

}, 10, 1 );
