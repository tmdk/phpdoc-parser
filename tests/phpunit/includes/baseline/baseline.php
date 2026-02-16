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

$string_literal_quote_normalization = [
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
	// Old parser keeps leading backslash in @see refers
	'id' => 'old-parser-keeps-leading-backslash-in-see-refers',

	'filter'     => fn( $expected, $actual ) => $expected !== null
		&& isset( $expected['refers'], $actual['refers'] )
		&& str_starts_with( $expected['refers'], '\\' )
		&& ltrim( $expected['refers'], '\\' ) === $actual['refers'],
	'resolution' => function ( $expected, $actual ) {
		$expected['refers'] = $actual['refers'];

		return [ $expected ];
	},
];

$callable_baseline = [
	[
		...$normalize_whitespace_in_tags,
		'path' => 'doc.tags[].{content|description}',
	],
	[
		...$inline_tag_whitespace,
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
		'filter'     => fn( $expected, $actual ) => $expected !== null && isset( TYPE_ALIASES[ $expected ] ) && TYPE_ALIASES[ $expected ] === $actual,
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
	[
		// Old parser serializes raw AST for anonymous class constructor, new parser uses extends class name
		'id' => 'old-parser-serializes-raw-ast-for-anonymous-class-constructor-new-parser-uses-extends-class-name',

		'path'       => 'uses.methods[]',
		'filter'     => fn( $expected, $actual ) => $expected !== null
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
		'filter'     => fn( $expected ) => $expected !== null && is_array( $expected['name'] ),
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
			...$invalid_type_syntax,
			'path' => 'properties[].doc.tags[]',
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
			'filter'     => fn( $expected, $actual ) => $expected !== null && isset( TYPE_ALIASES[ $expected ] ) && TYPE_ALIASES[ $expected ] === $actual,
			'resolution' => fn( $expected, $actual ) => [ $actual ],
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
	],
	'hook'     => [
		...$callable_baseline,
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
		...$callable_baseline,
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

			'filter'     => fn( $expected, $actual ) => $expected !== null
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
			'filter'     => fn( $tag ) => $tag['name'] === 'template',
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
		[
			...$numeric_notation_to_decimal,
			'path' => 'constants[].value',
		],
		[
			// Old parser adds leading backslash to class constant references
			'id' => 'old-parser-adds-leading-backslash-to-class-constant-references',

			'path'       => 'constants[].value',
			'filter'     => fn( $expected, $actual ) => $expected !== null
				&& str_starts_with( $expected, '\\' )
				&& ltrim( $expected, '\\' ) === $actual,
			'resolution' => fn( $expected, $actual ) => [ $actual ],
		],
		[
			// Old parses omits uses
			'id' => 'old-parses-omits-uses',

			'file'       => [
				'wp-includes/SimplePie/src/Cache/Base.php',
				'wp-includes/SimplePie/src/HTTP/Parser.php',
				'wp-includes/SimplePie/src/Net/IPv6.php',
				'wp-includes/SimplePie/src/XML/Declaration/Parser.php',
				'wp-includes/SimplePie/src/Author.php',
				'wp-includes/SimplePie/src/Caption.php',
				'wp-includes/SimplePie/src/Category.php',
				'wp-includes/SimplePie/src/Copyright.php',
				'wp-includes/SimplePie/src/Credit.php',
				'wp-includes/SimplePie/src/Enclosure.php',
				'wp-includes/SimplePie/src/File.php',
				'wp-includes/SimplePie/src/IRI.php',
				'wp-includes/SimplePie/src/Locator.php',
				'wp-includes/SimplePie/src/Misc.php',
				'wp-includes/SimplePie/src/Parser.php',
				'wp-includes/SimplePie/src/Rating.php',
				'wp-includes/SimplePie/src/Restriction.php',
				'wp-includes/SimplePie/src/Sanitize.php',
				'wp-includes/SimplePie/src/SimplePie.php',
			],
			'path'       => 'uses',
			'resolution' => function ( $expected, $actual ) {
				_baseline_assert(
					$expected === null,
					'Expected null uses from old parser, got: ' . json_encode( $expected )
				);

				return [ $actual ];
			},
		],
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
