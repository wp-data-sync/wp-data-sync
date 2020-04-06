<?php
/**
 * Auto Update
 *
 * Auto update this plugin
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

/**
 * Auto Update Plugin
 *
 * @param $update
 * @param $item
 *
 * @return bool
 */

add_filter( 'auto_update_plugin', 'WP_DataSync\App\auto_update_plugin', 10, 2 );

function auto_update_plugin( $update, $item ) {

	if ( isset( $item->slug ) && 'wp-data-sync' === $item->slug ) {

		if ( 'checked' === get_option( 'wp_data_sync_auto_update' ) ) {
			return TRUE;
		}

	}

	return $update;

}