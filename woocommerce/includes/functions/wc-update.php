<?php
/**
 * WooCommerce Update
 *
 * Run functions on WC integration update.
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\Woo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Run plugin update functions.
 */

add_action( 'init', function() {

	if ( WCDSYNC_VERSION !== get_option( 'WCDSYNC_VERSION' ) ) {

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        WC_Product_Sells::instance()->create_table();

        as_unschedule_all_actions( 'wp_data_sync_process_product_sells_action' );
        as_schedule_recurring_action(
            time(),
            15,
            'wp_data_sync_process_product_sells_action',
            [],
            'wp_data_sync'
        );

        wc_cleanup_actions();

        update_option( 'WCDSYNC_VERSION', WCDSYNC_VERSION );

	}

} );

function wc_cleanup_actions() {
    global $wpdb;

    $wpdb->delete(
        $wpdb->prefix . 'actionscheduler_actions',
        [ 'hook' => 'wp_data_sync_schedule_product_sells_events' ]
    );

    $wpdb->delete(
        $wpdb->prefix . 'actionscheduler_actions',
        [ 'hook' => 'wp_data_sync_process_product_sells' ]
    );

}
