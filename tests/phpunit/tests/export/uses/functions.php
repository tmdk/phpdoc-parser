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

		$data = $this->parse_string(
			<<<'PHP'
			wp_enqueue_script( 'my-script' );
			esc_html( $text );
			PHP
		);

		$this->assertEntityUsesFunction(
			$data,
			array(
				'name'     => 'wp_enqueue_script',
				'line'     => 1,
				'end_line' => 1,
			)
		);

		$this->assertEntityUsesFunction(
			$data,
			array(
				'name'     => 'esc_html',
				'line'     => 2,
				'end_line' => 2,
			)
		);
	}

	/**
	 * Test that hook functions (do_action, apply_filters) appear as function uses.
	 */
	public function test_hook_functions_as_uses() {

		$data = $this->parse_string(
			<<<'PHP'
			do_action( 'init' );
			$val = apply_filters( 'the_content', $content );
			PHP
		);

		$this->assertEntityUsesFunction(
			$data,
			array(
				'name'     => 'do_action',
				'line'     => 1,
				'end_line' => 1,
			)
		);

		$this->assertEntityUsesFunction(
			$data,
			array(
				'name'     => 'apply_filters',
				'line'     => 2,
				'end_line' => 2,
			)
		);
	}

	/**
	 * Test function-level function calls.
	 */
	public function test_function_level_calls() {

		$data = $this->parse_string(
			<<<'PHP'
			function func_with_calls() {
				wp_enqueue_style( 'my-style' );
				add_action( 'wp_head', 'callback' );
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'func_with_calls' );
		$this->assertIsArray( $func );

		$this->assertEntityUsesFunction(
			$func,
			array(
				'name'     => 'wp_enqueue_style',
				'line'     => 2,
				'end_line' => 2,
			)
		);

		$this->assertEntityUsesFunction(
			$func,
			array(
				'name'     => 'add_action',
				'line'     => 3,
				'end_line' => 3,
			)
		);
	}

	/**
	 * Test that hook calls inside functions are tracked as function uses.
	 */
	public function test_hook_calls_in_function_as_uses() {

		$data = $this->parse_string(
			<<<'PHP'
			function func_with_calls() {
				do_action( 'custom_action', $arg );
				$result = apply_filters( 'custom_filter', $value );
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'func_with_calls' );
		$this->assertIsArray( $func );

		$this->assertEntityUsesFunction(
			$func,
			array(
				'name'     => 'do_action',
				'line'     => 2,
				'end_line' => 2,
			)
		);

		$this->assertEntityUsesFunction(
			$func,
			array(
				'name'     => 'apply_filters',
				'line'     => 3,
				'end_line' => 3,
			)
		);
	}

	/**
	 * Test method-level function calls.
	 */
	public function test_method_level_calls() {

		$data = $this->parse_string(
			<<<'PHP'
			class Uses_Class {
				public function method_with_calls() {
					wp_nonce_field( 'action', 'nonce' );
					get_option( 'siteurl' );
				}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'Uses_Class', 'methods', 'method_with_calls' );
		$this->assertIsArray( $method );

		$this->assertEntityUsesFunction(
			$method,
			array(
				'name'     => 'wp_nonce_field',
				'line'     => 3,
				'end_line' => 3,
			)
		);

		$this->assertEntityUsesFunction(
			$method,
			array(
				'name'     => 'get_option',
				'line'     => 4,
				'end_line' => 4,
			)
		);
	}

	/**
	 * Test that include/require statements are exported.
	 */
	public function test_includes() {

		$data = $this->parse_string(
			<<<'PHP'
			include 'header.php';
			require 'config.php';
			include_once 'utils.php';
			require_once 'bootstrap.php';
			PHP
		);

		$this->assertArrayHasKey( 'includes', $data );

		$includes = $data['includes'];
		$this->assertCount( 4, $includes );

		$types = array_column( $includes, 'type' );
		$this->assertContains( 'Include', $types );
		$this->assertContains( 'Require', $types );
		$this->assertContains( 'Include Once', $types );
		$this->assertContains( 'Require Once', $types );
	}

	/**
	 * Test that _deprecated_function() calls set deprecation_version on
	 * the first function use entry.
	 */
	public function test_deprecation_version_in_function_uses() {

		$data = $this->parse_string(
			<<<'PHP'
			function deprecated_func_caller() {
				_deprecated_function( __FUNCTION__, '3.0.0', 'new_func' );
				some_legacy_work();
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'deprecated_func_caller' );
		$this->assertIsArray( $func );

		$uses = $func['uses']['functions'];
		$this->assertArrayHasKey( 'deprecation_version', $uses[0] );
		$this->assertEquals( '3.0.0', $uses[0]['deprecation_version'] );
	}

	/**
	 * Test that _deprecated_function() in a method sets deprecation_version
	 * on the function uses, not on the method uses.
	 */
	public function test_deprecation_version_in_method_scope() {

		$data = $this->parse_string(
			<<<'PHP'
			class Deprecated_Method_Class {
				public function old_method() {
					_deprecated_function( __METHOD__, '4.0.0', 'new_method' );
					SomeClass::legacy_call();
				}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'Deprecated_Method_Class', 'methods', 'old_method' );
		$this->assertIsArray( $method );

		// deprecation_version is on the function uses (where _deprecated_function lives).
		$func_uses = $method['uses']['functions'];
		$this->assertArrayHasKey( 'deprecation_version', $func_uses[0] );
		$this->assertEquals( '4.0.0', $func_uses[0]['deprecation_version'] );

		// Method uses don't carry deprecation_version.
		$method_uses = $method['uses']['methods'];
		$this->assertArrayNotHasKey( 'deprecation_version', $method_uses[0] );
	}
}
