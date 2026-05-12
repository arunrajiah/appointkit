<?php
/**
 * Gutenberg block: AppointKit Booking Form.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AppointKit Booking Form Gutenberg block.
 */
class AppointKit_Booking_Block {

	/**
	 * Register the block.
	 */
	public function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			'appointkit/booking-form',
			array(
				'title'           => __( 'AppointKit Booking Form', 'appointkit' ),
				'description'     => __( 'Embed an appointment booking form.', 'appointkit' ),
				'category'        => 'widgets',
				'icon'            => 'calendar-alt',
				'supports'        => array( 'align' => array( 'wide', 'full' ) ),
				'attributes'      => array(
					'serviceId' => array(
						'type'    => 'number',
						'default' => 0,
					),
					'staffId'   => array(
						'type'    => 'number',
						'default' => 0,
					),
				),
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * Server-side render callback.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render( $attributes ) {
		return AppointKit_Booking_Form::render(
			array(
				'service_id' => absint( $attributes['serviceId'] ?? 0 ),
				'staff_id'   => absint( $attributes['staffId'] ?? 0 ),
			)
		);
	}
}
