<?php
/**
 * Plugin Disable
 *
 * Disable specific plugins.
 *
 * @since   2.9.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'upgrader_process_complete', function() {

    $plugin = 'wp-data-sync-woocommerce/wp-data-sync-woocommerce.php';

    if ( is_plugin_active( $plugin ) ) {
        deactivate_plugins( $plugin );
    }

} );
