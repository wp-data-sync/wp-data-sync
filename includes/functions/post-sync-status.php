<?php
/**
 * Post Sync Status
 *
 * Show the post sync status switch.
 *
 * @since   2.0.0
 *
 * @package WP_Data_Sync_Api
 */

namespace WP_DataSync\App;

/**
 * Add sync status input to post type edit screens.
 *
 * @param \WP_Post $post
 */

add_action( 'post_submitbox_misc_actions', function( $post ) {

	$label = __( 'Sync Status', 'wp-data-sync' );
	$status = __( 'Active', 'wp-data-sync' );
	$checked = '';

	if ( get_post_meta( $post->ID, 'wpds_sync_status_disabled', true ) ) {
		$status = __( 'Disabled', 'wp-data-sync' );
		$checked = 'checked';
	}

	printf(
		'<div class="misc-pub-section" id="edit-sync-status">%s: <b>%s</b> <input type="checkbox" name="%s" value="disabled" %s></div>',
		esc_html( $label ),
		esc_html( $status ),
		esc_attr( 'wpds_sync_status_disabled' ),
		esc_attr( $checked ),
	);

}, 999, 1 );

/**
 * Save sync status on save post.
 * 
 * @param int $post_id
 */

add_action( 'save_post', function( $post_id ) {

	if ( isset( $_POST[ 'wpds_sync_status_disabled' ] ) ) {

		if ( 'disabled' === $_POST[ 'wpds_sync_status_disabled' ] ) {
			update_post_meta( $post_id, 'wpds_sync_status_disabled', 'disabled' );
			return;
		}

	}

	delete_post_meta( $post_id, 'wpds_sync_status_disabled' );

}, 10, 1 );