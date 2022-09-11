<?php
/**
 * Plugin Name: WP Data Sync
 * Plugin URI:  https://wpdatasync.com/products/
 * Description: Sync raw data from any data source to your WordPress website
 * Version:     2.4.15
 * Author:      WP Data Sync
 * Author URI:  https://wpdatasync.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-data-sync
 * Domain Path: /languages
 *
 * WC requires at least: 4.0
 * WC tested up to: 6.8.2
 *
 * Package:     WP_DataSync
*/

namespace WP_DataSync;

use WP_DataSync\App\Settings;
use WP_DataSync\App\SyncRequest;
use WP_DataSync\App\KeyRequest;
use WP_DataSync\App\ItemRequest;
use WP_DataSync\App\VersionRequest;
use WP_DataSync\App\UserRequest;
use WP_DataSync\App\ReportRequest;
use WP_DataSync\App\LogRequest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uploads = wp_get_upload_dir();

$defines = [
	'WPDSYNC_VERSION'    => '2.4.15',
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

add_action( 'plugins_loaded', function() {

	// Require includes dir files
	foreach ( glob( plugin_dir_path( __FILE__ ) . 'includes/**/*.php' ) as $file ) {
		require_once $file;
	}

	// Require test dir files in development envirnment.
	if ( defined( 'WPDS_LOCAL_DEV' ) && WPDS_LOCAL_DEV ) {

		foreach ( glob( plugin_dir_path( __FILE__ ) . 'tests/*.php' ) as $file ) {
			require_once $file;
		}

	}

	if ( is_admin() ) {
		Settings::instance()->actions();
	}

	add_action( 'rest_api_init', function() {
		SyncRequest::instance()->register_route();
		KeyRequest::instance()->register_route();
		ItemRequest::instance()->register_route();
		VersionRequest::instance()->register_route();
		UserRequest::instance()->register_route();
		ReportRequest::instance()->register_route();
		LogRequest::instance()->register_route();
	} );

	// Requyire woocommerce dir files
	if ( class_exists( 'woocommerce' ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'woocommerce/wc-data-sync.php' );
	}

	add_action( 'init', function() {
		load_plugin_textdomain( 'wp-data-sync', false, basename( dirname( __FILE__ ) ) . '/languages' );
	} );

} );
