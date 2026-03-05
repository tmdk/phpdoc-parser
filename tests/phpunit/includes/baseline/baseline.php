<?php

require_once __DIR__ . '/_helpers.php';

/**
 * Consolidated baseline entry for all invalid type syntax patterns:
 * - Unclosed generic arrays (array< without >)
 * - Unclosed array shapes (array{ without })
 * - Namespaced keywords (\Namespace\int)
 * - Namespaced nullable keywords (\Namespace\?string)
 * - Complex array types not parsed correctly
 * - Conditional types ($var|T is expr ? type-if-true : type-if-false)
 * - Unclosed callable (callable( without ))
 * - int-mask-of with class constant references
 */
$invalid_type_syntax = [
	// Invalid type syntax in typed tags (unclosed generics/shapes, namespaced keywords, complex arrays).
	'id' => 'invalid-type-syntax-in-typed-tags-unclosed-genericsshapes-namespaced-keywords-complex-arrays',

	'filter'     => function ( $tag ) {
		if ( ! isset( $tag['types'] ) ) {
			return false;
		}

		foreach ( $tag['types'] as $type ) {
			if ( is_invalid_type( $type ) ) {
				return true;
			}
		}

		return false;
	},
	'resolution' => function ( $expected, $actual ) {
		if ( isset( $actual['content'] ) ) {
			$expected['content'] = $actual['content'];
		} else {
			unset( $expected['content'] );
		}
		if ( isset( $actual['types'] ) ) {
			$expected['types'] = $actual['types'];
		} else {
			unset( $expected['types'] );
		}
		if ( isset( $expected['variable'], $actual['variable'] ) ) {
			$expected['variable'] = $actual['variable'];
		}

		return [ $expected, $actual ];
	},
];

$old_parser_namespaced_self_in_types = [
	// Old parser concatenated the namespace to the whole type string, producing malformed types such as:
	// \NS\self::CONST, \NS\non-negative-int, \NS\?ClassName (nullable ? in wrong position)
	'id' => 'old-parser-concatenated-namespace-to-type-string',

	'filter'     => function ( $expected, $actual ) {
		if ( ! is_string( $expected ) || ! is_string( $actual ) ) {
			return false;
		}
		// Case 1: \NS\self::CONST or \NS\hyphenated-pseudotype -> self::CONST or hyphenated-pseudotype
		if ( preg_match( '/^\\\\?(?:\w+\\\\)+(?:self::|[\w]+-[\w-]*)/', $expected )
			&& preg_replace( '/^\\\\?(?:\w+\\\\)+/', '', $expected ) === $actual ) {
			return true;
		}
		// Case 2: \NS\?ClassName -> ?\NS\ClassName (nullable ? repositioned before FQCN)
		if ( preg_match( '/^(\\\\?(?:\w+\\\\)+)\?(.+)$/', $expected, $m )
			&& $actual === '?' . $m[1] . $m[2] ) {
			return true;
		}
		// Case 3: \NS\?\AlreadyFQCN -> ?\AlreadyFQCN (suffix was already fully qualified)
		$suffix = preg_replace( '/^\\\\?(?:\w+\\\\)+/', '', $expected );
		if ( str_starts_with( $suffix, '?' ) && $suffix === $actual ) {
			return true;
		}

		return false;
	},
	'resolution' => fn( $expected, $actual ) => [ $actual ],
];

$generic_array_to_shorthand = [
	// phpDocumentor normalizes generic array syntax to shorthand (array<Type> -> Type[], array<mixed> -> array)
	'id' => 'phpdocumentor-normalizes-generic-array-syntax-to-shorthand-arraytype---type-arraymixed---array',

	'filter'     => function ( $expected, $actual ) {
		if ( $expected === null ) {
			return false;
		}
		// Match \array<Type> or array<Type> and check if actual is Type[]
		if ( preg_match( '/^\\\\?array<(.+)>$/', $expected, $matches ) ) {
			$inner_type = $matches[1];

			// array<mixed> normalizes to just 'array' or 'mixed[]'
			if ( $inner_type === 'mixed' && ( $actual === 'array' || $actual === 'mixed[]' ) ) {
				return true;
			}

			// array<array<mixed>> normalizes to array[] or mixed[][]
			if ( $inner_type === 'array<mixed>' && ( $actual === 'array[]' || $actual === 'mixed[][]' ) ) {
				return true;
			}

			return $actual === $inner_type . '[]';
		}

		return false;
	},
	'resolution' => fn( $expected, $actual ) => [ $actual ],
];

$remove_redundant_parentheses = [
	// phpDocumentor removes redundant parentheses from types (\(Type[])[] -> \Type[][])
	'id' => 'phpdocumentor-removes-redundant-parentheses-from-types-type---type',

	'filter'     => function ( $expected, $actual ) {
		if ( $expected === null ) {
			return false;
		}
		// Remove parentheses and check if it matches actual
		$without_parens = preg_replace( '/\(([^)]+)\)/', '$1', $expected );

		return $without_parens === $actual;
	},
	'resolution' => fn( $expected, $actual ) => [ $actual ],
];

$fully_qualified_type_in_types = [
	'id' => 'fully-qualified-type-in-types',

	'filter'     => function ( $expected, $actual ) {
		if ( $expected === null ) {
			return false;
		}
		// Non-nullable: Foo -> \Foo
		if ( str_starts_with( $actual, '\\' ) && substr( $actual, 1 ) === $expected ) {
			return true;
		}
		// Nullable: ?Foo -> ?\Foo
		if ( str_starts_with( $expected, '?' ) && str_starts_with( $actual, '?\\' ) && substr( $actual, 2 ) === substr( $expected, 1 ) ) {
			return true;
		}

		return false;
	},
	'resolution' => fn( $expected, $actual ) => [ $actual ],
];

$method_uses_class_fully_qualified_name  = [
	// new parser uses fully qualified name in-method uses
	'id' => 'new-parser-uses-fully-qualified-name-in-method-uses',

	'path'       => 'uses.methods[].class',
	'filter'     => fn( $expected, $actual ) => isset( $expected, $actual )
		&& str_starts_with( $actual, '\\' ) && substr( $actual, 1 ) === $expected,
	'resolution' => fn( $expected, $actual ) => [ $actual ],
];
$method_uses_class_resolves_to_classname = [
	// new parser resolves method uses class to classname ($wpdb -> wpdb, get_current_screen() -> WP_Screen)
	'id' => 'new-parser-resolves-method-uses-class-to-classname',

	'path'       => 'uses.methods[].class',
	'filter'     => fn( $expected, $actual ) => expression_resolves_to_classname( $expected, $actual ),
	'resolution' => fn( $expected, $actual ) => [ $actual ],
];

$generic_type_space_after_comma = [
	// phpDocumentor 6.x adds a space after commas in generic type parameters (array<string,bool> -> array<string, bool>)
	'id' => 'phpdocumentor-adds-space-after-commas-in-generic-type-parameters',

	'filter'     => function ( $expected, $actual ) {
		if ( $expected === null || $actual === null ) {
			return false;
		}

		return preg_replace( '/,\s+/', ',', $actual ) === $expected;
	},
	'resolution' => fn( $expected, $actual ) => [ $actual ],
];

$string_literal_quote_normalization    = [
	// phpDocumentor normalizes single-quoted string literals to double-quoted ('V' -> "V")
	'id' => 'phpdocumentor-normalizes-single-quoted-string-literals-to-double-quoted-v---v',

	'filter'     => function ( $expected, $actual ) {
		if ( $expected === null || $actual === null ) {
			return false;
		}

		return preg_replace( '/^\\\\?\'(.+)\'$/', '"\\1"', $expected ) === $actual;
	},
	'resolution' => fn( $expected, $actual ) => [ $actual ],
];
$default_value_omitted                 = [
	// old parser omits default value
	'id'         => 'old-parser-omits-default-value',
	'filter'     => function ( $expected, $actual ) {
		if ( $expected === null || $actual === null ) {
			return false;
		}

		return $expected === '' && $actual !== $expected;
	},
	'resolution' => fn( $expected, $actual ) => [ $actual ],
];
$default_value_quoted_strings_stripped = [
	'id'         => 'old-parser-replaces-quoted-strings-with-space',
	'filter'     => function ( $expected, $actual ) {
		if ( $expected === null || $actual === null ) {
			return false;
		}

		return equals_with_quoted_strings_stripped( $expected, $actual );
	},
	'resolution' => fn( $expected, $actual ) => [ $actual ],
];
$default_value_comments_stripped       = [
	'id'         => 'new-parser-strips-comments-from-default-values',
	'filter'     => function ( $expected, $actual ) {
		if ( $expected === null || $actual === null ) {
			return false;
		}

		return ( str_contains( $expected, '/*' ) && ! str_contains( $actual, '/*' ) )
			|| ( str_contains( $expected, '//' ) && ! str_contains( $actual, '//' ) );
	},
	'resolution' => fn( $expected, $actual ) => [ $actual ],
];

$normalize_whitespace_in_tags = [
	// Docblock parser removes indentation from consecutive lines.
	'id' => 'docblock-parser-removes-indentation-from-consecutive-lines',

	'resolution' => fn( $expected, $actual ) => [
		_normalize_whitespace( $expected ),
		_normalize_whitespace( $actual ),
	],
];

$inline_tag_whitespace = [
	// Inline tag whitespace differs between old and new parser
	'id' => 'inline-tag-whitespace-differs-between-old-and-new-parser',

	'path'       => 'doc.long_description',
	'filter'     => fn( $expected, $actual ) => $expected !== null
		&& $actual !== null
		&& $expected !== $actual
		&& _normalize_inline_tags( $expected ) === _normalize_inline_tags( $actual ),
	'resolution' => fn( $expected, $actual ) => [ $actual ],
];

$leading_backslash_in_refers = [
	// New parser adds leading backslash in refers
	'id' => 'new-parser-adds-leading-backslash-in-refers',

	'filter'     => fn( $expected, $actual ) => $expected !== null
		&& isset( $expected['refers'], $actual['refers'] )
		&& str_starts_with( $actual['refers'], '\\' )
		&& ltrim( $actual['refers'], '\\' ) === $expected['refers'],
	'resolution' => function ( $expected, $actual ) {
		$expected['refers'] = $actual['refers'];

		return [ $expected ];
	},
];

$escape_double_quotes = [
	// new parser escapes double quotes
	'id' => 'new-parser-escapes-double-quotes',

	'path'       => 'doc.{description|long_description}',
	'filter'     => fn( $expected, $actual ) => $expected !== null
		&& str_contains( $expected, '"' )
		&& preg_replace( '/(?<!<a href=)"(?!>http)/', '&quot;', $expected ) === $actual,
	'resolution' => fn( $expected, $actual ) => [ $actual ],
];

$deprecation_version_omitted = [
	// Old parser omits deprecation_version
	'id' => 'old-parser-omits-deprecation-version',

	'path'       => 'uses.functions[]',
	'filter'     => fn( $expected, $actual ) => $expected !== null
		&& ! array_key_exists( 'deprecation_version', $expected )
		&& array_key_exists( 'deprecation_version', $actual ),
	'resolution' => fn( $expected, $actual ) => [ $actual ],
];

$callable_baseline = [
	[
		...$normalize_whitespace_in_tags,
		'path' => 'doc.tags[].{content|description}',
	],
	[
		// New parser adds leading backslash to names in inline {@see} tags within descriptions.
		// Old parser also sometimes omitted whitespace inside inline tags ({@tag()} vs {@tag ()}).
		'id' => 'new-parser-adds-leading-backslash-in-refers',

		'path'   => 'doc.{description|long_description}',
		'filter' => function ( $expected, $actual ) {
			if ( ! is_string( $expected ) || ! is_string( $actual ) || $expected === $actual ) {
				return false;
			}
			$normalize = fn( $s ) => _normalize_inline_tags( preg_replace( '/\{@see \\\\/', '{@see ', $s ) );

			return $normalize( $expected ) === $normalize( $actual );
		},
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		// Old parser stripped backslashes from literal escape sequences (e.g. \n, \t) in tag content,
		// treating them as PHP escape sequences. New parser preserves the literal backslash.
		'id' => 'old-parser-stripped-backslashes-from-literal-escape-sequences-in-tag-content',

		'path'       => 'doc.tags[].content',
		'filter'     => fn( $expected, $actual ) => is_string( $expected )
			&& is_string( $actual )
			&& $expected !== $actual
			&& str_replace( '\\', '', $actual ) === $expected,
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		// Old parser evaluated \xHH hex escape sequences in docblock descriptions, stripping the backslash.
		// New parser preserves the literal \xHH. Only strip \x before hex chars to avoid touching \u{...} etc.
		'id' => 'old-parser-stripped-backslashes-from-hex-escape-sequences-in-doc-description',

		'path'       => 'doc.{description|long_description}',
		'filter'     => fn( $expected, $actual ) => is_string( $expected )
			&& is_string( $actual )
			&& $expected !== $actual
			&& preg_replace( '/\\\\(?=x[0-9a-fA-F])/', '', $actual ) === $expected,
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		...$inline_tag_whitespace,
	],
	[
		...$default_value_omitted,
		'path' => 'arguments[].default',
	],
	[
		...$fully_qualified_type_in_types,
		'path' => 'arguments[].type',
	],
	[
		...$default_value_quoted_strings_stripped,
		'path' => 'arguments[].default',
	],
	[
		// Old parser flattens indented code blocks in docblocks to <p>, new parser correctly renders as <pre><code>
		'id' => 'old-parser-flattens-indented-code-blocks-in-docblocks-to-p-new-parser-correctly-renders-as-precode',

		'path'       => 'doc.long_description',
		'filter'     => fn( $expected, $actual ) => is_string( $expected )
			&& is_string( $actual )
			&& str_starts_with( $expected, '<p>' )
			&& str_starts_with( $actual, '<pre><code>' ),
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		...$leading_backslash_in_refers,
		'path' => 'doc.tags[]',
	],
	[
		// New parser resolves unqualified names in @uses tag refers to their FQCN within the current namespace
		'id' => 'new-parser-resolves-uses-tag-refers-to-fqcn',

		'path'       => 'doc.tags[]',
		'filter'     => fn( $expected, $actual ) => isset( $expected['refers'], $actual['refers'] )
			&& ( $expected['name'] ?? null ) === 'uses'
			&& ! str_starts_with( $expected['refers'], '\\' )
			&& str_starts_with( $actual['refers'], '\\' )
			&& str_ends_with( $actual['refers'], '\\' . $expected['refers'] ),
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		// Old parser incorrectly adds leading backslash to unqualified class names in new expressions
		'id' => 'old-parser-incorrectly-adds-leading-backslash-to-unqualified-class-names-in-new-expressions',

		'path'       => 'uses.methods[]',
		'filter'     => fn( $expected, $actual ) => isset( $expected['class'], $actual['class'] )
			&& has_new_with_leading_backslash( $expected['class'] )
			&& preg_replace( '/\bnew\s+\\\\/', 'new ', $expected['class'] ) === $actual['class'],
		'resolution' => function ( $expected, $actual ) {
			$expected['class'] = $actual['class'];

			return [ $expected ];
		},
	],
	[
		// New parser correctly adds leading backslash to global-namespace class names in new expressions
		'id' => 'new-parser-adds-leading-backslash-to-global-namespace-class-names-in-new-expressions',

		'path'       => 'uses.methods[]',
		'filter'     => fn( $expected, $actual ) => isset( $expected['class'], $actual['class'] )
			&& has_new_with_leading_backslash( $actual['class'] )
			&& preg_replace( '/\bnew\s+\\\\/', 'new ', $actual['class'] ) === $expected['class'],
		'resolution' => function ( $expected, $actual ) {
			$expected['class'] = $actual['class'];

			return [ $expected ];
		},
	],
	[
		// Old parser added a leading backslash to global function calls inside method class expressions,
		// e.g. $obj->method(\wp_timezone()) instead of $obj->method(wp_timezone()).
		'id' => 'old-parser-added-leading-backslash-to-global-function-calls-in-method-class-expressions',

		'path'       => 'uses.methods[]',
		'filter'     => fn( $expected, $actual ) => isset( $expected['class'], $actual['class'] )
			&& preg_match( '/\\\\[a-z_]\w*\(/', $expected['class'] )
			&& preg_replace( '/\\\\(?=[a-z_]\w*\()/', '', $expected['class'] ) === $actual['class'],
		'resolution' => function ( $expected, $actual ) {
			$expected['class'] = $actual['class'];

			return [ $expected ];
		},
	],
	[
		// Old parser added a leading backslash to boolean/null constants (false, true, null) in expressions,
		// treating them like fully-qualified names. New parser correctly emits them without a backslash.
		// This may coincide with the leading-backslash difference on the class name itself (e.g. new \ClassName).
		'id' => 'old-parser-added-leading-backslash-to-boolean-null-constants-in-expressions',

		'path'   => 'uses.methods[]',
		'filter' => function ( $expected, $actual ) {
			if ( ! isset( $expected['class'], $actual['class'] ) ) {
				return false;
			}
			if ( ! preg_match( '/\\\\(false|true|null)\b/', $expected['class'] ) ) {
				return false;
			}
			$normalize = function ( $s ) {
				$s = ltrim( $s, '\\' );
				$s = preg_replace( '/\\\\(false|true|null)\b/', '$1', $s );
				$s = preg_replace( '/\bnew \\\\/', 'new ', $s );

				return $s;
			};

			return $normalize( $expected['class'] ) === $normalize( $actual['class'] );
		},
		'resolution' => function ( $expected, $actual ) {
			$expected['class'] = $actual['class'];

			return [ $expected ];
		},
	],
	[
		// New parser names anonymous class instantiations using the extended class name (ClassName@anonymous),
		// while the old parser used the generic 'class@anonymous' format.
		'id' => 'new-parser-names-anonymous-class-instantiations-with-extended-class-name',

		'path'       => 'uses.methods[]',
		'filter'     => fn( $expected, $actual ) => isset( $expected['class'], $actual['class'] )
			&& $expected['class'] === 'class@anonymous'
			&& str_ends_with( $actual['class'], '@anonymous' ),
		'resolution' => function ( $expected, $actual ) {
			$expected['class'] = $actual['class'];

			return [ $expected ];
		},
	],
	[
		// Old parser incorrectly adds leading backslash to unqualified class names in hook arguments
		'id' => 'old-parser-incorrectly-adds-leading-backslash-to-unqualified-class-names-in-hook-arguments',

		'path'       => 'hooks[]',
		'filter'     => function ( $expected, $actual ) {
			if ( ! isset( $expected['arguments'][0], $actual['arguments'][0] ) ) {
				return false;
			}

			return has_new_with_leading_backslash( $expected['arguments'][0] )
				&& preg_replace( '/\bnew\s+\\\\/', 'new ', $expected['arguments'][0] ) === $actual['arguments'][0];
		},
		'resolution' => function ( $expected, $actual ) {
			$expected['arguments'][0] = $actual['arguments'][0];

			return [ $expected ];
		},
	],
	$method_uses_class_fully_qualified_name,
	$method_uses_class_resolves_to_classname,
	[
		// Uses ordering differs between old and new parser
		'id' => 'uses-ordering-differs-between-old-and-new-parser',

		'path'       => 'uses.{functions|methods}',
		'filter'     => fn( $expected, $actual ) => _uses_differ_only_in_order( $expected, $actual ),
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		// Old parser fully-qualifies class names in argument defaults (adds leading backslash)
		'id' => 'old-parser-fully-qualifies-class-names-in-argument-defaults-adds-leading-backslash',

		'path'       => 'arguments[].default',
		'filter'     => fn( $expected, $actual ) => is_string( $expected )
			&& is_string( $actual )
			&& $expected !== $actual
			&& preg_replace( '/\\\\([A-Z][a-zA-Z0-9_]*::)/', '$1', $expected ) === $actual,
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		// Old parser expanded use-imported class names to FQCNs in argument defaults (e.g. Requests::GET -> \WpOrg\Requests\Requests::GET), new parser keeps name as written
		'id' => 'old-parser-expanded-use-imported-class-names-to-fqcns-in-argument-defaults',

		'path'       => 'arguments[].default',
		'filter'     => fn( $expected, $actual ) => is_string( $expected )
			&& is_string( $actual )
			&& str_contains( $expected, '::' )
			&& str_contains( $actual, '::' )
			&& preg_match( '/^\\\\?(?:\w+\\\\)+(\w+::.+)$/', $expected, $m )
			&& $m[1] === $actual,
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		// Old parser converts * bullet markers to <em> emphasis tags
		'id' => 'old-parser-converts-bullet-markers-to-em-emphasis-tags',

		'path'       => 'doc.tags[].content',
		'filter'     => function ( $expected, $actual ) {
			if ( $expected === null || $actual === null || $expected === $actual ) {
				return false;
			}
			if ( ! str_contains( $expected, '<em>' ) ) {
				return false;
			}

			// Simple case: only difference is <em>/<em> vs *
			if ( str_replace( [ '<em>', '</em>' ], '*', $expected ) === $actual ) {
				return true;
			}

			// Complex case: also normalize whitespace/line-breaks around bullets.
			// Step 1: Replace all <em>/<em> with * in expected.
			$normalized = str_replace( [ '<em>', '</em>' ], '*', $expected );

			// Step 2: Re-identify legitimate inline emphasis: *word* → <em>word</em>
			$normalized = preg_replace( '/\*(\S+)\*/', '<em>$1</em>', $normalized );

			// Step 3: Normalize bullet * patterns in both (handles <br>* vs space-*).
			$norm = static function ( $s ) {
				return preg_replace( '/(?:<br>|\s)\*\s/', "\n* ", $s );
			};

			return $norm( $normalized ) === $norm( $actual );
		},
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		// phpDocumentor replaces {} with } to avoid issues with inline tags
		'id' => 'phpdocumentor-replaces-with-to-avoid-issues-with-inline-tags',

		'path'       => 'doc.tags[].content',
		'filter'     => fn( $expected, $actual ) => $expected !== null && str_contains( $expected, '{}' ),
		'resolution' => fn( $expected, $actual ) => [ str_replace( '{}', '}', $expected ) ],
	],
	[
		// phpDocumentor replaces {} with } to avoid issues with inline tags
		'id' => 'phpdocumentor-replaces-with-to-avoid-issues-with-inline-tags',

		'path'       => 'doc.{description|long_description}',
		'filter'     => fn( $expected, $actual ) => $expected !== null && str_contains( $expected, '{}' ),
		'resolution' => fn( $expected, $actual ) => [ str_replace( '{}', '}', $expected ) ],
	],
	[
		...$escape_double_quotes,
		'path' => 'doc.tags[].content',
	],
	[
		// Invalid type syntax in param types
		'id' => 'invalid-type-syntax-in-param-types',

		'path'       => 'arguments[].type',
		'filter'     => is_invalid_type( ... ),
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		// Keywords are incorrectly prefixed with `\` by the old parser in param types.
		'id' => 'keywords-are-incorrectly-prefixed-with-by-the-old-parser-in-param-types',

		'path'       => 'arguments[].type',
		'filter'     => is_keyword( ... ),
		'resolution' => fn( $expected ) => [ ltrim( $expected, '\\' ) ],
	],
	[
		// Old parser adds leading backslash to class constant references in param defaults
		'id' => 'old-parser-adds-leading-backslash-to-class-constant-references-in-param-defaults',

		'path'       => 'arguments[].default',
		'filter'     => fn( $expected, $actual ) => $expected !== null
			&& str_starts_with( $expected, '\\' )
			&& ltrim( $expected, '\\' ) === $actual,
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		// Old parser converts octal numbers to decimal.
		'id' => 'old-parser-converts-octal-numbers-to-decimal',

		'path'       => 'arguments[].default',
		'filter'     => fn( $expected, $actual ) => $expected !== null && is_octal( $actual ),
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		// Keywords are incorrectly prefixed with `\` by the old parser in doc tag types.
		'id' => 'keywords-are-incorrectly-prefixed-with-by-the-old-parser-in-doc-tag-types',

		'path'       => 'doc.tags[].types[]',
		'filter'     => is_keyword( ... ),
		'resolution' => fn( $expected ) => [ ltrim( $expected, '\\' ) ],
	],
	[
		// New parser defaults to mixed when param has no type
		'id' => 'new-parser-defaults-to-mixed-when-param-has-no-type',

		'path'       => 'doc.tags[].types[]',
		'filter'     => fn( $expected, $actual ) => $expected === null && $actual === 'mixed',
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		// phpDocumentor normalizes type aliases (integer->int, boolean->bool, etc.)
		'id' => 'phpdocumentor-normalizes-type-aliases-integer-int-boolean-bool-etc',

		'path'       => 'doc.tags[].types[]',
		'filter'     => fn( $expected, $actual ) => $expected !== null &&
			isset( TYPE_ALIASES[ $expected ] ) &&
			TYPE_ALIASES[ $expected ] === $actual,
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		// phpDocumentor normalizes capitalized builtin PHP types to lowercase (Array->array, String->string, etc.)
		'id' => 'phpdocumentor-normalizes-capitalized-builtin-php-types-to-lowercase-array-array-string-string-etc',

		'path'       => 'doc.tags[].types[]',
		'filter'     => fn( $expected, $actual ) => $expected !== null && strtolower(
				$expected
			) === $actual && $expected !== $actual,
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		...$generic_array_to_shorthand,
		'path' => 'doc.tags[].types[]',
	],
	[
		...$remove_redundant_parentheses,
		'path' => 'doc.tags[].types[]',
	],
	[
		...$fully_qualified_type_in_types,
		'path' => 'doc.tags[].types[]',
	],
	[
		...$old_parser_namespaced_self_in_types,
		'path' => 'doc.tags[].types[]',
	],
	[
		...$string_literal_quote_normalization,
		'path' => 'doc.tags[].types[]',
	],
	[
		...$generic_type_space_after_comma,
		'path' => 'doc.tags[].types[]',
	],
	[
		...$invalid_type_syntax,
		'path' => 'doc.tags[]',
	],
	[
		// Old parser namespace-prefixed numeric literal types (e.g. \NS\-1, \NS\1), new parser keeps them as-is
		'id' => 'old-parser-namespace-prefixed-numeric-literal-types',

		'path'       => 'doc.tags[]',
		'filter'     => function ( $expected, $actual ) {
			if ( ! isset( $expected['types'], $actual['types'] ) ) {
				return false;
			}

			foreach ( $expected['types'] as $type ) {
				if ( is_string( $type ) && preg_match( '/^\\\\.*\\\\-?\d+$/', $type ) ) {
					return true;
				}
			}

			return false;
		},
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		// Old parser concatenated intersection types (&) into a single broken type string, new parser splits them correctly
		'id' => 'old-parser-concatenated-intersection-types-into-single-type-string',

		'path'       => 'doc.tags[]',
		'filter'     => function ( $expected, $actual ) {
			if ( ! isset( $expected['types'], $actual['types'] ) ) {
				return false;
			}

			foreach ( $expected['types'] as $type ) {
				if ( is_string( $type ) && str_contains( $type, '&' ) ) {
					return true;
				}
			}

			return false;
		},
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		// Names in global tags are not resolved to their FQSEN.
		'id' => 'names-in-global-tags-are-not-resolved-to-their-fqsen',

		'path'       => 'doc.tags[]',
		'filter'     => function ( $expected, $actual ) {
			if ( ! isset( $expected['name'], $expected['content'] ) ) {
				return false;
			}

			return $expected['name'] === 'global'
				&& preg_replace( '/\\\\(\w+(?:\\\\\w+)*+)/', '\1', $actual['content'] ) === $expected['content'];
		},
		'resolution' => function ( $expected, $actual ) {
			$expected['content'] = $actual['content'];

			return [ $expected, $actual ];
		},
	],
	[
		// Old parser strips array key from variable function calls ($checks[] vs $checks[$type])
		'id' => 'old-parser-strips-array-key-from-variable-function-calls-checks-vs-checkstype',

		'path'       => 'uses.functions[]',
		'filter'     => fn( $expected, $actual ) => $expected !== null
			&& preg_match( '/^\$\w+\[\]$/', $expected['name'] )
			&& preg_match( '/^\$\w+\[.+\]$/', $actual['name'] ),
		'resolution' => function ( $expected, $actual ) {
			$expected['name'] = $actual['name'];

			return [ $expected ];
		},
	],
	[
		// Old parser keeps fully qualified function names
		'id' => 'old-parser-keeps-fully-qualified-function-names',

		'path'       => 'uses.functions[]',
		'filter'     => fn( $expected, $actual ) => $expected !== null
			&& str_starts_with( $expected['name'], '\\' )
			&& ltrim( $expected['name'], '\\' ) === $actual['name'],
		'resolution' => function ( $expected, $actual ) {
			$expected['name'] = $actual['name'];

			return [ $expected ];
		},
	],
	[
		// Old parser sometimes includes deprecation_version => null, new parser omits the key
		'id' => 'old-parser-sometimes-includes-deprecation-version-null-new-parser-omits-the-key',

		'path'       => 'uses.functions[]',
		'filter'     => fn( $expected, $actual ) => $expected !== null
			&& array_key_exists( 'deprecation_version', $expected )
			&& $expected['deprecation_version'] === null
			&& ! array_key_exists( 'deprecation_version', $actual ),
		'resolution' => function ( $expected ) {
			unset( $expected['deprecation_version'] );

			return [ $expected ];
		},
	],
	$deprecation_version_omitted,
	[
		// Old parser serializes raw AST for anonymous class constructor, new parser uses extends class name
		'id' => 'old-parser-serializes-raw-ast-for-anonymous-class-constructor-new-parser-uses-extends-class-name',

		'path'       => 'uses.methods[]',
		'filter'     => fn( $expected, $actual ) => isset( $expected['name'], $expected['class'] )
			&& $expected['name'] === '__construct'
			&& is_array( $expected['class'] ),
		'resolution' => function ( $expected, $actual ) {
			$expected['class'] = $actual['class'];

			return [ $expected, $actual ];
		},
	],
	[
		// $this->$name produced "name": {"name": ...}
		'id' => 'this-name-produced-name-name',

		'path'       => 'uses.methods[]',
		'filter'     => fn( $expected ) => isset( $expected['name'] ) && is_array( $expected['name'] ),
		'resolution' => function ( $expected, $actual ) {
			$expected['name'] = $actual['name'];

			return [ $expected ];
		},
	],
	[
		// Old parser fails to extract variable name from pass-by-reference parameters (&$var)
		'id' => 'old-parser-fails-to-extract-variable-name-from-pass-by-reference-parameters-var',

		'path'       => 'doc.tags[]',
		'filter'     => function ( $expected, $actual ) {
			if ( ! isset( $expected['name'], $expected['variable'], $actual['variable'] ) ) {
				return false;
			}

			// Old parser has empty variable and content starts with &amp;$
			return $expected['name'] === 'param'
				&& $expected['variable'] === ''
				&& $actual['variable'] !== ''
				&& isset( $expected['content'] )
				&& str_starts_with( $expected['content'], '&amp;$' );
		},
		'resolution' => function ( $expected, $actual ) {
			// Accept v3's correct parsing
			return [ $actual ];
		},
	],
	[
		// phpDocumentor 6.x produces InvalidTag for malformed @param tags (missing $variable),
		// resulting in null types where the old parser extracted a type.
		'id' => 'phpdocumentor-produces-invalidtag-for-malformed-param-tags',

		'path'       => 'doc.tags[]',
		'filter'     => function ( $expected, $actual ) {
			if ( ! isset( $expected['name'] ) || $expected['name'] !== 'param' ) {
				return false;
			}

			// Old parser got types but new parser got null (InvalidTag).
			return isset( $expected['types'] )
				&& isset( $actual['types'] )
				&& $expected['types'] !== [ null ]
				&& $actual['types'] === [ null ]
				&& ( $expected['variable'] ?? '' ) === '';
		},
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
	[
		// Leading backslash differences
		'id' => 'leading-backslash-differences',

		'path'       => 'doc.{description|long_description}',
		'filter'     => fn( $expected, $actual ) => is_string( $expected ) && is_string( $actual )
			&& (
				str_contains( $expected, '\\' ) && ! str_contains( $actual, '\\' )
				|| ! str_contains( $expected, '\\' ) && str_contains( $actual, '\\' )
			)
			&& str_replace( '\\', '', $expected ) === str_replace( '\\', '', $actual ),
		'resolution' => fn( $expected, $actual ) => [ $actual ],
	],
];

$numeric_notation_to_decimal = [
	// Old parser converts octal/hex notation to decimal
	'id' => 'old-parser-converts-octalhex-notation-to-decimal',

	'filter'     => function ( $expected, $actual ) {
		if ( $expected === null ) {
			return false;
		}

		// Try octal: 0777 -> 511
		if ( preg_match( '/\b0[0-7]+\b/', $actual ) ) {
			$converted = preg_replace_callback(
				'/\b0[0-7]+\b/',
				fn( $m ) => intval( $m[0], 8 ),
				$actual
			);
			if ( $converted === $expected ) {
				return true;
			}
		}

		// Try hex: 0x1FF -> 511
		if ( preg_match( '/\b0x[0-9a-f]+\b/i', $actual ) ) {
			$converted = preg_replace_callback(
				'/\b0x[0-9a-f]+\b/i',
				fn( $m ) => intval( $m[0], 16 ),
				$actual
			);
			if ( $converted === $expected ) {
				return true;
			}
		}

		return false;
	},
	'resolution' => fn( $expected, $actual ) => [ $actual ],
];

return [
	'class'    => [
		[
			// New parser does not include anonymous class definitions in the output.
			// Old parser emitted them as 'class@anonymous'. Baseline accepts the omission.
			'id' => 'new-parser-omits-anonymous-class-definitions',

			'filter'     => fn( $expected, $actual ) => ( $expected['name'] ?? null ) === 'class@anonymous' && $actual === [],
			'resolution' => fn( $expected, $actual ) => [ [], $actual ],
		],
		[
			// Class start line changed due to attribute(s)
			'id' => 'class-start-line-changed-due-to-attributes',

			'path'       => 'line',
			'filter'     => fn( $expected, $actual ) => $expected === $actual + 1,
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			...$invalid_type_syntax,
			'path' => 'doc.tags[]',
		],
		[
			...$normalize_whitespace_in_tags,
			'path' => 'doc.tags[].{content|description}',
		],
		[
			// Conditional class definition - parser finds first declaration
			'id' => 'conditional-class-definition---parser-finds-first-declaration',

			'class'      => [ 'ftp', 'Services_JSON_Error' ],
			'resolution' => function ( $expected, $actual ) {
				_baseline_assert(
					$expected['name'] === $actual['name'],
					"Expected class name '{$expected['name']}' to match actual '{$actual['name']}'"
				);

				return [ $actual ];
			},
		],
		[
			// Old parser does not parse class docblock
			'id' => 'old-parser-does-not-parse-class-docblock',

			'class'      => [
				'NOOP_Translations',
				'Translation_Entry',
				'Translations',
				'WP_Customize_Control',
				'WP_Customize_Panel',
				'WP_Customize_Setting',
				'WP_Feed_Cache',
				'WP_Http',
				'WP_SimplePie_Sanitize_KSES',
				'WP_Text_Diff_Renderer_Table',
				'WP_Upgrader',
				'wpdb',
			],
			'path'       => 'doc',
			'resolution' => function ( $expected, $actual ) {
				_baseline_assert(
					_is_empty_doc( $expected ),
					'Expected empty doc from old parser, got: ' . json_encode( $expected )
				);

				return [ $actual ];
			},
		],
		[
			...$numeric_notation_to_decimal,
			'path' => 'properties[].default',
		],
		[
			...$default_value_omitted,
			'path' => 'properties[].default',
		],
		[
			...$default_value_quoted_strings_stripped,
			'path' => 'properties[].default',
		],
		[
			...$default_value_comments_stripped,
			'path' => 'properties[].default',
		],
		[
			// Old parser uses short array syntax [] while new parser uses long array syntax array()
			'id' => 'old-parser-uses-short-array-syntax-new-parser-uses-long-array-syntax',

			'path'       => 'properties[].default',
			'filter'     => fn( $expected, $actual ) => $expected !== null
				&& $actual !== null
				&& _array_short_to_long( $expected ) === $actual,
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			...$invalid_type_syntax,
			'path' => 'properties[].doc.tags[]',
		],
		[
			// Old parser concatenated intersection types (&) into a single broken type string, new parser splits them correctly
			'id' => 'old-parser-concatenated-intersection-types-into-single-type-string',

			'path'       => 'properties[].doc.tags[]',
			'filter'     => function ( $expected, $actual ) {
				if ( ! isset( $expected['types'], $actual['types'] ) ) {
					return false;
				}

				foreach ( $expected['types'] as $type ) {
					if ( is_string( $type ) && str_contains( $type, '&' ) ) {
						return true;
					}
				}

				return false;
			},
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			// Null default value represented as empty string
			'id' => 'null-default-value-represented-as-empty-string',

			'path'       => 'properties[].default',
			'filter'     => fn( $expected, $actual ) => $expected === null && $actual === '',
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			// phpDocumentor normalizes type aliases in properties
			'id' => 'phpdocumentor-normalizes-type-aliases-in-properties',

			'path'       => 'properties[].doc.tags[].types[]',
			'filter'     => fn( $expected, $actual ) => $expected !== null &&
				isset( TYPE_ALIASES[ $expected ] ) &&
				TYPE_ALIASES[ $expected ] === $actual,
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			...$fully_qualified_type_in_types,
			'path' => 'properties[].doc.tags[].types[]',
		],
		[
			...$old_parser_namespaced_self_in_types,
			'path' => 'properties[].doc.tags[].types[]',
		],
		[
			...$normalize_whitespace_in_tags,
			'path' => 'properties[].doc.tags[].{content|description}',
		],
		[
			...$generic_array_to_shorthand,
			'path' => 'properties[].doc.tags[].types[]',
		],
		[
			...$remove_redundant_parentheses,
			'path' => 'properties[].doc.tags[].types[]',
		],
		[
			...$fully_qualified_type_in_types,
			'path' => 'doc.tags[].types[]',
		],
		[
			...$string_literal_quote_normalization,
			'path' => 'properties[].doc.tags[].types[]',
		],
		[
			...$generic_type_space_after_comma,
			'path' => 'properties[].doc.tags[].types[]',
		],
		[
			// Example tags not parsed properly
			'id' => 'example-tags-not-parsed-properly',

			'path'       => 'properties[].doc.tags[]',
			'filter'     => function ( $expected, $actual ) {
				if ( $expected === null ) {
					return false;
				}

				return $expected['name'] === 'example'
					&& $expected['content'] !== $actual['content'];
			},
			'resolution' => function ( $expected, $actual ) {
				$expected['content'] = $actual['content'];

				return [ $expected, $actual ];
			},
		],
		[
			...$leading_backslash_in_refers,
			'path' => 'properties[].doc.tags[]',
		],
		[
			// New parser adds leading backslash to names in inline {@see} tags within descriptions.
			// The old parser also sometimes omitted a space before the content in inline tags
			// (e.g. {@parse_blocks()} vs {@parse_blocks ()}).
			'id' => 'new-parser-adds-leading-backslash-in-refers',

			'path'       => 'doc.{description|long_description}',
			'filter'     => function ( $expected, $actual ) {
				if ( ! is_string( $expected ) || ! is_string( $actual ) || $expected === $actual ) {
					return false;
				}
				// Normalize: strip leading backslash in {@see \func} and normalize inline tag whitespace.
				$normalize = fn( $s ) => _normalize_inline_tags( preg_replace( '/\{@see \\\\/', '{@see ', $s ) );

				return $normalize( $expected ) === $normalize( $actual );
			},
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			...$inline_tag_whitespace,
		],
		[
			// @extends tag content differs between old and new parser
			'id' => 'extends-tag-content-differs-between-old-and-new-parser',

			'path'       => 'doc.tags[]',
			'filter'     => function ( $expected, $actual ) {
				if ( $expected === null ) {
					return false;
				}

				return ( $expected['name'] ?? null ) === 'extends'
					&& $expected['content'] !== $actual['content'];
			},
			'resolution' => function ( $expected, $actual ) {
				$expected['content'] = $actual['content'];

				return [ $expected, $actual ];
			},
		],
		[
			// New parser resolves @uses references differently
			'id' => 'new-parser-resolves-uses-references-differently',

			'path'       => 'doc.tags[]',
			'filter'     => function ( $expected, $actual ) {
				if ( $expected === null ) {
					return false;
				}

				return ( $expected['name'] ?? null ) === 'uses'
					&& isset( $expected['refers'], $actual['refers'] )
					&& $expected['refers'] !== $actual['refers'];
			},
			'resolution' => function ( $expected, $actual ) {
				$expected['refers'] = $actual['refers'];

				return [ $expected, $actual ];
			},
		],
		[
			...$fully_qualified_type_in_types,
			'path' => 'extends',
		],
		[
			...$fully_qualified_type_in_types,
			'path' => 'implements[]',
		],
	],
	'hook'     => [
		...$callable_baseline,
		[
			...$escape_double_quotes,
			'path' => 'doc.{description|long_description}',
		],
		[
			// Old parser incorrectly adds leading backslash to names in hook arguments
			'id' => 'old-parser-incorrectly-adds-leading-backslash-to-names-in-hook-args',

			'path'       => 'arguments[]',
			'filter'     => fn( $expected, $actual ) => str_contains( $expected, '\\' )
				&& ! str_contains( $actual, '\\' )
				&& str_replace( '\\', '', $expected ) === str_replace( '\\', '', $actual ),
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			// Old parser incorrectly adds leading backslash before global function calls in hook arguments
			// e.g. \trim( ... ) instead of trim( ... ). Differs from the above when $actual also contains backslashes.
			'id' => 'old-parser-incorrectly-adds-leading-backslash-before-global-function-calls-in-hook-args',

			'path'       => 'arguments[]',
			'filter'     => fn( $expected, $actual ) => is_string( $expected )
				&& is_string( $actual )
				&& $expected !== $actual
				&& preg_replace( '/\\\\(?=[a-zA-Z_]\w*\()/', '', $expected ) === $actual,
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			// Old parser double-escapes backslashes in hook arguments
			'id' => 'old-parser-double-escapes-backslashes-in-hook-arguments',

			'path'       => 'arguments[]',
			'filter'     => fn( $expected, $actual ) => $expected !== null
				&& $actual !== null
				&& $expected !== $actual
				&& str_replace( '\\\\', '\\', $expected ) === $actual,
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			// Old parser incorrectly associated distant doc comment with hook
			'id' => 'old-parser-incorrectly-associated-distant-doc-comment-with-hook',

			'hook'       => [ 'update_usermeta', 'wp_authenticate', '{$hook_name}' ],
			'path'       => 'doc',
			'filter'     => fn( $expected ) => ! _is_empty_doc( $expected ),
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			// Old parser expanded escape sequences in double-quoted strings and converted to single quotes
			'id' => 'old-parser-expanded-escape-sequences-in-double-quoted-strings-and-converted-to-single-quotes',

			'path'       => 'arguments[]',
			'filter'     => fn( $expected, $actual ) => is_string( $expected )
				&& is_string( $actual )
				&& $expected !== $actual
				&& str_contains( $expected, "\n" ),
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			// Pretty printer whitespace differs in hook arguments
			'id' => 'pretty-printer-whitespace-differs-in-hook-arguments',

			'path'       => 'arguments[]',
			'filter'     => fn( $expected, $actual ) => $expected !== null
				&& $expected !== $actual
				&& preg_replace( '/\s+/', '', $expected ) === preg_replace( '/\s+/', '', $actual ),
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
	],
	'method'   => [
		[
			// Old parser missed all uses in WP_Block methods; new parser correctly finds them.
			'id'     => 'old-parser-missed-uses-in-wp-block-methods',
			'method' => fn( $name ) => str_starts_with( $name, 'WP_Block::' ),

			'path'       => 'uses',
			'filter'     => fn( $expected, $actual ) => ( $expected === null || $expected === [] )
				&& is_array( $actual ) && ! empty( $actual ),
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			// New parser does not include anonymous class definitions, so their methods are also absent.
			// Old parser emitted them under 'class@anonymous'. Baseline accepts the omission.
			'id'     => 'new-parser-omits-anonymous-class-method-definitions',
			'method' => fn( $name ) => str_starts_with( $name, 'class@anonymous::' ),

			'filter'     => fn( $expected, $actual ) => $actual === [],
			'resolution' => fn( $expected, $actual ) => [ [], $actual ],
		],
		[
			// Old parser source patch replaced ( $this->prop )( $args ) with call_user_func( $this->prop, $args )
			// to avoid aborting on that syntax. New parser correctly records the callable expression.
			'id'     => 'old-parser-source-patch-replaced-callable-property-invocation-with-call-user-func',
			'method' => [
				'WP_HTML_Open_Elements::after_element_push',
				'WP_HTML_Open_Elements::after_element_pop',
			],

			'path'       => 'uses.functions[]',
			'filter'     => fn( $expected, $actual ) => ( $expected['name'] ?? null ) === 'call_user_func'
				&& str_starts_with( $actual['name'] ?? '', '$this->' ),
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		...$callable_baseline,
		$method_uses_class_fully_qualified_name,
		$method_uses_class_resolves_to_classname,
		[
			...$escape_double_quotes,
			'path' => 'doc.{description|long_description}',
		],
		[
			...$fully_qualified_type_in_types,
			'path' => 'aliases[]',
		],
		[
			// Conditional class definition - parser finds first declaration
			'id' => 'conditional-class-definition---parser-finds-first-declaration',

			'method'     => [ 'Services_JSON_Error::__construct', 'Services_JSON_Error::Services_JSON_Error' ],
			'resolution' => function ( $expected, $actual ) {
				_baseline_assert(
					$expected['name'] === $actual['name'],
					"Expected method name '{$expected['name']}' to match actual '{$actual['name']}'"
				);

				return [ $actual ];
			},
		],
		[
			// Old parser did not parse docblock for methods with attributes
			'id' => 'old-parser-did-not-parse-docblock-for-methods-with-attributes',

			'filter'     => fn( $expected, $actual ) => isset( $expected['line'], $actual['line'] )
				&& $expected['line'] === $actual['line'] + 1
				&& $expected['doc'] !== $actual['doc'],
			'resolution' => function ( $expected, $actual ) {
				$expected['line'] = $actual['line'];
				$expected['doc']  = $actual['doc'];

				return [ $expected, $actual ];
			},
		],
		[
			// New parser changes template tag content format
			'id' => 'new-parser-changes-template-tag-content-format',

			'method'     => [ 'Registry::get_class', 'Registry::create' ],
			'path'       => 'doc.tags[]',
			'filter'     => fn( $tag ) => ( $tag['name'] ?? null ) === 'template',
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			// Old parser did not parse docblock correctly
			'id' => 'old-parser-did-not-parse-docblock-correctly',

			'method'     => 'WP_Block_Cloner::clone_instance',
			'path'       => 'doc',
			'resolution' => function ( $expected, $actual ) {
				_baseline_assert(
					_is_empty_doc( $expected ),
					'Expected empty doc from old parser, got: ' . json_encode( $expected )
				);

				return [ $actual ];
			},
		],
		[
			...$numeric_notation_to_decimal,
			'path' => 'uses.methods[].class',
		],
		[
			// $class::method produced "class": ""
			'id' => 'classmethod-produced-class',

			'path'       => 'uses.methods[]',
			'filter'     => fn( $expected, $actual ) => ( $expected['class'] ?? null ) === ''
				&& ( $actual['class'] ?? '' ) !== '',
			'resolution' => function ( $expected, $actual ) {
				$expected['class'] = $actual['class'];

				return [ $expected ];
			},
		],
	],
	'function' => [
		... $callable_baseline,
		$method_uses_class_fully_qualified_name,
		$method_uses_class_resolves_to_classname,
		[
			...$fully_qualified_type_in_types,
			'path' => 'aliases[]',
		],
		[
			...$escape_double_quotes,
			'path' => 'doc.{description|long_description}',
		],
		[
			// Conditional function definition - parser finds first declaration
			'id' => 'conditional-function-definition---parser-finds-first-declaration',

			'function'   => [
				'utf8_encode',
				'utf8_decode',
				'wp_is_valid_utf8',
				'wp_scrub_utf8',
				'wp_has_noncharacters',
			],
			'resolution' => function ( $expected, $actual ) {
				_baseline_assert(
					$expected['name'] === $actual['name'],
					"Expected function name '{$expected['name']}' to match actual '{$actual['name']}'"
				);

				return [ $actual ];
			},
		],
		[
			// Old parser did not parse docblock due to inline comment between docblock and function
			'id' => 'old-parser-did-not-parse-docblock-due-to-inline-comment-between-docblock-and-function',

			'function'   => 'skip',
			'path'       => 'doc',
			'resolution' => function ( $expected, $actual ) {
				_baseline_assert(
					_is_empty_doc( $expected ),
					'Expected empty doc from old parser, got: ' . json_encode( $expected )
				);

				return [ $actual ];
			},
		],
		[
			// Old parser incorrectly splits description with unclosed quotes.
			'id' => 'old-parser-incorrectly-splits-description-with-unclosed-quotes',

			'function'   => 'media_upload_text_after',
			'path'       => 'doc.{description|long_description}',
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			// Old parser did not parse file-level docblock, it belongs to function
			'id' => 'old-parser-did-not-parse-file-level-docblock-it-belongs-to-function',

			'function'   => [ 'block_core_accordion_item_render', 'render_block_core_accordion' ],
			'path'       => 'doc',
			'resolution' => function ( $expected, $actual ) {
				_baseline_assert(
					_is_empty_doc( $expected ),
					'Expected empty doc from old parser, got: ' . json_encode( $expected )
				);

				return [ $actual ];
			},
		],
	],
	'file'     => [
		$method_uses_class_fully_qualified_name,
		$method_uses_class_resolves_to_classname,
		[
			...$escape_double_quotes,
			'path' => 'file.{description|long_description}',
		],
		[
			...$numeric_notation_to_decimal,
			'path' => 'constants[].value',
		],
		[
			...$default_value_quoted_strings_stripped,
			'path' => 'constants[].value',
		],
		[
			// Old parser adds leading backslash to class constant references
			'id' => 'old-parser-adds-leading-backslash-to-class-constant-references',

			'path'       => 'constants[].value',
			'filter'     => fn( $expected, $actual ) => $expected !== null
				&& str_replace( '\\', '', $expected ) === str_replace( '\\', '', $actual ),
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			// Old parser produces empty const names
			'id' => 'old-parser-produces-empty-const-names',

			'path'       => 'constants[].name',
			'filter'     => fn( $expected, $actual ) => $expected === '',
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			'id' => 'old-parser-breaks-off-value-at-quotes',

			'path'       => 'constants[].value',
			'filter'     => fn( $expected, $actual ) => $actual !== null
				&& (
					str_contains( $actual, "'" )
					&& substr( $actual, 0, strpos( $actual, "'" ) ) === $expected
				)
				|| (
					str_contains( $actual, '"' )
					&& substr( $actual, 0, strpos( $actual, '"' ) ) === $expected
				),
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		$deprecation_version_omitted,
		[
			// Old parser did not parse file-level docblock, it belongs to function
			'id' => 'old-parser-did-not-parse-file-level-docblock-it-belongs-to-function',

			'file'       => [
				'wp-includes/blocks/accordion-item.php',
				'wp-includes/blocks/accordion.php',
			],
			'path'       => 'file',
			'resolution' => function ( $expected, $actual ) {
				$tags      = $expected['tags'] ?? [];
				$tag_names = array_column( $tags, 'name' );
				_baseline_assert(
					in_array( 'param', $tag_names, true ) || in_array( 'return', $tag_names, true ),
					'Expected file doc to have function-level tags (@param or @return), got tags: ' . implode(
						', ',
						$tag_names
					)
				);

				return [ $actual ];
			},
		],
		[
			// Old parser did not parse file-level docblock in template files
			'id' => 'old-parser-did-not-parse-file-level-docblock-in-template-files',

			'path'       => 'file',
			'filter'     => fn( $expected, $actual ) => ( $expected['description'] ?? '' ) === ''
				&& ( $actual['description'] ?? '' ) !== '',
			'resolution' => function ( $expected, $actual ) {
				_baseline_assert(
					_is_empty_doc( $expected ),
					'Expected empty doc from old parser, got: ' . json_encode( $expected )
				);

				return [ $actual ];
			},
		],
		[
			// Old parser inconsistent in leaving out empty functions, classes.
			'id' => 'old-parser-inconsistent-in-leaving-out-empty-functions-classes',

			'filter'     => fn( $expected, $actual ) => empty( $expected['functions'] )
				|| empty( $expected['classes'] ),
			'resolution' => function ( $expected, $actual ) {
				if ( empty( $expected['functions'] ) ) {
					unset( $expected['functions'] );
				}
				if ( empty( $expected['classes'] ) ) {
					unset( $expected['classes'] );
				}

				return [ $expected ];
			},
		],
	],
];
