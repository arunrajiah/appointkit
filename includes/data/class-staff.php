<?php
/**
 * Staff data model.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Represents a staff member who provides services.
 */
class AppointKit_Staff extends AppointKit_Base_Model {

	/** @var int */
	public $id = 0;

	/** @var int Associated WordPress user ID (0 = no WP account). */
	public $wp_user_id = 0;

	/** @var string */
	public $name = '';

	/** @var string */
	public $email = '';

	/** @var string */
	public $phone = '';

	/** @var string */
	public $bio = '';

	/** @var string URL of profile photo. */
	public $photo_url = '';

	/** @var string IANA timezone identifier. */
	public $timezone = 'UTC';

	/** @var string JSON-encoded Google Calendar OAuth token. */
	public $google_calendar_token = '';

	/** @var string Token used in iCal feed URL. */
	public $ical_token = '';

	/** @var string active|inactive */
	public $status = 'active';

	/** @var string MySQL datetime. */
	public $created_at = '';

	/** @var string MySQL datetime. */
	public $updated_at = '';

	/** @var int[] Service IDs this staff member offers (loaded separately). */
	public $service_ids = array();
}
