<?php
/**
 * Docblock_Tag
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Type;
use WP_Parser\Attributes\Serialized_Name;
use WP_Parser\Reference\Reference;

/**
 * Represents a docblock tag.
 */
class Docblock_Tag {
	#[Serialized_Name( 'name' )]
	private string $name;

	#[Serialized_Name( 'content' )]
	private ?string $content = null;

	#[Serialized_Name( 'types' )]
	private ?Type $type = null;

	#[Serialized_Name( 'variable' )]
	private ?string $variable = null;

	#[Serialized_Name( 'description' )]
	private ?string $description = null;

	#[Serialized_Name( 'refers' )]
	private string|Fqsen|Reference|null $reference = null;

	#[Serialized_Name( 'link' )]
	private ?string $link = null;

	private ?bool $is_invalid = false;

	/**
	 * Set the tag name.
	 *
	 * @param string $name
	 *
	 * @return void
	 */
	public function set_name( string $name ): void {
		$this->name = $name;
	}

	/**
	 * Set the content.
	 *
	 * @param string $content
	 *
	 * @return void
	 */
	public function set_content( string $content ): void {
		$this->content = $content;
	}

	/**
	 * Set the types.
	 *
	 * @param Type $type
	 *
	 * @return void
	 */
	public function set_type( Type $type ): void {
		$this->type = $type;
	}

	/**
	 * Set the variable name.
	 *
	 * @param string $variable
	 *
	 * @return void
	 */
	public function set_variable( string $variable ): void {
		$this->variable = $variable;
	}

	/**
	 * Set the link.
	 *
	 * @param string $link
	 *
	 * @return void
	 */
	public function set_link( string $link ): void {
		$this->link = $link;
	}

	/**
	 * Set the reference.
	 *
	 * @param string|Fqsen|Reference $reference
	 *
	 * @return void
	 */
	public function set_reference( string|Fqsen|Reference $reference ): void {
		$this->reference = $reference;
	}

	/**
	 * Set the description.
	 *
	 * @param string $description
	 *
	 * @return void
	 */
	public function set_description( string $description ): void {
		$this->description = $description;
	}

	/**
	 * @return string|null
	 */
	public function get_content(): ?string {
		return $this->content;
	}

	/**
	 * @return string|null
	 */
	public function get_description(): ?string {
		return $this->description;
	}

	/**
	 * @return string|null
	 */
	public function get_link(): ?string {
		return $this->link;
	}

	/**
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * @return string|Fqsen|Reference|null
	 */
	public function get_reference(): string|Fqsen|Reference|null {
		return $this->reference;
	}

	/**
	 * @return Type|null
	 */
	public function get_type(): ?Type {
		return $this->type;
	}

	/**
	 * @return string|null
	 */
	public function get_variable(): ?string {
		return $this->variable;
	}

	/**
	 * @return bool|null
	 */
	public function is_invalid(): ?bool {
		return $this->is_invalid;
	}

	/**
	 * @param bool|null $is_invalid
	 */
	public function set_is_invalid( ?bool $is_invalid ): void {
		$this->is_invalid = $is_invalid;
	}


}
