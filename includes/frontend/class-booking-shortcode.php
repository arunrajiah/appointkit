<?php
/**
 * Booking form shortcode: [appointkit_form].
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the [appointkit_form] shortcode.
 */
class AppointKit_Booking_Shortcode {

	/**
	 * Register the shortcode.
	 */
	public function register() {
		add_shortcode( 'appointkit_form', array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'service_id' => 0,
				'staff_id'   => 0,
			),
			$atts,
			'appointkit_form'
		);

		$atts['service_id'] = absint( $atts['service_id'] );
		$atts['staff_id']   = absint( $atts['staff_id'] );

		return AppointKit_Booking_Form::render( $atts );
	}
}
