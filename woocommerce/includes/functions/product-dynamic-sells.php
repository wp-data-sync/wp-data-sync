<?php
/**
 * Product - Dynamic Sells
 *
 * @since   2.11.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\Woo;

use WP_DataSync\App\Settings;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maybe Update Cross-Sells
 *
 * @param int $product_id
 * @param string $value
 *
 * @return bool
 */
add_action( 'wp_data_sync_integration_woo_dynamic_cross_sells', function( $product_id, $value ) {
    update_post_meta( $product_id, "woo_dynamic_cross_sells_value", $value );
}, 10, 2 );

/**
 * Maybe Update Up-Sells
 *
 * @param int $product_id
 * @param string $value
 *
 * @return bool
 */
add_action( 'wp_data_sync_integration_woo_dynamic_up_sells', function( $product_id, $value ) {
    update_post_meta( $product_id, "woo_dynamic_up_sells_value", $value );
}, 10, 2 );

/**
 * Get Cross-Sell IDs
 *
 * @param array $sell_ids
 * @param WC_Product $product
 *
 * @return array
 */
add_filter( 'woocommerce_product_get_cross_sell_ids', function( $sell_ids, $product ) {
    return woo_dynamic_sells_ids( 'cross_sells', $sell_ids, $product );
}, 10, 2 );

/**
 * Get Up-Sell IDs
 *
 * @param array $sell_ids
 * @param WC_Product $product
 *
 * @return array
 */
add_filter( 'woocommerce_product_get_upsell_ids', function( $sell_ids, $product ) {
    return woo_dynamic_sells_ids( 'up_sells', $sell_ids, $product );
}, 10, 2 );

/**
 * @param string $key
 * @param array $sell_ids
 * @param WC_Product $product
 *
 * @return array
 */

function woo_dynamic_sells_ids( $key, $sell_ids, $product ) {

    global $wpdb;

    if ( ! Settings::is_checked( "wp_data_sync_dynamic_{$key}_is_active" ) ) {
        return $sell_ids;
    }

    $value = $product->get_meta( "woo_dynamic_{$key}_value" );

    if ( empty( $value ) ) {
        return $sell_ids;
    }

    switch( get_option( "woo_dynamic_{$key}_sort_order" ) ) {

        case 'random':
            $sort_order = 'RAND()';

        case 'newest':
            $sort_order = 'DESC';

        default:
            $sort_order = 'ASC';
    }

    $results = $wpdb->get_col( $wpdb->prepare(
        "
        SELECT p.ID
        FROM $wpdb->posts p
        INNER JOIN $wpdb->postmeta pm
            ON pm.post_id = p.ID
        WHERE p.ID != %d
            AND p.post_status = 'publish'
            AND p.post_type = 'product'
            AND pm.meta_key = 'woo_dynamic_{$key}_value'
            AND pm.meta_value LIKE %s     
        ORDER BY p.ID $sort_order
        LIMIT %d
        ",
        $product->get_id(),
        esc_sql( $value ),
        get_option( "woo_dynamic_{$key}_quantity", 3 )
    ) );

    if ( ! empty( $results ) && ! is_wp_error( $results ) ) {
        return $results;
    }

    return $sell_ids;

}

