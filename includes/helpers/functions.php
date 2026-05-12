<?php
/**
 * Global helper functions.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get the available booking statuses.
 *
 * @return array
 */
function appointkit_get_booking_statuses() {
	$statuses = array(
		'pending'   => __( 'Pending', 'appointkit' ),
		'confirmed' => __( 'Confirmed', 'appointkit' ),
		'cancelled' => __( 'Cancelled', 'appointkit' ),
		'completed' => __( 'Completed', 'appointkit' ),
		'no_show'   => __( 'No Show', 'appointkit' ),
	);

	return apply_filters( 'appointkit_booking_statuses', $statuses );
}

/**
 * Get the available payment statuses.
 *
 * @return array
 */
function appointkit_get_payment_statuses() {
	return array(
		'unpaid'   => __( 'Unpaid', 'appointkit' ),
		'paid'     => __( 'Paid', 'appointkit' ),
		'refunded' => __( 'Refunded', 'appointkit' ),
		'failed'   => __( 'Failed', 'appointkit' ),
	);
}

/**
 * Format a price for display.
 *
 * @param float $price Amount in the store currency.
 * @return string
 */
function appointkit_format_price( $price ) {
	$currency_symbol = get_option( 'appointkit_currency_symbol', '$' );
	return $currency_symbol . number_format( (float) $price, 2 );
}

/**
 * Convert minutes to a human-readable duration string.
 *
 * @param int $minutes Duration in minutes.
 * @return string
 */
function appointkit_format_duration( $minutes ) {
	$minutes = (int) $minutes;
	if ( $minutes < 60 ) {
		/* translators: %d: number of minutes */
		return sprintf( _n( '%d minute', '%d minutes', $minutes, 'appointkit' ), $minutes );
	}
	$hours      = floor( $minutes / 60 );
	$remainder  = $minutes % 60;
	$hour_str   = sprintf( _n( '%d hour', '%d hours', $hours, 'appointkit' ), $hours );
	if ( 0 === $remainder ) {
		return $hour_str;
	}
	/* translators: 1: hours, 2: minutes */
	return sprintf( __( '%1$s %2$s', 'appointkit' ), $hour_str, sprintf( _n( '%d minute', '%d minutes', $remainder, 'appointkit' ), $remainder ) );
}

/**
 * Get the Stripe publishable key for the current mode.
 *
 * @return string
 */
function appointkit_get_stripe_pk() {
	$mode = get_option( 'appointkit_stripe_mode', 'test' );
	if ( 'live' === $mode ) {
		return get_option( 'appointkit_stripe_live_pk', '' );
	}
	return get_option( 'appointkit_stripe_test_pk', '' );
}

/**
 * Get the Stripe secret key for the current mode.
 *
 * @return string
 */
function appointkit_get_stripe_sk() {
	$mode = get_option( 'appointkit_stripe_mode', 'test' );
	if ( 'live' === $mode ) {
		return get_option( 'appointkit_stripe_live_sk', '' );
	}
	return get_option( 'appointkit_stripe_test_sk', '' );
}

/**
 * Check if Stripe is configured.
 *
 * @return bool
 */
function appointkit_stripe_is_configured() {
	return ! empty( appointkit_get_stripe_pk() ) && ! empty( appointkit_get_stripe_sk() );
}

/**
 * Generate a unique iCal token for a staff member.
 *
 * @return string 32-character hex token.
 */
function appointkit_generate_ical_token() {
	return bin2hex( random_bytes( 16 ) );
}

/**
 * Sanitize a time string to H:i:s format.
 *
 * @param string $time Raw time string.
 * @return string
 */
function appointkit_sanitize_time( $time ) {
	$parts = explode( ':', sanitize_text_field( $time ) );
	$h     = isset( $parts[0] ) ? absint( $parts[0] ) : 0;
	$m     = isset( $parts[1] ) ? absint( $parts[1] ) : 0;
	$s     = isset( $parts[2] ) ? absint( $parts[2] ) : 0;
	return sprintf( '%02d:%02d:%02d', min( $h, 23 ), min( $m, 59 ), min( $s, 59 ) );
}
