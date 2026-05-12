<?php
/**
 * Availability rules repository.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles database interactions for the availability_rules table.
 */
class AppointKit_Availability_Repository {

	/** @var string */
	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'appointkit_availability_rules';
	}

	/**
	 * Get all availability rules for a staff member.
	 *
	 * @param int $staff_id Staff ID.
	 * @return AppointKit_Availability_Rule[]
	 */
	public function get_for_staff( $staff_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE staff_id = %d ORDER BY type ASC, weekday ASC, date ASC",
				$staff_id
			)
		);
		return array_map( array( $this, 'row_to_model' ), $rows ?: array() );
	}

	/**
	 * Get weekday rules for a staff member.
	 *
	 * @param int $staff_id Staff ID.
	 * @return AppointKit_Availability_Rule[] Keyed by weekday (0–6).
	 */
	public function get_weekday_rules( $staff_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE staff_id = %d AND type = %s ORDER BY weekday ASC",
				$staff_id,
				'weekday'
			)
		);
		$rules = array();
		foreach ( $rows ?: array() as $row ) {
			$rules[ (int) $row->weekday ] = $this->row_to_model( $row );
		}
		return $rules;
	}

	/**
	 * Get date-override rules for a staff member within a date range.
	 *
	 * @param int    $staff_id   Staff ID.
	 * @param string $date_start Y-m-d.
	 * @param string $date_end   Y-m-d.
	 * @return AppointKit_Availability_Rule[] Keyed by date string.
	 */
	public function get_date_overrides( $staff_id, $date_start, $date_end ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table}
				 WHERE staff_id = %d AND type = %s AND date BETWEEN %s AND %s",
				$staff_id,
				'date',
				$date_start,
				$date_end
			)
		);
		$rules = array();
		foreach ( $rows ?: array() as $row ) {
			$rules[ $row->date ] = $this->row_to_model( $row );
		}
		return $rules;
	}

	/**
	 * Insert or update an availability rule.
	 *
	 * @param AppointKit_Availability_Rule $rule Rule model.
	 * @return int Inserted/updated ID.
	 */
	public function save( AppointKit_Availability_Rule $rule ) {
		global $wpdb;
		$now  = current_time( 'mysql', true );
		$data = array(
			'staff_id'   => absint( $rule->staff_id ),
			'type'       => sanitize_text_field( $rule->type ),
			'weekday'    => absint( $rule->weekday ),
			'date'       => sanitize_text_field( $rule->date ),
			'start_time' => appointkit_sanitize_time( $rule->start_time ),
			'end_time'   => appointkit_sanitize_time( $rule->end_time ),
			'is_off'     => (int) (bool) $rule->is_off,
			'updated_at' => $now,
		);
		$format = array( '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s' );

		if ( $rule->id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->update( $this->table, $data, array( 'id' => $rule->id ), $format, array( '%d' ) );
			return $rule->id;
		}

		$data['created_at'] = $now;
		$format[]           = '%s';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $this->table, $data, $format );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Delete an availability rule by ID.
	 *
	 * @param int $id Rule ID.
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return (bool) $wpdb->delete( $this->table, array( 'id' => absint( $id ) ), array( '%d' ) );
	}

	/**
	 * Delete all rules for a staff member.
	 *
	 * @param int $staff_id Staff ID.
	 */
	public function delete_for_staff( $staff_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete( $this->table, array( 'staff_id' => absint( $staff_id ) ), array( '%d' ) );
	}

	/**
	 * Convert a stdClass row to AppointKit_Availability_Rule.
	 *
	 * @param object $row Database row.
	 * @return AppointKit_Availability_Rule
	 */
	private function row_to_model( $row ) {
		return new AppointKit_Availability_Rule( $row );
	}
}
