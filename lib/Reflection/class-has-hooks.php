<?php
/**
 * Has_Hooks
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

interface Has_Hooks {

	public function add_hook( Hook $hook ): void;

}
