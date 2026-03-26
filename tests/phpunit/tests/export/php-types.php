<?php
/**
 * Tests for PHP type declarations in function arguments and return types.
 */

namespace WP_Parser\Tests;

/**
 * Test that PHP type declarations are exported correctly.
 */
class Export_PHP_Types extends Export_UnitTestCase {

	/**
	 * Test that union argument types are exported correctly.
	 */
	public function test_union_argument_types() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Function with nullable and union argument types.
			 *
			 * @param string|int $id      An ID.
			 * @param string     $label   A label.
			 * @param ?int       $timeout Optional timeout.
			 * @return string|false The result, or false on failure.
			 */
			function test_union_types( string|int $id, string $label, ?int $timeout = null ): string|false {
				return false;
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_union_types' );
		$this->assertIsArray( $func );

		$args = $func['arguments'];
		$this->assertEquals( 'string|int', $args[0]['type'], 'string|int union type should be preserved' );
		$this->assertEquals( 'string',     $args[1]['type'], 'string type should be exported' );
		$this->assertEquals( '?int',       $args[2]['type'], 'nullable ?int type should be exported' );
		$this->assertEquals( 'null',       $args[2]['default'], 'nullable argument with null default' );
	}

	/**
	 * Test that typed function arguments are exported correctly.
	 */
	public function test_typed_arguments() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Function with typed arguments and return type.
			 *
			 * @param int    $count The count.
			 * @param string $name  The name.
			 * @return bool Whether it worked.
			 */
			function test_typed_args( int $count, string $name ): bool {
				return true;
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_typed_args' );
		$this->assertIsArray( $func );

		$args = $func['arguments'];
		$this->assertEquals( 'int',    $args[0]['type'] );
		$this->assertEquals( 'string', $args[1]['type'] );
	}

	/**
	 * Test that typed class properties are exported correctly.
	 */
	public function test_typed_class_properties() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A class with typed properties.
			 */
			class Typed_Props_Class {

				/**
				 * A typed string property.
				 *
				 * @var string
				 */
				public string $title = '';

				/**
				 * A nullable typed property.
				 *
				 * @var int|null
				 */
				public ?int $count = null;
			}
			PHP
		);

		$class = $this->find_entity_data_in( $data, 'classes', 'Typed_Props_Class' );

		$title = $this->find_entity_data_in( $class, 'properties', '$title' );
		$this->assertIsArray( $title );
		$this->assertEquals( "''", $title['default'] );

		$count = $this->find_entity_data_in( $class, 'properties', '$count' );
		$this->assertIsArray( $count );
		$this->assertEquals( 'null', $count['default'] );
	}

	/**
	 * Test that union types in @param tags are exported as arrays of types.
	 */
	public function test_union_type_in_param_tag() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A class with typed properties.
			 */
			class Typed_Props_Class {

				/**
				 * Method with typed arguments and a union return type.
				 *
				 * @param string   $key   The key to look up.
				 * @param int|null $limit Optional limit.
				 * @return array|null The result, or null if not found.
				 */
				public function find( string $key, ?int $limit = null ): array|null {
					return null;
				}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'Typed_Props_Class', 'methods', 'find' );
		$this->assertIsArray( $method );

		$tags = array_values( array_filter( $method['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );

		$this->assertEquals( [ 'string' ],       $tags[0]['types'] );
		$this->assertEquals( [ 'int', 'null' ],  $tags[1]['types'], 'int|null union should split into array of types' );

		$return_tag = array_values( array_filter( $method['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) )[0];
		$this->assertEquals( [ 'array', 'null' ], $return_tag['types'], 'array|null return should split into array of types' );
	}

	/**
	 * Test that nullable type in @param is exported correctly.
	 */
	public function test_nullable_type_in_param_tag() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Function with nullable and union argument types.
			 *
			 * @param string|int $id      An ID.
			 * @param string     $label   A label.
			 * @param ?int       $timeout Optional timeout.
			 * @return string|false The result, or false on failure.
			 */
			function test_union_types( string|int $id, string $label, ?int $timeout = null ): string|false {
				return false;
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_union_types' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );

		$this->assertEquals( [ '?int' ], $tags[2]['types'], '?int nullable type should be preserved in param tag' );
	}

	/**
	 * Test intersection type declarations (PHP 8.1).
	 */
	public function test_intersection_types() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Function with intersection type parameter (PHP 8.1).
			 *
			 * @param Countable&Traversable $collection A countable traversable.
			 */
			function test_intersection_types( Countable&Traversable $collection ): void {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_intersection_types' );
		$this->assertIsArray( $func );
		$type = $func['arguments'][0]['type'];
		$this->assertStringContainsString( 'Countable', $type );
		$this->assertStringContainsString( 'Traversable', $type );
		$this->assertStringContainsString( '&', $type, 'Intersection type should use & separator' );
	}

	/**
	 * Test DNF types (PHP 8.2).
	 */
	public function test_dnf_types() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Function with DNF type parameter (PHP 8.2).
			 *
			 * @param (Countable&Traversable)|null $collection A nullable intersection.
			 */
			function test_dnf_types( (Countable&Traversable)|null $collection ): void {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_dnf_types' );
		$this->assertIsArray( $func );
		$type = $func['arguments'][0]['type'];
		$this->assertNotEmpty( $type );
		$this->assertStringContainsString( 'null', $type );
	}

	/**
	 * Test that functions with return type declarations are parsed.
	 */
	public function test_function_with_return_type() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Function with return type declaration.
			 *
			 * @return string The greeting.
			 */
			function test_return_type(): string {
				return 'hello';
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_return_type' );
		$this->assertIsArray( $func );

		$return_tag = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $return_tag );
		$this->assertEquals( [ 'string' ], $return_tag[0]['types'] );
	}

	/**
	 * Test that void/never return types are parsed as functions.
	 */
	public function test_void_and_never_return() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Function with void return type.
			 */
			function test_void_return(): void {}

			/**
			 * Function with never return type.
			 */
			function test_never_return(): never {
				throw new \RuntimeException();
			}
			PHP
		);

		$void_func = $this->find_entity_data_in( $data, 'functions', 'test_void_return' );
		$this->assertIsArray( $void_func, 'void return function should be parsed' );

		$never_func = $this->find_entity_data_in( $data, 'functions', 'test_never_return' );
		$this->assertIsArray( $never_func, 'never return function should be parsed' );
	}

	/**
	 * Test self/parent/static return types on methods.
	 */
	public function test_method_return_types() {
		$data = $this->parse_string(
			<<<'PHP'
			class Return_Type_Class {
				/**
				 * Method with self return type.
				 */
				public function clone_self(): self {
					return clone $this;
				}

				/**
				 * Method with parent return type.
				 */
				public function get_parent(): parent {
					return parent::getInstance();
				}

				/**
				 * Method with static return type.
				 */
				public function fluent(): static {
					return $this;
				}
			}
			PHP
		);

		$class = $this->find_entity_data_in( $data, 'classes', 'Return_Type_Class' );

		$clone = $this->find_entity_data_in( $class, 'methods', 'clone_self' );
		$this->assertIsArray( $clone, 'clone_self method should exist' );

		$parent = $this->find_entity_data_in( $class, 'methods', 'get_parent' );
		$this->assertIsArray( $parent, 'get_parent method should exist' );

		$fluent = $this->find_entity_data_in( $class, 'methods', 'fluent' );
		$this->assertIsArray( $fluent, 'fluent method should exist' );
	}

	/**
	 * Test readonly properties (PHP 8.1).
	 */
	public function test_readonly_properties() {
		$data = $this->parse_string(
			<<<'PHP'
			class Readonly_Props {
				/**
				 * A readonly property (PHP 8.1).
				 */
				public readonly string $name;

				/**
				 * A readonly typed property with constructor assignment.
				 */
				public readonly int $id;

				public function __construct( string $name, int $id ) {
					$this->name = $name;
					$this->id = $id;
				}
			}
			PHP
		);

		$class = $this->find_entity_data_in( $data, 'classes', 'Readonly_Props' );

		$name = $this->find_entity_data_in( $class, 'properties', '$name' );
		$this->assertIsArray( $name, 'readonly property $name should be exported' );

		$id = $this->find_entity_data_in( $class, 'properties', '$id' );
		$this->assertIsArray( $id, 'readonly property $id should be exported' );
	}

	/**
	 * Test variadic parameters.
	 */
	public function test_variadic_parameters() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Function with variadic parameter.
			 *
			 * @param string ...$args The arguments.
			 */
			function test_variadic( string ...$args ): void {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_variadic' );
		$this->assertIsArray( $func );
		$this->assertCount( 1, $func['arguments'] );
		$this->assertEquals( '$args', $func['arguments'][0]['name'] );
	}

	/**
	 * Test constructor property promotion (PHP 8.0).
	 */
	public function test_constructor_property_promotion() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Constructor property promotion (PHP 8.0).
			 */
			class Promoted_Props {

				public function __construct(
					private string $name,
					protected int $age = 0,
					public readonly string $role = 'user',
				) {}
			}
			PHP
		);

		// The constructor should exist and have parameters.
		$constructor = $this->find_entity_data_in( $data, 'classes', 'Promoted_Props', 'methods', '__construct' );
		$this->assertIsArray( $constructor, '__construct should exist' );
		$this->assertCount( 3, $constructor['arguments'], 'constructor should have 3 arguments' );
	}
}
