<?php
/**
 * Update Duplicate Fields
 *
 * @since   2.1.10
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function() {

	if ( ! $fields = get_option( 'wp_data_sync_update_duplicate_fields' ) ) {
		return;
	}

	if ( empty( $fields ) ) {
		return;
	}

	if ( ! is_array( $fields ) ) {
		return;
	}

	if ( 1 === count( $fields ) && 'none' === $fields[0] ) {
		return;
	}

	foreach ( $fields as $meta_key ) {

        /**
         * Update duplicate meta fields.
         *
         * @param string $meta_key
         * @param mixed $meta_value
         * @param DataSync $data_sync
         *
         * @return void
         */
        
		add_action( "wp_data_sync_duplicate_post_meta_$meta_key", function( $meta_key, $meta_value, $data_sync ) {

            if ( ! $post_ids = $data_sync->fetch_post_ids() ) {
                return;
            }

            // No need to continue if less than 2 ids
            if ( 2 > count( $post_ids ) ) {
                return;
            }

            foreach ( $post_ids as $post_id ) {
                $data_sync->save_post_meta( $post_id, $meta_key, $meta_value );
            }

            Log::write( 'update-duplicate-field', [
                'meta_key'   => $meta_key,
                'meta_value' => $meta_value,
                'primary_id' => $data_sync->get_primary_id(),
                'post_ids'   => $post_ids
            ] );

        }, 10, 3 );

	}

} );
