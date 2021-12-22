<?php
/**
 * Plugin Name: WP Data Sync
 * Plugin URI:  https://wpdatasync.com/products/
 * Description: Sync raw data from any data source to your WordPress website
 * Version:     2.1.17
 * Author:      WP Data Sync
 * Author URI:  https://wpdatasync.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-data-sync
 * Domain Path: /languages
 *
 * WC requires at least: 3.0
 * WC tested up to: 6.0.0
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
	'WPDSYNC_VERSION'    => '2.1.17',
	'WPDSYNC_CAP'        => 'manage_options',
	'WPDSYNC_PLUGIN'     => plugin_basename( __FILE__ ),
	'WPDSYNC_VIEWS'      => plugin_dir_path( __FILE__ ) . 'views/',
	'WPDSYNC_ASSETS'     => plugins_url( 'assets/', __FILE__ ),
	'WPDSYNC_LOG_DIR'    => $uploads['basedir'] . '/wp-data-sync-logs/',
	'WPDSYNC_EP_VERSION' => 'v2'
];

foreach ( $defines as $define => $value ) {
	if ( ! defined( $define ) ) {
		define( $define, $value );
	}
}

foreach ( glob( plugin_dir_path( __FILE__ ) . 'includes/**/*.php' ) as $file ) {
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
		App\UserRequest::instance()->register_route();
	} );

	if ( class_exists( 'woocommerce' ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'woocommerce/wc-data-sync.php' );
	}

	add_action( 'init', function() {
		load_plugin_textdomain( 'wp-data-sync', FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
	} );

} );
