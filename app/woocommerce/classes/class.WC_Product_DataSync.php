<?php
/**
 * WC_Product_DataSync
 *
 * WP Data Sync for WooCommerce methods
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

use WC_Product;
use WC_Product_Variable;
use WC_Product_Attribute;

class WC_Product_DataSync {

	/**
	 * @var object
	 */

	private $data_sync;

	/**
	 * @var object
	 */

	private $product;

	/**
	 * @var WC_Product_DataSync
	 */

	public static $instance;

	/**
	 * WC_Product_DataSync constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * Instance.
	 *
	 * @return WC_Product_DataSync
	 */

	public static function instance() {

		if ( self::$instance === NULL ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * WooCommerce process data.
	 *
	 * @param $product_id
	 * @param $data_sync
	 */

	public function wc_process( $product_id, $data_sync ) {

		$this->data_sync = $data_sync;
		$this->product   = $this->variations ? new WC_Product_Variable( $product_id ) : new WC_Product( $product_id );;

		if ( $attributes = $this->data_sync->get_attributes() ) {
			$this->attributes( $product_id, $attributes );
		}

		//if ( $this->variations = $this->data_sync->get_variations() ) {
			//$this->variations( $product_id, $attributes );
		//}

		if ( $product_gallery = $this->data_sync->get_product_gallery() ) {
			$this->product_gallery( $product_id, $product_gallery );
		}

		if ( $taxonomies = $this->data_sync->get_taxonomies() ) {
			$this->product_visibility( $product_id, $taxonomies );
		}

	}

	/**
	 * Product attributes.
	 *
	 * @param $product_id
	 * @param $attributes
	 */

	public function attributes( $product_id, $attributes ) {

		if ( empty( $attributes ) ) {
			return;
		}

		$product_attributes = [];
		$position = 1;
		$attributes = apply_filters( 'wp_data_sync_product_attributes', $attributes );

		foreach ( $attributes as $attribute ) {

			extract( $attribute );

			$product_attribute = new WC_Product_Attribute();

			if ( $is_taxonomy ) {
				$taxonomy = $this->attribute_taxonomy( $name );
				$term_ids = $this->attribute_term_ids( $taxonomy, $attribute );
			}

			$product_attribute->set_name( $is_taxonomy ? $taxonomy : $name );
			$product_attribute->set_options( $is_taxonomy ? $term_ids : join( ',', $values ) );
			$product_attribute->set_position( $position );
			$product_attribute->set_visible( $is_visible );
			$product_attribute->set_variation( $is_variation );

			$product_attributes[ $is_taxonomy ? $taxonomy : $name ] = $product_attribute;

			$position++;

		}

		$this->product->set_attributes( $product_attributes );

		$this->product->save();

		do_action( 'wp_data_sync_attributes', $product_id, $product_attributes );

	}

	/**
	 * Get the attribute term ids.
	 *
	 * @param $taxonomy
	 * @param $attribute
	 *
	 * @return array
	 */

	public function attribute_term_ids( $taxonomy, $attribute ) {

		extract( $attribute );

		$term_ids   = [];

		foreach ( $values as $value ) {
			$term_ids[] = $this->data_sync->term_id( $value, $taxonomy, 0 );
		}

		return $term_ids;

	}

	/**
	 * Attribute taxonomy.
	 *
	 * @param $raw_name
	 *
	 * @return string
	 */

	public function attribute_taxonomy( $raw_name ) {

		// These are exported as labels, so convert the label to a name if possible first.
		$attribute_labels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
		$attribute_name   = array_search( $raw_name, $attribute_labels, TRUE );

		if ( ! $attribute_name ) {
			$attribute_name = wc_sanitize_taxonomy_name( $raw_name );
		}

		$attribute_id  = wc_attribute_taxonomy_id_by_name( $attribute_name );
		$taxonomy_name = wc_attribute_taxonomy_name( $attribute_name );

		if ( $attribute_id ) {
			return $taxonomy_name;
		}

		// If the attribute does not exist, create it.
		$attribute_id = wc_create_attribute( [
			'name'         => $raw_name,
			'slug'         => $attribute_name,
			'type'         => 'select',
			'order_by'     => 'menu_order',
			'has_archives' => FALSE,
		] );

		// Register as taxonomy while importing.
		register_taxonomy(
			$taxonomy_name,
			apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy_name, [ 'product' ] ),
			apply_filters( 'woocommerce_taxonomy_args_' . $taxonomy_name, [
				'labels'       => [
					'name' => $raw_name,
				],
				'hierarchical' => TRUE,
				'show_ui'      => FALSE,
				'query_var'    => TRUE,
				'rewrite'      => FALSE,
			] )
		);

		return $taxonomy_name;;

	}

	public function variations( $product_id, $attributes ) {
		// Create variations

		// Add variation meta from $attributes

	}

	/**
	 * Variation.
	 *
	 * @param $product_id
	 * @param $attribute
	 * @param $taxonomy
	 *
	 * @return int|\WP_Error
	 */

	public function variation( $product_id, $attribute, $taxonomy ) {

		extract( $attribute );

		foreach ( $values as $key => $value ) {

			$variation_id = $this->create_variation( $product_id, $value );

			$this->variation_meta( $variation_id, $meta_values[ $key ] );

			Log::write( 'variation-id', $variation_id );

			if ( $is_taxonomy ) {
				$this->variation_term( $variation_id, $value, $taxonomy );
			}

		}

	}

	/**
	 * Variation.
	 *
	 * @param $product_id
	 *
	 * @return int|\WP_Error
	 */

	public function create_variation( $product_id, $value ) {

		$product = wc_get_product( $product_id );

		if( $product->is_type( 'subscrption' ) ) {
			wp_set_object_terms( $product_id, 'variable_subscription', 'product_type' );
		}
		else {
			wp_set_object_terms( $product_id, 'variable', 'product_type' );
		}

		$post_title = $product->get_name() . ' ' . $value;

		$variation_post = [
			'post_title'  => $post_title,
			'post_name'   => sanitize_title( $post_title ),
			'post_status' => 'publish',
			'post_parent' => $product_id,
			'post_type'   => 'product_variation',
			'guid'        => $product->get_permalink()
		];

		// Check to see if we already have this variation
		if ( $variation = get_page_by_title( $post_title, 'OBJECT', 'product_variation' ) ) {
			$variation_post['ID'] = $variation->ID;
		}

		return wp_insert_post( $variation_post );

	}

	/**
	 * Variation term.
	 *
	 * @param $variation_id
	 * @param $value
	 * @param $taxonomy
	 */

	public function variation_term( $variation_id, $value, $taxonomy ) {

		Log::write( 'variation-term', $value );

		$term = get_term_by( 'name', $value, $taxonomy );

		Log::write( 'variation-term', $term );

		if ( isset( $term->slug ) ) {

			update_post_meta( $variation_id, "attribute_$taxonomy", $term->slug );

			// https://github.com/woocommerce/woocommerce/issues/12718
			// FIXME: Do we really need this?
			wp_set_object_terms( $variation_id, $term->term_id, $taxonomy );

		}

	}

	/**
	 * Variation Meta.
	 *
	 * @param $variation_id
	 * @param $meta_values
	 */

	public function variation_meta( $variation_id, $meta_values ) {

		if ( empty( $meta_values ) ) {
			return;
		}

		if ( ! is_array( $meta_values ) ) {
			return;
		}

		foreach ( $meta_values as $meta_key => $meta_value ) {

			if ( '_variation_image' === $meta_key ) {

				if ( $attach_id = $this->data_sync->attachment( $variation_id, $meta_value ) ) {
					set_post_thumbnail( $variation_id, $attach_id );
				}

			}

			update_post_meta( $variation_id, $meta_key, $meta_value );

		}

	}

	/**
	 * Create a WooCommerce image gallery.
	 *
	 * @param $product_id
	 * @param $product_gallery
	 */

	public function product_gallery( $product_id, $product_gallery ) {

		$attach_ids = [];

		foreach ( $product_gallery as $image_url ) {

			$image_url = apply_filters( 'wp_data_sync_product_gallery_image_url', $image_url );

			if ( $attach_id = $this->data_sync->attachment( $product_id, $image_url ) ) {
				$attach_ids[] = $attach_id;
			}

		}

		$product_gallery_ids = apply_filters( 'wp_data_sync_product_gallery_ids', join( ',', $attach_ids ) );
		$product_gallery_key = apply_filters( 'wp_data_sync_product_gallery_meta_key', '_product_image_gallery' );

		update_post_meta( $product_id, $product_gallery_key, $product_gallery_ids );

		do_action( 'wp_data_sync_product_gallery', $product_id, $product_gallery );

	}

	/**
	 * Product visibility.
	 *
	 * @param $product_id
	 * @param $taxonomies
	 */

	public function product_visibility( $product_id, $taxonomies ) {

		$taxonomy = 'product_visibility';

		if ( is_array( $taxonomies ) && array_key_exists( $taxonomy, $taxonomies ) ) {
			return;
		}

		if ( $terms = get_option( 'wp_data_sync_product_visibility' ) ) {

			$term_ids = [];

			if ( 'null' !== $terms ) {

				$terms = explode( ',', $terms );

				foreach ( $terms as $term ) {
					$term_ids[] = $this->data_sync->term_id( $term, $taxonomy, 0 );
				}

			}

			wp_set_object_terms( $product_id, $term_ids, $taxonomy );

		}

	}

}