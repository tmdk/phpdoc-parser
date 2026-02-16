<?php
/**
 * Interface_Visitor
 *
 * @package WP_Parser\Visitors
 */

namespace WP_Parser\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Class Interface_Visitor
 */
class Interface_Visitor extends NodeVisitorAbstract {

	public function enterNode( Node $node ): ?int {
		if ( $node instanceof Node\Stmt\Interface_ ) {
			return self::DONT_TRAVERSE_CHILDREN;
		}

		return null;
	}

}
