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
use WC_Product_Variable;
use WC_Product_Attribute;
use WC_Product_Variation;
use WP_DataSync\App\DataSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Product_DataSync {

	/**
	 * @var DataSync
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
		$this->product   = $this->data_sync->get_variations() ? new WC_Product_Variable( $product_id ) : new WC_Product( $product_id );

		if ( $attributes = $this->data_sync->get_attributes() ) {
			$this->attributes( $product_id, $attributes );
		}

		if ( $variations = $this->data_sync->get_variations() ) {
			$this->variations( $product_id, $variations );
		}

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

			$product_attribute->set_name( $name );
			$product_attribute->set_options( $values );
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

			foreach ( $variations as $variation ) {

				extract( $variation );

				$_variation = new WC_Product_Variation();

				$_variation->set_parent_id( $product_id );

				if ( ! empty( $post_thumbnail ) ) {
					$attach_id = $this->data_sync->attachment( 0, $post_thumbnail );
					$_variation->set_image_id( $attach_id );
				}

				// Extract the Post Object
				extract( $post_object );

				// Check to see if we already have this variation
				if ( $exists = get_page_by_title( $post_title, 'OBJECT', 'product_variation' ) ) {
					$_variation->set_id( $exists->ID );
				}

				if ( ! empty( $post_title ) ) {
					$_variation->set_name( $post_title );
				}

				if ( ! empty( $post_name ) ) {
					$_variation->set_slug( $post_name );
				}

				if ( ! empty( $post_excerpt ) ) {
					$_variation->set_short_description( $post_excerpt );
				}
				if ( ! empty( $post_status ) ) {
					$_variation->set_status( $post_status );
				}

				if ( ! empty( $post_date ) ) {
					$_variation->set_date_created( $post_date );
				}

				if ( ! empty( $menu_order ) ) {
					$_variation->set_menu_order( $menu_order );
				}

				extract( $post_meta );

				if ( ! empty( $_sku ) ) {
					$_variation->set_sku( $_sku );
				}

				if ( ! empty( $_price ) ) {
					$_variation->set_price( $_price );
				}

				if ( ! empty( $_regular_price ) ) {
					$_variation->set_regular_price( $_regular_price );
				}

				if ( ! empty( $_sale_price ) ) {
					$_variation->set_sale_price( $_sale_price );
				}

				if ( ! empty( $_variation_description ) ) {
					$_variation->set_description( $_variation_description );
				}

				if ( ! empty( $_manage_stock ) ) {
					$_variation->set_manage_stock( $_manage_stock );
				}

				if ( ! empty( $_back_orders ) ) {
					$_variation->set_backorders( $_back_orders );
				}

				if ( ! empty( $_sold_individually ) ) {
					$_variation->set_sold_individually( $_sold_individually );
				}

				if ( ! empty( $_virtual ) ) {
					$_variation->set_virtual( $_virtual );
				}

				if ( ! empty( $_downloadable ) ) {
					$_variation->set_downloadable( $_downloadable );
				}

				if ( ! empty( $_stock ) ) {
					$_variation->set_stock( $_stock );
				}

				if ( ! empty( $_stock_status ) ) {
					$_variation->set_stock_status( $_stock_status );
				}

				if ( ! empty( $_length ) ) {
					$_variation->set_length( $_length );
				}

				if ( ! empty( $_width ) ) {
					$_variation->set_width( $_width );
				}

				if ( ! empty( $_height ) ) {
					$_variation->set_height( $_height );
				}

				if ( ! empty( $_weight ) ) {
					$_variation->set_weight( $_weight );
				}

				// Attributes
				if ( ! empty( $attributes ) ) {
					$_variation->set_default_attributes( $attributes );
					$_variation->set_attributes( $attributes );
				}

				$_variation->save();

			}

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