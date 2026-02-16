<?php
/**
 * Serialized_Name Attribute
 *
 * @package WP_Parser\Attributes
 */

namespace WP_Parser\Attributes;

use Attribute;

/**
 * Attribute to specify the serialized name of a property.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Serialized_Name {
	/**
	 * Constructor.
	 *
	 * @param string $name The name to use in the serialized output.
	 */
	public function __construct( public string $name ) {
	}
}
