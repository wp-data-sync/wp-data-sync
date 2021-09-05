<?php
/**
 * Exclude Data Types
 *
 * Exclude data types from item request.
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\Woo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'wp_data_sync_item_request_exclude_data_types', function( $options ) {

	$options = array_merge( $options, apply_filters( 'wc_data_sync_item_request_exclude_data_types', [
		'gallery_images' => __( 'Galley Images', 'woocommerce' ),
		'attributes'     => __( 'Attributes', 'woocommerce' ),
		'variations'     => __( 'Variations', 'woocommerce' )
	] ) );

	return $options;

}, 10, 1 );
