<?php
/**
 * Method_Serializer
 *
 * @package WP_Parser\Serializer
 */

namespace WP_Parser\Serializer;

use WP_Parser\Reflection\Method;

/**
 * Class Method_Serializer
 */
class Method_Serializer implements Serializer_Interface {

	public function __construct( private Object_Serializer $object_serializer ) {
	}

	public function serialize( mixed $value ): mixed {
		$result = $this->object_serializer->serialize( $value );
		if ( $result['namespace'] === 'global' ) {
			$result['namespace'] = '';
		}

		return $result;
	}

	public function supports( mixed $value ): bool {
		return $value instanceof Method;
	}
}
