<?php
/**
 * WooCommerce WP Data Sync
 *
 * Setup WP Data Sync for WooCommerce
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\Woo;

use WP_DataSync\App\DataSync;
use WC_Product;
use WC_Product_Factory;
use WP_DataSync\App\SyncRequest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Used to handle WooCommerce integration versions.
define( 'WCDSYNC_VERSION', '2.7.0' );

/**
 * Process WooCommerce.
 */

add_action( 'wp_data_sync_after_process_woo_product', function( $product_id, $data_sync ) {

    SyncRequest::$response['items'][ SyncRequest::$process_id ]['process_product'][] = 'start';

    if ( empty( $data_sync->get_product_type() ) ) {

        SyncRequest::$response['items'][ SyncRequest::$process_id ]['process_product'][] = 'empty product type';

        $product = wc_get_product( $product_id );

    }
    else {

        SyncRequest::$response['items'][ SyncRequest::$process_id ]['process_product'][] = 'has product type';

        // This is used if we have a product type to ensure we get the correct product class.
        $product_classname = WC_Product_Factory::get_product_classname( $product_id, $data_sync->get_product_type() );

        // Get the new product object from the correct classname.
        $product = new $product_classname( $product_id );
    }

    if ( $product instanceof WC_Product ) {

        $_product = new WC_Product_DataSync( $product, $data_sync );

        $_product->wc_process();

        SyncRequest::$response['items'][ SyncRequest::$process_id ]['process_product'][] = 'completed successfully';

        return;

    }

    SyncRequest::$response['items'][ SyncRequest::$process_id ]['process_product'][] = 'failed';

}, 10, 2 );

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
    }

}, 10, 2 );

/**
 * Shhedule the product sells events.
 *
 * @param array $sell_ids
 *
 * @return void
 */
add_action( 'wp_data_sync_schedule_product_sells_events', function( array $sell_ids ): void {

    $product_sells = WC_Product_Sells::instance();

    $rows = $product_sells->get_related_rows( $sell_ids );
    $i    = 1;

    foreach ( $rows as $row ) {
        if ( ! as_has_scheduled_action( 'wp_data_sync_process_product_sells', $row ) ) {
            as_schedule_single_action( time() + $i, 'wp_data_sync_process_product_sells', $row );
            $i = $i + 3;
        }
    }

} );

/**
 * Process the product sells event.
 *
 * @param int $product_id
 * @param string $meta_key
 *
 * @return void
 */
add_action( 'wp_data_sync_process_product_sells', function( int $product_id, string $meta_key ): void {

    if ( ! $product = wc_get_product( $product_id ) ) {
        return;
    }

    $product_sells = WC_Product_Sells::instance();

    if ( $args = $product->get_meta( $meta_key ) ) {

        $args['product'] = $product;;

        if ( $product_sells->set_properties( $args ) ) {
            $product_sells->save();
        }

    }

}, 10, 2 );

/**
 * WooCommerce ItemRequest
 */

add_filter( 'wp_data_sync_item', function( $item_data, $item_id, $item ) {

	if ( 'product' === $item->get_post_type() ) {
		return WC_Product_Item::instance()->wc_process( $item_data, $item_id );
	}

	return $item_data;

}, 10, 3 );

/**
 * Set Action Scheduler to Process Gallery Images
 *
 * @param int $product_id
 * @param DataSync $data_sync
 *
 * @return void
 */

add_action( 'wp_data_sync_process_gallery_woo_product', function( int $product_id, DataSync $data_sync ) {

    wc_schedule_single_action( time(), 'wp_data_sync_process_gallery_images', [
        'product_id'     => $product_id,
        'gallery_images' => $data_sync->get_gallery_images()
    ] );

}, 10, 2 );

/**
 * Process Gallery Images
 *
 * @param int $product_id
 * @param array $gallery_images
 *
 * @return void
 */

add_action( 'wp_data_sync_process_gallery_images', function( int $product_id, array $gallery_images ) {

    if ( $product = wc_get_product( $product_id ) ) {

        $data_sync = DataSync::instance();
        $data_sync->set_post_id( $product_id );
        $data_sync->set_gallery_images( $gallery_images );

        $attach_ids = [];

        foreach ( $gallery_images as $image ) {

            $image = apply_filters( 'wp_data_sync_product_gallery_image', $image, $product_id, $data_sync );

            $data_sync->set_attachment( $image );

            if ( $attach_id = $data_sync->attachment() ) {
                $attach_ids[] = $attach_id;
            }

        }

        $product->set_gallery_image_ids( $attach_ids );
        $product->save();

    }

}, 10, 2 );
