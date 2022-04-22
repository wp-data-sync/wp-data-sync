<?php
/**
 * WooCommerce Order Sync Status Admin Column
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\Woo;

use WP_DataSync\App\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add order sync status admin column.
 *
 * @param array $columns
 */

add_filter( 'manage_edit-shop_order_columns', function( $columns ) {

	if ( Settings::is_checked( 'wp_data_sync_show_order_sync_status_admin_column' ) ) {
		$columns['wpds_sync_status'] = __( 'Sync Status', 'wp-data-sync' );
	}

	return $columns;

}, 99 );

/**
 * Display contents of the order sync status admin column.
 *
 * @param string $column
 * @param int    $product_id
 */

add_action( 'manage_shop_order_posts_custom_column', function( $column, $product_id ) {

	if ( 'wpds_sync_status' === $column ) {

		if ( $value = get_post_meta( $product_id, WCDSYNC_ORDER_SYNC_STATUS, true ) ) {

			if ( 'no' !== $value ) {
				printf( '<span class="wpds-order-export synced">%s</span>', esc_html( '&#10003;' ) );

				return;
			}

		}

		printf( '<span class="wpds-order-export">%s</span>', esc_html( '&#10005;' ) );

	}

}, 10, 2 );
