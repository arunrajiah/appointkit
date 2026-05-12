<?php
/**
 * Staff booking assigned email.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sends an assignment notification to the staff member when a booking is confirmed.
 */
class AppointKit_Staff_Booking_Assigned {

	/** @var AppointKit_Booking */
	private $booking;

	public function __construct( AppointKit_Booking $booking ) {
		$this->booking = $booking;
	}

	/**
	 * Send the email.
	 *
	 * @return bool
	 */
	public function send() {
		$booking = $this->booking;
		$staff   = ( new AppointKit_Staff_Repository() )->find( $booking->staff_id );

		if ( ! $staff || empty( $staff->email ) ) {
			return false;
		}

		$to = apply_filters(
			'appointkit_email_recipients',
			array( $staff->email ),
			'staff_booking_assigned',
			$booking
		);

		$subject = sprintf(
			/* translators: %s: customer name */
			__( 'New Booking from %s', 'appointkit' ),
			$booking->customer_name
		);

		$message = $this->build_message( $booking, $staff );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . esc_html( get_option( 'appointkit_business_name', get_bloginfo( 'name' ) ) ) . ' <' . sanitize_email( get_option( 'appointkit_sender_email', get_bloginfo( 'admin_email' ) ) ) . '>',
		);

		return wp_mail( $to, $subject, $message, $headers );
	}

	private function build_message( AppointKit_Booking $booking, AppointKit_Staff $staff ) {
		ob_start();
		include APPOINTKIT_PLUGIN_DIR . 'templates/emails/staff-booking-assigned.php';
		return ob_get_clean();
	}
}
