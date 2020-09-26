<?php
/**
 * Admin Checkbox
 *
 * Admin settings checkbox.
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	return;
} ?>

<input
	type="checkbox"
	value="checked"
	name="<?php esc_attr_e( $args['key'] ); ?>"
	id="<?php esc_attr_e( $args['key'] ); ?>"
	class="<?php esc_attr_e( $args['class'] ); ?>"
	<?php esc_attr_e( $value ); ?>
>

<?php toottip( $args ); ?>
<?php message( $args ); ?>
