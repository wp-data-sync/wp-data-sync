<?php
/**
 * Plugin Action Links
 *
 * Add settings link to plugin action links.
 *
 * @since   1.0.2
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

add_filter( 'plugin_action_links', function( $links, $file ) {

	if ( $file === WP_DATA_SYNC_PLUGIN ) {

		$links[] = '<a href="options-general.php?page=wp-data-sync">' . __( 'Settings' ) . '</a>';

	}

	return $links;

}, 10, 2 );
