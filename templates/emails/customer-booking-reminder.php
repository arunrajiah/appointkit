<?php
/**
 * Customer booking reminder email template.
 *
 * Variables available: $booking.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

$start_display = AppointKit_Timezone::format_for_display( $booking->start_utc, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
$business_name = esc_html( get_option( 'appointkit_business_name', get_bloginfo( 'name' ) ) );
$hours         = (int) get_option( 'appointkit_reminder_hours_before', 24 );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head><meta charset="UTF-8"><title><?php esc_html_e( 'Appointment Reminder', 'appointkit' ); ?></title></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px;">
<table width="600" cellpadding="0" cellspacing="0" style="margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden;">
	<tr><td style="background: #27ae60; padding: 24px; text-align: center;">
		<h1 style="color: #ffffff; margin: 0; font-size: 22px;"><?php echo $business_name; // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>
	</td></tr>
	<tr><td style="padding: 32px;">
		<h2 style="color: #333333;"><?php
			printf(
				/* translators: %d: hours before */
				esc_html( _n( 'Reminder: Your appointment is in %d hour', 'Reminder: Your appointment is in %d hours', $hours, 'appointkit' ) ),
				$hours
			);
		?></h2>
		<p style="color: #555555;"><?php printf( esc_html__( 'Hi %s,', 'appointkit' ), esc_html( $booking->customer_name ) ); ?></p>
		<p style="color: #555555;"><?php
			printf(
				/* translators: %s: formatted date/time */
				esc_html__( 'This is a reminder that you have an appointment on %s.', 'appointkit' ),
				esc_html( $start_display )
			);
		?></p>
	</td></tr>
	<tr><td style="padding: 16px 32px; background: #f9f9f9; text-align: center; font-size: 12px; color: #aaaaaa;">
		<?php echo esc_html( $business_name ); ?> &mdash; <?php echo esc_url( home_url() ); ?>
	</td></tr>
</table>
</body>
</html>
