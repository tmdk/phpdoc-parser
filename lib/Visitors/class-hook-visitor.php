<?php
/**
 * Hook_Visitor
 *
 * @package WP_Parser\Visitors
 */

namespace WP_Parser\Visitors;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use WP_Parser\Factory\Docblock_Factory;
use WP_Parser\Factory\Hook_Factory;
use WP_Parser\Reflection\Has_Hooks;
use WP_Parser\Reflection\Hook;
use WP_Parser\Scope;

/**
 * Visitor for collecting hooks (actions and filters).
 */
class Hook_Visitor extends Scope_Aware_Visitor {

	private ?Doc $doc_comment = null;

	private Hook_Factory $hook_factory;
	private Docblock_Factory $docblock_factory;

	public function __construct( Scope $scope, Hook_Factory $hook_factory, Docblock_Factory $docblock_factory ) {
		parent::__construct( $scope );
		$this->hook_factory     = $hook_factory;
		$this->docblock_factory = $docblock_factory;
	}

	/**
	 * {@inheritDoc}
	 */
	public function enterNode( Node $node ) {
		// Only capture doc comments from statements that could precede a hook call,
		// not from function/method/class definitions (whose docblocks describe the
		// callable itself, not the hook inside it).
		if ( ! $node instanceof Node\Stmt\ClassMethod
			&& ! $node instanceof Node\Stmt\Function_
			&& ! $node instanceof Node\Stmt\Class_
		) {
			$doc_comment = $node->getDocComment();
			if ( $doc_comment instanceof Doc ) {
				$this->doc_comment = $doc_comment;
			}
		}

		// Check if this is a hook function call
		if ( $node instanceof FuncCall && $this->is_hook( $node ) ) {
			$this->add_hook( $node );
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function leaveNode( Node $node ) {
		if ( $node instanceof Node\Stmt ) {
			$this->doc_comment = null;
		}

		return null;
	}

	/**
	 * Check if a function call is a hook function.
	 *
	 * @param FuncCall $node
	 *
	 * @return bool
	 */
	private function is_hook( FuncCall $node ): bool {
		if ( ! $node->name instanceof Name ) {
			return false;
		}

		$function_name = $node->name->toString();

		return in_array( $function_name, Hook::FUNCTIONS, true );
	}

	/**
	 * Process a hook function call.
	 *
	 * @param FuncCall $node
	 *
	 * @return void
	 */
	private function add_hook( FuncCall $node ): void {
		if ( $this->doc_comment ) {
			$docblock = $this->docblock_factory->create( $this->doc_comment );
		} else {
			$docblock = $this->docblock_factory->create_empty();
		}

		$hook = $this->hook_factory->create( $node, $docblock );

		$this->closest_scope( Has_Hooks::class )?->add_hook( $hook );

		$this->doc_comment = null;
	}

}
