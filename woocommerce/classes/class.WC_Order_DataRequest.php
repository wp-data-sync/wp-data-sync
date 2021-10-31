<?php
/**
 * WC_Order_DataRequest
 *
 * Request WooCommerce product data
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\Woo;

use WP_DataSync\App\Request;
use WP_DataSync\App\Log;
use WP_REST_Server;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Order_DataRequest extends Request {

	/**
	 * @var string
	 */

	protected $access_token_key = 'wp_data_sync_access_token';

	/**
	 * @var string
	 */

	protected $private_token_key = 'wp_data_sync_private_token';

	/**
	 * @var string
	 */

	protected $permissions_key = 'wp_data_sync_order_sync_allowed';

	/**
	 * @var WC_Order_DataRequest
	 */

	public static $instance;

	/**
	 * WC_Order_DataRequest constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * @return WC_Order_DataRequest
	 */

	public static function instance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Register the route.
	 *
	 * @link https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 */

	public function register_route() {

		register_rest_route(
			'wp-data-sync/' . WPDSYNC_EP_VERSION,
			'/order-request/(?P<access_token>\S+)/(?P<min_date>\S+)/(?P<limit>\d+)/(?P<cache_buster>\S+)',
			[
				'methods' => WP_REST_Server::READABLE,
				'args'    => [
					'access_token' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'access_token' ]
					],
					'min_date' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							return is_string( $param );
						}
					],
					'limit' => [
						'sanitize_callback' => 'absint',
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						}
					],
					'cache_buster' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function( $param ) {
							return is_string( $param );
						}
					]
				],
				'permission_callback' => [ $this, 'access' ],
				'callback'            => [ $this, 'request' ],
			]
		);

	}

	/**
	 * Request.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */

	public function request( WP_REST_Request $request ) {

		$order_data = WC_Order_Data::instance();

		$min_date = $request->get_param( 'min_date' );
		$limit    = $request->get_param( 'limit' );
		$response = [];

		if ( $order_ids = $this->fetch_order_ids( $min_date, $limit ) ) {

			foreach ( $order_ids as $order_id ) {

				$status = 'no';

				if ( $order = wc_get_order( $order_id ) ) {

					$_order_data = $order_data->get( $order );

					if ( apply_filters( 'wp_data_sync_can_sync_order', TRUE, $_order_data, $order_id, $order ) ) {

						$response[ $order_id ] = $_order_data;

						$order->add_order_note( __( 'Order synced to WP Data Sync API', 'wp-data-sync' ) );

						$status = current_time( 'mysql' );

					}

				}

				update_post_meta( $order_id, WCDSYNC_ORDER_SYNC_STATUS, $status );

			}

		}

		Log::write( 'order-request', $request->get_url_params() );
		Log::write( 'order-request', $response );

		return rest_ensure_response( $response );

	}

	/**
	 * Format min date.
	 *
	 * @param $min_date
	 *
	 * @return false|string
	 */

	public function format_min_date( $min_date ) {
		return date( 'Y-m-d H:i:s', strtotime( $min_date ) );
	}

	/**
	 * Fetch order ids.
	 *
	 * @param $min_date
	 * @param $limit
	 *
	 * @return array|bool
	 */

	public function fetch_order_ids( $min_date, $limit ) {

		global $wpdb;

		$allowed_status = get_option( 'wp_data_sync_allowed_order_status' );

		if ( empty( $allowed_status ) ) {
			return FALSE;
		}

		$placeholders = join( ', ', array_fill( 0, count( $allowed_status ), '%s' ) );
		$values       = array_merge(
			[ $this->format_min_date( $min_date ) ],
			array_map( 'esc_sql', $allowed_status ),
			[ esc_sql( WCDSYNC_ORDER_SYNC_STATUS ) ],
			[ intval( $limit ) ]
		);

		$order_ids = $wpdb->get_col( $wpdb->prepare(
			"
			SELECT p.ID
			FROM $wpdb->posts p
			WHERE p.post_type = 'shop_order'
			AND p.post_date > %s
			AND p.post_status IN ($placeholders)
			AND NOT EXISTS (
			    SELECT * FROM $wpdb->postmeta pm
                WHERE pm.meta_key = %s
                AND pm.post_id = p.ID
			)
			LIMIT %d
			",
			$values
		) );

		if ( empty( $order_ids ) || is_wp_error( $order_ids ) ) {
			return FALSE;
		}

		return array_map( 'intval', $order_ids );

	}

}

add_action( 'admin_init', function() {

	$order_data = WC_Order_Data::instance();

	if ( $order = wc_get_order( 1481 ) ) {

		$response[ $order_id ] = $order_data->get( $order );

	}

	var_dump( $response ); die();

});
