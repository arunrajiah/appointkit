<?php
/**
 * Documents every action and filter AppointKit exposes for extensibility.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides a self-documenting registry of all plugin hooks.
 * Pro modules and third-party code should extend AppointKit exclusively through these.
 */
class AppointKit_Hook_Registry {

	/**
	 * Returns the full list of documented hooks.
	 *
	 * @return array[]
	 */
	public static function get_hooks() {
		return array(

			// --- Actions ---
			array(
				'type'        => 'action',
				'hook'        => 'appointkit_loaded',
				'args'        => 0,
				'description' => 'Fires after the AppointKit plugin has fully loaded.',
			),
			array(
				'type'        => 'action',
				'hook'        => 'appointkit_booking_created',
				'args'        => 1,
				'description' => 'Fires when a booking row is first inserted. Receives $booking (AppointKit_Booking).',
			),
			array(
				'type'        => 'action',
				'hook'        => 'appointkit_booking_confirmed',
				'args'        => 1,
				'description' => 'Fires when a booking status moves to "confirmed" (e.g., payment success). Receives $booking.',
			),
			array(
				'type'        => 'action',
				'hook'        => 'appointkit_booking_cancelled',
				'args'        => 1,
				'description' => 'Fires when a booking is cancelled. Receives $booking.',
			),
			array(
				'type'        => 'action',
				'hook'        => 'appointkit_slot_blocked',
				'args'        => 4,
				'description' => 'Fires when a slot is blocked for any reason. Args: $staff_id, $start_utc, $end_utc, $reason.',
			),

			// --- Filters ---
			array(
				'type'        => 'filter',
				'hook'        => 'appointkit_available_slots',
				'args'        => 4,
				'description' => 'Filter the array of available time slots. Args: $slots (array), $service_id, $staff_id, $date.',
			),
			array(
				'type'        => 'filter',
				'hook'        => 'appointkit_booking_form_fields',
				'args'        => 1,
				'description' => 'Filter the booking form field definitions. Pro adds custom fields. Receives $fields (array).',
			),
			array(
				'type'        => 'filter',
				'hook'        => 'appointkit_buffer_minutes',
				'args'        => 3,
				'description' => 'Filter buffer time after a booking. Args: $minutes (int), $staff_id, $service_id.',
			),
			array(
				'type'        => 'filter',
				'hook'        => 'appointkit_payment_gateways',
				'args'        => 1,
				'description' => 'Filter the list of active payment gateways. Pro can add more. Receives $gateways (array).',
			),
			array(
				'type'        => 'filter',
				'hook'        => 'appointkit_email_recipients',
				'args'        => 3,
				'description' => 'Filter email recipients before sending. Args: $recipients (array), $email_type (string), $booking.',
			),
			array(
				'type'        => 'filter',
				'hook'        => 'appointkit_booking_statuses',
				'args'        => 1,
				'description' => 'Filter available booking status values. Pro adds wait-list status. Receives $statuses (array).',
			),
		);
	}
}
