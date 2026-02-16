<?php
/**
 * Docblock_Tag_Serializer
 *
 * @package WP_Parser\Serializer
 */

namespace WP_Parser\Serializer;

use WP_Parser\Reflection\Docblock_Tag;

/**
 * Class Docblock_Tag_Serializer
 */
class Docblock_Tag_Serializer implements Serializer_Interface {

	public function __construct( private Object_Serializer $object_serializer ) {
	}

	public function serialize( mixed $value ): mixed {
		assert( $value instanceof Docblock_Tag );

		$result = $this->object_serializer->serialize( $value );

		if ( 'since' === $value->get_name() ) {
			$result = $this->serialize_since_tag( $result, $value );
		}

		if ( 'link' === $value->get_name() ) {
			$result = $this->serialize_link_tag( $result, $value );
		}

		if ( 'author' === $value->get_name() ) {
			$result = $this->serialize_author_tag( $result, $value );
		}

		return $result;
	}

	public function supports( mixed $value ): bool {
		return $value instanceof Docblock_Tag;
	}

	private function serialize_since_tag( array $result, Docblock_Tag $tag ): array {
		if ( $tag->is_invalid() && isset( $result['content'] ) ) {
			$result['description'] = $result['content'];
		}

		return $result;
	}

	private function serialize_link_tag( array $result, Docblock_Tag $tag ): array {
		if ( ! empty( $tag->get_content() ) ) {
			return $result;
		}

		$link   = $tag->get_link();
		$suffix = '';

		// Strip trailing sentence punctuation from the URL for the anchor tag,
		// but preserve it after the closing </a> tag.
		if ( preg_match( '/^(.+?)(\.+)$/', $link, $m ) ) {
			$link   = $m[1];
			$suffix = $m[2];
		}

		$result['content'] = sprintf(
			'<a href="%s">%s</a>%s',
			htmlspecialchars( $link, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE ),
			$link,
			$suffix
		);

		$result['link'] = $tag->get_link();

		$order = array_flip( [ 'name', 'content', 'link' ] );

		uksort(
			$result,
			fn( $a, $b ) => ( $order[ $a ] ?? PHP_INT_MAX ) <=> ( $order[ $b ] ?? PHP_INT_MAX )
		);

		return $result;
	}

	private function serialize_author_tag( mixed $result, Docblock_Tag $tag ): array {
		if ( ! $tag->is_invalid() ) {
			$result['content'] = preg_replace(
				'/^(.+) <([^>]+)>$/',
				'\1 <a href="mailto:\2">\2</a>',
				$result['content']
			);
		}

		return $result;
	}

}
