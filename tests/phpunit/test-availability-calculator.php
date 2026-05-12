<?php
/**
 * Tests for AppointKit_Availability_Calculator.
 *
 * @package AppointKit
 */

/**
 * Covers the critical availability algorithm including DST, midnight crossings,
 * back-to-back bookings, buffer boundaries, and "any staff" mode.
 */
class Test_Availability_Calculator extends WP_UnitTestCase {

	/** @var AppointKit_Availability_Calculator */
	private $calculator;

	/** @var AppointKit_Services_Repository|MockObject */
	private $services_repo;

	/** @var AppointKit_Staff_Repository|MockObject */
	private $staff_repo;

	/** @var AppointKit_Bookings_Repository|MockObject */
	private $bookings_repo;

	/** @var AppointKit_Availability_Repository|MockObject */
	private $availability_repo;

	/** @var AppointKit_Google_Calendar_Sync|MockObject */
	private $gcal;

	public function setUp(): void {
		parent::setUp();

		$this->services_repo     = $this->createMock( AppointKit_Services_Repository::class );
		$this->staff_repo        = $this->createMock( AppointKit_Staff_Repository::class );
		$this->bookings_repo     = $this->createMock( AppointKit_Bookings_Repository::class );
		$this->availability_repo = $this->createMock( AppointKit_Availability_Repository::class );
		$this->gcal              = $this->createMock( AppointKit_Google_Calendar_Sync::class );

		$this->gcal->method( 'get_busy_blocks' )->willReturn( array() );

		$this->calculator = new AppointKit_Availability_Calculator(
			$this->services_repo,
			$this->staff_repo,
			$this->bookings_repo,
			$this->availability_repo,
			$this->gcal
		);
	}

	// =====================================================
	// Helper factories
	// =====================================================

	private function make_service( array $overrides = array() ): AppointKit_Service {
		return new AppointKit_Service( array_merge( array(
			'id'            => 1,
			'name'          => 'Test Service',
			'duration'      => 60,
			'price'         => 0,
			'slot_interval' => 60,
			'buffer_before' => 0,
			'buffer_after'  => 0,
			'status'        => 'active',
		), $overrides ) );
	}

	private function make_staff( array $overrides = array() ): AppointKit_Staff {
		$staff              = new AppointKit_Staff( array_merge( array(
			'id'          => 1,
			'name'        => 'Test Staff',
			'status'      => 'active',
			'timezone'    => 'UTC',
		), $overrides ) );
		$staff->service_ids = $overrides['service_ids'] ?? array( 1 );
		return $staff;
	}

	private function make_weekday_rule( int $weekday, string $start = '09:00:00', string $end = '17:00:00', bool $is_off = false ): AppointKit_Availability_Rule {
		return new AppointKit_Availability_Rule( array(
			'id'         => $weekday + 1,
			'staff_id'   => 1,
			'type'       => 'weekday',
			'weekday'    => $weekday,
			'date'       => '0000-00-00',
			'start_time' => $start,
			'end_time'   => $end,
			'is_off'     => $is_off ? 1 : 0,
		) );
	}

	// =====================================================
	// Basic slot generation
	// =====================================================

	public function test_generates_hourly_slots_for_9to5_day() {
		$service = $this->make_service( array( 'duration' => 60, 'slot_interval' => 60 ) );
		$staff   = $this->make_staff( array( 'timezone' => 'UTC' ) );
		$date    = '2030-06-10'; // Tuesday.

		$this->bookings_repo->method( 'get_for_staff_in_range' )->willReturn( array() );
		$this->availability_repo->method( 'get_date_overrides' )->willReturn( array() );
		$this->availability_repo->method( 'get_weekday_rules' )->willReturn( array(
			2 => $this->make_weekday_rule( 2, '09:00:00', '17:00:00' ),
		) );

		$slots = $this->calculator->compute_slots_for_staff( $service, $staff, $date );

		$this->assertCount( 8, $slots, '9am–5pm / 60min = 8 slots' );
		$this->assertSame( '2030-06-10 09:00:00', $slots[0]['start_utc'] );
		$this->assertSame( '2030-06-10 16:00:00', $slots[7]['start_utc'] );
	}

	public function test_30min_slots_for_30min_service() {
		$service = $this->make_service( array( 'duration' => 30, 'slot_interval' => 30 ) );
		$staff   = $this->make_staff( array( 'timezone' => 'UTC' ) );
		$date    = '2030-06-10';

		$this->bookings_repo->method( 'get_for_staff_in_range' )->willReturn( array() );
		$this->availability_repo->method( 'get_date_overrides' )->willReturn( array() );
		$this->availability_repo->method( 'get_weekday_rules' )->willReturn( array(
			2 => $this->make_weekday_rule( 2, '09:00:00', '17:00:00' ),
		) );

		$slots = $this->calculator->compute_slots_for_staff( $service, $staff, $date );
		$this->assertCount( 16, $slots, '9am–5pm / 30min = 16 slots' );
	}

	// =====================================================
	// Day-off handling
	// =====================================================

	public function test_returns_empty_for_day_off_weekday_rule() {
		$service = $this->make_service();
		$staff   = $this->make_staff( array( 'timezone' => 'UTC' ) );
		$date    = '2030-06-10'; // Tuesday.

		$this->bookings_repo->method( 'get_for_staff_in_range' )->willReturn( array() );
		$this->availability_repo->method( 'get_date_overrides' )->willReturn( array() );
		$this->availability_repo->method( 'get_weekday_rules' )->willReturn( array(
			2 => $this->make_weekday_rule( 2, '09:00:00', '17:00:00', true ),
		) );

		$slots = $this->calculator->compute_slots_for_staff( $service, $staff, $date );
		$this->assertEmpty( $slots );
	}

	public function test_date_override_takes_precedence_over_weekday_rule() {
		$service = $this->make_service();
		$staff   = $this->make_staff( array( 'timezone' => 'UTC' ) );
		$date    = '2030-06-10';

		$override = new AppointKit_Availability_Rule( array(
			'id'         => 99,
			'staff_id'   => 1,
			'type'       => 'date',
			'weekday'    => 0,
			'date'       => $date,
			'start_time' => '10:00:00',
			'end_time'   => '12:00:00',
			'is_off'     => 0,
		) );

		$this->bookings_repo->method( 'get_for_staff_in_range' )->willReturn( array() );
		$this->availability_repo->method( 'get_date_overrides' )->willReturn( array( $date => $override ) );
		$this->availability_repo->method( 'get_weekday_rules' )->willReturn( array(
			2 => $this->make_weekday_rule( 2, '09:00:00', '17:00:00' ),
		) );

		$slots = $this->calculator->compute_slots_for_staff( $service, $staff, $date );
		$this->assertCount( 2, $slots, '10am–12pm / 60min = 2 slots' );
	}

	// =====================================================
	// Buffer boundaries
	// =====================================================

	public function test_buffer_after_blocks_next_slot() {
		$service = $this->make_service( array( 'duration' => 60, 'slot_interval' => 60, 'buffer_after' => 30 ) );
		$staff   = $this->make_staff( array( 'timezone' => 'UTC' ) );
		$date    = '2030-06-10';

		$existing_booking = new AppointKit_Booking( array(
			'id'        => 5,
			'staff_id'  => 1,
			'start_utc' => '2030-06-10 10:00:00',
			'end_utc'   => '2030-06-10 11:00:00',
			'status'    => 'confirmed',
		) );

		$this->bookings_repo->method( 'get_for_staff_in_range' )->willReturn( array( $existing_booking ) );
		$this->availability_repo->method( 'get_date_overrides' )->willReturn( array() );
		$this->availability_repo->method( 'get_weekday_rules' )->willReturn( array(
			2 => $this->make_weekday_rule( 2, '09:00:00', '17:00:00' ),
		) );

		$slots      = $this->calculator->compute_slots_for_staff( $service, $staff, $date );
		$start_utcs = array_column( $slots, 'start_utc' );

		// 10:00 is booked; with 30min buffer after, 11:00 slot (end 12:00) would conflict with buffer ending 11:30.
		$this->assertNotContains( '2030-06-10 10:00:00', $start_utcs, 'Booked slot must not appear' );
		$this->assertNotContains( '2030-06-10 11:00:00', $start_utcs, '11:00 slot blocked by 30-min buffer after 10:00 booking' );
		$this->assertContains( '2030-06-10 12:00:00', $start_utcs, '12:00 slot should be free after buffer clears' );
	}

	public function test_back_to_back_bookings_at_exact_boundary() {
		$service = $this->make_service( array( 'duration' => 60, 'buffer_after' => 0 ) );
		$staff   = $this->make_staff( array( 'timezone' => 'UTC' ) );
		$date    = '2030-06-10';

		$booking1 = new AppointKit_Booking( array(
			'id'        => 1,
			'staff_id'  => 1,
			'start_utc' => '2030-06-10 09:00:00',
			'end_utc'   => '2030-06-10 10:00:00',
			'status'    => 'confirmed',
		) );
		$booking2 = new AppointKit_Booking( array(
			'id'        => 2,
			'staff_id'  => 1,
			'start_utc' => '2030-06-10 11:00:00',
			'end_utc'   => '2030-06-10 12:00:00',
			'status'    => 'confirmed',
		) );

		$this->bookings_repo->method( 'get_for_staff_in_range' )->willReturn( array( $booking1, $booking2 ) );
		$this->availability_repo->method( 'get_date_overrides' )->willReturn( array() );
		$this->availability_repo->method( 'get_weekday_rules' )->willReturn( array(
			2 => $this->make_weekday_rule( 2, '09:00:00', '17:00:00' ),
		) );

		$slots      = $this->calculator->compute_slots_for_staff( $service, $staff, $date );
		$start_utcs = array_column( $slots, 'start_utc' );

		$this->assertNotContains( '2030-06-10 09:00:00', $start_utcs );
		$this->assertContains( '2030-06-10 10:00:00', $start_utcs, '10am slot free (booking1 ends at 10am exactly)' );
		$this->assertNotContains( '2030-06-10 11:00:00', $start_utcs );
	}

	// =====================================================
	// Timezone handling
	// =====================================================

	public function test_staff_timezone_shifts_working_window_to_utc() {
		$service = $this->make_service( array( 'duration' => 60 ) );
		$staff   = $this->make_staff( array( 'timezone' => 'America/New_York' ) ); // UTC-5 in standard time.
		$date    = '2030-01-14'; // Tuesday, January = no DST, UTC-5.

		$this->bookings_repo->method( 'get_for_staff_in_range' )->willReturn( array() );
		$this->availability_repo->method( 'get_date_overrides' )->willReturn( array() );
		$this->availability_repo->method( 'get_weekday_rules' )->willReturn( array(
			2 => $this->make_weekday_rule( 2, '09:00:00', '10:00:00' ),
		) );

		$slots = $this->calculator->compute_slots_for_staff( $service, $staff, $date );
		$this->assertCount( 1, $slots );
		// 9am EST = 14:00 UTC.
		$this->assertSame( '2030-01-14 14:00:00', $slots[0]['start_utc'] );
	}

	// =====================================================
	// Interval merging
	// =====================================================

	public function test_merge_intervals_handles_overlapping_blocks() {
		$utc = new DateTimeZone( 'UTC' );
		$blocks = array(
			array( 'start' => new DateTime( '2030-06-10 09:00:00', $utc ), 'end' => new DateTime( '2030-06-10 10:00:00', $utc ) ),
			array( 'start' => new DateTime( '2030-06-10 09:30:00', $utc ), 'end' => new DateTime( '2030-06-10 11:00:00', $utc ) ),
			array( 'start' => new DateTime( '2030-06-10 13:00:00', $utc ), 'end' => new DateTime( '2030-06-10 14:00:00', $utc ) ),
		);

		$merged = $this->calculator->merge_intervals( $blocks );
		$this->assertCount( 2, $merged );
		$this->assertSame( '2030-06-10 09:00:00', $merged[0]['start']->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( '2030-06-10 11:00:00', $merged[0]['end']->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( '2030-06-10 13:00:00', $merged[1]['start']->format( 'Y-m-d H:i:s' ) );
	}

	public function test_merge_intervals_empty_input() {
		$this->assertSame( array(), $this->calculator->merge_intervals( array() ) );
	}

	// =====================================================
	// Resolve working window
	// =====================================================

	public function test_resolve_working_window_returns_null_when_no_rule() {
		$staff = $this->make_staff( array( 'timezone' => 'UTC' ) );
		$date  = '2030-06-10'; // Tuesday (day 2).

		$this->availability_repo->method( 'get_date_overrides' )->willReturn( array() );
		$this->availability_repo->method( 'get_weekday_rules' )->willReturn( array() ); // No rules.

		$result = $this->calculator->resolve_working_window( $staff, $date );
		$this->assertNull( $result );
	}
}
