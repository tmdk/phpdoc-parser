<?php
/**
 * Uses
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use WP_Parser\Attributes\Serialized_Name;

/**
 * Represents function and method calls (uses) in a scope.
 */
class Uses {
	/** @var Function_Call[]|null */
	#[Serialized_Name( 'functions' )]
	private ?array $functions = null;

	/** @var Method_Call[]|null */
	#[Serialized_Name( 'methods' )]
	private ?array $methods = null;

	/**
	 * Add a function call.
	 *
	 * @param Function_Call $function_call
	 *
	 * @return void
	 */
	public function add_function( Function_Call $function_call ): void {
		$this->functions[] = $function_call;
	}

	/**
	 * Add a method call.
	 *
	 * @param Method_Call $method_call
	 *
	 * @return void
	 */
	public function add_method( Method_Call $method_call ): void {
		$this->methods[] = $method_call;
	}

	/**
	 * @return array|null
	 */
	public function get_functions(): ?array {
		return $this->functions;
	}

	/**
	 * @return array|null
	 */
	public function get_methods(): ?array {
		return $this->methods;
	}


}
