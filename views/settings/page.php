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

<div class="wp-data-sync-settings wrap">

	<h1 class="wp-data-sync-admin-h1"><?php esc_html_e( 'WP Data Sync' ); ?></h1>

	<?php do_action( 'wp_data_sync_help_buttons' ); ?>

	<form method="post" action="options.php">

		<?php $this->admin_tabs(); ?>

		<table class="form-table">

			<tbody>

				<?php settings_fields( $this->group_key ); ?>
				<?php do_settings_fields( WP_DATA_SYNC_CAP, 'default' ); ?>

			</tbody>

		</table>

		<?php submit_button(); ?>

	</form>

</div>