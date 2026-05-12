<?php
/**
 * Availability rule data model.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Represents a staff availability rule (weekday schedule or date-specific override).
 */
class AppointKit_Availability_Rule extends AppointKit_Base_Model {

	/** @var int */
	public $id = 0;

	/** @var int */
	public $staff_id = 0;

	/**
	 * Rule type.
	 * - 'weekday'  — recurring rule for a specific day of the week (0=Sun … 6=Sat).
	 * - 'date'     — single-date override (on/off or custom hours).
	 *
	 * @var string weekday|date
	 */
	public $type = 'weekday';

	/** @var int Day of week 0–6 (only relevant when type = 'weekday'). */
	public $weekday = 0;

	/** @var string Specific date in Y-m-d format (only relevant when type = 'date'). */
	public $date = '0000-00-00';

	/** @var string Working-hours start time H:i:s. */
	public $start_time = '09:00:00';

	/** @var string Working-hours end time H:i:s. */
	public $end_time = '17:00:00';

	/** @var int 1 = day off (no slots), 0 = working. */
	public $is_off = 0;

	/** @var string MySQL datetime. */
	public $created_at = '';

	/** @var string MySQL datetime. */
	public $updated_at = '';
}
