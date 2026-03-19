<?php
/**
 * Method_Reference
 *
 * @package WP_Parser\Reference
 */

namespace WP_Parser\Reference;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

/**
 * A static method reference: Foo::bar(), \Foo::bar(), self::bar(), namespace\Foo::bar()
 */
class Method_Reference extends Reference {

	public function __construct(
		public readonly Name $class,
		public readonly Identifier $method,
		public readonly ?string $description = null,
	) {}
}
