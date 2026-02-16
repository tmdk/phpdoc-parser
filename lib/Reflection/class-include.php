<?php
/**
 * Include_
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use WP_Parser\Attributes\Serialized_Name;

/**
 * Class Include_
 */
class Include_ {

	/**
	 * Name. Always empty.
	 *
	 * @var string
	 */
	#[Serialized_Name( 'name' )]
	private string $name = '';

	/**
	 * Starting line.
	 *
	 * @var int
	 */
	#[Serialized_Name( 'line' )]
	private int $line = 0;

	/**
	 * Include Type. One of 'Include', 'Include Once', 'Require', 'Require Once'.
	 *
	 * @var string
	 */
	#[Serialized_Name( 'type' )]
	private string $type = '';

	/**
	 * Include_ constructor.
	 *
	 * @param int    $line Line.
	 * @param string $name Name.
	 * @param string $type Type.
	 */
	public function __construct( int $line, string $name, string $type ) {
		$this->line = $line;
		$this->name = $name;
		$this->type = $type;
	}

	/**
	 * @return int
	 */
	public function get_line(): int {
		return $this->line;
	}

	/**
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

}
