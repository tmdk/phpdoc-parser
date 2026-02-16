<?php
/**
 * Constant
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use WP_Parser\Attributes\Serialized_Name;

/**
 * Class Constant
 */
class Constant {

	#[Serialized_Name( 'name' )]
	private ?string $name = null;

	#[Serialized_Name( 'line' )]
	private ?int $line = null;

	#[Serialized_Name( 'value' )]
	private ?string $value = null;

	/**
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function set_name( string $name ): void {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function get_value(): string {
		return $this->value;
	}

	/**
	 * @param string $value
	 */
	public function set_value( string $value ): void {
		$this->value = $value;
	}

	/**
	 * @return int|null
	 */
	public function get_line(): ?int {
		return $this->line;
	}

	/**
	 * @param int $line
	 */
	public function set_line( int $line ): void {
		$this->line = $line;
	}

}
