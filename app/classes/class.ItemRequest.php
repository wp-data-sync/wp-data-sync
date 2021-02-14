<?php
/**
 * Item Request
 *
 * Request item data.
 *
 * @since   1.2.0
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

class ItemRequest extends Access {

	/**
	 * @var string
	 */

	protected $access_token_key = 'wp_data_sync_item_request_access_token';

	/**
	 * @var string
	 */

	protected $private_token_key = 'wp_data_sync_item_request_private_token';

	/**
	 * @var string
	 */

	private $post_type;

	/**
	 * @var integer
	 */

	private $limit;

	/**
	 * @var ItemRequest
	 */

	public static $instance;

	/**
	 * ItemRequest constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * Instance.
	 *
	 * @return ItemRequest
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
			'wp-data-sync/' . WP_DATA_SYNC_EP_VERSION,
			'/get-item/(?P<access_token>\S+)/(?P<post_type>\S+)/(?P<limit>\d+)/(?P<cache_buster>\S+)/',
			[
				'methods' => WP_REST_Server::READABLE,
				'args'    => [
					'access_token' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'access_token' ]
					],
					'post_type' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'post_type' ]
					],
					'limit' => [
						'sanitize_callback' => 'intval',
						'validate_callback' => [ $this, 'limit' ]
					],
					'cache_buster' => [
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
	 * Process the request.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|\WP_REST_Response
	 */

	public function request() {

		if ( 'refresh' === $this->post_type ) {

			$this->truncate_table();

			return rest_ensure_response( FALSE );

		}

		$response = $this->get_items();

		Log::write( 'data-request-response', $response );

		return rest_ensure_response( $response );

	}

	/**
	 * Check if post type exists.
	 *
	 * @param $post_type
	 *
	 * @return bool
	 */

	public function post_type( $post_type ) {

		$this->post_type = sanitize_text_field( $post_type );

		return TRUE;

	}

	/**
	 * Limit.
	 *
	 * @param $limit
	 *
	 * @return bool
	 */

	public function limit( $limit ) {

		$this->limit = intval( $limit );

		return  $this->limit > 0;

	}

	/**
	 * Get items.
	 *
	 * @return mixed
	 */

	public function get_items() {

		if ( $item_ids = $this->item_ids() ) {

			$items = [];

			foreach ( $item_ids as $item_id ) {

				$items[] = $item_data = $this->get_item( $item_id );

				Log::write( 'item-request-item-data', $item_data );

				$this->insert_id( $item_id );

			}

			return apply_filters( 'wp_data_sync_get_items_response', $items );

		}

		return FALSE;

	}

	/**
	 * Get a single item.
	 *
	 * @param $item_id
	 *
	 * @return mixed|void
	 */

	public function get_item( $item_id ) {

		$item_data = [
			'source_item_id'  => $item_id,
			'post_data'       => $this->get_post( $item_id ),
			'post_meta'       => $this->post_meta( $item_id ),
			'taxonomies'      => $this->taxonomies( $item_id ),
			'featured_image'  => $this->featured_image( $item_id ),
			'integratiuons'   => apply_filters( 'wp_data_sync_item_request_integrations', [], $item_id )
		];

		return apply_filters( 'wp_data_sync_item_request', $item_data, $item_id, $this );

	}

	/**
	 * Get post.
	 *
	 * @param $item_id     int
	 * @param $post_parent int
	 *
	 * @return array|\WP_Post|null
	 */

	public function get_post( $item_id, $post_parent = 0 ) {

		global $wpdb;

		$item = $wpdb->get_row( $wpdb->prepare(
			"
			SELECT * 
			FROM $wpdb->posts
			WHERE ID = %d
			",
			$item_id
		) );

		if ( null === $item || is_wp_error( $item ) ) {
			return [];
		}

		unset( $item->ID );
		unset( $item->guid );

		$item->post_parent = $post_parent;

		if ( Settings::is_checked( 'wp_data_sync_unset_post_author' ) ) {
			unset( $item->post_author );
		}

		return $item;

	}

	/**
	 * Get the item IDs.
	 *
	 * @return bool|mixed
	 */

	public function item_ids() {

		global $wpdb;

		$table        = self::table();
		$status       = get_option( 'wp_data_sync_item_request_status', [ 'publish' ] );
		$count        = count( $status );
		$placeholders = join( ', ', array_fill( 0, $count, '%s' ) );
		$args         = array_merge( [ $this->post_type ], $status, [ $this->limit ] );

		$sql = $wpdb->prepare(
			"
			SELECT SQL_NO_CACHE SQL_CALC_FOUND_ROWS  p.ID 
			FROM {$wpdb->prefix}posts p
			LEFT JOIN $table i
			ON (p.ID = i.item_id) 
			WHERE (i.item_id IS NULL) 
			AND p.post_type = %s 
			AND p.post_status IN ( $placeholders )
			GROUP BY p.ID 
			ORDER BY p.ID DESC 
			LIMIT %d
			",
			$args
		);

		Log::write( 'item-request-sql', $sql );

		$item_ids = $wpdb->get_col( $sql );

		Log::write( 'item-request-sql', $item_ids );

		$wpdb->flush();

		if ( null === $item_ids ) {
			return FALSE;
		}

		return array_map( 'intval', $item_ids );

	}

	/**
	 * Post Meta.
	 *
	 * @param $item_id
	 *
	 * @return array
	 */

	public function post_meta( $item_id ) {

		$meta_values = [];
		$post_meta   = get_post_meta( $item_id );

		foreach ( $post_meta as $meta_key => $values ) {

			// Get the first element of array.
			$meta_value = array_shift( $values );

			$meta_values[ $meta_key ] = maybe_unserialize( $meta_value );

		}

		// Save the post ID into meta data.
		$meta_values['_source_item_id'] = $item_id;

		return $meta_values;

	}

	/**
	 * Featured image.
	 *
	 * @since 1.6.0
	 *
	 * @param $item_id
	 *
	 * @return mixed|void
	 */

	public function featured_image( $item_id ) {

		$featured_image = [
			'image_url'   => get_the_post_thumbnail_url( $item_id, 'full' ),
			'title'       => get_the_title( $item_id ) ?: '',
			'description' => get_the_content( $item_id ) ?: '',
			'caption'     => get_the_excerpt( $item_id ) ?: '',
			'alt'         => get_post_meta( $item_id, '_wp_attachment_image_alt', TRUE ) ?: ''
		];

		return apply_filters( 'wp_data_sync_item_request_featured_image', $featured_image );

	}

	/**
	 * Taxonomies.
	 * 
	 * @param $item_id
	 *
	 * @return array|\WP_Error|bool
	 */

	public function taxonomies( $item_id ) {

		if ( ! post_type_exists( $this->post_type ) ) {
			return FALSE;
		}

		$results = [];
		$taxonomies = get_object_taxonomies( $this->post_type );

		foreach ( $taxonomies as $taxonomy ) {

			if ( taxonomy_exists( $taxonomy ) ) {

				$term_ids = wp_get_object_terms( $item_id, $taxonomy, [ 'fields' => 'ids' ] );

				if ( ! empty( $term_ids ) && is_array( $term_ids ) ) {
					$results[ $taxonomy ] = $this->format_terms( $term_ids, $taxonomy );
				}

			}

		}

		return array_filter( $results );

	}

	/**
	 * Format Terms.
	 *
	 * @param $term_ids
	 * @param $taxonomy
	 *
	 * @return array
	 */

	public function format_terms( $term_ids, $taxonomy ) {

		$term_ids = wp_parse_id_list( $term_ids );

		if ( ! count( $term_ids ) ) {
			return [];
		}

		$formatted_terms = [];

		if ( is_taxonomy_hierarchical( $taxonomy ) ) {

			foreach ( $term_ids as $term_id ) {

				$ancestor_ids = array_reverse( get_ancestors( $term_id, $taxonomy ) );
				$ancestors    = [];

				foreach ( $ancestor_ids as $ancestor_id ) {

					$term = get_term( $ancestor_id, $taxonomy );

					if ( $term && ! is_wp_error( $term ) ) {
						$ancestors[ $term->slug ] = $this->term_array( $term );
					}

				}

				$term = get_term( $term_id, $taxonomy );

				if ( $term && ! is_wp_error( $term ) ) {
					$formatted_terms[ $term->slug ] = $this->term_array( $term );
				}

				$formatted_terms[ $term->slug ]['parents'] = $ancestors;

			}

		} else {

			foreach ( $term_ids as $term_id ) {

				$term = get_term( $term_id, $taxonomy );

				if ( $term && ! is_wp_error( $term ) ) {
					$formatted_terms[ $term->slug ] = $this->term_array( $term );
				}

			}

		}

		return $formatted_terms;

	}

	/**
	 * Term array.
	 *
	 * @param $term
	 *
	 * @return array
	 */

	public function term_array( $term ) {

		$term_array = [
			'name'        => $term->name,
			'description' => $term->description,
			'thumb_url'   => $this->term_thumb_url( $term ),
			'term_meta'   => $this->term_meta( $term )
		];

		return apply_filters( 'wp_data_sync_item_request_term_array', $term_array, $term->term_id );

	}

	/**
	 * Get term thumnail URL.
	 *
	 * @param $term
	 *
	 * @return bool|false|string
	 */

	public function term_thumb_url( $term ) {

		if ( $attach_id = get_term_meta( $term->term_id, 'thumbnail_id', TRUE ) ) {
			return wp_get_attachment_image_url( (int) $attach_id, 'full' );
		}

		return FALSE;

	}

	/**
	 * Term meta.
	 *
	 * @param $term
	 *
	 * @return array
	 */

	public function term_meta( $term ) {

		$meta_values = [];
		$term_meta   = get_term_meta( $term->term_id );

		foreach ( $term_meta as $meta_key => $values ) {

			// Get the first element of array.
			$meta_value = array_shift( $values );

			$meta_values[ $meta_key ] = maybe_unserialize( $meta_value );

		}

		return $meta_values;

	}

	/**
	 * Insert Item ID.
	 *
	 * @param $item_id
	 */

	public function insert_id( $item_id ) {

		global $wpdb;

		$wpdb->insert(
			self::table(),
			[ 'item_id' => $item_id ]
		);

	}

	/**
	 * Delete Item ID.
	 *
	 * @param $item_id
	 */

	public static function delete_id( $item_id ) {

		global $wpdb;

		$wpdb->delete(
			self::table(),
			[ 'item_id' => $item_id ]
		);

	}

	/**
	 * DB Table name.
	 *
	 * @return string
	 */

	private static function table() {

		global $wpdb;

		return $wpdb->prefix . 'data_sync_item_request';

	}

	/**
	 * Truncate Item Request table.
	 */

	private function truncate_table() {

		global $wpdb;

		$table = self::table();

		$wpdb->query( "TRUNCATE TABLE $table" );

	}

	/**
	 * Create the item request table.
	 */

	public static function create_table() {

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$charset_collate = $wpdb->get_charset_collate();
        $table           = self::table();

		$sql = "
			CREATE TABLE IF NOT EXISTS $table (
  			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  			item_id bigint(20) NOT NULL,
  			PRIMARY KEY (id),
			KEY item_id (item_id)
			) $charset_collate;
        ";

		dbDelta( $sql );

	}

	/**
	 * Get the post type.
	 *
	 * @return string
	 */

	public function get_post_type() {
		return $this->post_type;
	}

}
