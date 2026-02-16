<?php
/**
 * Function_Call_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use WP_Parser\Formatter\Pretty_Printer;
use WP_Parser\Reflection\Function_Call;

/**
 * Class Function_Call_Factory
 */
class Function_Call_Factory {

	private Pretty_Printer $pretty_printer;

	public function __construct() {
		$this->pretty_printer = new Pretty_Printer();
	}

	public function create( FuncCall $node ): Function_Call {
		$function_call = new Function_Call();
		$function_call->set_name( $this->get_name( $node->name ) );
		$function_call->set_line( $node->getStartLine() );
		$function_call->set_end_line( $node->getEndLine() );
		$function_call->set_deprecation_version( $this->get_deprecation_version( $node ) );

		return $function_call;
	}

	private function get_name( Node $node ): string {
		return match ( true ) {
			$node instanceof Name => $node->toString(),
			$node instanceof Expr\Variable => (string) $node->name,
			$node instanceof Expr => $this->pretty_printer->prettyPrintExpr( $node ),
			default => $node->getType() . ' node',
		};
	}

	private function get_deprecation_version( FuncCall $node ): ?string {
		if ( ! $node->name instanceof Name ) {
			return null;
		}

		$is_deprecation_function = match ( $node->name->toString() ) {
			'_deprecated_file',
			'_deprecated_function',
			'_deprecated_argument',
			'_deprecated_hook' => true,
			default => false,
		};

		if ( ! $is_deprecation_function ) {
			return null;
		}

		return $node->args[1]?->value?->value ?? null;
	}

}
