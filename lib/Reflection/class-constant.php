<?php
/**
 * Constant
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use WP_Parser\Attributes\Serialized_Name;
use WP_Parser\Formatter\Templated_String;

/**
 * Class Constant
 */
class Constant {

	#[Serialized_Name( 'name' )]
	private string|Templated_String|null $name = null;

	#[Serialized_Name( 'line' )]
	private ?int $line = null;

	#[Serialized_Name( 'value' )]
	private string|Templated_String|null $value = null;

	/**
	 * @return string
	 */
	public function get_name(): string|Templated_String|null {
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function set_name( string|Templated_String $name ): void {
		$this->name = $name;
	}

	/**
	 * @return string|Templated_String|null
	 */
	public function get_value(): string|Templated_String|null {
		return $this->value;
	}

	/**
	 * @param string|Templated_String $value
	 */
	public function set_value( string|Templated_String $value ): void {
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
