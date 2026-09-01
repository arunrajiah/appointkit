<?php
/**
 * Conflict checker — last-second double-booking guard.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Validates that a proposed booking slot doesn't conflict with existing bookings.
 * Called immediately before inserting a booking row (after slot availability is shown).
 */
class AppointKit_Conflict_Checker {

	/** @var AppointKit_Bookings_Repository */
	private $bookings_repo;

	public function __construct( ?AppointKit_Bookings_Repository $bookings_repo = null ) {
		$this->bookings_repo = $bookings_repo ?: new AppointKit_Bookings_Repository();
	}

	/**
	 * Check if the proposed slot is free for the given staff member.
	 *
	 * @param int    $staff_id  Staff ID.
	 * @param string $start_utc Proposed start (UTC MySQL datetime).
	 * @param string $end_utc   Proposed end (UTC MySQL datetime).
	 * @param int    $exclude_booking_id Exclude this booking ID from the check (for reschedule).
	 * @return bool True if there are no conflicts.
	 */
	public function is_free( $staff_id, $start_utc, $end_utc, $exclude_booking_id = 0 ) {
		$existing = $this->bookings_repo->get_for_staff_in_range(
			$staff_id,
			$start_utc,
			$end_utc
		);

		foreach ( $existing as $booking ) {
			if ( (int) $booking->id === (int) $exclude_booking_id ) {
				continue;
			}
			// Overlap when start < existing_end AND end > existing_start.
			if ( $start_utc < $booking->end_utc && $end_utc > $booking->start_utc ) {
				return false;
			}
		}
		return true;
	}
}
