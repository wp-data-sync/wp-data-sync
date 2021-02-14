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

namespace WP_DataSync\Woo;

use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;
use WC_Product_Grouped;
use WC_Product_External;
use WC_Product_Subscription_Legacy;
use WC_Product_Subscription_Variation;
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
	 * @var WC_Product|WC_Product_Variable
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
		$this->product   = $this->product( $product_id );

		if ( $attributes = $this->data_sync->get_attributes() ) {
			$this->attributes( $product_id, $attributes );
		}

		if ( $variations = $this->data_sync->get_variations() ) {
			$this->variations( $product_id, $variations );
		}

		/**
		 * @since 1.6.0 Deprecated
		 * @use   WC_Product_DataSync::gallery_images instaed
		 */
		if ( $product_gallery = $this->data_sync->get_product_gallery() ) {
			$this->product_gallery( $product_id, $product_gallery );
		}

		if ( $gallery_images = $this->data_sync->get_gallery_images() ) {
			$this->gallery_images( $product_id, $gallery_images );
		}

		if ( $taxonomies = $this->data_sync->get_taxonomies() ) {
			$this->product_visibility( $taxonomies );
		}

	}

	/**
	 * Product.
	 *
	 * @param $product_id
	 *
	 * @return WC_Product|WC_Product_External|WC_Product_Grouped|WC_Product_Simple|WC_Product_Subscription_Legacy|WC_Product_Subscription_Variation|WC_Product_Variable
	 */

	public function product( $product_id ) {

		if ( $this->data_sync->get_variations() ) {
			return new WC_Product_Variable( $product_id );
		}

		if ( $taxonomies = $this->data_sync->get_taxonomies() ) {

			if ( isset( $taxonomies['product_type'] ) ) {

				if ( array_key_exists( 'simple', $taxonomies['product_type'] ) ) {
					return new WC_Product_Simple( $product_id );
				}

				if ( array_key_exists( 'external', $taxonomies['product_type'] ) ) {
					return new WC_Product_External( $product_id );
				}

				if ( array_key_exists( 'grouped', $taxonomies['product_type'] ) ) {
					return new WC_Product_Grouped( $product_id );
				}

				if ( array_key_exists( 'variable', $taxonomies['product_type'] ) ) {
					return new WC_Product_Variable( $product_id );
				}

				if ( array_key_exists( 'subscription', $taxonomies['product_type'] ) ) {

					if ( class_exists( 'WC_Product_Subscription_Legacy' ) ) {
						return new WC_Product_Subscription_Legacy( $product_id );
					}

				}

				if ( array_key_exists( 'variable-subscription', $taxonomies['product_type'] ) ) {

					if ( class_exists( 'WC_Product_Subscription_Variation' ) ) {
						return new WC_Product_Subscription_Variation( $product_id );
					}

				}

			}

		}

		return new WC_Product( $product_id );

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
				'value'        => $is_taxonomy ? $values : join( ',', $values ),
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

				// Se the parent ID for the variation.
				$variation['post_data']['parent'] = $product_id;

				$data_sync->set_properties( $variation );
				$data_sync->process();

			}

		}

	}

	/**
	 * Create a WooCommerce image gallery.
	 *
	 * @since 1.6.0 Deprecated
	 * @use   WC+Product_DataSync::gallery_images instaed
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

		$product_gallery_ids = apply_filters( 'wp_data_sync_product_gallery_images__ids', join( ',', $attach_ids ), $product_id );
		$product_gallery_key = apply_filters( 'wp_data_sync_product_gallery_images_meta_key', '_product_image_gallery', $product_id );

		update_post_meta( $product_id, $product_gallery_key, $product_gallery_ids );

		do_action( 'wp_data_sync_product_gallery_images', $product_id, $gallery_images );

	}

	/**
	 * Product visibility.
	 *
	 * @param $taxonomies
	 */

	public function product_visibility( $taxonomies ) {

		Log::write( 'product-visibility', $taxonomies );

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