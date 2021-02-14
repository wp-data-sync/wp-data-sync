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
	 * @var bool
	 */

	private $is_accelerated = FALSE;

	/**
	 * @var bool|array
	 */

	private $post_data = FALSE;

	/**
	 * @var bool|array
	 */

	private $post_meta = FALSE;

	/**
	 * @var bool|array
	 */

	private $taxonomies = FALSE;

	/**
	 * @var bool|array
	 */

	private $featured_image = FALSE;

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

	private $gallery_images = FALSE;

	/**
	 * @var bool|array
	 */

	private $integrations = FALSE;

	/**
	 * @var bool
	 */

	private $is_new = FALSE;

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
	 * Set Properties
	 *
	 * Set property values.
	 *
	 * @param $data
	 */

	public function set_properties( $data ) {

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
			return [ 'error' => 'Primary ID empty!!' ];
		}

		// Set the post ID.
		if ( ! $this->post_id = $this->set_post_id() ) {
			return [ 'error' => 'Post ID failed!!' ];
		}

		$this->post_data['ID'] = $this->post_id;

		if ( $this->maybe_trash_post() ) {
			return [ 'success' => 'Trash Post' ];
		}

		if ( $this->post_data ) {
			$this->post_data();
		}

		if ( $this->post_meta ) {
			$this->post_meta( $this->post_id, $this->post_meta );
		}

		if ( $this->taxonomies ) {
			$this->taxonomy( $this->post_id, $this->taxonomies );
		}

		if ( $this->featured_image ) {
			$this->set_featured_image();
		}

		if ( $this->integrations ) {
			$this->integrations();
		}

		do_action( 'wp_data_sync_after_process', $this->post_id, $this );

		if ( $this->taxonomies || $this->attributes ) {
			$this->reset_term_taxonomy_count();
		}

		return [ 'post_id' => $this->post_id ];

	}

	/**
	 * Set Post ID.
	 *
	 * @return bool|int
	 */

	private function set_post_id() {

		// This is only used to migrate content from cloned sites with the exact same post IDs.
		// This will create a new post if the post types do not match.
		if ( 'post_id' === $this->primary_id['search_in'] ) {

			$post_id = (int) $this->primary_id['post_id'];

			if ( get_post_status( $post_id ) && $this->post_data['post_type'] === get_post_type( $post_id ) ) {
				return $post_id;
			}

		}

		if ( $post_id = $this->post_id( $this->primary_id ) ) {
			return $post_id;
		}

		return FALSE;

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

		extract( $primary_id );

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
				$meta_key,
				$meta_value
			)
		);

		if ( null === $post_id || is_wp_error( $post_id ) ) {

			// Do not create a new post if accelerated sync.
			if ( $this->is_accelerated ) {
				return FALSE;
			}

			$this->is_new = TRUE;

			$post_id = wp_insert_post( [
				'post_title'  => __( 'WP Data Sync Placeholder', 'wp-data-sync' ),
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

	public function post_data_defaults() {

		$keys = $this->post_data_keys();

		foreach ( $keys as $key ) {

			$value = isset( $this->post_data[ $key ] ) ? $this->post_data[ $key ] : get_option( "wp_data_sync_{$key}", '' );

			$this->post_data[ $key ] = apply_filters( "wp_data_sync_{$key}", $value, $this->post_id, $this );

		}

	}

	/**
	 * Trash post.
	 *
	 * @return bool
	 */

	public function maybe_trash_post() {

		if ( 0 < $this->post_id && 'trash' === $this->post_data['post_status'] ) {

			if ( Settings::is_true( 'wp_data_sync_force_delete' ) ) {

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

	public function post_data() {

		do_action( 'wp_data_sync_before_post_data', $this->post_data );

		if ( $this->is_new ) {
			$this->post_data_defaults();
		}

		$this->post_date_format();

		if ( wp_update_post( $this->post_data ) ) {
			do_action( 'wp_data_sync_after_post_data', $this->post_data );
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

			$taxonomy = trim( wp_unslash( $taxonomy ) );

			if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {

				Log::write( 'invalid-taxonomy', $taxonomy );

				continue;

			}

			$parent_id  = 0;
			$term_ids   = [];
			$parent_ids = [];
			$append     = Settings::is_true( 'wp_data_sync_append_terms' );

			foreach ( $terms as $term ) {

				if ( ! empty( $term['parents'] ) ) {

					foreach ( $term['parents'] as $parent ) {
						$parent_id = $this->set_term( $parent, $taxonomy, $parent_id );
					}

				}

				$term_ids[] = $this->set_term( $term, $taxonomy, $parent_id );

				// Reset parent ID.
				$parent_id = 0;

			}

			Log::write( 'term-id', $term_ids );

			wp_set_object_terms( $post_id, $term_ids, $taxonomy, $append );

		}

		do_action( 'wp_data_sync_taxonomies', $post_id, $taxonomies );

	}

	/**
	 * Set term..
	 *
	 * @param $term array
	 * @param $taxonomy string
	 * @param $parent_id int
	 *
	 * @return int|bool
	 */

	public function set_term( $term, $taxonomy, $parent_id ) {

		if ( ! is_array( $term ) ) {
			return FALSE;
		}

		/**
		 * Extract.
		 *
		 * $name
		 * $description
		 * $thumb_url
		 * $term_meta
		 */
		extract( $term );

		$name = trim( wp_unslash( $name ) );

		Log::write( 'term-id', "$name - $taxonomy - $parent_id" );

		$name     = apply_filters( 'wp_data_sync_term_name', $name, $taxonomy, $parent_id );
		$taxonomy = apply_filters( 'wp_data_sync_taxonomy', $taxonomy, $name, $parent_id );

		if ( ! $term_id = $this->term_exists( $name, $taxonomy, $parent_id ) ) {

			$term = wp_insert_term( $name, $taxonomy, [ 'parent' => $parent_id ] );

			$term_id = (int) $term['term_id'];

		}

		Log::write( 'term-id', $term_id );

		$this->term_desc( $description, $term_id, $taxonomy );
		$this->term_thumb( $thumb_url, $term_id );
		$this->term_meta( $term_meta, $term_id );

		return $term_id;

	}

	/**
	 * Term exists.
	 *
	 * @param $name
	 * @param $taxonomy
	 * @param $parent_id
	 *
	 * @return bool|int
	 */

	public function term_exists( $name, $taxonomy, $parent_id ) {

		global $wpdb;

		Log::write( 'term-exists', "Name: $name - Taxonomy: $taxonomy - Parent ID: $parent_id" );

		$sql = $wpdb->prepare(
			"
			SELECT SQL_NO_CACHE t.term_id
			FROM $wpdb->terms t
			INNER JOIN $wpdb->term_taxonomy tt
			ON tt.term_id = t.term_id
			WHERE t.name = %s
			AND tt.taxonomy = %s
			AND tt.parent = %d
			",
			$name,
			$taxonomy,
			$parent_id
		);

		Log::write( 'term-exists', $sql );

		$term_id = $wpdb->get_var( $sql );

		if ( null === $term_id || is_wp_error( $term_id ) ) {
			Log::write( 'term-exists', 'Term Does Not Exist' );
			Log::write( 'term-exists', $term_id );
			return FALSE;
		}

		Log::write( 'term-exists', "Term ID: $term_id" );

		return (int) $term_id;

	}

	/**
	 * Term description.
	 *
	 * @param $description string
	 * @param $term_id     int
	 * @param $taxonomy    string
	 */

	public function term_desc( $description, $term_id, $taxonomy ) {

		if ( ! Settings::is_true( 'wp_data_sync_sync_term_desc' ) ) {
			return;
		}

		if ( empty( $description ) ) {
			$description = '';
		}

		$args = [ 'description' => $description ];

		wp_update_term( $term_id, $taxonomy, $args );

	}

	/**
	 * term thumb.
	 *
	 * @param $thumb_url
	 * @param $term_id
	 */

	public function term_thumb( $thumb_url, $term_id ) {

		if ( ! Settings::is_true( 'wp_data_sync_sync_term_thumb' ) ) {
			return;
		}

		if ( ! $attach_id = $this->attachment( $this->post_id, $thumb_url ) ) {
			$attach_id = '';
		}

		update_term_meta( $term_id, 'thumbnail_id', $attach_id );

	}

	/**
	 * Term meta.
	 *
	 * @param $term_meta
	 * @param $term_id
	 */

	public function term_meta( $term_meta, $term_id ) {

		if ( ! Settings::is_true( 'wp_data_sync_sync_term_meta' ) ) {
			return;
		}

		if ( is_array( $term_meta ) && ! empty( $term_meta ) ) {

			$restricted_meta_keys = $this->restricted_meta_keys();

			foreach ( $term_meta as $meta_key => $value ) {

				if ( ! in_array( $meta_key, $restricted_meta_keys ) ) {
					update_term_meta( $term_id, $meta_key, $value );
				}

			}

		}

	}

	/**
	 * Reset the term taxonomy count.
	 *
	 * @since 1.4.22
	 *
	 * @link https://stackoverflow.com/questions/18669256/how-to-update-wordpress-taxonomiescategories-tags-count-field-after-bulk-impo
	 */

	public function reset_term_taxonomy_count() {

		global $wpdb;

		$wpdb->query(
			"
			UPDATE $wpdb->term_taxonomy tt SET count = (
				SELECT COUNT(*) FROM $wpdb->term_relationships tr 
    			LEFT JOIN $wpdb->posts p ON (p.ID = tr.object_id) 
    			WHERE 
        		tr.term_taxonomy_id = tt.term_taxonomy_id 
        		AND 
        		tt.taxonomy NOT IN ('link_category')
        		AND 
        		p.post_status IN ('publish', 'future')
			)
			"
		);

	}

	/**
	 * Set the featured image.
	 *
	 * @since 1.6.0
	 */

	public function set_featured_image() {

		if ( $attach_id = $this->attachment( $this->post_id, $this->featured_image ) ) {

			set_post_thumbnail( $this->post_id, $attach_id );

			do_action( 'wp_data_sync_featured_image', $this->post_id, $this->featured_image );

		}

	}

	/**
	 * Attachemnt.
	 *
	 * @param int    $post_id
	 * @param string $image
	 *
	 * @return bool|int|\WP_Post
	 */

	public function attachment( $post_id, $image ) {

		Log::write( 'attachment', $image );

		$image = $this->image( $image, $post_id );

		extract( $image );

		Log::write( 'attachment', "Image URL: $image_url" );

		if ( empty( $image_url ) ) {
			return FALSE;
		}

		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		if ( ! $image_url = $this->is_valid_image_url( $image_url ) ) {
			return FALSE;
		}

		$basename    = $this->basename( $post_id, $image );
		$image_title = preg_replace( '/\.[^.]+$/', '', $basename );

		Log::write( 'attachment', "Basename: $basename" );
		Log::write( 'attachment', "Image Title: $image_title" );

		$attachment = [
			'post_title'   => empty( $name ) ? $image_title : $name,
			'post_content' => $description,
			'post_excerpt' => $caption
		];

		if ( $attachment['ID'] = $this->attachment_exists( $image_url, $attachment['post_title'] ) ) {

			Log::write( 'attachment', "Exists: {$attachment['ID']} - {$attachment['post_title']}" );

			// Update the attachement
			wp_update_post( $attachment );

			// Update image alt
			update_post_meta( $attachment['ID'], '_wp_attachment_image_alt', $alt );

			// Update the image source URL.
			update_post_meta( $attachment['ID'], '_source_url', $image_url );

			return $attachment['ID'];

		}

		if ( $file_type = $this->file_type( $image_url ) ) {

			if ( $image_data = $this->fetch_image_data( $image_url ) ) {

				$upload_dir = wp_upload_dir();
				$file_path  = $this->file_path( $upload_dir, $basename );

				Log::write( 'attachment', "File Path: $file_path" );

				// Copy the image to image upload dir
				file_put_contents( $file_path, $image_data );

				$attachment = array_merge( [
					'guid'           => "{$upload_dir['url']}/{$basename}",
					'post_mime_type' => $file_type,
					'post_status'    => 'inherit'
				], $attachment );

				// Insert image data
				$attach_id = wp_insert_attachment( $attachment, $file_path, $post_id );

				Log::write( 'attachment', "Attachment ID: $attach_id" );

				if ( is_int( $attach_id ) && 0 < $attach_id ) {

					// Get metadata for featured image
					$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );

					Log::write( 'attachment', $attach_data );

					// Update metadata
					wp_update_attachment_metadata( $attach_id, $attach_data );

					// Update image alt
					update_post_meta( $attach_id, '_wp_attachment_image_alt', $alt );

					// Update the image source URL.
					update_post_meta( $attach_id, '_source_url', $image_url );

					return $attach_id;

				}

			}

		}

		return FALSE;

	}

	/**
	 * Is Valid Image URL.
	 *
	 * @param $image_url
	 *
	 * @return mixed|void
	 */

	public function is_valid_image_url( $image_url ) {

		// Check for a valid URL.
		if ( FALSE !== filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
			return apply_filters( 'wp_data_sync_is_valid_image_url', $image_url );
		}

		Log::write( 'attachment', "Invalid URL: $image_url" );

		return FALSE;

	}

	/**
	 * File type.
	 *
	 * @param $image_url
	 *
	 * @return bool|mixed|string
	 */

	public function file_type( $image_url ) {

		$file_type = wp_check_filetype( $image_url );

		if ( ! empty( $file_type['type'] ) ) {

			Log::write( 'attachment', "File Type: {$file_type['type']}" );

			return $file_type['type'];

		}

		$file_type = FALSE;

		if ( $type = exif_imagetype( $image_url ) ) {

			switch ( $type ) {

				case IMAGETYPE_JPEG :
					$file_type = 'image/jpeg';
					break;

				case IMAGETYPE_PNG :
					$file_type = 'image/png';
					break;

				case IMAGETYPE_GIF :
					$file_type = 'image/gif';
					break;

			}

		}

		Log::write( 'attachment', "File Type: $file_type" );

		return $file_type;

	}

	/**
	 * Fetch image data from an image url.
	 *
	 * @param $image_url
	 *
	 * @return bool|string
	 */

	public function fetch_image_data( $image_url ) {

		$response = wp_remote_get( $image_url, [ 'sslverify' => $this->ssl_verify() ] );

		Log::write( 'attachment', $response );

		if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			return wp_remote_retrieve_body( $response );
		}

		return FALSE;

	}

	/**
	 * Verify if SSL certificate is valid.
	 *
	 * @return bool
	 */

	public function ssl_verify() {

		if ( Settings::is_checked( 'wp_data_sync_allow_unsecure_images' ) ) {
			return FALSE;
		}

		return TRUE;

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
	 * @since 1.6.0 Query _source_url
	 *
	 * @param $image_url
	 * @param $post_title
	 *
	 * @return bool|int
	 */

	public function attachment_exists( $image_url, $post_title ) {

		global $wpdb;

		$attach_id = $wpdb->get_var( $wpdb->prepare(
			"
			SELECT post_id
			FROM $wpdb->postmeta
			WHERE meta_key = '_source_url'
			AND meta_value = %s
			",
			$image_url
		) );

		if ( null !== $attach_id && 0 < $attach_id && ! is_wp_error( $attach_id ) ) {
			return (int) $attach_id;
		}

		/**
		 * TODO: remove the query below once all images have a saved _source_url
		 */

		$attach_id = $wpdb->get_var( $wpdb->prepare(
			"
			SELECT ID
			FROM $wpdb->posts
			WHERE post_title = %s
			AND post_type = 'attachment'
			",
			$post_title
		) );

		if ( null === $attach_id || is_wp_error( $attach_id ) ) {
			return FALSE;
		}

		return (int) $attach_id;

	}

	/**
	 * Basename.
	 *
	 * @param int   $post_id
	 * @param array $image
	 *
	 * @return mixed|void
	 */

	public function basename( $post_id, $image ) {

		$basename = sanitize_file_name( basename( $image['image_url'] ) );

		return apply_filters( 'wp_data_sync_basename', $basename, $post_id, $image );

	}

	/**
	 * Image.
	 *
	 * @param array  $image
	 * @param int    $post_id
	 *
	 * @return mixed|void
	 */

	public function image( $image, $post_id ) {

		if ( ! is_array( $image ) ) {

			$image = [
				'image_url'   => $image,
				'title'       => '',
				'description' => '',
				'caption'     => '',
				'alt'         => ''
			];

		}

		return apply_filters( 'wp_data_sync_image', $image, $post_id );

	}

	/**
	 * Format the post date for WordPress.
	 */

	public function post_date_format() {

		if ( ! empty( $this->post_data['post_date'] ) ) {

			// Convert the date to time.
			$post_time = strtotime( $this->post_data['post_date'] );

			$this->post_data['post_date'] = date( 'Y-m-d H:i:s', $post_time );

		}

	}

	/**
	 * Post object keys.
	 *
	 * @return array
	 */

	public function post_data_keys() {

		$post_data_keys = [
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

		return apply_filters( 'wp_data_sync_post_data_keys', $post_data_keys );

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
			'_thumbnail_id',
			'thumbnail_id',
			'product_count_product_cat'
		];

		return apply_filters( 'wp_data_sync_restricted_meta_keys', $restricted_meta_keys );

	}

	/**
	 * Integrations.
	 */

	private function integrations() {

		foreach ( $this->integrations as $integration => $values ) {
			do_action( "wp_data_sync_integration_$integration", $this->post_id, $values, $this );
		}

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

	public function get_post_data() {
		return $this->post_data;
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
	 * Get post type.
	 *
	 * @return mixed
	 */

	public function get_post_type() {
		return isset( $this->post_data['post_type'] ) ? $this->post_data['post_type'] : get_option( 'wp_data_sync_post_type' );
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
	 * Get featured image.
	 *
	 * @return array|bool
	 */

	public function get_featured_image() {
		return $this->featured_image;
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
	 * @since 1.6.0 Deprecated
	 * @use   DataSync::get_gallery_images instaed
	 *
	 * @return array|bool
	 */

	public function get_product_gallery() {
		return $this->product_gallery;
	}

	/**
	 * Get gallery images.
	 *
	 * @return array|bool
	 */

	public function get_gallery_images() {
		return $this->gallery_images;
	}

	/**
	 * Get Integrations.
	 *
	 * @return array|bool
	 */

	public function get_integrations() {
		return $this->integrations;
	}

}