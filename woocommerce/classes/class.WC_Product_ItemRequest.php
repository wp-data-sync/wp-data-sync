<?php
/**
 * WC_Product_ItemRequest
 *
 * Request WooCommerce product data
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\Woo;

use WP_DataSync\App\ItemRequest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Product_ItemRequest {

	/**
	 * @var WC_Product
	 */

	private $product;

	/**
	 * @var int
	 */

	private $product_id;

	/**
	 * @var ItemRequest
	 */

	private $item_request;

	/**
	 * @var WC_Product_ItemRequest
	 */

	public static $instance;

	/**
	 * WC_Product_ItemRequest constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * @return WC_Product_ItemRequest
	 */

	public static function instance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * WC Process.
	 *
	 * @param $item_data
	 * @param $product_id
	 * @param $item_request ItemRequest
	 *
	 * @return mixed
	 */

	public function wc_process( $item_data, $product_id, $item_request ) {

		$this->product_id   = $product_id;
		$this->product      = wc_get_product( $product_id );
		$this->item_request = $item_request;

		if ( $images = $this->gallery_images() ) {
			$item_data['gallery_images'] = $images;
		}

		if ( $attributes = $this->product_attributes() ) {
			$item_data['attributes'] = $attributes;
		}

		if ( $this->product->is_type( 'variable' ) ) {

			if ( $variations = $this->product_variations() ) {
				$item_data['variations'] = $variations;
			}

		}

		return $item_data;

	}

	/**
	 * Gallery images.
	 *
	 * @since 1.6.0
	 *
	 * @return array|bool
	 */

	public function gallery_images() {

		$image_ids  = $this->product->get_gallery_image_ids();
		$image_urls = [];
		$i          = 1;

		if ( empty ( $image_ids ) ) {
			return FALSE;
		}

		foreach ( $image_ids as $image_id ) {

			$image_urls["image_$i"] = [
				'image_url'   => wp_get_attachment_image_url( $image_id, 'full' ),
				'title'       => get_the_title( $image_id ) ?: '',
				'description' => get_the_content( $image_id ) ?: '',
				'caption'     => get_the_excerpt( $image_id ) ?: '',
				'alt'         => get_post_meta( $image_id, '_wp_attachment_image_alt', TRUE ) ?: ''
			];

			$i++;

		}

		return $image_urls;

	}

	/**
	 * Product attributes.
	 *
	 * @return array|bool
	 */

	public function product_attributes() {

		if ( $product_attributes = get_post_meta( $this->product_id, '_product_attributes', TRUE ) ) {

			$attributes = [];

			foreach ( $product_attributes as $attribute ) {

				$slug = wc_attribute_taxonomy_slug( $attribute['name'] );

				$attributes[ $slug ] = $attribute;

				if ( $attribute['is_taxonomy'] ) {

					$attributes[ $slug ]['name']   = wc_attribute_label( $attribute['name'] );
					$value                         = $this->product->get_attribute( $attribute['name'] );
					$attributes[ $slug ]['values'] = $this->explode( $value );

				} else {
					$attributes[ $slug ]['values'] = $this->explode( $attribute['value'] );
				}

				unset( $attributes[ $slug ]['value'] );

			}

			return array_filter( $attributes );

		}

		return FALSE;

	}

	/**
	 * Explode.
	 *
	 * @param $value
	 *
	 * @return array
	 */

	public function explode( $value ) {

		$replace = [ '\\,', '|' ];
		$value   = str_replace( $replace, ',', $value );

		return array_map( 'trim', explode( ',', $value ) );

	}

	/**
	 * Product variations.
	 *
	 * @return bool|array
	 */

	public function product_variations() {

		$variations    = [];
		$variation_ids = $this->product->get_children();
		$i             = 1;

		if ( empty( $variation_ids ) ) {
			return FALSE;
		}

		foreach ( $variation_ids as $variation_id ) {

			$variation               = [];
			$variation['primary_id'] =  [
				'meta_key'   => '_source_item_id',
				'meta_value' => $variation_id,
				'search_in'  => 'post_meta',
				'post_id'    => $variation_id
			];

			$variation['post_data']  = $this->item_request->get_post( $variation_id );
            $variation['post_meta']  = $this->item_request->post_meta( $variation_id );

            if ( has_post_thumbnail( $variation_id ) ) {
	            $variation['featured_image'] = $this->item_request->featured_image( $variation_id );
            }

			$variations["variation_$i"] = array_filter( $variation );

            $i++;

		}

		return $variations;

	}

}