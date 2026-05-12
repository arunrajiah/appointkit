<?php
/**
 * WP-Cron job handlers.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles scheduled cron jobs: reminder emails and pending booking cleanup.
 */
class AppointKit_Cron {

	/**
	 * Send reminder emails for bookings happening within the configured window.
	 */
	public function send_reminders() {
		$hours_before = (int) get_option( 'appointkit_reminder_hours_before', 24 );
		$now_utc      = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$window_end   = clone $now_utc;
		$window_end->modify( '+' . $hours_before . ' hours' );

		// Add 5-minute buffer to avoid duplicate sends.
		$window_start = clone $now_utc;
		$window_start->modify( '-5 minutes' );

		$repo     = new AppointKit_Bookings_Repository();
		$bookings = $repo->get_upcoming_for_reminder(
			$window_start->format( 'Y-m-d H:i:s' ),
			$window_end->format( 'Y-m-d H:i:s' )
		);

		foreach ( $bookings as $booking ) {
			$email = new AppointKit_Customer_Booking_Reminder( $booking );
			$email->send();
			$repo->mark_reminder_sent( $booking->id );
		}
	}

	/**
	 * Delete pending bookings that were never paid after the expiry window.
	 */
	public function cleanup_pending_bookings() {
		$expiry_minutes = (int) apply_filters( 'appointkit_pending_expiry_minutes', 30 );
		$cutoff_utc     = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$cutoff_utc->modify( '-' . $expiry_minutes . ' minutes' );

		$repo = new AppointKit_Bookings_Repository();
		$repo->delete_expired_pending( $cutoff_utc->format( 'Y-m-d H:i:s' ) );
	}
}
