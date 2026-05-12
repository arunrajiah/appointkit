<?php
/**
 * Fired during plugin deactivation.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cleans up scheduled cron events on deactivation.
 */
class AppointKit_Deactivator {

	/**
	 * Deactivate the plugin.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'appointkit_send_reminders' );
		wp_clear_scheduled_hook( 'appointkit_cleanup_pending' );
		flush_rewrite_rules();
	}
}
