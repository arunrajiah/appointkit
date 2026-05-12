<?php
/**
 * Timezone conversion utilities.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles UTC ↔ site timezone ↔ customer timezone conversions.
 *
 * All times are stored in UTC in the database.
 * Display times are converted to the site's configured timezone.
 * Customer-facing times use the customer's detected timezone (via JS).
 */
class AppointKit_Timezone {

	/**
	 * Get the site's configured timezone as a DateTimeZone object.
	 *
	 * @return DateTimeZone
	 */
	public static function site_timezone() {
		$tzstring = get_option( 'timezone_string' );

		if ( $tzstring ) {
			return new DateTimeZone( $tzstring );
		}

		// Offset-based timezone (e.g., UTC+5).
		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = abs( (int) round( ( $offset - $hours ) * 60 ) );
		$sign    = $offset >= 0 ? '+' : '-';
		$tz_name = sprintf( 'Etc/GMT%s%d', ( $offset > 0 ? '-' : '+' ), abs( $hours ) );

		// Etc/GMT only supports whole-hour offsets; fall back to UTC for half-hour offsets.
		if ( 0 !== $minutes ) {
			return new DateTimeZone( 'UTC' );
		}

		return new DateTimeZone( $tz_name );
	}

	/**
	 * Convert a UTC datetime string to the site's timezone.
	 *
	 * @param string $utc_datetime MySQL datetime string (UTC).
	 * @return DateTime
	 */
	public static function utc_to_site( $utc_datetime ) {
		$dt = new DateTime( $utc_datetime, new DateTimeZone( 'UTC' ) );
		$dt->setTimezone( self::site_timezone() );
		return $dt;
	}

	/**
	 * Convert a site-timezone datetime string to UTC.
	 *
	 * @param string $local_datetime Datetime string in site timezone.
	 * @return DateTime (UTC)
	 */
	public static function site_to_utc( $local_datetime ) {
		$dt = new DateTime( $local_datetime, self::site_timezone() );
		$dt->setTimezone( new DateTimeZone( 'UTC' ) );
		return $dt;
	}

	/**
	 * Format a UTC datetime string for display in the site timezone.
	 *
	 * @param string $utc_datetime MySQL datetime string.
	 * @param string $format       PHP date format string.
	 * @return string
	 */
	public static function format_for_display( $utc_datetime, $format = 'Y-m-d H:i' ) {
		return self::utc_to_site( $utc_datetime )->format( $format );
	}

	/**
	 * Convert a DateTime to a MySQL UTC string.
	 *
	 * @param DateTime $dt DateTime object (any timezone).
	 * @return string MySQL datetime in UTC.
	 */
	public static function to_utc_string( DateTime $dt ) {
		$clone = clone $dt;
		$clone->setTimezone( new DateTimeZone( 'UTC' ) );
		return $clone->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Get a DateTimeZone from a timezone string, with fallback to site timezone.
	 *
	 * @param string $tz_string IANA timezone identifier.
	 * @return DateTimeZone
	 */
	public static function get_timezone( $tz_string ) {
		if ( empty( $tz_string ) ) {
			return self::site_timezone();
		}
		try {
			return new DateTimeZone( $tz_string );
		} catch ( Exception $e ) {
			return self::site_timezone();
		}
	}

	/**
	 * Check if a given UTC datetime falls within a DST transition for a timezone.
	 *
	 * @param DateTime     $dt DateTime in UTC.
	 * @param DateTimeZone $tz Target timezone.
	 * @return bool
	 */
	public static function is_dst_transition( DateTime $dt, DateTimeZone $tz ) {
		$before = clone $dt;
		$before->modify( '-1 hour' );
		$before->setTimezone( $tz );

		$after = clone $dt;
		$after->setTimezone( $tz );

		return $before->getOffset() !== $after->getOffset();
	}
}
