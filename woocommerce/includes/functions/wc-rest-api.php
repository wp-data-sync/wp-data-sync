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

    /**
     * Add extended product keys to line items.
     */
    $extended_keys = [ 'vendor', 'upc', 'mpn', 'gtin8', 'isbn' ];

    $response_data = $response->get_data();

    if ( isset( $response_data['line_items'] ) ) {

        foreach ( $response_data['line_items'] as $i => $line_item ) {

            if ( $product = wc_get_product( $line_item['product_id'] ) ) {

                foreach ( $extended_keys as $extended_key ) {
                    $response_data['line_items'][ $i ][ $extended_key ] = $product->get_meta( "_$extended_key" );
                }

                $brands = wc_get_object_terms( $product->get_id(), 'product_brand', 'name' );
                $response_data['line_items'][ $i ]['brand'] = join( ', ', $brands );
            }

        }

    }

    /**
     * Add full_name to billing and shipping.
     */
    $response_data['billing']['full_name'] = trim( sprintf( '%s %s',
        $response_data['billing']['first_name'],
        $response_data['billing']['last_name']
    ) );

    $response_data['shipping']['full_name'] = trim( sprintf( '%s %s',
        $response_data['shipping']['first_name'],
        $response_data['shipping']['last_name']
    ) );

    /**
     * Populate shipping fields with billing data if they are empty.
     */

    foreach ( $response_data['shipping'] as $key => $value ) {
        if ( empty( $value ) ) {
            $response_data['shipping'][ $key ] = $response_data['billing'][ $key ];
        }
    }
    
    $response_data = apply_filters( 'wp_data_sync_shop_order_response_data', $response_data );

    $response->set_data( $response_data );

    return $response;
} );