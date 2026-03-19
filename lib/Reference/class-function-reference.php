<?php
/**
 * Function_Reference
 *
 * @package WP_Parser\Reference
 */

namespace WP_Parser\Reference;

use PhpParser\Node\Name;

/**
 * A function call reference: foo(), \foo(), namespace\foo(), \ns\foo()
 */
class Function_Reference extends Reference {

	public function __construct(
		public readonly Name $name,
		public readonly ?string $description = null,
	) {}
}
