<?php
/**
 * Reset Sync
 *
 * Reset sync for shop orders
 *
 * @since   2.7.10
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\Woo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add Reset Order Sync Status Action
 *
 * @param array $actions
 *
 * @return array
 */

add_filter( 'bulk_actions-edit-shop_order', function( $actions ) {

    $actions['reset_order_sync_status'] = __( 'Reset Sync Status', 'wp-data-sync' );

    return $actions;

}, 20, 1 );

/**
 * Handle Order Sync Status Bulk Action
 *
 * @param string $redirect_to
 * @param string $action
 * @param array  $order_ids
 *
 * @return string
 */

add_filter( 'handle_bulk_actions-edit-shop_order', function( $redirect_to, $action, $order_ids ) {

    if ( $action !== 'reset_order_sync_status' ) {
        return $redirect_to;
    }

    foreach ( $order_ids as $order_id ) {
        delete_post_meta( $order_id, WCDSYNC_ORDER_SYNC_STATUS );
    }

    return $redirect_to;

}, 10, 3 );
