<?php
/**
 * Docblock_Tag_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Author;
use phpDocumentor\Reflection\DocBlock\Tags\BaseTag;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use phpDocumentor\Reflection\DocBlock\Tags\Extends_;
use phpDocumentor\Reflection\DocBlock\Tags\InvalidTag;
use phpDocumentor\Reflection\DocBlock\Tags\Link;
use phpDocumentor\Reflection\DocBlock\Tags\Since;
use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;
use phpDocumentor\Reflection\DocBlock\Tags\Template;
use phpDocumentor\Reflection\DocBlock\Tags\Uses;
use phpDocumentor\Reflection\DocBlock\Tags\Version;
use WP_Parser\Formatter\Docblock_Description_Formatter;
use WP_Parser\Formatter\Inline_Tag_Formatter;
use WP_Parser\Reflection\Docblock_Tag;
use WP_Parser\Tag\Legacy_See_Tag;

/**
 * Class Docblock_Tag_Factory
 */
class Docblock_Tag_Factory {

	private Docblock_Description_Formatter $description_formatter;

	public function __construct() {
		$this->description_formatter = new Docblock_Description_Formatter(
			markdown: 'inline',
			normalize_newlines: true,
			join_lines: true,
		);
		$this->description_formatter->set_tag_formatter( new Inline_Tag_Formatter() );
	}

	public function create( Tag $tag ): Docblock_Tag {
		switch ( true ) {
			case $tag instanceof Since:
			case $tag instanceof Deprecated:
			case $tag instanceof Version:
				$doc_tag = $this->from_versioned_tag( $tag );
				break;
			case $tag instanceof InvalidTag && $tag->getName() === 'since':
				$doc_tag = $this->from_invalid_since_tag( $tag );
				break;
			case $tag instanceof InvalidTag && $tag->getName() === 'version':
				$doc_tag = $this->from_invalid_version_tag( $tag );
				break;
			case $tag instanceof Author:
				$doc_tag = $this->from_author_tag( $tag );
				break;
			case $tag instanceof InvalidTag && $tag->getName() === 'author':
				$doc_tag = $this->from_invalid_author_tag( $tag );
				break;
			case $tag instanceof Link:
				$doc_tag = $this->from_link_tag( $tag );
				break;
			case $tag instanceof Legacy_See_Tag:
				$doc_tag = $this->from_see_tag( $tag );
				break;
			case $tag instanceof InvalidTag && $tag->getName() === 'see':
				$doc_tag = $this->from_invalid_see_tag( $tag );
				break;
			case $tag instanceof Uses:
				$doc_tag = $this->from_uses_tag( $tag );
				break;
			case $tag instanceof InvalidTag && $tag->getName() === 'uses':
				$doc_tag = $this->from_invalid_uses_tag( $tag );
				break;
			case $tag instanceof InvalidTag && $tag->getName() === 'var':
				$doc_tag = $this->from_invalid_var_tag( $tag );
				break;
			case $tag instanceof Template:
				$doc_tag = $this->from_template_tag( $tag );
				break;
			case $tag instanceof Extends_:
				$doc_tag = $this->from_extends_tag( $tag );
				break;
			default:
				$doc_tag = $this->from_tag( $tag );
		}

		$doc_tag->set_name( $tag->getName() );
		if ( $tag instanceof InvalidTag ) {
			$doc_tag->set_is_invalid( true );
		}

		return $doc_tag;
	}

	private function from_versioned_tag( Since|Deprecated|Version $tag ): Docblock_Tag {
		$doc_tag = new Docblock_Tag();

		$version = $tag->getVersion();

		if ( $version ) {
			$doc_tag->set_content( $version );
		}

		$description = $this->get_description( $tag );

		if ( ! $version && ! empty( $description ) ) {
			$doc_tag->set_content( $description );
		}

		if ( ! empty( $description ) ) {
			$doc_tag->set_description( $description );
		}

		return $doc_tag;
	}

	private function get_description( Tag $tag ): string {
		if ( ! $tag instanceof BaseTag ) {
			return '';
		}

		$description = $tag->getDescription();

		if ( null === $description ) {
			return '';
		}

		return trim( $this->description_formatter->format( $description ) );
	}

	private function from_invalid_since_tag( InvalidTag $tag ): Docblock_Tag {
		$doc_tag     = new Docblock_Tag();
		$description = (string) $tag;

		$doc_tag->set_content( $this->description_formatter->format( $description ) );

		return $doc_tag;
	}

	private function from_invalid_version_tag( InvalidTag $tag ): Docblock_Tag {
		$doc_tag     = new Docblock_Tag();
		$description = (string) $tag;

		$doc_tag->set_content( $this->description_formatter->format( $description ) );
		$doc_tag->set_description( $this->description_formatter->format( $description ) );

		return $doc_tag;
	}

	private function from_tag( Tag $tag ): Docblock_Tag {
		$doc_tag = new Docblock_Tag();

		if ( $tag instanceof TagWithType ) {
			$doc_tag->set_type( $tag->getType() );
		}

		if ( method_exists( $tag, 'getVariableName' ) ) {
			$variable = $tag->getVariableName();
			$doc_tag->set_variable( ! empty( $variable ) ? '$' . $tag->getVariableName() : '' );
		}

		$doc_tag->set_content( $this->get_description( $tag ) );

		return $doc_tag;
	}

	private function from_link_tag( Link $tag ): Docblock_Tag {
		$doc_tag     = new Docblock_Tag();
		$description = $this->get_description( $tag );
		if ( $description ) {
			$doc_tag->set_content( $description );
		}
		$doc_tag->set_link( $tag->getLink() );

		return $doc_tag;
	}

	private function from_see_tag( Legacy_See_Tag $tag ): Docblock_Tag {
		$doc_tag = new Docblock_Tag();

		$doc_tag->set_content( $this->get_description( $tag ) );
		$doc_tag->set_reference( $this->normalize_reference( $tag->getReference() ) );

		return $doc_tag;
	}

	private function from_invalid_see_tag( InvalidTag $tag ): Docblock_Tag {
		$doc_tag = new Docblock_Tag();
		$doc_tag->set_content( $this->get_description( $tag ) );
		$doc_tag->set_reference( (string) $tag );

		return $doc_tag;
	}

	private function from_uses_tag( Uses $tag ): Docblock_Tag {
		$doc_tag = new Docblock_Tag();

		$doc_tag->set_content( $this->get_description( $tag ) );
		$doc_tag->set_reference( $this->normalize_reference( (string) $tag->getReference() ) );

		return $doc_tag;
	}

	private function from_invalid_uses_tag( InvalidTag $tag ): Docblock_Tag {
		$doc_tag = new Docblock_Tag();
		$doc_tag->set_content( $this->get_description( $tag ) );
		$doc_tag->set_reference( (string) $tag );

		return $doc_tag;
	}

	private function from_invalid_var_tag( InvalidTag $tag ): Docblock_Tag {
		$doc_tag = new Docblock_Tag();
		$doc_tag->set_variable( '' );
		$doc_tag->set_content( $this->get_description( $tag ) );

		return $doc_tag;
	}

	private function from_author_tag( Author $tag ): Docblock_Tag {
		$doc_tag = new Docblock_Tag();
		$author  = $tag->getAuthorName();
		$email   = $tag->getEmail();

		if ( ! empty( $email ) ) {
			$author .= " <$email>";
		}
		if ( ! empty( $author ) ) {
			$doc_tag->set_content( $author );
		}

		return $doc_tag;
	}

	private function from_invalid_author_tag( InvalidTag $tag ): Docblock_Tag {
		$doc_tag     = new Docblock_Tag();
		$description = (string) $tag;

		$doc_tag->set_content( $this->description_formatter->format( $description ) );

		return $doc_tag;
	}

	private function normalize_reference( string $reference ): string {
		if ( str_starts_with( $reference, '\\' ) && ! str_contains( substr( $reference, 1 ), '\\' ) ) {
			return ltrim( $reference, '\\' );
		}

		return $reference;
	}

	private function from_template_tag( Template $tag ): Docblock_Tag {
		$doc_tag = new Docblock_Tag();

		$content = $tag->getTemplateName();
		$bound   = $tag->getBound();

		if ( $bound ) {
			$content .= ' of ' . $bound;
		}

		$doc_tag->set_content( $content );

		return $doc_tag;
	}

	private function from_extends_tag( Extends_ $tag ): Docblock_Tag {
		$doc_tag = new Docblock_Tag();

		$type = (string) $tag->getType();

		$unqualified_name = '/' .
			'(?(DEFINE) (?<ident> [a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*+ ) ) ' .
			'^ \\ (?&ident) (?! \\ (?&ident) )' .
			'/x';

		if ( preg_match( $unqualified_name, $type ) ) {
			$type = substr( $type, 1 );
		}

		$description = $this->get_description( $tag );

		$content = $type;

		if ( ! empty( $description ) ) {
			$content .= ( $content ? ' ' : '' ) . $description;
		}

		$doc_tag->set_content( $content );

		return $doc_tag;
	}

}
