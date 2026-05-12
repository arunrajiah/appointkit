<?php
/**
 * Admin new booking notification email template.
 *
 * Variables available: $booking.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

$start_display = AppointKit_Timezone::format_for_display( $booking->start_utc, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
$service       = ( new AppointKit_Services_Repository() )->find( $booking->service_id );
$staff         = ( new AppointKit_Staff_Repository() )->find( $booking->staff_id );
$business_name = esc_html( get_option( 'appointkit_business_name', get_bloginfo( 'name' ) ) );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head><meta charset="UTF-8"><title><?php esc_html_e( 'New Booking', 'appointkit' ); ?></title></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px;">
<table width="600" cellpadding="0" cellspacing="0" style="margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden;">
	<tr><td style="background: #2c3e50; padding: 24px; text-align: center;">
		<h1 style="color: #ffffff; margin: 0; font-size: 20px;"><?php
			printf( esc_html__( 'New Booking #%d', 'appointkit' ), (int) $booking->id );
		?></h1>
	</td></tr>
	<tr><td style="padding: 32px;">
		<table width="100%" cellpadding="8" cellspacing="0" style="border: 1px solid #eeeeee; border-radius: 4px;">
			<tr><td style="color:#888888;width:140px;"><?php esc_html_e( 'Customer', 'appointkit' ); ?></td>
				<td><?php echo esc_html( $booking->customer_name ); ?> &lt;<?php echo esc_html( $booking->customer_email ); ?>&gt;</td></tr>
			<?php if ( $service ) : ?>
			<tr style="background:#f9f9f9;"><td style="color:#888888;"><?php esc_html_e( 'Service', 'appointkit' ); ?></td>
				<td><?php echo esc_html( $service->name ); ?></td></tr>
			<?php endif; ?>
			<?php if ( $staff ) : ?>
			<tr><td style="color:#888888;"><?php esc_html_e( 'Staff', 'appointkit' ); ?></td>
				<td><?php echo esc_html( $staff->name ); ?></td></tr>
			<?php endif; ?>
			<tr style="background:#f9f9f9;"><td style="color:#888888;"><?php esc_html_e( 'Date & Time', 'appointkit' ); ?></td>
				<td><?php echo esc_html( $start_display ); ?></td></tr>
			<?php if ( $booking->price > 0 ) : ?>
			<tr><td style="color:#888888;"><?php esc_html_e( 'Amount', 'appointkit' ); ?></td>
				<td><?php echo esc_html( appointkit_format_price( $booking->price ) ); ?> &mdash; <?php echo esc_html( $booking->payment_status ); ?></td></tr>
			<?php endif; ?>
		</table>
		<p style="margin-top:16px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=appointkit&action=view&id=' . $booking->id ) ); ?>" style="background:#3788d8;color:#fff;padding:10px 20px;border-radius:4px;text-decoration:none;"><?php esc_html_e( 'View Booking', 'appointkit' ); ?></a>
		</p>
	</td></tr>
	<tr><td style="padding: 16px 32px; background: #f9f9f9; text-align: center; font-size: 12px; color: #aaaaaa;">
		<?php echo esc_html( $business_name ); ?>
	</td></tr>
</table>
</body>
</html>
