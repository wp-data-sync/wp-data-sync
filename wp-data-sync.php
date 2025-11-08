<?php
/**
 * Plugin Name: WP Data Sync
 * Plugin URI:  https://wpdatasync.com/products/
 * Description: Sync raw data from any data source to your WordPress website
 * Version:     3.4.9
 * Author:      WP Data Sync
 * Author URI:  https://wpdatasync.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-data-sync
 * Domain Path: /languages
 *
 * WC requires at least: 4.0
 * WC tested up to: 10.3.4
 *
 * Package:     WP_DataSync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$uploads = wp_get_upload_dir();

define( 'WPDSYNC_VERSION', '3.4.8' );
define( 'WPDSYNC_PLUGIN', __FILE__ );
define( 'WPDSYNC_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPDSYNC_URL', plugin_dir_url( __FILE__ ) );
define( 'WPDSYNC_FILE', 'wp-data-sync/wp-data-sync.php' );
define( 'WPDSYNC_VIEWS', WPDSYNC_PATH . 'views/' );
define( 'WPDSYNC_ASSETS', WPDSYNC_URL . 'assets/' );

$constants = [
    'WPDSYNC_CAP'        => 'manage_options',
    'WPDSYNC_LOG_DIR'    => $uploads['basedir'] . '/wp-data-sync-logs/',
    'WPDSYNC_EP_VERSION' => 'v2'
];

foreach ( $constants as $constant => $value ) {
    if ( ! defined( $constant ) ) {
        define( $constant, $value );
    }
}

add_action( 'plugins_loaded', function () {

    require WPDSYNC_PATH . 'require.php';

    if ( is_admin() ) {
        Settings::instance()->actions();
    }

    add_action( 'rest_api_init', function () {
        SyncRequest::instance()->register_route();
        KeyRequest::instance()->register_route();
        ItemRequest::instance()->register_route();
        VersionRequest::instance()->register_route();
        ReportRequest::instance()->register_route();
        LogRequest::instance()->register_route();
        ItemInfoRequest::instance()->register_route();
    } );

    add_action( 'init', function () {
        load_plugin_textdomain( 'wp-data-sync', false, basename( dirname( __FILE__ ) ) . '/languages' );
    } );

} );
