<?php
/**
 * Object_Serializer
 *
 * @package WP_Parser\Serializer
 */

namespace WP_Parser\Serializer;

use WP_Parser\Attributes\Serialize_Null;
use WP_Parser\Attributes\Serialized_Name;

/**
 * Class Object_Serializer
 */
class Object_Serializer implements Serializer_Interface {

	public function __construct( private Serializer $serializer ) {
	}

	/**
	 * Serialize an object using reflection and attributes.
	 *
	 * @param object $value
	 *
	 * @return array
	 */
	public function serialize( mixed $value ): array {
		assert( is_object( $value ) );

		$result     = [];
		$reflection = new \ReflectionClass( $value );

		foreach ( $reflection->getProperties() as $property ) {
			$name_attr       = $this->get_attribute( $property, Serialized_Name::class );
			$allow_null_attr = $this->get_attribute( $property, Serialize_Null::class );

			if ( null === $name_attr ) {
				continue;
			}

			$prop_key   = $name_attr->name;
			$prop_value = $property->getValue( $value );

			if ( $prop_value === null ) {
				if ( $allow_null_attr ) {
					$result[ $prop_key ] = null;
				}
				continue;
			}

			$result[ $prop_key ] = $this->serializer->serialize( $prop_value );
		}

		return $result;
	}

	private function get_attribute( \ReflectionProperty $property, string $attribute_class ): ?object {
		$attributes = $property->getAttributes( $attribute_class );

		return isset( $attributes[0] ) ? $attributes[0]->newInstance() : null;
	}

	public function supports( mixed $value ): bool {
		return is_object( $value );
	}
}
