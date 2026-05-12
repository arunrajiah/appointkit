<?php
/**
 * Availability calculator — core scheduling logic.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Computes available time slots for a (service, staff, date) tuple.
 *
 * Algorithm:
 *  1. Resolve working hours for the requested date (weekday rule + date overrides).
 *  2. Subtract existing bookings (including buffers).
 *  3. Subtract Google Calendar busy events (if the staff has a connected calendar).
 *  4. Generate slot start times at the service's slot_interval within the free windows.
 *  5. Return slots as UTC datetimes with display strings in the site timezone.
 *
 * Complexity: O(working_minutes / slot_interval) per call.
 * This runs on every booking form page load — keep it cheap.
 */
class AppointKit_Availability_Calculator {

	/** @var AppointKit_Services_Repository */
	private $services_repo;

	/** @var AppointKit_Staff_Repository */
	private $staff_repo;

	/** @var AppointKit_Bookings_Repository */
	private $bookings_repo;

	/** @var AppointKit_Availability_Repository */
	private $availability_repo;

	/** @var AppointKit_Google_Calendar_Sync */
	private $gcal;

	public function __construct(
		AppointKit_Services_Repository $services_repo = null,
		AppointKit_Staff_Repository $staff_repo = null,
		AppointKit_Bookings_Repository $bookings_repo = null,
		AppointKit_Availability_Repository $availability_repo = null,
		AppointKit_Google_Calendar_Sync $gcal = null
	) {
		$this->services_repo     = $services_repo ?: new AppointKit_Services_Repository();
		$this->staff_repo        = $staff_repo ?: new AppointKit_Staff_Repository();
		$this->bookings_repo     = $bookings_repo ?: new AppointKit_Bookings_Repository();
		$this->availability_repo = $availability_repo ?: new AppointKit_Availability_Repository();
		$this->gcal              = $gcal ?: new AppointKit_Google_Calendar_Sync();
	}

	/**
	 * Get available slots for a service / staff / date combination.
	 *
	 * @param int    $service_id  Service ID.
	 * @param int    $staff_id    Staff ID, or 0 for "any available staff."
	 * @param string $date        Y-m-d date string in the SITE timezone.
	 * @return array[] Each item: { start_utc, end_utc, start_display, end_display, staff_id }
	 */
	public function get_slots( $service_id, $staff_id, $date ) {
		$service = $this->services_repo->find( $service_id );
		if ( ! $service || 'active' !== $service->status ) {
			return array();
		}

		// "Any staff" mode — union of available slots across all qualified staff.
		if ( 0 === (int) $staff_id ) {
			return $this->get_slots_any_staff( $service, $date );
		}

		$staff = $this->staff_repo->find( $staff_id );
		if ( ! $staff || 'active' !== $staff->status ) {
			return array();
		}
		if ( ! in_array( $service_id, $staff->service_ids, true ) ) {
			return array();
		}

		$raw_slots = $this->compute_slots_for_staff( $service, $staff, $date );
		return apply_filters( 'appointkit_available_slots', $raw_slots, $service_id, $staff_id, $date );
	}

	/**
	 * Compute slots for a specific staff member.
	 *
	 * @param AppointKit_Service $service Service model.
	 * @param AppointKit_Staff   $staff   Staff model.
	 * @param string             $date    Y-m-d in site timezone.
	 * @return array[]
	 */
	public function compute_slots_for_staff( AppointKit_Service $service, AppointKit_Staff $staff, $date ) {
		// 1. Resolve working window in staff's local timezone.
		$working_window = $this->resolve_working_window( $staff, $date );
		if ( null === $working_window ) {
			return array(); // Day off.
		}

		list( $work_start_utc, $work_end_utc ) = $working_window;

		// 2. Collect booked blocks (existing bookings + buffers).
		$booked_blocks = $this->get_booked_blocks( $staff->id, $work_start_utc, $work_end_utc, $service );

		// 3. Collect Google Calendar busy blocks.
		$busy_blocks = $this->gcal->get_busy_blocks( $staff, $work_start_utc, $work_end_utc );

		// 4. Merge all blocked intervals.
		$blocked = array_merge( $booked_blocks, $busy_blocks );
		$blocked = $this->merge_intervals( $blocked );

		// 5. Generate slot candidates.
		$duration_min     = (int) $service->duration;
		$slot_interval    = (int) $service->slot_interval > 0 ? (int) $service->slot_interval : $duration_min;
		$buffer_after_min = (int) apply_filters( 'appointkit_buffer_minutes', $service->buffer_after, $staff->id, $service->id );

		$slots = array();
		$cursor = clone $work_start_utc;
		$now    = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

		while ( true ) {
			$slot_end = clone $cursor;
			$slot_end->modify( '+' . $duration_min . ' minutes' );

			// Slot must end within the working window.
			if ( $slot_end > $work_end_utc ) {
				break;
			}

			// Slot must be in the future.
			if ( $cursor <= $now ) {
				$cursor->modify( '+' . $slot_interval . ' minutes' );
				continue;
			}

			// Check if slot (including buffer after) overlaps any blocked interval.
			$slot_end_with_buffer = clone $slot_end;
			$slot_end_with_buffer->modify( '+' . $buffer_after_min . ' minutes' );

			if ( ! $this->overlaps_any( $cursor, $slot_end_with_buffer, $blocked ) ) {
				$slots[] = $this->build_slot( $cursor, $slot_end, $staff->id );
			}

			$cursor->modify( '+' . $slot_interval . ' minutes' );
		}

		return $slots;
	}

	/**
	 * Get slots for "any staff" — union of all qualified staff availability.
	 *
	 * @param AppointKit_Service $service Service model.
	 * @param string             $date    Y-m-d in site timezone.
	 * @return array[]
	 */
	private function get_slots_any_staff( AppointKit_Service $service, $date ) {
		$staff_list = $this->staff_repo->get_by_service( $service->id );
		$all_slots  = array();
		$seen_times = array();

		foreach ( $staff_list as $staff ) {
			$staff_slots = $this->compute_slots_for_staff( $service, $staff, $date );
			foreach ( $staff_slots as $slot ) {
				// Deduplicate by start time; keep the first staff who has that slot.
				if ( ! isset( $seen_times[ $slot['start_utc'] ] ) ) {
					$all_slots[]                      = $slot;
					$seen_times[ $slot['start_utc'] ] = true;
				}
			}
		}

		usort( $all_slots, static function ( $a, $b ) {
			return strcmp( $a['start_utc'], $b['start_utc'] );
		} );

		return apply_filters( 'appointkit_available_slots', $all_slots, $service->id, 0, $date );
	}

	/**
	 * Determine the working window (UTC DateTimes) for a staff member on a given date.
	 *
	 * @param AppointKit_Staff $staff Staff model.
	 * @param string           $date  Y-m-d in site timezone.
	 * @return array{DateTime, DateTime}|null Working [start_utc, end_utc] or null if day off.
	 */
	public function resolve_working_window( AppointKit_Staff $staff, $date ) {
		$staff_tz = AppointKit_Timezone::get_timezone( $staff->timezone );

		// Check for a date-specific override first.
		$overrides = $this->availability_repo->get_date_overrides( $staff->id, $date, $date );
		if ( isset( $overrides[ $date ] ) ) {
			$rule = $overrides[ $date ];
			if ( $rule->is_off ) {
				return null;
			}
			return $this->window_from_rule( $rule, $date, $staff_tz );
		}

		// Fall back to weekday rule.
		$weekday_number = (int) ( new DateTime( $date, $staff_tz ) )->format( 'w' ); // 0=Sun, 6=Sat.
		$weekday_rules  = $this->availability_repo->get_weekday_rules( $staff->id );

		if ( ! isset( $weekday_rules[ $weekday_number ] ) ) {
			return null; // No rule for this weekday = day off.
		}

		$rule = $weekday_rules[ $weekday_number ];
		if ( $rule->is_off ) {
			return null;
		}

		return $this->window_from_rule( $rule, $date, $staff_tz );
	}

	/**
	 * Build UTC DateTimes from a rule's times and a date.
	 *
	 * @param AppointKit_Availability_Rule $rule     Availability rule.
	 * @param string                       $date     Y-m-d.
	 * @param DateTimeZone                 $staff_tz Staff's timezone.
	 * @return array{DateTime, DateTime}
	 */
	private function window_from_rule( AppointKit_Availability_Rule $rule, $date, DateTimeZone $staff_tz ) {
		$start = new DateTime( $date . ' ' . $rule->start_time, $staff_tz );
		$end   = new DateTime( $date . ' ' . $rule->end_time, $staff_tz );

		// Handle midnight crossings (end_time is the next day).
		if ( $end <= $start ) {
			$end->modify( '+1 day' );
		}

		$utc = new DateTimeZone( 'UTC' );
		$start->setTimezone( $utc );
		$end->setTimezone( $utc );
		return array( $start, $end );
	}

	/**
	 * Collect booked intervals (with buffers) for a staff member in a UTC window.
	 *
	 * @param int                $staff_id       Staff ID.
	 * @param DateTime           $window_start   UTC window start.
	 * @param DateTime           $window_end     UTC window end.
	 * @param AppointKit_Service $service        Service (for buffer filter).
	 * @return array[] Each: { start: DateTime, end: DateTime }
	 */
	private function get_booked_blocks( $staff_id, DateTime $window_start, DateTime $window_end, AppointKit_Service $service ) {
		$bookings = $this->bookings_repo->get_for_staff_in_range(
			$staff_id,
			$window_start->format( 'Y-m-d H:i:s' ),
			$window_end->format( 'Y-m-d H:i:s' )
		);

		$utc    = new DateTimeZone( 'UTC' );
		$blocks = array();
		foreach ( $bookings as $booking ) {
			$bstart = new DateTime( $booking->start_utc, $utc );
			$bend   = new DateTime( $booking->end_utc, $utc );

			// Add buffer before (subtract from bstart).
			$buffer_before = (int) apply_filters( 'appointkit_buffer_before_minutes', $service->buffer_before, $staff_id, $service->id );
			if ( $buffer_before > 0 ) {
				$bstart->modify( '-' . $buffer_before . ' minutes' );
			}

			// Add buffer after (extend bend).
			$buffer_after = (int) apply_filters( 'appointkit_buffer_minutes', $service->buffer_after, $staff_id, $service->id );
			if ( $buffer_after > 0 ) {
				$bend->modify( '+' . $buffer_after . ' minutes' );
			}

			$blocks[] = array( 'start' => $bstart, 'end' => $bend );

			do_action( 'appointkit_slot_blocked', $staff_id, $booking->start_utc, $booking->end_utc, 'booking' );
		}
		return $blocks;
	}

	/**
	 * Merge overlapping/adjacent intervals.
	 *
	 * @param array[] $intervals Each: { start: DateTime, end: DateTime }
	 * @return array[]
	 */
	public function merge_intervals( array $intervals ) {
		if ( empty( $intervals ) ) {
			return array();
		}

		usort( $intervals, static function ( $a, $b ) {
			return $a['start'] <=> $b['start'];
		} );

		$merged = array( $intervals[0] );
		for ( $i = 1, $count = count( $intervals ); $i < $count; $i++ ) {
			$last = &$merged[ count( $merged ) - 1 ];
			if ( $intervals[ $i ]['start'] <= $last['end'] ) {
				if ( $intervals[ $i ]['end'] > $last['end'] ) {
					$last['end'] = $intervals[ $i ]['end'];
				}
			} else {
				$merged[] = $intervals[ $i ];
			}
		}
		return $merged;
	}

	/**
	 * Check if [start, end) overlaps any blocked interval.
	 *
	 * @param DateTime $start   Slot start.
	 * @param DateTime $end     Slot end (with buffer).
	 * @param array[]  $blocked Merged blocked intervals.
	 * @return bool
	 */
	private function overlaps_any( DateTime $start, DateTime $end, array $blocked ) {
		foreach ( $blocked as $block ) {
			// Overlap when start < block_end AND end > block_start.
			if ( $start < $block['end'] && $end > $block['start'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Build a slot array from UTC DateTimes.
	 *
	 * @param DateTime $start_utc Slot start in UTC.
	 * @param DateTime $end_utc   Slot end in UTC.
	 * @param int      $staff_id  Staff ID.
	 * @return array
	 */
	private function build_slot( DateTime $start_utc, DateTime $end_utc, $staff_id ) {
		$site_start = clone $start_utc;
		$site_start->setTimezone( AppointKit_Timezone::site_timezone() );
		$site_end = clone $end_utc;
		$site_end->setTimezone( AppointKit_Timezone::site_timezone() );

		return array(
			'start_utc'     => $start_utc->format( 'Y-m-d H:i:s' ),
			'end_utc'       => $end_utc->format( 'Y-m-d H:i:s' ),
			'start_display' => $site_start->format( 'H:i' ),
			'end_display'   => $site_end->format( 'H:i' ),
			'start_iso'     => $start_utc->format( DateTime::ATOM ),
			'staff_id'      => (int) $staff_id,
		);
	}
}
