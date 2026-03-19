<?php
/**
 * Reference_Parser
 *
 * @package WP_Parser\Reference
 */

namespace WP_Parser\Reference;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

use function implode;
use function ltrim;
use function str_starts_with;
use function substr;
use function trim;

/**
 * Parses a @see/@uses tag value into a typed Reference object.
 */
class Reference_Parser {

	private Reference_Lexer $lexer;

	public function __construct() {
		$this->lexer = new Reference_Lexer();
	}

	public function parse( string $input ): Reference {
		$input = trim( $input );

		if ( $input === '' ) {
			return new Raw_Reference( '' );
		}

		$tokens = $this->lexer->tokenize( $input );
		$pos    = 0;

		return $this->parse_reference( $tokens, $pos, $input );
	}

	/**
	 * @param list<array{string, int}> $tokens
	 */
	private function parse_reference( array $tokens, int &$pos, string $raw ): Reference {
		$type = $tokens[ $pos ][ Reference_Lexer::TYPE_OFFSET ];

		if ( $type === Reference_Lexer::TOKEN_URL ) {
			$url = $tokens[ $pos ][ Reference_Lexer::VALUE_OFFSET ];
			$pos++;
			$description = $this->consume_description( $tokens, $pos );
			return new Url_Reference( $url, $description );
		}

		if ( $type === Reference_Lexer::TOKEN_SINGLE_QUOTED
			|| $type === Reference_Lexer::TOKEN_DOUBLE_QUOTED
			|| $type === Reference_Lexer::TOKEN_BACKTICK
		) {
			return new Quoted_Reference( $tokens[ $pos ][ Reference_Lexer::VALUE_OFFSET ] );
		}

		if ( $type === Reference_Lexer::TOKEN_IDENTIFIER ) {
			return $this->parse_name_reference( $tokens, $pos, $raw );
		}

		return new Raw_Reference( $raw );
	}

	/**
	 * @param list<array{string, int}> $tokens
	 */
	private function parse_name_reference( array $tokens, int &$pos, string $raw ): Reference {
		$name_str = $tokens[ $pos ][ Reference_Lexer::VALUE_OFFSET ];
		$pos++;

		$name      = $this->make_name( $name_str );
		$next_type = $tokens[ $pos ][ Reference_Lexer::TYPE_OFFSET ];

		if ( $next_type === Reference_Lexer::TOKEN_DOUBLE_COLON ) {
			$pos++;
			return $this->parse_static_member( $tokens, $pos, $name, $raw );
		}

		if ( $next_type === Reference_Lexer::TOKEN_OPEN_PAREN ) {
			$pos++;
			if ( $tokens[ $pos ][ Reference_Lexer::TYPE_OFFSET ] !== Reference_Lexer::TOKEN_CLOSE_PAREN ) {
				return new Raw_Reference( $raw );
			}
			$pos++;

			if ( $tokens[ $pos ][ Reference_Lexer::TYPE_OFFSET ] === Reference_Lexer::TOKEN_ARROW ) {
				$pos++;
				return $this->parse_chained_method( $tokens, $pos, $name, $raw );
			}

			$description = $this->consume_description( $tokens, $pos );
			return new Function_Reference( $name, $description );
		}

		if ( $next_type === Reference_Lexer::TOKEN_WS || $next_type === Reference_Lexer::TOKEN_END ) {
			$description = $this->consume_description( $tokens, $pos );
			return new Const_Reference( $name, $description );
		}

		return new Raw_Reference( $raw );
	}

	/**
	 * @param list<array{string, int}> $tokens
	 */
	private function parse_chained_method( array $tokens, int &$pos, Name $function, string $raw ): Reference {
		if ( $tokens[ $pos ][ Reference_Lexer::TYPE_OFFSET ] !== Reference_Lexer::TOKEN_IDENTIFIER ) {
			return new Raw_Reference( $raw );
		}

		$method_name = $tokens[ $pos ][ Reference_Lexer::VALUE_OFFSET ];
		$pos++;

		if ( $tokens[ $pos ][ Reference_Lexer::TYPE_OFFSET ] !== Reference_Lexer::TOKEN_OPEN_PAREN ) {
			return new Raw_Reference( $raw );
		}
		$pos++;

		if ( $tokens[ $pos ][ Reference_Lexer::TYPE_OFFSET ] !== Reference_Lexer::TOKEN_CLOSE_PAREN ) {
			return new Raw_Reference( $raw );
		}
		$pos++;

		$description = $this->consume_description( $tokens, $pos );
		return new Chained_Method_Reference( $function, new Identifier( $method_name ), $description );
	}

	/**
	 * @param list<array{string, int}> $tokens
	 */
	private function parse_static_member( array $tokens, int &$pos, Name $class, string $raw ): Reference {
		$member_type  = $tokens[ $pos ][ Reference_Lexer::TYPE_OFFSET ];
		$member_value = $tokens[ $pos ][ Reference_Lexer::VALUE_OFFSET ];

		if ( $member_type === Reference_Lexer::TOKEN_VARIABLE ) {
			$pos++;
			$description = $this->consume_description( $tokens, $pos );
			return new Property_Reference( $class, new Identifier( ltrim( $member_value, '$' ) ), $description );
		}

		if ( $member_type === Reference_Lexer::TOKEN_IDENTIFIER ) {
			$pos++;
			if ( $tokens[ $pos ][ Reference_Lexer::TYPE_OFFSET ] === Reference_Lexer::TOKEN_OPEN_PAREN ) {
				$pos++;
				if ( $tokens[ $pos ][ Reference_Lexer::TYPE_OFFSET ] === Reference_Lexer::TOKEN_CLOSE_PAREN ) {
					$pos++;
					$description = $this->consume_description( $tokens, $pos );
					return new Method_Reference( $class, new Identifier( $member_value ), $description );
				}
				return new Raw_Reference( $raw );
			}

			$description = $this->consume_description( $tokens, $pos );
			return new Class_Const_Reference( $class, new Identifier( $member_value ), $description );
		}

		return new Raw_Reference( $raw );
	}

	private function make_name( string $name_str ): Name {
		if ( $name_str !== '' && $name_str[0] === '\\' ) {
			return new Name\FullyQualified( substr( $name_str, 1 ) );
		}

		if ( str_starts_with( $name_str, 'namespace\\' ) ) {
			return new Name\Relative( substr( $name_str, \strlen( 'namespace\\' ) ) );
		}

		return new Name( $name_str );
	}

	/**
	 * Skips one leading whitespace token then concatenates all remaining values until TOKEN_END.
	 *
	 * @param list<array{string, int}> $tokens
	 */
	private function consume_description( array $tokens, int &$pos ): ?string {
		if ( $tokens[ $pos ][ Reference_Lexer::TYPE_OFFSET ] === Reference_Lexer::TOKEN_WS ) {
			$pos++;
		}

		if ( $tokens[ $pos ][ Reference_Lexer::TYPE_OFFSET ] === Reference_Lexer::TOKEN_END ) {
			return null;
		}

		$parts = [];
		while ( $tokens[ $pos ][ Reference_Lexer::TYPE_OFFSET ] !== Reference_Lexer::TOKEN_END ) {
			$parts[] = $tokens[ $pos ][ Reference_Lexer::VALUE_OFFSET ];
			$pos++;
		}

		return implode( '', $parts );
	}
}
