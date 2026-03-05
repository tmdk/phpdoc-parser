<?php
/**
 * Integration tests that verify Templated_String_Printer produces correct
 * results for the patterns found in the qualified-names export tests.
 */

namespace WP_Parser\Tests;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar;
use PHPUnit\Framework\TestCase;
use WP_Parser\Formatter\Templated_String;
use WP_Parser\Formatter\Templated_String_Printer;
use WP_Parser\Reflection\Name;

class Templated_String_Integration_Test extends TestCase {

	private Templated_String_Printer $printer;

	protected function setUp(): void {
		$this->printer = new Templated_String_Printer();
	}

	public function test_param_default_class_const_global_ns() {
		$expr = new Expr\ClassConstFetch(
			new FullyQualified( 'WP_Ability' ),
			new Identifier( 'STATUS_ACTIVE' )
		);

		$result = $this->printer->print_expr( $expr );

		$this->assertSame( 'WP_Ability::STATUS_ACTIVE', (string) $result );
	}

	public function test_param_default_namespaced_class_const() {
		$expr = new Expr\ClassConstFetch(
			new FullyQualified( [ 'My_Plugin', 'Options' ] ),
			new Identifier( 'DEFAULT_VALUE' )
		);

		$result = $this->printer->print_expr( $expr );

		$this->assertSame( 'My_Plugin\\Options::DEFAULT_VALUE', (string) $result );
	}

	public function test_property_default_class_const_global_ns() {
		$expr = new Expr\ClassConstFetch(
			new FullyQualified( 'WP_Post' ),
			new Identifier( 'PUBLISHED' )
		);

		$result = $this->printer->print_expr( $expr );

		$this->assertSame( 'WP_Post::PUBLISHED', (string) $result );
	}

	public function test_hook_argument_class_const() {
		$expr = new Expr\ClassConstFetch(
			new FullyQualified( 'WP_Term' ),
			new Identifier( 'TYPE' )
		);

		$result = $this->printer->print_expr( $expr );

		$this->assertSame( 'WP_Term::TYPE', (string) $result );
	}

	public function test_static_call_class_single_part() {
		$name   = new FullyQualified( 'WP_Query' );
		$result = $this->printer->print_name( $name );

		$this->assertSame( 'WP_Query', (string) $result );
	}

	public function test_static_call_class_namespaced() {
		$name   = new FullyQualified( [ 'My_Plugin', 'Options' ] );
		$result = $this->printer->print_name( $name );

		$this->assertSame( 'My_Plugin\\Options', (string) $result );
	}

	public function test_printer_resets_between_calls() {
		$expr1 = new Expr\ClassConstFetch(
			new FullyQualified( 'WP_Post' ),
			new Identifier( 'A' )
		);
		$expr2 = new Expr\ClassConstFetch(
			new FullyQualified( [ 'My_Plugin', 'Options' ] ),
			new Identifier( 'B' )
		);

		$result1 = $this->printer->print_expr( $expr1 );
		$result2 = $this->printer->print_expr( $expr2 );

		$placeholder1 = Templated_String::placeholder( Name::from( $expr1->class ) );
		$placeholder2 = Templated_String::placeholder( Name::from( $expr2->class ) );
		// Each should have exactly one name, not accumulated
		$this->assertSame( $placeholder1 . '::A', $result1->get_template() );
		$this->assertSame( $placeholder2 . '::B', $result2->get_template() );
	}

	public function test_string_literal_no_names() {
		$expr   = new Scalar\String_( 'hello' );
		$result = $this->printer->print_expr( $expr );

		$this->assertFalse( $result->has_names() );
	}

	public function test_custom_resolver() {
		$expr = new Expr\ClassConstFetch(
			new FullyQualified( 'WP_Post' ),
			new Identifier( 'STATUS' )
		);

		$result = $this->printer->print_expr( $expr );

		// A hypothetical resolver that always adds backslash
		$resolved = $result->resolve( fn( $ref ) => '\\' . $ref->name );
		$this->assertSame( '\\WP_Post::STATUS', $resolved );
	}

	public function test_type_hint_name_single_part() {
		$name   = new FullyQualified( 'WP_Post' );
		$result = $this->printer->print_name( $name );

		$this->assertSame( 'WP_Post', (string) $result );
		$this->assertTrue( $result->has_names() );
	}
}
