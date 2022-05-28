<?php
/**
 * Item Request
 *
 * Request item data.
 *
 * @since   1.2.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ItemRequest extends Request {

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

	protected $permissions_key = 'wp_data_sync_allowed';

	/**
	 * @var string
	 */

	private $post_type;

	/**
	 * @var string
	 */

	private $api_id;

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
			'wp-data-sync',
			'/' . WPDSYNC_EP_VERSION . '/get-item/(?P<access_token>\S+)/(?P<post_type>\S+)/(?P<limit>\d+)/(?P<api_id>\S+)/',
			[
				'methods' => WP_REST_Server::READABLE,
				'args'    => [
					'access_token' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'access_token' ]
					],
					'post_type' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'set_post_type' ]
					],
					'limit' => [
						'sanitize_callback' => 'absint',
						'validate_callback' => [ $this, 'set_limit' ]
					],
					'api_id' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'set_api_id' ]
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

		$response = $this->get_items();

		Log::write( 'item-request', [
			'api_id'   => $this->api_id,
			'response' => $response
		], 'Response' );

		return rest_ensure_response( $response );

	}

	/**
	 * Check if post type exists.
	 *
	 * @param $post_type
	 *
	 * @return bool
	 */

	public function set_post_type( $post_type ) {

		$this->post_type = sanitize_text_field( $post_type );

		return true;

	}

	/**
	 * Set API ID.
	 *
	 * @param $api_id
	 *
	 * @return bool
	 */

	public function set_api_id( $api_id ) {

		$api_id = sanitize_text_field( $api_id );

		$this->api_id = strstr( $api_id, '~', true );

		return true;

	}

	/**
	 * Limit.
	 *
	 * @param $limit
	 *
	 * @return bool
	 */

	public function set_limit( $limit ) {

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

				Log::write( 'item-request', [
					'item_id'   => $item_id,
					'item_data' => $item_data
				], 'Item Data' );

				$this->insert_id( $item_id );

			}

			return apply_filters( 'wp_data_sync_get_items_response', $items );

		}

		return false;

	}

	/**
	 * Get a single item.
	 *
	 * @param $item_id
	 *
	 * @return mixed|void
	 */

	public function get_item( $item_id ) {

		$item_data                   = [];
		$item_data['source_item_id'] = $item_id;

		if ( ! Settings::is_data_type_excluded( 'post_data' ) ) {
			$item_data['post_data'] = $this->post_data( $item_id );
		}

		if ( ! Settings::is_data_type_excluded( 'post_meta' ) ) {
			$item_data['post_meta'] = $this->post_meta( $item_id );
		}

		if ( ! Settings::is_data_type_excluded( 'taxonomies' ) ) {
			$item_data['taxonomies'] = $this->taxonomies( $item_id );
		}

		if ( ! Settings::is_data_type_excluded( 'featured_image' ) ) {

			$item_data['featured_image'] = $this->featured_image( $item_id );

			if ( empty( $item_data['featured_image']['image_url'] ) ) {
				unset( $item_data['featured_image'] );
			}

		}

		if ( 'attachment' === $this->get_post_type() ) {
			$item_data['attachment'] = $this->attachment( $item_id );
		}

		if ( ! Settings::is_data_type_excluded( 'integrations' ) ) {
			$item_data['integrations'] = apply_filters( 'wp_data_sync_item_request_integrations', [], $item_id, $this );
		}

		return apply_filters( 'wp_data_sync_item_request', array_filter( $item_data ), $item_id, $this );

	}

	/**
	 * Post Data.
	 *
	 * @param $item_id int
	 *
	 * @return array|\WP_Post|null
	 */

	public function post_data( $item_id ) {

		global $wpdb;

		$post_data = $wpdb->get_row( $wpdb->prepare(
			"
			SELECT * 
			FROM $wpdb->posts
			WHERE ID = %d
			",
			intval( $item_id )
		), ARRAY_A );

		if ( empty( $post_data ) || is_wp_error( $post_data ) ) {
			return [];
		}

		unset( $post_data['ID'] );

		return apply_filters( 'wp_data_sync_item_request_post_data', $post_data, $item_id, $this );

	}

	/**
	 * Get the item IDs.
	 *
	 * @since 1.0.0
	 *        2.0.4 Add filters to SQL statements.
	 *
	 * @return bool|mixed
	 */

	public function item_ids() {

		global $wpdb;

		$table = self::table();

		/**
		 * SELECT statement.
		 */

		$select = "
			SELECT SQL_NO_CACHE SQL_CALC_FOUND_ROWS  p.ID 
			FROM {$wpdb->prefix}posts p
		";

		/**
		 * JOIN statement.
		 */

		$join = apply_filters( 'wp_data_sync_item_request_sql_join', $wpdb->prepare(
			"
			LEFT JOIN $table i
			ON (p.ID = i.item_id AND i.api_id = %s)
			",
			esc_sql( $this->api_id )
		), $this->post_type );

		/**
		 * WHERE statement
		 */

		$status       = get_option( 'wp_data_sync_item_request_status', [ 'publish' ] );
		$count        = count( $status );
		$placeholders = join( ', ', array_fill( 0, $count, '%s' ) );
		$where_args   = array_merge(
			[ esc_sql( $this->post_type ) ],
			array_map( 'esc_sql', $status )
		);

		$where = apply_filters( 'wp_data_sync_item_request_sql_where', $wpdb->prepare(
			" 
			WHERE i.item_id IS NULL 
			AND p.post_type = %s 
			AND p.post_status IN ( $placeholders )
			",
			$where_args
		), $this->post_type );

		/**
		 * ORDER BY statement
		 */

		$order_by = apply_filters( 'wp_data_sync_item_request_sql_order_by', "ORDER BY p.ID DESC", $this->post_type );

		/**
		 * LIMIT statement
		 */

		$limit = apply_filters( 'wp_data_sync_item_request_sql_limit', $wpdb->prepare(
			"LIMIT %d",
			$this->limit
		), $this->limit, $this->post_type );

		/**
		 * Combine parts to make the SQL statement.
		 */

		$sql = "$select $join $where $order_by $limit";

		$item_ids = $wpdb->get_col( $sql );

		Log::write( 'item-request',[
			'sql'      => $sql,
			'item_ids' => $item_ids
		], 'SQL Query' );

		$wpdb->flush();

		if ( empty( $item_ids ) || is_wp_error( $item_ids ) ) {
			return false;
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

		$post_meta   = [];
		$meta_values = get_post_meta( $item_id );

		foreach ( $meta_values as $meta_key => $values ) {

			// Get the first element of array.
			$meta_value = array_shift( $values );

			$post_meta[ $meta_key ] = maybe_unserialize( $meta_value );

		}

		// Save the post ID into meta data.
		$post_meta['_source_item_id'] = $item_id;

		return apply_filters( 'wp_data_sync_item_request_post_meta', $post_meta, $item_id, $this );

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
			'alt'         => get_post_meta( $item_id, '_wp_attachment_image_alt', true ) ?: ''
		];

		return apply_filters( 'wp_data_sync_item_request_featured_image', $featured_image, $item_id, $this );

	}

	/**
	 * Get attachment details.
	 *
	 * @param $item_id
	 *
	 * @return mixed|void
	 */

	public function attachment( $item_id ) {

		$attachment = [
			'image_url'   => wp_get_attachment_image_url( $item_id, 'full' ),
			'title'       => get_the_title( $item_id ) ?: '',
			'description' => get_the_content( $item_id ) ?: '',
			'caption'     => get_the_excerpt( $item_id ) ?: '',
			'alt'         => get_post_meta( $item_id, '_wp_attachment_image_alt', true ) ?: ''
		];

		return apply_filters( 'wp_data_sync_item_request_attachment', $attachment, $item_id, $this );

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
			return false;
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

		return apply_filters( 'wp_data_sync_item_request_taxonomies', array_filter( $results ), $item_id, $this );

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

		if ( $attach_id = get_term_meta( $term->term_id, 'thumbnail_id', true ) ) {
			return wp_get_attachment_image_url( (int) $attach_id, 'full' );
		}

		return false;

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

		return apply_filters( 'wp_data_sync_item_request_term_meta', $meta_values, $term, $this );

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
			[
				'item_id' => $item_id,
				'api_id'  => $this->api_id
			]
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
	 * Has synced.
	 *
	 * @return bool
	 */

	public static function has_synced() {

		global $wpdb;

		$table = self::table();

		$has_synced = $wpdb->get_var( $wpdb->prepare(
			"
			SELECT id 
			FROM $table
			WHERE item_id = %d
			",
			get_the_id()
		) );

		if ( null === $has_synced || is_wp_error( $has_synced ) ) {
			return false;
		}

		return true;

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
  			api_id varchar(100) NOT NULL,
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
