<?php
/**
 * Scope
 *
 * @package WP_Parser
 */

namespace WP_Parser;

use WP_Parser\Reflection\Class_;
use WP_Parser\Reflection\File;
use WP_Parser\Reflection\Function_;
use WP_Parser\Reflection\Method;
use WP_Parser\Reflection\Namespace_;

/**
 * Tracks the current scope during AST traversal.
 */
class Scope {
	/**
	 * @var (File|Class_|Function_|Method|Namespace_)[]
	 */
	private array $scope_stack = [];

	/**
	 * Get the current scope.
	 *
	 * @return File|Class_|Function_|Method|Namespace_|null
	 */
	public function current(): File|Class_|Function_|Method|Namespace_|null {
		return end( $this->scope_stack ) ?: null;
	}

	/**
	 * Push a scope onto the stack.
	 *
	 * @param File|Class_|Function_|Method|Namespace_ $scope
	 *
	 * @return void
	 */
	public function push( File|Class_|Function_|Method|Namespace_ $scope ): void {
		$this->scope_stack[] = $scope;
	}

	/**
	 * Pop a scope from the stack.
	 *
	 * @return Class_|File|Function_|Method|Namespace_|null
	 */
	public function pop(): Function_|File|Class_|null|Namespace_|Method {
		return array_pop( $this->scope_stack );
	}

	/**
	 * Returns the File scope object.
	 */
	public function file(): File {
		assert( $this->scope_stack[0] instanceof File );

		return $this->scope_stack[0];
	}

	/**
	 * Returns the Namespace scope object.
	 */
	public function namespace(): Namespace_ {
		assert( $this->scope_stack[1] instanceof Namespace_ );

		return $this->scope_stack[1];
	}

	public function closest( string $class ): File|Class_|Method|Function_|Namespace_|null {
		foreach ( array_reverse( $this->scope_stack ) as $object ) {
			if ( $object instanceof $class ) {
				return $object;
			}
		}

		return null;
	}
}
