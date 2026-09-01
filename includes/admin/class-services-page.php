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
			case 'delete':
				$this->handle_delete();
				break;
			default:
				$this->render_list();
		}
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
		$service_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$service    = $service_id ? $this->repo->find( $service_id ) : new AppointKit_Service();
		$errors     = array();

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['appointkit_service_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['appointkit_service_nonce'] ) ), 'appointkit_save_service' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'appointkit' ) );
			}

			$service->name          = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
			$service->description   = wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) );
			$service->duration      = absint( $_POST['duration'] ?? 60 );
			$service->price         = (float) wp_unslash( $_POST['price'] ?? 0 );
			$service->color         = sanitize_hex_color( wp_unslash( $_POST['color'] ?? '#3788d8' ) ) ?: '#3788d8';
			$service->slot_interval = absint( $_POST['slot_interval'] ?? $service->duration );
			$service->buffer_before = absint( $_POST['buffer_before'] ?? 0 );
			$service->buffer_after  = absint( $_POST['buffer_after'] ?? 10 );
			$service->status        = sanitize_text_field( $_POST['status'] ?? 'active' );

			if ( empty( $service->name ) ) {
				$errors[] = __( 'Service name is required.', 'appointkit' );
			}

			if ( empty( $errors ) ) {
				$id = $this->repo->save( $service );
				wp_safe_redirect( add_query_arg( array( 'page' => 'appointkit-services', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

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
