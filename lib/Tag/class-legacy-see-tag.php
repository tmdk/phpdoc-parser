<?php
/**
 * Legacy_See_Tag
 *
 * @package WP_Parser\v2\tags
 */

namespace WP_Parser\Tag;

use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\DocBlock\Tags\BaseTag;
use phpDocumentor\Reflection\FqsenResolver;
use phpDocumentor\Reflection\Types\Context as TypeContext;
use phpDocumentor\Reflection\Utils;
use Webmozart\Assert\Assert;

/**
 * Class Legacy_See_Tag
 *
 * Exists to emulate the legacy phpdocumentor see tag behavior, which did not try to resolve
 * the reference to a FQSEN.
 */
class Legacy_See_Tag extends BaseTag {

	protected string $name = 'see';
	protected string $refers;

	/**
	 * Initializes this tag.
	 */
	public function __construct( string $refers, ?Description $description = null ) {
		$this->refers      = $refers;
		$this->description = $description;
	}

	public static function create(
		string $body,
		?FqsenResolver $type_resolver = null,
		?DescriptionFactory $description_factory = null,
		?TypeContext $context = null
	): self {
		Assert::notNull( $description_factory );

		$parts       = Utils::pregSplit( '/\s+/Su', $body, 2 );
		$refers      = $parts[0] ?? '';
		$description = isset( $parts[1] ) ? $description_factory->create( $parts[1], $context ) : null;

		return new static( $refers, $description );
	}

	/**
	 * Returns the ref of this tag.
	 */
	public function getReference(): string {
		return $this->refers;
	}

	/**
	 * Returns a string representation of this tag.
	 */
	public function __toString(): string {
		if ( $this->description ) {
			$description = $this->description->render();
		} else {
			$description = '';
		}

		$refers = $this->refers;

		return $refers . ( $description !== '' ? ( $refers !== '' ? ' ' : '' ) . $description : '' );
	}
}
