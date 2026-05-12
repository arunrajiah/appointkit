<?php
/**
 * Payment result value object.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Holds the result of a payment gateway charge attempt.
 */
class AppointKit_Payment_Result {

	/** @var string Stripe PaymentIntent ID. */
	public $payment_intent_id;

	/** @var string succeeded|requires_action|failed */
	public $status;

	/** @var string|null URL for 3DS redirect when status = requires_action. */
	public $redirect_url;

	/**
	 * @param string      $payment_intent_id Stripe PI ID.
	 * @param string      $status            Payment status.
	 * @param string|null $redirect_url      3DS redirect URL.
	 */
	public function __construct( $payment_intent_id, $status, $redirect_url = null ) {
		$this->payment_intent_id = $payment_intent_id;
		$this->status            = $status;
		$this->redirect_url      = $redirect_url;
	}
}
