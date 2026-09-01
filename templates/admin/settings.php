<?php
/**
 * Settings admin page template.
 *
 * Variables available: $settings (array).
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

$appointkit_saved = isset( $_GET['saved'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag.
?>
<div class="wrap appointkit-admin">
	<h1><?php esc_html_e( 'AppointKit Settings', 'appointkit' ); ?></h1>

	<?php if ( $appointkit_saved ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved.', 'appointkit' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'appointkit_save_settings', 'appointkit_settings_nonce' ); ?>

		<h2 class="title"><?php esc_html_e( 'General', 'appointkit' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="appointkit_business_name"><?php esc_html_e( 'Business name', 'appointkit' ); ?></label>
					</th>
					<td>
						<input name="appointkit_business_name" type="text" id="appointkit_business_name"
							value="<?php echo esc_attr( $settings['business_name'] ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'Shown in booking confirmation emails.', 'appointkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="appointkit_sender_email"><?php esc_html_e( 'Sender email', 'appointkit' ); ?></label>
					</th>
					<td>
						<input name="appointkit_sender_email" type="email" id="appointkit_sender_email"
							value="<?php echo esc_attr( $settings['sender_email'] ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'Address that notification emails are sent from.', 'appointkit' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<h2 class="title"><?php esc_html_e( 'Scheduling', 'appointkit' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="appointkit_default_buffer_after"><?php esc_html_e( 'Default buffer after booking', 'appointkit' ); ?></label>
					</th>
					<td>
						<input name="appointkit_default_buffer_after" type="number" min="0" step="1"
							id="appointkit_default_buffer_after"
							value="<?php echo esc_attr( $settings['default_buffer_after'] ); ?>" class="small-text">
						<?php esc_html_e( 'minutes', 'appointkit' ); ?>
						<p class="description"><?php esc_html_e( 'Gap added after each appointment when a service does not set its own.', 'appointkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="appointkit_reminder_hours_before"><?php esc_html_e( 'Send reminder', 'appointkit' ); ?></label>
					</th>
					<td>
						<input name="appointkit_reminder_hours_before" type="number" min="0" step="1"
							id="appointkit_reminder_hours_before"
							value="<?php echo esc_attr( $settings['reminder_hours_before'] ); ?>" class="small-text">
						<?php esc_html_e( 'hours before the appointment', 'appointkit' ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="appointkit_cancel_cutoff_hours"><?php esc_html_e( 'Cancellation cutoff', 'appointkit' ); ?></label>
					</th>
					<td>
						<input name="appointkit_cancel_cutoff_hours" type="number" min="0" step="1"
							id="appointkit_cancel_cutoff_hours"
							value="<?php echo esc_attr( $settings['cancel_cutoff_hours'] ); ?>" class="small-text">
						<?php esc_html_e( 'hours before the appointment', 'appointkit' ); ?>
						<p class="description"><?php esc_html_e( 'Customers cannot cancel once this window has passed.', 'appointkit' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<h2 class="title"><?php esc_html_e( 'Currency', 'appointkit' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="appointkit_currency"><?php esc_html_e( 'Currency code', 'appointkit' ); ?></label>
					</th>
					<td>
						<input name="appointkit_currency" type="text" id="appointkit_currency" maxlength="3"
							value="<?php echo esc_attr( $settings['currency'] ); ?>" class="small-text">
						<p class="description"><?php esc_html_e( 'Three-letter ISO code, for example usd or eur.', 'appointkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="appointkit_currency_symbol"><?php esc_html_e( 'Currency symbol', 'appointkit' ); ?></label>
					</th>
					<td>
						<input name="appointkit_currency_symbol" type="text" id="appointkit_currency_symbol" maxlength="8"
							value="<?php echo esc_attr( $settings['currency_symbol'] ); ?>" class="small-text">
					</td>
				</tr>
			</tbody>
		</table>

		<h2 class="title"><?php esc_html_e( 'Stripe payments', 'appointkit' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Mode', 'appointkit' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Stripe mode', 'appointkit' ); ?></legend>
							<label>
								<input type="radio" name="appointkit_stripe_mode" value="test"
									<?php checked( $settings['stripe_mode'], 'test' ); ?>>
								<?php esc_html_e( 'Test', 'appointkit' ); ?>
							</label><br>
							<label>
								<input type="radio" name="appointkit_stripe_mode" value="live"
									<?php checked( $settings['stripe_mode'], 'live' ); ?>>
								<?php esc_html_e( 'Live', 'appointkit' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="appointkit_stripe_test_pk"><?php esc_html_e( 'Test publishable key', 'appointkit' ); ?></label>
					</th>
					<td>
						<input name="appointkit_stripe_test_pk" type="text" id="appointkit_stripe_test_pk"
							value="<?php echo esc_attr( $settings['stripe_test_pk'] ); ?>" class="regular-text code">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="appointkit_stripe_test_sk"><?php esc_html_e( 'Test secret key', 'appointkit' ); ?></label>
					</th>
					<td>
						<input name="appointkit_stripe_test_sk" type="password" id="appointkit_stripe_test_sk"
							value="" autocomplete="new-password" class="regular-text code">
						<p class="description">
							<?php
							echo esc_html(
								get_option( 'appointkit_stripe_test_sk', '' )
									? __( 'A key is saved. Leave blank to keep it, or enter a new key to replace it.', 'appointkit' )
									: __( 'No key saved yet.', 'appointkit' )
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="appointkit_stripe_live_pk"><?php esc_html_e( 'Live publishable key', 'appointkit' ); ?></label>
					</th>
					<td>
						<input name="appointkit_stripe_live_pk" type="text" id="appointkit_stripe_live_pk"
							value="<?php echo esc_attr( $settings['stripe_live_pk'] ); ?>" class="regular-text code">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="appointkit_stripe_live_sk"><?php esc_html_e( 'Live secret key', 'appointkit' ); ?></label>
					</th>
					<td>
						<input name="appointkit_stripe_live_sk" type="password" id="appointkit_stripe_live_sk"
							value="" autocomplete="new-password" class="regular-text code">
						<p class="description">
							<?php
							echo esc_html(
								get_option( 'appointkit_stripe_live_sk', '' )
									? __( 'A key is saved. Leave blank to keep it, or enter a new key to replace it.', 'appointkit' )
									: __( 'No key saved yet.', 'appointkit' )
							);
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<h2 class="title"><?php esc_html_e( 'Google Calendar', 'appointkit' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="appointkit_google_client_id"><?php esc_html_e( 'Client ID', 'appointkit' ); ?></label>
					</th>
					<td>
						<input name="appointkit_google_client_id" type="text" id="appointkit_google_client_id"
							value="<?php echo esc_attr( $settings['google_client_id'] ); ?>" class="regular-text code">
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="appointkit_google_client_secret"><?php esc_html_e( 'Client secret', 'appointkit' ); ?></label>
					</th>
					<td>
						<input name="appointkit_google_client_secret" type="password" id="appointkit_google_client_secret"
							value="" autocomplete="new-password" class="regular-text code">
						<p class="description">
							<?php
							echo esc_html(
								get_option( 'appointkit_google_client_secret', '' )
									? __( 'A secret is saved. Leave blank to keep it.', 'appointkit' )
									: __( 'No secret saved yet.', 'appointkit' )
							);
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<h2 class="title"><?php esc_html_e( 'Uninstall', 'appointkit' ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Data removal', 'appointkit' ); ?></th>
					<td>
						<label>
							<input name="appointkit_remove_data_on_uninstall" type="checkbox" value="1"
								<?php checked( (int) $settings['remove_data_on_uninstall'], 1 ); ?>>
							<?php esc_html_e( 'Delete all AppointKit data when the plugin is deleted', 'appointkit' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'This permanently removes every service, staff member, and booking. It cannot be undone.', 'appointkit' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( __( 'Save Settings', 'appointkit' ) ); ?>
	</form>
</div>
