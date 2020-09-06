<?php
/**
 * Data Request
 *
 * Request data to sync inot API..
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

class DataRequest extends Core {

	private $request_type;
	private $post_type;
	private $last_updated;

	/**
	 * @var DataRequest
	 */

	public static $instance;

	/**
	 * DataRequest constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * Instance.
	 *
	 * @return DataRequest
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
			'wp-data-sync/1.0/',
			'data/(?P<access_token>\S+)/(?P<request_type>\S+)/(?P<post_type>\S+)/(?P<last_updated>\S+)',
			[
				'methods' => WP_REST_Server::READABLE,
				'args'    => [
					'access_token' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'access_key' ]
					],
					'request_type' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'request_type' ]
					],
					'post_type' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'post_type' ]
					],
					'last_updatede' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'last_updated' ]
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

		$response = $this->get_data();

		Log::write( 'data-request-response', $response );

		return rest_ensure_response( $response );

	}

	/**
	 * Check if request type is valid.
	 *
	 * @return bool
	 */

	public function request_type( $request_type ) {

		if ( in_array( $request_type, [ 'initial', 'updated' ] )) {
			$this->request_type = $request_type;
			return TRUE;
		}

		return FALSE;

	}

	/**
	 * Check if post type exists.
	 *
	 * @param $post_type
	 *
	 * @return bool
	 */

	public function post_type( $post_type ) {

		$this->post_type = $post_type;

		return post_type_exists( $post_type );

	}

	/**
	 * Last updated.
	 *
	 * @param $last_updated
	 *
	 * @return bool
	 */

	public function last_updated( $last_updated ) {

		$this->last_updated = $last_updated;

		return is_string( $last_updated );

	}

	/**
	 * Get the data.
	 *
	 * @return array
	 */

	public function get_data() {

		$post_id = $this->get_post_id();

		$data = [
			'post_object'  => [
				'post_title' => 'string',
				'post_type'  => $this->post_type,
				// Etc...
			],
			// Mix primary ID with meta data
			'post_meta' => [
				'key' => 'value',
				'key' => 'value'
			],
			'taxonomies' => [
				'taxonomy' => [
					0 => [
						'term'    => 'string',
						'parents' => [
							'parent',
							'parent'
						]
					]
				]
			],
			'post_thumbnail'  => 'URL string',
			'product_gallery' => [
				'URL string',
				'URL string'
			],
			'attributes' => [
				0 => [
					'name'    => 'string',
					'values'  => [
						'string',
						'string',
						'string'
					],
					'is_visible'   => 'bool',
					'is_taxonomy'  => 'bool',
					'is_variation' => 'bool'
				]
			],
			'variations' => [
				0 => [
					'post_object' => [
						'post_title' => 'string',
						'post_type'  => 'string',
						// Etc...
					],
					'post_meta' => [
						'key' => 'value',
						'key' => 'value'
					]
				]
			]
		];

		return apply_filters( 'wp_data_sync_get_data_request', $data );

	}

	public function get_post_id() {

		return get_post( [
			'numposts' => 1,
			'post_type' => $this->post_type,
			'fields'    => 'ids'
		] );

	}

}

add_action( 'rest_api_init', function() {
	DataRequest::instance()->register_route();
} );