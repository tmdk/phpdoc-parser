<?php
/**
 * Reference_Lexer
 *
 * @package WP_Parser\Reference
 */

namespace WP_Parser\Reference;

use function implode;
use function preg_match_all;
use const PREG_SET_ORDER;

/**
 * Tokenizes a @see/@uses tag value using a single preg_match_all with (*MARK) to identify token types.
 * Approach borrowed from PHPStan's phpdoc-parser Lexer.
 */
class Reference_Lexer {

	const TOKEN_URL           = 0;
	const TOKEN_SINGLE_QUOTED = 1;
	const TOKEN_DOUBLE_QUOTED = 2;
	const TOKEN_BACKTICK      = 3;
	const TOKEN_DOUBLE_COLON  = 4;
	const TOKEN_IDENTIFIER    = 5;
	const TOKEN_VARIABLE      = 6;
	const TOKEN_OPEN_PAREN    = 7;
	const TOKEN_CLOSE_PAREN   = 8;
	const TOKEN_WS            = 9;
	const TOKEN_ARROW         = 10;
	const TOKEN_OTHER         = 11;
	const TOKEN_END           = 12;

	const VALUE_OFFSET = 0;
	const TYPE_OFFSET  = 1;

	private ?string $regexp = null;

	/**
	 * @return list<array{string, int}>
	 */
	public function tokenize( string $input ): array {
		if ( $this->regexp === null ) {
			$this->regexp = $this->generate_regexp();
		}

		preg_match_all( $this->regexp, $input, $matches, PREG_SET_ORDER );

		$tokens = [];
		foreach ( $matches as $match ) {
			$tokens[] = [ $match[0], (int) $match['MARK'] ];
		}

		$tokens[] = [ '', self::TOKEN_END ];

		return $tokens;
	}

	private function generate_regexp(): string {
		$patterns = [
			// Full URL in one token (must come before IDENTIFIER to win over scheme name)
			self::TOKEN_URL           => '[a-z][a-z0-9+\\-.]*://\\S*',

			// Quoted strings — consume the full literal including delimiters
			self::TOKEN_SINGLE_QUOTED => '\'(?:\\\\[^\\r\\n]|[^\'\\r\\n\\\\])*+\'',
			self::TOKEN_DOUBLE_QUOTED => '"(?:\\\\[^\\r\\n]|[^"\\r\\n\\\\])*+"',
			self::TOKEN_BACKTICK      => '`[^`]*+`',

			// :: before IDENTIFIER so Foo:: doesn't eat the colon as part of a name
			self::TOKEN_DOUBLE_COLON  => '::',

			// PHP identifier, optionally preceded by \ and repeated with \ separators.
			// Matches: Foo, \Foo, Foo\Bar, \Foo\Bar, namespace\Foo, self, etc.
			self::TOKEN_IDENTIFIER    => '(?:[\\\\]?+[a-z_\\x80-\\xFF][0-9a-z_\\x80-\\xFF]*+)++',

			self::TOKEN_VARIABLE      => '\\$[a-z_\\x80-\\xFF][0-9a-z_\\x80-\\xFF]*+',
			self::TOKEN_OPEN_PAREN    => '\\(',
			self::TOKEN_CLOSE_PAREN   => '\\)',
			self::TOKEN_WS            => '[\\x09\\x20]++',
			self::TOKEN_ARROW         => '->',
			self::TOKEN_OTHER         => '\\S++',
		];

		foreach ( $patterns as $type => &$pattern ) {
			$pattern = '(?:' . $pattern . ')(*MARK:' . $type . ')';
		}

		return '~' . implode( '|', $patterns ) . '~Asi';
	}
}
