<?php
/**
 * Raw_Reference
 *
 * @package WP_Parser\Reference
 */

namespace WP_Parser\Reference;

/**
 * Fallback for unrecognized reference formats. Holds the original string value.
 */
class Raw_Reference extends Reference {

	public function __construct(
		public readonly string $value,
	) {}
}
