<?php
/**
 * Constant_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Const_;
use WP_Parser\Formatter\Templated_String;
use WP_Parser\Formatter\Templated_String_Printer;
use WP_Parser\Reflection\Constant;

/**
 * Class Constant_Factory
 */
class Constant_Factory {

	private Templated_String_Printer $printer;

	public function __construct() {
		$this->printer = new Templated_String_Printer();
	}

	/**
	 * Create constant(s) from a define() function call or const declaration.
	 *
	 * @param FuncCall|Const_ $node
	 *
	 * @return Constant[]
	 */
	public function create( FuncCall|Const_ $node ): array {
		if ( $node instanceof FuncCall ) {
			return [ $this->create_from_define( $node ) ];
		}

		return $this->create_from_const( $node );
	}

	/**
	 * Create a constant from a define() function call.
	 *
	 * @param FuncCall $node
	 *
	 * @return Constant
	 */
	private function create_from_define( FuncCall $node ): Constant {
		$constant = new Constant();

		$constant->set_name( $this->get_name( $node->args[0]->value ) );
		$constant->set_value( $this->get_value( $node->args[1]->value ) );
		$constant->set_line( $node->getStartLine() );

		return $constant;
	}

	/**
	 * Create constants from a const declaration statement.
	 *
	 * @param Const_ $node
	 *
	 * @return Constant[]
	 */
	private function create_from_const( Const_ $node ): array {
		$constants = [];

		foreach ( $node->consts as $const ) {
			$constant = new Constant();
			$constant->set_name( $const->name->toString() );
			$constant->set_value( $this->get_value( $const->value ) );
			$constant->set_line( $node->getStartLine() );
			$constants[] = $constant;
		}

		return $constants;
	}

	private function get_name( Node $node ): string|Templated_String {
		return match ( true ) {
			$node instanceof Node\Scalar\String_ => $node->value,
			$node instanceof Node\Scalar\InterpolatedString => trim(
				(string) $this->printer->print_node( $node ),
				'"'
			),
			$node instanceof Node\Expr => $this->printer->print_expr( $node ),
			default => assert( false, new \InvalidArgumentException( 'Unexpected node type: ' . $node::class ) )
		};
	}

	private function get_value( Node $node ): string {
		return match ( true ) {
			$node instanceof Node\Expr => (string) $this->printer->print_expr( $node ),
			default => assert( false, new \InvalidArgumentException( 'Unexpected node type: ' . $node::class ) )
		};
	}

}
