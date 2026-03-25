<?php

do_action( 'plain_action' );
do_action( "action_with_double_quotes" );
do_action( $variable . '-action' );
do_action( "another-{$variable}-action" );
do_action( 'hook_' . $object->property . '_pre' );
apply_filters( 'plain_filter', $variable, $filter_context );

function hooks_in_control_structures() {
	if ( $condition ) {
		/**
		 * Hook inside if block.
		 */
		do_action( 'hook_in_if' );
	} elseif ( $other ) {
		/**
		 * Hook inside elseif block.
		 */
		do_action( 'hook_in_elseif' );
	} else {
		/**
		 * Hook inside else block.
		 */
		do_action( 'hook_in_else' );
	}

	foreach ( $items as $item ) {
		/**
		 * Hook inside foreach with early return.
		 */
		$result = apply_filters( 'hook_in_foreach', $item );
		if ( ! $result ) {
			return;
		}
	}

	switch ( $type ) {
		case 'post':
			/**
			 * Hook inside switch/case.
			 */
			do_action( 'hook_in_switch', $type );
			break;
	}
}

function hooks_in_expressions() {
	echo esc_url( apply_filters( 'hook_in_func_arg', $url ) );

	$output = sprintf(
		'<div>%s</div>',
		/**
		 * Hook inside sprintf argument.
		 */
		apply_filters( 'hook_in_sprintf', $content )
	);

	if ( ! apply_filters( 'hook_in_if_condition', true ) ) {
		return;
	}

	$value = (bool) apply_filters( 'hook_in_cast', $value );

	$time = time() + apply_filters( 'hook_in_arithmetic', 3600 );

	return true === apply_filters( 'hook_in_return_comparison', $check );
}

function multi_line_hook() {
	do_action(
		'multi_line_hook',
		$arg1,
		$arg2,
		$arg3
	);
}

function separated_docblock() {
	/**
	 * Hook with intervening code between docblock and call.
	 */
	$var = 'something';
	do_action( 'hook_separated_by_code', $var );
}

function documented_elsewhere() {
	/** This filter is documented in wp-includes/post.php */
	$value = apply_filters( 'hook_documented_elsewhere', $value );
}

function hook_with_variable_only() {
	do_action( $hook_name );
}

function hook_with_concat_and_variable() {
	do_action( $type . '_loaded' );
}

function hook_deprecated_types() {
	do_action_deprecated( 'old_deprecated_action', array(), '3.0.0' );
	apply_filters_deprecated( 'old_deprecated_filter', array( $value ), '2.5.0' );
}

function hook_with_class_constant_args() {
	do_action( 'hook_with_class_args', \WP_Post::class, My_Plugin::VERSION );
}
