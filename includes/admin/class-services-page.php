<?php
/**
 * Services admin page.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles the Services CRUD admin page.
 */
class AppointKit_Services_Page {

	/** @var AppointKit_Services_Repository */
	private $repo;

	/** @var string[] Validation errors carried from handle_request() to render(). */
	private static $errors = array();

	/** @var AppointKit_Service|null Rejected submission, redisplayed so input is not lost. */
	private static $posted = null;

	public function __construct() {
		$this->repo = new AppointKit_Services_Repository();
	}

	/**
	 * Render the page.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'appointkit' ) );
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';

		switch ( $action ) {
			case 'new':
			case 'edit':
				$this->render_form();
				break;
			default:
				$this->render_list();
		}
	}

	/**
	 * Handle saves and deletes. Called on admin_init, before any output.
	 */
	public function handle_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Selects the handler; each verifies its own nonce.
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

		if ( 'delete' === $action ) {
			$this->handle_delete();
			return;
		}

		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['appointkit_service_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['appointkit_service_nonce'] ) ), 'appointkit_save_service' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'appointkit' ) );
		}

		$this->save_from_post();
	}

	/**
	 * Persist a service from POST data, then redirect on success.
	 *
	 * Validation errors are stashed for render_form() to display.
	 */
	private function save_from_post() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_request().
		$service_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$service    = $service_id ? $this->repo->find( $service_id ) : new AppointKit_Service();

		if ( ! $service ) {
			$service = new AppointKit_Service();
		}

		$service->name          = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$service->description   = wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) );
		$service->duration      = absint( $_POST['duration'] ?? 60 );
		$service->price         = (float) wp_unslash( $_POST['price'] ?? 0 );
		$service->color         = sanitize_hex_color( wp_unslash( $_POST['color'] ?? '#3788d8' ) ) ?: '#3788d8';
		$service->slot_interval = absint( $_POST['slot_interval'] ?? $service->duration );
		$service->buffer_before = absint( $_POST['buffer_before'] ?? 0 );
		$service->buffer_after  = absint( $_POST['buffer_after'] ?? 10 );
		$service->status        = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'active' ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $service->name ) ) {
			self::$errors  = array( __( 'Service name is required.', 'appointkit' ) );
			self::$posted  = $service;
			return;
		}

		$this->repo->save( $service );
		wp_safe_redirect( add_query_arg( array( 'page' => 'appointkit-services', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Render the services list table.
	 */
	private function render_list() {
		$services = $this->repo->get_all();
		include APPOINTKIT_PLUGIN_DIR . 'templates/admin/services-list.php';
	}

	/**
	 * Render the add/edit service form.
	 */
	private function render_form() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; the id only selects which record to show.
		$service_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$service    = self::$posted ?: ( $service_id ? $this->repo->find( $service_id ) : new AppointKit_Service() );
		$errors     = self::$errors;

		include APPOINTKIT_PLUGIN_DIR . 'templates/admin/service-form.php';
	}

	/**
	 * Handle service deletion.
	 */
	private function handle_delete() {
		$service_id = absint( $_GET['id'] ?? 0 );
		if ( ! $service_id ) {
			wp_safe_redirect( add_query_arg( 'page', 'appointkit-services', admin_url( 'admin.php' ) ) );
			exit;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'appointkit_delete_service_' . $service_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'appointkit' ) );
		}

		$this->repo->delete( $service_id );
		wp_safe_redirect( add_query_arg( array( 'page' => 'appointkit-services', 'deleted' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
