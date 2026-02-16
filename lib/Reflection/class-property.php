<?php
/**
 * Property
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use WP_Parser\Attributes\Serialize_Null;
use WP_Parser\Attributes\Serialized_Name;

/**
 * Represents a class property.
 */
class Property {
	#[Serialized_Name( 'name' )]
	private string $name;

	#[Serialized_Name( 'line' )]
	private int $line;

	#[Serialized_Name( 'end_line' )]
	private int $end_line;

	#[Serialized_Name( 'default' )]
	#[Serialize_Null]
	private ?string $default = null;

	#[Serialized_Name( 'static' )]
	private bool $static = false;

	#[Serialized_Name( 'visibility' )]
	private string $visibility;

	#[Serialized_Name( 'doc' )]
	private ?DocBlock $doc_block = null;

	/**
	 * Set the property name.
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
	 * Set the default value.
	 *
	 * @param string $default
	 *
	 * @return void
	 */
	public function set_default( string $default ): void {
		$this->default = $default;
	}

	/**
	 * Set whether the property is static.
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
	 * Set the docblock.
	 *
	 * @param DocBlock|null $doc_block
	 *
	 * @return void
	 */
	public function set_doc_block( ?DocBlock $doc_block ): void {
		$this->doc_block = $doc_block;
	}
}
