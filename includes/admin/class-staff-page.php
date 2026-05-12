<?php
/**
 * Staff admin page.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles the Staff CRUD admin page, including Google Calendar OAuth.
 */
class AppointKit_Staff_Page {

	/** @var AppointKit_Staff_Repository */
	private $repo;

	/** @var AppointKit_Services_Repository */
	private $services_repo;

	/** @var AppointKit_Google_Calendar_Sync */
	private $gcal;

	public function __construct() {
		$this->repo          = new AppointKit_Staff_Repository();
		$this->services_repo = new AppointKit_Services_Repository();
		$this->gcal          = new AppointKit_Google_Calendar_Sync();
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
			case 'gcal_connect':
				$this->handle_gcal_connect();
				break;
			case 'gcal_callback':
				$this->handle_gcal_callback();
				break;
			case 'gcal_disconnect':
				$this->handle_gcal_disconnect();
				break;
			default:
				$this->render_list();
		}
	}

	private function render_list() {
		$staff_list = $this->repo->get_all();
		include APPOINTKIT_PLUGIN_DIR . 'templates/admin/staff-list.php';
	}

	private function render_form() {
		$staff_id    = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$staff       = $staff_id ? $this->repo->find( $staff_id ) : new AppointKit_Staff();
		$services    = $this->services_repo->get_all();
		$errors      = array();
		$timezones   = timezone_identifiers_list();

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['appointkit_staff_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['appointkit_staff_nonce'] ) ), 'appointkit_save_staff' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'appointkit' ) );
			}

			$staff->name      = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
			$staff->email     = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
			$staff->phone     = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
			$staff->bio       = wp_kses_post( wp_unslash( $_POST['bio'] ?? '' ) );
			$staff->timezone  = sanitize_text_field( wp_unslash( $_POST['timezone'] ?? 'UTC' ) );
			$staff->status    = sanitize_text_field( wp_unslash( $_POST['status'] ?? 'active' ) );
			$service_ids      = array_map( 'absint', (array) ( $_POST['service_ids'] ?? array() ) );

			if ( empty( $staff->name ) ) {
				$errors[] = __( 'Staff name is required.', 'appointkit' );
			}

			if ( empty( $errors ) ) {
				$this->repo->save( $staff, $service_ids );
				wp_safe_redirect( add_query_arg( array( 'page' => 'appointkit-staff', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		include APPOINTKIT_PLUGIN_DIR . 'templates/admin/staff-form.php';
	}

	private function handle_delete() {
		$staff_id = absint( $_GET['id'] ?? 0 );
		if ( ! $staff_id ) {
			wp_safe_redirect( add_query_arg( 'page', 'appointkit-staff', admin_url( 'admin.php' ) ) );
			exit;
		}
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'appointkit_delete_staff_' . $staff_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'appointkit' ) );
		}
		$this->repo->delete( $staff_id );
		wp_safe_redirect( add_query_arg( array( 'page' => 'appointkit-staff', 'deleted' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private function handle_gcal_connect() {
		$staff_id = absint( $_GET['id'] ?? 0 );
		if ( ! $staff_id || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'appointkit_gcal_connect_' . $staff_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'appointkit' ) );
		}
		$auth_url = $this->gcal->get_auth_url( $staff_id );
		wp_redirect( $auth_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}

	private function handle_gcal_callback() {
		$state    = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) );
		$parts    = explode( ':', $state, 2 );
		$nonce    = $parts[0] ?? '';
		$staff_id = absint( $parts[1] ?? 0 );

		if ( ! wp_verify_nonce( $nonce, 'appointkit_gcal_' . $staff_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'appointkit' ) );
		}

		$code  = sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) );
		$staff = $this->repo->find( $staff_id );
		if ( $staff && $code ) {
			$this->gcal->handle_oauth_callback( $code, $staff );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'appointkit-staff', 'action' => 'edit', 'id' => $staff_id, 'gcal' => 'connected' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private function handle_gcal_disconnect() {
		$staff_id = absint( $_GET['id'] ?? 0 );
		if ( ! $staff_id || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'appointkit_gcal_disconnect_' . $staff_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'appointkit' ) );
		}

		$staff = $this->repo->find( $staff_id );
		if ( $staff ) {
			$staff->google_calendar_token = '';
			$this->repo->save( $staff, $staff->service_ids );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'appointkit-staff', 'action' => 'edit', 'id' => $staff_id, 'gcal' => 'disconnected' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
