<?php
/**
 * Services repository.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles all database interactions for the services table.
 */
class AppointKit_Services_Repository {

	/**
	 * @var string
	 */
	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'appointkit_services';
	}

	/**
	 * Find all active services.
	 *
	 * @return AppointKit_Service[]
	 */
	public function get_all() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from $wpdb->prefix and a hardcoded literal; all values use placeholders.
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE status = %s ORDER BY name ASC", 'active' )
		);
		return array_map( array( $this, 'row_to_model' ), $rows ?: array() );
	}

	/**
	 * Find a single service by ID.
	 *
	 * @param int $id Service ID.
	 * @return AppointKit_Service|null
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
	 * Insert or update a service.
	 *
	 * @param AppointKit_Service $service Service model.
	 * @return int Inserted/updated ID.
	 */
	public function save( AppointKit_Service $service ) {
		global $wpdb;
		$now  = current_time( 'mysql', true );
		$data = array(
			'name'          => sanitize_text_field( $service->name ),
			'description'   => wp_kses_post( $service->description ),
			'duration'      => absint( $service->duration ),
			'price'         => (float) $service->price,
			'color'         => sanitize_hex_color( $service->color ) ?: '#3788d8',
			'slot_interval' => absint( $service->slot_interval ),
			'buffer_before' => absint( $service->buffer_before ),
			'buffer_after'  => absint( $service->buffer_after ),
			'status'        => sanitize_text_field( $service->status ),
			'updated_at'    => $now,
		);
		$format = array( '%s', '%s', '%d', '%f', '%s', '%d', '%d', '%d', '%s', '%s' );

		if ( $service->id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->update( $this->table, $data, array( 'id' => $service->id ), $format, array( '%d' ) );
			return $service->id;
		}

		$data['created_at'] = $now;
		$format[]           = '%s';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $this->table, $data, $format );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Delete a service by ID.
	 *
	 * @param int $id Service ID.
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return (bool) $wpdb->delete( $this->table, array( 'id' => absint( $id ) ), array( '%d' ) );
	}

	/**
	 * Convert a stdClass row to an AppointKit_Service.
	 *
	 * @param object $row Database row.
	 * @return AppointKit_Service
	 */
	private function row_to_model( $row ) {
		return new AppointKit_Service( $row );
	}
}
