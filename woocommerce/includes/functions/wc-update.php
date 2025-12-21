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

		update_option( 'WCDSYNC_VERSION', WCDSYNC_VERSION );

	}

} );
