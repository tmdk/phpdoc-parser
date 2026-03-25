<?php

/**
 * This is the file-level docblock summary.
 *
 * This is the file-level docblock description, which may span multiple lines. In
 * fact, this one does. It spans more than two full lines, continuing on to the
 * third line.
 *
 * @since 1.5.0
 */

/**
 * This is a function docblock.
 *
 * This function is just a test, but we've added this description anyway.
 *
 * @since 2.6.0
 *
 * @param string $var A string value.
 * @param int    $num A number.
 *
 * @return bool Whether the function was called correctly.
 */
function test_func( $var, $num ) {

	// In real life, there *might* be more stuff in here.
	return true;
}

/**
 * This is a class docblock.
 *
 * This is the more wordy description: This is a comment with two *'s at the start,
 * which means that it is a doc comment. Docblock comments are comment blocks used
 * to document code. This one documents the Test_Class class.
 *
 * @since 3.5.2
 */
class Test_Class {

	/**
	 * This is a docblock for a class property.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $a_string;

	/**
	 * This is a method docblock.
	 *
	 * @since 4.5.0
	 *
	 * @param mixed $var A parameter.
	 * @param array $arr Another parameter.
	 *
	 * @return mixed The first param.
	 */
	public function test_method( $var, $arr ) {
		return $var;
	}
}

/**
 * A filter.
 */
$var = apply_filters( 'test_filter', $var );

/**
 * A reference array filter.
 */
$var = apply_filters_ref_array( 'test_ref_array_filter', array( &$var ) );

/**
 * A test action.
 *
 * @since 3.7.0
 *
 * @param WP_Post $post Post object.
 */
do_action( 'test_action', $post );

do_action( 'undocumented_hook' );

/**
 * A reference array action.
 */
do_action_ref_array( 'test_ref_array_action', array( &$var ) );

/**
 * A deprecated function.
 *
 * @since 1.0.0
 * @deprecated 3.0.0 Use new_func() instead.
 */
function deprecated_func() {}

/**
 * Deprecated without version.
 *
 * @deprecated Use something_else() instead.
 */
function deprecated_no_version() {}

/**
 * A function by a single author.
 *
 * @author John Doe
 */
function authored_func() {}

/**
 * A function with author name and email.
 *
 * @author Jane Smith <jane@example.com>
 */
function authored_email_func() {}

/**
 * A function with a link tag.
 *
 * @link https://example.com/docs
 */
function linked_func() {}

/**
 * A function with a link and description.
 *
 * @link https://example.com/api API documentation
 */
function linked_desc_func() {}

/**
 * A function with a version tag.
 *
 * @version 2.1.0
 */
function versioned_func() {}

/**
 * A function with a source tag.
 *
 * @source 10 20 Some source content.
 */
function sourced_func() {}

/**
 * A function with a uses tag.
 *
 * @uses \WP_Query::get_posts() To fetch the posts.
 */
function uses_tag_func() {}

/**
 * A function with a covers tag.
 *
 * @covers \WP_Query::get_posts
 */
function covers_tag_func() {}

/**
 * A function with a global tag.
 *
 * @global WP_Locale $wp_locale WordPress date/time locale object.
 */
function global_tag_func() {}

/**
 * A function with a malformed since tag.
 *
 * @since
 */
function malformed_since_func() {}

/**
 * A function with a param without type.
 *
 * @param $value
 */
function param_no_type_func( $value ) {}

/**
 * A function with a broken return tag.
 *
 * @return
 */
function broken_return_func() {}

/** */
function empty_docblock_func() {}

function undocumented_func() {}

/**
 * A function with inline {@link https://example.com link text} in the description.
 */
function inline_link_func() {}

/**
 * A function with inline {@see WP_Query} reference.
 */
function inline_see_func() {}

/**
 * A function with **bold** and *italic* and `code` in the description.
 */
function markdown_func() {}

/**
 * A function with a code block.
 *
 *     $result = do_something();
 *     echo $result;
 */
function code_block_func() {}

/**
 * A function with lists.
 *
 * - Item one
 * - Item two
 * - Item three
 *
 * 1. First
 * 2. Second
 */
function list_func() {}

/**
 * First paragraph of the long description.
 *
 * Second paragraph of the long description.
 *
 * Third paragraph of the long description.
 */
function multi_paragraph_func() {}

/**
 * A function with HTML entities &amp; special &lt;characters&gt;.
 */
function html_entities_func() {}

/**
 * This is a description that spans
 * multiple lines but should be joined
 * into a single line.
 */
function line_joining_func() {}

/**
 * Intersection type in @param.
 *
 * @param Countable&Traversable $collection A collection.
 * @return Countable&Traversable The same collection.
 */
function test_intersection_phpdoc( $collection ) {
	return $collection;
}

/**
 * Generic type in @param.
 *
 * @param Collection<string, WP_Post> $posts A collection of posts.
 * @return array<int, string> Mapped values.
 */
function test_generic_types( $posts ) {
	return [];
}

/**
 * Array shape in @param.
 *
 * @param array{id: int, name: string, active?: bool} $data The data shape.
 * @return array{success: bool, message: string} The result shape.
 */
function test_array_shapes( $data ) {
	return [ 'success' => true, 'message' => '' ];
}

/**
 * List shape in @param.
 *
 * @param list{int, string} $pair A typed pair.
 */
function test_list_shapes( $pair ) {}

/**
 * Object shape in @param.
 *
 * @param object{name: string, age: int} $person A person object.
 */
function test_object_shapes( $person ) {}

/**
 * Callable type in @param.
 *
 * @param callable(int, string): bool $callback A callback function.
 */
function test_callable_types( $callback ) {}

/**
 * Conditional return type.
 *
 * @template T
 * @param T $value The value.
 * @return ($value is string ? int : float) The conditional result.
 */
function test_conditional_types( $value ) {
	return is_string( $value ) ? strlen( $value ) : 1.0;
}

/**
 * Class-string type.
 *
 * @param class-string<WP_Post> $class The class name.
 * @return class-string The result class.
 */
function test_class_string( $class ) {
	return $class;
}

/**
 * Const expression types.
 *
 * @param Foo::BAR $value A constant value.
 * @param Foo::BAR_* $pattern A constant pattern.
 */
function test_const_expressions( $value, $pattern ) {}

/**
 * Offset access type.
 *
 * @template T of array
 * @template K of key-of<T>
 * @param T $arr The array.
 * @param K $key The key.
 * @return T[K] The value.
 */
function test_offset_access( $arr, $key ) {
	return $arr[ $key ];
}

/**
 * Parenthesized type.
 *
 * @param (int|string)[] $items Array of int or string.
 */
function test_parenthesized_types( $items ) {}

class PHPDoc_Return_Types {
	/**
	 * Static return type in PHPDoc.
	 *
	 * @return static The current instance.
	 */
	public function test_static_return() {
		return new static();
	}

	/**
	 * $this return type.
	 *
	 * @return $this The current instance for chaining.
	 */
	public function test_this_return() {
		return $this;
	}
}

/**
 * Never/void/no-return types.
 *
 * @return never This never returns.
 */
function test_never_type() {
	throw new \Exception();
}

/**
 * @return void
 */
function test_void_type() {}

/**
 * @return no-return
 */
function test_no_return_type() {
	exit;
}

/**
 * WordPress @type argument hash — simple scalars.
 *
 * @param array $args {
 *     Optional. Arguments.
 *
 *     @type string $name         The name.
 *     @type int|string $value    The value.
 *     @type int[] $ids           Array of IDs.
 *     @type WP_Post $post        A post object.
 *     @type 'auto'|'low'|'high' $priority Priority level.
 *     @type array<string, mixed> $schema  The schema.
 * }
 */
function test_type_hash_args( $args = array() ) {}

/**
 * WordPress @type with optional and numeric entries.
 *
 * @param array $args {
 *     Arguments.
 *
 *     @type string $0             First positional arg.
 *     @type string $font-family   Font family.
 *     @type array  ...$0 {
 *         Nested args.
 *
 *         @type string $key A key.
 *     }
 * }
 */
function test_type_hash_special( $args = array() ) {}

/**
 * Key-of type.
 *
 * @param key-of<array{a: int, b: string}> $key A key from the shape.
 */
function test_key_of( $key ) {}

/**
 * Value-of type.
 *
 * @param value-of<array{a: int, b: string}> $value A value from the shape.
 */
function test_value_of( $value ) {}

/**
 * A function with @see tags that have references and descriptions.
 *
 * @see Some_Class::method() Does something useful.
 * @see https://example.com/docs
 * @see Another_Class
 */
function see_tag_func() {}

/**
 * A function with @uses tags with references and descriptions.
 *
 * @uses \WP_Query::get_posts() Fetches the posts.
 * @uses \wp_list_pluck() For extracting fields.
 */
function uses_tag_with_desc_func() {}

/**
 * A function with a @link tag that has no description (triggers HTML link generation).
 *
 * @link https://developer.wordpress.org/reference
 */
function link_no_desc_func() {}

/**
 * A function with a @link tag that has a URL ending in a period.
 *
 * @link https://example.com/docs.
 */
function link_trailing_dot_func() {}

/**
 * A function with a valid @author tag with name and email.
 *
 * @author WordPress Core Team <team@wordpress.org>
 */
function author_valid_email_func() {}

do_action_deprecated( 'deprecated_action', array( $arg ), '3.0.0', 'new_action' );
apply_filters_deprecated( 'deprecated_filter', array( $val ), '2.5.0' );
do_action_ref_array( 'ref_array_action_2', array( &$a, &$b ) );
