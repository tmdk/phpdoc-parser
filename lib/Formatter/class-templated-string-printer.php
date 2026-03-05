<?php
/**
 * Templated_String_Printer
 *
 * @package WP_Parser\Formatter
 */

namespace WP_Parser\Formatter;

use PhpParser\Node;
use WP_Parser\Reflection\Name;

/**
 * A Pretty_Printer subclass that produces Templated_String objects instead of
 * plain strings. Fully-qualified names are replaced with {n} placeholders and
 * recorded as Name_Reference objects, so the serializer can decide how to
 * render them.
 */
class Templated_String_Printer extends Pretty_Printer {

	private const SPECIAL_CONST_NAMES = [
		'true',
		'false',
		'null',
	];

	/** @var Name[] */
	private array $names = [];

	/**
	 * Pretty-print an expression node, returning a Templated_String.
	 */
	public function print_expr( Node\Expr $expr ): Templated_String {
		$this->names = [];
		$template    = $this->prettyPrintExpr( $expr );

		return new Templated_String( $template, $this->names );
	}

	/**
	 * Pretty-print any node (expression, type node, name, etc.), returning a Templated_String.
	 *
	 * Use this in place of prettyPrint([$node]) or prettyPrintExpr($node).
	 */
	public function print_node( Node $node ): Templated_String {
		$this->names = [];

		if ( $node instanceof Node\Expr ) {
			$template = $this->prettyPrintExpr( $node );
		} else {
			$template = $this->prettyPrint( [ $node ] );
		}

		return new Templated_String( $template, $this->names );
	}

	/**
	 * Pretty-print a Name node, returning a Templated_String.
	 */
	public function print_name( Node\Name $name ): Templated_String {
		$this->names = [];

		if ( $name instanceof Node\Name\FullyQualified ) {
			$template = $this->pName_FullyQualified( $name );
		} else {
			$template = $this->p( $name );
		}

		return new Templated_String( $template, $this->names );
	}

	protected function pName_FullyQualified( Node\Name\FullyQualified $node ): string {
		if ( in_array( $node->name, self::SPECIAL_CONST_NAMES, true ) ) {
			return $node->name;
		}

		$name = Name::from(
			$node,
			context: array_filter(
				[
					$node->getAttribute( 'nameContextNodeClass' ),
					$node->getAttribute( 'nameContextProperty' ),
				],
				fn( $value ) => $value !== null
			)
		);

		$placeholder = Templated_String::placeholder( $name );

		$this->names[ $placeholder ] = $name;

		return $placeholder;
	}
}
