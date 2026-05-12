<?php
/**
 * Customer booking confirmed email.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sends a booking confirmation email to the customer.
 */
class AppointKit_Customer_Booking_Confirmed {

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

		$service = ( new AppointKit_Services_Repository() )->find( $booking->service_id );
		$staff   = ( new AppointKit_Staff_Repository() )->find( $booking->staff_id );

		$to = apply_filters(
			'appointkit_email_recipients',
			array( $booking->customer_email ),
			'customer_booking_confirmed',
			$booking
		);

		$subject = sprintf(
			/* translators: %s: site name */
			__( 'Booking Confirmed – %s', 'appointkit' ),
			get_bloginfo( 'name' )
		);

		$message = $this->build_message( $booking, $service, $staff );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . esc_html( get_option( 'appointkit_business_name', get_bloginfo( 'name' ) ) ) . ' <' . sanitize_email( get_option( 'appointkit_sender_email', get_bloginfo( 'admin_email' ) ) ) . '>',
		);

		return wp_mail( $to, $subject, $message, $headers );
	}

	/**
	 * Build the HTML email body.
	 *
	 * @param AppointKit_Booking      $booking Booking model.
	 * @param AppointKit_Service|null $service Service model.
	 * @param AppointKit_Staff|null   $staff   Staff model.
	 * @return string
	 */
	private function build_message( AppointKit_Booking $booking, $service, $staff ) {
		ob_start();
		include APPOINTKIT_PLUGIN_DIR . 'templates/emails/customer-booking-confirmed.php';
		return ob_get_clean();
	}
}
