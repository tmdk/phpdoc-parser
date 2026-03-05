<?php
/**
 * Inline_Tag_Formatter
 *
 * @package WP_Parser\Formatter
 */

namespace WP_Parser\Formatter;

use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Formatter;
use phpDocumentor\Reflection\DocBlock\Tags\InvalidTag;
use phpDocumentor\Reflection\DocBlock\Tags\See;

/**
 * Class Inline_Tag_Formatter
 */
class Inline_Tag_Formatter implements Formatter {

	/**
	 * @inheritDoc
	 */
	public function format( Tag $tag ): string {
		return trim( '@' . $tag->getName() . ' ' . $this->format_tag_body( $tag ) );
	}

	private function format_tag_body( Tag $tag ): string {
		if ( $tag instanceof See || $tag instanceof InvalidTag && $tag->getName() === 'see' ) {
			$reference = (string) $tag;
			if ( str_starts_with( $reference, '\\' ) &&
				! str_contains( substr( $reference, 1 ), '\\' ) &&
				str_contains( $reference, '()' ) ) {
				return ltrim( $reference, '\\' );
			}

			return $reference;
		}

		return (string) $tag;
	}
}
