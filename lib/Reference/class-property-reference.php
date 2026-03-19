<?php
/**
 * Property_Reference
 *
 * @package WP_Parser\Reference
 */

namespace WP_Parser\Reference;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

/**
 * A property reference with explicit $ sigil: Foo::$prop, \Foo::$prop
 */
class Property_Reference extends Reference {

	public function __construct(
		public readonly Name $class,
		public readonly Identifier $property,
		public readonly ?string $description = null,
	) {}
}
