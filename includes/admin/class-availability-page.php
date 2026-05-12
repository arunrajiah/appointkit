<?php
/**
 * Availability admin page.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the weekly availability grid editor for each staff member.
 */
class AppointKit_Availability_Page {

	/** @var AppointKit_Availability_Repository */
	private $repo;

	/** @var AppointKit_Staff_Repository */
	private $staff_repo;

	public function __construct() {
		$this->repo       = new AppointKit_Availability_Repository();
		$this->staff_repo = new AppointKit_Staff_Repository();
	}

	/**
	 * Render the page.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'appointkit' ) );
		}

		$staff_id = absint( $_GET['staff_id'] ?? 0 );
		$staff    = $staff_id ? $this->staff_repo->find( $staff_id ) : null;
		$all_staff = $this->staff_repo->get_all();

		// Default to first staff member.
		if ( ! $staff && ! empty( $all_staff ) ) {
			$staff    = $all_staff[0];
			$staff_id = $staff->id;
		}

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['appointkit_availability_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['appointkit_availability_nonce'] ) ), 'appointkit_save_availability' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'appointkit' ) );
			}
			$this->handle_save( $staff_id );
		}

		$rules = $staff_id ? $this->repo->get_for_staff( $staff_id ) : array();
		$days  = array(
			0 => __( 'Sunday', 'appointkit' ),
			1 => __( 'Monday', 'appointkit' ),
			2 => __( 'Tuesday', 'appointkit' ),
			3 => __( 'Wednesday', 'appointkit' ),
			4 => __( 'Thursday', 'appointkit' ),
			5 => __( 'Friday', 'appointkit' ),
			6 => __( 'Saturday', 'appointkit' ),
		);

		include APPOINTKIT_PLUGIN_DIR . 'templates/admin/availability.php';
	}

	/**
	 * Save submitted availability rules for a staff member.
	 *
	 * @param int $staff_id Staff ID.
	 */
	private function handle_save( $staff_id ) {
		if ( ! $staff_id ) {
			return;
		}

		// Delete existing rules and re-insert from form data.
		$this->repo->delete_for_staff( $staff_id );

		$weekday_rules = (array) ( $_POST['weekday'] ?? array() );
		foreach ( $weekday_rules as $day => $rule_data ) {
			$rule              = new AppointKit_Availability_Rule();
			$rule->staff_id    = $staff_id;
			$rule->type        = 'weekday';
			$rule->weekday     = absint( $day );
			$rule->is_off      = empty( $rule_data['active'] ) ? 1 : 0;
			$rule->start_time  = appointkit_sanitize_time( $rule_data['start_time'] ?? '09:00:00' );
			$rule->end_time    = appointkit_sanitize_time( $rule_data['end_time'] ?? '17:00:00' );
			$this->repo->save( $rule );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'appointkit-availability', 'staff_id' => $staff_id, 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
