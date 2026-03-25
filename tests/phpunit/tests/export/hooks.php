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
}
