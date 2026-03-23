<?php
/**
 * Hook
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use WP_Parser\Attributes\Serialized_Name;

/**
 * Represents a WordPress hook (action or filter).
 */
class Hook {

	public const FUNCTIONS = [
		'apply_filters',
		'apply_filters_ref_array',
		'apply_filters_deprecated',
		'do_action',
		'do_action_ref_array',
		'do_action_deprecated',
	];

	#[Serialized_Name( 'name' )]
	private string $name;

	#[Serialized_Name( 'line' )]
	private int $line;

	#[Serialized_Name( 'end_line' )]
	private int $end_line;

	#[Serialized_Name( 'type' )]
	private string $type;

	/** @var array */
	#[Serialized_Name( 'arguments' )]
	private array $arguments = [];

	#[Serialized_Name( 'doc' )]
	private ?DocBlock $doc_block = null;

	/**
	 * Set the hook name.
	 *
	 * @param string $name
	 * @return void
	 */
	public function set_name( string $name ): void {
		$this->name = $name;
	}

	/**
	 * Set the hook type.
	 *
	 * @param string $type
	 * @return void
	 */
	public function set_type( string $type ): void {
		$this->type = $type;
	}

	/**
	 * Set the line number.
	 *
	 * @param int $line
	 * @return void
	 */
	public function set_line( int $line ): void {
		$this->line = $line;
	}

	/**
	 * Set the end line number.
	 *
	 * @param int $end_line
	 * @return void
	 */
	public function set_end_line( int $end_line ): void {
		$this->end_line = $end_line;
	}

	/**
	 * Set the arguments.
	 *
	 * @param array $arguments
	 * @return void
	 */
	public function set_arguments( array $arguments ): void {
		$this->arguments = $arguments;
	}

	/**
	 * Set the docblock.
	 *
	 * @param DocBlock|null $doc_block
	 * @return void
	 */
	public function set_doc_block( ?DocBlock $doc_block ): void {
		$this->doc_block = $doc_block;
	}

	public function get_name(): string {
		return $this->name;
	}

	public function get_line(): int {
		return $this->line;
	}

	public function get_end_line(): int {
		return $this->end_line;
	}

	public function get_type(): string {
		return $this->type;
	}

	public function get_arguments(): array {
		return $this->arguments;
	}

	public function get_doc_block(): ?DocBlock {
		return $this->doc_block;
	}
}
