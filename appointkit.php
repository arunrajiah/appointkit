<?php
/**
 * AppointKit – Appointment Booking & Scheduling
 *
 * @package           AppointKit
 * @author            Arun Rajiah
 * @copyright         2024 Arun Rajiah
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       AppointKit – Appointment Booking & Scheduling
 * Plugin URI:        https://hub.arunrajiah.com/appointkit
 * Description:       Complete appointment booking and scheduling system for WordPress. Unlimited services, staff, and bookings. Stripe payments, Google Calendar sync, email notifications.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Arun Rajiah
 * Author URI:        https://arunrajiah.com
 * Text Domain:       appointkit
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

define( 'APPOINTKIT_VERSION', '1.0.0' );
define( 'APPOINTKIT_PLUGIN_FILE', __FILE__ );
define( 'APPOINTKIT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'APPOINTKIT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'APPOINTKIT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once APPOINTKIT_PLUGIN_DIR . 'includes/class-appointkit-activator.php';
require_once APPOINTKIT_PLUGIN_DIR . 'includes/class-appointkit-deactivator.php';

register_activation_hook( __FILE__, array( 'AppointKit_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AppointKit_Deactivator', 'deactivate' ) );

require_once APPOINTKIT_PLUGIN_DIR . 'includes/class-appointkit.php';

/**
 * Begins plugin execution.
 */
function appointkit_run() {
	$plugin = new AppointKit();
	$plugin->run();
}
appointkit_run();
