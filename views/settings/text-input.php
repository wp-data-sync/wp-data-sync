<?php
/**
 * Admin Input
 *
 * Admin settings input.
 *
 * @since  1.0
 *
 * @package WP_DataSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
} ?>

<td>

	<input
		type="<?php printf( esc_attr__( '%s' ), $args['type'] ); ?>"
		name="<?php printf( esc_attr__( '%s' ), $args['key'] ); ?>"
		id="<?php printf( esc_attr__( '%s' ), $args['key'] ); ?>"
		value="<?php printf( esc_attr__( '%s' ), $value ); ?>"
		class="<?php printf( esc_attr__( '%s' ), $args['class'] ); ?>"
		placeholder="<?php printf( esc_attr__( '%s' ), $args['placeholder'] ); ?>"
	>

</td>