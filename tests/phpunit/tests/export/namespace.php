<?php

/**
 * A test case for hook exporting.
 */

namespace WP_Parser\Tests;

/**
 * Test that hooks are exported correctly.
 */
class Export_Namespace extends Export_UnitTestCase {

	/**
	 * Test that hook names are standardized on export.
	 */
	public function test_basic_namespace_support() {
		$data = $this->parse_string(
			<<<'PHP'
			namespace Awesome\Space;
			function ohai() {}
			PHP
		);

		$expected = 'Awesome\\Space';
		$actual   = $data['functions'][0]['namespace'];

		$this->assertEquals( $expected, $actual, 'Namespace should be parsed' );
	}

	/**
	 * Test multiple namespaces in one file.
	 */
	public function test_multiple_namespaces() {
		$data = $this->parse_string(
			<<<'PHP'
			namespace Alpha {
				function alpha_func() {}
			}
			namespace Beta {
				function beta_func() {}
			}
			PHP
		);

		$alpha = $this->find_entity_data_in( $data, 'functions', 'alpha_func' );
		$this->assertIsArray( $alpha, 'alpha_func should be found' );
		$this->assertEquals( 'Alpha', $alpha['namespace'] );

		$beta = $this->find_entity_data_in( $data, 'functions', 'beta_func' );
		$this->assertIsArray( $beta, 'beta_func should be found' );
		$this->assertEquals( 'Beta', $beta['namespace'] );
	}

	/**
	 * Test that function use imports don't crash the parser.
	 *
	 * Function and const use statements are TYPE_FUNCTION/TYPE_CONSTANT,
	 * not TYPE_NORMAL, and should be skipped by the Namespace_Visitor.
	 */
	public function test_function_and_const_use_imports() {
		$data = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			use function strlen;
			use const PHP_INT_MAX;
			function func() {
				return strlen( 'hello' );
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'func' );
		$this->assertIsArray( $func, 'func should be parsed despite function/const use imports' );
	}

	/**
	 * Test that a global namespace block (no name) is handled.
	 */
	public function test_global_namespace_block() {
		$data = $this->parse_string(
			<<<'PHP'
			namespace {
				function global_func() {}
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'global_func' );
		$this->assertIsArray( $func, 'global_func should be found' );
	}
}
