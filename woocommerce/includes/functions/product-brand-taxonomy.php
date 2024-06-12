<?php
/**
 * Product Brand Taxonomy
 *
 * Brand taxonomy for WooCommerce products.
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\Woo\App;

use Walker_Category_Checklist;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', function() {

	$args = apply_filters( 'wp_data_sync_brand_taxonomy_args', [
		'labels'             => brand_taxonomy_labels(),
		'hierarchical'       => true,
		'show_in_nav_menus'	 => false,
		'query_var'          => true,
		'public' 			 => true,
		'show_tagcloud'		 => false,
		'show_in_quick_edit' => true
	] );

	register_taxonomy( brand_taxonomy_key(), 'product', $args );

}, 1 );

/**
 * Brand taxonomy key.
 * 
 * @return mixed|void
 */

function brand_taxonomy_key() {
	return apply_filters( 'wp_data_sync_brand_taxonomy_key', 'brand' );
}

/**
 * Taxonomy labels.
 *
 * @return array
 */

function brand_taxonomy_labels() {

	return apply_filters( 'wp_data_sync_brand_taxonomy_labels', [
		'name'              => _x( 'Brands', 'Brands', 'wp-data-sync-woocommerce' ),
		'singular_name'     => _x( 'Brand', 'Brand', 'wp-data-sync-woocommerce' ),
		'search_items'      => __( 'Search Brands', 'wp-data-sync-woocommerce' ),
		'all_items'         => __( 'All Brands', 'wp-data-sync-woocommerce' ),
		'parent_item'       => __( 'Parent Brand', 'wp-data-sync-woocommerce' ),
		'parent_item_colon' => __( 'Parent Brand:', 'wp-data-sync-woocommerce' ),
		'edit_item'         => __( 'View Brand', 'wp-data-sync-woocommerce' ),
		'update_item'       => __( 'Update Brand', 'wp-data-sync-woocommerce' ),
		'add_new_item'      => __( 'Add New Brand', 'wp-data-sync-woocommerce' ),
		'new_item_name'     => __( 'New Brand Name', 'wp-data-sync-woocommerce' ),
		'menu_name'         => __( 'Brands', 'wp-data-sync-woocommerce' ),
	] );

}

/**
 * Brand taxonomy radio buttons.
 *
 * @param array $args
 * @param int $product_id
 *
 * @return mixed
 */

add_filter( 'wp_terms_checklist_args', function( $args, $product_id ) {

    if ( ! empty( $args['taxonomy'] ) &&  brand_taxonomy_key() === $args['taxonomy'] ) {

        // Don't override 3rd party walkers.
        if ( empty( $args['walker'] ) || is_a( $args['walker'], 'Walker' ) ) {

            class WPDS_Category_Radio extends Walker_Category_Checklist {

                public function start_el( &$output, $category, $depth = 0, $args = [], $id = 0 ) {

                    $output .= sprintf(
                        '<li id="%s-%s"><label class="selectit"><input value="%s" type="radio" name="tax_input[%s][]" id="in-%s-%s" %s %s/> %s</label></li>',
                        esc_attr( $args['taxonomy'] ),
                        esc_attr( $category->term_id ),
                        esc_attr( $category->term_id ),
                        esc_attr( $args['taxonomy'] ),
                        esc_attr( $args['taxonomy'] ),
                        esc_attr( $category->term_id ),
                        esc_attr( checked( in_array( $category->term_id, $args['selected_cats'] ), true, false ) ),
                        esc_attr( disabled( empty( $args['disabled'] ), false, false ) ),
                        esc_html( apply_filters( 'the_category', $category->name ) )
                    );

                }

            }

            $args['walker'] = new WPDS_Category_Radio;

        }

    }

    return $args;

}, 10, 2 );

/**
 * Remove Genesis category checklist toggle from brand taxonomy.
 */

add_action( 'admin_enqueue_scripts', function() {

    $theme = wp_get_theme();
    if ( 'genesis' !== $theme->get_template() ) {
        return;
    }

    $screen = get_current_screen();

    if ( 'product' === $screen->id ) {

        $taxonomy_key = brand_taxonomy_key();

        wp_add_inline_script( 'jquery',
            "
			setTimeout( function() {
				jQuery('#taxonomy-$taxonomy_key #genesis-category-checklist-toggle').remove();
			}, 1000 );
			"
        );

    }

}, 999 );
