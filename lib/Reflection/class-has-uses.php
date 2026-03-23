<?php
/**
 * Has_Uses
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

interface Has_Uses {

	public function get_uses(): ?Uses;

	public function add_function_use( Function_Call $function_call ): void;

	public function add_method_use( Method_Call $method_call ): void;

}
