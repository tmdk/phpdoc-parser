<?php
/**
 * Serializer
 *
 * @package WP_Parser
 */

namespace WP_Parser\Serializer;

/**
 * Serializes Reflection objects to arrays.
 */
class Serializer implements Serializer_Interface {

	private array $serializers = [];

	public function __construct() {
		$object_serializer = new Object_Serializer( $this );

		$this->serializers[] = new Templated_String_Serializer();
		$this->serializers[] = new Type_Serializer();
		$this->serializers[] = new Docblock_Tag_Serializer( $object_serializer );
		$this->serializers[] = new Method_Serializer( $object_serializer );
		$this->serializers[] = new Uses_Serializer( $this );
		$this->serializers[] = $object_serializer;
	}

	/**
	 * Serialize a value (object, array, or scalar).
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function serialize( mixed $value ): mixed {
		if ( is_array( $value ) ) {
			return array_map( [ $this, 'serialize' ], $value );
		}

		if ( ! is_object( $value ) ) {
			return $value;
		}

		foreach ( $this->serializers as $serializer ) {
			if ( $serializer->supports( $value ) ) {
				return $serializer->serialize( $value );
			}
		}

		throw new \RuntimeException( 'No serializer found for ' . get_debug_type( $value ) );
	}


	public function supports( mixed $value ): bool {
		return true;
	}
}
