<?php
/**
 * Admin calendar view template.
 *
 * Rendered by AppointKit_Calendar_View. The grid itself is built by assets/js/admin.js,
 * which fetches events from the /appointkit/v1/calendar-events REST endpoint.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap appointkit-admin">
	<h1><?php esc_html_e( 'Calendar', 'appointkit' ); ?></h1>

	<div class="appointkit-calendar" id="appointkit-calendar">
		<div class="appointkit-calendar__toolbar">
			<div class="appointkit-calendar__nav">
				<button type="button" class="button" data-cal-nav="prev" aria-label="<?php esc_attr_e( 'Previous period', 'appointkit' ); ?>">&lsaquo;</button>
				<button type="button" class="button" data-cal-nav="today"><?php esc_html_e( 'Today', 'appointkit' ); ?></button>
				<button type="button" class="button" data-cal-nav="next" aria-label="<?php esc_attr_e( 'Next period', 'appointkit' ); ?>">&rsaquo;</button>
			</div>
			<h2 class="appointkit-calendar__title" data-cal-title aria-live="polite"></h2>
			<div class="appointkit-calendar__views">
				<button type="button" class="button button-primary" data-cal-view="week"><?php esc_html_e( 'Week', 'appointkit' ); ?></button>
				<button type="button" class="button" data-cal-view="day"><?php esc_html_e( 'Day', 'appointkit' ); ?></button>
			</div>
		</div>

		<p class="appointkit-calendar__status" data-cal-status role="status" aria-live="polite">
			<?php esc_html_e( 'Loading bookings…', 'appointkit' ); ?>
		</p>

		<div class="appointkit-calendar__grid" data-cal-grid></div>
	</div>
</div>
