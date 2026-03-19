<?php
/**
 * Type_Pretty_Printer
 *
 * @package WP_Parser\Formatter
 */

namespace WP_Parser\Formatter;

use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Callable_;
use phpDocumentor\Reflection\Types\CallableParameter;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Expression;
use phpDocumentor\Reflection\Types\Intersection;
use phpDocumentor\Reflection\Types\Iterable_;
use phpDocumentor\Reflection\Types\Nullable;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\PseudoTypes\ArrayKey;
use phpDocumentor\Reflection\PseudoTypes\ArrayShape;
use phpDocumentor\Reflection\PseudoTypes\CallableArray;
use phpDocumentor\Reflection\PseudoTypes\ClassString;
use phpDocumentor\Reflection\PseudoTypes\Conditional;
use phpDocumentor\Reflection\PseudoTypes\ConditionalForParameter;
use phpDocumentor\Reflection\PseudoTypes\ConstExpression;
use phpDocumentor\Reflection\PseudoTypes\EnumString;
use phpDocumentor\Reflection\PseudoTypes\Generic;
use phpDocumentor\Reflection\PseudoTypes\InterfaceString;
use phpDocumentor\Reflection\PseudoTypes\IntMask;
use phpDocumentor\Reflection\PseudoTypes\IntMaskOf;
use phpDocumentor\Reflection\PseudoTypes\KeyOf;
use phpDocumentor\Reflection\PseudoTypes\List_;
use phpDocumentor\Reflection\PseudoTypes\ListShape;
use phpDocumentor\Reflection\PseudoTypes\NonEmptyArray;
use phpDocumentor\Reflection\PseudoTypes\NonEmptyList;
use phpDocumentor\Reflection\PseudoTypes\ObjectShape;
use phpDocumentor\Reflection\PseudoTypes\OffsetAccess;
use phpDocumentor\Reflection\PseudoTypes\PrivatePropertiesOf;
use phpDocumentor\Reflection\PseudoTypes\PropertiesOf;
use phpDocumentor\Reflection\PseudoTypes\ProtectedPropertiesOf;
use phpDocumentor\Reflection\PseudoTypes\PublicPropertiesOf;
use phpDocumentor\Reflection\PseudoTypes\ShapeItem;
use phpDocumentor\Reflection\PseudoTypes\TraitString;
use phpDocumentor\Reflection\PseudoTypes\ValueOf;

use function array_map;
use function implode;
use function iterator_to_array;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function substr;
use function trim;

/**
 * Class Type_Pretty_Printer
 *
 * Formats Type objects to strings with configurable class name formatting.
 * Analogous to Pretty_Printer for php-parser nodes.
 */
class Type_Pretty_Printer {

	public function __construct( protected bool $use_fully_qualified_names = true ) {}

	/**
	 * Format a Fqsen (class/interface/etc. name reference).
	 * Override in a subclass to customize name formatting.
	 */
	public function print_fqsen( Fqsen $fqsen ): string {
		if ( $this->use_fully_qualified_names ) {
			return (string) $fqsen;
		}

		$fqsen_string = (string) $fqsen;

		// Strip leading \ only for global-namespace functions (ends with (), no ::, no \ after leading \).
		if ( str_ends_with( $fqsen_string, '()' )
			&& ! str_contains( $fqsen_string, '::' )
			&& ! str_contains( substr( $fqsen_string, 1 ), '\\' )
		) {
			return substr( $fqsen_string, 1 );
		}

		return $fqsen_string;
	}

	/**
	 * Print a Type to its string representation.
	 * Dispatches from most-specific to least-specific type class.
	 */
	public function print_type( Type $type ): string {
		if ( $type instanceof KeyOf ) {
			return 'key-of<' . $this->print_type( $type->getType() ) . '>';
		}

		if ( $type instanceof ArrayKey ) {
			return 'array-key';
		}

		if ( $type instanceof ListShape ) {
			return 'list{' . $this->print_shape_item_list( $type->getItems() ) . '}';
		}

		if ( $type instanceof ArrayShape ) {
			return 'array{' . $this->print_shape_item_list( $type->getItems() ) . '}';
		}

		if ( $type instanceof ObjectShape ) {
			return 'object{' . $this->print_shape_item_list( $type->getItems() ) . '}';
		}

		if ( $type instanceof Generic ) {
			$name = $type->getFqsen() ? $this->print_fqsen( $type->getFqsen() ) : 'object';
			return $name . '<' . $this->print_type_list( $type->getTypes() ) . '>';
		}

		if ( $type instanceof PrivatePropertiesOf ) {
			return 'private-properties-of<' . $this->print_type( $type->getType() ) . '>';
		}

		if ( $type instanceof ProtectedPropertiesOf ) {
			return 'protected-properties-of<' . $this->print_type( $type->getType() ) . '>';
		}

		if ( $type instanceof PublicPropertiesOf ) {
			return 'public-properties-of<' . $this->print_type( $type->getType() ) . '>';
		}

		if ( $type instanceof PropertiesOf ) {
			return 'properties-of<' . $this->print_type( $type->getType() ) . '>';
		}

		if ( $type instanceof NonEmptyList ) {
			$value_type = $type->getOriginalValueType();
			if ( $value_type === null ) {
				return 'non-empty-list';
			}
			return 'non-empty-list<' . $this->print_type( $value_type ) . '>';
		}

		if ( $type instanceof List_ ) {
			$value_type = $type->getOriginalValueType();
			if ( $value_type === null ) {
				return 'list';
			}
			return 'list<' . $this->print_type( $value_type ) . '>';
		}

		if ( $type instanceof NonEmptyArray ) {
			$value_type = $type->getOriginalValueType();
			if ( $value_type === null ) {
				return 'non-empty-array';
			}
			$key_type = $type->getOriginalKeyType();
			if ( $key_type ) {
				return 'non-empty-array<' . $this->print_type( $key_type ) . ', ' . $this->print_type( $value_type ) . '>';
			}
			return 'non-empty-array<' . $this->print_type( $value_type ) . '>';
		}

		if ( $type instanceof CallableArray ) {
			return 'callable-array';
		}

		if ( $type instanceof Array_ ) {
			$value_type = $type->getOriginalValueType();
			if ( $value_type === null ) {
				return 'array';
			}
			$key_type         = $type->getOriginalKeyType();
			$value_type_string = $this->print_type( $value_type );
			if ( $key_type ) {
				return 'array<' . $this->print_type( $key_type ) . ', ' . $value_type_string . '>';
			}
			if ( ! preg_match( '/[^\w\\\\]/', $value_type_string ) || str_ends_with( $value_type_string, '[]' ) ) {
				return $value_type_string . '[]';
			}
			return 'array<' . $value_type_string . '>';
		}

		if ( $type instanceof Iterable_ ) {
			$value_type = $type->getOriginalValueType();
			if ( $value_type === null ) {
				return 'iterable';
			}
			$key_type = $type->getOriginalKeyType();
			if ( $key_type ) {
				return 'iterable<' . $this->print_type( $key_type ) . ', ' . $this->print_type( $value_type ) . '>';
			}
			return 'iterable<' . $this->print_type( $value_type ) . '>';
		}

		if ( $type instanceof Object_ ) {
			$fqsen = $type->getFqsen();
			if ( $fqsen ) {
				return $this->print_fqsen( $fqsen );
			}
			return 'object';
		}

		if ( $type instanceof Compound ) {
			return implode( '|', array_map( [ $this, 'print_type' ], iterator_to_array( $type ) ) );
		}

		if ( $type instanceof Intersection ) {
			return implode( '&', array_map( [ $this, 'print_type' ], iterator_to_array( $type ) ) );
		}

		if ( $type instanceof Nullable ) {
			return '?' . $this->print_type( $type->getActualType() );
		}

		if ( $type instanceof ClassString ) {
			$generic_type = $type->getGenericType();
			if ( $generic_type === null ) {
				return 'class-string';
			}
			return 'class-string<' . $this->print_type( $generic_type ) . '>';
		}

		if ( $type instanceof EnumString ) {
			$generic_type = $type->getGenericType();
			if ( $generic_type === null ) {
				return 'enum-string';
			}
			return 'enum-string<' . $this->print_type( $generic_type ) . '>';
		}

		if ( $type instanceof InterfaceString ) {
			$generic_type = $type->getGenericType();
			if ( $generic_type === null ) {
				return 'interface-string';
			}
			return 'interface-string<' . $this->print_type( $generic_type ) . '>';
		}

		if ( $type instanceof TraitString ) {
			$generic_type = $type->getGenericType();
			if ( $generic_type === null ) {
				return 'trait-string';
			}
			return 'trait-string<' . $this->print_type( $generic_type ) . '>';
		}

		if ( $type instanceof ConstExpression ) {
			return sprintf( '%s::%s', $this->print_type( $type->getOwner() ), $type->getExpression() );
		}

		if ( $type instanceof Conditional ) {
			return sprintf(
				'(%s %s %s ? %s : %s)',
				$this->print_type( $type->getSubjectType() ),
				$type->isNegated() ? 'is not' : 'is',
				$this->print_type( $type->getTargetType() ),
				$this->print_type( $type->getIf() ),
				$this->print_type( $type->getElse() )
			);
		}

		if ( $type instanceof ConditionalForParameter ) {
			return sprintf(
				'(%s %s %s ? %s : %s)',
				'$' . $type->getParameterName(),
				$type->isNegated() ? 'is not' : 'is',
				$this->print_type( $type->getTargetType() ),
				$this->print_type( $type->getIf() ),
				$this->print_type( $type->getElse() )
			);
		}

		if ( $type instanceof ValueOf ) {
			return 'value-of<' . $this->print_type( $type->getType() ) . '>';
		}

		if ( $type instanceof IntMaskOf ) {
			return 'int-mask-of<' . $this->print_type( $type->getType() ) . '>';
		}

		if ( $type instanceof IntMask ) {
			return 'int-mask<' . $this->print_type_list( $type->getTypes() ) . '>';
		}

		if ( $type instanceof OffsetAccess ) {
			$inner = $type->getType();
			if ( $inner instanceof Callable_ || $inner instanceof ConstExpression || $inner instanceof Nullable ) {
				return '(' . $this->print_type( $inner ) . ')[' . $this->print_type( $type->getOffset() ) . ']';
			}
			return $this->print_type( $inner ) . '[' . $this->print_type( $type->getOffset() ) . ']';
		}

		if ( $type instanceof Expression ) {
			return '(' . $this->print_type( $type->getValueType() ) . ')';
		}

		if ( $type instanceof Callable_ ) {
			if ( ! $type->getParameters() && $type->getReturnType() === null ) {
				return $type->getIdentifier();
			}
			$return_type = $type->getReturnType();
			if ( $return_type instanceof Callable_ ) {
				$return_type_str = '(' . $this->print_type( $return_type ) . ')';
			} else {
				$return_type_str = $return_type ? $this->print_type( $return_type ) : '';
			}
			return $type->getIdentifier()
				. '('
				. implode( ', ', array_map( [ $this, 'print_callable_parameter' ], $type->getParameters() ) )
				. '): '
				. $return_type_str;
		}

		return (string) $type;
	}

	/**
	 * Print a shape item (for array{}, list{}, object{} types).
	 */
	protected function print_shape_item( ShapeItem $item ): string {
		$key = $item->getKey();
		if ( $key !== null && $key !== '' ) {
			return sprintf(
				'%s%s: %s',
				$key,
				$item->isOptional() ? '?' : '',
				$this->print_type( $item->getValue() )
			);
		}
		return $this->print_type( $item->getValue() );
	}

	/**
	 * Print a comma-separated list of shape items.
	 *
	 * @param ShapeItem[] $items
	 */
	protected function print_shape_item_list( array $items ): string {
		return implode( ', ', array_map( [ $this, 'print_shape_item' ], $items ) );
	}

	/**
	 * Print a comma-separated list of types.
	 *
	 * @param Type[] $types
	 */
	protected function print_type_list( array $types ): string {
		return implode( ', ', array_map( [ $this, 'print_type' ], $types ) );
	}

	/**
	 * Print a callable parameter.
	 */
	protected function print_callable_parameter( CallableParameter $param ): string {
		$reference = $param->isReference() ? '&' : '';
		$variadic  = $param->isVariadic() ? '...' : '';
		$optional  = $param->isOptional() ? '=' : '';
		$name      = $param->getName() !== null ? '$' . $param->getName() : '';

		return trim( $this->print_type( $param->getType() ) . ' ' . $reference . $variadic . $name . $optional );
	}
}
