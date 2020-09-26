<?php
/**
 * Cron schedules
 *
 * @since   1.4.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\Woo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'cron_schedules', function( $schedules ) {

	$schedules['every_five_seconds'] = [
       'interval' => 5,
       'display' => __( 'Every 5 Seconds' )
   ];

	return $schedules;

});