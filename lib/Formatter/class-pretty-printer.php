<?php
/**
 * Pretty_Printer
 *
 * @package WP_Parser\Formatter
 */

namespace WP_Parser\Formatter;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar;
use PhpParser\PrettyPrinter\Standard;

/**
 * Class Pretty_Printer
 */
class Pretty_Printer extends Standard {

	private const SPECIAL_CONST_NAMES = [
		'true',
		'false',
		'null',
	];

	public function __construct() {
		parent::__construct( [ 'shortArraySyntax' => false ] );
	}

	protected function pName_FullyQualified( Name\FullyQualified $node ): string {
		if ( $this->is_special_const_name( $node->name ) ) {
			return $node->name;
		}

		$context_class    = $node->getAttribute( 'nameContextNodeClass' );
		$context_property = $node->getAttribute( 'nameContextProperty' );

		if ( ! $context_class || $this->has_namespace( $node ) ) {
			return parent::pName_FullyQualified( $node );
		}

		if ( $context_class !== Expr\StaticCall::class
			&& ( $context_class !== Param::class || $context_property !== 'type' )
		) {
			return $node->name;
		}

		return parent::pName_FullyQualified( $node );
	}

	protected function pScalar_String( Scalar\String_ $node ): string {
		$kind = $node->getAttribute( 'kind', Scalar\String_::KIND_SINGLE_QUOTED );

		if ( $kind !== Scalar\String_::KIND_DOUBLE_QUOTED
			&& $kind !== Scalar\String_::KIND_SINGLE_QUOTED ) {
			return parent::pScalar_String( $node );
		}

		$raw_value = $node->getAttribute( 'rawValue' );
		if ( ! $raw_value ) {
			return parent::pScalar_String( $node );
		}

		return $raw_value;
	}

	protected function pExpr_Array( Expr\Array_ $node ): string {
		return 'array(' . $this->pCommaSeparated( $node->items ) . ')';
	}

	protected function pComments( array $comments ): string {
		return '';
	}

	private function has_namespace( Name\FullyQualified $node ): bool {
		return str_contains( $node->toString(), '\\' );
	}

	private function is_special_const_name( string $name ): bool {
		return in_array( $name, self::SPECIAL_CONST_NAMES, true );
	}

}
