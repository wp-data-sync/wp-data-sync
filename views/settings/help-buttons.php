<?php
/**
 * Help Buttons
 *
 * Display help buttons on settings page
 *
 * @since   1.0.2
 *
 * @package WP_DataSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
} ?>

<style>
	.wpds-help-buttons {
		padding: 20px 0;
	}
	.wpds-help-buttons .button-primary {
		margin-bottom: 10px;
	}
</style>

<div class="wpds-help-buttons">

	<a href="https://wpdatasync.com/products/?affid=admin" class="button-primary" target="_blank">
		<?php _e( 'Products', 'wp-data-sync' ); ?>
	</a>

	<a href="https://wpdatasync.com/support/?affid=admin" class="button-primary" target="_blank">
		<?php _e( 'Support', 'wp-data-sync' ); ?>
	</a>

	<a href="https://wpdatasync.com/docunentation/?affid=admin" class="button-primary" target="_blank">
		<?php _e( 'Documentation', 'wp-data-sync' ); ?>
	</a>

</div>
