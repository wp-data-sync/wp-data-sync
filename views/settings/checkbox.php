<?php
/**
 * Admin Checkbox
 *
 * Admin settings checkbox.
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	return;
} ?>

<input
	type="checkbox"
	value="checked"
	name="<?php echo esc_attr( $key ); ?>"
	id="<?php echo esc_attr( $key ); ?>"
	class="<?php echo esc_attr( $class ); ?>"
	<?php echo esc_attr( $value ); ?>
>

<?php toottip( $args ); ?>
<?php message( $args ); ?>
