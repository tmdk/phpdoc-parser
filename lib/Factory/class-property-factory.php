<?php
/**
 * Property_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use PhpParser\Node;
use WP_Parser\Formatter\Templated_String_Printer;
use WP_Parser\Reflection\Property;

/**
 * Factory for creating Property objects from php-parser nodes.
 */
class Property_Factory {

	private Templated_String_Printer $printer;
	private Docblock_Factory $docblock_factory;

	public function __construct( Docblock_Factory $docblock_factory ) {
		$this->printer          = new Templated_String_Printer();
		$this->docblock_factory = $docblock_factory;
	}

	/**
	 * Create a Property from a php-parser property node.
	 *
	 * @param Node\Stmt\Property $node
	 *
	 * @return Property[]
	 */
	public function create( Node\Stmt\Property $node ): array {
		$properties = [];

		// A property statement can declare multiple properties
		foreach ( $node->props as $prop ) {
			$property = new Property();
			$property->set_name( '$' . $prop->name->toString() );
			$property->set_line( $node->getStartLine() );
			$property->set_end_line( $node->getEndLine() );
			$property->set_static( $node->isStatic() );

			// Visibility
			if ( $node->isPublic() ) {
				$property->set_visibility( 'public' );
			} elseif ( $node->isProtected() ) {
				$property->set_visibility( 'protected' );
			} elseif ( $node->isPrivate() ) {
				$property->set_visibility( 'private' );
			}

			// Default value
			if ( $prop->default !== null ) {
				$property->set_default( $this->printer->print_node( $prop->default ) );
			}

			// Docblock
			$doc_comment = $node->getDocComment();
			if ( $doc_comment ) {
				$property->set_doc_block( $this->docblock_factory->create( $doc_comment ) );
			} else {
				$property->set_doc_block( $this->docblock_factory->create_empty() );
			}

			$properties[] = $property;
		}

		return $properties;
	}
}
