<?php
/**
 * Templated_String_Serializer
 *
 * @package WP_Parser\Serializer
 */

namespace WP_Parser\Serializer;

use PhpParser\Node\Const_;
use PhpParser\Node\Expr\FuncCall;
use WP_Parser\Formatter\Templated_String;
use WP_Parser\Reflection\Name;

/**
 * Serializes Templated_String objects to plain strings by applying the
 * standard name-qualification rule:
 *
 *   - Single-part name (no backslash in name) → rendered without leading backslash
 *   - Namespaced name (contains backslash) → rendered with leading backslash
 */
class Templated_String_Serializer implements Serializer_Interface {

	public function serialize( mixed $value ): string {
		assert( $value instanceof Templated_String );

		return $value->resolve( $this->resolve( ... ) );
	}

	private function resolve( Name $name ): string {
		if ( $name->has_context( FuncCall::class, 'name' ) ) {
			return $name->name;
		}

		return $name->get_fully_qualified_name();
	}

	public function supports( mixed $value ): bool {
		return $value instanceof Templated_String;
	}
}
