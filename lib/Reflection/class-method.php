<?php
/**
 * Method
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use WP_Parser\Attributes\Serialized_Name;

/**
 * Represents a class method.
 */
class Method implements Has_Uses, Has_Hooks {
	#[Serialized_Name( 'name' )]
	private string $name;

	#[Serialized_Name( 'namespace' )]
	private string $namespace = '';

	#[Serialized_Name( 'aliases' )]
	private array $aliases = [];

	#[Serialized_Name( 'line' )]
	private int $line;

	#[Serialized_Name( 'end_line' )]
	private int $end_line;

	#[Serialized_Name( 'final' )]
	private bool $final = false;

	#[Serialized_Name( 'abstract' )]
	private bool $abstract = false;

	#[Serialized_Name( 'static' )]
	private bool $static = false;

	#[Serialized_Name( 'visibility' )]
	private string $visibility;

	/** @var Param[] */
	#[Serialized_Name( 'arguments' )]
	private array $arguments = [];

	#[Serialized_Name( 'doc' )]
	private ?DocBlock $doc_block = null;

	#[Serialized_Name( 'uses' )]
	private ?Uses $uses = null;

	/** @var Hook[]|null */
	#[Serialized_Name( 'hooks' )]
	private ?array $hooks = null;

	/**
	 * Set the method name.
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
	 * Set whether the method is final.
	 *
	 * @param bool $final
	 *
	 * @return void
	 */
	public function set_final( bool $final ): void {
		$this->final = $final;
	}

	/**
	 * Set whether the method is abstract.
	 *
	 * @param bool $abstract
	 *
	 * @return void
	 */
	public function set_abstract( bool $abstract ): void {
		$this->abstract = $abstract;
	}

	/**
	 * Set whether the method is static.
	 *
	 * @param bool $static
	 *
	 * @return void
	 */
	public function set_static( bool $static ): void {
		$this->static = $static;
	}

	/**
	 * Set the visibility.
	 *
	 * @param string $visibility
	 *
	 * @return void
	 */
	public function set_visibility( string $visibility ): void {
		$this->visibility = $visibility;
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

	public function get_uses(): ?Uses {
		return $this->uses;
	}

	public function add_function_use( Function_Call $function_call ): void {
		$this->uses ??= new Uses();
		$this->uses->add_function( $function_call );
	}

	public function add_method_use( Method_Call $method_call ): void {
		$this->uses ??= new Uses();
		$this->uses->add_method( $method_call );
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

	public function get_name(): string {
		return $this->name;
	}

	public function get_namespace(): string {
		return $this->namespace;
	}

	public function get_aliases(): array {
		return $this->aliases;
	}

	public function get_line(): int {
		return $this->line;
	}

	public function get_end_line(): int {
		return $this->end_line;
	}

	public function is_final(): bool {
		return $this->final;
	}

	public function is_abstract(): bool {
		return $this->abstract;
	}

	public function is_static(): bool {
		return $this->static;
	}

	public function get_visibility(): string {
		return $this->visibility;
	}

	public function get_arguments(): array {
		return $this->arguments;
	}

	public function get_doc_block(): ?DocBlock {
		return $this->doc_block;
	}

	public function get_hooks(): ?array {
		return $this->hooks;
	}
}
