<?php
/**
 * Class_
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use WP_Parser\Attributes\Serialized_Name;
use WP_Parser\Formatter\Templated_String;

/**
 * Represents a class.
 */
class Class_ {
	#[Serialized_Name( 'name' )]
	private string $name;

	#[Serialized_Name( 'namespace' )]
	private string $namespace = '';

	#[Serialized_Name( 'line' )]
	private int $line;

	#[Serialized_Name( 'end_line' )]
	private int $end_line;

	#[Serialized_Name( 'final' )]
	private bool $final = false;

	#[Serialized_Name( 'abstract' )]
	private bool $abstract = false;

	#[Serialized_Name( 'extends' )]
	private string|Templated_String $extends = '';

	#[Serialized_Name( 'implements' )]
	private array $implements = [];

	/** @var Property[] */
	#[Serialized_Name( 'properties' )]
	private array $properties = [];

	/** @var Method[] */
	#[Serialized_Name( 'methods' )]
	private array $methods = [];

	#[Serialized_Name( 'doc' )]
	private ?DocBlock $doc_block = null;

	/**
	 * Set the class name.
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
	 * Set whether the class is final.
	 *
	 * @param bool $final
	 *
	 * @return void
	 */
	public function set_final( bool $final ): void {
		$this->final = $final;
	}

	/**
	 * Set whether the class is abstract.
	 *
	 * @param bool $abstract
	 *
	 * @return void
	 */
	public function set_abstract( bool $abstract ): void {
		$this->abstract = $abstract;
	}

	/**
	 * Set the parent class.
	 *
	 * @param string|Templated_String $extends
	 *
	 * @return void
	 */
	public function set_extends( string|Templated_String $extends ): void {
		$this->extends = $extends;
	}

	/**
	 * Set the implemented interfaces.
	 *
	 * @param (string|Templated_String)[] $implements
	 *
	 * @return void
	 */
	public function set_implements( array $implements ): void {
		$this->implements = $implements;
	}

	/**
	 * Add a property.
	 *
	 * @param Property $property
	 *
	 * @return void
	 */
	public function add_property( Property $property ): void {
		$this->properties[] = $property;
	}

	/**
	 * Add a method.
	 *
	 * @param Method $method
	 *
	 * @return void
	 */
	public function add_method( Method $method ): void {
		$this->methods[] = $method;
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
	 * @return bool
	 */
	public function is_abstract(): bool {
		return $this->abstract;
	}

	/**
	 * @return DocBlock|null
	 */
	public function get_doc_block(): ?DocBlock {
		return $this->doc_block;
	}

	/**
	 * @return int
	 */
	public function get_end_line(): int {
		return $this->end_line;
	}

	/**
	 * @return string
	 */
	public function get_extends(): string|Templated_String {
		return $this->extends;
	}

	/**
	 * @return bool
	 */
	public function is_final(): bool {
		return $this->final;
	}

	/**
	 * @return array
	 */
	public function get_implements(): array {
		return $this->implements;
	}

	/**
	 * @return int
	 */
	public function get_line(): int {
		return $this->line;
	}

	/**
	 * @return array
	 */
	public function get_methods(): array {
		return $this->methods;
	}

	/**
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function get_namespace(): string {
		return $this->namespace;
	}

	/**
	 * @return array
	 */
	public function get_properties(): array {
		return $this->properties;
	}

	public function get_fully_qualified_name(): Name {
		$namespace = $this->namespace === 'global' ? '' : $this->namespace;

		return new Name( ltrim( $namespace . '\\' . $this->name, '\\' ), fully_qualified: true );
	}

}
