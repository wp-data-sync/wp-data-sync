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

	printf( '<div>%s</div>', esc_html__( 'Allow logging for log files to be created.', 'wp-data-sync' ) );

	return;

}

$file_name = get_option( Log::FILE_KEY ); ?>

<p>
	<select name="<?php echo esc_attr( Log::FILE_KEY );?>" id="log-file-select" class="log-file-select">
		<?php printf( '<option value="-1">%s</option>', esc_html( 'Select One' ) ); ?>
		<?php foreach ( $files as $file ) { ?>

			<?php $selected = ( $file_name === $file ) ? 'selected' : ''; ?>
			<?php printf( '<option value="%s" %s>%s</option>', esc_attr( $file ), esc_attr( $selected ), esc_html( $file ) ); ?>

		<?php } ?>
	</select>
</p>

<p><textarea class="widefat wpds-log-content" rows="20"><?php echo esc_html( $log ); ?></textarea></p>

<script>
	jQuery(document).ready( function($) {
		$('form #log-file-select').on('change', function() {
			$(this).closest('form').find('input[type=submit]').click();
		});
	});
</script>
