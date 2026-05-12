<?php
/**
 * Slot generator — thin wrapper around the availability calculator.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public API for generating available booking slots.
 * Caches results per request to avoid redundant DB hits on the same form load.
 */
class AppointKit_Slot_Generator {

	/** @var AppointKit_Availability_Calculator */
	private $calculator;

	/** @var array In-memory cache keyed by service_id:staff_id:date. */
	private $cache = array();

	public function __construct( AppointKit_Availability_Calculator $calculator = null ) {
		$this->calculator = $calculator ?: new AppointKit_Availability_Calculator();
	}

	/**
	 * Get available slots (with per-request caching).
	 *
	 * @param int    $service_id Service ID.
	 * @param int    $staff_id   Staff ID (0 = any).
	 * @param string $date       Y-m-d in site timezone.
	 * @return array[]
	 */
	public function get_slots( $service_id, $staff_id, $date ) {
		$cache_key = "{$service_id}:{$staff_id}:{$date}";
		if ( ! isset( $this->cache[ $cache_key ] ) ) {
			$this->cache[ $cache_key ] = $this->calculator->get_slots(
				(int) $service_id,
				(int) $staff_id,
				sanitize_text_field( $date )
			);
		}
		return $this->cache[ $cache_key ];
	}

	/**
	 * Check if a specific UTC slot is still available.
	 *
	 * @param int    $service_id Service ID.
	 * @param int    $staff_id   Staff ID (must be specific, not 0).
	 * @param string $start_utc  Proposed slot start (UTC MySQL datetime).
	 * @return bool
	 */
	public function is_slot_available( $service_id, $staff_id, $start_utc ) {
		$site_tz = AppointKit_Timezone::site_timezone();
		$dt_site = new DateTime( $start_utc, new DateTimeZone( 'UTC' ) );
		$dt_site->setTimezone( $site_tz );
		$date  = $dt_site->format( 'Y-m-d' );
		$slots = $this->get_slots( $service_id, $staff_id, $date );

		foreach ( $slots as $slot ) {
			if ( $slot['start_utc'] === $start_utc && (int) $slot['staff_id'] === (int) $staff_id ) {
				return true;
			}
		}
		return false;
	}
}
