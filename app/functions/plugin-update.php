<?php
/**
 * Plugin Update
 *
 * Run functions on plugin update.
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
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

	if ( WP_DATA_SYNC_VERSION !== get_option( 'WP_DATA_SYNC_VERSION' ) ) {

		ItemRequest::create_table();

		update_option( 'WP_DATA_SYNC_VERSION', WP_DATA_SYNC_VERSION );

	}

}