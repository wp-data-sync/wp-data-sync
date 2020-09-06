<?php
/**
 * Item Updated
 *
 * Remove wp_data_sync_item_synced meta when item is updated or trashed.
 *
 * @since   1.2.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'post_updated', 'WP_DataSync\App\item_updated', 20, 1 );
add_action( 'trashed_post', 'WP_DataSync\App\item_updated', 20, 1 );
add_action( 'untrash_post', 'WP_DataSync\App\item_updated', 20, 1 );

function item_updated( $post_id ) {

	if ( get_post_meta( $post_id, ItemRequest::sync_key, TRUE ) ) {
		delete_post_meta( $post_id, ItemRequest::sync_key );
	}

}