<?php
/**
 * Admin calendar view.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders a week/day calendar view of all bookings (powered by FullCalendar via REST API).
 */
class AppointKit_Calendar_View {

	/**
	 * Render the calendar view.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'appointkit' ) );
		}

		wp_enqueue_style(
			'appointkit-fullcalendar',
			APPOINTKIT_PLUGIN_URL . 'assets/css/fullcalendar.min.css',
			array(),
			'6.1.9'
		);
		wp_enqueue_script(
			'appointkit-fullcalendar',
			APPOINTKIT_PLUGIN_URL . 'assets/js/fullcalendar.min.js',
			array(),
			'6.1.9',
			true
		);
		wp_enqueue_script(
			'appointkit-calendar-view',
			APPOINTKIT_PLUGIN_URL . 'assets/js/admin.js',
			array( 'appointkit-fullcalendar' ),
			APPOINTKIT_VERSION,
			true
		);
		wp_localize_script(
			'appointkit-calendar-view',
			'appointkitCalendar',
			array(
				'apiBase'     => rest_url( 'appointkit/v1' ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'initialView' => 'timeGridWeek',
				'locale'      => get_locale(),
				'i18n'        => array(
					'rescheduleNotice' => __( 'Drag to reschedule this booking.', 'appointkit' ),
				),
			)
		);

		include APPOINTKIT_PLUGIN_DIR . 'templates/admin/calendar.php';
	}
}
