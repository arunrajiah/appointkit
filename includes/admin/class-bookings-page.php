<?php
/**
 * Bookings admin page.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the bookings list and handles booking management actions.
 */
class AppointKit_Bookings_Page {

	/** @var AppointKit_Bookings_Repository */
	private $repo;

	/** @var AppointKit_Booking_Creator */
	private $creator;

	public function __construct() {
		$this->repo    = new AppointKit_Bookings_Repository();
		$this->creator = new AppointKit_Booking_Creator();
	}

	/**
	 * Render the page.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'appointkit' ) );
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';

		switch ( $action ) {
			case 'view':
				$this->render_detail();
				break;
			default:
				$this->render_list();
		}
	}

	/**
	 * Handle a cancellation. Called on admin_init, before any output.
	 */
	public function handle_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Selects the handler; the nonce is verified in handle_cancel().
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		if ( 'cancel' === $action ) {
			$this->handle_cancel();
		}
	}

	private function render_list() {
		$args = array(
			'status'     => sanitize_text_field( wp_unslash( $_GET['status'] ?? '' ) ),
			'staff_id'   => absint( $_GET['staff_id'] ?? 0 ),
			'service_id' => absint( $_GET['service_id'] ?? 0 ),
			'search'     => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ),
			'per_page'   => 25,
			'paged'      => absint( $_GET['paged'] ?? 1 ),
		);

		$result   = $this->repo->get_list( $args );
		$bookings = $result['bookings'];
		$total    = $result['total'];
		$statuses = appointkit_get_booking_statuses();

		include APPOINTKIT_PLUGIN_DIR . 'templates/admin/bookings-list.php';
	}

	private function render_detail() {
		$booking_id = absint( $_GET['id'] ?? 0 );
		$booking    = $this->repo->find( $booking_id );
		if ( ! $booking ) {
			wp_die( esc_html__( 'Booking not found.', 'appointkit' ) );
		}
		$service = ( new AppointKit_Services_Repository() )->find( $booking->service_id );
		$staff   = ( new AppointKit_Staff_Repository() )->find( $booking->staff_id );
		include APPOINTKIT_PLUGIN_DIR . 'templates/admin/booking-detail.php';
	}

	private function handle_cancel() {
		$booking_id = absint( $_GET['id'] ?? 0 );
		if ( ! $booking_id || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'appointkit_cancel_booking_' . $booking_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'appointkit' ) );
		}

		$result = $this->creator->cancel( $booking_id );
		$query  = is_wp_error( $result )
			? array( 'error' => urlencode( $result->get_error_message() ) )
			: array( 'cancelled' => 1 );

		wp_safe_redirect( add_query_arg( array_merge( array( 'page' => 'appointkit' ), $query ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
