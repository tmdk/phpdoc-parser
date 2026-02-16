<?php
/**
 * Include_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use WP_Parser\Reflection\Include_;

/**
 * Class Include_Factory
 */
class Include_Factory {

	/**
	 * Create a Include_ from a php-parser function node.
	 *
	 * @param \PhpParser\Node\Expr\Include_ $node
	 *
	 * @return Include_
	 */
	public function create( \PhpParser\Node\Expr\Include_ $node ): Include_ {
		$type = match ( $node->type ) {
			\PhpParser\Node\Expr\Include_::TYPE_INCLUDE => 'Include',
			\PhpParser\Node\Expr\Include_::TYPE_INCLUDE_ONCE => 'Include Once',
			\PhpParser\Node\Expr\Include_::TYPE_REQUIRE => 'Require',
			\PhpParser\Node\Expr\Include_::TYPE_REQUIRE_ONCE => 'Require Once',
		};

		return new Include_( $node->getStartLine(), '', $type );
	}

}
