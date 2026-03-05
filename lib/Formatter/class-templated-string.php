<?php
/**
 * Templated_String
 *
 * @package WP_Parser\Formatter
 */

namespace WP_Parser\Formatter;

use WP_Parser\Reflection\Name;

class Templated_String {

	/**
	 * @param string $template The template string with {n} placeholders.
	 * @param Name[] $names The name references corresponding to placeholders.
	 */
	public function __construct(
		private readonly string $template,
		private readonly array $names = [],
	) {
	}

	public static function from_name( Name $name ): self {
		$placeholder = self::placeholder( $name );

		return new self( $placeholder, [ $placeholder => $name ] );
	}

	public static function placeholder( Name $name ): string {
		return '{WP_PHPDOC_PARSER_' . md5( json_encode( $name ) ) . '}';
	}

	/**
	 * Resolve the template by replacing placeholders with the result of $resolver.
	 *
	 * @param callable(Name): string $resolver
	 */
	public function resolve( callable $resolver ): string {
		if ( empty( $this->names ) ) {
			return $this->template;
		}

		return preg_replace_callback(
			'/\{WP_PHPDOC_PARSER_[a-f0-9]{32}}/',
			fn( array $m ) => $resolver( $this->names[ $m[0] ] ),
			$this->template
		);
	}

	/**
	 * Whether this templated string contains any name references.
	 */
	public function has_names(): bool {
		return ! empty( $this->names );
	}

	/**
	 * Get the raw template string.
	 */
	public function get_template(): string {
		return $this->template;
	}

	/**
	 * Default string conversion: return name as-is.
	 */
	public function __toString(): string {
		return $this->resolve( static fn( Name $name ): string => $name->name );
	}
}
