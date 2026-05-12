<?php
/**
 * Admin controller — registers menus and enqueues admin assets.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wires up the WordPress admin menu and global admin assets.
 */
class AppointKit_Admin {

	/** @var string */
	private $version;

	public function __construct( $version ) {
		$this->version = $version;
	}

	/**
	 * Register admin menus.
	 */
	public function add_admin_menus() {
		add_menu_page(
			__( 'AppointKit', 'appointkit' ),
			__( 'AppointKit', 'appointkit' ),
			'manage_options',
			'appointkit',
			array( new AppointKit_Bookings_Page(), 'render' ),
			'dashicons-calendar-alt',
			58
		);

		add_submenu_page(
			'appointkit',
			__( 'Bookings', 'appointkit' ),
			__( 'Bookings', 'appointkit' ),
			'manage_options',
			'appointkit',
			array( new AppointKit_Bookings_Page(), 'render' )
		);

		add_submenu_page(
			'appointkit',
			__( 'Calendar', 'appointkit' ),
			__( 'Calendar', 'appointkit' ),
			'manage_options',
			'appointkit-calendar',
			array( new AppointKit_Calendar_View(), 'render' )
		);

		add_submenu_page(
			'appointkit',
			__( 'Services', 'appointkit' ),
			__( 'Services', 'appointkit' ),
			'manage_options',
			'appointkit-services',
			array( new AppointKit_Services_Page(), 'render' )
		);

		add_submenu_page(
			'appointkit',
			__( 'Staff', 'appointkit' ),
			__( 'Staff', 'appointkit' ),
			'manage_options',
			'appointkit-staff',
			array( new AppointKit_Staff_Page(), 'render' )
		);

		add_submenu_page(
			'appointkit',
			__( 'Availability', 'appointkit' ),
			__( 'Availability', 'appointkit' ),
			'manage_options',
			'appointkit-availability',
			array( new AppointKit_Availability_Page(), 'render' )
		);

		add_submenu_page(
			'appointkit',
			__( 'Settings', 'appointkit' ),
			__( 'Settings', 'appointkit' ),
			'manage_options',
			'appointkit-settings',
			array( new AppointKit_Settings_Page(), 'render' )
		);
	}

	/**
	 * Enqueue admin stylesheets on AppointKit pages only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_styles( $hook ) {
		if ( ! $this->is_appointkit_page( $hook ) ) {
			return;
		}
		wp_enqueue_style(
			'appointkit-admin',
			APPOINTKIT_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			$this->version
		);
	}

	/**
	 * Enqueue admin scripts on AppointKit pages only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( ! $this->is_appointkit_page( $hook ) ) {
			return;
		}
		wp_enqueue_script(
			'appointkit-admin',
			APPOINTKIT_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);
		wp_localize_script(
			'appointkit-admin',
			'appointkitAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'appointkit_admin' ),
				'i18n'    => array(
					'confirmDelete' => __( 'Are you sure you want to delete this?', 'appointkit' ),
				),
			)
		);
	}

	/**
	 * Check if the current page is an AppointKit admin page.
	 *
	 * @param string $hook Admin page hook.
	 * @return bool
	 */
	private function is_appointkit_page( $hook ) {
		$appointkit_hooks = array(
			'toplevel_page_appointkit',
			'appointkit_page_appointkit-calendar',
			'appointkit_page_appointkit-services',
			'appointkit_page_appointkit-staff',
			'appointkit_page_appointkit-availability',
			'appointkit_page_appointkit-settings',
		);
		return in_array( $hook, $appointkit_hooks, true );
	}
}
