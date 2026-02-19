<?php
/**
 * Tests for type handling in docblock tags.
 */

namespace WP_Parser\Tests;

/**
 * Test that types in docblock tags are exported correctly.
 */
class Export_PHPDoc_Types extends Export_UnitTestCase {

	/**
	 * Test that type aliases are normalized in param and return tags.
	 *
	 * phpDocumentor normalizes: integer->int, boolean->bool, double->float.
	 */
	public function test_type_alias_normalization() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_type_aliases' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );

		$this->assertEquals( [ 'int' ],   $tags[0]['types'], 'integer should normalize to int' );
		$this->assertEquals( [ 'bool' ],  $tags[1]['types'], 'boolean should normalize to bool' );
		$this->assertEquals( [ 'float' ], $tags[2]['types'], 'double should normalize to float' );

		$return_tag = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) )[0];
		$this->assertEquals( [ 'bool' ], $return_tag['types'], 'boolean return should normalize to bool' );
	}

	/**
	 * Test that capitalized builtin PHP types are normalized to lowercase.
	 *
	 * phpDocumentor normalizes: String->string, Array->array, etc.
	 */
	public function test_capitalized_type_normalization() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_capitalized_types' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );

		$this->assertEquals( [ 'string' ], $tags[0]['types'], 'String should normalize to string' );
		$this->assertEquals( [ 'array' ],  $tags[1]['types'], 'Array should normalize to array' );

		$return_tag = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) )[0];
		$this->assertEquals( [ 'string' ], $return_tag['types'], 'String return should normalize to string' );
	}

	/**
	 * Test that generic array syntax is normalized to shorthand.
	 *
	 * phpDocumentor normalizes: array<Type> -> Type[].
	 */
	public function test_generic_array_shorthand_normalization() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_generic_array' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );

		$this->assertEquals( [ 'int[]' ],   $tags[0]['types'], 'array<int> should normalize to int[]' );
		$this->assertEquals( [ 'mixed[]' ], $tags[1]['types'], 'array<mixed> should normalize to mixed[]' );

		$return_tag = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) )[0];
		$this->assertEquals( [ 'string[]' ], $return_tag['types'], 'array<string> should normalize to string[]' );
	}

	/**
	 * Test that a param tag with no type defaults to mixed.
	 */
	public function test_untyped_param_defaults_to_mixed() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_untyped_param' );
		$this->assertIsArray( $func );

		$param_tag = $func['doc']['tags'][0];
		$this->assertEquals( [ 'mixed' ], $param_tag['types'], 'untyped @param should default to mixed' );
		$this->assertEquals( '$value',    $param_tag['variable'] );
	}

	/**
	 * Test that pass-by-reference param variable names are extracted correctly.
	 *
	 * The new parser correctly strips the & prefix from &$var.
	 */
	public function test_pass_by_reference_variable_extraction() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_ref_param' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );

		$this->assertEquals( '$regular', $tags[0]['variable'], 'regular param variable should be $regular' );
		$this->assertEquals( '$name',    $tags[1]['variable'], 'pass-by-ref param variable should be $name, not &$name' );
	}

	/**
	 * Test that single-quoted string literal types are normalized to double-quoted.
	 *
	 * phpDocumentor normalizes: 'value' -> "value" in type strings.
	 */
	public function test_string_literal_quote_normalization() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_string_literal_types' );
		$this->assertIsArray( $func );

		$param_tag = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) )[0];
		$this->assertEquals( [ '"yes"', '"no"' ], $param_tag['types'], 'single-quoted string literals should normalize to double-quoted' );

		$return_tag = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) )[0];
		$this->assertEquals( [ '"success"', '"failure"' ], $return_tag['types'], 'single-quoted return type literals should normalize to double-quoted' );
	}
}
