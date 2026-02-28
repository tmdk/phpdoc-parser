<?php

namespace WP_Parser;

/**
 * @param string $directory
 *
 * @return array|\WP_Error
 */
function get_wp_files( string $directory ): \WP_Error|array {
	$iterableFiles = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator( $directory )
	);

	$files = [];

	try {
		foreach ( $iterableFiles as $file ) {
			if ( 'php' !== $file->getExtension() ) {
				continue;
			}

			$files[] = $file->getPathname();
		}
	} catch ( \UnexpectedValueException ) {
		return new \WP_Error(
			'unexpected_value_exception',
			sprintf( 'Directory [%s] contained a directory we can not recurse into', $directory )
		);
	}

	return $files;
}

/**
 * @param array  $files
 * @param string $root
 *
 * @return array
 */
function parse_files( array $files, string $root ): array {
	$parser     = new Parser();
	$serializer = new Serializer\Serializer();
	$output     = [];

	foreach ( $files as $filename ) {
		$filename    = ltrim( substr( $filename, strlen( $root ) ), DIRECTORY_SEPARATOR );
		$source_file = Source_File::from_file( $filename, $root );
		$output[]    = $serializer->serialize( $parser->parse_file( $source_file ) );
	}

	return $output;
}
