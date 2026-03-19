<?php
/**
 * Fqsen_Serializer
 *
 * @package WP_Parser\Serializer
 */

namespace WP_Parser\Serializer;

use phpDocumentor\Reflection\Fqsen;
use WP_Parser\Formatter\Type_Pretty_Printer;

/**
 * Class Fqsen_Serializer
 */
class Fqsen_Serializer implements Serializer_Interface {

	public function __construct( private readonly Type_Pretty_Printer $printer = new Type_Pretty_Printer() ) {}

	public function serialize( mixed $value ): string {
		assert( $value instanceof Fqsen );

		return $this->printer->print_fqsen( $value );
	}

	public function supports( mixed $value ): bool {
		return $value instanceof Fqsen;
	}
}