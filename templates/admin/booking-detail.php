<?php
/**
 * Single booking detail admin template.
 *
 * Variables available: $booking (AppointKit_Booking), $booking_id (int),
 * $service (AppointKit_Service|null), $staff (AppointKit_Staff|null).
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

$appointkit_statuses     = appointkit_get_booking_statuses();
$appointkit_pay_statuses = appointkit_get_payment_statuses();
$appointkit_datefmt      = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
$appointkit_meta         = $booking->get_meta();
$appointkit_cancel_url   = wp_nonce_url(
	admin_url( 'admin.php?page=appointkit&action=cancel&id=' . absint( $booking->id ) ),
	'appointkit_cancel_booking_' . absint( $booking->id )
);
?>
<div class="wrap appointkit-admin">
	<h1 class="wp-heading-inline">
		<?php
		printf(
			/* translators: %d: booking ID number */
			esc_html__( 'Booking #%d', 'appointkit' ),
			absint( $booking->id )
		);
		?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=appointkit' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Back to Bookings', 'appointkit' ); ?>
	</a>
	<hr class="wp-header-end">

	<table class="form-table appointkit-detail" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Status', 'appointkit' ); ?></th>
				<td>
					<span class="appointkit-status appointkit-status--<?php echo esc_attr( $booking->status ); ?>">
						<?php echo esc_html( $appointkit_statuses[ $booking->status ] ?? $booking->status ); ?>
					</span>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Customer', 'appointkit' ); ?></th>
				<td>
					<strong><?php echo esc_html( $booking->customer_name ); ?></strong><br>
					<a href="mailto:<?php echo esc_attr( $booking->customer_email ); ?>"><?php echo esc_html( $booking->customer_email ); ?></a>
					<?php if ( $booking->customer_phone ) : ?>
						<br><?php echo esc_html( $booking->customer_phone ); ?>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Service', 'appointkit' ); ?></th>
				<td>
					<?php echo $service ? esc_html( $service->name ) : esc_html__( 'Service no longer exists', 'appointkit' ); ?>
					<?php if ( $service ) : ?>
						<br><span class="description"><?php echo esc_html( appointkit_format_duration( $service->duration ) ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Staff', 'appointkit' ); ?></th>
				<td><?php echo $staff ? esc_html( $staff->name ) : esc_html__( 'Staff member no longer exists', 'appointkit' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Starts', 'appointkit' ); ?></th>
				<td><?php echo esc_html( AppointKit_Timezone::format_for_display( $booking->start_utc, $appointkit_datefmt ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Ends', 'appointkit' ); ?></th>
				<td><?php echo esc_html( AppointKit_Timezone::format_for_display( $booking->end_utc, $appointkit_datefmt ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Payment', 'appointkit' ); ?></th>
				<td>
					<?php echo esc_html( $appointkit_pay_statuses[ $booking->payment_status ] ?? $booking->payment_status ); ?>
					<?php if ( (float) $booking->price > 0 ) : ?>
						<?php echo esc_html( ', ' . appointkit_format_price( $booking->price ) ); ?>
					<?php endif; ?>
					<?php if ( $booking->payment_intent_id ) : ?>
						<br><span class="description code"><?php echo esc_html( $booking->payment_intent_id ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
			<?php if ( $booking->notes ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Customer notes', 'appointkit' ); ?></th>
					<td><?php echo nl2br( esc_html( $booking->notes ) ); ?></td>
				</tr>
			<?php endif; ?>
			<?php if ( ! empty( $appointkit_meta ) ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Additional fields', 'appointkit' ); ?></th>
					<td>
						<?php foreach ( $appointkit_meta as $appointkit_meta_key => $appointkit_meta_value ) : ?>
							<strong><?php echo esc_html( $appointkit_meta_key ); ?>:</strong>
							<?php echo esc_html( is_scalar( $appointkit_meta_value ) ? (string) $appointkit_meta_value : wp_json_encode( $appointkit_meta_value ) ); ?><br>
						<?php endforeach; ?>
					</td>
				</tr>
			<?php endif; ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Booked on', 'appointkit' ); ?></th>
				<td><?php echo esc_html( AppointKit_Timezone::format_for_display( $booking->created_at, $appointkit_datefmt ) ); ?></td>
			</tr>
		</tbody>
	</table>

	<?php if ( 'cancelled' !== $booking->status ) : ?>
		<p class="submit">
			<a href="<?php echo esc_url( $appointkit_cancel_url ); ?>" class="button button-secondary appointkit-confirm">
				<?php esc_html_e( 'Cancel this booking', 'appointkit' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
