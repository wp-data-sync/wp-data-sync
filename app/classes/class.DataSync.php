<?php
/**
 * DataSync
 *
 * Process to DataSync
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DataSync {

	/**
	 * @var bool|array
	 */

	private $primary_id = FALSE;

	/**
	 * @var bool|int
	 */

	private $post_id = FALSE;

	/**
	 * @var bool|array
	 */

	private $post_object = FALSE;

	/**
	 * @var bool|array
	 */

	private $post_meta = FALSE;

	/**
	 * @var bool|array
	 */

	private $taxonomies = FALSE;

	/**
	 * @var bool|string
	 */

	private $post_thumbnail = FALSE;

	/**
	 * @var bool|array
	 */

	private $attributes = FALSE;

	/**
	 * @var bool|array
	 */

	private $variations = FALSE;

	/**
	 * @var bool|array
	 */

	private $product_gallery = FALSE;

	/**
	 * @var bool|array
	 */

	private $cross_sells = FALSE;

	/**
	 * @var bool|array
	 */

	private $up_sells = FALSE;

	/**
	 * @var DataSync
	 */

	public static $instance;

	/**
	 * DataSync constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * Instance.
	 *
	 * @return DataSync
	 */

	public static function instance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Init.
	 *
	 * Set property values.
	 *
	 * @param $data
	 */

	public function init( $data ) {

		if ( is_array( $data ) ) {

			foreach ( $data as $key => $value ) {
				$this->$key = $value;
			}

		}

    }

	/**
	 * Process request data.
	 *
	 * @return mixed
	 */

	public function process() {

		// A primary ID is required!!
		if ( empty( $this->primary_id ) ) {
			return [ 'primary_id' => 'Empty primary ID' ];
		}

		// Set the post ID.
		$this->set_post_id();

		if ( $this->maybe_trash_post() ) {
			return [ 'post_object' => 'Trash Post' ];
		}

		if ( isset( $this->post_object ) ) {
			$this->post_object();
		}

		if ( isset(  $this->post_meta ) ) {
			$this->post_meta( $this->post_id, $this->post_meta );
		}

		if ( isset(  $this->taxonomies ) ) {
			$this->taxonomy( $this->post_id, $this->taxonomies );
		}

		if ( isset( $this->post_thumbnail ) ) {
			$this->post_thumbnail( $this->post_id, $this->post_thumbnail );
		}

		do_action( 'wp_data_sync_after_process', $this->post_id, $this );

		return [ 'post_id' => $this->post_id ];

	}

	/**
	 * Set the post ID.
	 */

	private function set_post_id() {

		if ( $this->post_id = $this->post_id( $this->primary_id ) ) {
			$this->post_object['ID'] = $this->post_id;
		}

	}

	/**
	 * Post ID.
	 *
	 * @param $primary_id
	 *
	 * @return bool|int
	 */

	public function post_id( $primary_id ) {

		global $wpdb;

		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT post_id 
    			FROM {$wpdb->postmeta} pm 
    			JOIN {$wpdb->posts} p 
      			ON p.ID = pm.post_id
    			WHERE meta_key = %s 
      			AND meta_value = %s 
      			ORDER BY meta_id DESC
    			LIMIT 1
				",
				$primary_id['meta_key'],
				$primary_id['meta_value']
			)
		);

		if ( null === $post_id ) {

			$post_id = wp_insert_post( [
				'post_title'  => 'WP Data Sync Placeholder',
				'post_type'   => get_option( 'wp_data_sync_post_type' ),
				'post_status' => 'draft'
			] );

		}

		return (int) $post_id;

	}

	/**
	 * Get the default value for a post object key.
	 *
	 */

	public function post_object_defaults() {

		// Only set the defaults for a new post.
		if ( empty( $this->post_id ) ) {

			$keys = $this->post_object_keys();

			foreach ( $keys as $key ) {

				$value = isset( $this->post_object[ $key ] ) ? $this->post_object[ $key ] : get_option( "wp_data_sync_{$key}", '' );

				$this->post_object[ $key ] = apply_filters( "wp_data_sync_{$key}", $value );

			}

		}

	}

	/**
	 * Trash post.
	 *
	 * @return bool
	 */

	public function maybe_trash_post() {

		if ( 0 < $this->post_id && 'trash' === $this->post_object['post_status'] ) {

			if ( 'true' === get_option( 'wp_data_sync_force_delete' ) ) {

				if ( wp_delete_post( $this->post_id, TRUE ) ) {
					return TRUE;
				}

			}

			if ( wp_trash_post( $this->post_id ) ) {
				return TRUE;
			}

		}

		return FALSE;

	}

	/**
	 * Post object.
	 *
	 * @return int|\WP_Error
	 */

	public function post_object() {

		do_action( 'wp_data_sync_before_post_object', $this->post_object );

		$this->post_object_defaults();
		$this->post_date_format();

		if ( wp_update_post( $this->post_object ) ) {
			do_action( 'wp_data_sync_after_post_object', $this->post_object );
		}

	}

	/**
	 * Post meta.
	 *
	 * @param $post_id
	 * @param $post_meta
	 */

	public function post_meta( $post_id, $post_meta ) {

		if ( is_array( $post_meta ) ) {

			$restricted_meta_keys = $this->restricted_meta_keys();

			foreach( $post_meta as $meta_key => $meta_value ) {

				$meta_key   = $this->post_meta_key( $meta_key, $meta_value );
				$meta_value = $this->post_meta_value( $meta_value, $meta_key );

				if ( ! in_array( $meta_key, $restricted_meta_keys ) ) {
					update_post_meta( $post_id, $meta_key, $meta_value );
				}

				do_action( "wp_data_sync_post_meta_$meta_key", $post_id, $meta_value, $this );

			}

		}

		do_action( 'wp_data_sync_post_meta', $post_id, $post_meta, $this );

	}

	/**
	 * Post meta Key.
	 *
	 * @param $meta_key
	 * @param $meta_value
	 * @param $post_id
	 *
	 * @return mixed|void
	 */

	public function post_meta_key( $meta_key, $meta_value ) {
		return apply_filters( 'wp_data_sync_meta_key', $meta_key, $meta_value, $this->post_id, $this );
	}

	/**
	 * Post meta value.
	 *
	 * @param $meta_value
	 * @param $meta_key
	 * @param $post_id
	 *
	 * @return mixed|void
	 */

	public function post_meta_value( $meta_value, $meta_key ) {
		return apply_filters( 'wp_data_sync_meta_value', $meta_value, $meta_key, $this->post_id, $this );
	}

	/**
	 * Set taxonomies.
	 *
	 * @param $post_id
	 * @param $taxonomies
	 */

	public function taxonomy( $post_id, $taxonomies ) {

		if ( ! is_array( $taxonomies ) ) {
			return;
		}

		foreach ( $taxonomies as $taxonomy => $terms ) {

			if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {

				Log::write( 'invalid-taxonomy', $taxonomy );

				continue;

			}

			$parent_id  = 0;
			$term_ids   = [];
			$parent_ids = [];
			$append     = ( 'true' === get_option( 'wp_data_sync_append_terms' ) ) ? TRUE : FALSE;

			foreach ( $terms as $term ) {

				if ( ! empty( $term['parents'] ) ) {

					foreach ( $term['parents'] as $parent ) {
						$parent_id = $this->term_id( $parent, $taxonomy, $parent_id );
					}

				}

				$term_ids[] = $this->term_id( $term['name'], $taxonomy, $parent_id );

			}

			wp_set_object_terms( $post_id, $term_ids, $taxonomy, $append );

		}

		do_action( 'wp_data_sync_taxonomies', $this->post_id, $this->taxonomies );

	}

	/**
	 * Term id.
	 *
	 * @param $term_name
	 * @param $taxonomy
	 * @param $parent_id
	 *
	 * @return int
	 */

	public function term_id( $term_name, $taxonomy, $parent_id ) {

		$term_name = apply_filters( 'wp_data_sync_term_name', $term_name, $taxonomy, $parent_id );
		$taxonomy  = apply_filters( 'wp_data_sync_taxonomy', $taxonomy, $term_name, $parent_id );

		$term = term_exists( $term_name, $taxonomy, $parent_id );

		if ( 0 === $term || NULL === $term ) {
			$term = wp_insert_term( $term_name, $taxonomy, [ 'parent' => $parent_id ] );
		}

		if ( is_wp_error( $term ) ) {
			return FALSE;
		}

		return intval( $term['term_id'] );

	}

	/**
	 * Set the post thumbnail.
	 *
	 * @param $post_id
	 * @param $post_thumbnail
	 */

	public function post_thumbnail( $post_id, $post_thumbnail ) {

		if ( $attach_id = $this->attachment( $post_id, $post_thumbnail ) ) {

			set_post_thumbnail( $post_id, $attach_id );

			do_action( 'wp_data_sync_post_thumbnail', $post_id, $post_thumbnail );

		}

	}

	/**
	 * Attachemnt.
	 *
	 * @param $post_id
	 * @param $image_url
	 *
	 * @return bool|int|\WP_Post
	 */

	public function attachment( $post_id, $image_url ) {

		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		$basename   = $this->basename( $post_id, $image_url );
		$post_title = preg_replace( '/\.[^.]+$/', '', $basename );

		if ( $attachment_id = $this->attachment_exists( $post_title ) ) {

			Log::write( 'attachemnt-exists', $post_title );

			return $attachment_id;

		}

		Log::write( 'attachemnt-file', $image_url );

		$file_type = wp_check_filetype( $basename );

		if ( FALSE !== strpos( $file_type['type'], 'image' ) ) {

			if ( $image_data = $this->fetch_image_data( $image_url ) ) {

				$upload_dir = wp_upload_dir();
				$file_path  = $this->file_path( $upload_dir, $basename );

				// Copy the image to image upload dir
				file_put_contents( $file_path, $image_data );

				$attachment = [
					'guid'           => "{$upload_dir['url']}/{$basename}",
					'post_mime_type' => $file_type['type'],
					'post_title'     => $post_title,
					'post_content'   => '',
					'post_status'    => 'inherit'
				];

				// Insert featured image data
				$attach_id = wp_insert_attachment( $attachment, $file_path, $post_id );

				if ( is_int( $attach_id ) && 0 < $attach_id ) {

					// Get metadata for featured image
					$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );

					// Update metadata
					wp_update_attachment_metadata( $attach_id, $attach_data );

					return $attach_id;

				}

			}

		}

		return FALSE;

	}

	/**
	 * Fetch image data from an image url.
	 *
	 * @param $image_url
	 *
	 * @return bool|string
	 */

	public function fetch_image_data( $image_url ) {

		if ( $response = wp_remote_get( $image_url ) ) {

			if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
				return wp_remote_retrieve_body( $response );
			}

		}

		return FALSE;

	}

	/**
	 * File path.
	 *
	 * @param $upload_dir
	 * @param $basename
	 *
	 * @return string
	 */

	public function file_path( $upload_dir, $basename ) {

		if( wp_mkdir_p( $upload_dir['path'] ) ) {
			return "{$upload_dir['path']}/{$basename}";
		}

		return "{$upload_dir['basedir']}/{$basename}";

	}

	/**
	 * Check to see if attachment exists.
	 *
	 * @param $post_title
	 *
	 * @return bool|\WP_Post
	 */

	public function attachment_exists( $post_title ) {

		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT ID
				FROM $wpdb->posts
				WHERE post_title = '%s'
				AND post_type = 'attachment'
				",
				$post_title
			)
		);

		if ( null === $row ) {
			return FALSE;
		}

		return $row->ID;

	}

	/**
	 * Basename.
	 *
	 * @param $post_id
	 * @param $image_url
	 *
	 * @return mixed|void
	 */

	public function basename( $post_id, $image_url ) {

		$basename = sanitize_file_name( basename( $image_url  ) );

		return apply_filters( 'wp_data_sync_basename', $basename, $post_id );

	}

	/**
	 * Format the post date for WordPress.
	 */

	public function post_date_format() {

		if ( ! empty( $this->post_object['post_date'] ) ) {

			// Convert the date to time.
			$post_time = strtotime( $this->post_object['post_date'] );

			$this->post_object['post_date'] = date( 'Y-m-d H:i:s', $post_time );

		}

	}

	/**
	 * Post object keys.
	 *
	 * @return array
	 */

	public function post_object_keys() {

		$post_object_keys = [
			'post_title',
			'post_status',
			'post_author',
			'post_type',
			'post_date',
			'post_content',
			'post_excerpt',
			'post_password',
			'post_parent',
			'ping_status',
			'comment_status'
		];

		return apply_filters( 'wp_data_sync_post_object_keys', $post_object_keys );

	}

	/**
	 * Filter Restricted Meta Keys.
	 *
	 * An array of restricted meta keys.
	 * Keys are restricted since their meta value may break other functionality.
	 *
	 * @return mixed|void
	 */

	public function restricted_meta_keys() {

		$restricted_meta_keys = [
			'_edit_lock',
			'_edit_last',
			'_thumbnail_id'
		];

		return apply_filters( 'wp_data_sync_restricted_meta_keys', $restricted_meta_keys );

	}

	/**
	 * Get the primary ID.
	 *
	 * @return int|bool
	 */

	public function get_primary_id() {
		return $this->primary_id;
	}

	/**
	 * Get the post ID.
	 *
	 * @return int|bool
	 */

	public function get_id() {
		return $this->post_id;
	}

	/**
	 * Get the post object.
	 *
	 * @return array|bool
	 */

	public function get_post_object() {
		return $this->post_object;
	}

	/**
	 * Get the post meta.
	 *
	 * @return array|bool
	 */

	public function get_post_meta() {
		return $this->post_meta;
	}

	/**
	 * Get the taxonomies.
	 *
	 * @return array|bool
	 */

	public function get_taxonomies() {
		return $this->taxonomies;
	}

	/**
	 * Get the post thumbnail.
	 *
	 * @return string|bool
	 */

	public function get_post_thumbnail() {
		return $this->post_thumbnail;
	}

	/**
	 * Get the attributes.
	 *
	 * @return array|bool
	 */

	public function get_attributes() {
		return $this->attributes;
	}

	/**
	 * Get variations.
	 *
	 * @return mixed|bool
	 */

	public function get_variations() {
		return $this->variations;
	}

	/**
	 * Get the product gallery.
	 *
	 * @return array|bool
	 */

	public function get_product_gallery() {
		return $this->product_gallery;
	}

	/**
	 * Get cross sells.
	 *
	 * @return array|bool
	 */

	public function get_cross_sells() {
		return $this->cross_sells;
	}

	/**
	 * Get up sells.
	 *
	 * @return array|bool
	 */

	public function get_up_sells() {
		return $this->up_sells;
	}

}