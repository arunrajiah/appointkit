<?php
/**
 * Tests for AppointKit_Conflict_Checker.
 *
 * @package AppointKit
 */

class Test_Conflict_Checker extends WP_UnitTestCase {

	/** @var AppointKit_Conflict_Checker */
	private $checker;

	/** @var AppointKit_Bookings_Repository|MockObject */
	private $repo;

	public function setUp(): void {
		parent::setUp();
		$this->repo    = $this->createMock( AppointKit_Bookings_Repository::class );
		$this->checker = new AppointKit_Conflict_Checker( $this->repo );
	}

	private function make_booking( string $start_utc, string $end_utc, int $id = 1 ): AppointKit_Booking {
		return new AppointKit_Booking( array(
			'id'        => $id,
			'staff_id'  => 1,
			'start_utc' => $start_utc,
			'end_utc'   => $end_utc,
			'status'    => 'confirmed',
		) );
	}

	public function test_is_free_when_no_existing_bookings() {
		$this->repo->method( 'get_for_staff_in_range' )->willReturn( array() );
		$this->assertTrue( $this->checker->is_free( 1, '2030-06-10 10:00:00', '2030-06-10 11:00:00' ) );
	}

	public function test_conflict_with_overlapping_booking() {
		$existing = $this->make_booking( '2030-06-10 09:30:00', '2030-06-10 10:30:00' );
		$this->repo->method( 'get_for_staff_in_range' )->willReturn( array( $existing ) );
		$this->assertFalse( $this->checker->is_free( 1, '2030-06-10 10:00:00', '2030-06-10 11:00:00' ) );
	}

	public function test_no_conflict_when_adjacent_bookings() {
		$existing = $this->make_booking( '2030-06-10 09:00:00', '2030-06-10 10:00:00' );
		$this->repo->method( 'get_for_staff_in_range' )->willReturn( array( $existing ) );
		// New slot starts exactly when existing ends.
		$this->assertTrue( $this->checker->is_free( 1, '2030-06-10 10:00:00', '2030-06-10 11:00:00' ) );
	}

	public function test_exclude_booking_id_skips_own_booking() {
		$existing = $this->make_booking( '2030-06-10 10:00:00', '2030-06-10 11:00:00', 42 );
		$this->repo->method( 'get_for_staff_in_range' )->willReturn( array( $existing ) );
		// Excluding booking 42 should result in no conflict.
		$this->assertTrue( $this->checker->is_free( 1, '2030-06-10 10:00:00', '2030-06-10 11:00:00', 42 ) );
	}
}
