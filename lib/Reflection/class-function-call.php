<?php
/**
 * Function_Call
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use WP_Parser\Attributes\Serialized_Name;

/**
 * Represents a function call.
 */
class Function_Call {
	#[Serialized_Name( 'name' )]
	private string $name;

	#[Serialized_Name( 'line' )]
	private int $line;

	#[Serialized_Name( 'end_line' )]
	private int $end_line;

	#[Serialized_Name( 'deprecation_version' )]
	private ?string $deprecation_version = null;

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
	 * @return string|null
	 */
	public function get_deprecation_version(): ?string {
		return $this->deprecation_version;
	}

	/**
	 * @param string|null $deprecation_version
	 */
	public function set_deprecation_version( ?string $deprecation_version ): void {
		$this->deprecation_version = $deprecation_version;
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
}
