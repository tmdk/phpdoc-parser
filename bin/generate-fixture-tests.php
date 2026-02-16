#!/usr/bin/env php
<?php
/**
 * Generate test cases from fixtures
 *
 * This script scans all fixture files and generates one test file per fixture
 * using trait-based testing for file, functions, classes, and methods.
 */

$fixtures_dir = __DIR__ . '/../tests/phpunit/fixtures';
$output_dir   = __DIR__ . '/../tests/phpunit/tests/parser/regression';

// Ensure output directory exists
if ( ! is_dir( $output_dir ) ) {
	mkdir( $output_dir, 0755, true );
}

// Find all fixture files
$fixtures = [];
$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $fixtures_dir, RecursiveDirectoryIterator::SKIP_DOTS )
);

foreach ( $iterator as $file ) {
	if ( $file->getExtension() === 'json' ) {
		$relative_path = str_replace( $fixtures_dir . '/', '', $file->getPathname() );
		$fixtures[]    = $relative_path;
	}
}

// Generate one test file per fixture
$generated_count = 0;
foreach ( $fixtures as $fixture ) {
	$fixture_path = $fixtures_dir . '/' . $fixture;
	$fixture_data = json_decode( file_get_contents( $fixture_path ), true );

	if ( ! isset( $fixture_data['path'] ) ) {
		continue;
	}

	generate_test_file( $fixture, $fixture_data, $fixtures_dir, $output_dir );
	$generated_count++;
}

echo "Generated $generated_count test files from " . count( $fixtures ) . " fixtures.\n";

/**
 * Generate a test file for a single fixture
 */
function generate_test_file( string $fixture, array $fixture_data, string $fixtures_dir, string $output_dir ): void {
	$source_file = $fixture_data['path'];

	// Generate test class name and file name from source file
	$test_class_name = generate_test_class_name( $source_file );
	$test_file_name  = generate_test_file_name( $source_file );

	// Determine subdirectory structure
	$relative_dir = dirname( $fixture );
	if ( $relative_dir === '.' ) {
		$relative_dir = '';
	}
	$test_dir = $output_dir . ( $relative_dir ? '/' . $relative_dir : '' );

	if ( ! is_dir( $test_dir ) ) {
		mkdir( $test_dir, 0755, true );
	}

	$output_file = $test_dir . '/' . $test_file_name . '.php';

	// Analyze fixture data to build providers
	$provider_data = analyze_fixture_data( $fixture, $fixture_data );

	// Generate the test class
	$class_content = generate_test_class_content( $test_class_name, $fixture, $provider_data, $source_file );
	file_put_contents( $output_file, $class_content );

	echo "Generated: $output_file\n";
}

/**
 * Generate a test class name from source file path
 */
function generate_test_class_name( string $source_file ): string {
	// Remove .php extension
	$name = basename( $source_file, '.php' );

	// Convert to PascalCase
	$parts = preg_split( '/([-_.])/', $name, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
	$parts = array_map( fn( $part ) => match ( $part ) {
		'-' => '_',
		'.' => '__',
		'_' => '___',
		default => $part,
	}, $parts );
	$parts = array_map( 'ucfirst', $parts );
	$parts = array_map( fn( $part ) => str_replace( 'Wp', 'WP', $part ), $parts );

	return 'Test_' . implode( '', $parts );
}

/**
 * Generate a test file name from source file path
 */
function generate_test_file_name( string $source_file ): string {
	// Remove .php extension and convert to lowercase
	$name = basename( $source_file, '.php' );
	$name = strtolower( $name );

	return 'test-' . $name;
}

/**
 * Analyze fixture data to determine which traits are needed
 */
function analyze_fixture_data( string $fixture, array $data ): array {
	$has_functions = ! empty( $data['functions'] );
	$has_classes   = ! empty( $data['classes'] );

	$has_methods = false;
	if ( $has_classes ) {
		foreach ( $data['classes'] as $class ) {
			if ( ! empty( $class['methods'] ) ) {
				$has_methods = true;
				break;
			}
		}
	}

	return [
		'functions' => $has_functions,
		'classes'   => $has_classes,
		'methods'   => $has_methods,
	];
}

/**
 * Generate the full test class content
 */
function generate_test_class_content( string $class_name, string $fixture, array $provider_data, string $source_file ): string {
	$namespace = 'WP_Parser\\Tests\\Regression';
	$dirname   = dirname( $source_file );

	if ( $dirname !== '.' ) {
		$parts     = explode( '/', $dirname );
		$parts     = array_map( format_namespace_part( ... ), $parts );
		$namespace .= '\\' . implode( '\\', $parts );
	}

	// Escape fixture path for use in code
	$fixture_escaped = addslashes( $fixture );
	$source_escaped  = addslashes( $source_file );

	// Generate trait use statements
	$traits_code = generate_trait_uses( $provider_data );

	return <<<PHP
<?php
/**
 * {$class_name}
 *
 * @package WP_Parser\Tests
 */

namespace {$namespace};

/**
 * Test class for {$source_file}
 */
class {$class_name} extends \WP_Parser\Tests\Regression_TestCase {

	protected const FIXTURE     = '{$fixture_escaped}';
	protected const SOURCE_FILE = '{$source_escaped}';

{$traits_code}}

PHP;
}

function format_namespace_part( string $part ): string {
	$part = preg_replace( '/^wp-/', 'WP-', $part );
	$part = ucfirst( $part );
	$part = preg_replace_callback( '/-([a-z])/', fn( $matches ) => '_' . strtoupper( $matches[1] ), $part );

	return preg_replace( '/[^a-z0-9_]/i', '_', $part );
}

/**
 * Generate trait use statements based on fixture analysis
 */
function generate_trait_uses( array $provider_data ): string {
	$traits = [ 'Test_File_Trait' ];

	if ( $provider_data['functions'] ) {
		$traits[] = 'Test_Function_Trait';
	}

	if ( $provider_data['classes'] ) {
		$traits[] = 'Test_Class_Trait';
	}

	if ( $provider_data['methods'] ) {
		$traits[] = 'Test_Method_Trait';
	}

	$code = '';
	foreach ( $traits as $trait ) {
		$code .= "\tuse \\WP_Parser\\Tests\\{$trait};\n";
	}

	return $code;
}
