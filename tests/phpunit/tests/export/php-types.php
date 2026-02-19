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

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_union_types' );
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

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_typed_args' );
		$this->assertIsArray( $func );

		$args = $func['arguments'];
		$this->assertEquals( 'int',    $args[0]['type'] );
		$this->assertEquals( 'string', $args[1]['type'] );
	}

	/**
	 * Test that typed class properties are exported correctly.
	 */
	public function test_typed_class_properties() {

		$class = $this->find_entity_data_in( $this->export_data, 'classes', 'Typed_Props_Class' );
		$this->assertIsArray( $class );

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

		$class = $this->find_entity_data_in( $this->export_data, 'classes', 'Typed_Props_Class' );
		$this->assertIsArray( $class );

		$method = $this->find_entity_data_in( $class, 'methods', 'find' );
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

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_union_types' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );

		$this->assertEquals( [ '?int' ], $tags[2]['types'], '?int nullable type should be preserved in param tag' );
	}
}
