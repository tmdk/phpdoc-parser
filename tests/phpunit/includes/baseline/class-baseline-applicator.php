<?php
/**
 * Baseline_Applicator
 *
 * @package WP_Parser\Tests
 */

namespace WP_Parser\Tests;

/**
 * Applies baseline resolutions to expected/actual data pairs.
 */
class Baseline_Applicator {

	private array $baseline;
	private array $excluded_ids;

	public function __construct( array $baseline, array $excluded_ids = [] ) {
		$this->baseline     = $baseline;
		$this->excluded_ids = array_flip( $excluded_ids );
	}

	/**
	 * Get all baseline IDs across all types.
	 *
	 * @return array Sorted, deduplicated array of ['id' => ..., 'types' => [...]].
	 */
	public function get_all_ids(): array {
		$ids_map = [];

		foreach ( $this->baseline as $type => $entries ) {
			foreach ( $entries as $entry ) {
				$id = $entry['id'] ?? '';
				if ( $id === '' ) {
					continue;
				}

				if ( ! isset( $ids_map[ $id ] ) ) {
					$ids_map[ $id ] = [
						'id'    => $id,
						'types' => [],
					];
				}

				if ( ! in_array( $type, $ids_map[ $id ]['types'], true ) ) {
					$ids_map[ $id ]['types'][] = $type;
				}
			}
		}

		$result = array_values( $ids_map );
		usort( $result, fn( $a, $b ) => strcmp( $a['id'], $b['id'] ) );

		return $result;
	}

	/**
	 * Apply baseline resolutions to expected and actual values.
	 *
	 * @param string $type The baseline type (e.g., 'function', 'hook').
	 * @param string $name The name of the item being tested.
	 * @param array  $expected The expected data.
	 * @param array  $actual The actual data.
	 *
	 * @return array The modified [ $expected, $actual ] pair.
	 */
	public function apply_baseline_resolutions( string $type, string $name, array $expected, array $actual ): array {
		if ( ! isset( $this->baseline[ $type ] ) ) {
			return [ $expected, $actual ];
		}

		foreach ( $this->baseline[ $type ] as $entry ) {
			if ( isset( $entry['id'], $this->excluded_ids[ $entry['id'] ] ) ) {
				continue;
			}

			if ( isset( $entry[ $type ] ) ) {
				$matcher = $entry[ $type ];
				if ( is_callable( $matcher ) ) {
					if ( ! $matcher( $name ) ) {
						continue;
					}
				} elseif ( is_array( $matcher ) ) {
					if ( ! in_array( $name, $matcher, true ) ) {
						continue;
					}
				} elseif ( $matcher !== $name ) {
					continue;
				}
			}

			$path = isset( $entry['path'] ) ? $this->parse_path( $entry['path'] ) : [];

			[ $expected, $actual ] = $this->apply_resolution_at_path(
				[ $expected, $actual ],
				$path,
				$entry['resolution'],
				$entry['filter'] ?? null
			);
		}

		return [ $expected, $actual ];
	}

	/**
	 * Apply hook baseline resolutions to hooks nested in the given data.
	 *
	 * @param array $expected Expected data containing 'hooks' array.
	 * @param array $actual Actual data containing 'hooks' array.
	 *
	 * @return array The modified [ $expected, $actual ] pair.
	 */
	public function apply_hook_baselines( array $expected, array $actual ): array {
		if ( ! isset( $this->baseline['hook'] ) ) {
			return [ $expected, $actual ];
		}

		$expected_hooks = $expected['hooks'] ?? [];
		$actual_hooks   = $actual['hooks'] ?? [];

		// Get all hook indices from both expected and actual
		$hooks_to_process = array_unique(
			array_merge(
				array_keys( $expected_hooks ),
				array_keys( $actual_hooks )
			)
		);

		foreach ( $hooks_to_process as $index ) {
			$has_expected = array_key_exists( $index, $expected_hooks );
			$has_actual   = array_key_exists( $index, $actual_hooks );

			if ( ! $has_expected && ! $has_actual ) {
				continue;
			}

			$hook_expected = $has_expected ? $expected_hooks[ $index ] : [];
			$hook_actual   = $has_actual ? $actual_hooks[ $index ] : [];

			$hook_name = $hook_expected['name'] ?? $hook_actual['name'] ?? '';

			[ $hook_expected, $hook_actual ] = $this->apply_baseline_resolutions(
				'hook',
				$hook_name,
				$hook_expected,
				$hook_actual
			);

			if ( $has_expected ) {
				$expected_hooks[ $index ] = $hook_expected;
			}
			if ( $has_actual ) {
				$actual_hooks[ $index ] = $hook_actual;
			}
		}

		if ( ! empty( $expected_hooks ) ) {
			$expected['hooks'] = $expected_hooks;
		}
		if ( ! empty( $actual_hooks ) ) {
			$actual['hooks'] = $actual_hooks;
		}

		return [ $expected, $actual ];
	}

	/**
	 * Parse a dot-separated path string into a structured path array.
	 *
	 * @param string $path_string The path string to parse.
	 *
	 * @return array The structured path array.
	 */
	private function parse_path( string $path_string ): array {
		$path     = [];
		$segments = explode( '.', $path_string );

		foreach ( $segments as $segment ) {
			// Handle alternatives: {a|b|c}
			if ( preg_match( '/^\{([^}]+)}$/', $segment, $matches ) ) {
				$path[] = explode( '|', $matches[1] );
				continue;
			}

			// Handle key with bracket notation: foo[1] or foo[]
			if ( preg_match( '/^([a-zA-Z_][a-zA-Z0-9_]*)(\[.*)?$/', $segment, $matches ) ) {
				$key      = $matches[1];
				$brackets = $matches[2] ?? '';

				$path[] = $key;

				// Parse all bracket expressions: [1], [], [foo]
				if ( preg_match_all( '/\[([^]]*)]/', $brackets, $bracket_matches ) ) {
					foreach ( $bracket_matches[1] as $bracket_content ) {
						if ( $bracket_content === '' ) {
							// Wildcard: []
							$path[] = '*';
						} elseif ( is_numeric( $bracket_content ) ) {
							// Numeric index: [1]
							$path[] = (int) $bracket_content;
						} else {
							// String key: [foo]
							$path[] = $bracket_content;
						}
					}
				}

				continue;
			}

			// Plain segment
			$path[] = $segment;
		}

		return $path;
	}

	/**
	 * Apply a resolution callback at a specified path in paired data structures.
	 *
	 * @param array         $pair [ $expected, $actual ] pair.
	 * @param array         $path The path to traverse.
	 * @param callable      $resolution fn($expected, $actual = null): array
	 * @param callable|null $filter fn($expected, $actual = null): bool
	 *
	 * @return array The modified [ $expected, $actual ] pair.
	 */
	private function apply_resolution_at_path( array $pair, array $path, callable $resolution, ?callable $filter = null ): array {
		if ( empty( $path ) ) {
			if ( $filter !== null && ! $filter( $pair[0], $pair[1] ) ) {
				return $pair;
			}
			$result = $resolution( $pair[0], $pair[1] );

			// If only expected is returned, keep actual unchanged.
			return count( $result ) === 1 ? [ $result[0], $pair[1] ] : $result;
		}

		[ $expected, $actual ] = $pair;
		$key = array_shift( $path );

		// Determine which keys to process.
		if ( $key === '*' ) {
			// Wildcard: all keys from both arrays.
			$keys = array_unique(
				array_merge(
					is_array( $expected ) ? array_keys( $expected ) : [],
					is_array( $actual ) ? array_keys( $actual ) : []
				)
			);
		} elseif ( is_array( $key ) ) {
			// Alternatives: specified keys.
			$keys = $key;
		} else {
			// Single key.
			$keys = [ $key ];
		}

		foreach ( $keys as $k ) {
			$has_expected = is_array( $expected ) && array_key_exists( $k, $expected );
			$has_actual   = is_array( $actual ) && array_key_exists( $k, $actual );

			if ( ! $has_expected && ! $has_actual ) {
				continue;
			}

			[ $sub_expected, $sub_actual ] = $this->apply_resolution_at_path(
				[ $has_expected ? $expected[ $k ] : null, $has_actual ? $actual[ $k ] : null ],
				$path,
				$resolution,
				$filter
			);

			$expected[ $k ] = $sub_expected;
			$actual[ $k ]   = $sub_actual;
		}

		return [ $expected, $actual ];
	}
}
