<?php
/**
 * Param_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use PhpParser\Node;
use WP_Parser\Formatter\Pretty_Printer;
use WP_Parser\Reflection\Param;

/**
 * Factory for creating Param objects from php-parser nodes.
 */
class Param_Factory {

	private Pretty_Printer $pretty_printer;

	public function __construct() {
		$this->pretty_printer = new Pretty_Printer();
	}

	/**
	 * Create a Param from a php-parser parameter node.
	 *
	 * @param Node\Param $node
	 *
	 * @return Param
	 */
	public function create( Node\Param $node ): Param {
		$param = new Param();
		$param->set_name( '$' . $node->var->name );

		// Set type if present
		if ( $node->type !== null ) {
			$param->set_type( $this->pretty_printer->prettyPrint( [ $node->type ] ) );
		}

		// Set default value if present
		if ( $node->default !== null ) {
			$param->set_default( $this->get_default_value( $node->default ) );
		}

		return $param;
	}

	/**
	 * Get the default value of a parameter.
	 *
	 * @param Node\Expr $expr
	 *
	 * @return string
	 */
	private function get_default_value( Node\Expr $expr ): string {
		return $this->pretty_printer->prettyPrintExpr( $expr );
	}
}
