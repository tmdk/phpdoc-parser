#!/usr/bin/env php
<?php

set_error_handler( static fn( int $errno ) => $errno === E_DEPRECATED, E_DEPRECATED );
require_once __DIR__ . '/../vendor/autoload.php';

use function WP_Parser\get_wp_files;
use function WP_Parser\parse_files;

if ( $_SERVER['argc'] < 3 ) {
	die( "usage {$argv[0]} <source-dir> <outfile>" );
}

$files = get_wp_files( $argv[1] );

$output = parse_files( $files, $argv[1] );

file_put_contents( $argv[2], json_encode( $output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
