<?php
/**
 * Message
 *
 * Display a message.
 *
 * @since   1.4.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function message( $args ) {

	if ( ! empty( $args['msg'] ) ) {
		printf( '<span class="wpds-message">%s</span>', esc_html( $args['msg'] ) );
	}

}