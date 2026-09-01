<?php
/**
 * Bookings list admin template.
 *
 * Variables available: $bookings (AppointKit_Booking[]), $total (int), $args (array), $statuses (array).
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only display flags.
$appointkit_cancelled = isset( $_GET['cancelled'] );
$appointkit_error     = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$appointkit_services   = ( new AppointKit_Services_Repository() )->get_all();
$appointkit_staff_list = ( new AppointKit_Staff_Repository() )->get_all();
$appointkit_per_page   = max( 1, (int) $args['per_page'] );
$appointkit_pages      = (int) ceil( $total / $appointkit_per_page );
$appointkit_datefmt    = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
?>
<div class="wrap appointkit-admin">
	<h1><?php esc_html_e( 'Bookings', 'appointkit' ); ?></h1>

	<?php if ( $appointkit_cancelled ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Booking cancelled.', 'appointkit' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $appointkit_error ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $appointkit_error ); ?></p></div>
	<?php endif; ?>

	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="appointkit">

		<div class="tablenav top">
			<div class="alignleft actions">
				<label for="appointkit-filter-status" class="screen-reader-text"><?php esc_html_e( 'Filter by status', 'appointkit' ); ?></label>
				<select name="status" id="appointkit-filter-status">
					<option value=""><?php esc_html_e( 'All statuses', 'appointkit' ); ?></option>
					<?php foreach ( $statuses as $appointkit_key => $appointkit_label ) : ?>
						<option value="<?php echo esc_attr( $appointkit_key ); ?>" <?php selected( $args['status'], $appointkit_key ); ?>>
							<?php echo esc_html( $appointkit_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<label for="appointkit-filter-service" class="screen-reader-text"><?php esc_html_e( 'Filter by service', 'appointkit' ); ?></label>
				<select name="service_id" id="appointkit-filter-service">
					<option value="0"><?php esc_html_e( 'All services', 'appointkit' ); ?></option>
					<?php foreach ( $appointkit_services as $appointkit_service_option ) : ?>
						<option value="<?php echo absint( $appointkit_service_option->id ); ?>" <?php selected( (int) $args['service_id'], (int) $appointkit_service_option->id ); ?>>
							<?php echo esc_html( $appointkit_service_option->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<label for="appointkit-filter-staff" class="screen-reader-text"><?php esc_html_e( 'Filter by staff', 'appointkit' ); ?></label>
				<select name="staff_id" id="appointkit-filter-staff">
					<option value="0"><?php esc_html_e( 'All staff', 'appointkit' ); ?></option>
					<?php foreach ( $appointkit_staff_list as $appointkit_staff_option ) : ?>
						<option value="<?php echo absint( $appointkit_staff_option->id ); ?>" <?php selected( (int) $args['staff_id'], (int) $appointkit_staff_option->id ); ?>>
							<?php echo esc_html( $appointkit_staff_option->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<label for="appointkit-search" class="screen-reader-text"><?php esc_html_e( 'Search bookings', 'appointkit' ); ?></label>
				<input type="search" id="appointkit-search" name="s" value="<?php echo esc_attr( $args['search'] ); ?>"
					placeholder="<?php esc_attr_e( 'Name or email', 'appointkit' ); ?>">

				<button type="submit" class="button"><?php esc_html_e( 'Filter', 'appointkit' ); ?></button>
			</div>

			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					printf(
						/* translators: %s: number of bookings */
						esc_html( _n( '%s booking', '%s bookings', (int) $total, 'appointkit' ) ),
						esc_html( number_format_i18n( $total ) )
					);
					?>
				</span>
			</div>
		</div>
	</form>

	<table class="wp-list-table widefat fixed striped table-view-list">
		<thead>
			<tr>
				<th scope="col" class="column-primary"><?php esc_html_e( 'Customer', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Service', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Staff', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'When', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Status', 'appointkit' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Payment', 'appointkit' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $bookings ) ) : ?>
			<tr class="no-items">
				<td class="colspanchange" colspan="6"><?php esc_html_e( 'No bookings found.', 'appointkit' ); ?></td>
			</tr>
		<?php else : ?>
			<?php
			$appointkit_services_repo = new AppointKit_Services_Repository();
			$appointkit_staff_repo    = new AppointKit_Staff_Repository();
			$appointkit_pay_statuses  = appointkit_get_payment_statuses();
			?>
			<?php foreach ( $bookings as $appointkit_booking ) : ?>
				<?php
				$appointkit_row_service = $appointkit_services_repo->find( $appointkit_booking->service_id );
				$appointkit_row_staff   = $appointkit_staff_repo->find( $appointkit_booking->staff_id );
				$appointkit_view_url    = admin_url( 'admin.php?page=appointkit&action=view&id=' . absint( $appointkit_booking->id ) );
				$appointkit_cancel_url  = wp_nonce_url(
					admin_url( 'admin.php?page=appointkit&action=cancel&id=' . absint( $appointkit_booking->id ) ),
					'appointkit_cancel_booking_' . absint( $appointkit_booking->id )
				);
				?>
				<tr>
					<td class="column-primary has-row-actions">
						<strong><a href="<?php echo esc_url( $appointkit_view_url ); ?>"><?php echo esc_html( $appointkit_booking->customer_name ); ?></a></strong>
						<br><span class="description"><?php echo esc_html( $appointkit_booking->customer_email ); ?></span>
						<div class="row-actions">
							<span class="view">
								<a href="<?php echo esc_url( $appointkit_view_url ); ?>"><?php esc_html_e( 'View', 'appointkit' ); ?></a>
							</span>
							<?php if ( 'cancelled' !== $appointkit_booking->status ) : ?>
								| <span class="trash">
									<a href="<?php echo esc_url( $appointkit_cancel_url ); ?>" class="appointkit-confirm submitdelete">
										<?php esc_html_e( 'Cancel', 'appointkit' ); ?>
									</a>
								</span>
							<?php endif; ?>
						</div>
					</td>
					<td><?php echo $appointkit_row_service ? esc_html( $appointkit_row_service->name ) : '<span aria-hidden="true">&#45;</span>'; ?></td>
					<td><?php echo $appointkit_row_staff ? esc_html( $appointkit_row_staff->name ) : '<span aria-hidden="true">&#45;</span>'; ?></td>
					<td><?php echo esc_html( AppointKit_Timezone::format_for_display( $appointkit_booking->start_utc, $appointkit_datefmt ) ); ?></td>
					<td>
						<span class="appointkit-status appointkit-status--<?php echo esc_attr( $appointkit_booking->status ); ?>">
							<?php echo esc_html( $statuses[ $appointkit_booking->status ] ?? $appointkit_booking->status ); ?>
						</span>
					</td>
					<td>
						<?php echo esc_html( $appointkit_pay_statuses[ $appointkit_booking->payment_status ] ?? $appointkit_booking->payment_status ); ?>
						<?php if ( (float) $appointkit_booking->price > 0 ) : ?>
							<br><span class="description"><?php echo esc_html( appointkit_format_price( $appointkit_booking->price ) ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $appointkit_pages > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'current'   => max( 1, (int) $args['paged'] ),
							'total'     => $appointkit_pages,
							'prev_text' => '&lsaquo;',
							'next_text' => '&rsaquo;',
						)
					)
				);
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
