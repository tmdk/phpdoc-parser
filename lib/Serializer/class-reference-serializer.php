<?php
/**
 * Reference_Serializer
 *
 * @package WP_Parser\Serializer
 */

namespace WP_Parser\Serializer;

use PhpParser\Node\Name;
use WP_Parser\Reference\Chained_Method_Reference;
use WP_Parser\Reference\Class_Const_Reference;
use WP_Parser\Reference\Const_Reference;
use WP_Parser\Reference\Function_Reference;
use WP_Parser\Reference\Method_Reference;
use WP_Parser\Reference\Property_Reference;
use WP_Parser\Reference\Quoted_Reference;
use WP_Parser\Reference\Raw_Reference;
use WP_Parser\Reference\Reference;
use WP_Parser\Reference\Url_Reference;

/**
 * Serializes Reference objects to strings, applying the same name-qualification
 * rules as Templated_String_Serializer:
 *
 *   - Name\FullyQualified  → \Name  (always prefixed, regardless of function/class)
 *   - Name\Relative        → namespace\Name
 *   - Name (unqualified)   → Name   (as written, no leading \)
 */
class Reference_Serializer implements Serializer_Interface {

	public function serialize( mixed $value ): string {
		assert( $value instanceof Reference );

		return self::format( $value );
	}

	public static function format( Reference $value ): string {
		return match ( true ) {
			$value instanceof Url_Reference            => $value->url,
			$value instanceof Quoted_Reference         => $value->value,
			$value instanceof Raw_Reference            => $value->value,
			$value instanceof Function_Reference       => self::format_name( $value->name ) . '()',
			$value instanceof Chained_Method_Reference => self::format_name( $value->function ) . '()->' . $value->method->name . '()',
			$value instanceof Const_Reference          => self::format_name( $value->name ),
			$value instanceof Class_Const_Reference    => self::format_name( $value->class ) . '::' . $value->const->name,
			$value instanceof Method_Reference         => self::format_name( $value->class ) . '::' . $value->method->name . '()',
			$value instanceof Property_Reference       => self::format_name( $value->class ) . '::$' . $value->property->name,
			default                                    => '',
		};
	}

	public static function format_name( Name $name ): string {
		if ( $name instanceof Name\FullyQualified ) {
			return '\\' . $name->name;
		}

		if ( $name instanceof Name\Relative ) {
			return 'namespace\\' . $name->name;
		}

		return $name->name;
	}

	public function supports( mixed $value ): bool {
		return $value instanceof Reference;
	}
}
