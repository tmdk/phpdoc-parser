<?php

namespace WP_Parser\Tests;

use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\Int_;
use PHPUnit\Framework\TestCase;
use WP_Parser\Formatter\Templated_String;
use WP_Parser\Formatter\Templated_String_Printer;
use WP_Parser\Reflection\Name;

class Templated_String_Printer_Test extends TestCase {

	private Templated_String_Printer $printer;

	protected function setUp(): void {
		$this->printer = new Templated_String_Printer();
	}

	public function test_simple_class_const_single_part_name() {
		$expr = new ClassConstFetch(
			new FullyQualified( 'WP_Post' ),
			new Identifier( 'STATUS' )
		);

		$result      = $this->printer->print_expr( $expr );
		$placeholder = Templated_String::placeholder( Name::from( $expr->class ) );

		$this->assertSame( $placeholder . '::STATUS', $result->get_template() );
		$this->assertTrue( $result->has_names() );

		$this->assertSame( 'WP_Post::STATUS', (string) $result );
	}

	public function test_class_const_namespaced_name() {
		$expr = new ClassConstFetch(
			new FullyQualified( [ 'My_Plugin', 'Options' ] ),
			new Identifier( 'DEFAULT_VALUE' )
		);

		$result      = $this->printer->print_expr( $expr );
		$placeholder = Templated_String::placeholder( Name::from( $expr->class ) );

		$this->assertSame( $placeholder . '::DEFAULT_VALUE', $result->get_template() );

		$this->assertSame( 'My_Plugin\\Options::DEFAULT_VALUE', (string) $result );
	}

	public function test_expression_without_names_returns_plain_templated_string() {
		$expr = new Int_( 42 );

		$result = $this->printer->print_expr( $expr );

		$this->assertFalse( $result->has_names() );
		$this->assertSame( '42', (string) $result );
	}

	public function test_multiple_names_in_single_expression() {
		$wp_post_status    = new ClassConstFetch(
			new FullyQualified( 'WP_Post' ),
			new Identifier( 'STATUS' )
		);
		$my_plugin_default = new ClassConstFetch(
			new FullyQualified( [ 'My_Plugin', 'Options' ] ),
			new Identifier( 'DEFAULT' )
		);

		$expr = new Array_(
			[
				new ArrayItem( $wp_post_status ),
				new ArrayItem( $my_plugin_default ),
			]
		);

		$result = $this->printer->print_expr( $expr );

		$this->assertTrue( $result->has_names() );
		$this->assertSame(
			sprintf(
				'array(%s::STATUS, %s::DEFAULT)',
				Templated_String::placeholder( Name::from( $wp_post_status->class ) ),
				Templated_String::placeholder( Name::from( $my_plugin_default->class ) )
			),
			$result->get_template()
		);
		$this->assertSame( 'array(WP_Post::STATUS, My_Plugin\\Options::DEFAULT)', (string) $result );
	}

	public function test_name_node_as_expression() {
		$name = new FullyQualified( 'Iterator' );

		$result = $this->printer->print_name( $name );

		$this->assertTrue( $result->has_names() );
		$this->assertSame( 'Iterator', (string) $result );
	}

	public function test_namespaced_name_node() {
		$name = new FullyQualified( [ 'My_Plugin', 'Loadable' ] );

		$result = $this->printer->print_name( $name );

		$this->assertSame( 'My_Plugin\\Loadable', (string) $result );
	}

	public function test_special_const_names_are_not_templated() {
		$true_expr  = new ConstFetch( new FullyQualified( 'true' ) );
		$false_expr = new ConstFetch( new FullyQualified( 'false' ) );
		$null_expr  = new ConstFetch( new FullyQualified( 'null' ) );

		$result = $this->printer->print_expr( $true_expr );
		$this->assertFalse( $result->has_names() );
		$this->assertSame( 'true', (string) $result );

		$result = $this->printer->print_expr( $false_expr );
		$this->assertFalse( $result->has_names() );
		$this->assertSame( 'false', (string) $result );

		$result = $this->printer->print_expr( $null_expr );
		$this->assertFalse( $result->has_names() );
		$this->assertSame( 'null', (string) $result );
	}
}
