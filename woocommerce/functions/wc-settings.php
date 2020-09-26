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
			2 => (object) [
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
			],
			4 => (object) [
				'key' 		=> 'wp_data_sync_orders',
				'label'		=> __( 'Sync Orders', 'wp-data-sync' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'checkbox',
					'selected'          => get_option( 'wp_data_sync_orders' ),
					'name'              => 'wp_data_sync_orders',
					'class'             => 'sync-orders',
					'value'             => 'checked',
					'info'              => __( 'Sync order details using the WP Data Sync API.')
				]
			]
		],
		'sync_orders' => [
			0 => (object) [
				'key'      => 'wp_data_sync_existingl_orders',
				'label'    => __( 'Sync all existing orders:', 'wp-data-sync' ),
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
			],
			1 => (object) [
				'key' 		=> 'wp_data_sync_order_sync_on_status',
				'label'		=> '',
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => [ $_settings, 'sanitize_array' ],
					'heading'            => __( 'Sync order when status is:', 'wp-data-sync' ),
					'basename'          => 'checkboxes',
					'type'		        => '',
					'class'		        => 'this-class',
					'placeholder'       => '',
					'values'            => get_option( 'wp_data_sync_order_sync_on_status', [] ),
					'options'           => [
						0 => [
							'value' => 'pending',
							'id'    => 'order-pending',
							'class' => 'order-pending',
							'label' => __( 'Pending', 'woocommerce' )
						],
						1 => [
							'value' => 'processing',
							'id'    => 'order-processing',
							'class' => 'order-processing',
							'label' => __( 'Processing', 'woocommerce' )
						],
						2 => [
							'value' => 'on-hold',
							'id'    => 'order-on-hold',
							'class' => 'order-on-hold',
							'label' => __( 'On Hold', 'woocommerce' )
						],
						3 => [
							'value' => 'completed',
							'id'    => 'order-completed',
							'class' => 'order-completed',
							'label' => __( 'Completed', 'woocommerce' )
						],
						4 => [
							'value' => 'cancelled',
							'id'    => 'order-cancelled',
							'class' => 'order-cancelled',
							'label' => __( 'Cancelled', 'woocommerce' )
						],
						5 => [
							'value' => 'refunded',
							'id'    => 'order-refunded',
							'class' => 'order-refunded',
							'label' => __( 'Refunded', 'woocommerce' )
						]
					]
				]
			]
		]
	] );

	if ( 'checked' !== get_option( 'wp_data_sync_orders' ) ) {
		unset( $settings['sync_orders'] );
	}

	return $settings;

}, 10, 2 );
