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

		// The calendar grid is drawn by assets/js/admin.js, which AppointKit_Admin has
		// already enqueued (as 'appointkit-admin') for every AppointKit screen. Localizing
		// onto that same handle avoids loading the file twice under two handles.
		wp_localize_script(
			'appointkit-admin',
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
