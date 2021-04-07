<?php
/**
 * Item Updated
 *
 * Delete item ID from DB when item is updated, trashed or untrashed.
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
add_action( 'set_object_terms', 'WP_DataSync\App\item_updated', 20, 1 );

/**
 * Delete item ID from DB.
 *
 * @param $item_id
 */

function item_updated( $item_id ) {
	ItemRequest::delete_id( $item_id );
}
