<?php
/**
 * Has_Uses
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

interface Has_Uses {

	public function set_uses( ?Uses $uses ): void;

	public function get_uses(): Uses;

}
