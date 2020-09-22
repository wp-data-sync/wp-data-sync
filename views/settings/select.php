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

<select
	name="<?php esc_attr_e( $args['name'] ); ?>"
	id="<?php esc_attr_e( $args['name'] ); ?>"
	class="<?php esc_attr_e( $args['class'] ); ?>"
>

	<option value="-1"><?php _e( 'Select One', 'wp-data-sync' ); ?></option>

	<?php foreach( $args['values'] as $value => $label ) { ?>

		<?php $selected = $args['selected'] === $value ? 'selected' : ''; ?>

		<option
			value="<?php esc_attr_e( $value ); ?>"
			<?php esc_attr_e( $selected ); ?>
		><?php esc_html_e( $label ); ?></option>

	<?php } ?>

</select>

<?php toottip( $args ); ?>
