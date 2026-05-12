<?php
/**
 * Booking data model.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Represents a customer appointment booking.
 */
class AppointKit_Booking extends AppointKit_Base_Model {

	/** @var int */
	public $id = 0;

	/** @var int */
	public $service_id = 0;

	/** @var int */
	public $staff_id = 0;

	/** @var string */
	public $customer_name = '';

	/** @var string */
	public $customer_email = '';

	/** @var string */
	public $customer_phone = '';

	/** @var string Booking start time in UTC (MySQL datetime). */
	public $start_utc = '';

	/** @var string Booking end time in UTC (MySQL datetime). */
	public $end_utc = '';

	/** @var string pending|confirmed|cancelled|completed|no_show */
	public $status = 'pending';

	/** @var float Amount charged. */
	public $price = 0.00;

	/** @var string Stripe PaymentIntent ID. */
	public $payment_intent_id = '';

	/** @var string unpaid|paid|refunded|failed */
	public $payment_status = 'unpaid';

	/** @var string Customer-provided notes. */
	public $notes = '';

	/** @var string JSON-encoded extra meta (custom form fields, etc.). */
	public $meta = '';

	/** @var string MySQL datetime. */
	public $created_at = '';

	/** @var string MySQL datetime. */
	public $updated_at = '';

	/**
	 * Get decoded meta as associative array.
	 *
	 * @return array
	 */
	public function get_meta() {
		if ( empty( $this->meta ) ) {
			return array();
		}
		$decoded = json_decode( $this->meta, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Set a meta value and re-encode.
	 *
	 * @param string $key   Meta key.
	 * @param mixed  $value Meta value.
	 */
	public function set_meta( $key, $value ) {
		$meta         = $this->get_meta();
		$meta[ $key ] = $value;
		$this->meta   = wp_json_encode( $meta );
	}
}
