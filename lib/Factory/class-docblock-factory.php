<?php
/**
 * Docblock_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\DocBlockFactory as PhpDocBlockFactory;
use phpDocumentor\Reflection\FqsenResolver;
use phpDocumentor\Reflection\Types;
use PhpParser\Comment\Doc;
use WP_Parser\Formatter\Docblock_Description_Formatter;
use WP_Parser\Formatter\Inline_Tag_Formatter;
use WP_Parser\Reflection\DocBlock;
use WP_Parser\Scope;
use WP_Parser\Tag\Legacy_See_Tag;

/**
 * Factory for creating DocBlock objects from php-parser doc comments.
 */
class Docblock_Factory {

	private PhpDocBlockFactory $phpdoc_factory;
	private Docblock_Description_Formatter $description_formatter;
	private Docblock_Description_Formatter $summary_formatter;
	private ?Scope $scope = null;

	public function __construct( private Docblock_Tag_Factory $tag_factory ) {
		$this->phpdoc_factory = $this->create_docblock_factory();
		$this->phpdoc_factory->registerTagHandler( 'see', Legacy_See_Tag::class );

		$this->description_formatter = new Docblock_Description_Formatter(
			wrap_code_in_pre: true,
			markdown: true,
			normalize_newlines: true
		);
		$this->description_formatter->set_tag_formatter( new Inline_Tag_Formatter() );
		$this->summary_formatter = new Docblock_Description_Formatter(
			join_lines: true,
		);
	}

	/**
	 * Create a DocBlock from a php-parser doc comment.
	 *
	 * @param Doc $doc_comment
	 *
	 * @return DocBlock|null
	 */
	public function create( Doc $doc_comment ): ?DocBlock {
		try {
			$phpdoc = $this->phpdoc_factory->create(
				$doc_comment->getText(),
				$this->get_type_context()
			);
		} catch ( \Exception ) {
			return null;
		}

		$docblock = new DocBlock();
		$docblock->set_description( $this->summary_formatter->format( $phpdoc->getSummary() ) );

		$long_description = $this->description_formatter->format( $phpdoc->getDescription() );
		$docblock->set_long_description( $long_description );

		$tags = [];
		foreach ( $phpdoc->getTags() as $tag ) {
			$tags[] = $this->tag_factory->create( $tag );
		}

		$docblock->set_tags( $tags );

		return $docblock;
	}

	/**
	 * Create a DocBlockFactory with our PHPStan_Tag_Factory to avoid bullet
	 * list duplication in hash notation tags.
	 */
	private function create_docblock_factory(): PhpDocBlockFactory {
		$fqsen_resolver = new FqsenResolver();
		$tag_factory    = Standard_Tag_Factory::create_instance( $fqsen_resolver );

		$description_factory = new DescriptionFactory( $tag_factory );

		return new PhpDocBlockFactory( $description_factory, $tag_factory );
	}

	private function get_type_context(): ?Types\Context {
		if ( $this->scope === null ) {
			return null;
		}

		return new Types\Context(
			$this->scope->namespace()->get_name(),
			$this->scope->namespace()->get_aliases()
		);
	}

	/**
	 * Creates an empty DocBlock.
	 *
	 * @return DocBlock
	 */
	public function create_empty(): DocBlock {
		return new DocBlock();
	}

	/**
	 * @param Scope $scope
	 */
	public function set_scope( Scope $scope ): void {
		$this->scope = $scope;
	}
}
