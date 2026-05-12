<?php
/**
 * Defines internationalization functionality.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads the plugin text domain for translation.
 */
class AppointKit_i18n {

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'appointkit',
			false,
			dirname( APPOINTKIT_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
