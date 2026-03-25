<?php

/**
 * A test case for hook exporting.
 */

namespace WP_Parser\Tests;

/**
 * Test that hooks are exported correctly.
 */
class Export_Hooks extends Export_UnitTestCase {

	/**
	 * Test that hook names are standardized on export.
	 */
	public function test_hook_names_standardized() {

		$this->assertFileContainsHook(
			array( 'name' => 'plain_action', 'line' => 3 )
		);

		$this->assertFileContainsHook(
			array( 'name' => 'action_with_double_quotes', 'line' => 4 )
		);

		$this->assertFileContainsHook(
			array( 'name' => '{$variable}-action', 'line' => 5 )
		);

		$this->assertFileContainsHook(
			array( 'name' => 'another-{$variable}-action', 'line' => 6 )
		);

		$this->assertFileContainsHook(
			array( 'name' => 'hook_{$object->property}_pre', 'line' => 7 )
		);

		$this->assertFileContainsHook(
			array(
				'type' => 'filter',
				'name' => 'plain_filter',
				'line' => 8,
				'arguments.0' => '$variable',
				'arguments.1' => '$filter_context'
			)
		);
	}

	/**
	 * Helper to find a hook in a function's hooks array.
	 */
	private function find_hook_in_function( string $func_name, string $hook_name ) {
		$func = $this->find_entity_data_in( $this->export_data, 'functions', $func_name );
		$this->assertIsArray( $func, "Function $func_name should exist" );
		return $this->find_entity_data_in( $func, 'hooks', $hook_name );
	}

	/**
	 * Test that hooks inside if/elseif/else blocks are detected.
	 */
	public function test_hook_in_if_block() {

		$hook = $this->find_hook_in_function( 'hooks_in_control_structures', 'hook_in_if' );
		$this->assertIsArray( $hook, 'hook_in_if should be detected' );
		$this->assertEquals( 'action', $hook['type'] );
	}

	public function test_hook_in_elseif_block() {

		$hook = $this->find_hook_in_function( 'hooks_in_control_structures', 'hook_in_elseif' );
		$this->assertIsArray( $hook, 'hook_in_elseif should be detected' );
	}

	public function test_hook_in_else_block() {

		$hook = $this->find_hook_in_function( 'hooks_in_control_structures', 'hook_in_else' );
		$this->assertIsArray( $hook, 'hook_in_else should be detected' );
	}

	/**
	 * Test that hooks inside foreach with early return are detected.
	 */
	public function test_hook_in_foreach() {

		$hook = $this->find_hook_in_function( 'hooks_in_control_structures', 'hook_in_foreach' );
		$this->assertIsArray( $hook, 'hook_in_foreach should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
	}

	/**
	 * Test that hooks inside switch/case are detected.
	 */
	public function test_hook_in_switch() {

		$hook = $this->find_hook_in_function( 'hooks_in_control_structures', 'hook_in_switch' );
		$this->assertIsArray( $hook, 'hook_in_switch should be detected' );
	}

	/**
	 * Test that hooks nested in function arguments are detected.
	 */
	public function test_hook_in_function_argument() {

		$hook = $this->find_hook_in_function( 'hooks_in_expressions', 'hook_in_func_arg' );
		$this->assertIsArray( $hook, 'hook_in_func_arg should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
	}

	/**
	 * Test that hooks inside sprintf arguments are detected.
	 */
	public function test_hook_in_sprintf() {

		$hook = $this->find_hook_in_function( 'hooks_in_expressions', 'hook_in_sprintf' );
		$this->assertIsArray( $hook, 'hook_in_sprintf should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
	}

	/**
	 * Test that hooks in if conditions are detected.
	 */
	public function test_hook_in_if_condition() {

		$hook = $this->find_hook_in_function( 'hooks_in_expressions', 'hook_in_if_condition' );
		$this->assertIsArray( $hook, 'hook_in_if_condition should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
	}

	/**
	 * Test that hooks in cast expressions are detected.
	 */
	public function test_hook_in_cast() {

		$hook = $this->find_hook_in_function( 'hooks_in_expressions', 'hook_in_cast' );
		$this->assertIsArray( $hook, 'hook_in_cast should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
	}

	/**
	 * Test that hooks in arithmetic expressions are detected.
	 */
	public function test_hook_in_arithmetic() {

		$hook = $this->find_hook_in_function( 'hooks_in_expressions', 'hook_in_arithmetic' );
		$this->assertIsArray( $hook, 'hook_in_arithmetic should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
	}

	/**
	 * Test that hooks in return comparisons are detected.
	 */
	public function test_hook_in_return_comparison() {

		$hook = $this->find_hook_in_function( 'hooks_in_expressions', 'hook_in_return_comparison' );
		$this->assertIsArray( $hook, 'hook_in_return_comparison should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
	}

	/**
	 * Test that multi-line hook calls are detected.
	 */
	public function test_multi_line_hook() {

		$hook = $this->find_hook_in_function( 'multi_line_hook', 'multi_line_hook' );
		$this->assertIsArray( $hook, 'multi_line_hook should be detected' );
	}

	/**
	 * Test that hooks separated from their docblock by intervening code are still detected.
	 */
	public function test_hook_separated_by_code() {

		$hook = $this->find_hook_in_function( 'separated_docblock', 'hook_separated_by_code' );
		$this->assertIsArray( $hook, 'hook_separated_by_code should be detected' );
	}

	/**
	 * Test that "documented elsewhere" reference hooks are detected.
	 */
	public function test_documented_elsewhere() {

		$hook = $this->find_hook_in_function( 'documented_elsewhere', 'hook_documented_elsewhere' );
		$this->assertIsArray( $hook, 'hook_documented_elsewhere should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
		$this->assertStringContainsString( 'documented in', $hook['doc']['description'] );
	}

	/**
	 * Test that hooks with a variable-only name are detected.
	 */
	public function test_hook_variable_only_name() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'hook_with_variable_only' );
		$this->assertIsArray( $func );
		$this->assertNotEmpty( $func['hooks'] );
		$this->assertStringContainsString( '$hook_name', $func['hooks'][0]['name'] );
	}

	/**
	 * Test that hooks with concatenation and variable produce a templated name.
	 */
	public function test_hook_concat_variable_name() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'hook_with_concat_and_variable' );
		$this->assertIsArray( $func );
		$this->assertNotEmpty( $func['hooks'] );
		$this->assertStringContainsString( '$type', $func['hooks'][0]['name'] );
		$this->assertStringContainsString( 'loaded', $func['hooks'][0]['name'] );
	}

	/**
	 * Test deprecated hook types.
	 */
	public function test_deprecated_hook_types() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'hook_deprecated_types' );
		$this->assertIsArray( $func );

		$action = $this->find_entity_data_in( $func, 'hooks', 'old_deprecated_action' );
		$this->assertIsArray( $action );
		$this->assertEquals( 'action_deprecated', $action['type'] );

		$filter = $this->find_entity_data_in( $func, 'hooks', 'old_deprecated_filter' );
		$this->assertIsArray( $filter );
		$this->assertEquals( 'filter_deprecated', $filter['type'] );
	}

	/**
	 * Test that hook arguments with class constants are preserved.
	 */
	public function test_hook_with_class_constant_args() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'hook_with_class_constant_args' );
		$this->assertIsArray( $func );

		$hook = $this->find_entity_data_in( $func, 'hooks', 'hook_with_class_args' );
		$this->assertIsArray( $hook );
		$this->assertCount( 2, $hook['arguments'] );
		$this->assertStringContainsString( 'WP_Post', $hook['arguments'][0] );
	}

	/**
	 * Test that hook arguments with class constants in a namespace
	 * use the original (short) name, not the fully-qualified name.
	 */
	public function test_hook_args_use_original_names_in_namespace() {

		$data = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			do_action( 'ns_hook', WP_Post::STATUS, \WP_Post::STATUS, Options::VALUE );
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'hooks', 'ns_hook' );
		$this->assertIsArray( $hook );

		// Unqualified name resolved by NameResolver — original name preserved.
		$this->assertEquals( 'WP_Post::STATUS', $hook['arguments'][0] );
		// Already FQ — printed without leading backslash.
		$this->assertStringContainsString( 'WP_Post::STATUS', $hook['arguments'][1] );
		// Namespace-local name.
		$this->assertEquals( 'Options::VALUE', $hook['arguments'][2] );
	}
}
