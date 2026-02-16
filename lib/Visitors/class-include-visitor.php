<?php
/**
 * Include_Visitor
 *
 * @package WP_Parser\Visitors
 */

namespace WP_Parser\Visitors;

use PhpParser\Node;
use WP_Parser\Factory\Include_Factory;
use WP_Parser\Scope;

/**
 * Class Include_Visitor
 */
class Include_Visitor extends Scope_Aware_Visitor {

	private Include_Factory $include_factory;

	public function __construct( Scope $scope, Include_Factory $include_factory ) {
		parent::__construct( $scope );
		$this->include_factory = $include_factory;
	}

	public function enterNode( Node $node ): void {
		if ( $node instanceof Node\Expr\Include_ ) {
			$this->file_scope()->add_include( $this->include_factory->create( $node ) );
		}
	}

}
