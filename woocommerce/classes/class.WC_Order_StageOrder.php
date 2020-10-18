<?php
/**
 * WC_Order_StageOrder
 *
 * Request WooCommerce product data
 *
 * @since   1.4.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\Woo;

use WP_DataSync\App\Log;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Order_StageOrder {

	const EVENT_HOOK = 'wp_data_sync_stage_order_event';

	/**
	 * @var WC_Order_StageOrder
	 */

	public static $instance;

	/**
	 * @var int
	 */

	private $order_id;

	/**
	 * WC_Order_StageOrder constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * @return WC_Order_StageOrder
	 */

	public static function instance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Push order.
	 *
	 * @param $order_id
	 *
	 * @return bool
	 */

	public function push( $order_id ) {

		Log::write( 'stage-order', $order_id );

		$this->order_id = $order_id;

		if ( $endpoint = $this->endpoint() ) {

			if ( $args = $this->args() ) {

				if ( $response = wp_remote_post( $endpoint, $args ) ) {

					Log::write( 'stage-order', $response );

					return TRUE;

				}

			}

		}

		return FALSE;

	}

	/**
	 * Endpoint.
	 *
	 * @param $order_id
	 *
	 * @return string|void
	 */

	protected function endpoint() {

		if ( ! $api_url = get_option( 'wp_data_sync_api_url' ) ) {
			return;
		}

		if ( ! $access_key = get_option( 'wp_data_sync_access_key' ) ) {
			return;
		}

		return trailingslashit( join( '/', [
			untrailingslashit( $api_url ),
			'api',
			'stage-order',
			WP_DATA_SYNC_EP_VERSION,
			$access_key,
			$this->order_id
		] ) );

	}

	/**
	 * Args.
	 *
	 * @return array|void
	 */

	protected function args() {

		if ( ! $private_key = get_option( 'wp_data_sync_private_key' ) ) {
			return;
		}

		return [
			'timeout'   => 5,
			'sslverify' => FALSE,
			// Wait for response only if logging is active.
			'blocking'  => Log::is_active(),
			'body'      => json_encode( $this->order_id ),
			'headers'   => [
				'Accept'         => 'application/json',
				'Authentication' => $private_key,
				'Referer'        => untrailingslashit( home_url() )
			]
		];

	}

	/**
	 * Database tabke name.
	 *
	 * @return string
	 */

	public static function table() {

		global $wpdb;

		return $wpdb->prefix . 'data_sync_stage_order';

	}

	/**
	 * Create the stage order database table.
	 */

	public static function create_table() {

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$charset_collate = $wpdb->get_charset_collate();
		$table           = self::table();

		$sql = "
			CREATE TABLE IF NOT EXISTS $table (
  			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  			order_id bigint(20) NOT NULL,
  			status varchar(40) NOT NULL,
  			PRIMARY KEY (id),
			KEY order_id (order_id),
			KEY status (status)
			) $charset_collate;
        ";

		dbDelta( $sql );

	}

	/**
	 * Truncate table.
	 */

	public function truncate_table() {

		global $wpdb;

		$table = self::table();

		$wpdb->query( "TRUNCATE TABLE $table" );

	}

	/**
	 * Set.
	 *
	 * @param $order \WC_Order
	 */

	public function set( $order ) {

		global $wpdb;

		$wpdb->insert(
			self::table(),
			[
				'order_id' => $order->get_id(),
				'status'   => $order->get_status()
			]
		);

	}

	/**
	 * Get a staged order.
	 *
	 * @return bool|string
	 */

	public function get() {

		global $wpdb;

		$table = self::table();

		$order_id = $wpdb->get_var(
			"
			SELECT order_id
			FROM $table
			"
		);

		if ( null === $order_id || is_wp_error( $order_id ) ) {
			return FALSE;
		}

		return $order_id;

	}

	/**
	 * Delete staged order.
	 *
	 * @param $order_id
	 */

	public function delete( $order_id ) {

		global $wpdb;

		$wpdb->delete(
			self::table(),
			[ 'order_id' => $order_id ]
		);

	}

	/**
	 * Set cron.
	 */

	public function set_cron() {

		if ( ! wp_next_scheduled( self::EVENT_HOOK ) ) {
			wp_schedule_event( time(), 'every_five_seconds', self::EVENT_HOOK );
		}

	}

	/**
	 * Delete cron.
	 */

	public function delete_cron() {

		if ( $timestamp = wp_next_scheduled( self::EVENT_HOOK ) ) {
			wp_unschedule_event( $timestamp, self::EVENT_HOOK );
		}

	}

	/**
	 * Count message.
	 *
	 * @return string|void
	 */

	public static function count_msg() {

		global $wpdb;

		$table = self::table();

		$count = $wpdb->get_var(
			"
			SELECT count(*)
			FROM $table
			"
		);

		if ( 0 === (int) $count || null === $count || is_wp_error( $count ) ) {
			return '';
		}

		return __( "$count orders in the queue.", 'wp-data-sync' );

	}

}
