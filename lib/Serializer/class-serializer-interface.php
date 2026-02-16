<?php
/**
 * Serializer_Interface
 *
 * @package WP_Parser\Serializer
 */

namespace WP_Parser\Serializer;

interface Serializer_Interface {

	public function serialize( mixed $value ): mixed;

	public function supports( mixed $value ): bool;

}
