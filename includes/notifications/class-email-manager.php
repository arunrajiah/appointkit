<?php
/**
 * Email manager — dispatches all notification emails.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Central coordinator for all AppointKit transactional emails.
 */
class AppointKit_Email_Manager {

	/**
	 * Send all emails triggered by a confirmed booking.
	 *
	 * @param AppointKit_Booking $booking Confirmed booking.
	 */
	public function send_booking_confirmed( AppointKit_Booking $booking ) {
		( new AppointKit_Customer_Booking_Confirmed( $booking ) )->send();
		( new AppointKit_Staff_Booking_Assigned( $booking ) )->send();
		( new AppointKit_Admin_New_Booking( $booking ) )->send();
	}

	/**
	 * Send all emails triggered by a cancelled booking.
	 *
	 * @param AppointKit_Booking $booking Cancelled booking.
	 */
	public function send_booking_cancelled( AppointKit_Booking $booking ) {
		( new AppointKit_Customer_Booking_Cancelled( $booking ) )->send();
	}
}
