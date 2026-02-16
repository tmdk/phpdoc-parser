<?php
/**
 * Serializer Attribute
 *
 * @package WP_Parser\Attributes
 */

namespace WP_Parser\Attributes;

use Attribute;

/**
 * Attribute to specify a custom serializer method for a property.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Serializer {
	/**
	 * Constructor.
	 *
	 * @param class-string $class The class name of the serializer method to call.
	 */
	public function __construct( public string $class ) {
	}
}
