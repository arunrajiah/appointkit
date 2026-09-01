<?php
/**
 * Bookings repository.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles all database interactions for the bookings table.
 */
class AppointKit_Bookings_Repository {

	/** @var string */
	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'appointkit_bookings';
	}

	/**
	 * Get all bookings for a staff member in a UTC datetime range.
	 *
	 * @param int    $staff_id  Staff ID.
	 * @param string $start_utc Start datetime (UTC, MySQL format).
	 * @param string $end_utc   End datetime (UTC, MySQL format).
	 * @return AppointKit_Booking[]
	 */
	public function get_for_staff_in_range( $staff_id, $start_utc, $end_utc ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from $wpdb->prefix and a hardcoded literal; all values use placeholders.
				"SELECT * FROM {$this->table}
				 WHERE staff_id = %d
				   AND status NOT IN ('cancelled')
				   AND start_utc < %s
				   AND end_utc > %s",
				$staff_id,
				$end_utc,
				$start_utc
			)
		);
		return array_map( array( $this, 'row_to_model' ), $rows ?: array() );
	}

	/**
	 * Get bookings for a customer email.
	 *
	 * @param string $email Customer email address.
	 * @return AppointKit_Booking[]
	 */
	public function get_by_customer_email( $email ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from $wpdb->prefix and a hardcoded literal; all values use placeholders.
				"SELECT * FROM {$this->table} WHERE customer_email = %s ORDER BY start_utc DESC",
				sanitize_email( $email )
			)
		);
		return array_map( array( $this, 'row_to_model' ), $rows ?: array() );
	}

	/**
	 * Find a booking by ID.
	 *
	 * @param int $id Booking ID.
	 * @return AppointKit_Booking|null
	 */
	public function find( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from $wpdb->prefix and a hardcoded literal; all values use placeholders.
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
		);
		return $row ? $this->row_to_model( $row ) : null;
	}

	/**
	 * Find a booking by Stripe PaymentIntent ID.
	 *
	 * @param string $payment_intent_id Stripe PI ID.
	 * @return AppointKit_Booking|null
	 */
	public function find_by_payment_intent( $payment_intent_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from $wpdb->prefix and a hardcoded literal; all values use placeholders.
				"SELECT * FROM {$this->table} WHERE payment_intent_id = %s",
				sanitize_text_field( $payment_intent_id )
			)
		);
		return $row ? $this->row_to_model( $row ) : null;
	}

	/**
	 * Get upcoming bookings that need a reminder email sent.
	 *
	 * @param string $window_start_utc Window start (UTC MySQL datetime).
	 * @param string $window_end_utc   Window end (UTC MySQL datetime).
	 * @return AppointKit_Booking[]
	 */
	public function get_upcoming_for_reminder( $window_start_utc, $window_end_utc ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from $wpdb->prefix and a hardcoded literal; all values use placeholders.
				"SELECT * FROM {$this->table}
				 WHERE status = %s
				   AND reminder_sent = 0
				   AND start_utc BETWEEN %s AND %s",
				'confirmed',
				$window_start_utc,
				$window_end_utc
			)
		);
		return array_map( array( $this, 'row_to_model' ), $rows ?: array() );
	}

	/**
	 * Mark a booking's reminder as sent.
	 *
	 * @param int $booking_id Booking ID.
	 */
	public function mark_reminder_sent( $booking_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update(
			$this->table,
			array( 'reminder_sent' => 1, 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => absint( $booking_id ) ),
			array( '%d', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Delete expired pending bookings.
	 *
	 * @param string $cutoff_utc Cutoff datetime (UTC MySQL format). Pending bookings created before this are deleted.
	 */
	public function delete_expired_pending( $cutoff_utc ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from $wpdb->prefix and a hardcoded literal; all values use placeholders.
				"DELETE FROM {$this->table} WHERE status = %s AND created_at < %s",
				'pending',
				$cutoff_utc
			)
		);
	}

	/**
	 * Insert or update a booking.
	 *
	 * @param AppointKit_Booking $booking Booking model.
	 * @return int Inserted/updated ID.
	 */
	public function save( AppointKit_Booking $booking ) {
		global $wpdb;
		$now  = current_time( 'mysql', true );
		$data = array(
			'service_id'        => absint( $booking->service_id ),
			'staff_id'          => absint( $booking->staff_id ),
			'customer_name'     => sanitize_text_field( $booking->customer_name ),
			'customer_email'    => sanitize_email( $booking->customer_email ),
			'customer_phone'    => sanitize_text_field( $booking->customer_phone ),
			'start_utc'         => sanitize_text_field( $booking->start_utc ),
			'end_utc'           => sanitize_text_field( $booking->end_utc ),
			'status'            => sanitize_text_field( $booking->status ),
			'price'             => (float) $booking->price,
			'payment_intent_id' => sanitize_text_field( $booking->payment_intent_id ),
			'payment_status'    => sanitize_text_field( $booking->payment_status ),
			'notes'             => sanitize_textarea_field( $booking->notes ),
			'meta'              => $booking->meta,
			'updated_at'        => $now,
		);
		$format = array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s' );

		if ( $booking->id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->update( $this->table, $data, array( 'id' => $booking->id ), $format, array( '%d' ) );
			return $booking->id;
		}

		$data['created_at']    = $now;
		$data['reminder_sent'] = 0;
		$format[]              = '%s';
		$format[]              = '%d';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $this->table, $data, $format );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Update only the status field.
	 *
	 * @param int    $id     Booking ID.
	 * @param string $status New status.
	 * @return bool
	 */
	public function update_status( $id, $status ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return (bool) $wpdb->update(
			$this->table,
			array(
				'status'     => sanitize_text_field( $status ),
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $id ) ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Update payment fields after a successful charge.
	 *
	 * @param int    $id                Booking ID.
	 * @param string $payment_intent_id Stripe PI ID.
	 * @param string $payment_status    New payment status.
	 * @param string $booking_status    New booking status.
	 */
	public function update_payment( $id, $payment_intent_id, $payment_status, $booking_status ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update(
			$this->table,
			array(
				'payment_intent_id' => sanitize_text_field( $payment_intent_id ),
				'payment_status'    => sanitize_text_field( $payment_status ),
				'status'            => sanitize_text_field( $booking_status ),
				'updated_at'        => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $id ) ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Get paginated bookings for the admin list view.
	 *
	 * @param array $args Query arguments: status, staff_id, service_id, search, per_page, paged, orderby, order.
	 * @return array{ bookings: AppointKit_Booking[], total: int }
	 */
	public function get_list( array $args = array() ) {
		global $wpdb;
		$defaults = array(
			'status'     => '',
			'staff_id'   => 0,
			'service_id' => 0,
			'search'     => '',
			'per_page'   => 25,
			'paged'      => 1,
			'orderby'    => 'start_utc',
			'order'      => 'DESC',
		);
		$args     = wp_parse_args( $args, $defaults );
		$where    = array( '1=1' );
		$values   = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = sanitize_text_field( $args['status'] );
		}
		if ( ! empty( $args['staff_id'] ) ) {
			$where[]  = 'staff_id = %d';
			$values[] = absint( $args['staff_id'] );
		}
		if ( ! empty( $args['service_id'] ) ) {
			$where[]  = 'service_id = %d';
			$values[] = absint( $args['service_id'] );
		}
		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(customer_name LIKE %s OR customer_email LIKE %s)';
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$values[] = $like;
			$values[] = $like;
		}

		$where_sql  = implode( ' AND ', $where );
		$allowed_ob = array( 'id', 'start_utc', 'status', 'customer_name', 'created_at' );
		$orderby    = in_array( $args['orderby'], $allowed_ob, true ) ? $args['orderby'] : 'start_utc';
		$order      = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$limit      = absint( $args['per_page'] );
		$offset     = ( absint( $args['paged'] ) - 1 ) * $limit;

		$count_sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$where_sql}";
		$rows_sql  = "SELECT * FROM {$this->table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $values ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( $rows_sql, array_merge( $values, array( $limit, $offset ) ) ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $count_sql );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( $rows_sql, $limit, $offset ) );
		}

		return array(
			'bookings' => array_map( array( $this, 'row_to_model' ), $rows ?: array() ),
			'total'    => $total,
		);
	}

	/**
	 * Convert a stdClass row to AppointKit_Booking.
	 *
	 * @param object $row Database row.
	 * @return AppointKit_Booking
	 */
	private function row_to_model( $row ) {
		return new AppointKit_Booking( $row );
	}
}
