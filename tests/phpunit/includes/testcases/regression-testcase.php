<?php
/**
 * Regression_Base_Test
 *
 * @package WP_Parser\Tests
 */

namespace WP_Parser\Tests;

use PHPUnit\Framework\TestCase;
use WP_Parser\Source_File;

/**
 * Base class for regression tests
 */
abstract class Regression_TestCase extends TestCase {

	private const FIXTURES_DIR  = __DIR__ . '/../../fixtures';
	private const WORDPRESS_DIR = __DIR__ . '/../../../../wordpress';
	private const BASELINE_FILE = __DIR__ . '/../../includes/baseline/baseline.php';

	private static array $parsed_files = [];
	private static ?Baseline_Applicator $applicator = null;

	/**
	 * Parse a fixture file and return it.
	 *
	 * @return array
	 */
	protected function get_fixture(): array {
		$fixture_full_path = self::FIXTURES_DIR . '/' . static::FIXTURE;

		return json_decode( file_get_contents( $fixture_full_path ), true );
	}

	public function provide_functions(): array {
		$fixture = $this->get_fixture();

		$functions = [];
		foreach ( $fixture['functions'] ?? [] as $function ) {
			$functions[ $function['name'] ] = [ $function['name'], $function ];
		}

		return $functions;
	}

	public function provide_classes(): array {
		$fixture = $this->get_fixture();

		$classes = [];
		foreach ( $fixture['classes'] ?? [] as $class ) {
			$classes[ $class['name'] ] = [ $class['name'], $class ];
		}

		return $classes;
	}

	public function provide_methods(): array {
		$fixture = $this->get_fixture();

		$methods = [];
		foreach ( $fixture['classes'] ?? [] as $class ) {
			foreach ( $class['methods'] ?? [] as $method ) {
				$methods[ $class['name'] . '::' . $method['name'] ] = [
					$class['name'],
					$method['name'],
					$method,
				];
			}
		}

		return $methods;
	}


	protected function parse_file( string $source_file ): array {
		// Check if already parsed
		if ( isset( self::$parsed_files[ $source_file ] ) ) {
			return self::$parsed_files[ $source_file ];
		}

		$parser = new \WP_Parser\Parser();
		$filename = self::WORDPRESS_DIR . '/' . $source_file;
		$file = $parser->parse_file(
			Source_File::from_string( $source_file, file_get_contents( $filename ), '/wordpress')
		);

		$serializer = new \WP_Parser\Serializer\Serializer();

		self::$parsed_files[ $source_file ] = $serializer->serialize( $file );

		return self::$parsed_files[ $source_file ];
	}

	/**
	 * Find a function in a parsed file
	 *
	 * @param array  $file
	 * @param string $function_name
	 *
	 * @return array|null
	 */
	protected function find_function( array $file, string $function_name ): ?array {
		foreach ( $file['functions'] ?? [] as $function ) {
			if ( $function['name'] === $function_name ) {
				return $function;
			}
		}

		return null;
	}

	/**
	 * Find a class in a file array
	 *
	 * @param array  $file
	 * @param string $class_name
	 *
	 * @return array|null
	 */
	protected function find_class( array $file, string $class_name ): ?array {
		foreach ( $file['classes'] ?? [] as $class ) {
			if ( $class['name'] === $class_name ) {
				return $class;
			}
		}

		return null;
	}

	/**
	 * Find a method in a parsed file
	 *
	 * @param array  $file
	 * @param string $class_name
	 * @param string $method_name
	 *
	 * @return array|null
	 */
	protected function find_method( array $file, string $class_name, string $method_name ): ?array {
		$class = $this->find_class( $file, $class_name );

		if ( $class === null ) {
			return null;
		}

		foreach ( $class['methods'] ?? [] as $method ) {
			if ( $method['name'] === $method_name ) {
				return $method;
			}
		}

		return null;
	}

	protected function apply_baseline_resolutions( string $type, string $name, array $expected, array $actual ): array {
		return $this->get_applicator()->apply_baseline_resolutions( $type, $name, $expected, $actual );
	}

	protected function apply_hook_baselines( array $expected, array $actual ): array {
		return $this->get_applicator()->apply_hook_baselines( $expected, $actual );
	}

	private function get_applicator(): Baseline_Applicator {
		if ( self::$applicator === null ) {
			self::$applicator = new Baseline_Applicator( require self::BASELINE_FILE );
		}

		return self::$applicator;
	}

}

trait Test_File_Trait {

	public function test_file(): void {
		$expected = $this->get_fixture();

		$actual = $this->parse_file( self::SOURCE_FILE );

		unset( $expected['functions'], $expected['classes'] );
		unset( $actual['functions'], $actual['classes'] );

		[ $expected, $actual ] = $this->apply_baseline_resolutions(
			'file',
			self::SOURCE_FILE,
			$expected,
			$actual
		);

		[ $expected, $actual ] = $this->apply_hook_baselines( $expected, $actual );

		$this->assertEquals( $expected, $actual );
	}

}

trait Test_Class_Trait {

	/**
	 * @dataProvider provide_classes
	 */
	public function test_class( $class_name, $expected ): void {
		$file   = $this->parse_file( self::SOURCE_FILE );
		$actual = $this->find_class( $file, $class_name );

		unset( $expected['methods'] );
		if ( $actual !== null ) {
			unset( $actual['methods'] );
		}

		[ $expected, $actual ] = $this->apply_baseline_resolutions(
			'class',
			$class_name,
			$expected,
			$actual ?? []
		);

		$this->assertEquals( $expected, $actual );
	}

}

trait Test_Function_Trait {

	/**
	 * @dataProvider provide_functions
	 */
	public function test_function( $function_name, $expected ): void {
		$file   = $this->parse_file( self::SOURCE_FILE );
		$actual = $this->find_function( $file, $function_name );

		[ $expected, $actual ] = $this->apply_baseline_resolutions(
			'function',
			$function_name,
			$expected,
			$actual ?? []
		);

		[ $expected, $actual ] = $this->apply_hook_baselines( $expected, $actual );

		$this->assertEquals( $expected, $actual );
	}

}

trait Test_Method_Trait {

	/**
	 * @dataProvider provide_methods
	 */
	public function test_method( $class_name, $method_name, $expected ): void {
		$file   = $this->parse_file( self::SOURCE_FILE );
		$actual = $this->find_method( $file, $class_name, $method_name );

		[ $expected, $actual ] = $this->apply_baseline_resolutions(
			'method',
			"$class_name::$method_name",
			$expected,
			$actual ?? []
		);

		[ $expected, $actual ] = $this->apply_hook_baselines( $expected, $actual );

		$this->assertEquals( $expected, $actual );
	}

}
