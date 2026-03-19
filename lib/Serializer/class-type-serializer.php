<?php
/**
 * Type_Serializer
 *
 * @package WP_Parser
 */

namespace WP_Parser\Serializer;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\AggregatedType;
use WP_Parser\Formatter\Type_Pretty_Printer;

/**
 * Class Type_Serializer
 */
class Type_Serializer implements Serializer_Interface {

	public function __construct( private readonly Type_Pretty_Printer $printer = new Type_Pretty_Printer() ) {}

	public function serialize( mixed $value ): array {
		assert( $value instanceof Type );

		if ( $value instanceof AggregatedType ) {
			return array_map( fn( Type $type ) => $this->printer->print_type( $type ), iterator_to_array( $value ) );
		}

		return [ $this->printer->print_type( $value ) ];
	}

	public function supports( mixed $value ): bool {
		return $value instanceof Type;
	}
}
