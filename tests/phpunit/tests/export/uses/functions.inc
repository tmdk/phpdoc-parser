<?php

// File-level function calls.
wp_enqueue_script( 'my-script' );
esc_html( $text );

// Include/require at file level.
include 'header.php';
require 'config.php';
include_once 'utils.php';
require_once 'bootstrap.php';

// Hook uses at file level.
do_action( 'init' );
$val = apply_filters( 'the_content', $content );

function func_with_calls() {
	wp_enqueue_style( 'my-style' );
	add_action( 'wp_head', 'callback' );

	do_action( 'custom_action', $arg );
	$result = apply_filters( 'custom_filter', $value );
}

class Uses_Class {
	public function method_with_calls() {
		wp_nonce_field( 'action', 'nonce' );
		get_option( 'siteurl' );
	}
}
