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

/**
 * Function with intersection type parameter (PHP 8.1).
 *
 * @param Countable&Traversable $collection A countable traversable.
 */
function test_intersection_types( Countable&Traversable $collection ): void {}

/**
 * Function with DNF type parameter (PHP 8.2).
 *
 * @param (Countable&Traversable)|null $collection A nullable intersection.
 */
function test_dnf_types( (Countable&Traversable)|null $collection ): void {}

/**
 * Function with return type declaration.
 *
 * @return string The greeting.
 */
function test_return_type(): string {
	return 'hello';
}

/**
 * Function with void return type.
 */
function test_void_return(): void {}

/**
 * Function with never return type.
 */
function test_never_return(): never {
	throw new \RuntimeException();
}

class Return_Type_Class {
	/**
	 * Method with self return type.
	 */
	public function clone_self(): self {
		return clone $this;
	}

	/**
	 * Method with parent return type.
	 */
	public function get_parent(): parent {
		return parent::getInstance();
	}

	/**
	 * Method with static return type.
	 */
	public function fluent(): static {
		return $this;
	}
}

class Readonly_Props {
	/**
	 * A readonly property (PHP 8.1).
	 */
	public readonly string $name;

	/**
	 * A readonly typed property with constructor assignment.
	 */
	public readonly int $id;

	public function __construct( string $name, int $id ) {
		$this->name = $name;
		$this->id = $id;
	}
}

/**
 * Function with variadic parameter.
 *
 * @param string ...$args The arguments.
 */
function test_variadic( string ...$args ): void {}

/**
 * Constructor property promotion (PHP 8.0).
 */
class Promoted_Props {

	public function __construct(
		private string $name,
		protected int $age = 0,
		public readonly string $role = 'user',
	) {}
}