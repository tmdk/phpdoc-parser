<?php
/**
 * Url_Reference
 *
 * @package WP_Parser\Reference
 */

namespace WP_Parser\Reference;

class Url_Reference extends Reference {

	public function __construct(
		public readonly string $url,
		public readonly ?string $description = null,
	) {}
}
