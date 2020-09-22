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

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	return;
} ?>

<input
	type="<?php esc_attr_e( $args['type'] ); ?>"
	name="<?php esc_attr_e( $args['key'] ); ?>"
	id="<?php esc_attr_e( $args['key'] ); ?>"
	value="<?php esc_attr_e( $value ); ?>"
	class="<?php esc_attr_e( $args['class'] ); ?>"
	placeholder="<?php esc_attr_e( $args['placeholder'] ); ?>"
>

<?php toottip( $args ); ?>
