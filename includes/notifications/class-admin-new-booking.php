<?php
/**
 * Admin new booking email.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sends a new booking notification to the site admin.
 */
class AppointKit_Admin_New_Booking {

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
		$booking   = $this->booking;
		$admin_email = get_option( 'appointkit_sender_email', get_bloginfo( 'admin_email' ) );

		$to = apply_filters(
			'appointkit_email_recipients',
			array( $admin_email ),
			'admin_new_booking',
			$booking
		);

		$subject = sprintf(
			/* translators: 1: booking ID, 2: customer name */
			__( '[New Booking #%1$d] %2$s', 'appointkit' ),
			$booking->id,
			$booking->customer_name
		);

		$message = $this->build_message( $booking );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		return wp_mail( $to, $subject, $message, $headers );
	}

	private function build_message( AppointKit_Booking $booking ) {
		ob_start();
		include APPOINTKIT_PLUGIN_DIR . 'templates/emails/admin-new-booking.php';
		return ob_get_clean();
	}
}
