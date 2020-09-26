<?php
/**
 * Author
 *
 * Default author dropdown.
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

wp_dropdown_users( $args );

message( $args );