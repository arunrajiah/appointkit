<?php
/**
 * Stripe payment gateway (one-off charges via Stripe Elements JS + PaymentIntents API).
 *
 * @package AppointKit
 *
 * No Stripe PHP SDK is bundled — the charge is created server-side via wp_remote_post()
 * to the Stripe API. This keeps vendor/ clean and avoids GPL-incompatibility concerns.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles PaymentIntent creation and confirmation via the Stripe API.
 */
class AppointKit_Stripe_Gateway {

	private const API_BASE = 'https://api.stripe.com/v1';

	/**
	 * Create a PaymentIntent and attempt to confirm it with a PaymentMethod.
	 *
	 * @param int                 $amount_cents Amount in the smallest currency unit (e.g., cents).
	 * @param string              $payment_method_id Stripe PaymentMethod ID from Stripe Elements.
	 * @param AppointKit_Booking  $booking       The booking being paid for.
	 * @return AppointKit_Payment_Result|WP_Error
	 */
	public function charge( $amount_cents, $payment_method_id, AppointKit_Booking $booking ) {
		$secret_key = appointkit_get_stripe_sk();
		if ( empty( $secret_key ) ) {
			return new WP_Error( 'stripe_not_configured', __( 'Payment is not configured. Please contact the site administrator.', 'appointkit' ) );
		}

		// Create PaymentIntent.
		$pi_response = $this->api_post(
			'/payment_intents',
			array(
				'amount'               => absint( $amount_cents ),
				'currency'             => strtolower( get_option( 'appointkit_currency', 'usd' ) ),
				'payment_method'       => sanitize_text_field( $payment_method_id ),
				'confirmation_method'  => 'manual',
				'confirm'              => 'true',
				'return_url'           => home_url( '/appointkit/booking-complete' ),
				'description'          => sprintf(
					/* translators: 1: booking ID, 2: service ID */
					__( 'AppointKit Booking #%1$d (Service #%2$d)', 'appointkit' ),
					$booking->id,
					$booking->service_id
				),
				'metadata[booking_id]'   => $booking->id,
				'metadata[service_id]'   => $booking->service_id,
				'metadata[staff_id]'     => $booking->staff_id,
				'metadata[customer_email]' => $booking->customer_email,
			),
			$secret_key
		);

		if ( is_wp_error( $pi_response ) ) {
			return $pi_response;
		}

		$pi_status = $pi_response['status'] ?? '';
		$pi_id     = sanitize_text_field( $pi_response['id'] ?? '' );

		if ( 'succeeded' === $pi_status ) {
			return new AppointKit_Payment_Result( $pi_id, 'succeeded' );
		}

		if ( 'requires_action' === $pi_status ) {
			$redirect = $pi_response['next_action']['redirect_to_url']['url'] ?? '';
			return new AppointKit_Payment_Result( $pi_id, 'requires_action', $redirect );
		}

		return new WP_Error( 'payment_failed', __( 'Payment could not be processed. Please try again.', 'appointkit' ) );
	}

	/**
	 * Confirm a PaymentIntent after 3DS authentication.
	 *
	 * @param string $payment_intent_id Stripe PI ID.
	 * @return AppointKit_Payment_Result|WP_Error
	 */
	public function confirm( $payment_intent_id ) {
		$secret_key = appointkit_get_stripe_sk();
		if ( empty( $secret_key ) ) {
			return new WP_Error( 'stripe_not_configured', __( 'Payment is not configured.', 'appointkit' ) );
		}

		$response = $this->api_post(
			'/payment_intents/' . rawurlencode( sanitize_text_field( $payment_intent_id ) ) . '/confirm',
			array(),
			$secret_key
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 'succeeded' === ( $response['status'] ?? '' ) ) {
			return new AppointKit_Payment_Result( sanitize_text_field( $response['id'] ), 'succeeded' );
		}

		return new WP_Error( 'payment_failed', __( 'Payment confirmation failed.', 'appointkit' ) );
	}

	/**
	 * Make an authenticated POST request to the Stripe API.
	 *
	 * @param string $endpoint   API endpoint path (e.g., '/payment_intents').
	 * @param array  $body       Request body as key-value pairs.
	 * @param string $secret_key Stripe secret key.
	 * @return array|WP_Error Decoded JSON response array.
	 */
	private function api_post( $endpoint, array $body, $secret_key ) {
		$response = wp_remote_post(
			self::API_BASE . $endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $secret_key,
					'Content-Type'  => 'application/x-www-form-urlencoded',
				),
				'body'    => $body,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) ) {
			return new WP_Error( 'stripe_parse_error', __( 'Unexpected response from payment gateway.', 'appointkit' ) );
		}

		if ( $code >= 400 ) {
			$message = $body['error']['message'] ?? __( 'An error occurred with the payment gateway.', 'appointkit' );
			return new WP_Error( 'stripe_error', wp_kses_post( $message ) );
		}

		return $body;
	}
}
