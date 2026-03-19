<?php
/**
 * Class_Const_Reference
 *
 * @package WP_Parser\Reference
 */

namespace WP_Parser\Reference;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

/**
 * A class constant reference: Foo::BAR, \Foo::BAR, self::BAR, namespace\Foo::BAR
 * Also covers Foo::property_name (no $ sigil) per strict-syntax convention.
 */
class Class_Const_Reference extends Reference {

	public function __construct(
		public readonly Name $class,
		public readonly Identifier $const,
		public readonly ?string $description = null,
	) {}
}
