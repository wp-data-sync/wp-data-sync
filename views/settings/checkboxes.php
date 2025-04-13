<?php
/**
 * Admin Checkboxes
 *
 * Admin settings checkboxes.
 *
 * @since   1.3.4
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	return;
} ?>

<?php foreach ( $options as $option ) { ?>

	<?php extract( $option ); ?>

	<?php $checked = in_array( $value, $values ) ? 'checked' : ''; ?>

	<tr class="widefat">

		<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>

		<td>
			<input
				type="checkbox"
				value="<?php echo esc_attr( $value ); ?>"
				name="<?php echo esc_attr( $key ); ?>[]"
				id="<?php echo esc_attr( $id ); ?>"
				class="<?php echo esc_attr( $class ); ?>"
				<?php echo esc_attr( $checked ); ?>
			>

			<?php toottip( $args ); ?>
			<?php message( $args ); ?>

		</td>

	</tr>

<?php } ?>