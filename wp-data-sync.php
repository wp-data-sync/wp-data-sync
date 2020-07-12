<?php
/**
 * Plugin Name: WP Data Sync
 * Plugin URI:  https://wpdatasync.com/products/
 * Description: Sync raw data from WP Data Sync API to your WordPress website
 * Version:     1.1.2
 * Author:      WP Data Sync
 * Author URI:  https://wpdatasync.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-data-sync
 * Domain Path: /languages
 *
 * WC requires at least: 2.5
 * WC tested up to: 4.2.0
 *
 * Package:     WP_DataSync
*/

namespace WP_DataSync;

$uploads = wp_get_upload_dir();

$defines = [
	'WP_DATA_SYNC_VERSION' => '1.1.2',
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

	if ( is_admin() ) {
		App\Settings::instance()->actions();
	}

	if ( class_exists( 'woocommerce' ) ) {

		foreach ( glob( plugin_dir_path( __FILE__ ) . 'app/woocommerce/**/*.php' ) as $file ) {
			require_once $file;
		}

		/**
		 * Process WooCommerce.
		 */

		add_action( 'wp_data_sync_after_process', function ( $post_id, $data, $data_sync ) {
			App\WC_DataSync::instance( $data_sync )->wc_process( $post_id, $data );
		}, 10, 3 );

	}

} );
