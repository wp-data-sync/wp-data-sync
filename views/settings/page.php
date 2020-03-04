<?php
/**
 * Page
 *
 * Admin settings page.
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
} ?>

<div class="wp-data-sync-wrap">

	<h1 class="wp-data-sync-admin-h1"><?php _e( 'WP Data Sync', 'wp-data-sync' ); ?></h1>

	<?php do_action( 'wp_data_sync_help_buttons' ); ?>

	<form method="post" action="options.php">

		<table class="form-table">

			<tbody>

				<?php settings_fields( $this->group ); ?>
				<?php do_settings_fields( 'manage_options', 'default' ); ?>

			</tbody>

		</table>

		<?php submit_button(); ?>

	</form>

</div>