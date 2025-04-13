<?php
/**
 * Select
 *
 * Settings select.
 *
 * @since   1.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	return;
} ?>

<select
	name="<?php echo esc_attr( $name ); ?>"
	id="<?php echo esc_attr( $name ); ?>"
	class="<?php echo esc_attr( $class ); ?>"
>

	<option value="-1"><?php esc_html_e( 'Select One', 'wp-data-sync' ); ?></option>

	<?php foreach( $values as $value => $label ) { ?>

		<?php $choice = $selected == $value ? 'selected' : ''; ?>

		<option
			value="<?php echo esc_attr( $value ); ?>"
			<?php echo esc_attr( $choice ); ?>
		><?php echo esc_html( $label ); ?></option>

	<?php } ?>

</select>

<?php toottip( $args ); ?>
<?php message( $args ); ?>
