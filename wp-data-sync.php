<?php
/**
 * Plugin Name: WP Data Sync
 * Plugin URI:  https://wpdatasync.com/products/
 * Description: Sync raw data from WP Data Sync API to your WordPress website
 * Version:     1.0.5
 * Author:      WP Data Sync
 * Author URI:  https://wpdatasync.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-data-sync
 * Domain Path: /languages
 *
 * Package:     WP_DataSync
*/

namespace WP_DataSync;

$uploads = wp_get_upload_dir();

$defines = [
	'WP_DATA_SYNC_VERSION' => '1.0.5',
	'WP_DATA_SYNC_CAP'     => 'manage_options',
	'WP_DATA_SYNC_PLUGIN'  => plugin_basename( __FILE__ ),
	'WP_DATA_SYNC_VIEWS'   => plugin_dir_path( __FILE__ ) . 'views/',
	'WP_DATA_SYNC_LOG_DIR' => $uploads['basedir'] . '/wp-data-sync-logs/'
];

foreach ( $defines as $define => $value ) {
	if ( ! defined( $define ) ) {
		define( $define, $value );
	}
}

foreach ( glob( plugin_dir_path( __FILE__ ) . 'app/**/*.php' ) as $file ) {
	require_once $file;
}

add_action( 'plugins_loaded', function() {
	$settings = App\Settings::instance();
	$settings->actions();
} );
