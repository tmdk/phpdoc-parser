<?php
/**
 * DocBlock
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use WP_Parser\Attributes\Serialized_Name;

/**
 * Represents a docblock.
 */
class DocBlock {
	#[Serialized_Name( 'description' )]
	private string $description = '';

	#[Serialized_Name( 'long_description' )]
	private string $long_description = '';

	/** @var Docblock_Tag[] */
	#[Serialized_Name( 'tags' )]
	private array $tags = [];

	/**
	 * Set the short description.
	 *
	 * @param string $description
	 * @return void
	 */
	public function set_description( string $description ): void {
		$this->description = $description;
	}

	/**
	 * Set the long description.
	 *
	 * @param string $long_description
	 * @return void
	 */
	public function set_long_description( string $long_description ): void {
		$this->long_description = $long_description;
	}

	/**
	 * Set the tags.
	 *
	 * @param Docblock_Tag[] $tags
	 *
	 * @return void
	 */
	public function set_tags( array $tags ): void {
		$this->tags = $tags;
	}
}
