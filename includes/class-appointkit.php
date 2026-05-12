<?php
/**
 * Core plugin class.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class — wires together the loader, i18n, admin, and frontend.
 */
class AppointKit {

	/**
	 * The loader responsible for maintaining and registering all hooks.
	 *
	 * @var AppointKit_Loader
	 */
	protected $loader;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version;

	public function __construct() {
		$this->version = APPOINTKIT_VERSION;
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_frontend_hooks();
		$this->define_api_hooks();
		$this->define_cron_hooks();

		do_action( 'appointkit_loaded' );
	}

	private function load_dependencies() {
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/class-appointkit-loader.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/class-appointkit-i18n.php';

		// Helpers.
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/helpers/functions.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/helpers/class-timezone.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/helpers/class-cron.php';

		// Data models.
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/data/class-base-model.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/data/class-service.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/data/class-staff.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/data/class-booking.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/data/class-availability-rule.php';

		// Repositories.
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/repositories/class-services-repository.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/repositories/class-staff-repository.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/repositories/class-bookings-repository.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/repositories/class-availability-repository.php';

		// Booking engine.
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/booking/class-availability-calculator.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/booking/class-slot-generator.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/booking/class-conflict-checker.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/booking/class-booking-creator.php';

		// Payments.
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/payments/class-payment-result.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/payments/class-stripe-gateway.php';

		// Integrations.
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/integrations/class-google-calendar-sync.php';

		// Notifications.
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/notifications/class-email-manager.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/notifications/class-customer-booking-confirmed.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/notifications/class-customer-booking-cancelled.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/notifications/class-customer-booking-reminder.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/notifications/class-staff-booking-assigned.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/notifications/class-admin-new-booking.php';

		// Admin.
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/admin/class-admin.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/admin/class-services-page.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/admin/class-staff-page.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/admin/class-bookings-page.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/admin/class-availability-page.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/admin/class-calendar-view.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/admin/class-settings-page.php';

		// Frontend.
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/frontend/class-booking-form.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/frontend/class-booking-shortcode.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/frontend/class-booking-block.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/frontend/class-ical-feed.php';
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/frontend/class-my-bookings.php';

		// REST API.
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/api/class-rest-api.php';

		// Extensibility.
		require_once APPOINTKIT_PLUGIN_DIR . 'includes/extensibility/class-hook-registry.php';

		$this->loader = new AppointKit_Loader();
	}

	private function set_locale() {
		$plugin_i18n = new AppointKit_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	private function define_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}
		$admin = new AppointKit_Admin( $this->version );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $admin, 'add_admin_menus' );
	}

	private function define_frontend_hooks() {
		$shortcode = new AppointKit_Booking_Shortcode();
		$this->loader->add_action( 'init', $shortcode, 'register' );

		$block = new AppointKit_Booking_Block();
		$this->loader->add_action( 'init', $block, 'register' );

		$ical = new AppointKit_iCal_Feed();
		$this->loader->add_action( 'init', $ical, 'register_rewrite_rules' );
		$this->loader->add_action( 'template_redirect', $ical, 'maybe_serve' );

		$my_bookings = new AppointKit_My_Bookings();
		$this->loader->add_action( 'init', $my_bookings, 'register' );
	}

	private function define_api_hooks() {
		$api = new AppointKit_REST_API();
		$this->loader->add_action( 'rest_api_init', $api, 'register_routes' );
	}

	private function define_cron_hooks() {
		$cron = new AppointKit_Cron();
		$this->loader->add_action( 'appointkit_send_reminders', $cron, 'send_reminders' );
		$this->loader->add_action( 'appointkit_cleanup_pending', $cron, 'cleanup_pending_bookings' );
	}

	public function run() {
		$this->loader->run();
	}

	public function get_version() {
		return $this->version;
	}
}
