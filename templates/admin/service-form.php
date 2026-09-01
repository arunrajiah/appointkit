<?php
/**
 * Add/edit service admin template.
 *
 * Variables available: $service (AppointKit_Service), $service_id (int), $errors (string[]).
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap appointkit-admin">
	<h1><?php echo esc_html( $service_id ? __( 'Edit Service', 'appointkit' ) : __( 'Add Service', 'appointkit' ) ); ?></h1>

	<?php if ( ! empty( $errors ) ) : ?>
		<div class="notice notice-error">
			<?php foreach ( $errors as $appointkit_error ) : ?>
				<p><?php echo esc_html( $appointkit_error ); ?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'appointkit_save_service', 'appointkit_service_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="appointkit-service-name"><?php esc_html_e( 'Name', 'appointkit' ); ?> <span class="description">(<?php esc_html_e( 'required', 'appointkit' ); ?>)</span></label></th>
					<td>
						<input name="name" type="text" id="appointkit-service-name" required
							value="<?php echo esc_attr( $service->name ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="appointkit-service-description"><?php esc_html_e( 'Description', 'appointkit' ); ?></label></th>
					<td>
						<textarea name="description" id="appointkit-service-description" rows="4" class="large-text"><?php echo esc_textarea( $service->description ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Shown to customers on the booking form.', 'appointkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="appointkit-service-duration"><?php esc_html_e( 'Duration', 'appointkit' ); ?></label></th>
					<td>
						<input name="duration" type="number" min="1" step="1" id="appointkit-service-duration"
							value="<?php echo esc_attr( $service->duration ); ?>" class="small-text">
						<?php esc_html_e( 'minutes', 'appointkit' ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="appointkit-service-slot-interval"><?php esc_html_e( 'Slot interval', 'appointkit' ); ?></label></th>
					<td>
						<input name="slot_interval" type="number" min="1" step="1" id="appointkit-service-slot-interval"
							value="<?php echo esc_attr( $service->slot_interval ); ?>" class="small-text">
						<?php esc_html_e( 'minutes', 'appointkit' ); ?>
						<p class="description"><?php esc_html_e( 'How often a start time is offered. Set to the duration for back-to-back slots.', 'appointkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="appointkit-service-price"><?php esc_html_e( 'Price', 'appointkit' ); ?></label></th>
					<td>
						<input name="price" type="number" min="0" step="0.01" id="appointkit-service-price"
							value="<?php echo esc_attr( $service->price ); ?>" class="small-text">
						<?php echo esc_html( get_option( 'appointkit_currency_symbol', '$' ) ); ?>
						<p class="description"><?php esc_html_e( 'Set to 0 for a free service. Paid services require Stripe to be configured.', 'appointkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="appointkit-service-buffer-before"><?php esc_html_e( 'Buffer before', 'appointkit' ); ?></label></th>
					<td>
						<input name="buffer_before" type="number" min="0" step="1" id="appointkit-service-buffer-before"
							value="<?php echo esc_attr( $service->buffer_before ); ?>" class="small-text">
						<?php esc_html_e( 'minutes', 'appointkit' ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="appointkit-service-buffer-after"><?php esc_html_e( 'Buffer after', 'appointkit' ); ?></label></th>
					<td>
						<input name="buffer_after" type="number" min="0" step="1" id="appointkit-service-buffer-after"
							value="<?php echo esc_attr( $service->buffer_after ); ?>" class="small-text">
						<?php esc_html_e( 'minutes', 'appointkit' ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="appointkit-service-color"><?php esc_html_e( 'Calendar color', 'appointkit' ); ?></label></th>
					<td>
						<input name="color" type="color" id="appointkit-service-color"
							value="<?php echo esc_attr( $service->color ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="appointkit-service-status"><?php esc_html_e( 'Status', 'appointkit' ); ?></label></th>
					<td>
						<select name="status" id="appointkit-service-status">
							<option value="active" <?php selected( $service->status, 'active' ); ?>><?php esc_html_e( 'Active', 'appointkit' ); ?></option>
							<option value="inactive" <?php selected( $service->status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'appointkit' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Inactive services are hidden from the booking form.', 'appointkit' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<?php submit_button( __( 'Save Service', 'appointkit' ), 'primary', 'submit', false ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=appointkit-services' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Cancel', 'appointkit' ); ?>
			</a>
		</p>
	</form>
</div>
