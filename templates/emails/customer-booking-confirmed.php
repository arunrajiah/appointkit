<?php
/**
 * Customer booking confirmed email template.
 *
 * Variables available: $booking, $service, $staff.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

$start_display = AppointKit_Timezone::format_for_display( $booking->start_utc, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
$business_name = esc_html( get_option( 'appointkit_business_name', get_bloginfo( 'name' ) ) );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
<meta charset="UTF-8">
<title><?php esc_html_e( 'Booking Confirmed', 'appointkit' ); ?></title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px;">
<table width="600" cellpadding="0" cellspacing="0" style="margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden;">
	<tr>
		<td style="background: #3788d8; padding: 24px; text-align: center;">
			<h1 style="color: #ffffff; margin: 0; font-size: 22px;"><?php echo $business_name; // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>
		</td>
	</tr>
	<tr>
		<td style="padding: 32px;">
			<h2 style="color: #333333;"><?php esc_html_e( 'Your booking is confirmed!', 'appointkit' ); ?></h2>
			<p style="color: #555555;"><?php
				printf(
					/* translators: %s: customer first name */
					esc_html__( 'Hi %s,', 'appointkit' ),
					esc_html( $booking->customer_name )
				);
			?></p>
			<p style="color: #555555;"><?php esc_html_e( 'Your appointment has been confirmed. Here are the details:', 'appointkit' ); ?></p>

			<table width="100%" cellpadding="8" cellspacing="0" style="border: 1px solid #eeeeee; border-radius: 4px; margin: 16px 0;">
				<?php if ( $service ) : ?>
				<tr>
					<td style="color: #888888; width: 140px;"><?php esc_html_e( 'Service', 'appointkit' ); ?></td>
					<td style="color: #333333; font-weight: bold;"><?php echo esc_html( $service->name ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( $staff ) : ?>
				<tr style="background: #f9f9f9;">
					<td style="color: #888888;"><?php esc_html_e( 'With', 'appointkit' ); ?></td>
					<td style="color: #333333;"><?php echo esc_html( $staff->name ); ?></td>
				</tr>
				<?php endif; ?>
				<tr>
					<td style="color: #888888;"><?php esc_html_e( 'Date & Time', 'appointkit' ); ?></td>
					<td style="color: #333333;"><?php echo esc_html( $start_display ); ?></td>
				</tr>
				<?php if ( $booking->price > 0 ) : ?>
				<tr style="background: #f9f9f9;">
					<td style="color: #888888;"><?php esc_html_e( 'Amount', 'appointkit' ); ?></td>
					<td style="color: #333333;"><?php echo esc_html( appointkit_format_price( $booking->price ) ); ?></td>
				</tr>
				<?php endif; ?>
			</table>

			<p style="color: #555555; font-size: 13px;"><?php esc_html_e( 'If you need to cancel or reschedule, please contact us as soon as possible.', 'appointkit' ); ?></p>
		</td>
	</tr>
	<tr>
		<td style="padding: 16px 32px; background: #f9f9f9; text-align: center; font-size: 12px; color: #aaaaaa;">
			<?php echo esc_html( $business_name ); ?> &mdash; <?php echo esc_url( home_url() ); ?>
		</td>
	</tr>
</table>
</body>
</html>
