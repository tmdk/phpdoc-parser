#!/usr/bin/env php
<?php

ini_set( 'memory_limit', '-1' );

if ( ! isset( $argv[1] ) ) {
	fprintf( STDERR, "Usage: php %s <export.json>\n", $argv[0] );
	exit( 1 );
}

$export = json_decode( file_get_contents( $argv[1] ), true );

$fixture_dir = __DIR__ . '/../tests/phpunit/fixtures/';

// Track all fixture paths to detect conflicts.
$fixture_paths = [];

/**
 * Registers a fixture path, resolving conflicts by adding numeric suffixes.
 *
 * @param string $path The fixture file path.
 * @param array  $meta The meta information for debugging conflicts.
 *
 * @return string The resolved path (with suffix if needed).
 */
function register_fixture_path( string $path, array $meta ): string {
	global $fixture_paths;

	if ( ! isset( $fixture_paths[ $path ] ) ) {
		$fixture_paths[ $path ] = $meta;

		return $path;
	}

	// Find a unique path by adding numeric suffix.
	$dir      = dirname( $path );
	$basename = basename( $path, '.json' );
	$suffix   = 2;

	do {
		$new_path = "$dir/$basename-$suffix.json";
		$suffix++;
	} while ( isset( $fixture_paths[ $new_path ] ) );

	$fixture_paths[ $new_path ] = $meta;

	return $new_path;
}

/**
 * Writes a fixture file if it doesn't already exist.
 *
 * @param string $path The fixture file path.
 * @param array  $meta The meta information about the fixture (for conflict detection).
 * @param array  $data The fixture data (written directly to file).
 */
function write_fixture( string $path, array $meta, array $data ): void {
	$path = register_fixture_path( $path, $meta );

	if ( ! file_exists( dirname( $path ) ) ) {
		mkdir( dirname( $path ), 0777, true );
	}

	if ( ! file_exists( $path ) ) {
		file_put_contents( $path, json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	}
}

foreach ( $export as $file ) {
	$file['root'] = '/wordpress';

	$file_path     = $file['path'];
	$file_dirname  = dirname( $file_path );
	$file_basename = basename( $file_path );
	$file_dir      = $fixture_dir . ( $file_dirname === '.' ? '' : "$file_dirname/" );
	$file_prefix   = strtolower( $file_basename );

	// Create one fixture file per source file containing all data.
	$file_fixture_path = $file_dir . "$file_prefix.json";
	write_fixture( $file_fixture_path, [ 'file' => $file_path ], $file );
}

fprintf( STDOUT, "Generated %d fixture paths.\n", count( $fixture_paths ) );
