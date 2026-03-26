<?php

/**
 * A test case for exporting function use when one function is defined within another.
 */

namespace WP_Parser\Tests;

/**
 * Test that function use is exported correctly when function declarations are nested.
 */
class Export_Nested_Function_Use extends Export_UnitTestCase {

	/**
	 * Test that the uses data of the outer function is correct.
	 */
	public function test_top_function_uses_correct() {

		$data = $this->parse_string(
			<<<'PHP'


			function test() {

				a_function();

				function sub_test() {

					b_function();

					My_Class::static_method();
				}

				sub_test();

				My_Class::do_things();
			}

			class My_Class extends Parent_Class {

				public function a_method() {

					$this->do_it();

					function sub_method_test() {

						b_function();

						My_Class::a_method();
					}

					do_things();
				}
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test' );
		$this->assertIsArray( $func );

		$this->assertEntityUsesFunction(
			$func
			, array(
				'name'     => 'a_function',
				'line'     => 5,
				'end_line' => 5,
			)
		);

		$this->assertEntityUsesFunction(
			$func
			, array(
				'name'     => 'sub_test',
				'line'     => 14,
				'end_line' => 14,
			)
		);

		$this->assertEntityUsesMethod(
			$func
			, array(
				'name'     => 'do_things',
				'line'     => 16,
				'end_line' => 16,
				'class'    => '\My_Class',
				'static'   => true,
			)
		);

		$this->assertEntityNotUsesFunction(
			$func
			, array(
				'name'     => 'b_function',
				'line'     => 9,
				'end_line' => 9,
			)
		);

		$this->assertEntityNotUsesMethod(
			$func
			, array(
				'name'     => 'static_method',
				'line'     => 11,
				'end_line' => 11,
				'class'    => '\My_Class',
				'static'   => true,
			)
		);
	}

	/**
	 * Test that the usages of the nested function is correct.
	 */
	public function test_nested_function_uses_correct() {

		$data = $this->parse_string(
			<<<'PHP'


			function test() {

				a_function();

				function sub_test() {

					b_function();

					My_Class::static_method();
				}

				sub_test();

				My_Class::do_things();
			}

			class My_Class extends Parent_Class {

				public function a_method() {

					$this->do_it();

					function sub_method_test() {

						b_function();

						My_Class::a_method();
					}

					do_things();
				}
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'sub_test' );
		$this->assertIsArray( $func );

		$this->assertEntityUsesFunction(
			$func
			, array(
				'name'     => 'b_function',
				'line'     => 9,
				'end_line' => 9,
			)
		);

		$this->assertEntityUsesMethod(
			$func
			, array(
				'name'     => 'static_method',
				'line'     => 11,
				'end_line' => 11,
				'class'    => '\My_Class',
				'static'   => true,
			)
		);

		$this->assertEntityNotUsesFunction(
			$func
			, array(
				'name'     => 'a_function',
				'line'     => 5,
				'end_line' => 5,
			)
		);

		$this->assertEntityNotUsesFunction(
			$func
			, array(
				'name'     => 'sub_test',
				'line'     => 14,
				'end_line' => 14,
			)
		);

		$this->assertEntityNotUsesMethod(
			$func
			, array(
				'name'     => 'do_things',
				'line'     => 16,
				'end_line' => 16,
			)
		);
	}


	/**
	 * Test that the uses data of the outer method is correct.
	 */
	public function test_method_uses_correct() {

		$data = $this->parse_string(
			<<<'PHP'


			function test() {

				a_function();

				function sub_test() {

					b_function();

					My_Class::static_method();
				}

				sub_test();

				My_Class::do_things();
			}

			class My_Class extends Parent_Class {

				public function a_method() {

					$this->do_it();

					function sub_method_test() {

						b_function();

						My_Class::a_method();
					}

					do_things();
				}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'My_Class', 'methods', 'a_method' );
		$this->assertIsArray( $method );

		$this->assertEntityUsesMethod(
			$method
			, array(
				'name'     => 'do_it',
				'line'     => 23,
				'end_line' => 23,
				'class'    => '\My_Class',
				'static'   => false,
			)
		);

		$this->assertEntityUsesFunction(
			$method
			, array(
				'name'     => 'do_things',
				'line'     => 32,
				'end_line' => 32,
			)
		);

		$this->assertEntityNotUsesFunction(
			$method
			, array(
				'name'     => 'b_function',
				'line'     => 27,
				'end_line' => 27,
			)
		);

		$this->assertEntityNotUsesMethod(
			$method
			, array(
				'name'     => 'a_method',
				'line'     => 29,
				'end_line' => 29,
				'class'    => '\My_Class',
				'static'   => true,
			)
		);
	}

	/**
	 * Test that the usages of the nested function within a method is correct.
	 */
	public function test_nested_function_in_method_uses_correct() {

		$data = $this->parse_string(
			<<<'PHP'


			function test() {

				a_function();

				function sub_test() {

					b_function();

					My_Class::static_method();
				}

				sub_test();

				My_Class::do_things();
			}

			class My_Class extends Parent_Class {

				public function a_method() {

					$this->do_it();

					function sub_method_test() {

						b_function();

						My_Class::a_method();
					}

					do_things();
				}
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'sub_method_test' );
		$this->assertIsArray( $func );

		$this->assertEntityUsesFunction(
			$func
			, array(
				'name'     => 'b_function',
				'line'     => 27,
				'end_line' => 27,
			)
		);

		$this->assertEntityUsesMethod(
			$func
			, array(
				'name'     => 'a_method',
				'line'     => 29,
				'end_line' => 29,
				'class'    => '\My_Class',
				'static'   => true,
			)
		);

		$this->assertEntityNotUsesMethod(
			$func
			, array(
				'name'     => 'do_it',
				'line'     => 23,
				'end_line' => 23,
				'class'    => '\My_Class',
				'static'   => false,
			)
		);

		$this->assertEntityNotUsesFunction(
			$func
			, array(
				'name'     => 'do_things',
				'line'     => 32,
				'end_line' => 32,
			)
		);
	}
}
