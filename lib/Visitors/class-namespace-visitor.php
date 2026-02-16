<?php
/**
 * Namespace_Visitor
 *
 * @package WP_Parser\Visitors
 */

namespace WP_Parser\Visitors;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\UseItem;
use WP_Parser\Reflection\Namespace_;
use WP_Parser\Scope;

/**
 * Visitor for tracking namespaces and its use statements.
 */
class Namespace_Visitor extends Scope_Aware_Visitor {

	public function __construct( Scope $scope ) {
		parent::__construct( $scope );
	}

	public function beforeTraverse( array $nodes ): void {
		$this->push_scope( new Namespace_() );
	}

	public function enterNode( Node $node ) {
		if ( $node instanceof Stmt\Namespace_ ) {
			$this->enter_namespace( $node );
		} elseif ( $node instanceof Stmt\Use_ && $node->type === Stmt\Use_::TYPE_NORMAL ) {
			$this->add_use( $node );
		} elseif ( $node instanceof Stmt\GroupUse && $node->type === Stmt\Use_::TYPE_NORMAL ) {
			$this->add_group_use( $node );
		}

		return null;
	}

	/**
	 * Enter namespace.
	 *
	 * @param Stmt\Namespace_ $node
	 *
	 * @return void
	 */
	private function enter_namespace( Stmt\Namespace_ $node ): void {
		assert( $this->pop_scope() instanceof Namespace_ );
		$namespace = $node->name ? $node->name->toString() : 'global';
		$this->push_scope( new Namespace_( $namespace ) );
	}

	/**
	 * Handle use statement.
	 *
	 * @param Stmt\Use_ $node
	 *
	 * @return void
	 */
	private function add_use( Stmt\Use_ $node ): void {
		$namespace = $this->namespace_scope();

		foreach ( $node->uses as $use ) {
			$this->add_alias( $namespace, $use );
		}
	}

	/**
	 * Handle group use statement.
	 *
	 * @param Stmt\GroupUse $node
	 *
	 * @return void
	 */
	private function add_group_use( Stmt\GroupUse $node ): void {
		$namespace = $this->namespace_scope();

		foreach ( $node->uses as $use ) {
			$this->add_alias( $namespace, $use, $node->prefix );
		}
	}

	/**
	 * Add an alias/import to the file.
	 *
	 * @param Namespace_ $namespace
	 * @param UseItem    $use The use item.
	 * @param Name|null  $prefix Optional prefix for group use.
	 *
	 * @return void
	 */
	private function add_alias( Namespace_ $namespace, Node\UseItem $use, ?Name $prefix = null ): void {
		$name = $prefix ? Name::concat( $prefix, $use->name ) : $use->name;

		$namespace->add_alias( (string) $use->getAlias(), $name );
	}
}
