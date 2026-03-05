<?php
/**
 * Name
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use PhpParser\Node;

class Name {

	public function __construct(
		public readonly string $name,
		public readonly bool $fully_qualified = false,
		public readonly array $context = []
	) {
	}

	public static function from( Node\Name $node, array $context = [] ): Name {
		$name = $node->toString();

		return new self( $name, $name instanceof Node\Name\FullyQualified, $context );
	}

	public function has_context( ...$context ): bool {
		for ( $i = 0; $i < count( $context ); $i ++ ) {
			if ( ( $this->context[ $i ] ?? null ) !== $context[ $i ] ) {
				return false;
			}
		}

		return true;
	}

	public function get_namespace(): string {
		if ( str_contains( $this->name, '\\' ) ) {
			return substr( $this->name, 0, strrpos( $this->name, '\\' ) );
		}

		return 'global';
	}

	public function is_fully_qualified(): bool {
		return $this->fully_qualified;
	}

	public function get_fully_qualified_name(): string {
		return '\\' . $this->name;
	}
}
