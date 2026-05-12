<?php
/**
 * Google Calendar 1-way sync (read busy times).
 *
 * @package AppointKit
 *
 * Free tier: reads busy events from staff's Google Calendar to avoid double-bookings.
 * No write-back to Google in the Free plugin.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fetches busy time blocks from Google Calendar for a staff member.
 */
class AppointKit_Google_Calendar_Sync {

	private const FREEBUSY_URL = 'https://www.googleapis.com/calendar/v3/freeBusy';
	private const AUTH_URL     = 'https://accounts.google.com/o/oauth2/v2/auth';
	private const TOKEN_URL    = 'https://oauth2.googleapis.com/token';

	/**
	 * Get busy blocks from Google Calendar for a staff member in a UTC window.
	 *
	 * @param AppointKit_Staff $staff        Staff model.
	 * @param DateTime         $window_start UTC window start.
	 * @param DateTime         $window_end   UTC window end.
	 * @return array[] Each: { start: DateTime, end: DateTime }
	 */
	public function get_busy_blocks( AppointKit_Staff $staff, DateTime $window_start, DateTime $window_end ) {
		if ( empty( $staff->google_calendar_token ) ) {
			return array();
		}

		$token_data = json_decode( $staff->google_calendar_token, true );
		if ( empty( $token_data['access_token'] ) ) {
			return array();
		}

		$access_token = $this->maybe_refresh_token( $token_data, $staff );
		if ( empty( $access_token ) ) {
			return array();
		}

		$response = wp_remote_post(
			self::FREEBUSY_URL,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( array(
					'timeMin' => $window_start->format( DateTime::ATOM ),
					'timeMax' => $window_end->format( DateTime::ATOM ),
					'items'   => array( array( 'id' => 'primary' ) ),
				) ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		$busy = $data['calendars']['primary']['busy'] ?? array();

		$utc    = new DateTimeZone( 'UTC' );
		$blocks = array();
		foreach ( $busy as $slot ) {
			try {
				$blocks[] = array(
					'start' => new DateTime( $slot['start'], $utc ),
					'end'   => new DateTime( $slot['end'], $utc ),
				);
			} catch ( Exception $e ) {
				// Skip malformed entries.
				continue;
			}
		}

		return $blocks;
	}

	/**
	 * Exchange an authorization code for tokens and store them.
	 *
	 * @param string           $code     OAuth authorization code.
	 * @param AppointKit_Staff $staff    Staff model.
	 * @return bool
	 */
	public function handle_oauth_callback( $code, AppointKit_Staff $staff ) {
		$client_id     = get_option( 'appointkit_google_client_id', '' );
		$client_secret = get_option( 'appointkit_google_client_secret', '' );
		$redirect_uri  = admin_url( 'admin.php?page=appointkit-staff&action=gcal_callback' );

		$response = wp_remote_post(
			self::TOKEN_URL,
			array(
				'body' => array(
					'code'          => sanitize_text_field( $code ),
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'redirect_uri'  => $redirect_uri,
					'grant_type'    => 'authorization_code',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$token_data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $token_data['access_token'] ) ) {
			return false;
		}

		$token_data['obtained_at'] = time();

		$repo           = new AppointKit_Staff_Repository();
		$staff->google_calendar_token = wp_json_encode( $token_data );
		$repo->save( $staff, $staff->service_ids );

		return true;
	}

	/**
	 * Build the Google OAuth authorization URL for a staff member.
	 *
	 * @param int $staff_id Staff ID.
	 * @return string
	 */
	public function get_auth_url( $staff_id ) {
		$client_id    = get_option( 'appointkit_google_client_id', '' );
		$redirect_uri = admin_url( 'admin.php?page=appointkit-staff&action=gcal_callback' );
		$state        = wp_create_nonce( 'appointkit_gcal_' . $staff_id );

		return add_query_arg(
			array(
				'client_id'             => rawurlencode( $client_id ),
				'redirect_uri'          => rawurlencode( $redirect_uri ),
				'response_type'         => 'code',
				'scope'                 => rawurlencode( 'https://www.googleapis.com/auth/calendar.readonly' ),
				'access_type'           => 'offline',
				'prompt'                => 'consent',
				'state'                 => rawurlencode( $state . ':' . $staff_id ),
			),
			self::AUTH_URL
		);
	}

	/**
	 * Refresh the access token if it has expired.
	 *
	 * @param array            $token_data Stored token data array.
	 * @param AppointKit_Staff $staff      Staff model.
	 * @return string Access token, or empty string on failure.
	 */
	private function maybe_refresh_token( array $token_data, AppointKit_Staff $staff ) {
		$expires_in   = (int) ( $token_data['expires_in'] ?? 3600 );
		$obtained_at  = (int) ( $token_data['obtained_at'] ?? 0 );
		$access_token = $token_data['access_token'];

		if ( time() < $obtained_at + $expires_in - 60 ) {
			return $access_token;
		}

		if ( empty( $token_data['refresh_token'] ) ) {
			return '';
		}

		$client_id     = get_option( 'appointkit_google_client_id', '' );
		$client_secret = get_option( 'appointkit_google_client_secret', '' );

		$response = wp_remote_post(
			self::TOKEN_URL,
			array(
				'body' => array(
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'refresh_token' => $token_data['refresh_token'],
					'grant_type'    => 'refresh_token',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}

		$new_data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $new_data['access_token'] ) ) {
			return '';
		}

		$token_data['access_token']  = $new_data['access_token'];
		$token_data['expires_in']    = $new_data['expires_in'] ?? 3600;
		$token_data['obtained_at']   = time();

		$repo                         = new AppointKit_Staff_Repository();
		$staff->google_calendar_token = wp_json_encode( $token_data );
		$repo->save( $staff, $staff->service_ids );

		return $token_data['access_token'];
	}
}
