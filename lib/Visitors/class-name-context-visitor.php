<?php
/**
 * Name_Context_Visitor
 *
 * @package WP_Parser\Visitors
 */

namespace WP_Parser\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Class Name_Context_Visitor
 */
class Name_Context_Visitor extends NodeVisitorAbstract {

	private array $nodes = [];

	public function enterNode( Node $node ): void {
		$context_node = $this->nodes[ array_key_last( $this->nodes ) ] ?? null;

		if ( $node instanceof Node\Name\FullyQualified && $context_node instanceof Node ) {
			$node->setAttribute( 'nameContextNodeClass', $context_node::class );
		}

		foreach ( $node->getSubNodeNames() as $subnode_name ) {
			$subnode = $node->$subnode_name;

			if ( $subnode instanceof Node\Name\FullyQualified ) {
				$subnode->setAttribute( 'nameContextProperty', $subnode_name );
			}
		}

		$this->nodes[] = $node;
	}

	public function leaveNode( Node $node ): void {
		array_pop( $this->nodes );
	}

}
