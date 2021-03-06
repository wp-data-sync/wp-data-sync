<?php
/**
 * Settings
 *
 * Plugin settings
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
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
				'key' 		=> 'wp_data_sync_order_sync_allowed',
				'label'		=> __( 'Allow Order Sync', 'wp-data-sync' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'checkbox',
					'tyoe'              => '',
					'class'             => 'sync-orders',
					'placeholder'       => '',
					'info'              => __( 'Allow order details to sync with the WP Data Sync API.')
				]
			],
			1 => [
				'key' 		=> 'wp_data_sync_allowed_order_status',
				'label'		=> __( 'Allowed Order Status', 'wp-data-sync' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => [ $_settings, 'sanitize_array' ],
					'basename'          => 'select-multiple',
					'name'              => 'wp_data_sync_allowed_order_status',
					'type'		        => '',
					'class'		        => 'wc-enhanced-select regular-text',
					'placeholder'       => '',
					'selected'          => get_option( 'wp_data_sync_allowed_order_status', [] ),
					'options'           => apply_filters( 'wp_data_sync_allowed_order_status', [
						'wc-pending'    => __( 'Pending', 'woocommerce' ),
						'wc-processing' => __( 'Processing', 'woocommerce' ),
						'wc-on-hold'    => __( 'On Hold', 'woocommerce' ),
						'wc-completed'  => __( 'Completed', 'woocommerce' ),
						'wc-refunded'   => __( 'Refunded', 'woocommerce' )
					] )
				]
			],
			2 => [
				'key' 		=> 'wp_data_sync_show_order_sync_status_admin_column',
				'label'		=> __( 'Show Order Sync Status Admin Column', 'wp-data-sync' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'checkbox',
					'tyoe'              => '',
					'class'             => 'show-admin-column',
					'placeholder'       => '',
					'info'              => __( 'Show admin column for order export status on Orders list.')
				]
			],
		]
	] );

	return $settings;

}, 10, 2 );
