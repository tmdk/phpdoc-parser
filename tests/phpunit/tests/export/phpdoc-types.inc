<?php

/**
 * Test type alias normalization.
 *
 * phpDocumentor normalizes type aliases: integer->int, boolean->bool, double->float.
 *
 * @param integer $count An integer value.
 * @param boolean $flag  A boolean value.
 * @param double  $ratio A double value.
 * @return boolean Whether it worked.
 */
function test_type_aliases( $count, $flag, $ratio ) {
	return true;
}

/**
 * Test capitalized builtin type normalization.
 *
 * phpDocumentor normalizes capitalized builtin PHP types to lowercase.
 *
 * @param String $name  A string value.
 * @param Array  $items An array value.
 * @return String The name.
 */
function test_capitalized_types( $name, $items ) {
	return $name;
}

/**
 * Test generic array shorthand normalization.
 *
 * phpDocumentor normalizes array<Type> to Type[].
 *
 * @param array<int>   $numbers  Array of integers.
 * @param array<mixed> $anything Array of mixed values.
 * @return array<string> Array of strings.
 */
function test_generic_array( $numbers, $anything ) {
	return [];
}

/**
 * Test untyped parameter default.
 *
 * When a param tag has no type, the parser defaults to mixed.
 *
 * @param $value A value without explicit type.
 */
function test_untyped_param( $value ) {}

/**
 * Test pass-by-reference parameter variable extraction.
 *
 * Parser correctly extracts the variable name from &$var.
 *
 * @param string $regular A regular parameter.
 * @param string &$name   A pass-by-reference parameter.
 */
function test_ref_param( $regular, &$name ) {}

/**
 * Test string literal type quote normalization.
 *
 * phpDocumentor normalizes single-quoted string literals to double-quoted.
 *
 * @param 'yes'|'no' $flag A flag value.
 * @return 'success'|'failure' The result.
 */
function test_string_literal_types( $flag ) {
	return 'success';
}
