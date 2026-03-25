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
		$expected = 'Awesome\\Space';
		$actual   = $this->export_data['functions'][0]['namespace'];

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
}
