<?php
/**
 * Add/edit staff admin template.
 *
 * Variables available: $staff (AppointKit_Staff), $staff_id (int), $services (AppointKit_Service[]),
 * $errors (string[]), $timezones (string[]).
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag set by the OAuth redirect.
$appointkit_gcal_notice = isset( $_GET['gcal'] ) ? sanitize_text_field( wp_unslash( $_GET['gcal'] ) ) : '';
$appointkit_selected    = array_map( 'absint', (array) $staff->service_ids );
?>
<div class="wrap appointkit-admin">
	<h1><?php echo esc_html( $staff_id ? __( 'Edit Staff Member', 'appointkit' ) : __( 'Add Staff Member', 'appointkit' ) ); ?></h1>

	<?php if ( 'connected' === $appointkit_gcal_notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Google Calendar connected.', 'appointkit' ); ?></p></div>
	<?php elseif ( 'disconnected' === $appointkit_gcal_notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Google Calendar disconnected.', 'appointkit' ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! empty( $errors ) ) : ?>
		<div class="notice notice-error">
			<?php foreach ( $errors as $appointkit_error ) : ?>
				<p><?php echo esc_html( $appointkit_error ); ?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'appointkit_save_staff', 'appointkit_staff_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="appointkit-staff-name"><?php esc_html_e( 'Name', 'appointkit' ); ?> <span class="description">(<?php esc_html_e( 'required', 'appointkit' ); ?>)</span></label></th>
					<td><input name="name" type="text" id="appointkit-staff-name" required value="<?php echo esc_attr( $staff->name ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="appointkit-staff-email"><?php esc_html_e( 'Email', 'appointkit' ); ?></label></th>
					<td>
						<input name="email" type="email" id="appointkit-staff-email" value="<?php echo esc_attr( $staff->email ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'Booking notifications for this staff member are sent here.', 'appointkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="appointkit-staff-phone"><?php esc_html_e( 'Phone', 'appointkit' ); ?></label></th>
					<td><input name="phone" type="tel" id="appointkit-staff-phone" value="<?php echo esc_attr( $staff->phone ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label for="appointkit-staff-bio"><?php esc_html_e( 'Bio', 'appointkit' ); ?></label></th>
					<td>
						<textarea name="bio" id="appointkit-staff-bio" rows="4" class="large-text"><?php echo esc_textarea( $staff->bio ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Shown to customers when they pick a staff member.', 'appointkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="appointkit-staff-timezone"><?php esc_html_e( 'Timezone', 'appointkit' ); ?></label></th>
					<td>
						<select name="timezone" id="appointkit-staff-timezone">
							<?php foreach ( $timezones as $appointkit_tz ) : ?>
								<option value="<?php echo esc_attr( $appointkit_tz ); ?>" <?php selected( $staff->timezone, $appointkit_tz ); ?>>
									<?php echo esc_html( $appointkit_tz ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Services offered', 'appointkit' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Services offered', 'appointkit' ); ?></legend>
							<?php if ( empty( $services ) ) : ?>
								<p class="description"><?php esc_html_e( 'No services exist yet. Create a service first.', 'appointkit' ); ?></p>
							<?php else : ?>
								<?php foreach ( $services as $appointkit_service ) : ?>
									<label style="display:block;margin-bottom:4px;">
										<input type="checkbox" name="service_ids[]" value="<?php echo absint( $appointkit_service->id ); ?>"
											<?php checked( in_array( (int) $appointkit_service->id, $appointkit_selected, true ) ); ?>>
										<?php echo esc_html( $appointkit_service->name ); ?>
									</label>
								<?php endforeach; ?>
							<?php endif; ?>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="appointkit-staff-status"><?php esc_html_e( 'Status', 'appointkit' ); ?></label></th>
					<td>
						<select name="status" id="appointkit-staff-status">
							<option value="active" <?php selected( $staff->status, 'active' ); ?>><?php esc_html_e( 'Active', 'appointkit' ); ?></option>
							<option value="inactive" <?php selected( $staff->status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'appointkit' ); ?></option>
						</select>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<?php submit_button( __( 'Save Staff Member', 'appointkit' ), 'primary', 'submit', false ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=appointkit-staff' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Cancel', 'appointkit' ); ?>
			</a>
		</p>
	</form>

	<?php if ( $staff_id ) : ?>
		<hr>
		<h2><?php esc_html_e( 'Google Calendar', 'appointkit' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Connecting a calendar lets AppointKit read busy times so this staff member is never double-booked.', 'appointkit' ); ?>
		</p>
		<?php if ( ! empty( $staff->google_calendar_token ) ) : ?>
			<p>
				<a class="button appointkit-confirm" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=appointkit-staff&action=gcal_disconnect&id=' . absint( $staff_id ) ), 'appointkit_gcal_disconnect_' . absint( $staff_id ) ) ); ?>">
					<?php esc_html_e( 'Disconnect Google Calendar', 'appointkit' ); ?>
				</a>
			</p>
		<?php elseif ( get_option( 'appointkit_google_client_id', '' ) ) : ?>
			<p>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=appointkit-staff&action=gcal_connect&id=' . absint( $staff_id ) ), 'appointkit_gcal_connect_' . absint( $staff_id ) ) ); ?>">
					<?php esc_html_e( 'Connect Google Calendar', 'appointkit' ); ?>
				</a>
			</p>
		<?php else : ?>
			<p class="description">
				<?php
				printf(
					/* translators: %s: link to the AppointKit settings page */
					esc_html__( 'Add a Google client ID in %s first.', 'appointkit' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=appointkit-settings' ) ) . '">' . esc_html__( 'Settings', 'appointkit' ) . '</a>'
				);
				?>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $staff->ical_token ) ) : ?>
			<h2><?php esc_html_e( 'iCal feed', 'appointkit' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Subscribe to this URL in any calendar app to see this staff member’s bookings.', 'appointkit' ); ?></p>
			<p>
				<input type="text" class="large-text code" readonly
					value="<?php echo esc_url( home_url( '/appointkit/ical/' . $staff->ical_token . '.ics' ) ); ?>"
					onfocus="this.select();">
			</p>
		<?php endif; ?>
	<?php endif; ?>
</div>
