<?php

/**
 * A test case for exporting constructor method use.
 */

namespace WP_Parser\Tests;

/**
 * Test that use of the __construct() method is exported for new Class() statements.
 */
class Export_Constructor_Use extends Export_UnitTestCase {

	/**
	 * Test that use is exported when the class name is used explicitly.
	 */
	public function test_new_class() {
		$data = $this->parse_string(
			<<<'PHP'
			$query = new WP_Query();

			function test() {
				$a = new My_Class;
			}
			PHP
		);

		$this->assertEntityUsesMethod(
			$data,
			array(
				'name'     => '__construct',
				'line'     => 1,
				'end_line' => 1,
				'class'    => '\WP_Query',
				'static'   => false,
			)
		);

		$function = $this->find_entity_data_in( $data, 'functions', 'test' );
		$this->assertIsArray( $function );
		$this->assertEntityUsesMethod(
			$function,
			array(
				'name'     => '__construct',
				'line'     => 4,
				'end_line' => 4,
				'class'    => '\My_Class',
				'static'   => false,
			)
		);
	}

	/**
	 * Test that use is exported when the self keyword is used.
	 */
	public function test_new_self() {
		$data = $this->parse_string(
			<<<'PHP'
			class My_Class extends Parent_Class {

				static function instance() {
					return new self;
				}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'My_Class', 'methods', 'instance' );
		$this->assertIsArray( $method );
		$this->assertEntityUsesMethod(
			$method,
			array(
				'name'     => '__construct',
				'line'     => 4,
				'end_line' => 4,
				'class'    => '\My_Class',
				'static'   => false,
			)
		);
	}

	/**
	 * Test that use is exported when the parent keyword is used.
	 */
	public function test_new_parent() {
		$data = $this->parse_string(
			<<<'PHP'
			class My_Class extends Parent_Class {

				static function parent() {
					return new parent;
				}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'My_Class', 'methods', 'parent' );
		$this->assertIsArray( $method );
		$this->assertEntityUsesMethod(
			$method,
			array(
				'name'     => '__construct',
				'line'     => 4,
				'end_line' => 4,
				'class'    => '\Parent_Class',
				'static'   => false,
			)
		);
	}

	/**
	 * Test that use is exported when a variable is used.
	 */
	public function test_new_variable() {
		$data = $this->parse_string(
			<<<'PHP'
			$b = new $class;
			PHP
		);

		$this->assertEntityUsesMethod(
			$data,
			array(
				'name'     => '__construct',
				'line'     => 1,
				'end_line' => 1,
				'class'    => '$class',
				'static'   => false,
			)
		);
	}
}
