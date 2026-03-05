<?php
/**
 * Param_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use PhpParser\Node;
use WP_Parser\Formatter\Pretty_Printer;
use WP_Parser\Formatter\Templated_String_Printer;
use WP_Parser\Reflection\Param;

/**
 * Factory for creating Param objects from php-parser nodes.
 */
class Param_Factory {

	private Templated_String_Printer $templated_printer;
	private Pretty_Printer $pretty_printer;

	public function __construct() {
		$this->templated_printer = new Templated_String_Printer();
		$this->pretty_printer    = new Pretty_Printer( use_fully_qualified_names: false );
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
			$param->set_type( $this->templated_printer->print_node( $node->type ) );
		}

		// Set default value if present
		if ( $node->default !== null ) {
			$param->set_default( $this->pretty_printer->prettyPrintExpr( $node->default ) );
		}

		return $param;
	}

}
