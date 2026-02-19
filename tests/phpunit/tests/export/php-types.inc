<?php

/**
 * Function with nullable and union argument types.
 *
 * @param string|int $id      An ID.
 * @param string     $label   A label.
 * @param ?int       $timeout Optional timeout.
 * @return string|false The result, or false on failure.
 */
function test_union_types( string|int $id, string $label, ?int $timeout = null ): string|false {
	return false;
}

/**
 * Function with typed arguments and return type.
 *
 * @param int    $count The count.
 * @param string $name  The name.
 * @return bool Whether it worked.
 */
function test_typed_args( int $count, string $name ): bool {
	return true;
}

/**
 * A class with typed properties.
 */
class Typed_Props_Class {

	/**
	 * A typed string property.
	 *
	 * @var string
	 */
	public string $title = '';

	/**
	 * A nullable typed property.
	 *
	 * @var int|null
	 */
	public ?int $count = null;

	/**
	 * Method with typed arguments and a union return type.
	 *
	 * @param string   $key   The key to look up.
	 * @param int|null $limit Optional limit.
	 * @return array|null The result, or null if not found.
	 */
	public function find( string $key, ?int $limit = null ): array|null {
		return null;
	}
}