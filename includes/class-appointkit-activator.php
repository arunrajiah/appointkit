<?php
/**
 * Fired during plugin activation.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates custom database tables and schedules cron jobs on activation.
 */
class AppointKit_Activator {

	/**
	 * Run activation routines.
	 */
	public static function activate() {
		self::create_tables();
		self::schedule_cron_jobs();
		self::set_default_options();
		flush_rewrite_rules();
	}

	/**
	 * Create custom database tables using dbDelta().
	 */
	private static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		// Services table.
		$sql_services = "CREATE TABLE {$wpdb->prefix}appointkit_services (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL DEFAULT '',
			description longtext NOT NULL DEFAULT '',
			duration int(11) UNSIGNED NOT NULL DEFAULT 60,
			price decimal(10,2) NOT NULL DEFAULT 0.00,
			color varchar(20) NOT NULL DEFAULT '#3788d8',
			slot_interval int(11) UNSIGNED NOT NULL DEFAULT 60,
			buffer_before int(11) UNSIGNED NOT NULL DEFAULT 0,
			buffer_after int(11) UNSIGNED NOT NULL DEFAULT 10,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			KEY status (status)
		) $charset_collate;";

		// Staff table.
		$sql_staff = "CREATE TABLE {$wpdb->prefix}appointkit_staff (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			wp_user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			name varchar(255) NOT NULL DEFAULT '',
			email varchar(255) NOT NULL DEFAULT '',
			phone varchar(50) NOT NULL DEFAULT '',
			bio longtext NOT NULL DEFAULT '',
			photo_url varchar(500) NOT NULL DEFAULT '',
			timezone varchar(100) NOT NULL DEFAULT 'UTC',
			google_calendar_token longtext NOT NULL DEFAULT '',
			ical_token varchar(64) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			KEY wp_user_id (wp_user_id),
			KEY status (status),
			KEY ical_token (ical_token)
		) $charset_collate;";

		// Staff-service pivot.
		$sql_staff_services = "CREATE TABLE {$wpdb->prefix}appointkit_staff_services (
			staff_id bigint(20) UNSIGNED NOT NULL,
			service_id bigint(20) UNSIGNED NOT NULL,
			PRIMARY KEY (staff_id, service_id),
			KEY service_id (service_id)
		) $charset_collate;";

		// Bookings table.
		$sql_bookings = "CREATE TABLE {$wpdb->prefix}appointkit_bookings (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			service_id bigint(20) UNSIGNED NOT NULL,
			staff_id bigint(20) UNSIGNED NOT NULL,
			customer_name varchar(255) NOT NULL DEFAULT '',
			customer_email varchar(255) NOT NULL DEFAULT '',
			customer_phone varchar(50) NOT NULL DEFAULT '',
			start_utc datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			end_utc datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			status varchar(30) NOT NULL DEFAULT 'pending',
			price decimal(10,2) NOT NULL DEFAULT 0.00,
			payment_intent_id varchar(255) NOT NULL DEFAULT '',
			payment_status varchar(30) NOT NULL DEFAULT 'unpaid',
			notes longtext NOT NULL DEFAULT '',
			meta longtext NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			KEY service_id (service_id),
			KEY staff_id (staff_id),
			KEY start_utc (start_utc),
			KEY status (status),
			KEY customer_email (customer_email)
		) $charset_collate;";

		// Availability rules table.
		$sql_availability = "CREATE TABLE {$wpdb->prefix}appointkit_availability_rules (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			staff_id bigint(20) UNSIGNED NOT NULL,
			type varchar(30) NOT NULL DEFAULT 'weekday',
			weekday tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
			date date NOT NULL DEFAULT '0000-00-00',
			start_time time NOT NULL DEFAULT '09:00:00',
			end_time time NOT NULL DEFAULT '17:00:00',
			is_off tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			KEY staff_id (staff_id),
			KEY type (type),
			KEY weekday (weekday),
			KEY date (date)
		) $charset_collate;";

		// Locations table (used by Pro multi-location; Free creates the table but doesn't expose UI).
		$sql_locations = "CREATE TABLE {$wpdb->prefix}appointkit_locations (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL DEFAULT '',
			address text NOT NULL DEFAULT '',
			phone varchar(50) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			KEY status (status)
		) $charset_collate;";

		dbDelta( $sql_services );
		dbDelta( $sql_staff );
		dbDelta( $sql_staff_services );
		dbDelta( $sql_bookings );
		dbDelta( $sql_availability );
		dbDelta( $sql_locations );

		update_option( 'appointkit_db_version', APPOINTKIT_VERSION );
	}

	/**
	 * Schedule WP-Cron recurring events.
	 */
	private static function schedule_cron_jobs() {
		if ( ! wp_next_scheduled( 'appointkit_send_reminders' ) ) {
			wp_schedule_event( time(), 'hourly', 'appointkit_send_reminders' );
		}
		if ( ! wp_next_scheduled( 'appointkit_cleanup_pending' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'appointkit_cleanup_pending' );
		}
	}

	/**
	 * Set sensible defaults on first activation.
	 */
	private static function set_default_options() {
		$defaults = array(
			'appointkit_business_name'         => get_bloginfo( 'name' ),
			'appointkit_sender_email'          => get_bloginfo( 'admin_email' ),
			'appointkit_default_buffer_after'  => 10,
			'appointkit_reminder_hours_before' => 24,
			'appointkit_cancel_cutoff_hours'   => 24,
			'appointkit_stripe_mode'           => 'test',
			'appointkit_stripe_test_pk'        => '',
			'appointkit_stripe_test_sk'        => '',
			'appointkit_stripe_live_pk'        => '',
			'appointkit_stripe_live_sk'        => '',
			'appointkit_remove_data_on_uninstall' => 0,
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}
}
