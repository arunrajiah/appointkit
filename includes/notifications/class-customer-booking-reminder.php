<?php
/**
 * Customer booking reminder email.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sends a reminder email to the customer N hours before their booking.
 */
class AppointKit_Customer_Booking_Reminder {

	/** @var AppointKit_Booking */
	private $booking;

	public function __construct( AppointKit_Booking $booking ) {
		$this->booking = $booking;
	}

	/**
	 * Send the reminder email.
	 *
	 * @return bool
	 */
	public function send() {
		$booking = $this->booking;

		$to = apply_filters(
			'appointkit_email_recipients',
			array( $booking->customer_email ),
			'customer_booking_reminder',
			$booking
		);

		$subject = sprintf(
			/* translators: %s: site name */
			__( 'Reminder: Your Upcoming Appointment – %s', 'appointkit' ),
			get_bloginfo( 'name' )
		);

		$message = $this->build_message( $booking );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . esc_html( get_option( 'appointkit_business_name', get_bloginfo( 'name' ) ) ) . ' <' . sanitize_email( get_option( 'appointkit_sender_email', get_bloginfo( 'admin_email' ) ) ) . '>',
		);

		return wp_mail( $to, $subject, $message, $headers );
	}

	private function build_message( AppointKit_Booking $booking ) {
		ob_start();
		include APPOINTKIT_PLUGIN_DIR . 'templates/emails/customer-booking-reminder.php';
		return ob_get_clean();
	}
}
