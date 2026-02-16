<?php
/**
 * Method_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use PhpParser\Node;
use WP_Parser\Reflection\Class_;
use WP_Parser\Reflection\Method;
use WP_Parser\Scope;

/**
 * Factory for creating Method objects from php-parser nodes.
 */
class Method_Factory {
	private Docblock_Factory $docblock_factory;
	private Param_Factory $param_factory;

	public function __construct( Docblock_Factory $docblock_factory, Param_Factory $param_factory ) {
		$this->docblock_factory = $docblock_factory;
		$this->param_factory    = $param_factory;
	}

	/**
	 * Create a Method from a php-parser method node.
	 *
	 * @param Node\Stmt\ClassMethod $node
	 *
	 * @return Method
	 */
	public function create( Node\Stmt\ClassMethod $node, Scope $scope ): Method {
		$class     = $scope->closest( Class_::class );
		$namespace = $scope->namespace();

		$method = new Method();
		$method->set_name( $node->name->toString() );
		$method->set_line( $node->getStartLine() );
		$method->set_end_line( $node->getEndLine() );
		$method->set_final( $node->isFinal() );
		$method->set_abstract( $node->isAbstract() );
		$method->set_static( $node->isStatic() );
		$method->set_namespace( $class->get_namespace() );
		$method->set_aliases( $namespace->get_aliases() );

		// Visibility
		if ( $node->isPublic() ) {
			$method->set_visibility( 'public' );
		} elseif ( $node->isProtected() ) {
			$method->set_visibility( 'protected' );
		} elseif ( $node->isPrivate() ) {
			$method->set_visibility( 'private' );
		}

		// Parameters
		$params = [];
		foreach ( $node->params as $param_node ) {
			$params[] = $this->param_factory->create( $param_node );
		}
		$method->set_arguments( $params );

		// Docblock
		$doc_comment = $node->getDocComment();
		if ( $doc_comment ) {
			$method->set_doc_block( $this->docblock_factory->create( $doc_comment ) );
		} else {
			$method->set_doc_block( $this->docblock_factory->create_empty() );
		}

		return $method;
	}

}
