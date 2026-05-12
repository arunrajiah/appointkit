<?php
/**
 * Booking creator — orchestrates slot validation, payment, and confirmation.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates a booking: validates the slot, charges via Stripe, persists, and sends emails.
 */
class AppointKit_Booking_Creator {

	/** @var AppointKit_Services_Repository */
	private $services_repo;

	/** @var AppointKit_Staff_Repository */
	private $staff_repo;

	/** @var AppointKit_Bookings_Repository */
	private $bookings_repo;

	/** @var AppointKit_Slot_Generator */
	private $slot_generator;

	/** @var AppointKit_Conflict_Checker */
	private $conflict_checker;

	/** @var AppointKit_Stripe_Gateway */
	private $stripe;

	/** @var AppointKit_Email_Manager */
	private $emails;

	public function __construct(
		AppointKit_Services_Repository $services_repo = null,
		AppointKit_Staff_Repository $staff_repo = null,
		AppointKit_Bookings_Repository $bookings_repo = null,
		AppointKit_Slot_Generator $slot_generator = null,
		AppointKit_Conflict_Checker $conflict_checker = null,
		AppointKit_Stripe_Gateway $stripe = null,
		AppointKit_Email_Manager $emails = null
	) {
		$this->services_repo    = $services_repo ?: new AppointKit_Services_Repository();
		$this->staff_repo       = $staff_repo ?: new AppointKit_Staff_Repository();
		$this->bookings_repo    = $bookings_repo ?: new AppointKit_Bookings_Repository();
		$this->slot_generator   = $slot_generator ?: new AppointKit_Slot_Generator();
		$this->conflict_checker = $conflict_checker ?: new AppointKit_Conflict_Checker();
		$this->stripe           = $stripe ?: new AppointKit_Stripe_Gateway();
		$this->emails           = $emails ?: new AppointKit_Email_Manager();
	}

	/**
	 * Create a booking from submitted form data.
	 *
	 * @param array $data {
	 *     @type int    $service_id       Required.
	 *     @type int    $staff_id         Required. Specific staff (resolved from "any" before this point).
	 *     @type string $start_utc        Required. UTC MySQL datetime.
	 *     @type string $customer_name    Required.
	 *     @type string $customer_email   Required.
	 *     @type string $customer_phone   Optional.
	 *     @type string $notes            Optional.
	 *     @type string $payment_method_id Optional. Stripe PaymentMethod ID.
	 * }
	 * @return AppointKit_Booking|WP_Error
	 */
	public function create( array $data ) {
		// Validate required fields.
		$required = array( 'service_id', 'staff_id', 'start_utc', 'customer_name', 'customer_email' );
		foreach ( $required as $field ) {
			if ( empty( $data[ $field ] ) ) {
				/* translators: %s: field name */
				return new WP_Error( 'missing_field', sprintf( __( 'Missing required field: %s', 'appointkit' ), $field ) );
			}
		}

		$service_id = absint( $data['service_id'] );
		$staff_id   = absint( $data['staff_id'] );
		$start_utc  = sanitize_text_field( $data['start_utc'] );

		$service = $this->services_repo->find( $service_id );
		if ( ! $service ) {
			return new WP_Error( 'invalid_service', __( 'Service not found.', 'appointkit' ) );
		}

		$staff = $this->staff_repo->find( $staff_id );
		if ( ! $staff ) {
			return new WP_Error( 'invalid_staff', __( 'Staff member not found.', 'appointkit' ) );
		}

		// Compute end time from service duration.
		$start_dt = new DateTime( $start_utc, new DateTimeZone( 'UTC' ) );
		$end_dt   = clone $start_dt;
		$end_dt->modify( '+' . (int) $service->duration . ' minutes' );
		$end_utc = $end_dt->format( 'Y-m-d H:i:s' );

		// Check slot is still available (race-condition guard).
		if ( ! $this->conflict_checker->is_free( $staff_id, $start_utc, $end_utc ) ) {
			return new WP_Error( 'slot_taken', __( 'Sorry, that time slot is no longer available. Please choose another.', 'appointkit' ) );
		}

		// Build the booking model.
		$booking                  = new AppointKit_Booking();
		$booking->service_id      = $service_id;
		$booking->staff_id        = $staff_id;
		$booking->customer_name   = sanitize_text_field( $data['customer_name'] );
		$booking->customer_email  = sanitize_email( $data['customer_email'] );
		$booking->customer_phone  = sanitize_text_field( $data['customer_phone'] ?? '' );
		$booking->start_utc       = $start_utc;
		$booking->end_utc         = $end_utc;
		$booking->price           = (float) $service->price;
		$booking->notes           = sanitize_textarea_field( $data['notes'] ?? '' );
		$booking->status          = 'pending';
		$booking->payment_status  = 'unpaid';

		// Handle extra fields from Pro custom forms.
		$extra_fields = apply_filters( 'appointkit_booking_form_fields', array() );
		if ( ! empty( $extra_fields ) && ! empty( $data['extra'] ) ) {
			foreach ( $extra_fields as $field ) {
				$field_key = sanitize_key( $field['name'] );
				if ( isset( $data['extra'][ $field_key ] ) ) {
					$booking->set_meta( $field_key, sanitize_text_field( $data['extra'][ $field_key ] ) );
				}
			}
		}

		// Insert the booking row (pending status).
		$booking->id = $this->bookings_repo->save( $booking );
		if ( ! $booking->id ) {
			return new WP_Error( 'db_error', __( 'Could not create booking. Please try again.', 'appointkit' ) );
		}

		do_action( 'appointkit_booking_created', $booking );

		// Handle payment if a price is set and Stripe is configured.
		if ( $booking->price > 0 && ! empty( $data['payment_method_id'] ) && appointkit_stripe_is_configured() ) {
			$payment_result = $this->stripe->charge(
				(int) round( $booking->price * 100 ),
				sanitize_text_field( $data['payment_method_id'] ),
				$booking
			);

			if ( is_wp_error( $payment_result ) ) {
				// Roll back: delete the pending booking.
				$this->bookings_repo->update_status( $booking->id, 'cancelled' );
				return $payment_result;
			}

			$this->bookings_repo->update_payment(
				$booking->id,
				$payment_result->payment_intent_id,
				'paid',
				'confirmed'
			);
			$booking->status            = 'confirmed';
			$booking->payment_status    = 'paid';
			$booking->payment_intent_id = $payment_result->payment_intent_id;
		} elseif ( $booking->price <= 0 ) {
			// Free service — confirm immediately.
			$this->bookings_repo->update_status( $booking->id, 'confirmed' );
			$booking->status = 'confirmed';
		}

		if ( 'confirmed' === $booking->status ) {
			do_action( 'appointkit_booking_confirmed', $booking );
			$this->emails->send_booking_confirmed( $booking );
		}

		return $booking;
	}

	/**
	 * Cancel a booking by ID.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $reason     Optional cancellation reason.
	 * @return bool|WP_Error
	 */
	public function cancel( $booking_id, $reason = '' ) {
		$booking = $this->bookings_repo->find( $booking_id );
		if ( ! $booking ) {
			return new WP_Error( 'not_found', __( 'Booking not found.', 'appointkit' ) );
		}
		if ( in_array( $booking->status, array( 'cancelled', 'completed' ), true ) ) {
			return new WP_Error( 'invalid_status', __( 'This booking cannot be cancelled.', 'appointkit' ) );
		}

		$this->bookings_repo->update_status( $booking_id, 'cancelled' );
		$booking->status = 'cancelled';

		do_action( 'appointkit_booking_cancelled', $booking );
		$this->emails->send_booking_cancelled( $booking );

		return true;
	}
}
