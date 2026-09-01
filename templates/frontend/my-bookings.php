<?php
/**
 * My Bookings page template.
 *
 * Variables available: $bookings (array of AppointKit_Booking), $customer_email.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

$cutoff_hours = (int) get_option( 'appointkit_cancel_cutoff_hours', 24 );
$now          = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
$cancelled_msg = isset( $_GET['cancelled'] ) ? __( 'Your booking has been cancelled.', 'appointkit' ) : '';
$cancel_error  = isset( $_GET['cancel_error'] ) ? sanitize_text_field( wp_unslash( $_GET['cancel_error'] ) ) : '';
?>
<div class="appointkit-my-bookings">
	<?php if ( $cancelled_msg ) : ?>
		<div class="appointkit-notice appointkit-notice--success"><?php echo esc_html( $cancelled_msg ); ?></div>
	<?php endif; ?>
	<?php if ( 'cutoff' === $cancel_error ) : ?>
		<div class="appointkit-notice appointkit-notice--error"><?php
			printf(
				/* translators: %d: hours before appointment */
				esc_html( _n( 'Bookings can only be cancelled more than %d hour in advance.', 'Bookings can only be cancelled more than %d hours in advance.', $cutoff_hours, 'appointkit' ) ),
				absint( $cutoff_hours )
			);
		?></div>
	<?php elseif ( $cancel_error ) : ?>
		<div class="appointkit-notice appointkit-notice--error"><?php esc_html_e( 'There was an error cancelling your booking.', 'appointkit' ); ?></div>
	<?php endif; ?>

	<?php if ( empty( $bookings ) ) : ?>
		<p><?php esc_html_e( 'You have no bookings yet.', 'appointkit' ); ?></p>
	<?php else : ?>
	<table class="appointkit-bookings-table">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Date & Time', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Service', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Status', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Actions', 'appointkit' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $bookings as $booking ) :
			$service       = ( new AppointKit_Services_Repository() )->find( $booking->service_id );
			$start_dt      = new DateTime( $booking->start_utc, new DateTimeZone( 'UTC' ) );
			$diff_hours    = ( $start_dt->getTimestamp() - $now->getTimestamp() ) / 3600;
			$can_cancel    = 'confirmed' === $booking->status && $diff_hours >= $cutoff_hours;
			$start_display = AppointKit_Timezone::format_for_display( $booking->start_utc, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
		?>
			<tr>
				<td><?php echo esc_html( $start_display ); ?></td>
				<td><?php echo $service ? esc_html( $service->name ) : esc_html__( '—', 'appointkit' ); ?></td>
				<td><?php
					$statuses = appointkit_get_booking_statuses();
					echo esc_html( $statuses[ $booking->status ] ?? $booking->status );
				?></td>
				<td>
				<?php if ( $can_cancel ) : ?>
					<form method="post">
						<?php wp_nonce_field( 'appointkit_cancel_booking', 'appointkit_cancel_nonce' ); ?>
						<input type="hidden" name="appointkit_cancel_booking_id" value="<?php echo absint( $booking->id ); ?>">
						<input type="hidden" name="appointkit_cancel_email" value="<?php echo esc_attr( $customer_email ); ?>">
						<button type="submit" class="appointkit-btn appointkit-btn--danger" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to cancel this booking?', 'appointkit' ); ?>')">
							<?php esc_html_e( 'Cancel', 'appointkit' ); ?>
						</button>
					</form>
				<?php else : ?>
					<?php esc_html_e( '—', 'appointkit' ); ?>
				<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
