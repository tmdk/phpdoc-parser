<?php
/**
 * Source_File
 *
 * @package WP_Parser
 */

namespace WP_Parser;

/**
 * Represents a source file to be parsed, either from disk or from a pre-loaded string.
 *
 * The source string is private so callers cannot retain a reference to it,
 * allowing the Parser to free it (by unsetting the Source_File) after parsing.
 */
class Source_File {

	private function __construct(
		private readonly string $filename,
		private readonly string $base_dir,
		private ?string $source,
	) {
	}

	/**
	 * Create a Source_File that will be read from disk on demand.
	 *
	 * @param string $filename Relative filename (used for metadata and path construction).
	 * @param string $base_dir Root directory, same as Parser's $root_dir.
	 */
	public static function from_file( string $filename, string $base_dir ): self {
		return new self( $filename, $base_dir, null );
	}

	/**
	 * Create a Source_File with pre-loaded source code.
	 *
	 * @param string $filename Relative filename (used for metadata).
	 * @param string $source PHP source code.
	 */
	public static function from_string( string $filename, string $source, string $base_dir = '' ): self {
		return new self( $filename, $base_dir, $source );
	}

	/**
	 * Return the source code, reading from disk if not already loaded.
	 */
	public function get_source(): string {
		if ( $this->source !== null ) {
			return $this->source;
		}

		$path = rtrim( $this->base_dir, '/' ) . '/' . ltrim( $this->filename, '/' );

		$source = file_get_contents( $path );

		if ( false === $source ) {
			throw new \RuntimeException( "Could not read file: $path" );
		}

		$this->source = $source;

		return $this->source;
	}

	/**
	 * @return string
	 */
	public function get_base_dir(): string {
		return $this->base_dir;
	}

	/**
	 * @return string
	 */
	public function get_filename(): string {
		return $this->filename;
	}
}
