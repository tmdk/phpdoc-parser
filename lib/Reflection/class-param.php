<?php
/**
 * Param
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use WP_Parser\Attributes\Serialize_Null;
use WP_Parser\Attributes\Serialized_Name;

/**
 * Represents a function or method parameter.
 */
class Param {
	#[Serialized_Name( 'name' )]
	private string $name;

	#[Serialized_Name( 'default' )]
	#[Serialize_Null]
	private ?string $default = null;

	#[Serialized_Name( 'type' )]
	private string $type = '';

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
	 * @param string $type
	 *
	 * @return void
	 */
	public function set_type( string $type ): void {
		$this->type = $type;
	}

	/**
	 * Set the default value.
	 *
	 * @param string $default
	 *
	 * @return void
	 */
	public function set_default( string $default ): void {
		$this->default = $default;
	}
}
