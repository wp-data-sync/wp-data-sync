<?php
/**
 * WC REST API
 *
 * WC REST API functions.
 *
 * @since   3.4.1
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\Woo;

use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add shop order data fields.
 *
 * @param WP_REST_Response $response
 *
 * @return array
 */

add_filter( 'woocommerce_rest_prepare_shop_order_object', function( WP_REST_Response $response ) {

    $extended_keys = [ 'vendor', 'upc', 'mpn', 'gtin8', 'isbn' ];

    $response_data = $response->get_data();

    if ( isset( $response_data['line_items'] ) ) {

        foreach ( $response_data['line_items'] as $i => $line_item ) {

            if ( $product = wc_get_product( $line_item['product_id'] ) ) {

                foreach ( $extended_keys as $extended_key ) {
                    $response_data['line_items'][ $i ][ $extended_key ] = $product->get_meta( "_$extended_key" );
                }

            }

        }

    }

    $response->set_data( $response_data );

    return $response;
} );