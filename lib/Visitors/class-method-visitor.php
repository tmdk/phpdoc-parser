<?php
/**
 * Method_Visitor
 *
 * @package WP_Parser\Visitors
 */

namespace WP_Parser\Visitors;

use PhpParser\Node;
use WP_Parser\Factory\Method_Factory;
use WP_Parser\Reflection\Class_;
use WP_Parser\Scope;

/**
 * Visitor for collecting methods.
 */
class Method_Visitor extends Scope_Aware_Visitor {
	private Method_Factory $method_factory;

	public function __construct( Scope $scope, Method_Factory $method_factory ) {
		parent::__construct( $scope );
		$this->method_factory = $method_factory;
	}

	/**
	 * {@inheritDoc}
	 */
	public function enterNode( Node $node ) {
		if ( ! $node instanceof Node\Stmt\ClassMethod ) {
			return null;
		}

		$current_class = $this->current_scope();
		assert( $current_class instanceof Class_ );

		$method = $this->method_factory->create( $node, $this->scope );
		$current_class->add_method( $method );

		$this->push_scope( $method );
	}

	/**
	 * {@inheritDoc}
	 */
	public function leaveNode( Node $node ) {
		if ( $node instanceof Node\Stmt\ClassMethod ) {
			$this->pop_scope();
		}

		return null;
	}
}
