<?php
/**
 * Admin Text Area
 *
 * Admin settings input.
 *
 * @since  3.5.4
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

toottip( $args );

printf(
    '<textarea name="%s" id="%s" class="%s" rows="%d" placeholder="%s">%s</textarea>',
    esc_attr( $key ),
    esc_attr( $key ),
    esc_attr( $class ),
    intval( $rows ),
    esc_attr( $placeholder ),
    esc_textarea( $value )
);

message( $args );
