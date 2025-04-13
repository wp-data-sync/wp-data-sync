<?php
/**
 * Admin Input
 *
 * Admin settings input.
 *
 * @since  1.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

printf(
    '<input type="%s" name="%s" id="%s" value="%s" class="%s" placeholder="%s">',
    esc_attr( $type ),
    esc_attr( $key ),
    esc_attr( $key ),
    esc_attr( $value ),
    esc_attr( $class ),
    esc_attr( $placeholder )
);

toottip( $args );
message( $args );
