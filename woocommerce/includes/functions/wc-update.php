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

add_action( 'init', 'WP_DataSync\Woo\wc_update' );
add_action( 'admin_init', 'WP_DataSync\Woo\wc_update' );

/**
 * Run plugin update functions.
 */

function wc_update() {

	if ( WCDSYNC_VERSION !== get_option( 'WCDSYNC_VERSION' ) ) {

		WC_Product_Sells::create_table();

		update_option( 'WCDSYNC_VERSION', WCDSYNC_VERSION );

	}

}
