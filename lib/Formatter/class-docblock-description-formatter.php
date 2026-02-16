<?php
/**
 * Docblock_Description_Formatter
 *
 * @package WP_Parser\v2\Reflection
 */

namespace WP_Parser\Formatter;

use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Formatter;

/**
 * Class Docblock_Description_Formatter
 */
class Docblock_Description_Formatter {

	/**
	 * Parsedown.
	 *
	 * @var \Parsedown|null
	 */
	private ?\Parsedown $parsedown;

	private ?Formatter $tag_formatter = null;

	/**
	 * Docblock_Description_Formatter constructor.
	 *
	 * @param bool        $wrap_code_in_pre Wrap plain code HTML elements in a pre element.
	 * @param bool|string $markdown Parse markdown. If "inline" is passed, parse inline markdown.
	 * @param bool        $normalize_newlines Normalize newlines.
	 * @param bool        $join_lines Convert newlines into spaces.
	 * @param bool        $normalize_spaces Reduce multiple consecutive spaces to a single space.
	 */
	public function __construct(
		private bool $wrap_code_in_pre = false,
		private bool|string $markdown = false,
		private bool $normalize_newlines = false,
		private bool $join_lines = false,
		private bool $normalize_spaces = false
	) {
		if ( $this->markdown ) {
			$this->parsedown = \Parsedown::instance();
		}
	}

	/**
	 * Creates a Docbock_Description_Formatter instance.
	 *
	 * @param bool        $wrap_code_in_pre Wrap plain code HTML elements in a pre element.
	 * @param bool|string $markdown Parse markdown. If "inline" is passed, parse inline markdown.
	 * @param bool        $normalize_newlines Normalize newlines.
	 * @param bool        $join_lines Convert newlines into spaces.
	 * @param bool        $normalize_spaces Reduce multiple consecutive spaces to a single space.
	 *
	 * @return self
	 */
	public static function create(
		bool $wrap_code_in_pre = false,
		bool|string $markdown = false,
		bool $normalize_newlines = false,
		bool $join_lines = false,
		bool $normalize_spaces = false
	): self {
		return new self( $wrap_code_in_pre, $markdown, $normalize_newlines, $join_lines, $normalize_spaces );
	}

	/**
	 * Format $text using formatting options.
	 *
	 * @param Description|Tag|string $description Text to format.
	 *
	 * @return string
	 */
	public function format( Description|string|Tag $description ): string {
		$description = $this->to_string( $description );

		if ( $this->wrap_code_in_pre ) {
			$description = $this->wrap_code_in_pre( $description );
		}

		if ( $this->markdown ) {
			$description = $this->parse_markdown( $description );
		}

		if ( $this->normalize_newlines ) {
			$description = $this->normalize_newlines( $description );
		}

		if ( $this->join_lines ) {
			$description = $this->join_lines( $description );
		}

		if ( $this->normalize_spaces ) {
			$description = $this->normalize_spaces( $description );
		}

		return $description;
	}

	/**
	 * Parse markdown.
	 *
	 * @param string $text Text containing markdown.
	 *
	 * @return string
	 */
	private function parse_markdown( string $text ): string {
		if ( 'inline' === $this->markdown ) {
			// Escape * at line starts to prevent Parsedown inline mode from
			// interpreting list bullets as emphasis. Parsedown will convert
			// \* to a literal *, preserving bullets for normalize_newlines().
			$text = preg_replace( '/^(\s*)\* /m', '$1\\\\* ', $text );

			return $this->parsedown->line( $text );
		}

		return $this->parsedown->text( $text );
	}

	/**
	 * Replace newlines with spaces.
	 *
	 * @param string $text Text.
	 *
	 * @return string
	 */
	private function join_lines( string $text ): string {
		return preg_replace( '/[\n\r]+/', ' ', $text );
	}

	/**
	 * Fixes newline handling in parsed text.
	 *
	 * DocBlock lines, particularly for descriptions, generally adhere to a given character width. For sentences and
	 * paragraphs that exceed that width, what is intended as a manual soft wrap (via line break) is used to ensure
	 * on-screen/in-file legibility of that text. These line breaks are retained by phpDocumentor. However, consumers
	 * of this parsed data may believe the line breaks to be intentional and may display the text as such.
	 *
	 * This function fixes text by merging consecutive lines of text into a single line. A special exception is made
	 * for text appearing in `<code>` and `<pre>` tags, as newlines appearing in those tags are always intentional.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	private function normalize_newlines( $text ): string {
		// Non-naturally occurring string to use as temporary replacement.
		$replacement_string = '{{{{{}}}}}';

		// Replace newline characters within 'code' and 'pre' tags with replacement string.
		$text = preg_replace_callback(
			"/(<pre><code[^>]*>)(.+)(?=<\/code><\/pre>)/sU",
			function ( $matches ) use ( $replacement_string ) {
				return preg_replace( '/[\n\r]/', $replacement_string, $matches[1] . $matches[2] );
			},
			$text
		);

		// Insert a newline when \n follows `.`.
		$text = preg_replace(
			"/\.[\n\r]+(?!\s*[\n\r])/m",
			'.<br>',
			$text
		);

		// Insert a new line when \n is followed by what appears to be a list.
		$text = preg_replace(
			"/[\n\r]+(\s*[*-] )(?!\s*[\n\r])/m",
			'<br>$1',
			$text
		);

		// Merge consecutive non-blank lines together by replacing the newlines with a space.
		$text = preg_replace(
			"/[\n\r](?!\s*[\n\r])/m",
			' ',
			$text
		);

		// Restore newline characters into code blocks.
		return str_replace( $replacement_string, "\n", $text );
	}

	/**
	 * Get rendered string representation of description of $value.
	 *
	 * @param Description|string $value
	 *
	 * @return string
	 */
	private function to_string( Description|string $value ): string {
		if ( $value instanceof Description ) {
			return $value->render( $this->tag_formatter );
		}

		return (string) $value;
	}

	/**
	 * If the long description contains a plain HTML <code> element, surround
	 * it with a pre element.
	 *
	 * @param string $description Description.
	 *
	 * @return string
	 */
	private function wrap_code_in_pre( string $description ): string {
		if ( str_contains( $description, '<code>' ) ) {
			return str_replace(
				[ '<code>', "<code>\r\n", "<code>\n", "<code>\r", '</code>' ],
				[ '<pre><code>', '<code>', '<code>', '<code>', '</code></pre>' ],
				$description
			);
		}

		return $description;
	}

	/**
	 * Reduce multiple consecutive spaces to a single space.
	 *
	 * @param string $description
	 *
	 * @return string
	 */
	private function normalize_spaces( string $description ): string {
		$description = preg_replace( '/ {2,}/', ' ', $description );

		return preg_replace( '/(?<=<br>) ++/', '', $description );
	}

	public function set_tag_formatter( ?Formatter $tag_formatter ): void {
		$this->tag_formatter = $tag_formatter;
	}

}
