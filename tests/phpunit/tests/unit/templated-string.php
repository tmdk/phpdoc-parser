<?php

namespace WP_Parser\Tests;

use PHPUnit\Framework\TestCase;
use WP_Parser\Formatter\Templated_String;
use WP_Parser\Reflection\Name;

class Templated_String_Test extends TestCase {

	public function test_plain_string_without_names() {
		$ts = new Templated_String( 'hello world' );

		$this->assertSame( 'hello world', $ts->resolve( fn() => '' ) );
		$this->assertSame( 'hello world', (string) $ts );
		$this->assertFalse( $ts->has_names() );
	}

	public function test_single_name_reference() {
		$wp_post     = new Name( 'WP_Post' );
		$placeholder = Templated_String::placeholder( $wp_post );
		$ts          = new Templated_String(
			sprintf( '%s::STATUS', $placeholder ),
			[ $placeholder => $wp_post ]
		);

		$this->assertTrue( $ts->has_names() );
		$this->assertSame(
			'WP_Post::STATUS',
			$ts->resolve( fn( Name $name ) => $name->name )
		);
	}

	public function test_multiple_name_references() {
		$wp_post = new Name( 'WP_Post' );
		$options = new Name( 'My_Plugin\\Options' );
		$ts      = new Templated_String(
			sprintf(
				'array(%s::STATUS, %s::DEFAULT)',
				Templated_String::placeholder( $wp_post ),
				Templated_String::placeholder( $options ),
			),
			[
				Templated_String::placeholder( $wp_post ) => $wp_post,
				Templated_String::placeholder( $options ) => $options,
			]
		);

		$resolved = $ts->resolve(
			function ( Name $name ) {
				return $name->get_namespace() === 'global' ? 'Fake\\' . $name->name : $name->name;
			}
		);

		$this->assertSame( 'array(Fake\\WP_Post::STATUS, My_Plugin\\Options::DEFAULT)', $resolved );
	}

	public function test_name_reference_value_object() {
		$name = new Name( 'My_Plugin\\Options', fully_qualified: true );

		$this->assertSame( 'My_Plugin\\Options', $name->name );
	}
}
