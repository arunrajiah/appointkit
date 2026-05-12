<?php
/**
 * Booking form renderer.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the multi-step booking form and enqueues required assets.
 */
class AppointKit_Booking_Form {

	/**
	 * Enqueue frontend assets.
	 */
	public static function enqueue_assets() {
		wp_enqueue_style(
			'appointkit-frontend',
			APPOINTKIT_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			APPOINTKIT_VERSION
		);

		wp_enqueue_script(
			'appointkit-timezone-converter',
			APPOINTKIT_PLUGIN_URL . 'assets/js/timezone-converter.js',
			array(),
			APPOINTKIT_VERSION,
			true
		);

		// Stripe.js — loaded from Stripe CDN (required by Stripe ToS).
		wp_enqueue_script(
			'stripe-js',
			'https://js.stripe.com/v3/',
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			true
		);

		wp_enqueue_script(
			'appointkit-booking-form',
			APPOINTKIT_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'stripe-js', 'appointkit-timezone-converter' ),
			APPOINTKIT_VERSION,
			true
		);

		wp_localize_script(
			'appointkit-booking-form',
			'appointkitForm',
			array(
				'apiBase'           => rest_url( 'appointkit/v1' ),
				'nonce'             => wp_create_nonce( 'wp_rest' ),
				'stripePublishableKey' => appointkit_get_stripe_pk(),
				'currency'          => get_option( 'appointkit_currency', 'usd' ),
				'i18n'              => array(
					'selectService'  => __( 'Select a service', 'appointkit' ),
					'selectStaff'    => __( 'Select a staff member', 'appointkit' ),
					'selectDate'     => __( 'Select a date', 'appointkit' ),
					'selectTime'     => __( 'Select a time', 'appointkit' ),
					'fillDetails'    => __( 'Enter your details', 'appointkit' ),
					'payAndConfirm'  => __( 'Pay & Confirm', 'appointkit' ),
					'confirm'        => __( 'Confirm Booking', 'appointkit' ),
					'loading'        => __( 'Loading…', 'appointkit' ),
					'noSlots'        => __( 'No available times for this date. Please choose another date.', 'appointkit' ),
					'errorOccurred'  => __( 'An error occurred. Please try again.', 'appointkit' ),
					'anyStaff'       => __( 'Any available', 'appointkit' ),
				),
			)
		);
	}

	/**
	 * Render the booking form HTML.
	 *
	 * @param array $atts Shortcode/block attributes.
	 * @return string
	 */
	public static function render( array $atts = array() ) {
		$defaults = array(
			'service_id' => 0,
			'staff_id'   => 0,
		);
		$atts = wp_parse_args( $atts, $defaults );

		self::enqueue_assets();

		ob_start();
		include APPOINTKIT_PLUGIN_DIR . 'templates/frontend/booking-form.php';
		return ob_get_clean();
	}
}
