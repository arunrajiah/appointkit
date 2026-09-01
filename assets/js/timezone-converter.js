/**
 * AppointKit timezone helpers.
 *
 * Slots arrive from the REST API with both a UTC timestamp and a site-timezone
 * display string. These helpers re-render them in the visitor's own timezone so
 * someone booking from another country sees their local time.
 *
 * Exposed as window.AppointKitTZ so frontend.js can use it without a bundler.
 *
 * @package AppointKit
 */

( function ( window ) {
	'use strict';

	var AppointKitTZ = {
		/**
		 * The visitor's IANA timezone, or empty string if unavailable.
		 *
		 * @return {string} Timezone identifier.
		 */
		guess: function () {
			try {
				return Intl.DateTimeFormat().resolvedOptions().timeZone || '';
			} catch ( e ) {
				return '';
			}
		},

		/**
		 * Parse a UTC MySQL datetime ("YYYY-MM-DD HH:MM:SS") into a Date.
		 *
		 * Safari refuses to parse the space-separated form, so normalise it to ISO
		 * with an explicit Z before handing it to the Date constructor.
		 *
		 * @param {string} utc UTC datetime string.
		 * @return {Date|null} Parsed date, or null when unparseable.
		 */
		parseUTC: function ( utc ) {
			if ( ! utc ) {
				return null;
			}
			var iso = String( utc ).trim().replace( ' ', 'T' );
			if ( ! /[Zz]|[+-]\d{2}:?\d{2}$/.test( iso ) ) {
				iso += 'Z';
			}
			var date = new Date( iso );
			return isNaN( date.getTime() ) ? null : date;
		},

		/**
		 * Format a UTC datetime as a local time-of-day string (e.g. "2:30 PM").
		 *
		 * @param {string} utc     UTC datetime string.
		 * @param {string} fallback Text to return when parsing fails.
		 * @return {string} Localised time.
		 */
		localTime: function ( utc, fallback ) {
			var date = this.parseUTC( utc );
			if ( ! date ) {
				return fallback || '';
			}
			try {
				return date.toLocaleTimeString(
					undefined,
					{
						hour: 'numeric',
						minute: '2-digit'
					}
				);
			} catch ( e ) {
				return fallback || '';
			}
		},

		/**
		 * Format a UTC datetime as a full local date and time.
		 *
		 * @param {string} utc      UTC datetime string.
		 * @param {string} fallback Text to return when parsing fails.
		 * @return {string} Localised date and time.
		 */
		localDateTime: function ( utc, fallback ) {
			var date = this.parseUTC( utc );
			if ( ! date ) {
				return fallback || '';
			}
			try {
				return date.toLocaleString(
					undefined,
					{
						weekday: 'long',
						year: 'numeric',
						month: 'long',
						day: 'numeric',
						hour: 'numeric',
						minute: '2-digit'
					}
				);
			} catch ( e ) {
				return fallback || '';
			}
		},

		/**
		 * True when the visitor's timezone differs from the site's.
		 *
		 * @param {string} siteTimezone Site IANA timezone.
		 * @return {boolean} Whether the zones differ.
		 */
		differsFromSite: function ( siteTimezone ) {
			var local = this.guess();
			return ! ! ( local && siteTimezone && local !== siteTimezone );
		}
	};

	window.AppointKitTZ = AppointKitTZ;
} )( window );
