<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package AppointKit
 */

// Only run during an actual uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Only remove data if the user opted in.
if ( ! get_option( 'appointkit_remove_data_on_uninstall' ) ) {
	return;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'appointkit_services',
	$wpdb->prefix . 'appointkit_staff',
	$wpdb->prefix . 'appointkit_staff_services',
	$wpdb->prefix . 'appointkit_bookings',
	$wpdb->prefix . 'appointkit_availability_rules',
	$wpdb->prefix . 'appointkit_locations',
);

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// Remove all options.
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE 'appointkit_%'"
);
