<?php
/**
 * Plugin Update
 *
 * Run functions on plugin update.
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'WP_DataSync\App\plugin_update' );
add_action( 'admin_init', 'WP_DataSync\App\plugin_update' );

/**
 * Run plugin update functions.
 */

function plugin_update() {

	if ( WPDSYNC_VERSION !== get_option( 'WPDSYNC_VERSION' ) ) {

		ItemRequest::create_table();

		update_option( 'WPDSYNC_VERSION', WPDSYNC_VERSION );

	}

}