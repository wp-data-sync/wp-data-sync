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

<td>
	<input
		type="checkbox"
		value="checked"
		name="<?php printf( esc_attr__( '%s' ), $args['key'] ); ?>"
		id="<?php printf( esc_attr__( '%s' ), $args['key'] ); ?>"
		class="<?php printf( esc_attr__( '%s' ), $args['class'] ); ?>"
		<?php printf( esc_attr__( '%s' ), $value ); ?>
	>

	<?php toottip( $args ); ?>

</td>