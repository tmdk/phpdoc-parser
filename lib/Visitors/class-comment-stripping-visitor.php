<?php
/**
 * Comment_Stripping_Visitor
 *
 * @package WP_Parser\Visitors
 */

namespace WP_Parser\Visitors;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Class Comment_Stripping_Visitor
 */
class Comment_Stripping_Visitor extends NodeVisitorAbstract {

	public function enterNode( Node $node ): void {
		if ( $node instanceof Node\ArrayItem ) {
			// Strip regular comments but keep DocBlocks.
			$comments = array_filter(
				$node->getComments(),
				fn( $comment ) => $comment instanceof Doc
			);
			$node->setAttribute( 'comments', array_values( $comments ) );
		}
	}

}
