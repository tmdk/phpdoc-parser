<?php
/**
 * Uses_Visitor
 *
 * @package WP_Parser\Visitors
 */

namespace WP_Parser\Visitors;

use PhpParser\Node;
use WP_Parser\Factory\Function_Call_Factory;
use WP_Parser\Factory\Method_Call_Factory;
use WP_Parser\Reflection\Has_Uses;
use WP_Parser\Scope;

/**
 * Visitor for tracking function and method calls (uses).
 */
class Uses_Visitor extends Scope_Aware_Visitor {

	private Method_Call_Factory $method_call_factory;
	private Function_Call_Factory $function_call_factory;

	public function __construct( Scope $scope, Function_Call_Factory $function_call_factory, Method_Call_Factory $method_call_factory ) {
		parent::__construct( $scope );
		$this->method_call_factory   = $method_call_factory;
		$this->function_call_factory = $function_call_factory;
	}

	/**
	 * {@inheritDoc}
	 */
	public function enterNode( Node $node ) {
		$current_scope = $this->closest_scope( Has_Uses::class );

		// Track function calls
		if ( $node instanceof Node\Expr\FuncCall ) {
			$function_call = $this->function_call_factory->create( $node );

			$current_scope->get_uses()->add_function( $function_call );
		}

		// Track static method calls (including constructor calls)
		if ( $node instanceof Node\Expr\MethodCall
			|| $node instanceof Node\Expr\StaticCall
			|| $node instanceof Node\Expr\New_
		) {
			$method_call = $this->method_call_factory->create( $node, $this->scope );

			if ( $method_call ) {
				$current_scope->get_uses()->add_method( $method_call );
			}
		}

		return null;
	}
}
