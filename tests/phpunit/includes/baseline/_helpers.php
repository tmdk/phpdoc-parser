<?php

use WP_Parser\Factory\Method_Call_Factory;

const KEYWORD_REGEX =
	'/^\\\\\\??(?:string|class-string|interface-string|html-escaped-string' .
	'|lowercase-string|non-empty-lowercase-string|non-empty-string' .
	'|numeric-string|numeric|trait-string|int|integer|positive-int' .
	'|negative-int|bool|boolean|real|float|double|object|mixed|array' .
	'|array-key|non-empty-array|resource|void|null|scalar|callback' .
	'|callable|callable-string|false|true|literal-string|self|\$this' .
	'|static|parent|iterable|never|list|non-empty-list)(?:$|<|\{|:)/';

const TYPE_ALIASES = [
	'integer' => 'int',
	'boolean' => 'bool',
	'double'  => 'float',
	'real'    => 'float',
	'mixed[]' => 'array',
];

function is_keyword( ?string $str ): bool {
	return $str !== null && (bool) preg_match( KEYWORD_REGEX, $str );
}

function _normalize_whitespace( mixed $str ): mixed {
	if ( ! is_string( $str ) ) {
		return $str;
	}

	$str = preg_replace( '/ {2,}/', ' ', $str );

	return preg_replace( '/(?<=<br>|\n) +/', '', $str );
}

function _normalize_inline_tags( string $str ): string {
	// Normalize whitespace inside inline tags: {@tag content } -> {@tag content}
	return preg_replace_callback(
		'/\{@(\w+)(.*?)\}/', function ( $m ) {
		$content = trim( $m[2] );

		return '{@' . $m[1] . ( $content !== '' ? ' ' . $content : '' ) . '}';
	}, $str
	);
}

function _uses_differ_only_in_order( ?array $expected, ?array $actual ): bool {
	if ( $expected === null || $actual === null || count( $expected ) !== count( $actual ) ) {
		return false;
	}
	if ( $expected === $actual ) {
		return false;
	}
	if ( ! isset( $expected[0]['line'] ) ) {
		return false;
	}

	$normalize = fn( array $item ) => isset( $item['class'] )
		? array_merge( $item, [ 'class' => ltrim( $item['class'], '\\' ) ] )
		: $item;

	$sort = function ( array $arr ) use ( $normalize ) {
		$copy = array_map( $normalize, $arr );
		usort(
			$copy, fn( $a, $b ) => $a['line'] <=> $b['line']
			?: $a['end_line'] <=> $b['end_line']
				?: strcmp( $a['name'], $b['name'] )
		);

		return $copy;
	};

	return $sort( $expected ) === $sort( $actual );
}

function is_octal( mixed $str ): bool {
	return is_string( $str ) && preg_match( '/^0[0-7]+$/', $str );
}

/**
 * Regex pattern to match invalid type strings.
 *
 * Matches:
 * 1. Unclosed generic arrays: array< without closing >
 * 2. Unclosed array shapes: array{ without closing }
 * 3. Namespaced keywords: \Namespace\int or \Namespace\?int (keywords shouldn't be namespaced)
 * 4. Conditional types: ($var|T is expr ? type-if-true : type-if-false)
 * 5. Unclosed callable: callable( without closing )
 * 6. int-mask-of with class constant references
 * 7. Incomplete types with unclosed parentheses
 */
const INVALID_TYPE_REGEX = <<<'REGEX'
/(?x)
  # Pattern 1: Unclosed generic array - array< without closing >
  \b array < [^>]+ $
|
  # Pattern 2: Unclosed array shape - array{ without closing }
  \b array \{ [^}]+ $
|
  # Pattern 3: Incorrectly namespaced keywords
  # Matches: \Namespace\SubNamespace\keyword or \Namespace\?keyword
  ^ \\                                                       # Leading backslash
  (?: [a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*+ \\ )+        # One or more namespace segments
  \??                                                        # Optional nullable ?
  (?:                                                        # Keyword (from KEYWORD_REGEX)
    string|class-string|interface-string|html-escaped-string
    |lowercase-string|non-empty-lowercase-string|non-empty-string
    |numeric-string|numeric|trait-string|int|integer|positive-int
    |negative-int|bool|boolean|real|float|double|object|mixed|array
    |array-key|non-empty-array|resource|void|null|scalar|callback
    |callable|callable-string|false|true|literal-string|self|\$this
    |static|parent|iterable|never|list|non-empty-list
  )
  (?: $ | < | \{ | \( )                                       # End or start of generic or shape
|
  # Pattern 4: Conditional types - ($var is expr ? type-if-true : type-if-false)
  \( [^)]* \s+ is \s+ [^)]* \? [^)]* : [^)]* \)
|
  # Pattern 5: Unclosed callable - callable( without closing )
  \b callable \( (?! [^)]* \) )
|
  # Pattern 6: int-mask-of with class constant references
  int-mask-of < [^>]* :: [^>]* >
|
  # Pattern 7: Incomplete types with unclosed parentheses (e.g., \SimplePie\($date_format)
  ^ [^()]* \( (?! [^)]* \) ) [^)]* $
/
REGEX;

/**
 * Check if a type string contains invalid type syntax.
 *
 * @param mixed $str The string to check.
 *
 * @return bool True if the type is invalid, false otherwise.
 */
function is_invalid_type( mixed $str ): bool {
	return is_string( $str ) && preg_match( INVALID_TYPE_REGEX, $str );
}

/**
 * Check if a string contains "new \ClassName(...)" pattern.
 *
 * Matches strings like:
 * - "new \WP_Block($block->parsed_block)"
 * - "new \WP_REST_Response(array())"
 *
 * @param mixed $str The string to check.
 *
 * @return bool True if the string contains new with leading backslash.
 */
function has_new_with_leading_backslash( mixed $str ): bool {
	return is_string( $str ) && preg_match( '/\bnew\s+\\\\[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*\s*\(/', $str );
}

function _baseline_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new \RuntimeException( "Broad baseline precondition failed: $message" );
	}
}

function _is_phantom_tag( mixed $tag ): bool {
	if ( ! is_array( $tag ) ) {
		return false;
	}
	foreach ( $tag as $value ) {
		if ( $value === null ) {
			continue;
		}
		if ( is_array( $value ) && $value === [ null ] ) {
			continue;
		}

		return false;
	}

	return true;
}

function _is_empty_doc( mixed $doc ): bool {
	if ( ! is_array( $doc ) ) {
		return $doc === null;
	}
	if ( ( $doc['description'] ?? '' ) !== '' || ( $doc['long_description'] ?? '' ) !== '' ) {
		return false;
	}
	foreach ( $doc['tags'] ?? [] as $tag ) {
		if ( ! _is_phantom_tag( $tag ) ) {
			return false;
		}
	}

	return true;
}

function expression_resolves_to_classname( $expected, $actual ): bool {
	if ( $expected === null || $actual === null ) {
		return false;
	}

	return ( Method_Call_Factory::FUNCTION_RETURN_MAPPING[ $expected ] ?? null ) === ltrim( $actual, '\\' )
		|| ( Method_Call_Factory::GLOBAL_VAR_MAPPING[ $expected ] ?? null ) === ltrim( $actual, '\\' );
}

/**
 * Convert short array syntax to long array syntax, iteratively replacing innermost brackets.
 */
function _array_short_to_long( string $s ): string {
	$prev = null;
	while ( $prev !== $s ) {
		$prev = $s;
		$s    = preg_replace( '/\[([^\[\]]*)\]/', 'array($1)', $s );
	}

	return $s;
}

function equals_with_quoted_strings_stripped( $expected, $actual ): bool {
	// test without backslashes because they might interfere.
	$expected = str_replace( '\\', '', $expected );
	$actual = str_replace( '\\', '', $actual );
	$expected = _array_short_to_long( $expected );
	$actual = _array_short_to_long( $actual );

	$normalized = preg_replace(
		[
			"/'(?:\\\\.|[^'])*'/",
			'/"(?:\\\\.|[^"])*"/',
		], '', $actual
	);

	return $normalized === $expected;
}
