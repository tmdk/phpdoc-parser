<?php

namespace WP_Parser\Tests;

use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Callable_;
use phpDocumentor\Reflection\Types\CallableParameter;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Expression;
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
use phpDocumentor\Reflection\Types\This;
use phpDocumentor\Reflection\Types\Void_;
use phpDocumentor\Reflection\PseudoTypes\ArrayKey;
use phpDocumentor\Reflection\PseudoTypes\ArrayShape;
use phpDocumentor\Reflection\PseudoTypes\ArrayShapeItem;
use phpDocumentor\Reflection\PseudoTypes\CallableArray;
use phpDocumentor\Reflection\PseudoTypes\CallableString;
use phpDocumentor\Reflection\PseudoTypes\ClassString;
use phpDocumentor\Reflection\PseudoTypes\ClosedResource;
use phpDocumentor\Reflection\PseudoTypes\Conditional;
use phpDocumentor\Reflection\PseudoTypes\ConditionalForParameter;
use phpDocumentor\Reflection\PseudoTypes\ConstExpression;
use phpDocumentor\Reflection\PseudoTypes\EnumString;
use phpDocumentor\Reflection\PseudoTypes\False_;
use phpDocumentor\Reflection\PseudoTypes\FloatValue;
use phpDocumentor\Reflection\PseudoTypes\Generic;
use phpDocumentor\Reflection\PseudoTypes\HtmlEscapedString;
use phpDocumentor\Reflection\PseudoTypes\IntegerRange;
use phpDocumentor\Reflection\PseudoTypes\IntegerValue;
use phpDocumentor\Reflection\PseudoTypes\InterfaceString;
use phpDocumentor\Reflection\PseudoTypes\IntMask;
use phpDocumentor\Reflection\PseudoTypes\IntMaskOf;
use phpDocumentor\Reflection\PseudoTypes\KeyOf;
use phpDocumentor\Reflection\PseudoTypes\List_;
use phpDocumentor\Reflection\PseudoTypes\ListShape;
use phpDocumentor\Reflection\PseudoTypes\ListShapeItem;
use phpDocumentor\Reflection\PseudoTypes\LiteralString;
use phpDocumentor\Reflection\PseudoTypes\LowercaseString;
use phpDocumentor\Reflection\PseudoTypes\NegativeInteger;
use phpDocumentor\Reflection\PseudoTypes\NeverReturn;
use phpDocumentor\Reflection\PseudoTypes\NeverReturns;
use phpDocumentor\Reflection\PseudoTypes\NonEmptyArray;
use phpDocumentor\Reflection\PseudoTypes\NonEmptyList;
use phpDocumentor\Reflection\PseudoTypes\NonEmptyLowercaseString;
use phpDocumentor\Reflection\PseudoTypes\NonEmptyString;
use phpDocumentor\Reflection\PseudoTypes\NonFalsyString;
use phpDocumentor\Reflection\PseudoTypes\NonNegativeInteger;
use phpDocumentor\Reflection\PseudoTypes\NonPositiveInteger;
use phpDocumentor\Reflection\PseudoTypes\NonZeroInteger;
use phpDocumentor\Reflection\PseudoTypes\NoReturn;
use phpDocumentor\Reflection\PseudoTypes\Numeric_;
use phpDocumentor\Reflection\PseudoTypes\NumericString;
use phpDocumentor\Reflection\PseudoTypes\ObjectShape;
use phpDocumentor\Reflection\PseudoTypes\ObjectShapeItem;
use phpDocumentor\Reflection\PseudoTypes\OffsetAccess;
use phpDocumentor\Reflection\PseudoTypes\OpenResource;
use phpDocumentor\Reflection\PseudoTypes\PositiveInteger;
use phpDocumentor\Reflection\PseudoTypes\PrivatePropertiesOf;
use phpDocumentor\Reflection\PseudoTypes\PropertiesOf;
use phpDocumentor\Reflection\PseudoTypes\ProtectedPropertiesOf;
use phpDocumentor\Reflection\PseudoTypes\PublicPropertiesOf;
use phpDocumentor\Reflection\PseudoTypes\Scalar;
use phpDocumentor\Reflection\PseudoTypes\StringValue;
use phpDocumentor\Reflection\PseudoTypes\TraitString;
use phpDocumentor\Reflection\PseudoTypes\True_;
use phpDocumentor\Reflection\PseudoTypes\TruthyString;
use phpDocumentor\Reflection\PseudoTypes\ValueOf;
use PHPUnit\Framework\TestCase;
use WP_Parser\Formatter\Type_Pretty_Printer;

class Type_Pretty_Printer_Test extends TestCase {

	private Type_Pretty_Printer $printer;

	protected function setUp(): void {
		$this->printer = new Type_Pretty_Printer();
	}

	/**
	 * @dataProvider data_types
	 */
	public function test_matches_to_string( Type $type ): void {
		$this->assertSame( (string) $type, $this->printer->print_type( $type ) );
	}

	public function data_types(): \Generator {
		$wp_post = new Fqsen( '\WP_Post' );
		$class_a = new Fqsen( '\A' );
		$class_b = new Fqsen( '\B' );

		// Primitive types
		yield 'bool'     => [ new Boolean() ];
		yield 'int'      => [ new Integer() ];
		yield 'float'    => [ new Float_() ];
		yield 'string'   => [ new String_() ];
		yield 'void'     => [ new Void_() ];
		yield 'null'     => [ new Null_() ];
		yield 'resource' => [ new Resource_() ];
		yield 'mixed'    => [ new Mixed_() ];
		yield 'self'     => [ new Self_() ];
		yield 'static'   => [ new Static_() ];
		yield 'parent'   => [ new Parent_() ];
		yield '$this'    => [ new This() ];
		yield 'never'    => [ new Never_() ];

		// Simple PseudoType string constants
		yield 'array-key'                  => [ new ArrayKey() ];
		yield 'callable-array'             => [ new CallableArray() ];
		yield 'callable-string'            => [ new CallableString() ];
		yield 'closed-resource'            => [ new ClosedResource() ];
		yield 'false'                      => [ new False_() ];
		yield 'html-escaped-string'        => [ new HtmlEscapedString() ];
		yield 'literal-string'             => [ new LiteralString() ];
		yield 'lowercase-string'           => [ new LowercaseString() ];
		yield 'negative-int'               => [ new NegativeInteger() ];
		yield 'never-return'               => [ new NeverReturn() ];
		yield 'never-returns'              => [ new NeverReturns() ];
		yield 'non-empty-lowercase-string' => [ new NonEmptyLowercaseString() ];
		yield 'non-empty-string'           => [ new NonEmptyString() ];
		yield 'non-falsy-string'           => [ new NonFalsyString() ];
		yield 'non-negative-int'           => [ new NonNegativeInteger() ];
		yield 'non-positive-int'           => [ new NonPositiveInteger() ];
		yield 'non-zero-int'               => [ new NonZeroInteger() ];
		yield 'no-return'                  => [ new NoReturn() ];
		yield 'numeric'                    => [ new Numeric_() ];
		yield 'numeric-string'             => [ new NumericString() ];
		yield 'open-resource'              => [ new OpenResource() ];
		yield 'positive-int'               => [ new PositiveInteger() ];
		yield 'scalar'                     => [ new Scalar() ];
		yield 'true'                       => [ new True_() ];
		yield 'truthy-string'              => [ new TruthyString() ];

		// Literal value types
		yield 'int<0, 100>'   => [ new IntegerRange( '0', '100' ) ];
		yield 'int<min, max>' => [ new IntegerRange( 'min', 'max' ) ];
		yield 'float value'   => [ new FloatValue( 3.14 ) ];
		yield 'int value'     => [ new IntegerValue( 42 ) ];
		yield 'string value'  => [ new StringValue( 'hello' ) ];

		// Object_
		yield 'object (untyped)'          => [ new Object_() ];
		yield 'object (simple class)'     => [ new Object_( $wp_post ) ];
		yield 'object (namespaced class)' => [ new Object_( new Fqsen( '\My\Namespaced\Class_' ) ) ];

		// Generic
		yield 'generic single param'    => [ new Generic( $wp_post, [ new Integer() ] ) ];
		yield 'generic multiple params' => [ new Generic( $wp_post, [ new String_(), new Integer() ] ) ];
		yield 'generic without fqsen'   => [ new Generic( null, [ new Integer() ] ) ];

		// ObjectShape
		yield 'object{} empty'    => [ new ObjectShape() ];
		yield 'object{prop: T}'   => [ new ObjectShape( new ObjectShapeItem( 'prop', new String_(), false ) ) ];
		yield 'object{prop?: T}'  => [ new ObjectShape( new ObjectShapeItem( 'prop', new String_(), true ) ) ];

		// Compound / Intersection / Nullable
		yield 'int|string'       => [ new Compound( [ new Integer(), new String_() ] ) ];
		yield 'int|string|null'  => [ new Compound( [ new Integer(), new String_(), new Null_() ] ) ];
		yield '\A&\B'            => [ new Intersection( [ new Object_( $class_a ), new Object_( $class_b ) ] ) ];
		yield '?int'             => [ new Nullable( new Integer() ) ];
		yield '?object'          => [ new Nullable( new Object_( $wp_post ) ) ];

		// Array_
		yield 'array (untyped)'       => [ new Array_() ];
		yield 'int[]'                 => [ new Array_( new Integer() ) ];
		yield '\WP_Post[]'            => [ new Array_( new Object_( $wp_post ) ) ];
		yield 'int[][] (nested)'      => [ new Array_( new Array_( new Integer() ) ) ];
		yield 'array<int|string>'     => [ new Array_( new Compound( [ new Integer(), new String_() ] ) ) ];
		yield 'array<string, int>'    => [ new Array_( new Integer(), new String_() ) ];

		// List_
		yield 'list (untyped)' => [ new List_() ];
		yield 'list<int>'      => [ new List_( new Integer() ) ];

		// NonEmptyArray
		yield 'non-empty-array (untyped)'    => [ new NonEmptyArray() ];
		yield 'non-empty-array<int>'         => [ new NonEmptyArray( new Integer() ) ];
		yield 'non-empty-array<string, int>' => [ new NonEmptyArray( new Integer(), new String_() ) ];

		// NonEmptyList
		yield 'non-empty-list (untyped)' => [ new NonEmptyList() ];
		yield 'non-empty-list<int>'      => [ new NonEmptyList( new Integer() ) ];

		// Iterable_
		yield 'iterable (untyped)'    => [ new Iterable_() ];
		yield 'iterable<int>'         => [ new Iterable_( new Integer() ) ];
		yield 'iterable<string, int>' => [ new Iterable_( new Integer(), new String_() ) ];

		// ArrayShape
		yield 'array{} empty'             => [ new ArrayShape() ];
		yield 'array{key: T}'             => [ new ArrayShape( new ArrayShapeItem( 'key', new String_(), false ) ) ];
		yield 'array{key?: T}'            => [ new ArrayShape( new ArrayShapeItem( 'key', new String_(), true ) ) ];
		yield 'array{T} no key'           => [ new ArrayShape( new ArrayShapeItem( null, new String_(), false ) ) ];
		yield 'array{key: T, idx: T}'     => [ new ArrayShape(
			new ArrayShapeItem( 'key', new String_(), false ),
			new ArrayShapeItem( 'idx', new Integer(), false ),
		) ];

		// ListShape
		yield 'list{} empty'   => [ new ListShape() ];
		yield 'list{idx: T}'   => [ new ListShape( new ListShapeItem( 'idx', new Integer(), false ) ) ];

		// PropertiesOf variants
		yield 'properties-of'           => [ new PropertiesOf( new Object_( $wp_post ) ) ];
		yield 'private-properties-of'   => [ new PrivatePropertiesOf( new Object_( $wp_post ) ) ];
		yield 'protected-properties-of' => [ new ProtectedPropertiesOf( new Object_( $wp_post ) ) ];
		yield 'public-properties-of'    => [ new PublicPropertiesOf( new Object_( $wp_post ) ) ];

		// String_ pseudo types with optional generic parameter
		yield 'class-string (bare)'     => [ new ClassString() ];
		yield 'class-string<T>'         => [ new ClassString( new Object_( $wp_post ) ) ];
		yield 'enum-string (bare)'      => [ new EnumString() ];
		yield 'enum-string<T>'          => [ new EnumString( new Object_( $wp_post ) ) ];
		yield 'interface-string (bare)' => [ new InterfaceString() ];
		yield 'interface-string<T>'     => [ new InterfaceString( new Object_( $wp_post ) ) ];
		yield 'trait-string (bare)'     => [ new TraitString() ];
		yield 'trait-string<T>'         => [ new TraitString( new Object_( $wp_post ) ) ];

		// Callable_
		yield 'callable (bare)'             => [ new Callable_() ];
		yield 'Closure (bare)'              => [ new Callable_( 'Closure' ) ];
		yield 'callable(): string'          => [ new Callable_( 'callable', [], new String_() ) ];
		yield 'callable(int): string'       => [ new Callable_( 'callable', [ new CallableParameter( new Integer() ) ], new String_() ) ];
		yield 'callable(int $x): string'    => [ new Callable_( 'callable', [ new CallableParameter( new Integer(), 'x' ) ], new String_() ) ];
		yield 'callable(int &$x): string'   => [ new Callable_( 'callable', [ new CallableParameter( new Integer(), 'x', true ) ], new String_() ) ];
		yield 'callable(int ...$x): string' => [ new Callable_( 'callable', [ new CallableParameter( new Integer(), 'x', false, true ) ], new String_() ) ];
		yield 'callable(int $x=): string'   => [ new Callable_( 'callable', [ new CallableParameter( new Integer(), 'x', false, false, true ) ], new String_() ) ];
		yield 'callable(int): (callable)'   => [ new Callable_( 'callable', [ new CallableParameter( new Integer() ) ], new Callable_() ) ];

		// Expression
		yield 'expression (int|string)' => [ new Expression( new Compound( [ new Integer(), new String_() ] ) ) ];

		// KeyOf, ValueOf, IntMask, IntMaskOf
		yield 'key-of<T>'        => [ new KeyOf( new Object_( $wp_post ) ) ];
		yield 'value-of<T>'      => [ new ValueOf( new Object_( $wp_post ) ) ];
		yield 'int-mask<1, 2>'   => [ new IntMask( new IntegerValue( 1 ), new IntegerValue( 2 ) ) ];
		yield 'int-mask-of<T>'   => [ new IntMaskOf( new Object_( $wp_post ) ) ];

		// OffsetAccess
		yield 'T[int]'             => [ new OffsetAccess( new Object_( $wp_post ), new Integer() ) ];
		yield '(?T)[string]'       => [ new OffsetAccess( new Nullable( new Integer() ), new String_() ) ];
		yield '(callable)[string]' => [ new OffsetAccess( new Callable_(), new String_() ) ];
		yield '(T::CONST)[int]'    => [ new OffsetAccess( new ConstExpression( new Object_( $wp_post ), 'CONST' ), new Integer() ) ];

		// ConstExpression
		yield 'T::CONST' => [ new ConstExpression( new Object_( $wp_post ), 'CONST' ) ];

		// Conditional
		yield 'T is U ? V : W'       => [ new Conditional( false, new Object_( $class_a ), new Object_( $class_b ), new Integer(), new String_() ) ];
		yield 'T is not U ? V : W'   => [ new Conditional( true, new Object_( $class_a ), new Object_( $class_b ), new Integer(), new String_() ) ];
		yield '$p is U ? V : W'      => [ new ConditionalForParameter( false, 'param', new Object_( $class_a ), new Integer(), new String_() ) ];
		yield '$p is not U ? V : W'  => [ new ConditionalForParameter( true, 'param', new Object_( $class_a ), new Integer(), new String_() ) ];
	}
}
