<?php
/**
 * Heading
 *
 * Admin settings heading.
 *
 * @since   1.3.4
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

extract( $args );

printf( '<tr><td colspan="2"><h2>%s</h2></td></tr>', esc_html( $heading ) );
