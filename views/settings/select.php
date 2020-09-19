<?php
/**
 * Select
 *
 * Settings select.
 *
 * @since   1.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	return;
} ?>

<td>

	<select
		name="<?php printf( esc_attr__( '%s' ), $args['name'] ); ?>"
		id="<?php printf( esc_attr__( '%s' ), $args['name'] ); ?>"
		class="<?php printf( esc_attr__( '%s' ), $args['class'] ); ?>"
	>

		<option value="-1"><?php _e( 'Select One', 'wp-data-sync' ); ?></option>

		<?php foreach( $args['values'] as $value => $label ) { ?>

			<?php $selected = $args['selected'] === $value ? 'selected' : ''; ?>

			<option
				value="<?php printf( esc_attr__( '%s' ), $value ); ?>"
				<?php printf( esc_attr__( '%s' ), $selected ); ?>
			><?php printf( esc_html__( '%s' ), $label ); ?></option>

		<?php } ?>

	</select>

	<?php toottip( $args ); ?>

</td>