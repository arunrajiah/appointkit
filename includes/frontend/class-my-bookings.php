<?php
/**
 * Customer "My Bookings" page handler.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds a /my-account/bookings endpoint for customers to view and cancel bookings.
 */
class AppointKit_My_Bookings {

	/**
	 * Register the My Bookings endpoint.
	 */
	public function register() {
		add_rewrite_endpoint( 'bookings', EP_ROOT | EP_PAGES );
		add_action( 'template_redirect', array( $this, 'maybe_handle_cancel' ) );
	}

	/**
	 * Handle a customer cancellation request.
	 */
	public function maybe_handle_cancel() {
		if ( ! is_page() && empty( get_query_var( 'bookings' ) ) ) {
			return;
		}

		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['appointkit_cancel_booking_id'] ) ) {
			return;
		}

		if ( ! isset( $_POST['appointkit_cancel_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['appointkit_cancel_nonce'] ) ), 'appointkit_cancel_booking' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'appointkit' ) );
		}

		$booking_id = absint( $_POST['appointkit_cancel_booking_id'] );
		$customer_email = sanitize_email( wp_unslash( $_POST['appointkit_cancel_email'] ?? '' ) );

		$repo    = new AppointKit_Bookings_Repository();
		$booking = $repo->find( $booking_id );

		if ( ! $booking || strtolower( $booking->customer_email ) !== strtolower( $customer_email ) ) {
			wp_safe_redirect( add_query_arg( 'cancel_error', '1', get_permalink() ) );
			exit;
		}

		$cutoff_hours = (int) get_option( 'appointkit_cancel_cutoff_hours', 24 );
		$start        = new DateTime( $booking->start_utc, new DateTimeZone( 'UTC' ) );
		$now          = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$diff_hours   = ( $start->getTimestamp() - $now->getTimestamp() ) / 3600;

		if ( $diff_hours < $cutoff_hours ) {
			wp_safe_redirect( add_query_arg( 'cancel_error', 'cutoff', get_permalink() ) );
			exit;
		}

		$creator = new AppointKit_Booking_Creator();
		$creator->cancel( $booking_id );

		wp_safe_redirect( add_query_arg( 'cancelled', '1', get_permalink() ) );
		exit;
	}

	/**
	 * Render the My Bookings list (called from a template).
	 *
	 * @param string $customer_email Customer email address.
	 * @return string
	 */
	public static function render_for_email( $customer_email ) {
		if ( empty( $customer_email ) ) {
			return '';
		}
		$repo     = new AppointKit_Bookings_Repository();
		$bookings = $repo->get_by_customer_email( $customer_email );
		ob_start();
		include APPOINTKIT_PLUGIN_DIR . 'templates/frontend/my-bookings.php';
		return ob_get_clean();
	}
}
