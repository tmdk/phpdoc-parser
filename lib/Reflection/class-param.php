<?php
/**
 * Param
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use WP_Parser\Attributes\Serialize_Null;
use WP_Parser\Attributes\Serialized_Name;
use WP_Parser\Formatter\Templated_String;

/**
 * Represents a function or method parameter.
 */
class Param {
	#[Serialized_Name( 'name' )]
	private string $name;

	#[Serialized_Name( 'default' )]
	#[Serialize_Null]
	private string|Templated_String|null $default = null;

	#[Serialized_Name( 'type' )]
	private string|Templated_String $type = '';

	/**
	 * Set the parameter name.
	 *
	 * @param string $name
	 *
	 * @return void
	 */
	public function set_name( string $name ): void {
		$this->name = $name;
	}

	/**
	 * Set the parameter type.
	 *
	 * @param string|Templated_String $type
	 *
	 * @return void
	 */
	public function set_type( string|Templated_String $type ): void {
		$this->type = $type;
	}

	/**
	 * Set the default value.
	 *
	 * @param string|Templated_String $default
	 *
	 * @return void
	 */
	public function set_default( string|Templated_String $default ): void {
		$this->default = $default;
	}
}
