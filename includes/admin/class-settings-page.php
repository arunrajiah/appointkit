<?php
/**
 * Settings admin page.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the plugin settings page.
 * Displays a single dismissable admin notice on this page only (WP.org compliant).
 */
class AppointKit_Settings_Page {

	/**
	 * Render the settings page.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'appointkit' ) );
		}

		$settings = $this->get_settings();
		include APPOINTKIT_PLUGIN_DIR . 'templates/admin/settings.php';
	}

	/**
	 * Handle a settings save. Called on admin_init, before any output.
	 */
	public function handle_request() {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['appointkit_settings_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['appointkit_settings_nonce'] ) ), 'appointkit_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'appointkit' ) );
		}
		$this->handle_save();
	}

	/**
	 * Save settings from POST data.
	 */
	private function handle_save() {
		$fields = array(
			'appointkit_business_name'             => 'sanitize_text_field',
			'appointkit_sender_email'              => 'sanitize_email',
			'appointkit_default_buffer_after'      => 'absint',
			'appointkit_reminder_hours_before'     => 'absint',
			'appointkit_cancel_cutoff_hours'       => 'absint',
			'appointkit_stripe_mode'               => 'sanitize_text_field',
			'appointkit_stripe_test_pk'            => 'sanitize_text_field',
			'appointkit_stripe_live_pk'            => 'sanitize_text_field',
			'appointkit_google_client_id'          => 'sanitize_text_field',
			'appointkit_currency'                  => 'sanitize_text_field',
			'appointkit_currency_symbol'           => 'sanitize_text_field',
			'appointkit_remove_data_on_uninstall'  => 'absint',
		);

		// Nonce and capability are verified in render() before this method is called.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		foreach ( $fields as $key => $sanitizer ) {
			$value = wp_unslash( $_POST[ $key ] ?? '' );
			update_option( $key, call_user_func( $sanitizer, $value ) );
		}

		// Secret keys — only save if not empty (don't overwrite with blank).
		foreach ( array( 'appointkit_stripe_test_sk', 'appointkit_stripe_live_sk', 'appointkit_google_client_secret' ) as $secret_key ) {
			$value = sanitize_text_field( wp_unslash( $_POST[ $secret_key ] ?? '' ) );
			if ( ! empty( $value ) ) {
				update_option( $secret_key, $value );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		wp_safe_redirect( add_query_arg( array( 'page' => 'appointkit-settings', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Get all settings as an associative array.
	 *
	 * @return array
	 */
	private function get_settings() {
		return array(
			'business_name'            => get_option( 'appointkit_business_name', get_bloginfo( 'name' ) ),
			'sender_email'             => get_option( 'appointkit_sender_email', get_bloginfo( 'admin_email' ) ),
			'default_buffer_after'     => get_option( 'appointkit_default_buffer_after', 10 ),
			'reminder_hours_before'    => get_option( 'appointkit_reminder_hours_before', 24 ),
			'cancel_cutoff_hours'      => get_option( 'appointkit_cancel_cutoff_hours', 24 ),
			'stripe_mode'              => get_option( 'appointkit_stripe_mode', 'test' ),
			'stripe_test_pk'           => get_option( 'appointkit_stripe_test_pk', '' ),
			'stripe_live_pk'           => get_option( 'appointkit_stripe_live_pk', '' ),
			'google_client_id'         => get_option( 'appointkit_google_client_id', '' ),
			'currency'                 => get_option( 'appointkit_currency', 'usd' ),
			'currency_symbol'          => get_option( 'appointkit_currency_symbol', '$' ),
			'remove_data_on_uninstall' => get_option( 'appointkit_remove_data_on_uninstall', 0 ),
		);
	}
}
