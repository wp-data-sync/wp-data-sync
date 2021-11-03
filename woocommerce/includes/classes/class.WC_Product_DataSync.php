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

use WP_DataSync\Api\App\Data;
use WP_DataSync\App\DataSync;
use WP_DataSync\App\Log;
use WP_DataSync\App\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Product_DataSync {

	/**
	 * @var DataSync
	 */

	private $data_sync;

	/**
	 * @var int
	 */

	private $product_id;

	/**
	 * @var WC_Product_DataSync
	 */

	private static $instance;

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
	 * Set Product ID
	 *
	 * @param int $product_id
	 */

	public function set_product_id( $product_id ) {
		$this->product_id = (int) $product_id;
	}

	/**
	 * Set Data Sync
	 *
	 * @param DataSync $data_sync
	 */

	public function set_data_sync( $data_sync ) {
		$this->data_sync = $data_sync;
	}

	/**
	 * WooCommerce process data.
	 */

	public function wc_process() {

		if ( $this->data_sync->get_attributes() ) {
			$this->attributes();
			$this->data_sync->reset_term_taxonomy_count();
		}

		if ( has_term( 'variable', 'product_type', $this->porduct_id ) ) {
			$this->set_variations_inactive();
		}

		if ( $this->data_sync->get_variations() ) {
			$this->variations();
		}

		if ( $this->data_sync->get_gallery_images() ) {
			$this->gallery_images();
		}

		if ( $this->data_sync->get_taxonomies() ) {
			$this->product_visibility();
		}

	}

	/**
	 * Product attributes.
	 */

	public function attributes() {

		$attributes = $this->data_sync->get_attributes();

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

				wp_set_object_terms( $this->product_id, $term_ids, $taxonomy );

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

		update_post_meta( $this->product_id, '_product_attributes', $product_attributes );

		do_action( 'wp_data_sync_attributes', $this->product_id, $product_attributes );

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

			if ( ! empty( $value ) ) {

				if( $term_id = $this->data_sync->set_term( [ 'name' => $value ], $taxonomy ) ) {
					$term_ids[] = $term_id;
				}

			}

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
	 * Set variations inactive.
	 *
	 * We want to set all variations inactive.
	 * Later when variations are updated,
	 * we will set only current variations active.
	 */

	public function set_variations_inactive() {

		global $wpdb;

		$wpdb->update(
			$wpdb->posts,
			[ 'post_status' => 'private' ],
			[ 'post_parent' => $this->product_id ]
		);

	}

	/**
	 * Variations.
	 *
	 * @link https://woocommerce.github.io/code-reference/classes/WC-Product-Variation.html
	 *
	 * @throws \WC_Data_Exception
	 */

	public function variations() {

		$variations = $this->data_sync->get_variations();

		if ( is_array( $variations ) ) {

			$data_sync = DataSync::instance();
			$parent_id = $this->product_id;

			foreach ( $variations as $variation ) {

				// Set the parent ID for the variation.
				$variation['post_data']['post_parent'] = $parent_id;

				if ( ! isset( $variation['post_data']['post_status'] ) ) {
					$variation['post_data']['post_status'] = 'publish';
				}

				$data_sync->set_properties( $variation );
				$data_sync->process();

				$variation_id = $data_sync->get_post_id();

				Log::write( 'variation', $variation_id, 'Variation ID' );
				Log::write( 'variation', $parent_id, 'Variation Parent ID' );
				Log::write( 'variation', $variation, 'Variation Data' );

			}

		}

	}

	/**
	 * Gallery images.
	 *
	 * @since 1.6.0
	 */

	public function gallery_images() {

		$gallery_images = $this->data_sync->get_gallery_images();
		$attach_ids     = [];

		foreach ( $gallery_images as $image ) {

			$image = apply_filters( 'wp_data_sync_product_gallery_image', $image, $this->product_id );

			$this->data_sync->set_attachment( $image );

			if ( $attach_id = $this->data_sync->attachment() ) {
				$attach_ids[] = $attach_id;
			}

		}

		$gallery_ids = apply_filters( 'wp_data_sync_gallery_image_ids', $attach_ids, $this->product_id );
		$gallery_key = apply_filters( 'wp_data_sync_gallery_image_meta_key', '_product_image_gallery', $this->product_id );

		update_post_meta( $this->product_id, $gallery_key, join( ',', $gallery_ids ) );

		do_action( 'wp_data_sync_gallery_images', $this->product_id, $gallery_images );

	}

	/**
	 * Product visibility.
	 *
	 * @since 1.0.0
	 * @since 1.10.4
	 */

	public function product_visibility() {

		/**
		 * Should we preserve the current product visibility?
		 */
		if ( Settings::is_checked( 'wp_data_sync_use_current_product_visibility' ) ) {

			// Check for any product visibility.
			if ( has_term( '', 'product_visibility', $this->product_id ) ) {
				return;
			}

		}

		$taxonomies = $this->data_sync->get_taxonomies();

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

		wp_set_object_terms( $this->product_id, $term, 'product_visibility' );

	}

}