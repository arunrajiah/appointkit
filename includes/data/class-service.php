<?php
/**
 * Service data model.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Represents a bookable service.
 */
class AppointKit_Service extends AppointKit_Base_Model {

	/** @var int */
	public $id = 0;

	/** @var string */
	public $name = '';

	/** @var string */
	public $description = '';

	/** @var int Duration in minutes. */
	public $duration = 60;

	/** @var float */
	public $price = 0.00;

	/** @var string Hex color for calendar display. */
	public $color = '#3788d8';

	/** @var int Slot interval in minutes (defaults to duration). */
	public $slot_interval = 60;

	/** @var int Buffer before booking in minutes. */
	public $buffer_before = 0;

	/** @var int Buffer after booking in minutes. */
	public $buffer_after = 10;

	/** @var string active|inactive */
	public $status = 'active';

	/** @var string MySQL datetime. */
	public $created_at = '';

	/** @var string MySQL datetime. */
	public $updated_at = '';
}
