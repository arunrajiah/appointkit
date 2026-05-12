<?php
/**
 * REST API endpoints.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers all /wp-json/appointkit/v1/* REST endpoints.
 */
class AppointKit_REST_API {

	private const NAMESPACE = 'appointkit/v1';

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		register_rest_route( self::NAMESPACE, '/services', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_services' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( self::NAMESPACE, '/staff', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_staff' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'service_id' => array(
					'required'          => false,
					'validate_callback' => array( $this, 'validate_positive_int' ),
					'sanitize_callback' => 'absint',
				),
			),
		) );

		register_rest_route( self::NAMESPACE, '/slots', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_slots' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'service_id' => array(
					'required'          => true,
					'validate_callback' => array( $this, 'validate_positive_int' ),
					'sanitize_callback' => 'absint',
				),
				'staff_id'   => array(
					'required'          => false,
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
				'date'       => array(
					'required'          => true,
					'validate_callback' => array( $this, 'validate_date' ),
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );

		register_rest_route( self::NAMESPACE, '/bookings', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'create_booking' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'service_id'        => array( 'required' => true, 'sanitize_callback' => 'absint' ),
				'staff_id'          => array( 'required' => true, 'sanitize_callback' => 'absint' ),
				'start_utc'         => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'customer_name'     => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'customer_email'    => array( 'required' => true, 'sanitize_callback' => 'sanitize_email' ),
				'customer_phone'    => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
				'notes'             => array( 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field' ),
				'payment_method_id' => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
			),
		) );

		register_rest_route( self::NAMESPACE, '/bookings/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_booking' ),
			'permission_callback' => array( $this, 'booking_read_permission' ),
			'args'                => array(
				'id' => array( 'validate_callback' => array( $this, 'validate_positive_int' ) ),
			),
		) );

		register_rest_route( self::NAMESPACE, '/bookings/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => array( $this, 'cancel_booking' ),
			'permission_callback' => array( $this, 'booking_cancel_permission' ),
			'args'                => array(
				'id'    => array( 'validate_callback' => array( $this, 'validate_positive_int' ) ),
				'email' => array( 'required' => true, 'sanitize_callback' => 'sanitize_email' ),
			),
		) );

		// Admin-only calendar events endpoint.
		register_rest_route( self::NAMESPACE, '/calendar-events', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_calendar_events' ),
			'permission_callback' => array( $this, 'admin_permission' ),
			'args'                => array(
				'start' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'end'   => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
			),
		) );
	}

	/**
	 * GET /services
	 *
	 * @return WP_REST_Response
	 */
	public function get_services() {
		$services = ( new AppointKit_Services_Repository() )->get_all();
		$data     = array_map( function ( $s ) {
			return array(
				'id'            => $s->id,
				'name'          => $s->name,
				'description'   => $s->description,
				'duration'      => $s->duration,
				'price'         => $s->price,
				'price_display' => appointkit_format_price( $s->price ),
				'color'         => $s->color,
				'slot_interval' => $s->slot_interval,
			);
		}, $services );
		return rest_ensure_response( $data );
	}

	/**
	 * GET /staff
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function get_staff( WP_REST_Request $request ) {
		$service_id = $request->get_param( 'service_id' );
		$repo       = new AppointKit_Staff_Repository();

		if ( $service_id ) {
			$staff_list = $repo->get_by_service( $service_id );
		} else {
			$staff_list = $repo->get_all();
		}

		$data = array_map( function ( $s ) {
			return array(
				'id'        => $s->id,
				'name'      => $s->name,
				'bio'       => $s->bio,
				'photo_url' => $s->photo_url,
			);
		}, $staff_list );
		return rest_ensure_response( $data );
	}

	/**
	 * GET /slots
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function get_slots( WP_REST_Request $request ) {
		$generator = new AppointKit_Slot_Generator();
		$slots     = $generator->get_slots(
			$request->get_param( 'service_id' ),
			$request->get_param( 'staff_id' ),
			$request->get_param( 'date' )
		);
		return rest_ensure_response( $slots );
	}

	/**
	 * POST /bookings
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_booking( WP_REST_Request $request ) {
		$creator = new AppointKit_Booking_Creator();
		$result  = $creator->create( $request->get_params() );

		if ( is_wp_error( $result ) ) {
			return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => 422 ) );
		}

		$service = ( new AppointKit_Services_Repository() )->find( $result->service_id );
		return rest_ensure_response( array(
			'id'             => $result->id,
			'status'         => $result->status,
			'payment_status' => $result->payment_status,
			'start_display'  => AppointKit_Timezone::format_for_display( $result->start_utc ),
			'end_display'    => AppointKit_Timezone::format_for_display( $result->end_utc ),
			'service_name'   => $service ? $service->name : '',
		) );
	}

	/**
	 * GET /bookings/{id}
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_booking( WP_REST_Request $request ) {
		$booking = ( new AppointKit_Bookings_Repository() )->find( $request->get_param( 'id' ) );
		if ( ! $booking ) {
			return new WP_Error( 'not_found', __( 'Booking not found.', 'appointkit' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( $this->format_booking( $booking ) );
	}

	/**
	 * DELETE /bookings/{id}
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function cancel_booking( WP_REST_Request $request ) {
		$booking_id = $request->get_param( 'id' );
		$email      = $request->get_param( 'email' );
		$booking    = ( new AppointKit_Bookings_Repository() )->find( $booking_id );

		if ( ! $booking || strtolower( $booking->customer_email ) !== strtolower( $email ) ) {
			return new WP_Error( 'not_found', __( 'Booking not found.', 'appointkit' ), array( 'status' => 404 ) );
		}

		$creator = new AppointKit_Booking_Creator();
		$result  = $creator->cancel( $booking_id );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => 422 ) );
		}

		return rest_ensure_response( array( 'cancelled' => true ) );
	}

	/**
	 * GET /calendar-events (admin only)
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function get_calendar_events( WP_REST_Request $request ) {
		global $wpdb;
		$start = sanitize_text_field( $request->get_param( 'start' ) );
		$end   = sanitize_text_field( $request->get_param( 'end' ) );
		$table = $wpdb->prefix . 'appointkit_bookings';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$bookings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.*, s.name AS service_name, s.color AS service_color, st.name AS staff_name
				 FROM {$table} b
				 LEFT JOIN {$wpdb->prefix}appointkit_services s ON s.id = b.service_id
				 LEFT JOIN {$wpdb->prefix}appointkit_staff st ON st.id = b.staff_id
				 WHERE b.status NOT IN ('cancelled')
				   AND b.start_utc < %s
				   AND b.end_utc > %s",
				$end,
				$start
			)
		);

		$events = array_map( function ( $row ) {
			return array(
				'id'    => $row->id,
				'title' => esc_html( $row->service_name . ' — ' . $row->customer_name ),
				'start' => AppointKit_Timezone::format_for_display( $row->start_utc, 'c' ),
				'end'   => AppointKit_Timezone::format_for_display( $row->end_utc, 'c' ),
				'color' => esc_html( $row->service_color ),
				'extendedProps' => array(
					'booking_id'   => (int) $row->id,
					'staff_name'   => esc_html( $row->staff_name ),
					'status'       => esc_html( $row->status ),
				),
			);
		}, $bookings ?: array() );

		return rest_ensure_response( $events );
	}

	// --- Permission callbacks ---

	/**
	 * Permission: admin only.
	 *
	 * @return bool
	 */
	public function admin_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Permission: admin, or the booking owner (verified by email).
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return bool
	 */
	public function booking_read_permission( WP_REST_Request $request ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$booking = ( new AppointKit_Bookings_Repository() )->find( $request->get_param( 'id' ) );
		$email   = sanitize_email( $request->get_header( 'X-Appointkit-Email' ) ?: '' );
		return $booking && $email && strtolower( $booking->customer_email ) === strtolower( $email );
	}

	/**
	 * Permission: admin, or booking owner (email in body).
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return bool
	 */
	public function booking_cancel_permission( WP_REST_Request $request ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$email = sanitize_email( $request->get_param( 'email' ) ?: '' );
		return ! empty( $email );
	}

	// --- Validation helpers ---

	/**
	 * Validate that a value is a positive integer.
	 *
	 * @param mixed $value Parameter value.
	 * @return bool
	 */
	public function validate_positive_int( $value ) {
		return is_numeric( $value ) && (int) $value > 0;
	}

	/**
	 * Validate a Y-m-d date string.
	 *
	 * @param string $value Date string.
	 * @return bool
	 */
	public function validate_date( $value ) {
		$d = DateTime::createFromFormat( 'Y-m-d', $value );
		return $d && $d->format( 'Y-m-d' ) === $value;
	}

	/**
	 * Format a booking model for API output.
	 *
	 * @param AppointKit_Booking $booking Booking model.
	 * @return array
	 */
	private function format_booking( AppointKit_Booking $booking ) {
		return array(
			'id'             => $booking->id,
			'service_id'     => $booking->service_id,
			'staff_id'       => $booking->staff_id,
			'customer_name'  => $booking->customer_name,
			'customer_email' => $booking->customer_email,
			'start_utc'      => $booking->start_utc,
			'end_utc'        => $booking->end_utc,
			'start_display'  => AppointKit_Timezone::format_for_display( $booking->start_utc ),
			'end_display'    => AppointKit_Timezone::format_for_display( $booking->end_utc ),
			'status'         => $booking->status,
			'payment_status' => $booking->payment_status,
		);
	}
}
