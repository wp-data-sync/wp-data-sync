<?php
/**
 * Stage Order
 *
 * Stage order ID with WP Data Sync API.
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

add_action( 'woocommerce_order_status_changed', function( $order_id, $status_from, $status_to ) {

	if ( 'checked' !== get_option( 'wp_data_sync_orders' ) ) {
		return;
	}

	if ( ! in_array( $status_to, get_option( 'wp_data_sync_order_sync_on_status', [] ) ) ) {
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

	Log::write( 'stage-order', $order_id );

	$endpoint = join( '/', [
		untrailingslashit( $api_url ),
		'wp-json',
		'1.0',
		'stage-order',
		$access_key,
		$order_id
	] );

	$response = wp_remote_post( $endpoint, [
		'timeout'	=> 5,
		'sslverify' => TRUE,
		// Wait for response only if logging is active.
		'blocking'  => Log::is_active(),
		'body'      => $order_id,
		'headers'	=>  [
			'Accept' 		 => 'application/json',
			'Authentication' => $private_key
		]
	] );

	Log::write( 'stage-order', $response );

}, 10, 3 );