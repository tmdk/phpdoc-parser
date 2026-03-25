<?php
/**
 * Global_Tag
 *
 * @package WP_Parser\Tag
 */

namespace WP_Parser\Tag;

use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\DocBlock\Tags\BaseTag;
use phpDocumentor\Reflection\FqsenResolver;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Context as TypeContext;
use phpDocumentor\Reflection\Utils;
use Webmozart\Assert\Assert;

/**
 * Reflection class for a @global tag in a Docblock.
 *
 * Parses: @global Type $variable Description
 *         @global $variable
 */
class Global_Tag extends BaseTag {

	protected string $name = 'global';

	private ?Type $type;
	private ?string $variable;

	public function __construct( ?Type $type, ?string $variable, ?Description $description = null ) {
		$this->type        = $type;
		$this->variable    = $variable;
		$this->description = $description;
	}

	public static function create(
		string $body,
		?FqsenResolver $typeResolver = null,
		?DescriptionFactory $descriptionFactory = null,
		?TypeContext $context = null
	): self {
		Assert::notNull( $descriptionFactory );

		$parts = Utils::pregSplit( '/\s+/Su', $body, 3 );

		// @global $variable (no type)
		if ( isset( $parts[0] ) && str_starts_with( $parts[0], '$' ) ) {
			$description = isset( $parts[1] )
				? $descriptionFactory->create( implode( ' ', array_slice( $parts, 1 ) ), $context )
				: null;

			return new self( null, $parts[0], $description );
		}

		$type_string = $parts[0] ?? null;
		$variable    = $parts[1] ?? null;

		$type = null;
		if ( $type_string !== null ) {
			try {
				$type = ( new TypeResolver() )->resolve( $type_string, $context );
			} catch ( \RuntimeException ) {
				// Complex types (e.g. generics with unions) may not resolve — keep raw.
			}
		}

		$description = isset( $parts[2] )
			? $descriptionFactory->create( $parts[2], $context )
			: null;

		return new self( $type, $variable, $description );
	}

	public function getType(): ?Type {
		return $this->type;
	}

	public function get_variable(): ?string {
		return $this->variable;
	}

	public function __toString(): string {
		$parts = array_filter( [ $this->type ? (string) $this->type : null, $this->variable ] );
		$head  = implode( ' ', $parts );

		$description = $this->description ? $this->description->render() : '';

		if ( $description !== '' ) {
			$head .= ( $head !== '' ? ' ' : '' ) . $description;
		}

		return $head;
	}
}
