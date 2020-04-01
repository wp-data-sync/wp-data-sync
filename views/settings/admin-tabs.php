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

<nav class="nav-tab-wrapper">

    <?php foreach ( $tabs as $tab ) { ?>

    	<?php $status = $this->tab_status( $tab ); ?>

		<a href="<?php printf( '%s', esc_url( $href ) ); ?><?php esc_attr_e( $tab['id'] ); ?>" class="nav-tab <?php esc_attr_e( $status ); ?>">
			<?php esc_html_e( $tab['label'] ); ?>
		</a>

    <?php } ?>

</nav>
