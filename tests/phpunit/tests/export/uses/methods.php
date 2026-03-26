<?php

/**
 * A test case for exporting method use.
 */

namespace WP_Parser\Tests;

/**
 * Test that method use is exported correctly.
 */
class Export_Method_Use extends Export_UnitTestCase {

	/**
	 * Test that static method use is exported.
	 */
	public function test_static_methods() {

		$data = $this->parse_string(
			<<<'PHP'


			My_Class::static_method( $var );

			$wpdb->update( $table, $data, $where );

			function test() {
				Another_Class::another_method();

				get_class()->call_method();
			}

			class My_Class extends Parent_Class {

				static function static_method() {
					Another_Class::do_static_stuff();
					self::do_stuff();
					$this->go();
					parent::do_parental_stuff();
				}
			}
			PHP
		);

		$this->assertEntityUsesMethod(
			$data,
			array(
				'name'     => 'static_method',
				'line'     => 3,
				'end_line' => 3,
				'class'    => '\My_Class',
				'static'   => true,
			)
		);

		$function_data = $this->find_entity_data_in( $data, 'functions', 'test' );
		$this->assertIsArray( $function_data );
		$this->assertEntityUsesMethod(
			$function_data,
			array(
				'name'     => 'another_method',
				'line'     => 8,
				'end_line' => 8,
				'class'    => '\Another_Class',
				'static'   => true,
			)
		);

		$method_data = $this->find_entity_data_in( $data, 'classes', 'My_Class', 'methods', 'static_method' );
		$this->assertIsArray( $method_data );

		$this->assertEntityUsesMethod(
			$method_data,
			array(
				'name'     => 'do_static_stuff',
				'line'     => 16,
				'end_line' => 16,
				'class'    => '\Another_Class',
				'static'   => true,
			)
		);

		$this->assertEntityUsesMethod(
			$method_data,
			array(
				'name'     => 'do_stuff',
				'line'     => 17,
				'end_line' => 17,
				'class'    => '\My_Class',
				'static'   => true,
			)
		);

		$this->assertEntityUsesMethod(
			$method_data,
			array(
				'name'     => 'do_parental_stuff',
				'line'     => 19,
				'end_line' => 19,
				'class'    => '\Parent_Class',
				'static'   => true,
			)
		);
	}

	/**
	 * Test that instance method use is exported.
	 */
	public function test_instance_methods() {

		$data = $this->parse_string(
			<<<'PHP'


			My_Class::static_method( $var );

			$wpdb->update( $table, $data, $where );

			function test() {
				Another_Class::another_method();

				get_class()->call_method();
			}

			class My_Class extends Parent_Class {

				static function static_method() {
					Another_Class::do_static_stuff();
					self::do_stuff();
					$this->go();
					parent::do_parental_stuff();
				}
			}
			PHP
		);

		$this->assertEntityUsesMethod(
			$data,
			array(
				'name'     => 'update',
				'line'     => 5,
				'end_line' => 5,
				'class'    => '$wpdb',
				'static'   => false,
			)
		);

		$function_data = $this->find_entity_data_in( $data, 'functions', 'test' );
		$this->assertIsArray( $function_data );
		$this->assertEntityUsesMethod(
			$function_data,
			array(
				'name'     => 'call_method',
				'line'     => 10,
				'end_line' => 10,
				'class'    => 'get_class()',
				'static'   => false,
			)
		);

		$method_data = $this->find_entity_data_in( $data, 'classes', 'My_Class', 'methods', 'static_method' );
		$this->assertIsArray( $method_data );

		$this->assertEntityUsesMethod(
			$method_data,
			array(
				'name'     => 'go',
				'line'     => 18,
				'end_line' => 18,
				'class'    => '\My_Class',
				'static'   => false,
			)
		);
	}
}
