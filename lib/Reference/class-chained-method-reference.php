<?php
/**
 * Chained_Method_Reference
 *
 * @package WP_Parser\Reference
 */

namespace WP_Parser\Reference;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

/**
 * An instance method call chained on a function call: foo()->bar()
 */
class Chained_Method_Reference extends Reference {

	public function __construct(
		public readonly Name $function,
		public readonly Identifier $method,
		public readonly ?string $description = null,
	) {}
}
