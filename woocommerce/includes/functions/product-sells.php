<?php
/**
 * WooCommerce Product Sells
 *
 * @since   2.8.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\Woo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schedule WooCommerce Cross-Sells.
 *
 * @param int $product_id
 * @param array $values
 *
 * @return void
 */

add_action( 'wp_data_sync_integration_woo_cross_sells', function( int $product_id, array $values ): void {

    if ( ! $product = wc_get_product( $product_id ) ) {
        return;
    }

    $args = array_merge( [
        'product' => $product,
        'type'    => 'cross',
    ], $values );

    $product_sells = WC_Product_Sells::instance();

    if ( $product_sells->set_properties( $args ) ) {
        $product_sells->save();
        $product_sells->save_event();
    }

}, 10, 2 );

/**
 * Schedule WooCommence Upsells.
 *
 * @param int $product_id
 * @param array $values
 *
 * @return void
 */

add_action( 'wp_data_sync_integration_woo_up_sells', function( int $product_id, array $values ): void {

    if ( ! $product = wc_get_product( $product_id ) ) {
        return;
    }

    $args = array_merge( [
        'product' => $product,
        'type'    => 'up',
    ], $values );

    $product_sells = WC_Product_Sells::instance();

    if ( $product_sells->set_properties( $args ) ) {
        $product_sells->save();
        $product_sells->save_event();
    }

}, 10, 2 );

/**
 * Process the product sells action.
 *
 * @return void
 */
add_action( 'wp_data_sync_process_product_sells_action', function(): void {

    $product_sells = WC_Product_Sells::instance();
    $event         = $product_sells->get_event();

    if ( empty( $event ) ) {
        return;
    }

    extract( $event );

    $product_sells->delete_event( $id );

    foreach ( $product_sells->get_related_rows( $sell_ids ) as $row ) {

        /**
         * Extract
         *
         * $product_id
         * $meta_key
         */
        extract( $row );

        if ( ! $product = wc_get_product( $product_id ) ) {
            continue;
        }

        if ( $args = $product->get_meta( $meta_key ) ) {

            $args['product'] = $product;;

            if ( $product_sells->set_properties( $args ) ) {
                $product_sells->save();
            }

        }

    }

} );
