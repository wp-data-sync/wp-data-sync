<?php
/**
 * Admin Tabs
 *
 * Tabs for the admin settings page.
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
} ?>

<p>
	<nav class="nav-tab-wrapper">

        <?php foreach ( $tabs as $tab ) { ?>

    	    <?php $status = $settings->tab_status( $tab ); ?>

			<?php printf( '
				<a href="%s%s" class="nav-tab %s %s">%s</a>',
		        esc_url( $href ),
		        esc_attr( $tab['id'] ),
		        esc_attr( $tab['id'] ),
		        esc_attr( $status ),
		        esc_html( $tab['label'] )
	        ); ?>

     <?php } ?>

	</nav>
</p>
