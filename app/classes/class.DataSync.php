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

class DataSync extends Core {

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
	 * Process request data.
	 *
	 * @param $data
	 *
	 * @return mixed
	 */

	public function process( $data ) {

		/**
		 * Extract
		 *
		 * $primary_id
		 * $post_object
		 * $taxonomies
		 * $post_thumbnail
		 */

		extract( $data );

		// A post object is required!!
		if ( empty( $post_object ) ) {
			return [ 'post_object' => 'Empty post object' ];
		}

		// Check to see if we already have this post.
		$post_object = $this->post_exists( $post_object, $primary_id );

		if ( $this->maybe_trash_post( $post_object ) ) {
			return [ 'post_object' => 'Trash Post' ];
		}

		$post_object = $this->post_object_defaults( $post_object );
		$post_object = $this->post_date_format( $post_object );

		if ( $post_id = $this->post_object( $post_object ) ) {

			if ( isset(  $taxonomies ) ) {
				$this->taxonomy( $post_id, $taxonomies );
			}

			if ( isset( $post_thumbnail ) ) {
				$this->post_thumbnail( $post_id, $post_thumbnail );
			}

			do_action( 'wp_data_sync_post_meta', $post_id, $post_object['meta_input'] );
			do_action( 'wp_data_sync_after_process', $post_id, $data, $this );

			return [ 'post_id' => $post_id ];

		}

	}

	/**
	 * Get the post ID.
	 *
	 * @param $post_object
	 * @param $primary_id
	 *
	 * @return mixed
	 */

	public function post_exists( $post_object, $primary_id ) {

		if ( $post_id = $this->post_id( $primary_id ) ) {
			$post_object['ID'] = $post_id;
		}

		return $post_object;

	}

	/**
	 * Get the default value for a post object key.
	 *
	 * @param $post_object
	 * @param $key
	 *
	 * @return mixed|void
	 */

	public function post_object_defaults( $post_object ) {

		$keys = $this->post_object_keys();

		foreach ( $keys as $key ) {

			$value = isset( $post_object[$key] ) ? $post_object[$key] : get_option( "wp_data_sync_{$key}", '' );

			$post_object[$key] = apply_filters( "wp_data_sync_{$key}", $value );

		}

		return $post_object;

	}

	/**
	 * Trash post.
	 *
	 * @param $post_object
	 * @param $post_id
	 *
	 * @return bool
	 */

	public function maybe_trash_post( $post_object ) {

		if ( 0 < $post_object['ID'] && 'trash' === $post_object['post_status'] ) {

			if ( 'true' === get_option( 'wp_data_sync_force_delete' ) ) {

				if ( wp_delete_post( $post_object['ID'], TRUE ) ) {
					return TRUE;
				}

			}

			if ( wp_trash_post( $post_object['ID'] ) ) {
				return TRUE;
			}

		}

		return FALSE;

	}

	/**
	 * Post object.
	 *
	 * @param $post_object
	 *
	 * @return int|\WP_Error
	 */

	public function post_object( $post_object ) {

		do_action( 'wp_data_sync_before_post_object', $post_object );

		if ( $post_id = wp_insert_post( $post_object ) ) {

			$post_object['ID'] = $post_id;

			do_action( 'wp_data_sync_after_post_object', $post_object );

			return $post_id;

		}

		return FALSE;

	}

	/**
	 * Add taxonomies to the object.
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
						$parent_ids[] = $parent_id = $this->term_id( $parent, $taxonomy, $parent_id );
					}

				}

				$term_ids[] = $this->term_id( $term['name'], $taxonomy, $parent_id );

			}

			$ids = array_merge( $term_ids, $parent_ids );

			wp_set_object_terms( $post_id, $ids, $taxonomy, $append );

		}

		do_action( 'wp_data_sync_taxonomies', $post_id, $taxonomies );

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

		return intval( $term['term_id'] );

	}

	/**
	 * Set the post thumbnail
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
	 * Attachment.
	 *
	 * @param $post_id
	 * @param $image_url
	 *
	 * @return bool|int|\WP_Error|\WP_Post
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
				SELECT post_id
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

		return $row->post_id;

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
	 *
	 * @param $post_object
	 *
	 * @return mixed
	 */

	public function post_date_format( $post_object ) {

		if ( ! empty( $post_object['post_date'] ) ) {

			// Convert the date to time.
			$post_time = strtotime( $post_object['post_date'] );

			$post_object['post_date'] = date( 'Y-m-d H:i:s', $post_time );

		}

		return $post_object;

	}

	/**
	 * Post object keys.
	 *
	 * @return array
	 */

	public function post_object_keys() {

		return [
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

	}

}