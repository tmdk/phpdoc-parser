<?php
/**
 * Type_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\PseudoTypes\False_;
use phpDocumentor\Reflection\PseudoTypes\True_;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Callable_;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Float_;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\Intersection;
use phpDocumentor\Reflection\Types\Iterable_;
use phpDocumentor\Reflection\Types\Mixed_;
use phpDocumentor\Reflection\Types\Never_;
use phpDocumentor\Reflection\Types\Null_;
use phpDocumentor\Reflection\Types\Nullable;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\Parent_;
use phpDocumentor\Reflection\Types\Resource_;
use phpDocumentor\Reflection\Types\Self_;
use phpDocumentor\Reflection\Types\Static_;
use phpDocumentor\Reflection\Types\String_;
use phpDocumentor\Reflection\Types\Void_;
use PhpParser\Node;

/**
 * Class Type_Factory
 *
 * Converts php-parser type nodes to phpDocumentor Type objects.
 */
class Type_Factory {

	/**
	 * Create a phpDocumentor Type from various input formats.
	 *
	 * @param mixed $value A php-parser Node or other type representation.
	 * @return Type|null The created Type or null if the value cannot be converted.
	 */
	public function create( mixed $value ): ?Type {
		if ( $value instanceof Node ) {
			return $this->from_phpparser_type( $value );
		}

		return null;
	}

	/**
	 * Convert a php-parser type node to a phpDocumentor Type.
	 *
	 * @param Node $node A php-parser type node.
	 * @return Type|null The converted Type or null if conversion fails.
	 */
	private function from_phpparser_type( Node $node ): ?Type {
		// Handle Identifier (scalar types, keywords)
		if ( $node instanceof Node\Identifier ) {
			return $this->identifier_to_type( $node->name );
		}

		// Handle Name (class/interface names)
		if ( $node instanceof Node\Name ) {
			return $this->name_to_object( $node );
		}

		// Handle NullableType (?type)
		if ( $node instanceof Node\NullableType ) {
			$inner_type = $this->from_phpparser_type( $node->type );
			if ( $inner_type !== null ) {
				return new Nullable( $inner_type );
			}
			return null;
		}

		// Handle UnionType (type1|type2|...)
		if ( $node instanceof Node\UnionType ) {
			$types = [];
			foreach ( $node->types as $type_node ) {
				$type = $this->from_phpparser_type( $type_node );
				if ( $type !== null ) {
					$types[] = $type;
				}
			}
			return count( $types ) > 0 ? new Compound( $types ) : null;
		}

		// Handle IntersectionType (type1&type2&...)
		if ( $node instanceof Node\IntersectionType ) {
			$types = [];
			foreach ( $node->types as $type_node ) {
				$type = $this->from_phpparser_type( $type_node );
				if ( $type !== null ) {
					$types[] = $type;
				}
			}
			return count( $types ) > 0 ? new Intersection( $types ) : null;
		}

		return null;
	}

	/**
	 * Convert an identifier string to a phpDocumentor Type.
	 *
	 * Maps PHP scalar types and keywords to their phpDocumentor equivalents.
	 *
	 * @param string $name The identifier name (e.g., 'int', 'string', 'bool').
	 * @return Type|null The mapped Type or null if not recognized.
	 */
	private function identifier_to_type( string $name ): ?Type {
		return match ( strtolower( $name ) ) {
			'bool' => new Boolean(),
			'int' => new Integer(),
			'float' => new Float_(),
			'string' => new String_(),
			'array' => new Array_(),
			'object' => new Object_(),
			'mixed' => new Mixed_(),
			'void' => new Void_(),
			'never' => new Never_(),
			'null' => new Null_(),
			'self' => new Self_(),
			'parent' => new Parent_(),
			'static' => new Static_(),
			'callable' => new Callable_(),
			'iterable' => new Iterable_(),
			'resource' => new Resource_(),
			'true' => new True_(),
			'false' => new False_(),
			default => null,
		};
	}

	/**
	 * Convert a php-parser Name node to a phpDocumentor Object_ type.
	 *
	 * @param Node\Name $name The Name node representing a class/interface.
	 * @return Object_ The Object_ type with an Fqsen.
	 */
	private function name_to_object( Node\Name $name ): Object_ {
		// Convert the name to a fully qualified string with leading backslash
		$fqsen_string = '\\' . $name->toString();

		try {
			$fqsen = new Fqsen( $fqsen_string );
			return new Object_( $fqsen );
		} catch ( \InvalidArgumentException ) {
			// If Fqsen creation fails, return untyped object
			return new Object_();
		}
	}

}
