<?php
/**
 * Namespace_
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use PhpParser\Node\Name;

/**
 * Class Namespace_
 */
class Namespace_ {

	private array $aliases = [];

	public function __construct( private string $name = 'global' ) {
	}

	/**
	 * Set the namespace.
	 *
	 * @param string $namespace
	 *
	 * @return void
	 */
	public function set_name( string $namespace ): void {
		$this->name = $namespace;
	}

	/**
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Add a namespace alias.
	 *
	 * @param string $alias_name The alias name (case-insensitive lookup key).
	 * @param Name   $original_name The original fully qualified name.
	 *
	 * @return void
	 */
	public function add_alias( string $alias_name, Name $original_name ): void {
		$this->aliases[ $alias_name ] = '\\' . $original_name->toString();
	}

	/**
	 * @return array
	 */
	public function get_aliases(): array {
		return $this->aliases;
	}

}
