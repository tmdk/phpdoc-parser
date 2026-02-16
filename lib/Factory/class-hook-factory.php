<?php
/**
 * Hook_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use PhpParser\Node\Expr\FuncCall;
use WP_Parser\Formatter\Pretty_Printer;
use WP_Parser\Reflection\DocBlock;
use WP_Parser\Reflection\Hook;

/**
 * Factory for creating Hook reflection objects.
 */
class Hook_Factory {

	private Pretty_Printer $pretty_printer;

	public function __construct() {
		$this->pretty_printer = new Pretty_Printer();
	}

	/**
	 * Create a Hook from a FuncCall node.
	 *
	 * @param FuncCall      $node
	 * @param DocBlock|null $doc_block
	 *
	 * @return Hook
	 */
	public function create( FuncCall $node, ?DocBlock $doc_block = null ): Hook {
		$hook = new Hook();

		$hook_name = $this->extract_hook_name( $node );
		$hook->set_name( $hook_name );

		$hook_type = $this->determine_hook_type( $node );
		$hook->set_type( $hook_type );

		$hook->set_line( $node->getStartLine() );
		$hook->set_end_line( $node->getEndLine() );

		$hook->set_arguments( $this->extract_arguments( $node ) );

		if ( $doc_block ) {
			$hook->set_doc_block( $doc_block );
		}

		return $hook;
	}

	/**
	 * Extract the hook name from the first argument.
	 *
	 * @param FuncCall $node
	 *
	 * @return string
	 */
	private function extract_hook_name( FuncCall $node ): string {
		if ( empty( $node->args ) ) {
			return '';
		}

		$first_arg = $node->args[0];
		$name      = $this->pretty_printer->prettyPrintExpr( $first_arg->value );

		return $this->normalize_hook_name( $name );
	}

	/**
	 * Normalize the hook name to handle quotes, concatenation, and variables.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	private function normalize_hook_name( string $name ): string {
		$matches = [];

		// quotes on both ends of a string
		if ( preg_match( '/^[\'"]([^\'"]*)[\'"]$/', $name, $matches ) ) {
			return $matches[1];
		}

		// two concatenated things, last one of them a variable
		if ( preg_match(
			'/(?:[\'"]([^\'"]*)[\'"]\s*\.\s*)?' . // First filter name string (optional)
			'(\$\S*)' .                           // Dynamic variable
			'(?:\s*\.\s*[\'"]([^\'"]*)[\'"])?/',  // Second filter name string (optional)
			$name,
			$matches
		) ) {

			if ( isset( $matches[3] ) ) {
				return $matches[1] . '{' . $matches[2] . '}' . $matches[3];
			} else {
				return $matches[1] . '{' . $matches[2] . '}';
			}
		}

		return $name;
	}

	/**
	 * Determine the hook type from the function name.
	 *
	 * @param FuncCall $node
	 *
	 * @return string
	 */
	private function determine_hook_type( FuncCall $node ): string {
		$function_name = $node->name->toString();

		return match ( $function_name ) {
			'do_action' => 'action',
			'do_action_ref_array' => 'action_reference',
			'do_action_deprecated' => 'action_deprecated',
			'apply_filters_ref_array' => 'filter_reference',
			'apply_filters_deprecated' => 'filter_deprecated',
			default => 'filter',
		};
	}

	/**
	 * Extract arguments from the function call.
	 *
	 * @param FuncCall $node
	 *
	 * @return array
	 */
	private function extract_arguments( FuncCall $node ): array {
		$arguments = [];

		foreach ( array_slice( $node->args, 1 ) as $arg ) {
			$arguments[] = $this->pretty_printer->prettyPrintExpr( $arg->value );
		}

		return $arguments;
	}
}
