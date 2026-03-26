<?php
/**
 * A parent test case class for the data export tests.
 *
 * @package WP_Parser\Tests
 */

namespace WP_Parser\Tests;

use WP_Parser\Parser;

use WP_Parser\Serializer\Serializer;
use WP_Parser\Source_File;

/**
 * Parent test case for data export tests.
 */
class Export_UnitTestCase extends \WP_UnitTestCase {

	/**
	 * Parser.
	 *
	 * @var Parser
	 */
	private Parser $parser;
	/**
	 * Serializer.
	 *
	 * @var Serializer
	 */
	private Serializer $serializer;

	protected function parse_string( string $php ) {
		$caller = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 )[1]['function'];
		if ( ! str_starts_with( $php, '<?php' ) ) {
			$php = '<?php ' . $php;
		}
		$source_file = Source_File::from_string( "$caller.php", $php );

		return $this->serializer->serialize( $this->parser->parse_file( $source_file ) );
	}

	/**
	 * Set up before each test.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->parser     = new Parser();
		$this->serializer = new Serializer();
	}

	protected function assertArrayPathEquals( array $array, string $path, $expected ): void {
		$keys        = explode( '.', $path );
		$path_exists = true;

		$traversed = [];
		$key       = null;
		$value     = &$array;

		foreach ( $keys as $key ) {
			if ( ! key_exists( $key, $value ) ) {
				$path_exists = false;
				break;
			}

			$value       = &$value[ $key ];
			$traversed[] = $key;
		}

		if ( ! $path_exists ) {
			$key_description = ctype_digit( $key ) ? "index $key" : "key \"$key\"";
			$this->fail(
				"Array does not contain path $path: no $key_description in " .
				( $traversed ? implode( '.', $traversed ) : 'array' ) . ":\n" .
				json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
			);
		}

		$this->assertEquals( $expected, $value );
	}

	/**
	 * Assert that an entity uses another entity.
	 *
	 * @param array  $entity The exported entity data.
	 * @param string $type The type of thing that this entity should use.
	 * @param array  $used The expected data for the thing the entity should use.
	 */
	private function assertEntityUses( array $entity, string $type, array $used ): void {

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
	private function assertEntityNotUses( array $entity, string $type, array $used ): void {

		if ( $this->entity_uses( $entity, $type, $used ) ) {

			$name = $entity['path'] ?? $entity['name'];

			$this->fail( "Matching $type used by $name." );
		}
	}

	/**
	 * Assert that an entity uses a function.
	 *
	 * @param array $entity The exported entity data.
	 * @param array $used   The expected data for the used function.
	 */
	protected function assertEntityUsesFunction( array $entity, array $used ): void {
		$this->assertEntityUses( $entity, 'functions', $used );
	}

	/**
	 * Assert that an entity doesn't use a function.
	 *
	 * @param array $entity The exported entity data.
	 * @param array $used   The expected data for the function that should not be used.
	 */
	protected function assertEntityNotUsesFunction( array $entity, array $used ): void {
		$this->assertEntityNotUses( $entity, 'functions', $used );
	}

	/**
	 * Assert that an entity uses a method.
	 *
	 * @param array $entity The exported entity data.
	 * @param array $used   The expected data for the used method.
	 */
	protected function assertEntityUsesMethod( array $entity, array $used ): void {
		$this->assertEntityUses( $entity, 'methods', $used );
	}

	/**
	 * Assert that an entity doesn't use a method.
	 *
	 * @param array $entity The exported entity data.
	 * @param array $used   The expected data for the method that should not be used.
	 */
	protected function assertEntityNotUsesMethod( array $entity, array $used ): void {
		$this->assertEntityNotUses( $entity, 'methods', $used );
	}

	/**
	 * Find the exported data for an entity, optionally traversing nested levels.
	 *
	 * Accepts one or more type/name pairs to traverse into nested entities.
	 *
	 * Single level:  find_entity_data_in( $data, 'functions', 'my_func' )
	 * Two levels:    find_entity_data_in( $data, 'classes', 'Foo', 'methods', 'bar' )
	 *
	 * @param array  $data    The data to search in.
	 * @param string ...$path Alternating type and name pairs (e.g. 'classes', 'Foo', 'methods', 'bar').
	 *
	 * @return array|false The data for the entity, or false if it couldn't be found.
	 */
	protected function find_entity_data_in( array $data, string ...$path ): bool|array {

		$pairs = array_chunk( $path, 2 );

		foreach ( $pairs as $pair ) {
			[ $type, $name ] = $pair;

			if ( empty( $data[ $type ] ) ) {
				return false;
			}

			$found = false;
			foreach ( $data[ $type ] as $entity_data ) {
				if ( $entity_data['name'] === $name ) {
					$data  = $entity_data;
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				return false;
			}
		}

		return $data;
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
	private function entity_uses( array $entity, string $type, array $used ): bool {

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
