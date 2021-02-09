<?php
/**
 * Plugin Name: WP Data Sync
 * Plugin URI:  https://wpdatasync.com/products/
 * Description: Sync raw data from any data source to your WordPress website
 * Version:     1.6.7
 * Author:      WP Data Sync
 * Author URI:  https://wpdatasync.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-data-sync
 * Domain Path: /languages
 *
 * WC requires at least: 3.0
 * WC tested up to: 4.8.0
 *
 * Package:     WP_DataSync
*/

namespace WP_DataSync;

use WP_DataSync\App\ItemRequest;
use WP_DataSync\App\KeyRequest;
use WP_DataSync\App\SyncRequest;
use WP_DataSync\App\VersionRequest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uploads = wp_get_upload_dir();

$defines = [
	'WP_DATA_SYNC_VERSION'    => '1.6.7',
	'WP_DATA_SYNC_CAP'        => 'manage_options',
	'WP_DATA_SYNC_PLUGIN'     => plugin_basename( __FILE__ ),
	'WP_DATA_SYNC_VIEWS'      => plugin_dir_path( __FILE__ ) . 'views/',
	'WP_DATA_SYNC_ASSETS'     => plugins_url( 'assets/', __FILE__ ),
	'WP_DATA_SYNC_LOG_DIR'    => $uploads['basedir'] . '/wp-data-sync-logs/',
	'WP_DATA_SYNC_EP_VERSION' => 'v1'
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

	if ( is_admin() ) {
		App\Settings::instance()->actions();
	}

	add_action( 'rest_api_init', function() {
		App\SyncRequest::instance()->register_route();
		App\KeyRequest::instance()->register_route();
		App\ItemRequest::instance()->register_route();
		App\VersionRequest::instance()->register_route();
	} );

	if ( class_exists( 'woocommerce' ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'woocommerce/wc-data-sync.php' );
	}

} );
