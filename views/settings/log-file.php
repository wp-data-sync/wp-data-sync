<?php
/**
 * Log file
 *
 * Display the content of a log file.
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! $files ) {

	printf( '%s', esc_html( __( 'Allow logging for log files to be created.', 'wp-data-sync' ) ) );

	return;

} ?>

<p>
	<select name="wp_data_sync_log_file">
		<?php foreach ( $files as $file ) { ?>
			<?php printf( '<option value="%s">%s</option>', esc_attr( $file ), esc_html( $file ) ); ?>
		<?php } ?>
	</select>
</p>

<p><textarea class="widefat wpds-log-content" rows="20"><?php esc_html_e( $log ); ?></textarea></p>
