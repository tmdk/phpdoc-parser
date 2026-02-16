<?php
/**
 * Serialize_Null
 *
 * @package WP_Parser\Attributes
 */

namespace WP_Parser\Attributes;

use Attribute;

/**
 * Attribute to specify if a null value should be serialized.
 */
#[Attribute( Attribute::TARGET_PROPERTY )]
class Serialize_Null {
}
