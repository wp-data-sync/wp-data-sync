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

class ItemRequest extends Core {

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
			'wp-data-sync/1.0/',
			'get-item/(?P<access_token>\S+)/(?P<post_type>\S+)/(?P<limit>\d+)/',
			[
				'methods' => WP_REST_Server::READABLE,
				'args'    => [
					'access_token' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'access_key' ]
					],
					'post_type' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'post_type' ]
					],
					'limit' => [
						'sanitize_callback' => 'intval',
						'validate_callback' => [ $this, 'limit' ]
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

		$this->post_type = $post_type;

		// Allow 'refresh' as post type to trunacte the ItemRequest table.
		if ( 'refresh' === $post_type ) {
			return TRUE;
		}

		return post_type_exists( $post_type );

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

				$items[] = $this->get_item( $item_id );

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
			'post_object'     => $this->get_post( $item_id ),
			'post_meta'       => $this->post_meta( $item_id ),
			'taxonomies'      => $this->taxonomies( $item_id ),
			'post_thumbnail'  => $this->thumbnail_url( $item_id ),
		];

		return apply_filters( 'wp_data_sync_item_request', $item_data, $item_id, $this );

	}

	/**
	 * Get post.
	 *
	 * @param $item_id
	 *
	 * @return array|\WP_Post|null
	 */

	public function get_post( $item_id ) {

		$item = get_post( $item_id );

		unset( $item->ID );
		unset( $item->guid );
		unset( $item->post_parent );
		unset( $item->post_date_gmt );
		unset( $item->post_modified_gmt );

		return $item;

	}

	/**
	 * Get the item IDs.
	 *
	 * @return bool|mixed
	 */

	public function item_ids() {

		global $wpdb;

		$table = self::table();

		$item_ids = $wpdb->get_col(
			$wpdb->prepare(
				"
				SELECT SQL_NO_CACHE SQL_CALC_FOUND_ROWS  p.ID 
				FROM {$wpdb->prefix}posts p
				LEFT JOIN $table i
				ON (p.ID = i.item_id) 
				WHERE (i.item_id IS NULL) 
				AND p.post_type = %s 
				AND (p.post_status = 'publish' OR p.post_status = 'trash')
				GROUP BY p.ID 
				ORDER BY p.ID DESC 
				LIMIT %d
				",
				$this->post_type,
				$this->limit
			)
		);

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

		$values                    = [];
		$values['_source_item_id']  = $item_id;

		$post_meta = get_post_meta( $item_id );

		foreach ( $post_meta as $key => $value ) {
			$values[ $key ] = $value[0];
		}

		return $values;

	}

	/**
	 * Thumbnail URL.
	 *
	 * @param $item_id
	 *
	 * @return bool|false|string
	 */

	public function thumbnail_url( $item_id ) {
		return get_the_post_thumbnail_url( $item_id );
	}

	/**
	 * Taxonomies.
	 * 
	 * @param $item_id
	 *
	 * @return array|\WP_Error
	 */

	public function taxonomies( $item_id ) {

		$results = [];
		$taxonomies = get_object_taxonomies( $this->post_type );

		foreach ( $taxonomies as $taxonomy ) {

			$term_ids = wp_get_object_terms( $item_id, $taxonomy, [ 'fields' => 'ids', 'childless' => TRUE ] );

			if ( ! empty( $term_ids ) && is_array( $term_ids ) ) {
				$results[ $taxonomy ] = $this->format_terms( $term_ids, $taxonomy );
			}

		}

		return array_filter( $results );

	}

	/**
	 * Formated terms.
	 *
	 * @param $term_ids
	 * @param $taxonomy
	 *
	 * @return array|string
	 */

	public function format_terms( $term_ids, $taxonomy ) {

		$term_ids = wp_parse_id_list( $term_ids );

		if ( ! count( $term_ids ) ) {
			return '';
		}

		$terms = [];
		$i     = 1;

		if ( is_taxonomy_hierarchical( $taxonomy ) ) {

			foreach ( $term_ids as $term_id ) {

				$parents = [];
				$p       = 1;
				$parent_ids   = array_reverse( get_ancestors( $term_id, $taxonomy ) );

				foreach ( $parent_ids as $parent_id ) {

					$term = get_term( $parent_id, $taxonomy );

					if ( $term && ! is_wp_error( $term ) ) {
						$parents["parent_$p"] = $term->name;
					}

					$p++;

				}

				$term = get_term( $term_id, $taxonomy );

				if ( $term && ! is_wp_error( $term ) ) {

					$terms["term_$i"] = array_filter( [
						'name'    => $term->name,
						'parents' => $parents
					] );

				}

				$i++;

			}

		} else {

			foreach ( $term_ids as $term_id ) {

				$term = get_term( $term_id, $taxonomy );

				if ( $term && ! is_wp_error( $term ) ) {
					$terms["term_$i"] = array_filter( [
						'name'    => $term->name,
						'parents' => []
					] );
				}

				$i++;

			}

		}

		return array_filter( $terms );

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

}
