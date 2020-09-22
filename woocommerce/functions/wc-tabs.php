<?php
/**
 * Admin Tabs
 *
 * Add admin tabs to the WP Data Sync admin page.
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\Woo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_data_sync_admin_tabs', function( $tabs ) {

	$tabs = array_merge( $tabs, [
		'woocommerce' => [
			'label' => __( 'WooCommerce' ),
			'id'    => 'woocommerce'
		],
		'sync_orders' => [
			'label' => __( 'Sync Orders' ),
			'id'    => 'sync_orders'
		]
	] );

	if ( 'checked' !== get_option( 'wp_data_sync_orders' ) ) {
		unset( $tabs['sync_orders'] );
	}

	return $tabs;

} );