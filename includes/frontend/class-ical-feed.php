<?php
/**
 * iCal feed per staff member.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Serves /appointkit/ical/{staff_token}.ics for staff calendar subscriptions.
 */
class AppointKit_iCal_Feed {

	/**
	 * Register rewrite rules.
	 */
	public function register_rewrite_rules() {
		add_rewrite_rule(
			'^appointkit/ical/([a-f0-9]{32})\.ics$',
			'index.php?appointkit_ical=1&appointkit_ical_token=$matches[1]',
			'top'
		);
		add_rewrite_tag( '%appointkit_ical%', '1' );
		add_rewrite_tag( '%appointkit_ical_token%', '[a-f0-9]{32}' );
	}

	/**
	 * Serve the iCal feed if the request matches.
	 */
	public function maybe_serve() {
		if ( ! get_query_var( 'appointkit_ical' ) ) {
			return;
		}

		$token = sanitize_text_field( get_query_var( 'appointkit_ical_token' ) );
		if ( empty( $token ) ) {
			status_header( 404 );
			exit;
		}

		$staff = $this->get_staff_by_token( $token );
		if ( ! $staff ) {
			status_header( 404 );
			exit;
		}

		$bookings = ( new AppointKit_Bookings_Repository() )->get_by_staff_upcoming( $staff->id );

		header( 'Content-Type: text/calendar; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="appointkit-' . esc_attr( sanitize_title( $staff->name ) ) . '.ics"' );

		echo $this->build_ical( $staff, $bookings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Find a staff member by their iCal token.
	 *
	 * @param string $token iCal token.
	 * @return AppointKit_Staff|null
	 */
	private function get_staff_by_token( $token ) {
		global $wpdb;
		$table = $wpdb->prefix . 'appointkit_staff';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE ical_token = %s AND status = %s", $token, 'active' )
		);
		return $row ? new AppointKit_Staff( $row ) : null;
	}

	/**
	 * Build iCal content from a list of bookings.
	 *
	 * @param AppointKit_Staff     $staff    Staff model.
	 * @param AppointKit_Booking[] $bookings Upcoming bookings.
	 * @return string
	 */
	private function build_ical( AppointKit_Staff $staff, array $bookings ) {
		$lines = array(
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//AppointKit//AppointKit//EN',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'X-WR-CALNAME:' . $this->ical_escape( $staff->name . ' – ' . get_bloginfo( 'name' ) ),
			'X-WR-TIMEZONE:UTC',
		);

		foreach ( $bookings as $booking ) {
			$dtstart = str_replace( array( '-', ' ', ':' ), array( '', 'T', '' ), $booking->start_utc ) . 'Z';
			$dtend   = str_replace( array( '-', ' ', ':' ), array( '', 'T', '' ), $booking->end_utc ) . 'Z';
			$uid     = 'booking-' . $booking->id . '@' . wp_parse_url( home_url(), PHP_URL_HOST );
			$service = ( new AppointKit_Services_Repository() )->find( $booking->service_id );

			$lines[] = 'BEGIN:VEVENT';
			$lines[] = 'UID:' . $uid;
			$lines[] = 'DTSTAMP:' . gmdate( 'Ymd\THis\Z' );
			$lines[] = 'DTSTART:' . $dtstart;
			$lines[] = 'DTEND:' . $dtend;
			$lines[] = 'SUMMARY:' . $this->ical_escape( $service ? $service->name : __( 'Appointment', 'appointkit' ) );
			$lines[] = 'DESCRIPTION:' . $this->ical_escape( __( 'Customer:', 'appointkit' ) . ' ' . $booking->customer_name );
			$lines[] = 'STATUS:CONFIRMED';
			$lines[] = 'END:VEVENT';
		}

		$lines[] = 'END:VCALENDAR';
		return implode( "\r\n", $lines ) . "\r\n";
	}

	/**
	 * Escape a value for use in an iCal property.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private function ical_escape( $value ) {
		return str_replace( array( '\\', ';', ',', "\n" ), array( '\\\\', '\\;', '\\,', '\\n' ), $value );
	}
}
