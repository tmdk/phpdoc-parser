<?php
/**
 * A parent test case class for the data export tests.
 *
 * @package WP_Parser\Tests
 */

namespace WP_Parser\Tests;

use function WP_Parser\parse_files;

/**
 * Parent test case for data export tests.
 */
class Export_UnitTestCase extends \WP_UnitTestCase {

	/**
	 * The exported data.
	 *
	 * @var array
	 */
	protected array $export_data = [];

	/**
	 * Parse the file for the current testcase.
	 */
	protected function parse_file(): void {

		$class_reflector = new \ReflectionClass( $this );
		$file            = $class_reflector->getFileName();
		$file            = rtrim( $file, 'ph' ) . 'inc';
		$path            = dirname( $file );

		$export_data = parse_files( [ $file ], $path );

		$this->export_data = $export_data[0];
	}

	/**
	 * Parse the file to get the exported data before the first test.
	 */
	public function set_up(): void {

		parent::set_up();

		if ( ! $this->export_data ) {
			$this->parse_file();
		}
	}

	/**
	 * Assert that an entity contains another entity.
	 *
	 * @param array  $entity The exported entity data.
	 * @param string $type The type of thing that this entity should contain.
	 * @param array  $expected The expected data for the thing the entity should contain.
	 */
	protected function assertEntityContains( array $entity, string $type, array $expected ): void {

		$this->assertArrayHasKey( $type, $entity );

		foreach ( $entity[ $type ] as $exported ) {
			if ( $exported['line'] == $expected['line'] ) {
				foreach ( $expected as $key => $expected_value ) {
					$exported_value = $exported[ $key ] ?? _wp_array_get( $exported, explode( '.', $key ) );

					$this->assertEquals( $expected_value, $exported_value );
				}

				return;
			}
		}

		$this->fail( "No matching $type contained by {$entity['name']}." );
	}

	/**
	 * Assert that a file contains the declaration of a hook.
	 *
	 * @param array $hook The expected export data for the hook.
	 */
	protected function assertFileContainsHook( array $hook ): void {

		$this->assertEntityContains( $this->export_data, 'hooks', $hook );
	}

	/**
	 * Assert that an entity uses another entity.
	 *
	 * @param array  $entity The exported entity data.
	 * @param string $type The type of thing that this entity should use.
	 * @param array  $used The expected data for the thing the entity should use.
	 */
	protected function assertEntityUses( array $entity, string $type, array $used ): void {

		if ( ! $this->entity_uses( $entity, $type, $used ) ) {

			$name = $entity['path'] ?? $entity['name'];

			$this->fail( "No matching $type used by $name." );
		}
	}

	/**
	 * Assert that an entity doesn't use another entity.
	 *
	 * @param array  $entity The exported entity data.
	 * @param string $type The type of thing that this entity shouldn't use.
	 * @param array  $used The expected data for the thing the entity shouldn't use.
	 */
	protected function assertEntityNotUses( array $entity, string $type, array $used ): void {

		if ( $this->entity_uses( $entity, $type, $used ) ) {

			$name = $entity['path'] ?? $entity['name'];

			$this->fail( "Matching $type used by $name." );
		}
	}

	/**
	 * Assert that a function uses another entity.
	 *
	 * @param string $type The type of entity. E.g. 'functions', 'methods'.
	 * @param string $function_name The name of the function that uses this function.
	 * @param array  $entity The expected exported data for the used entity.
	 */
	protected function assertFunctionUses( string $type, string $function_name, array $entity ): void {

		$function_data = $this->find_entity_data_in(
			$this->export_data,
			'functions',
			$function_name
		);

		$this->assertIsArray( $function_data );
		$this->assertEntityUses( $function_data, $type, $entity );
	}

	/**
	 * Assert that a function doesn't use another entity.
	 *
	 * @param string $type The type of entity. E.g. 'functions', 'methods'.
	 * @param string $function_name The name of the function that uses this function.
	 * @param array  $entity The expected exported data for the used entity.
	 */
	protected function assertFunctionNotUses( string $type, string $function_name, array $entity ): void {

		$function_data = $this->find_entity_data_in(
			$this->export_data,
			'functions',
			$function_name
		);

		$this->assertIsArray( $function_data );
		$this->assertEntityNotUses( $function_data, $type, $entity );
	}

	/**
	 * Assert that a method uses another entity.
	 *
	 * @param string $type The type of entity. E.g. 'functions', 'methods'.
	 * @param string $class_name The name of the class that the method is used in.
	 * @param string $method_name The name of the method that uses this method.
	 * @param array  $entity The expected exported data for this entity.
	 */
	protected function assertMethodUses( string $type, string $class_name, string $method_name, array $entity ): void {

		$class_data = $this->find_entity_data_in(
			$this->export_data,
			'classes',
			$class_name
		);

		$this->assertIsArray( $class_data );

		$method_data = $this->find_entity_data_in(
			$class_data,
			'methods',
			$method_name
		);

		$this->assertIsArray( $method_data );
		$this->assertEntityUses( $method_data, $type, $entity );
	}

	/**
	 * Assert that a method doesn't use another entity.
	 *
	 * @param string $type The type of entity. E.g. 'functions', 'methods'.
	 * @param string $class_name The name of the class that the method is used in.
	 * @param string $method_name The name of the method that uses this method.
	 * @param array  $entity The expected exported data for this entity.
	 */
	protected function assertMethodNotUses( string $type, string $class_name, string $method_name, array $entity ): void {

		$class_data = $this->find_entity_data_in(
			$this->export_data,
			'classes',
			$class_name
		);

		$this->assertIsArray( $class_data );

		$method_data = $this->find_entity_data_in(
			$class_data,
			'methods',
			$method_name
		);

		$this->assertIsArray( $method_data );
		$this->assertEntityNotUses( $method_data, $type, $entity );
	}

	/**
	 * Assert that a file uses a function.
	 *
	 * @param array $function The expected export data for the function.
	 */
	protected function assertFileUsesFunction( array $function ): void {

		$this->assertEntityUses( $this->export_data, 'functions', $function );
	}

	/**
	 * Assert that a function uses another function.
	 *
	 * @param string $function_name The name of the function that uses this function.
	 * @param array  $function The expected exported data for the used function.
	 */
	protected function assertFunctionUsesFunction( string $function_name, array $function ): void {

		$this->assertFunctionUses( 'functions', $function_name, $function );
	}

	/**
	 * Assert that a method uses a function.
	 *
	 * @param string $class_name The name of the class that the method is used in.
	 * @param string $method_name The name of the method that uses this method.
	 * @param array  $function The expected exported data for this function.
	 */
	protected function assertMethodUsesFunction( string $class_name, string $method_name, array $function ): void {

		$this->assertMethodUses( 'functions', $class_name, $method_name, $function );
	}

	/**
	 * Assert that a file uses a function.
	 *
	 * @param array $function The expected export data for the function.
	 */
	protected function assertFileNotUsesFunction( array $function ): void {

		$this->assertEntityNotUses( $this->export_data, 'functions', $function );
	}

	/**
	 * Assert that a function uses another function.
	 *
	 * @param string $function_name The name of the function that uses this function.
	 * @param array  $function The expected exported data for the used function.
	 */
	protected function assertFunctionNotUsesFunction( string $function_name, array $function ): void {

		$this->assertFunctionNotUses( 'functions', $function_name, $function );
	}

	/**
	 * Assert that a method uses a function.
	 *
	 * @param string $class_name The name of the class that the method is used in.
	 * @param string $method_name The name of the method that uses this method.
	 * @param array  $function The expected exported data for this function.
	 */
	protected function assertMethodNotUsesFunction( string $class_name, string $method_name, array $function ): void {

		$this->assertMethodNotUses( 'functions', $class_name, $method_name, $function );
	}

	/**
	 * Assert that a file uses an method.
	 *
	 * @param array $method The expected export data for the method.
	 */
	protected function assertFileUsesMethod( array $method ): void {

		$this->assertEntityUses( $this->export_data, 'methods', $method );
	}

	/**
	 * Assert that a function uses a method.
	 *
	 * @param string $function_name The name of the function that uses this method.
	 * @param array  $method The expected exported data for this method.
	 */
	protected function assertFunctionUsesMethod( string $function_name, array $method ): void {

		$this->assertFunctionUses( 'methods', $function_name, $method );
	}

	/**
	 * Assert that a method uses a method.
	 *
	 * @param string $class_name The name of the class that the method is used in.
	 * @param string $method_name The name of the method that uses this method.
	 * @param array  $method The expected exported data for this method.
	 */
	protected function assertMethodUsesMethod( string $class_name, string $method_name, array $method ): void {

		$this->assertMethodUses( 'methods', $class_name, $method_name, $method );
	}

	/**
	 * Assert that a file uses an method.
	 *
	 * @param array $method The expected export data for the method.
	 */
	protected function assertFileNotUsesMethod( array $method ): void {

		$this->assertEntityNotUses( $this->export_data, 'methods', $method );
	}

	/**
	 * Assert that a function uses a method.
	 *
	 * @param string $function_name The name of the function that uses this method.
	 * @param array  $method The expected exported data for this method.
	 */
	protected function assertFunctionNotUsesMethod( string $function_name, array $method ): void {

		$this->assertFunctionNotUses( 'methods', $function_name, $method );
	}

	/**
	 * Assert that a method uses a method.
	 *
	 * @param string $class_name The name of the class that the method is used in.
	 * @param string $method_name The name of the method that uses this method.
	 * @param array  $method The expected exported data for this method.
	 */
	protected function assertMethodNotUsesMethod( string $class_name, string $method_name, array $method ): void {

		$this->assertMethodNotUses( 'methods', $class_name, $method_name, $method );
	}

	/**
	 * Assert that an entity has a docblock.
	 *
	 * @param array  $entity The exported entity data.
	 * @param array  $docs The expected data for the entity's docblock.
	 * @param string $doc_key The key in the entity array that should hold the docs.
	 */
	protected function assertEntityHasDocs( array $entity, array $docs, string $doc_key = 'doc' ): void {

		$this->assertArrayHasKey( $doc_key, $entity );

		foreach ( $docs as $key => $expected_value ) {
			$this->assertEquals( $expected_value, $entity[ $doc_key ][ $key ] );
		}
	}

	/**
	 * Assert that a file has a docblock.
	 *
	 * @param array $docs The expected data for the file's docblock.
	 */
	protected function assertFileHasDocs( array $docs ): void {

		$this->assertEntityHasDocs( $this->export_data, $docs, 'file' );
	}

	/**
	 * Assert that a function has a docblock.
	 *
	 * @param string $func The function name.
	 * @param array  $docs The expected data for the function's docblock.
	 */
	protected function assertFunctionHasDocs( string $func, array $docs ): void {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', $func );
		$this->assertEntityHasDocs( $func, $docs );
	}

	/**
	 * Assert that a class has a docblock.
	 *
	 * @param string $class The class name.
	 * @param array  $docs The expected data for the class's docblock.
	 */
	protected function assertClassHasDocs( string $class, array $docs ): void {

		$class = $this->find_entity_data_in( $this->export_data, 'classes', $class );
		$this->assertEntityHasDocs( $class, $docs );
	}

	/**
	 * Assert that a method has a docblock.
	 *
	 * @param string $class The name of the class that the method is used in.
	 * @param string $method The method name.
	 * @param array  $docs The expected data for the method's docblock.
	 */
	protected function assertMethodHasDocs( string $class, string $method, array $docs ): void {

		$class = $this->find_entity_data_in( $this->export_data, 'classes', $class );
		$this->assertIsArray( $class );

		$method = $this->find_entity_data_in( $class, 'methods', $method );
		$this->assertEntityHasDocs( $method, $docs );
	}

	/**
	 * Assert that a property has a docblock.
	 *
	 * @param string $class The name of the class that the method is used in.
	 * @param string $property The property name.
	 * @param array  $docs The expected data for the property's docblock.
	 */
	protected function assertPropertyHasDocs( string $class, string $property, array $docs ): void {

		$class = $this->find_entity_data_in( $this->export_data, 'classes', $class );
		$this->assertIsArray( $class );

		$property = $this->find_entity_data_in( $class, 'properties', $property );
		$this->assertEntityHasDocs( $property, $docs );
	}

	/**
	 * Assert that a hook has a docblock.
	 *
	 * @param string $hook The hook name.
	 * @param array  $docs The expected data for the hook's docblock.
	 */
	protected function assertHookHasDocs( string $hook, array $docs ): void {

		$hook = $this->find_entity_data_in( $this->export_data, 'hooks', $hook );
		$this->assertEntityHasDocs( $hook, $docs );
	}

	/**
	 * Find the exported data for an entity.
	 *
	 * @param array  $data The data to search in.
	 * @param string $type The type of entity.
	 * @param        $entity
	 *
	 * @return array|false The data for the entity, or false if it couldn't be found.
	 */
	protected function find_entity_data_in( array $data, string $type, $entity ): bool|array {

		if ( empty( $data[ $type ] ) ) {
			return false;
		}

		foreach ( $data[ $type ] as $entity_data ) {
			if ( $entity_data['name'] === $entity ) {
				return $entity_data;
			}
		}

		return false;
	}

	/**
	 * Check if one entity uses another entity.
	 *
	 * @param array  $entity The exported entity data.
	 * @param string $type The type of thing that this entity should use.
	 * @param array  $used The expected data for the thing the entity should use.
	 *
	 * @return bool Whether the entity uses the other.
	 */
	function entity_uses( array $entity, string $type, array $used ): bool {

		if ( ! isset( $entity['uses'][ $type ] ) ) {
			return false;
		}

		foreach ( $entity['uses'][ $type ] as $exported_used ) {
			if ( $exported_used['line'] == $used['line'] ) {
				$this->assertEquals( $used, $exported_used );

				return true;
			}
		}

		return false;
	}
}
