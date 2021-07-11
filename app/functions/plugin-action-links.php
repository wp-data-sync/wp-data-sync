<?php
/**
 * Plugin Action Links
 *
 * Add settings link to plugin action links.
 *
 * @since   1.0.2
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'plugin_action_links', function( $links, $file ) {

	if ( $file === WPDSYNC_PLUGIN ) {

		$links[] = '<a href="options-general.php?page=wp-data-sync">' . __( 'Settings' ) . '</a>';

	}

	return $links;

}, 10, 2 );
