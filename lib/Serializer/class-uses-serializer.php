<?php
/**
 * Uses_Serializer
 *
 * @package WP_Parser\Serializer
 */

namespace WP_Parser\Serializer;

use WP_Parser\Reflection\Uses;

/**
 * Class Uses_Serializer
 */
class Uses_Serializer implements Serializer_Interface {

	public function __construct( private Serializer $serializer ) {
	}

	public function serialize( mixed $value ): mixed {
		assert( $value instanceof Uses );

		$functions = $this->serializer->serialize( $value->get_functions() );
		$methods   = $this->serializer->serialize( $value->get_methods() );

		// move deprecation_version of a _deprecated_* function to the first uses of this type.
		// todo: looks like a bug, check why this is done and if it is still necessary.

		if ( $functions ) {
			$deprecation_version = null;
			foreach ( $functions as &$function ) {
				if ( isset( $function['deprecation_version'] ) ) {
					$deprecation_version = $function['deprecation_version'];
					unset( $function['deprecation_version'] );
				}
			}
			if ( $deprecation_version ) {
				$functions[0]['deprecation_version'] = $deprecation_version;
			}
		}

		if ( $methods ) {
			$deprecation_version = null;
			foreach ( $methods as &$method ) {
				if ( isset( $method['deprecation_version'] ) ) {
					$deprecation_version = $method['deprecation_version'];
					unset( $method['deprecation_version'] );
				}
			}
			if ( $deprecation_version ) {
				$methods[0]['deprecation_version'] = $deprecation_version;
			}
		}

		return array_filter( compact( 'functions', 'methods' ), fn( $uses ) => ! is_null( $uses ) );
	}

	public function supports( mixed $value ): bool {
		return $value instanceof Uses;
	}
}
