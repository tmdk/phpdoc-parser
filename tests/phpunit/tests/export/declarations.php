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

		$class = $this->find_entity_data_in( $this->export_data, 'classes', 'Abstract_Base' );
		$this->assertIsArray( $class );
		$this->assertTrue( $class['abstract'], 'Abstract_Base should be marked abstract' );
		$this->assertFalse( $class['final'], 'Abstract_Base should not be marked final' );
	}

	/**
	 * Test that abstract methods are exported with the abstract flag.
	 */
	public function test_abstract_method() {

		$class  = $this->find_entity_data_in( $this->export_data, 'classes', 'Abstract_Base' );
		$method = $this->find_entity_data_in( $class, 'methods', 'abstract_method' );
		$this->assertIsArray( $method );
		$this->assertTrue( $method['abstract'], 'abstract_method should be marked abstract' );
		$this->assertFalse( $method['final'] );
	}

	/**
	 * Test that concrete methods in abstract class are not abstract.
	 */
	public function test_concrete_method_in_abstract_class() {

		$class  = $this->find_entity_data_in( $this->export_data, 'classes', 'Abstract_Base' );
		$method = $this->find_entity_data_in( $class, 'methods', 'concrete_method' );
		$this->assertIsArray( $method );
		$this->assertFalse( $method['abstract'] );
	}

	/**
	 * Test that final classes are exported with the final flag.
	 */
	public function test_final_class() {

		$class = $this->find_entity_data_in( $this->export_data, 'classes', 'Final_Class' );
		$this->assertIsArray( $class );
		$this->assertTrue( $class['final'], 'Final_Class should be marked final' );
		$this->assertFalse( $class['abstract'], 'Final_Class should not be marked abstract' );
	}

	/**
	 * Test that final methods are exported with the final flag.
	 */
	public function test_final_method() {

		$class  = $this->find_entity_data_in( $this->export_data, 'classes', 'Final_Class' );
		$method = $this->find_entity_data_in( $class, 'methods', 'final_method' );
		$this->assertIsArray( $method );
		$this->assertTrue( $method['final'], 'final_method should be marked final' );
	}

	/**
	 * Test that regular methods in final class are not final.
	 */
	public function test_regular_method_in_final_class() {

		$class  = $this->find_entity_data_in( $this->export_data, 'classes', 'Final_Class' );
		$method = $this->find_entity_data_in( $class, 'methods', 'regular_method' );
		$this->assertIsArray( $method );
		$this->assertFalse( $method['final'] );
	}

	/**
	 * Test that static properties are exported with the static flag.
	 */
	public function test_static_property() {

		$class = $this->find_entity_data_in( $this->export_data, 'classes', 'Static_Members' );
		$this->assertIsArray( $class );

		$prop = $this->find_entity_data_in( $class, 'properties', '$counter' );
		$this->assertIsArray( $prop );
		$this->assertTrue( $prop['static'], '$counter should be static' );
		$this->assertEquals( 'public', $prop['visibility'] );
	}

	/**
	 * Test that static methods are exported with the static flag.
	 */
	public function test_static_method() {

		$class  = $this->find_entity_data_in( $this->export_data, 'classes', 'Static_Members' );
		$method = $this->find_entity_data_in( $class, 'methods', 'increment' );
		$this->assertIsArray( $method );
		$this->assertTrue( $method['static'], 'increment should be static' );
		$this->assertEquals( 'public', $method['visibility'] );
	}

	/**
	 * Test that protected static methods are exported correctly.
	 */
	public function test_protected_static_method() {

		$class  = $this->find_entity_data_in( $this->export_data, 'classes', 'Static_Members' );
		$method = $this->find_entity_data_in( $class, 'methods', 'reset' );
		$this->assertIsArray( $method );
		$this->assertTrue( $method['static'] );
		$this->assertEquals( 'protected', $method['visibility'] );
	}

	/**
	 * Test public property visibility.
	 */
	public function test_public_property() {

		$class = $this->find_entity_data_in( $this->export_data, 'classes', 'Property_Showcase' );
		$prop  = $this->find_entity_data_in( $class, 'properties', '$pub_prop' );
		$this->assertIsArray( $prop );
		$this->assertEquals( 'public', $prop['visibility'] );
		$this->assertEquals( "'hello'", $prop['default'] );
	}

	/**
	 * Test protected property visibility.
	 */
	public function test_protected_property() {

		$class = $this->find_entity_data_in( $this->export_data, 'classes', 'Property_Showcase' );
		$prop  = $this->find_entity_data_in( $class, 'properties', '$prot_prop' );
		$this->assertIsArray( $prop );
		$this->assertEquals( 'protected', $prop['visibility'] );
	}

	/**
	 * Test private property visibility.
	 */
	public function test_private_property() {

		$class = $this->find_entity_data_in( $this->export_data, 'classes', 'Property_Showcase' );
		$prop  = $this->find_entity_data_in( $class, 'properties', '$priv_prop' );
		$this->assertIsArray( $prop );
		$this->assertEquals( 'private', $prop['visibility'] );
		$this->assertEquals( '42', $prop['default'] );
	}

	/**
	 * Test property with array default.
	 */
	public function test_array_default_property() {

		$class = $this->find_entity_data_in( $this->export_data, 'classes', 'Property_Showcase' );
		$prop  = $this->find_entity_data_in( $class, 'properties', '$arr_default' );
		$this->assertIsArray( $prop );
		$this->assertNotEmpty( $prop['default'] );
	}

	/**
	 * Test property with null default.
	 */
	public function test_null_default_property() {

		$class = $this->find_entity_data_in( $this->export_data, 'classes', 'Property_Showcase' );
		$prop  = $this->find_entity_data_in( $class, 'properties', '$null_default' );
		$this->assertIsArray( $prop );
		$this->assertEquals( 'null', $prop['default'] );
	}

	/**
	 * Test property with bool default.
	 */
	public function test_bool_default_property() {

		$class = $this->find_entity_data_in( $this->export_data, 'classes', 'Property_Showcase' );
		$prop  = $this->find_entity_data_in( $class, 'properties', '$bool_default' );
		$this->assertIsArray( $prop );
		$this->assertEquals( 'true', $prop['default'] );
	}

	/**
	 * Test that a class using a trait is exported.
	 */
	public function test_trait_user_class() {

		$class = $this->find_entity_data_in( $this->export_data, 'classes', 'Trait_User' );
		$this->assertIsArray( $class );

		$method = $this->find_entity_data_in( $class, 'methods', 'own_method' );
		$this->assertIsArray( $method, 'own_method should be exported' );
	}

	/**
	 * Test that closures in methods are parsed without error.
	 */
	public function test_closures_in_method() {

		$class  = $this->find_entity_data_in( $this->export_data, 'classes', 'Closure_Class' );
		$this->assertIsArray( $class );

		$method = $this->find_entity_data_in( $class, 'methods', 'with_closures' );
		$this->assertIsArray( $method, 'with_closures method should be exported' );
	}
}
