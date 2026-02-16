<?php
/**
 * Function_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use PhpParser\Node;
use WP_Parser\Reflection\Function_;
use WP_Parser\Scope;

/**
 * Factory for creating Function_ objects from php-parser nodes.
 */
class Function_Factory {
	private Docblock_Factory $docblock_factory;
	private Param_Factory $param_factory;

	public function __construct( Docblock_Factory $docblock_factory, Param_Factory $param_factory ) {
		$this->docblock_factory = $docblock_factory;
		$this->param_factory    = $param_factory;
	}

	/**
	 * Create a Function_ from a php-parser function node.
	 *
	 * @param Node\Stmt\Function_ $node
	 *
	 * @return Function_
	 */
	public function create( Node\Stmt\Function_ $node, Scope $scope ): Function_ {
		$namespace = $scope->namespace();

		$function = new Function_();
		$function->set_name( $node->name->toString() );
		$function->set_line( $node->getStartLine() );
		$function->set_end_line( $node->getEndLine() );
		$function->set_aliases( $namespace->get_aliases() );

		$function->set_namespace( $this->get_namespace( $node ) );

		// Parameters
		$params = [];
		foreach ( $node->params as $param_node ) {
			$params[] = $this->param_factory->create( $param_node );
		}
		$function->set_arguments( $params );

		// Docblock
		$doc_comment = $node->getDocComment();
		if ( $doc_comment ) {
			$function->set_doc_block( $this->docblock_factory->create( $doc_comment ) );
		} else {
			$function->set_doc_block( $this->docblock_factory->create_empty() );
		}

		return $function;
	}

	private function get_namespace( Node\Stmt\Function_ $node ): string {
		$namespace = null;

		if ( $node->namespacedName instanceof Node\Name ) {
			$namespace = $node->namespacedName->slice( 0, -1 )?->toCodeString();
		}

		return $namespace ?: 'global';
	}
}
