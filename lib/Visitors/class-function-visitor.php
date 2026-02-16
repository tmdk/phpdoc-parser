<?php
/**
 * Function_Visitor
 *
 * @package WP_Parser\Visitors
 */

namespace WP_Parser\Visitors;

use PhpParser\Node;
use WP_Parser\Factory\Function_Factory;
use WP_Parser\Scope;

/**
 * Visitor for collecting functions.
 */
class Function_Visitor extends Scope_Aware_Visitor {
	private Function_Factory $function_factory;

	public function __construct( Scope $scope, Function_Factory $function_factory ) {
		parent::__construct( $scope );
		$this->function_factory = $function_factory;
	}

	/**
	 * {@inheritDoc}
	 */
	public function enterNode( Node $node ) {
		if ( $node instanceof Node\Stmt\Function_ ) {
			$function     = $this->function_factory->create( $node, $this->scope );
			$current_file = $this->file_scope();
			$current_file->add_function( $function );

			$this->push_scope( $function );
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function leaveNode( Node $node ) {
		if ( $node instanceof Node\Stmt\Function_ ) {
			$this->pop_scope();
		}

		return null;
	}
}
