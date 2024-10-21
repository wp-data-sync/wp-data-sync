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
use WP_DataSync\App\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', function () {

    if ( ! Settings::is_checked( 'wp_data_sync_product_brand_taxonomy' ) ) {
        return;
    }

    $args = apply_filters( 'wp_data_sync_brand_taxonomy_args', [
        'labels'             => apply_filters( 'wp_data_sync_brand_taxonomy_labels', [
            'name'              => _x( 'Brands', 'Brands', 'wp-data-sync' ),
            'singular_name'     => _x( 'Brand', 'Brand', 'wp-data-sync' ),
            'search_items'      => __( 'Search Brands', 'wp-data-sync' ),
            'all_items'         => __( 'All Brands', 'wp-data-sync' ),
            'parent_item'       => __( 'Parent Brand', 'wp-data-sync' ),
            'parent_item_colon' => __( 'Parent Brand:', 'wp-data-sync' ),
            'edit_item'         => __( 'View Brand', 'wp-data-sync' ),
            'update_item'       => __( 'Update Brand', 'wp-data-sync' ),
            'add_new_item'      => __( 'Add New Brand', 'wp-data-sync' ),
            'new_item_name'     => __( 'New Brand Name', 'wp-data-sync' ),
            'menu_name'         => __( 'Brands', 'wp-data-sync' ),
        ] ),
        'hierarchical'       => true,
        'show_in_nav_menus'  => false,
        'query_var'          => true,
        'public'             => true,
        'show_tagcloud'      => false,
        'show_in_quick_edit' => true
    ] );

    register_taxonomy( product_brand_taxonomy_key(), 'product', $args );

}, 1 );

/**
 * Brand taxonomy radio buttons.
 *
 * @param array $args
 * @param int $product_id
 *
 * @return mixed
 */

add_filter( 'wp_terms_checklist_args', function ( $args, $product_id ) {

    if ( ! Settings::is_checked( 'wp_data_sync_product_brand_taxonomy' ) ) {
        return $args;
    }

    if ( ! empty( $args['taxonomy'] ) && product_brand_taxonomy_key() === $args['taxonomy'] ) {

        // Don't override 3rd party walkers.
        if ( empty( $args['walker'] ) || is_a( $args['walker'], 'Walker' ) ) {

            class WPDS_Category_Radio extends Walker_Category_Checklist {

                public function start_el( &$output, $data_object, $depth = 0, $args = [], $current_object_id = 0 ) {

                    $output .= sprintf(
                        '<li id="%s-%s"><label class="selectit"><input value="%s" type="radio" name="tax_input[%s][]" id="in-%s-%s" %s %s/> %s</label></li>',
                        esc_attr( $args['taxonomy'] ),
                        esc_attr( $data_object->term_id ),
                        esc_attr( $data_object->term_id ),
                        esc_attr( $args['taxonomy'] ),
                        esc_attr( $args['taxonomy'] ),
                        esc_attr( $data_object->term_id ),
                        esc_attr( checked( in_array( $data_object->term_id, $args['selected_cats'] ), true, false ) ),
                        esc_attr( disabled( empty( $args['disabled'] ), false, false ) ),
                        esc_html( apply_filters( 'the_category', $data_object->name ) )
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

add_action( 'admin_enqueue_scripts', function () {

    if ( ! Settings::is_checked( 'wp_data_sync_product_brand_taxonomy' ) ) {
        return;
    }

    $theme = wp_get_theme();
    if ( 'genesis' !== $theme->get_template() ) {
        return;
    }

    $screen = get_current_screen();

    if ( 'product' === $screen->id ) {

        $taxonomy_key = product_brand_taxonomy_key();

        wp_add_inline_script( 'jquery',
            "
			setTimeout( function() {
				jQuery('#taxonomy-$taxonomy_key #genesis-category-checklist-toggle').remove();
			}, 1000 );
			"
        );

    }

}, 999 );

/**
 * Product Brand Taxonomy Key
 *
 * @return string
 */

function product_brand_taxonomy_key() {
    return apply_filters( 'wp_data_sync_brand_taxonomy_key', 'brand' );
}