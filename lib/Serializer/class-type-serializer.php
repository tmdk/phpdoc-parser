<?php
/**
 * Type_Serializer
 *
 * @package WP_Parser
 */

namespace WP_Parser\Serializer;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\AggregatedType;

/**
 * Class Type_Serializer
 */
class Type_Serializer implements Serializer_Interface {

	public function serialize( mixed $value ): array {
		assert( $value instanceof Type );

		if ( $value instanceof AggregatedType ) {
			return array_map( fn( Type $type ) => (string) $type, iterator_to_array( $value ) );
		}

		return [ (string) $value ];
	}

	public function supports( mixed $value ): bool {
		return $value instanceof Type;
	}
}
