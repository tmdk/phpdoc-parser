<?php
/**
 * Const_Reference
 *
 * @package WP_Parser\Reference
 */

namespace WP_Parser\Reference;

use PhpParser\Node\Name;

/**
 * A bare name reference: a global constant, class name, or function without parens.
 * Examples: WP_ENVIRONMENT_TYPE, WP_REST_Controller, \Foo, namespace\Foo
 */
class Const_Reference extends Reference {

	public function __construct(
		public readonly Name $name,
		public readonly ?string $description = null,
	) {}
}
