<?php
/**
 * Abstract base model class.
 *
 * @package AppointKit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides common constructor and property-hydration for all data models.
 */
abstract class AppointKit_Base_Model {

	/**
	 * Hydrate model properties from a stdClass database row or associative array.
	 *
	 * @param object|array $data Row data from the database.
	 */
	public function __construct( $data = null ) {
		if ( null === $data ) {
			return;
		}
		$data = is_array( $data ) ? (object) $data : $data;
		foreach ( get_object_vars( $data ) as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}
	}

	/**
	 * Convert model to array.
	 *
	 * @return array
	 */
	public function to_array() {
		return get_object_vars( $this );
	}
}
