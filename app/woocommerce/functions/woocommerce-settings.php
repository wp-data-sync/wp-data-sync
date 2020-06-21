<?php
/**
 * Settings
 *
 * Plugin settings
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

add_filter( 'wp_data_sync_settings', function( $settings ) {

	$settings = array_merge( $settings, [
		'woocommerce' => [
			0 => (object) [
				'key' 		=> 'wp_data_sync_product_visibility',
				'label'		=> __( 'Default Product Visibility', 'wp-data-sync' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'select',
					'selected'          => get_option( 'wp_data_sync_product_visibility' ),
					'name'              => 'wp_data_sync_product_visibility',
					'class'             => 'product-visibility widefat',
					'values'            => [
						'null'                                     => __( 'Shop and search results', 'woocommerce' ),
						'exclude-from-search'                      => __( 'Shop only', 'woocommerce' ),
						'exclude-from-catalog'                     => __( 'Search results only', 'woocommerce' ),
						'exclude-from-catalog,exclude-from-search' => __( 'Hidden', 'woocommerce' )
					]
				]
			],
			1 => (object) [
				'key' 		=> 'wp_data_sync_order_details',
				'label'		=> __( 'Sync Order Details (Coming Soon)', 'wp-data-sync' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'checkbox',
					'selected'          => get_option( 'wp_data_sync_order_details' ),
					'name'              => 'wp_data_sync_order_details',
					'class'             => 'sync-orders disabled',
					'value'             => 'checked'
				]
			]
		]
	] );

	return $settings;

} );
