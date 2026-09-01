<?php
/**
 * Availability grid admin template.
 *
 * Variables available: $staff (AppointKit_Staff|null), $staff_id (int), $all_staff (AppointKit_Staff[]),
 * $rules (AppointKit_Availability_Rule[]), $days (array of weekday index => label).
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag.
$appointkit_saved = isset( $_GET['saved'] );

// Index the saved weekday rules by day so the grid can pre-fill.
$appointkit_by_day = array();
foreach ( $rules as $appointkit_rule ) {
	if ( 'weekday' === $appointkit_rule->type ) {
		$appointkit_by_day[ (int) $appointkit_rule->weekday ] = $appointkit_rule;
	}
}
?>
<div class="wrap appointkit-admin">
	<h1><?php esc_html_e( 'Availability', 'appointkit' ); ?></h1>

	<?php if ( $appointkit_saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Availability saved.', 'appointkit' ); ?></p></div>
	<?php endif; ?>

	<?php if ( empty( $all_staff ) ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php
				printf(
					/* translators: %s: link to the AppointKit staff page */
					esc_html__( 'Add a staff member in %s before setting availability.', 'appointkit' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=appointkit-staff' ) ) . '">' . esc_html__( 'Staff', 'appointkit' ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php else : ?>

		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="appointkit-staff-switcher">
			<input type="hidden" name="page" value="appointkit-availability">
			<label for="appointkit-staff-select"><?php esc_html_e( 'Staff member', 'appointkit' ); ?></label>
			<select name="staff_id" id="appointkit-staff-select" onchange="this.form.submit();">
				<?php foreach ( $all_staff as $appointkit_member ) : ?>
					<option value="<?php echo absint( $appointkit_member->id ); ?>" <?php selected( (int) $staff_id, (int) $appointkit_member->id ); ?>>
						<?php echo esc_html( $appointkit_member->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<noscript><button type="submit" class="button"><?php esc_html_e( 'Switch', 'appointkit' ); ?></button></noscript>
		</form>

		<form method="post" action="">
			<?php wp_nonce_field( 'appointkit_save_availability', 'appointkit_availability_nonce' ); ?>

			<p class="description">
				<?php
				echo esc_html(
					$staff
						? sprintf(
							/* translators: %s: staff member timezone */
							__( 'Working hours are entered in this staff member’s timezone (%s).', 'appointkit' ),
							$staff->timezone
						)
						: __( 'Working hours are entered in the staff member’s timezone.', 'appointkit' )
				);
				?>
			</p>

			<table class="wp-list-table widefat fixed striped appointkit-availability-grid">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Day', 'appointkit' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Working', 'appointkit' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Start', 'appointkit' ); ?></th>
						<th scope="col"><?php esc_html_e( 'End', 'appointkit' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $days as $appointkit_day_index => $appointkit_day_label ) : ?>
					<?php
					$appointkit_day_rule = $appointkit_by_day[ $appointkit_day_index ] ?? null;
					$appointkit_active   = $appointkit_day_rule ? ( 0 === (int) $appointkit_day_rule->is_off ) : false;
					$appointkit_start    = $appointkit_day_rule ? substr( $appointkit_day_rule->start_time, 0, 5 ) : '09:00';
					$appointkit_end      = $appointkit_day_rule ? substr( $appointkit_day_rule->end_time, 0, 5 ) : '17:00';
					?>
					<tr>
						<th scope="row"><?php echo esc_html( $appointkit_day_label ); ?></th>
						<td>
							<label>
								<input type="checkbox"
									name="weekday[<?php echo absint( $appointkit_day_index ); ?>][active]"
									value="1" <?php checked( $appointkit_active ); ?>>
								<span class="screen-reader-text">
									<?php
									printf(
										/* translators: %s: weekday name */
										esc_html__( 'Working on %s', 'appointkit' ),
										esc_html( $appointkit_day_label )
									);
									?>
								</span>
							</label>
						</td>
						<td>
							<input type="time"
								name="weekday[<?php echo absint( $appointkit_day_index ); ?>][start_time]"
								value="<?php echo esc_attr( $appointkit_start ); ?>"
								aria-label="<?php echo esc_attr( sprintf( /* translators: %s: weekday name */ __( 'Start time on %s', 'appointkit' ), $appointkit_day_label ) ); ?>">
						</td>
						<td>
							<input type="time"
								name="weekday[<?php echo absint( $appointkit_day_index ); ?>][end_time]"
								value="<?php echo esc_attr( $appointkit_end ); ?>"
								aria-label="<?php echo esc_attr( sprintf( /* translators: %s: weekday name */ __( 'End time on %s', 'appointkit' ), $appointkit_day_label ) ); ?>">
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<?php submit_button( __( 'Save Availability', 'appointkit' ) ); ?>
		</form>
	<?php endif; ?>
</div>
