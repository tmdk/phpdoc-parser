<?php
/**
 * Function_
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use WP_Parser\Attributes\Serialized_Name;

/**
 * Represents a function.
 */
class Function_ implements Has_Uses, Has_Hooks {
	#[Serialized_Name( 'name' )]
	private string $name;

	#[Serialized_Name( 'namespace' )]
	private string $namespace = 'global';

	#[Serialized_Name( 'aliases' )]
	private array $aliases = [];

	#[Serialized_Name( 'line' )]
	private int $line;

	#[Serialized_Name( 'end_line' )]
	private int $end_line;

	/** @var Param[] */
	#[Serialized_Name( 'arguments' )]
	private array $arguments = [];

	#[Serialized_Name( 'doc' )]
	private ?DocBlock $doc_block = null;

	/** @var Hook[] */
	#[Serialized_Name( 'hooks' )]
	private array $hooks = [];

	#[Serialized_Name( 'uses' )]
	private ?Uses $uses = null;

	/**
	 * Set the function name.
	 *
	 * @param string $name
	 *
	 * @return void
	 */
	public function set_name( string $name ): void {
		$this->name = $name;
	}

	/**
	 * Set the namespace.
	 *
	 * @param string $namespace
	 *
	 * @return void
	 */
	public function set_namespace( string $namespace ): void {
		$this->namespace = $namespace;
	}

	/**
	 * Set the namespace aliases.
	 *
	 * @param array $aliases
	 *
	 * @return void
	 */
	public function set_aliases( array $aliases ): void {
		$this->aliases = $aliases;
	}

	/**
	 * Set the line number.
	 *
	 * @param int $line
	 *
	 * @return void
	 */
	public function set_line( int $line ): void {
		$this->line = $line;
	}

	/**
	 * Set the end line number.
	 *
	 * @param int $end_line
	 *
	 * @return void
	 */
	public function set_end_line( int $end_line ): void {
		$this->end_line = $end_line;
	}

	/**
	 * Set the arguments.
	 *
	 * @param Param[] $arguments
	 *
	 * @return void
	 */
	public function set_arguments( array $arguments ): void {
		$this->arguments = $arguments;
	}

	/**
	 * Set the docblock.
	 *
	 * @param DocBlock|null $doc_block
	 *
	 * @return void
	 */
	public function set_doc_block( ?DocBlock $doc_block ): void {
		$this->doc_block = $doc_block;
	}

	/**
	 * Set the uses.
	 *
	 * @param Uses|null $uses
	 *
	 * @return void
	 */
	public function set_uses( ?Uses $uses ): void {
		$this->uses = $uses;
	}

	/**
	 * Get or create the Uses object.
	 *
	 * @return Uses
	 */
	public function get_uses(): Uses {
		return $this->uses ??= new Uses();
	}

	/**
	 * Add a hook.
	 *
	 * @param Hook $hook
	 *
	 * @return void
	 */
	public function add_hook( Hook $hook ): void {
		$this->hooks[] = $hook;
	}
}
