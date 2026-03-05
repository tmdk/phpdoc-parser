<?php
/**
 * Method_Call
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use WP_Parser\Attributes\Serialized_Name;
use WP_Parser\Formatter\Templated_String;

/**
 * Represents a method call.
 */
class Method_Call {
	#[Serialized_Name( 'name' )]
	private ?string $name = null;

	#[Serialized_Name( 'class' )]
	private string|Templated_String|null $class = null;

	#[Serialized_Name( 'static' )]
	private ?bool $static = false;

	#[Serialized_Name( 'line' )]
	private ?int $line = null;

	#[Serialized_Name( 'end_line' )]
	private ?int $end_line = null;

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
	 * Set the class name.
	 *
	 * @param string $class
	 *
	 * @return void
	 */
	public function set_class( string|Templated_String $class ): void {
		$this->class = $class;
	}

	/**
	 * Set whether the call is static.
	 *
	 * @param bool $static
	 *
	 * @return void
	 */
	public function set_static( bool $static ): void {
		$this->static = $static;
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
}
