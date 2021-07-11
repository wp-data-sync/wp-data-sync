<?php
/**
 * WC_Product_DataSync
 *
 * WP Data Sync for WooCommerce methods
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\Woo;

use WC_Product;
use WP_DataSync\App\DataSync;
use WP_DataSync\App\Log;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Product_DataSync {

	/**
	 * @var DataSync
	 */

	private $data_sync;

	/**
	 * @var WC_Product
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
		$this->product   = wc_get_product( $product_id );

		if ( $post_meta = $this->data_sync->get_post_meta() ) {
			$this->price( $product_id, $post_meta );
		}

		if ( $attributes = $this->data_sync->get_attributes() ) {
			$this->attributes( $product_id, $attributes );
		}

		if ( $this->product->is_type( 'variable' ) ) {
			$this->set_variations_inactive( $product_id );
		}

		if ( $variations = $this->data_sync->get_variations() ) {
			$this->variations( $product_id, $variations );
		}

		if ( $gallery_images = $this->data_sync->get_gallery_images() ) {
			$this->gallery_images( $product_id, $gallery_images );
		}

		if ( $taxonomies = $this->data_sync->get_taxonomies() ) {
			$this->product_visibility( $taxonomies );
		}

	}

	/**
	 * Price.
	 *
	 * @param $product_id
	 * @param $post_meta
	 */

	public function price( $product_id, $post_meta ) {

		extract( $post_meta );

		if ( isset( $_regular_price ) ) {

			LOg::write( 'product-price', "Product ID: $product_id Regular Price: $_regular_price" );

			$this->data_sync->post_meta( $product_id, [ '_regular_price' => $_regular_price ] );

			if ( ! empty( $_regular_price ) ) {
				$this->data_sync->post_meta( $product_id, [ '_price' => $_regular_price ] );
			}

		}

		if ( isset( $_sale_price ) ) {

			LOg::write( 'product-price', "Product ID: $product_id Sale Price: $_sale_price" );

			$this->data_sync->post_meta( $product_id, [ '_sale_price' => $_sale_price ] );

			if ( ! empty( $_sale_price ) ) {
				$this->data_sync->post_meta( $product_id, [ '_price' => $_sale_price ] );
			}

		}

		if ( isset( $_price ) ) {

			LOg::write( 'product-price', "Product ID: $product_id Price: $_price" );

			if ( ! empty( $_price ) ) {
				$this->data_sync->post_meta( $product_id, [ '_price' => $_price ] );
			}

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

			if ( $is_taxonomy ) {

				$taxonomy = $this->attribute_taxonomy( $name );
				$term_ids = $this->attribute_term_ids( $taxonomy, $attribute );

				wp_set_object_terms( $product_id, $term_ids, $taxonomy );

			}

			$product_attributes[ $is_taxonomy ? $taxonomy : $name ] = [
				'name'         => $is_taxonomy ? $taxonomy : $name,
				'value'        => $is_taxonomy ? $values : join( '|', $values ),
				'position'     => $position,
				'is_visible'   => (int) $is_visible,
				'is_variation' => (int) $is_variation,
				'is_taxonomy'  => (int) $is_taxonomy
			];

		}

		update_post_meta( $product_id, '_product_attributes', $product_attributes );

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
			$term_ids[] = $this->data_sync->set_term( [ 'name' => $value ], $taxonomy, 0 );
		}

		Log::write('test-value', $term_ids);

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

		return $taxonomy_name;

	}

	/**
	 * Set variations inactive.
	 *
	 * We want to set all variations inactive.
	 * Later when variations are updated,
	 * we will set only current variations active.
	 *
	 * @param $product_id
	 */

	public function set_variations_inactive( $product_id ) {

		global $wpdb;

		$wpdb->update(
			$wpdb->posts,
			[ 'post_status' => 'private' ],
			[ 'post_parent' => $product_id ]
		);

	}

	/**
	 * Variations.
	 *
	 * @link https://woocommerce.github.io/code-reference/classes/WC-Product-Variation.html
	 *
	 * @param $product_id
	 * @param $variations
	 *
	 * @throws \WC_Data_Exception
	 */

	public function variations( $product_id, $variations ) {

		if ( is_array( $variations ) ) {

			$data_sync = DataSync::instance();

			foreach ( $variations as $variation ) {

				// Set the parent ID for the variation.
				$variation['post_data']['post_parent'] = $product_id;

				if ( ! isset( $variation['post_data']['post_status'] ) ) {
					$variation['post_data']['post_status'] = 'publish';
				}

				Log::write( 'variation', $variation );

				$data_sync->set_properties( $variation );
				$result = $data_sync->process();

				Log::write( 'variation', 'Variation ID: ' . $result['post_id'] );

			}

		}

	}

	/**
	 * Gallery images.
	 *
	 * @since 1.6.0
	 *
	 * @param $product_id
	 * @param $gallery_images
	 */

	public function gallery_images( $product_id, $gallery_images ) {

		$attach_ids = [];

		foreach ( $gallery_images as $image ) {

			$image = apply_filters( 'wp_data_sync_product_gallery_image', $image, $product_id );

			if ( $attach_id = $this->data_sync->attachment( $product_id, $image ) ) {
				$attach_ids[] = $attach_id;
			}

		}

		$gallery_ids = apply_filters( 'wp_data_sync_gallery_image_ids', $attach_ids, $product_id );
		$gallery_key = apply_filters( 'wp_data_sync_gallery_image_meta_key', '_product_image_gallery', $product_id );

		update_post_meta( $product_id, $gallery_key, join( ',', $gallery_ids ) );

		do_action( 'wp_data_sync_gallery_images', $product_id, $gallery_images );

	}

	/**
	 * Product visibility.
	 *
	 * @param $taxonomies
	 */

	public function product_visibility( $taxonomies ) {

		if ( is_array( $taxonomies ) && array_key_exists( 'product_visibility', $taxonomies ) ) {

			foreach( $taxonomies['product_visibility'] as $term_array ) {
				$term = $term_array['name'];
			}

		}

		Log::write( 'product-visibility', "API Term: $term " );

		if ( empty( $term ) ) {

			$term = get_option( 'wp_data_sync_product_visibility', 'visible' );

			Log::write( 'product-visibility', "Default Term: $term " );

		}

		$this->product->set_catalog_visibility( $term );
		$this->product->save();

	}

}