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

		$data = $this->parse_string(
			<<<'PHP'
			do_action( 'plain_action' );
			do_action( "action_with_double_quotes" );
			do_action( $variable . '-action' );
			do_action( "another-{$variable}-action" );
			do_action( 'hook_' . $object->property . '_pre' );
			apply_filters( 'plain_filter', $variable, $filter_context );
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'hooks', 'plain_action' );
		$this->assertIsArray( $hook );
		$this->assertEquals( 'action', $hook['type'] );

		$hook = $this->find_entity_data_in( $data, 'hooks', 'action_with_double_quotes' );
		$this->assertIsArray( $hook );
		$this->assertEquals( 'action', $hook['type'] );

		$hook = $this->find_entity_data_in( $data, 'hooks', '{$variable}-action' );
		$this->assertIsArray( $hook );
		$this->assertEquals( 'action', $hook['type'] );

		$hook = $this->find_entity_data_in( $data, 'hooks', 'another-{$variable}-action' );
		$this->assertIsArray( $hook );
		$this->assertEquals( 'action', $hook['type'] );

		$hook = $this->find_entity_data_in( $data, 'hooks', 'hook_{$object->property}_pre' );
		$this->assertIsArray( $hook );
		$this->assertEquals( 'action', $hook['type'] );

		$hook = $this->find_entity_data_in( $data, 'hooks', 'plain_filter' );
		$this->assertIsArray( $hook );
		$this->assertEquals( 'filter', $hook['type'] );
		$this->assertEquals( '$variable', $hook['arguments'][0] );
		$this->assertEquals( '$filter_context', $hook['arguments'][1] );
	}

	/**
	 * Test that hooks inside if/elseif/else blocks are detected.
	 */
	public function test_hook_in_if_block() {

		$data = $this->parse_string(
			<<<'PHP'
			function hooks_in_control_structures() {
				if ( $condition ) {
					/**
					 * Hook inside if block.
					 */
					do_action( 'hook_in_if' );
				} elseif ( $other ) {
					/**
					 * Hook inside elseif block.
					 */
					do_action( 'hook_in_elseif' );
				} else {
					/**
					 * Hook inside else block.
					 */
					do_action( 'hook_in_else' );
				}

				foreach ( $items as $item ) {
					/**
					 * Hook inside foreach with early return.
					 */
					$result = apply_filters( 'hook_in_foreach', $item );
					if ( ! $result ) {
						return;
					}
				}

				switch ( $type ) {
					case 'post':
						/**
						 * Hook inside switch/case.
						 */
						do_action( 'hook_in_switch', $type );
						break;
				}
			}
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'functions', 'hooks_in_control_structures', 'hooks', 'hook_in_if' );
		$this->assertIsArray( $hook, 'hook_in_if should be detected' );
		$this->assertEquals( 'action', $hook['type'] );
	}

	public function test_hook_in_elseif_block() {

		$data = $this->parse_string(
			<<<'PHP'
			function hooks_in_control_structures() {
				if ( $condition ) {
					/**
					 * Hook inside if block.
					 */
					do_action( 'hook_in_if' );
				} elseif ( $other ) {
					/**
					 * Hook inside elseif block.
					 */
					do_action( 'hook_in_elseif' );
				} else {
					/**
					 * Hook inside else block.
					 */
					do_action( 'hook_in_else' );
				}
			}
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'functions', 'hooks_in_control_structures', 'hooks', 'hook_in_elseif' );
		$this->assertIsArray( $hook, 'hook_in_elseif should be detected' );
	}

	public function test_hook_in_else_block() {

		$data = $this->parse_string(
			<<<'PHP'
			function hooks_in_control_structures() {
				if ( $condition ) {
					do_action( 'hook_in_if' );
				} else {
					/**
					 * Hook inside else block.
					 */
					do_action( 'hook_in_else' );
				}
			}
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'functions', 'hooks_in_control_structures', 'hooks', 'hook_in_else' );
		$this->assertIsArray( $hook, 'hook_in_else should be detected' );
	}

	/**
	 * Test that hooks inside foreach with early return are detected.
	 */
	public function test_hook_in_foreach() {

		$data = $this->parse_string(
			<<<'PHP'
			function hooks_in_control_structures() {
				foreach ( $items as $item ) {
					/**
					 * Hook inside foreach with early return.
					 */
					$result = apply_filters( 'hook_in_foreach', $item );
					if ( ! $result ) {
						return;
					}
				}
			}
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'functions', 'hooks_in_control_structures', 'hooks', 'hook_in_foreach' );
		$this->assertIsArray( $hook, 'hook_in_foreach should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
	}

	/**
	 * Test that hooks inside switch/case are detected.
	 */
	public function test_hook_in_switch() {

		$data = $this->parse_string(
			<<<'PHP'
			function hooks_in_control_structures() {
				switch ( $type ) {
					case 'post':
						/**
						 * Hook inside switch/case.
						 */
						do_action( 'hook_in_switch', $type );
						break;
				}
			}
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'functions', 'hooks_in_control_structures', 'hooks', 'hook_in_switch' );
		$this->assertIsArray( $hook, 'hook_in_switch should be detected' );
	}

	/**
	 * Test that hooks nested in function arguments are detected.
	 */
	public function test_hook_in_function_argument() {

		$data = $this->parse_string(
			<<<'PHP'
			function hooks_in_expressions() {
				echo esc_url( apply_filters( 'hook_in_func_arg', $url ) );
			}
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'functions', 'hooks_in_expressions', 'hooks', 'hook_in_func_arg' );
		$this->assertIsArray( $hook, 'hook_in_func_arg should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
	}

	/**
	 * Test that hooks inside sprintf arguments are detected.
	 */
	public function test_hook_in_sprintf() {

		$data = $this->parse_string(
			<<<'PHP'
			function hooks_in_expressions() {
				$output = sprintf(
					'<div>%s</div>',
					/**
					 * Hook inside sprintf argument.
					 */
					apply_filters( 'hook_in_sprintf', $content )
				);
			}
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'functions', 'hooks_in_expressions', 'hooks', 'hook_in_sprintf' );
		$this->assertIsArray( $hook, 'hook_in_sprintf should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
	}

	/**
	 * Test that hooks in if conditions are detected.
	 */
	public function test_hook_in_if_condition() {

		$data = $this->parse_string(
			<<<'PHP'
			function hooks_in_expressions() {
				if ( ! apply_filters( 'hook_in_if_condition', true ) ) {
					return;
				}
			}
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'functions', 'hooks_in_expressions', 'hooks', 'hook_in_if_condition' );
		$this->assertIsArray( $hook, 'hook_in_if_condition should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
	}

	/**
	 * Test that hooks in cast expressions are detected.
	 */
	public function test_hook_in_cast() {

		$data = $this->parse_string(
			<<<'PHP'
			function hooks_in_expressions() {
				$value = (bool) apply_filters( 'hook_in_cast', $value );
			}
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'functions', 'hooks_in_expressions', 'hooks', 'hook_in_cast' );
		$this->assertIsArray( $hook, 'hook_in_cast should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
	}

	/**
	 * Test that hooks in arithmetic expressions are detected.
	 */
	public function test_hook_in_arithmetic() {

		$data = $this->parse_string(
			<<<'PHP'
			function hooks_in_expressions() {
				$time = time() + apply_filters( 'hook_in_arithmetic', 3600 );
			}
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'functions', 'hooks_in_expressions', 'hooks', 'hook_in_arithmetic' );
		$this->assertIsArray( $hook, 'hook_in_arithmetic should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
	}

	/**
	 * Test that hooks in return comparisons are detected.
	 */
	public function test_hook_in_return_comparison() {

		$data = $this->parse_string(
			<<<'PHP'
			function hooks_in_expressions() {
				return true === apply_filters( 'hook_in_return_comparison', $check );
			}
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'functions', 'hooks_in_expressions', 'hooks', 'hook_in_return_comparison' );
		$this->assertIsArray( $hook, 'hook_in_return_comparison should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
	}

	/**
	 * Test that multi-line hook calls are detected.
	 */
	public function test_multi_line_hook() {

		$data = $this->parse_string(
			<<<'PHP'
			function multi_line_hook() {
				do_action(
					'multi_line_hook',
					$arg1,
					$arg2,
					$arg3
				);
			}
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'functions', 'multi_line_hook', 'hooks', 'multi_line_hook' );
		$this->assertIsArray( $hook, 'multi_line_hook should be detected' );
	}

	/**
	 * Test that hooks separated from their docblock by intervening code are still detected.
	 */
	public function test_hook_separated_by_code() {

		$data = $this->parse_string(
			<<<'PHP'
			function separated_docblock() {
				/**
				 * Hook with intervening code between docblock and call.
				 */
				$var = 'something';
				do_action( 'hook_separated_by_code', $var );
			}
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'functions', 'separated_docblock', 'hooks', 'hook_separated_by_code' );
		$this->assertIsArray( $hook, 'hook_separated_by_code should be detected' );
	}

	/**
	 * Test that "documented elsewhere" reference hooks are detected.
	 */
	public function test_documented_elsewhere() {

		$data = $this->parse_string(
			<<<'PHP'
			function documented_elsewhere() {
				/** This filter is documented in wp-includes/post.php */
				$value = apply_filters( 'hook_documented_elsewhere', $value );
			}
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'functions', 'documented_elsewhere', 'hooks', 'hook_documented_elsewhere' );
		$this->assertIsArray( $hook, 'hook_documented_elsewhere should be detected' );
		$this->assertEquals( 'filter', $hook['type'] );
		$this->assertStringContainsString( 'documented in', $hook['doc']['description'] );
	}

	/**
	 * Test that hooks with a variable-only name are detected.
	 */
	public function test_hook_variable_only_name() {

		$data = $this->parse_string(
			<<<'PHP'
			function hook_with_variable_only() {
				do_action( $hook_name );
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'hook_with_variable_only' );
		$this->assertIsArray( $func );
		$this->assertNotEmpty( $func['hooks'] );
		$this->assertStringContainsString( '$hook_name', $func['hooks'][0]['name'] );
	}

	/**
	 * Test that hooks with concatenation and variable produce a templated name.
	 */
	public function test_hook_concat_variable_name() {

		$data = $this->parse_string(
			<<<'PHP'
			function hook_with_concat_and_variable() {
				do_action( $type . '_loaded' );
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'hook_with_concat_and_variable' );
		$this->assertIsArray( $func );
		$this->assertNotEmpty( $func['hooks'] );
		$this->assertStringContainsString( '$type', $func['hooks'][0]['name'] );
		$this->assertStringContainsString( 'loaded', $func['hooks'][0]['name'] );
	}

	/**
	 * Test deprecated hook types.
	 */
	public function test_deprecated_hook_types() {

		$data = $this->parse_string(
			<<<'PHP'
			function hook_deprecated_types() {
				do_action_deprecated( 'old_deprecated_action', array(), '3.0.0' );
				apply_filters_deprecated( 'old_deprecated_filter', array( $value ), '2.5.0' );
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'hook_deprecated_types' );

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

		$data = $this->parse_string(
			<<<'PHP'
			function hook_with_class_constant_args() {
				do_action( 'hook_with_class_args', \WP_Post::class, My_Plugin::VERSION );
			}
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'functions', 'hook_with_class_constant_args', 'hooks', 'hook_with_class_args' );
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
