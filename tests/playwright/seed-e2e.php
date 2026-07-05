<?php
/**
 * Seed wp-env with the minimum content needed by the Playwright booking flow.
 *
 * @package AppointKit
 */

global $wpdb;

$page = get_page_by_path( 'booking' );
if ( ! $page ) {
	wp_insert_post(
		array(
			'post_title'   => 'Booking',
			'post_name'    => 'booking',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '[appointkit_form]',
		)
	);
}

$services_table     = $wpdb->prefix . 'appointkit_services';
$staff_table        = $wpdb->prefix . 'appointkit_staff';
$pivot_table        = $wpdb->prefix . 'appointkit_staff_services';
$availability_table = $wpdb->prefix . 'appointkit_availability_rules';
$now                = current_time( 'mysql', true );

$service_id = (int) $wpdb->get_var( "SELECT id FROM {$services_table} WHERE name = 'E2E Consultation' LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
if ( ! $service_id ) {
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$services_table,
		array(
			'name'          => 'E2E Consultation',
			'description'   => 'Seeded service for Playwright.',
			'duration'      => 30,
			'price'         => 0,
			'color'         => '#3788d8',
			'slot_interval' => 30,
			'buffer_before' => 0,
			'buffer_after'  => 0,
			'status'        => 'active',
			'created_at'    => $now,
			'updated_at'    => $now,
		)
	);
	$service_id = (int) $wpdb->insert_id;
}

$staff_id = (int) $wpdb->get_var( "SELECT id FROM {$staff_table} WHERE email = 'e2e-staff@example.com' LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
if ( ! $staff_id ) {
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$staff_table,
		array(
			'name'       => 'E2E Staff',
			'email'      => 'e2e-staff@example.com',
			'timezone'   => 'UTC',
			'ical_token' => wp_generate_password( 32, false, false ),
			'status'     => 'active',
			'created_at' => $now,
			'updated_at' => $now,
		)
	);
	$staff_id = (int) $wpdb->insert_id;
}

$wpdb->replace( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$pivot_table,
	array(
		'staff_id'   => $staff_id,
		'service_id' => $service_id,
	),
	array( '%d', '%d' )
);

for ( $weekday = 0; $weekday <= 6; $weekday++ ) {
	$exists = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"SELECT id FROM {$availability_table} WHERE staff_id = %d AND type = %s AND weekday = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$staff_id,
			'weekday',
			$weekday
		)
	);

	if ( $exists ) {
		continue;
	}

	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$availability_table,
		array(
			'staff_id'   => $staff_id,
			'type'       => 'weekday',
			'weekday'    => $weekday,
			'start_time' => '09:00:00',
			'end_time'   => '17:00:00',
			'is_off'     => 0,
			'created_at' => $now,
			'updated_at' => $now,
		)
	);
}
