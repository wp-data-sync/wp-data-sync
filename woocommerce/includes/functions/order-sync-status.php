<?php
/**
 * WooCommerce Order Sync Status
 *
 * @since   3.3.6
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\Woo;

use WP_DataSync\App\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Delete order sync status meta data when order status changed.
 *
 * @param int    $order_id
 * @param string $old_status
 * @param string $new_status
 *
 * @return void
 */
add_action( 'woocommerce_order_status_changed', function( int $order_id, string $old_status, string $new_status ): void {

    if ( $order = wc_get_order( $order_id ) ) {

        $statuses = get_option( 'wp_data_sync_allowed_order_status', [] );

        if ( in_array( "wc-$new_status", $statuses ) ) {
            $order->delete_meta_data( WCDSYNC_ORDER_SYNC_STATUS );
            $order->save();
        }

    }

}, 10, 3 );
