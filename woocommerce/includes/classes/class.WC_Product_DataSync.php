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
use WC_Product;
use WC_Product_Variation;
use WC_Product_Attribute;

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
     * WC_Product_DataSync constructor.
     *
     * @param WC_Product $product
     * @param DataSync   $data_sync
     */

	public function __construct( $product, $data_sync ) {
        $this->set_product( $product );
        $this->set_data_sync( $data_sync );
	}

	/**
	 * Set Product
	 *
	 * @param WC_Product $product
	 */

	public function set_product( $product ) {
		$this->product = $product;
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

        if ( $this->data_sync->get_taxonomies() ) {
            $this->product_visibility();
        }

		if ( $this->data_sync->get_wc_categories() ) {
			$this->categories();
		}

		if ( $this->data_sync->get_attributes() ) {
			$this->attributes();
		}

        if ( $this->product->is_type( 'variable' ) || $this->product->is_type( 'variable-subscription' ) ) {
            $this->set_variations_inactive();

            if ( $this->data_sync->get_variations() ) {
                $this->variations();
            }
        }

	}

    /**
     * Prices
     *
     * @return void
     */

    public function prices() {

        $prices = $this->data_sync->get_wc_prices();

        extract( $prices );

        if ( isset( $_regular_price ) ) {
            $this->product->set_regular_price( $_regular_price );
        }

        if ( isset( $_sale_price ) ) {
            $this->product->set_sale_price( $_sale_price );
        }

        Log::write( 'wc-prices', [
            'product_id'    => $this->product->get_id(),
            'is_on_sale'    => $this->product->is_on_sale(),
            'price'         => $this->product->get_price(),
            'regular_price' => $this->product->get_regular_price(),
            'sale_price'    => $this->product->get_sale_price(),
            'api_prices'    => $prices
        ], 'Set WC Prices' );

    }

	/**
	 * Product categories.
	 *
	 * @return void
	 */

	public function categories() {

        $separator        = apply_filters( 'wc_data_sync_category_string_separator', ',' );
		$category_strings = explode( $separator, $this->data_sync->get_wc_categories() );

		if ( empty( $category_strings ) ) {
			return;
		}

        $term_ids  = [];
		$append    = Settings::is_true( 'wp_data_sync_append_terms' );
        $delimiter = apply_filters( 'wc_data_sync_category_string_delimiter', '>' );

		foreach ( $category_strings as $category_string ) {

			$parent_id = null;
			$_terms    = array_map( 'trim', explode( $delimiter, $category_string ) );
			$total     = count( $_terms );

			foreach ( $_terms as $index => $_term ) {

                $_term = apply_filters( 'wc_data_sync_category_term', $_term );

				if ( $term_id = $this->data_sync->term_id( $_term, 'product_cat', $parent_id ) ) {

					// Only requires assign the last category.
					if ( ( 1 + $index ) === $total ) {
						$term_ids[] = $term_id;
					} else {
						// Store parent to be able to insert or query categories based in parent ID.
						$parent_id = $term_id;
					}

				}

			}

		}

		Log::write( 'wc-dategories', [
			'product_id' => $this->product->get_id(),
			'strings'    => $category_strings,
			'term_ids'   => $term_ids
		] );

        $this->product->set_category_ids( $term_ids );

	}

	/**
	 * Product attributes.
	 */

	public function attributes() {

        if ( empty( $this->data_sync->get_attributes() ) ) {
            return;
        }

        $_attributes = [];
        $position    = 1;

        foreach ( $this->data_sync->get_attributes() as $args ) {

            extract( $args );

            $attribute = new WC_Product_Attribute();
            $taxonomy_id = 0;

            if ( $is_taxonomy ) {
                extract( $this->get_attribute( $name ) );
            }

            $attribute->set_id( $taxonomy_id );
            $attribute->set_name( $name );
            $attribute->set_position( $position );
            $attribute->set_visible( $is_visible );
            $attribute->set_variation( $is_variation );
            $attribute->set_options( $values );

            $_attributes[] = $attribute;
            $position ++;

        }

        $this->product->set_attributes( $_attributes );

	}

    /**
     * Attribute taxonomy.
     *
     * @param $raw_name
     *
     * @return array
     */

    public function get_attribute( $raw_name ) {

        // These are exported as labels, so convert the label to a name if possible first.
        $attribute_labels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
        $attribute_name   = array_search( $raw_name, $attribute_labels, true );

        if ( ! $attribute_name ) {
            $attribute_name = wc_sanitize_taxonomy_name( $raw_name );
        }

        $taxonomy_id   = wc_attribute_taxonomy_id_by_name( $attribute_name );
        $taxonomy_name = wc_attribute_taxonomy_name( $attribute_name );

        if ( $taxonomy_id ) {
            return [
                'taxonomy_id' => $taxonomy_id,
                'name'        => $taxonomy_name,
            ];
        }

        // If the attribute does not exist, create it.
        $taxonomy_id = wc_create_attribute( [
            'name'         => $raw_name,
            'slug'         => $attribute_name,
            'type'         => 'select',
            'order_by'     => 'menu_order',
            'has_archives' => false,
        ] );

        // Register as taxonomy while importing.
        register_taxonomy(
            $taxonomy_name,
            apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy_name, [ 'product' ] ),
            apply_filters( 'woocommerce_taxonomy_args_' . $taxonomy_name, [
                'labels'       => [
                    'name' => $raw_name,
                ],
                'hierarchical' => true,
                'show_ui'      => false,
                'query_var'    => true,
                'rewrite'      => false,
            ] )
        );

        return [
            'taxonomy_id' => $taxonomy_id,
            'name'        => $taxonomy_name,
        ];

    }

	/**
	 * Variations
	 *
	 * @return viod
	 */

	public function variations() {

		$_variations = $this->data_sync->get_variations();

		if ( is_array( $_variations ) ) {

			$data_sync = DataSync::instance();

			foreach ( $_variations as $i => $values ) {

                $values['post_data']['post_parent'] = $this->product->get_id();

                $data_sync->set_properties( $values );
                $data_sync->process();

                $variation = new WC_Product_Variation();

                $variation->set_id( $data_sync->get_post_id() );
                $variation->set_parent_id( $this->product->get_id() );
                $variation->set_status( 'publish' );
                $variation->set_menu_order( $i + 1 );

                extract( $data_sync->get_wc_prices() );

                if ( isset( $_regular_price ) ) {
                    $variation->set_regular_price( $_regular_price );
                }

                if ( isset( $_sale_price ) ) {
                    $variation->set_sale_price( $_sale_price );
                }

				if ( $selected_options =  $data_sync->get_selected_options() ) {

                    $attributes = [];

                    foreach ( $selected_options as $name => $value ) {

                        if ( $this->is_attribute_taxonomy( $name ) ) {
                            $name  = wc_sanitize_taxonomy_name( $name );
                            $name  = wc_attribute_taxonomy_name( $name );
                            $value = sanitize_title( $value );
                        }
                        else {
                            $name  = wc_sanitize_taxonomy_name( $name );
                        }

                        $attributes['attribute_' . $name  ] = $value;

                    }

                    $variation->set_attributes( $attributes );

				}

                $variation->save();

			}

		}

	}

    /**
     * Is Attribute Taxonomy
     *
     * @param $name
     *
     * @return false|mixed
     */

    public function is_attribute_taxonomy( $name ) {

        $attributes = $this->data_sync->get_attributes();

        if ( empty( $attributes ) ) {
            return false;
        }

        $i = array_search( $name, array_column( $attributes, 'name' ) );

        if ( false !== $i ) {
            return $attributes[ $i ]['is_taxonomy'];
        }

        return false;

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
            return;
		}

		$term       = NULL;
		$taxonomies = $this->data_sync->get_taxonomies();

		if ( is_array( $taxonomies ) && array_key_exists( 'product_visibility', $taxonomies ) ) {

			foreach( $taxonomies['product_visibility'] as $term_array ) {
				$term = $term_array['name'];
			}

		}

		if ( empty( $term ) ) {
			$term = get_option( 'wp_data_sync_product_visibility', 'visible' );
		}

        $allowed_terms = wc_get_product_visibility_options();

        if ( empty( $term ) || ! array_key_exists( $term, $allowed_terms ) ) {
            $term = 'visible';
        }

        $this->product->set_catalog_visibility( $term );

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
            [ 'post_parent' => $this->product->get_id() ]
        );

    }

    /**
     * Save
     *
     * @return void
     */

    public function save() {
        $this->product->save();
    }

}
