<?php
/**
 * Class_Visitor
 *
 * @package WP_Parser\Visitors
 */

namespace WP_Parser\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use WP_Parser\Factory\Class_Factory;
use WP_Parser\Factory\Property_Factory;
use WP_Parser\Reflection\Class_;
use WP_Parser\Scope;

/**
 * Visitor for collecting classes and properties.
 */
class Class_Visitor extends Scope_Aware_Visitor {
	private Class_Factory $class_factory;
	private Property_Factory $property_factory;

	private ?Node\Stmt\Class_ $ignore = null;

	public function __construct( Scope $scope, Class_Factory $class_factory, Property_Factory $property_factory ) {
		parent::__construct( $scope );
		$this->class_factory    = $class_factory;
		$this->property_factory = $property_factory;
	}

	/**
	 * {@inheritDoc}
	 */
	public function enterNode( Node $node ): ?int {
		if ( $node instanceof Node\Stmt\Class_ ) {
			return $this->enter_class( $node );
		}

		return null;
	}

	private function is_anonymous( Node\Stmt\Class_ $node ): bool {
		return $node->name === null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function leaveNode( Node $node ) {
		if ( $node === $this->ignore ) {
			$this->ignore = null;

			return null;
		}

		if ( $node instanceof Node\Stmt\Class_ ) {
			$this->pop_scope();
		}

		if ( $node instanceof Node\Stmt\Property ) {
			return $this->leave_property( $node );
		}

		return null;
	}

	private function enter_class( Node\Stmt\Class_ $node ): ?int {
		if ( $this->is_anonymous( $node ) ) {
			$this->ignore = $node;

			return NodeVisitor::DONT_TRAVERSE_CHILDREN;
		}

		$class = $this->class_factory->create( $node );
		$file  = $this->file_scope();

		$file->add_class( $class );
		$this->push_scope( $class );

		return null;
	}

	private function leave_property( Node\Stmt\Property $node ) {
		$properties    = $this->property_factory->create( $node );
		$current_class = $this->current_scope();

		if ( $current_class instanceof Class_ ) {
			foreach ( $properties as $property ) {
				$current_class->add_property( $property );
			}
		}

		return null;
	}
}
