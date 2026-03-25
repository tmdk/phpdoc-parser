<?php
/**
 * Tests for function use tracking at file, function, and method level.
 */

namespace WP_Parser\Tests;

/**
 * Test that function uses are exported correctly.
 */
class Export_Function_Use extends Export_UnitTestCase {

	/**
	 * Test file-level plain function calls.
	 */
	public function test_file_level_function_calls() {

		$this->assertFileUsesFunction(
			array(
				'name'     => 'wp_enqueue_script',
				'line'     => 4,
				'end_line' => 4,
			)
		);

		$this->assertFileUsesFunction(
			array(
				'name'     => 'esc_html',
				'line'     => 5,
				'end_line' => 5,
			)
		);
	}

	/**
	 * Test that hook functions (do_action, apply_filters) appear as function uses.
	 */
	public function test_hook_functions_as_uses() {

		$this->assertFileUsesFunction(
			array(
				'name'     => 'do_action',
				'line'     => 14,
				'end_line' => 14,
			)
		);

		$this->assertFileUsesFunction(
			array(
				'name'     => 'apply_filters',
				'line'     => 15,
				'end_line' => 15,
			)
		);
	}

	/**
	 * Test function-level function calls.
	 */
	public function test_function_level_calls() {

		$this->assertFunctionUsesFunction(
			'func_with_calls'
			, array(
				'name'     => 'wp_enqueue_style',
				'line'     => 18,
				'end_line' => 18,
			)
		);

		$this->assertFunctionUsesFunction(
			'func_with_calls'
			, array(
				'name'     => 'add_action',
				'line'     => 19,
				'end_line' => 19,
			)
		);
	}

	/**
	 * Test that hook calls inside functions are tracked as function uses.
	 */
	public function test_hook_calls_in_function_as_uses() {

		$this->assertFunctionUsesFunction(
			'func_with_calls'
			, array(
				'name'     => 'do_action',
				'line'     => 21,
				'end_line' => 21,
			)
		);

		$this->assertFunctionUsesFunction(
			'func_with_calls'
			, array(
				'name'     => 'apply_filters',
				'line'     => 22,
				'end_line' => 22,
			)
		);
	}

	/**
	 * Test method-level function calls.
	 */
	public function test_method_level_calls() {

		$this->assertMethodUsesFunction(
			'Uses_Class'
			, 'method_with_calls'
			, array(
				'name'     => 'wp_nonce_field',
				'line'     => 27,
				'end_line' => 27,
			)
		);

		$this->assertMethodUsesFunction(
			'Uses_Class'
			, 'method_with_calls'
			, array(
				'name'     => 'get_option',
				'line'     => 28,
				'end_line' => 28,
			)
		);
	}
}
