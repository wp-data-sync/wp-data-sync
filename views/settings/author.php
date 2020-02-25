<?php
/**
 * Author
 *
 * Default author dropdown.
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
} ?>

<td>

	<?php wp_dropdown_users( $args ); ?>

</td>