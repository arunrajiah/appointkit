<?php
/**
 * Tests for AppointKit_Timezone utility.
 *
 * @package AppointKit
 */

class Test_Timezone extends WP_UnitTestCase {

	public function test_utc_to_site_converts_correctly() {
		update_option( 'timezone_string', 'America/New_York' );
		$dt = AppointKit_Timezone::utc_to_site( '2030-01-14 14:00:00' );
		$this->assertSame( '2030-01-14 09:00', $dt->format( 'Y-m-d H:i' ), '14:00 UTC = 09:00 EST' );
	}

	public function test_site_to_utc_converts_correctly() {
		update_option( 'timezone_string', 'America/New_York' );
		$dt = AppointKit_Timezone::site_to_utc( '2030-01-14 09:00:00' );
		$this->assertSame( '2030-01-14 14:00:00', $dt->format( 'Y-m-d H:i:s' ), '09:00 EST = 14:00 UTC' );
	}

	public function test_to_utc_string_normalizes_any_timezone() {
		$dt = new DateTime( '2030-06-10 09:00:00', new DateTimeZone( 'America/Los_Angeles' ) );
		$this->assertSame( '2030-06-10 16:00:00', AppointKit_Timezone::to_utc_string( $dt ) );
	}

	public function test_site_timezone_falls_back_to_utc_for_fractional_offset() {
		delete_option( 'timezone_string' );
		update_option( 'gmt_offset', 5.5 ); // India.
		$tz = AppointKit_Timezone::site_timezone();
		$this->assertInstanceOf( DateTimeZone::class, $tz );
	}
}
