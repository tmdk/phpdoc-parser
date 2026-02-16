<?php
/**
 * Constant_Visitor
 *
 * @package WP_Parser\Visitors
 */

namespace WP_Parser\Visitors;

use PhpParser\Node;
use WP_Parser\Factory\Constant_Factory;
use WP_Parser\Scope;

/**
 * Class Constant_Visitor
 */
class Constant_Visitor extends Scope_Aware_Visitor {

	private Constant_Factory $constant_factory;

	public function __construct( Scope $scope, Constant_Factory $constant_factory ) {
		parent::__construct( $scope );
		$this->constant_factory = $constant_factory;
	}

	public function enterNode( Node $node ): void {
		if ( $this->is_const( $node ) ) {
			foreach ( $this->constant_factory->create( $node ) as $constant ) {
				$this->file_scope()->add_constant( $constant );
			}
		}
	}

	private function is_const( Node $node ): bool {
		return $this->is_define( $node ) || $node instanceof Node\Stmt\Const_;
	}

	private function is_define( Node $node ): bool {
		return $node instanceof Node\Expr\FuncCall
			&& $node->name instanceof Node\Name
			&& $node->name->toString() === 'define'
			&& count( $node->args ) >= 2;
	}

}
