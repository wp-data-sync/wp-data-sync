<?php
/**
 * Add Custom Fields to Products
 *
 * Add custom fields to product data.
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\Woo\App;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add product data fields.
 */

add_action( 'woocommerce_product_options_general_product_data', function() {

	woocommerce_wp_text_input([
		'id'          => '_upc',
		'label'       => __( 'UPC', 'wp-data-sync' ),
		'desc_tip'    => true,
		'description' => __( 'Universal Product Code', 'wp-data-sync' )
	]);

	woocommerce_wp_text_input([
		'id'          => '_mpn',
		'label'       => __( 'MPN', 'wp-data-sync' ),
		'desc_tip'    => true,
		'description' => __( 'Manufacturer Part Number', 'wp-data-sync' )
	]);

	woocommerce_wp_text_input([
		'id'          => '_gtin8',
		'label'       => __( 'GTIN', 'wp-data-sync' ),
		'desc_tip'    => true,
		'description' => __( 'Global Trade Item Number', 'wp-data-sync' )
	]);

	woocommerce_wp_text_input([
		'id'          => '_isbn',
		'label'       => __( 'ISBN', 'wp-data-sync' ),
		'desc_tip'    => true,
		'description' => __( 'International Standard Book Number', 'wp-data-sync' )
	]);

} );

/**
 * Save product data fields.
 */

add_action('woocommerce_process_product_meta', function( $product_id ) {

	$product = wc_get_product( $product_id );

	$_upc = isset( $_POST['_upc'] ) ? sanitize_text_field( wp_unslash( $_POST['_upc'] ) ) : '';
	$product->update_meta_data( '_upc', $_upc );

	$_mpn = isset( $_POST['_mpn'] ) ? sanitize_text_field( wp_unslash( $_POST['_mpn'] ) ) : '';
	$product->update_meta_data( '_mpn', $_mpn );

	$_gtin8 = isset( $_POST['_gtin8'] ) ? sanitize_text_field( wp_unslash( $_POST['_gtin8'] ) ) : '';
	$product->update_meta_data( '_gtin8', $_gtin8 );

	$_isbn = isset( $_POST['_isbn'] ) ? sanitize_text_field( wp_unslash( $_POST['_isbn'] ) ) : '';
	$product->update_meta_data( '_isbn', $_isbn );

	$product->save();

});

/**
 * Add variable product data fields.
 */

add_action( 'woocommerce_variation_options_pricing', function( $loop, $variation_data, $variation ) {

	woocommerce_wp_text_input([
		'id'          => "_upc[$loop]",
		'label'       => __( 'UPC', 'wp-data-sync' ),
		'desc_tip'    => true,
		'wrapper_class' => 'form-row form-row-first',
		'description' => __( 'Universal Product Code', 'wp-data-sync' ),
		'value'       => get_post_meta( $variation->ID, '_upc', true )
	]);

	woocommerce_wp_text_input([
		'id'          => "_mpn[$loop]",
		'label'       => __( 'MPN', 'wp-data-sync' ),
		'desc_tip'    => true,
		'wrapper_class' => 'form-row form-row-last',
		'description' => __( 'Manufacturer Part Number', 'wp-data-sync' ),
		'value'       => get_post_meta( $variation->ID, '_mpn', true )
	]);

	woocommerce_wp_text_input([
		'id'          => "_gtin8[$loop]",
		'label'       => __( 'GTIN', 'wp-data-sync' ),
		'desc_tip'    => true,
		'wrapper_class' => 'form-row form-row-first',
		'description' => __( 'Global Trade Item Number', 'wp-data-sync' ),
		'value'       => get_post_meta( $variation->ID, '_gtin8', true )
	]);

	woocommerce_wp_text_input([
		'id'          => "_isbn[$loop]",
		'label'       => __( 'ISBN', 'wp-data-sync' ),
		'desc_tip'    => true,
		'wrapper_class' => 'form-row form-row-last',
		'description' => __( 'International Standard Book Number', 'wp-data-sync' ),
		'value'       => get_post_meta( $variation->ID, '_isbn', true )
	]);

}, 10, 3 );

/**
 * Save variable product data fields.
 */

add_action( 'woocommerce_save_product_variation', function( $variation_id, $i ) {

	$keys = [ '_upc', '_mpn', '_gtin8', '_isbn' ];

	foreach ( $keys as $key ) {

		if ( isset( $_POST[ $key ][ $i ] ) ) {

			$value = sanitize_text_field( wp_unslash( $_POST[ $key ][ $i ] ) );

			update_post_meta( $variation_id, $key, esc_attr( $value ) );

		}

	}

}, 10, 2 );
