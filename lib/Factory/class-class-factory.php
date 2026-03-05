<?php
/**
 * Class_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use PhpParser\Node;
use WP_Parser\Formatter\Templated_String_Printer;
use WP_Parser\Reflection\Class_;
use WP_Parser\Reflection\Name;

/**
 * Factory for creating Class_ objects from php-parser nodes.
 */
class Class_Factory {
	private Docblock_Factory $docblock_factory;
	private Templated_String_Printer $printer;

	public function __construct( Docblock_Factory $docblock_factory ) {
		$this->docblock_factory = $docblock_factory;
		$this->printer          = new Templated_String_Printer();
	}

	/**
	 * Create a Class_ from a php-parser class node.
	 *
	 * @param Node\Stmt\Class_ $node
	 *
	 * @return Class_
	 */
	public function create( Node\Stmt\Class_ $node ): Class_ {
		$class = new Class_();
		$class->set_name( $node->name->toString() );
		$class->set_line( $node->getStartLine() );
		$class->set_end_line( $node->getEndLine() );
		$class->set_final( $node->isFinal() );
		$class->set_abstract( $node->isAbstract() );
		$class->set_namespace( $this->get_namespace( $node ) );

		// Parent class
		if ( $node->extends ) {
			$class->set_extends( $this->printer->print_name( $node->extends ) );
		}

		// Interfaces
		$implements = [];
		foreach ( $node->implements as $interface ) {
			$implements[] = $this->printer->print_name( $interface );
		}
		$class->set_implements( $implements );

		// Docblock
		$doc_comment = $node->getDocComment();
		if ( $doc_comment ) {
			$docblock = $this->docblock_factory->create( $doc_comment );
			$class->set_doc_block( $docblock );
		} else {
			$class->set_doc_block( $this->docblock_factory->create_empty() );
		}

		return $class;
	}

	private function get_namespace( Node\Stmt\Class_ $node ): string {
		if ( $node->namespacedName instanceof Node\Name ) {
			return Name::from( $node->namespacedName )->get_namespace();
		}

		return 'global';
	}

}
