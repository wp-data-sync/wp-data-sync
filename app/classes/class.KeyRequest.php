<?php
/**
 * Key Request
 *
 * Request the registered data keys.
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KeyRequest extends Core {

	/**
	 * @var KeyRequest
	 */

	public static $instance;

	/**
	 * KeyRequest constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * Instance.
	 *
	 * @return KeyRequest
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
			'wp-data-sync/' . WP_DATA_SYNC_EP_VERSION . '/',
			'key/(?P<access_token>\S+)/',
			[
				'methods' => WP_REST_Server::READABLE,
				'args'    => [
					'access_token' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'access_key' ]
					]
				],
				'permission_callback' => [ $this, 'access' ],
				'callback'            => [ $this, 'request' ],
			]
		);

	}

	/**
	 * Process the request.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|\WP_REST_Response
	 */

	public function request() {

		$response = $this->get_keys();

		Log::write( 'data-key-request-response', $response );

		return rest_ensure_response( $response );

	}

	/**
	 * Get the data keys.
	 *
	 * @return array
	 */

	public function get_keys() {

		$keys =  [
			0 => [
				'heading' => __( 'Post Types' ),
				'keys'    => $this->get_post_types(),
				'type'    => 'key_value'
			],
			1 => [
				'heading' => __( 'Taxonomies' ),
				'keys'    => $this->get_taxonomies(),
				'type'    => 'key_value'
			],
			2  => [
				'heading' => __( 'Meta Keys' ),
				'keys'    => $this->meta_keys(),
				'type'    => 'value'
			]
		];

		return apply_filters( 'wp_data_sync_data_keys', $keys );

	}

	/**
	 * Get post types.
	 *
	 * @return array
	 */

	public function get_post_types() {

		$post_types = [];

		foreach ( get_post_types( [], 'object' ) as $post_type ) {

			$post_types[ $post_type->name ] = $post_type->label;

		}

		return $post_types;

	}

	/**
	 * Get taxonomies.
	 *
	 * @return array
	 */

	public function get_taxonomies() {

		$taxonomies = [];

		foreach ( get_taxonomies( [], 'object' ) as $taxonomy ) {

			// Filter WooCommerce product attribute taxonomies.
			if ( class_exists( 'WooCommerce' ) && substr( $taxonomy->name, 0, 3 ) === 'pa_' ) {
				continue;
			}

			$taxonomies[ $taxonomy->name ] = $taxonomy->label;

		}

		return $taxonomies;

	}

	/**
	 * Get all the unique meta keys.
	 *
	 * @return array|object|null
	 */

	public function meta_keys() {

		global $wpdb;

		$rows = $wpdb->get_results(
			"
			SELECT DISTINCT meta_key 
			FROM {$wpdb->postmeta}
			WHERE meta_key NOT IN ( '_edit_last', '_edit_lock' )
			AND meta_key NOT LIKE '_menu_item_%'
			ORDER BY meta_key
			"
		);

		if ( null === $rows ) {
			return [];
		}

		$meta_keys = [];

		foreach ( $rows as $row ) {

			$meta_keys[] = $row->meta_key;
		}

		return $meta_keys;

	}

}
