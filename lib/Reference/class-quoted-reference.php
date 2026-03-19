<?php
/**
 * Quoted_Reference
 *
 * @package WP_Parser\Reference
 */

namespace WP_Parser\Reference;

/**
 * A reference that is a quoted string literal (single, double, or backtick).
 * The full quoted string including delimiters is the reference; no description splitting occurs.
 */
class Quoted_Reference extends Reference {

	public function __construct(
		public readonly string $value,
	) {}
}
