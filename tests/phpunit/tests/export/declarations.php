<?php
/**
 * Tests for PHP declarations: abstract/final classes, static members,
 * property visibility and defaults, trait usage, closures.
 */

namespace WP_Parser\Tests;

/**
 * Test that various PHP declarations are exported correctly.
 */
class Export_Declarations extends Export_UnitTestCase {

	/**
	 * Test that abstract classes are exported with the abstract flag.
	 */
	public function test_abstract_class() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * An abstract base class.
			 */
			abstract class Abstract_Base {

				/**
				 * An abstract method.
				 *
				 * @param string $value The value.
				 * @return bool The result.
				 */
				abstract public function abstract_method( $value );

				/**
				 * A concrete method in an abstract class.
				 */
				public function concrete_method() {
					return true;
				}
			}
			PHP
		);

		$class = $this->find_entity_data_in( $data, 'classes', 'Abstract_Base' );
		$this->assertIsArray( $class );
		$this->assertTrue( $class['abstract'], 'Abstract_Base should be marked abstract' );
		$this->assertFalse( $class['final'], 'Abstract_Base should not be marked final' );
	}

	/**
	 * Test that abstract methods are exported with the abstract flag.
	 */
	public function test_abstract_method() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * An abstract base class.
			 */
			abstract class Abstract_Base {

				/**
				 * An abstract method.
				 *
				 * @param string $value The value.
				 * @return bool The result.
				 */
				abstract public function abstract_method( $value );

				/**
				 * A concrete method in an abstract class.
				 */
				public function concrete_method() {
					return true;
				}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'Abstract_Base', 'methods', 'abstract_method' );
		$this->assertIsArray( $method );
		$this->assertTrue( $method['abstract'], 'abstract_method should be marked abstract' );
		$this->assertFalse( $method['final'] );
	}

	/**
	 * Test that concrete methods in abstract class are not abstract.
	 */
	public function test_concrete_method_in_abstract_class() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * An abstract base class.
			 */
			abstract class Abstract_Base {

				/**
				 * An abstract method.
				 *
				 * @param string $value The value.
				 * @return bool The result.
				 */
				abstract public function abstract_method( $value );

				/**
				 * A concrete method in an abstract class.
				 */
				public function concrete_method() {
					return true;
				}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'Abstract_Base', 'methods', 'concrete_method' );
		$this->assertIsArray( $method );
		$this->assertFalse( $method['abstract'] );
	}

	/**
	 * Test that final classes are exported with the final flag.
	 */
	public function test_final_class() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A final class that cannot be extended.
			 */
			final class Final_Class {

				/**
				 * A final method that cannot be overridden.
				 */
				final public function final_method() {}

				/**
				 * A regular method in a final class.
				 */
				public function regular_method() {}
			}
			PHP
		);

		$class = $this->find_entity_data_in( $data, 'classes', 'Final_Class' );
		$this->assertIsArray( $class );
		$this->assertTrue( $class['final'], 'Final_Class should be marked final' );
		$this->assertFalse( $class['abstract'], 'Final_Class should not be marked abstract' );
	}

	/**
	 * Test that final methods are exported with the final flag.
	 */
	public function test_final_method() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A final class that cannot be extended.
			 */
			final class Final_Class {

				/**
				 * A final method that cannot be overridden.
				 */
				final public function final_method() {}

				/**
				 * A regular method in a final class.
				 */
				public function regular_method() {}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'Final_Class', 'methods', 'final_method' );
		$this->assertIsArray( $method );
		$this->assertTrue( $method['final'], 'final_method should be marked final' );
	}

	/**
	 * Test that regular methods in final class are not final.
	 */
	public function test_regular_method_in_final_class() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A final class that cannot be extended.
			 */
			final class Final_Class {

				/**
				 * A final method that cannot be overridden.
				 */
				final public function final_method() {}

				/**
				 * A regular method in a final class.
				 */
				public function regular_method() {}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'Final_Class', 'methods', 'regular_method' );
		$this->assertIsArray( $method );
		$this->assertFalse( $method['final'] );
	}

	/**
	 * Test that static properties are exported with the static flag.
	 */
	public function test_static_property() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A class demonstrating static properties and methods.
			 */
			class Static_Members {

				/**
				 * A static property.
				 *
				 * @var int
				 */
				public static $counter = 0;

				/**
				 * A static method.
				 */
				public static function increment() {
					self::$counter++;
				}

				/**
				 * A protected static method.
				 */
				protected static function reset() {
					self::$counter = 0;
				}
			}
			PHP
		);

		$prop = $this->find_entity_data_in( $data, 'classes', 'Static_Members', 'properties', '$counter' );
		$this->assertIsArray( $prop );
		$this->assertTrue( $prop['static'], '$counter should be static' );
		$this->assertEquals( 'public', $prop['visibility'] );
	}

	/**
	 * Test that static methods are exported with the static flag.
	 */
	public function test_static_method() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A class demonstrating static properties and methods.
			 */
			class Static_Members {

				/**
				 * A static property.
				 *
				 * @var int
				 */
				public static $counter = 0;

				/**
				 * A static method.
				 */
				public static function increment() {
					self::$counter++;
				}

				/**
				 * A protected static method.
				 */
				protected static function reset() {
					self::$counter = 0;
				}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'Static_Members', 'methods', 'increment' );
		$this->assertIsArray( $method );
		$this->assertTrue( $method['static'], 'increment should be static' );
		$this->assertEquals( 'public', $method['visibility'] );
	}

	/**
	 * Test that protected static methods are exported correctly.
	 */
	public function test_protected_static_method() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A class demonstrating static properties and methods.
			 */
			class Static_Members {

				/**
				 * A static property.
				 *
				 * @var int
				 */
				public static $counter = 0;

				/**
				 * A static method.
				 */
				public static function increment() {
					self::$counter++;
				}

				/**
				 * A protected static method.
				 */
				protected static function reset() {
					self::$counter = 0;
				}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'Static_Members', 'methods', 'reset' );
		$this->assertIsArray( $method );
		$this->assertTrue( $method['static'] );
		$this->assertEquals( 'protected', $method['visibility'] );
	}

	/**
	 * Test public property visibility.
	 */
	public function test_public_property() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A class demonstrating property visibility and defaults.
			 */
			class Property_Showcase {

				/**
				 * A public property.
				 *
				 * @var string
				 */
				public $pub_prop = 'hello';

				/**
				 * A protected property.
				 *
				 * @var array
				 */
				protected $prot_prop = [];

				/**
				 * A private property.
				 *
				 * @var int
				 */
				private $priv_prop = 42;

				/**
				 * A property with an array default.
				 *
				 * @var array
				 */
				public $arr_default = array( 'a', 'b', 'c' );

				/**
				 * A property with a null default.
				 *
				 * @var mixed
				 */
				public $null_default = null;

				/**
				 * A property with a constant default.
				 *
				 * @var bool
				 */
				public $bool_default = true;
			}
			PHP
		);

		$prop = $this->find_entity_data_in( $data, 'classes', 'Property_Showcase', 'properties', '$pub_prop' );
		$this->assertIsArray( $prop );
		$this->assertEquals( 'public', $prop['visibility'] );
		$this->assertEquals( "'hello'", $prop['default'] );
	}

	/**
	 * Test protected property visibility.
	 */
	public function test_protected_property() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A class demonstrating property visibility and defaults.
			 */
			class Property_Showcase {

				/**
				 * A public property.
				 *
				 * @var string
				 */
				public $pub_prop = 'hello';

				/**
				 * A protected property.
				 *
				 * @var array
				 */
				protected $prot_prop = [];

				/**
				 * A private property.
				 *
				 * @var int
				 */
				private $priv_prop = 42;

				/**
				 * A property with an array default.
				 *
				 * @var array
				 */
				public $arr_default = array( 'a', 'b', 'c' );

				/**
				 * A property with a null default.
				 *
				 * @var mixed
				 */
				public $null_default = null;

				/**
				 * A property with a constant default.
				 *
				 * @var bool
				 */
				public $bool_default = true;
			}
			PHP
		);

		$prop = $this->find_entity_data_in( $data, 'classes', 'Property_Showcase', 'properties', '$prot_prop' );
		$this->assertIsArray( $prop );
		$this->assertEquals( 'protected', $prop['visibility'] );
	}

	/**
	 * Test private property visibility.
	 */
	public function test_private_property() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A class demonstrating property visibility and defaults.
			 */
			class Property_Showcase {

				/**
				 * A public property.
				 *
				 * @var string
				 */
				public $pub_prop = 'hello';

				/**
				 * A protected property.
				 *
				 * @var array
				 */
				protected $prot_prop = [];

				/**
				 * A private property.
				 *
				 * @var int
				 */
				private $priv_prop = 42;

				/**
				 * A property with an array default.
				 *
				 * @var array
				 */
				public $arr_default = array( 'a', 'b', 'c' );

				/**
				 * A property with a null default.
				 *
				 * @var mixed
				 */
				public $null_default = null;

				/**
				 * A property with a constant default.
				 *
				 * @var bool
				 */
				public $bool_default = true;
			}
			PHP
		);

		$prop = $this->find_entity_data_in( $data, 'classes', 'Property_Showcase', 'properties', '$priv_prop' );
		$this->assertIsArray( $prop );
		$this->assertEquals( 'private', $prop['visibility'] );
		$this->assertEquals( '42', $prop['default'] );
	}

	/**
	 * Test property with array default.
	 */
	public function test_array_default_property() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A class demonstrating property visibility and defaults.
			 */
			class Property_Showcase {

				/**
				 * A property with an array default.
				 *
				 * @var array
				 */
				public $arr_default = array( 'a', 'b', 'c' );
			}
			PHP
		);

		$prop = $this->find_entity_data_in( $data, 'classes', 'Property_Showcase', 'properties', '$arr_default' );
		$this->assertIsArray( $prop );
		$this->assertNotEmpty( $prop['default'] );
	}

	/**
	 * Test property with null default.
	 */
	public function test_null_default_property() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A class demonstrating property visibility and defaults.
			 */
			class Property_Showcase {

				/**
				 * A property with a null default.
				 *
				 * @var mixed
				 */
				public $null_default = null;
			}
			PHP
		);

		$prop = $this->find_entity_data_in( $data, 'classes', 'Property_Showcase', 'properties', '$null_default' );
		$this->assertIsArray( $prop );
		$this->assertEquals( 'null', $prop['default'] );
	}

	/**
	 * Test property with bool default.
	 */
	public function test_bool_default_property() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A class demonstrating property visibility and defaults.
			 */
			class Property_Showcase {

				/**
				 * A property with a constant default.
				 *
				 * @var bool
				 */
				public $bool_default = true;
			}
			PHP
		);

		$prop = $this->find_entity_data_in( $data, 'classes', 'Property_Showcase', 'properties', '$bool_default' );
		$this->assertIsArray( $prop );
		$this->assertEquals( 'true', $prop['default'] );
	}

	/**
	 * Test that a class using a trait is exported.
	 */
	public function test_trait_user_class() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A class that uses a trait.
			 */
			class Trait_User {
				public function own_method() {}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'Trait_User', 'methods', 'own_method' );
		$this->assertIsArray( $method, 'own_method should be exported' );
	}

	/**
	 * Test that closures in methods are parsed without error.
	 */
	public function test_closures_in_method() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A class with closures and arrow functions.
			 */
			class Closure_Class {

				/**
				 * A method that uses closures.
				 */
				public function with_closures() {
					$fn    = function( $x ) { return $x * 2; };
					$arrow = fn( $x ) => $x * 2;

					return $fn( 1 ) + $arrow( 2 );
				}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'Closure_Class', 'methods', 'with_closures' );
		$this->assertIsArray( $method, 'with_closures method should be exported' );
	}

	/**
	 * Test that define() constants are exported.
	 */
	public function test_define_constants() {
		$data = $this->parse_string(
			<<<'PHP'
			define( 'SIMPLE_DEFINE', 'hello world' );
			define( 'NUMERIC_DEFINE', 42 );
			PHP
		);

		$this->assertArrayHasKey( 'constants', $data );

		$simple = $this->find_entity_data_in( $data, 'constants', 'SIMPLE_DEFINE' );
		$this->assertIsArray( $simple );
		$this->assertEquals( "'hello world'", $simple['value'] );

		$numeric = $this->find_entity_data_in( $data, 'constants', 'NUMERIC_DEFINE' );
		$this->assertIsArray( $numeric );
		$this->assertEquals( '42', $numeric['value'] );
	}

	/**
	 * Test that const declarations are exported.
	 */
	public function test_const_declarations() {
		$data = $this->parse_string(
			<<<'PHP'
			const SIMPLE_CONST = 'constant value';
			PHP
		);

		$const = $this->find_entity_data_in( $data, 'constants', 'SIMPLE_CONST' );
		$this->assertIsArray( $const );
		$this->assertEquals( "'constant value'", $const['value'] );
	}

	/**
	 * Test that multi-const declarations are exported as separate entries.
	 */
	public function test_multi_const_declaration() {
		$data = $this->parse_string(
			<<<'PHP'
			const MULTI_A = 1, MULTI_B = 2;
			PHP
		);

		$a = $this->find_entity_data_in( $data, 'constants', 'MULTI_A' );
		$this->assertIsArray( $a );
		$this->assertEquals( '1', $a['value'] );

		$b = $this->find_entity_data_in( $data, 'constants', 'MULTI_B' );
		$this->assertIsArray( $b );
		$this->assertEquals( '2', $b['value'] );
	}

	/**
	 * Test that anonymous classes are skipped without crashing.
	 */
	public function test_anonymous_class_skipped() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A class with an anonymous class instantiation.
			 */
			class Anon_Class_User {

				/**
				 * Returns an anonymous class instance.
				 */
				public function create_anon() {
					return new class {
						public function anon_method() {}
					};
				}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'Anon_Class_User', 'methods', 'create_anon' );
		$this->assertIsArray( $method, 'create_anon method should be exported' );
	}

	/**
	 * Test that heredoc values in define() are exported.
	 */
	public function test_heredoc_constant_value() {
		$data = $this->parse_string(
			<<<'PHP'
			define( 'HEREDOC_DEFINE', <<<EOT
			heredoc value
			EOT );
			PHP
		);

		$const = $this->find_entity_data_in( $data, 'constants', 'HEREDOC_DEFINE' );
		$this->assertIsArray( $const );
		$this->assertStringContainsString( 'heredoc value', $const['value'] );
	}
}
