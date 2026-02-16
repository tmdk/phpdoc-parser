<?php
/**
 * Scope_Aware_Visitor
 *
 * @package WP_Parser\Visitors
 */

namespace WP_Parser\Visitors;

use PhpParser\NodeVisitorAbstract;
use WP_Parser\Reflection\Class_;
use WP_Parser\Reflection\File;
use WP_Parser\Reflection\Function_;
use WP_Parser\Reflection\Method;
use WP_Parser\Reflection\Namespace_;
use WP_Parser\Scope;

/**
 * Base class for visitors that need to track scope.
 */
abstract class Scope_Aware_Visitor extends NodeVisitorAbstract {
	protected Scope $scope;

	public function __construct( Scope $scope ) {
		$this->scope = $scope;
	}

	/**
	 * Get the current scope.
	 *
	 * @return File|Class_|Function_|Method|Namespace_|null
	 */
	protected function current_scope(): File|Class_|Function_|Method|Namespace_|null {
		return $this->scope->current();
	}

	/**
	 * Get the closest ancestor scope with a matching class.
	 *
	 * @param class-string $classname
	 *
	 * @return File|Class_|Function_|Method|Namespace_|null
	 */
	protected function closest_scope( string $classname ): File|Class_|Function_|Method|Namespace_|null {
		return $this->scope->closest( $classname );
	}

	/**
	 * Push a scope onto the stack.
	 *
	 * @param File|Class_|Function_|Method|Namespace_ $scope
	 *
	 * @return void
	 */
	protected function push_scope( File|Class_|Function_|Method|Namespace_ $scope ): void {
		$this->scope->push( $scope );
	}

	/**
	 * Pop a scope from the stack.
	 *
	 * @return File|Class_|Function_|Method|Namespace_
	 */
	protected function pop_scope(): File|Class_|Function_|Method|Namespace_ {
		return $this->scope->pop();
	}

	/**
	 * Returns the topmost (global) file scope.
	 *
	 * @return File
	 */
	protected function file_scope(): File {
		return $this->scope->file();
	}

	/**
	 * Returns the namespace scope object.
	 *
	 * @return Namespace_
	 */
	protected function namespace_scope(): Namespace_ {
		return $this->scope->namespace();
	}


}
