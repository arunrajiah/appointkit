<?php
/**
 * Staff repository.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles all database interactions for the staff table and staff-service pivot.
 */
class AppointKit_Staff_Repository {

	/** @var string */
	private $table;

	/** @var string */
	private $pivot_table;

	public function __construct() {
		global $wpdb;
		$this->table       = $wpdb->prefix . 'appointkit_staff';
		$this->pivot_table = $wpdb->prefix . 'appointkit_staff_services';
	}

	/**
	 * Find all active staff members.
	 *
	 * @return AppointKit_Staff[]
	 */
	public function get_all() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from $wpdb->prefix and a hardcoded literal; all values use placeholders.
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE status = %s ORDER BY name ASC", 'active' )
		);
		$staff = array_map( array( $this, 'row_to_model' ), $rows ?: array() );
		foreach ( $staff as $member ) {
			$member->service_ids = $this->get_service_ids( $member->id );
		}
		return $staff;
	}

	/**
	 * Find staff members who offer a specific service.
	 *
	 * @param int $service_id Service ID.
	 * @return AppointKit_Staff[]
	 */
	public function get_by_service( $service_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are built from $wpdb->prefix and hardcoded literals; all values use placeholders.
			$wpdb->prepare(
				"SELECT s.* FROM {$this->table} s
				 INNER JOIN {$this->pivot_table} p ON p.staff_id = s.id
				 WHERE p.service_id = %d AND s.status = %s
				 ORDER BY s.name ASC",
				$service_id,
				'active'
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$staff = array_map( array( $this, 'row_to_model' ), $rows ?: array() );
		foreach ( $staff as $member ) {
			$member->service_ids = $this->get_service_ids( $member->id );
		}
		return $staff;
	}

	/**
	 * Find a single staff member by ID.
	 *
	 * @param int $id Staff ID.
	 * @return AppointKit_Staff|null
	 */
	public function find( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from $wpdb->prefix and a hardcoded literal; all values use placeholders.
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id )
		);
		if ( ! $row ) {
			return null;
		}
		$staff              = $this->row_to_model( $row );
		$staff->service_ids = $this->get_service_ids( $staff->id );
		return $staff;
	}

	/**
	 * Get service IDs for a staff member.
	 *
	 * @param int $staff_id Staff ID.
	 * @return int[]
	 */
	public function get_service_ids( $staff_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from $wpdb->prefix and a hardcoded literal; all values use placeholders.
			$wpdb->prepare( "SELECT service_id FROM {$this->pivot_table} WHERE staff_id = %d", $staff_id )
		);
		return array_map( 'intval', $ids ?: array() );
	}

	/**
	 * Insert or update a staff member and their service assignments.
	 *
	 * @param AppointKit_Staff $staff       Staff model.
	 * @param int[]            $service_ids Service IDs to assign.
	 * @return int Inserted/updated ID.
	 */
	public function save( AppointKit_Staff $staff, array $service_ids = array() ) {
		global $wpdb;
		$now  = current_time( 'mysql', true );
		$data = array(
			'wp_user_id'             => absint( $staff->wp_user_id ),
			'name'                   => sanitize_text_field( $staff->name ),
			'email'                  => sanitize_email( $staff->email ),
			'phone'                  => sanitize_text_field( $staff->phone ),
			'bio'                    => wp_kses_post( $staff->bio ),
			'photo_url'              => esc_url_raw( $staff->photo_url ),
			'timezone'               => sanitize_text_field( $staff->timezone ),
			'google_calendar_token'  => $staff->google_calendar_token,
			'ical_token'             => sanitize_text_field( $staff->ical_token ) ?: appointkit_generate_ical_token(),
			'status'                 => sanitize_text_field( $staff->status ),
			'updated_at'             => $now,
		);
		$format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

		if ( $staff->id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->update( $this->table, $data, array( 'id' => $staff->id ), $format, array( '%d' ) );
			$id = $staff->id;
		} else {
			$data['created_at'] = $now;
			$format[]           = '%s';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert( $this->table, $data, $format );
			$id = (int) $wpdb->insert_id;
		}

		$this->sync_services( $id, $service_ids );
		return $id;
	}

	/**
	 * Update the service assignments for a staff member.
	 *
	 * @param int   $staff_id    Staff ID.
	 * @param int[] $service_ids New list of service IDs.
	 */
	private function sync_services( $staff_id, array $service_ids ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete( $this->pivot_table, array( 'staff_id' => $staff_id ), array( '%d' ) );
		foreach ( $service_ids as $sid ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$this->pivot_table,
				array(
					'staff_id'   => $staff_id,
					'service_id' => absint( $sid ),
				),
				array( '%d', '%d' )
			);
		}
	}

	/**
	 * Delete a staff member by ID.
	 *
	 * @param int $id Staff ID.
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete( $this->pivot_table, array( 'staff_id' => absint( $id ) ), array( '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return (bool) $wpdb->delete( $this->table, array( 'id' => absint( $id ) ), array( '%d' ) );
	}

	/**
	 * Convert a stdClass row to AppointKit_Staff.
	 *
	 * @param object $row Database row.
	 * @return AppointKit_Staff
	 */
	private function row_to_model( $row ) {
		return new AppointKit_Staff( $row );
	}
}
