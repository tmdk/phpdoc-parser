<?php
/**
 * See_Tag
 *
 * @package WP_Parser\Tag
 */

namespace WP_Parser\Tag;

use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\DocBlock\Tags\BaseTag;
use phpDocumentor\Reflection\FqsenResolver;
use phpDocumentor\Reflection\Types\Context as TypeContext;
use phpDocumentor\Reflection\Utils;
use Webmozart\Assert\Assert;
use WP_Parser\Reference\Reference;
use WP_Parser\Reference\Reference_Parser;
use WP_Parser\Serializer\Reference_Serializer;

/**
 * Reflection class for a {@}see tag in a Docblock.
 */
class See_Tag extends BaseTag {

	protected string $name = 'see';

	private Reference $ref;

	public function __construct( Reference $ref, ?Description $description = null ) {
		$this->ref         = $ref;
		$this->description = $description;
	}

	public static function create(
		string $body,
		?FqsenResolver $typeResolver = null,
		?DescriptionFactory $descriptionFactory = null,
		?TypeContext $context = null
	): self {
		Assert::notNull( $descriptionFactory );

		$parts       = Utils::pregSplit( '/\s+/Su', $body, 2 );
		$description = isset( $parts[1] ) ? $descriptionFactory->create( $parts[1], $context ) : null;
		$ref         = ( new Reference_Parser() )->parse( $parts[0] );

		return new self( $ref, $description );
	}

	public function get_reference(): Reference {
		return $this->ref;
	}

	public function __toString(): string {
		$refers      = Reference_Serializer::format( $this->ref );
		$description = $this->description ? $this->description->render() : '';

		return $refers . ( $description !== '' ? ( $refers !== '' ? ' ' : '' ) . $description : '' );
	}
}
