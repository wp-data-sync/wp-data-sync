<?php
/**
 * Page
 *
 * Admin settings page.
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	return;
} ?>

<script>
	jQuery(document).ready(function($) {
		$( '.wpds-copy-report' ).on( 'click', function() {
			var report = $('#wpds-system-report');
			report.focus();
			report.select();
			document.execCommand('copy');
		});
	});
</script>

<div class="wp-data-sync-settings wrap">

	<h1 class="wp-data-sync-admin-h1"><?php esc_html_e( 'WP Data Sync' ); ?></h1>

	<?php do_action( 'wp_data_sync_help_buttons' ); ?>

	<?php view( 'settings/admin-tabs', $args ); ?>

	<table class="form-table">

		<tbody>

			<tr>
				<td><textarea id="wpds-system-report" class="widefat" rows="12"><?php printf( '%s', $report ); ?></textarea></td>
			</tr>
			<tr>
				<td>
					<button class="wpds-copy-report button-primary"><?php esc_html_e( 'Copy Report to Clipboard', 'wp-data-sync' ); ?></button>
				</td>
			</tr>

		</tbody>

	</table>

</div>