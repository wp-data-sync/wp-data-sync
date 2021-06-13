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

namespace WP_DataSync\Woo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'wp_data_sync_settings', function( $settings, $_settings ) {

	$settings = array_merge( $settings, [
		'woocommerce' => [
			0 => [
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
						'visible'                                  => __( 'Shop and search results', 'woocommerce' ),
						'exclude-from-search'                      => __( 'Shop only', 'woocommerce' ),
						'exclude-from-catalog'                     => __( 'Search results only', 'woocommerce' ),
						'exclude-from-catalog,exclude-from-search' => __( 'Hidden', 'woocommerce' )
					]
				]
			],
			1 => [
				'key' 		=> 'wp_data_sync_process_cross_sells',
				'label'		=> __( 'Process Cross Sells', 'wp-data-sync' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'checkbox',
					'type'		        => '',
					'class'		        => '',
					'placeholder'       => '',
					'info'              => __( 'This relates the IDs from your data source with the IDs from your website. Please note, if the related product does not exist, this system will relate the product when it is created in the data sync.' )
				]
			],
			2 => [
				'key' 		=> 'wp_data_sync_process_up_sells',
				'label'		=> __( 'Process Up Sells', 'wp-data-sync' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'checkbox',
					'type'		        => '',
					'class'		        => '',
					'placeholder'       => '',
					'info'              => __( 'This relates the IDs from your data source with the IDs from your website. Please note, if the related product does not exist, this system will relate the product when it is created in the data sync.' )
				]
			]
		],
		'orders' => [
			0 => [
				'key' 		=> 'wp_data_sync_order_sync_active',
				'label'		=> __( 'Activate Order Sync', 'wp-data-sync' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'checkbox',
					'tyoe'              => '',
					'class'             => 'sync-orders',
					'placeholder'       => '',
					'info'              => __( 'Sync order details using the WP Data Sync API.')
				]
			],
			1 => [
				'key'      => 'wp_data_sync_order_webhook_url',
				'label'    => __( 'Orders Webhook URL', 'wp-data-sync' ),
				'callback' => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'text-input',
					'type'		        => 'text',
					'class'		        => 'regular-text',
					'placeholder'       => ''
				]
			],
			2 => [
				'key' 		=> 'wp_data_sync_order_sync_on_status',
				'label'		=> __( 'Sync order when status is', 'wp-data-sync' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => [ $_settings, 'sanitize_array' ],
					'basename'          => 'select-multiple',
					'name'              => 'wp_data_sync_order_sync_on_status',
					'type'		        => '',
					'class'		        => 'wc-enhanced-select regular-text',
					'placeholder'       => '',
					'selected'          => get_option( 'wp_data_sync_order_sync_on_status', [] ),
					'options'            => [
						'pending'    => __( 'Pending', 'woocommerce' ),
						'processing' => __( 'Processing', 'woocommerce' ),
						'on-hold'    => __( 'On Hold', 'woocommerce' ),
						'completed'  => __( 'Completed', 'woocommerce' ),
						'refunded'   => __( 'Refunded', 'woocommerce' )
					]
				]
			],
			3 => [
				'key'      => 'wp_data_sync_order_sync_existing',
				'label'    => __( 'Sync all existing orders now', 'wp-data-sync' ),
				'callback' => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'checkbox',
					'type'		        => '',
					'class'		        => '',
					'placeholder'       => '',
					'msg'               => WC_Order_StageOrder::count_msg(),
					'info'              => __( 'Schedule a task to sync existing orders in small batches. This setting will reset itself when all orders have been processed.', 'wp-data-sync' )
				]
			]
		]
	] );

	return $settings;

}, 10, 2 );
