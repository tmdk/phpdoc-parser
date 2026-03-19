<?php

namespace WP_Parser\Tests;

use PhpParser\Node\Name;
use PHPUnit\Framework\TestCase;
use WP_Parser\Reference\Chained_Method_Reference;
use WP_Parser\Reference\Class_Const_Reference;
use WP_Parser\Reference\Const_Reference;
use WP_Parser\Reference\Function_Reference;
use WP_Parser\Reference\Method_Reference;
use WP_Parser\Reference\Property_Reference;
use WP_Parser\Reference\Quoted_Reference;
use WP_Parser\Reference\Raw_Reference;
use WP_Parser\Reference\Reference_Parser;
use WP_Parser\Reference\Url_Reference;

class Reference_Parser_Test extends TestCase {

	private Reference_Parser $parser;

	protected function setUp(): void {
		$this->parser = new Reference_Parser();
	}

	// --- URLs ---

	public function test_url_no_description() {
		$ref = $this->parser->parse( 'https://example.com/path' );
		$this->assertInstanceOf( Url_Reference::class, $ref );
		$this->assertSame( 'https://example.com/path', $ref->url );
		$this->assertNull( $ref->description );
	}

	public function test_url_with_fragment() {
		$ref = $this->parser->parse( 'https://example.com/page#section' );
		$this->assertInstanceOf( Url_Reference::class, $ref );
		$this->assertSame( 'https://example.com/page#section', $ref->url );
	}

	public function test_url_with_description() {
		$ref = $this->parser->parse( 'https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project' );
		$this->assertInstanceOf( Url_Reference::class, $ref );
		$this->assertSame( 'https://github.com/PHPMailer/PHPMailer/', $ref->url );
		$this->assertSame( 'The PHPMailer GitHub project', $ref->description );
	}

	public function test_url_non_https_scheme() {
		$ref = $this->parser->parse( 'http://example.com' );
		$this->assertInstanceOf( Url_Reference::class, $ref );
		$this->assertSame( 'http://example.com', $ref->url );
	}

	// --- Quoted strings ---

	public function test_single_quoted_string() {
		$ref = $this->parser->parse( "'wp_link_query_args' filter" );
		$this->assertInstanceOf( Quoted_Reference::class, $ref );
		$this->assertSame( "'wp_link_query_args'", $ref->value );
	}

	public function test_double_quoted_string() {
		$ref = $this->parser->parse( '"some_hook" description ignored' );
		$this->assertInstanceOf( Quoted_Reference::class, $ref );
		$this->assertSame( '"some_hook"', $ref->value );
	}

	public function test_backtick_string() {
		$ref = $this->parser->parse( '`shell_command`' );
		$this->assertInstanceOf( Quoted_Reference::class, $ref );
		$this->assertSame( '`shell_command`', $ref->value );
	}

	// --- Function references ---

	public function test_function_simple() {
		$ref = $this->parser->parse( 'foo()' );
		$this->assertInstanceOf( Function_Reference::class, $ref );
		$this->assertInstanceOf( Name::class, $ref->name );
		$this->assertSame( 'foo', $ref->name->name );
		$this->assertNull( $ref->description );
	}

	public function test_function_with_underscores() {
		$ref = $this->parser->parse( '_add_default_theme_supports()' );
		$this->assertInstanceOf( Function_Reference::class, $ref );
		$this->assertSame( '_add_default_theme_supports', $ref->name->name );
	}

	public function test_function_fully_qualified() {
		$ref = $this->parser->parse( '\json_last_error()' );
		$this->assertInstanceOf( Function_Reference::class, $ref );
		$this->assertInstanceOf( Name\FullyQualified::class, $ref->name );
		$this->assertSame( 'json_last_error', $ref->name->name );
	}

	public function test_function_namespaced_fully_qualified() {
		$ref = $this->parser->parse( '\SimplePie\Misc::https_url()' );
		// This is a method reference, not a function
		$this->assertInstanceOf( Method_Reference::class, $ref );
		$this->assertInstanceOf( Name\FullyQualified::class, $ref->class );
		$this->assertSame( 'SimplePie\Misc', $ref->class->name );
		$this->assertSame( 'https_url', $ref->method->name );
	}

	public function test_function_relative_namespace() {
		$ref = $this->parser->parse( 'namespace\foo()' );
		$this->assertInstanceOf( Function_Reference::class, $ref );
		$this->assertInstanceOf( Name\Relative::class, $ref->name );
		$this->assertSame( 'foo', $ref->name->name );
	}

	public function test_function_with_description() {
		$ref = $this->parser->parse( '_http_build_query() Used to build the query' );
		$this->assertInstanceOf( Function_Reference::class, $ref );
		$this->assertSame( '_http_build_query', $ref->name->name );
		$this->assertSame( 'Used to build the query', $ref->description );
	}

	// --- Chained method references ---

	public function test_chained_method_simple() {
		$ref = $this->parser->parse( 'get_current_screen()->add_help_tab()' );
		$this->assertInstanceOf( Chained_Method_Reference::class, $ref );
		$this->assertSame( 'get_current_screen', $ref->function->name );
		$this->assertSame( 'add_help_tab', $ref->method->name );
		$this->assertNull( $ref->description );
	}

	public function test_chained_method_with_description() {
		$ref = $this->parser->parse( 'get_current_screen()->add_help_tab() Add a tab' );
		$this->assertInstanceOf( Chained_Method_Reference::class, $ref );
		$this->assertSame( 'get_current_screen', $ref->function->name );
		$this->assertSame( 'add_help_tab', $ref->method->name );
		$this->assertSame( 'Add a tab', $ref->description );
	}

	public function test_chained_method_fully_qualified() {
		$ref = $this->parser->parse( '\get_current_screen()->add_help_tab()' );
		$this->assertInstanceOf( Chained_Method_Reference::class, $ref );
		$this->assertInstanceOf( Name\FullyQualified::class, $ref->function );
		$this->assertSame( 'get_current_screen', $ref->function->name );
		$this->assertSame( 'add_help_tab', $ref->method->name );
	}

	// --- Const/class references (bare names) ---

	public function test_const_simple() {
		$ref = $this->parser->parse( 'WP_ENVIRONMENT_TYPE' );
		$this->assertInstanceOf( Const_Reference::class, $ref );
		$this->assertSame( 'WP_ENVIRONMENT_TYPE', $ref->name->name );
		$this->assertNull( $ref->description );
	}

	public function test_class_name() {
		$ref = $this->parser->parse( 'WP_REST_Controller' );
		$this->assertInstanceOf( Const_Reference::class, $ref );
		$this->assertSame( 'WP_REST_Controller', $ref->name->name );
	}

	public function test_const_fully_qualified() {
		$ref = $this->parser->parse( '\utf8_decode()' );
		// Has parens → Function_Reference, not Const
		$this->assertInstanceOf( Function_Reference::class, $ref );
		$this->assertInstanceOf( Name\FullyQualified::class, $ref->name );
	}

	public function test_class_with_description() {
		$ref = $this->parser->parse( 'OAuth' );
		$this->assertInstanceOf( Const_Reference::class, $ref );
		$this->assertSame( 'OAuth', $ref->name->name );
	}

	// --- Class const references ---

	public function test_class_const_simple() {
		$ref = $this->parser->parse( 'Foo::BAR' );
		$this->assertInstanceOf( Class_Const_Reference::class, $ref );
		$this->assertSame( 'Foo', $ref->class->name );
		$this->assertSame( 'BAR', $ref->const->name );
		$this->assertNull( $ref->description );
	}

	public function test_class_const_fully_qualified() {
		$ref = $this->parser->parse( '\Foo::BAR' );
		$this->assertInstanceOf( Class_Const_Reference::class, $ref );
		$this->assertInstanceOf( Name\FullyQualified::class, $ref->class );
		$this->assertSame( 'Foo', $ref->class->name );
		$this->assertSame( 'BAR', $ref->const->name );
	}

	public function test_class_const_namespaced() {
		$ref = $this->parser->parse( '\WpOrg\Requests\Hooks' );
		// No ::, so it's a bare const/class reference
		$this->assertInstanceOf( Const_Reference::class, $ref );
		$this->assertInstanceOf( Name\FullyQualified::class, $ref->name );
		$this->assertSame( 'WpOrg\Requests\Hooks', $ref->name->name );
	}

	public function test_class_const_self() {
		$ref = $this->parser->parse( 'self::SOME_CONST' );
		$this->assertInstanceOf( Class_Const_Reference::class, $ref );
		$this->assertSame( 'self', $ref->class->name );
		$this->assertSame( 'SOME_CONST', $ref->const->name );
	}

	public function test_method_self() {
		$ref = $this->parser->parse( 'self::method()' );
		$this->assertInstanceOf( Method_Reference::class, $ref );
		$this->assertSame( 'self', $ref->class->name );
		$this->assertSame( 'method', $ref->method->name );
	}

	public function test_property_self() {
		$ref = $this->parser->parse( 'self::$var' );
		$this->assertInstanceOf( Property_Reference::class, $ref );
		$this->assertSame( 'self', $ref->class->name );
		$this->assertSame( 'var', $ref->property->name );
	}

	public function test_class_const_with_description() {
		$ref = $this->parser->parse( 'Foo::BAR Some description' );
		$this->assertInstanceOf( Class_Const_Reference::class, $ref );
		$this->assertSame( 'Some description', $ref->description );
	}

	// Without $ sigil, non-uppercase member names are still Class_Const_Reference (option C)
	public function test_member_without_sigil_is_class_const() {
		$ref = $this->parser->parse( 'wpdb::field_types' );
		$this->assertInstanceOf( Class_Const_Reference::class, $ref );
		$this->assertSame( 'wpdb', $ref->class->name );
		$this->assertSame( 'field_types', $ref->const->name );
	}

	// --- Method references ---

	public function test_method_simple() {
		$ref = $this->parser->parse( 'SMTP::authenticate()' );
		$this->assertInstanceOf( Method_Reference::class, $ref );
		$this->assertSame( 'SMTP', $ref->class->name );
		$this->assertSame( 'authenticate', $ref->method->name );
		$this->assertNull( $ref->description );
	}

	public function test_method_fully_qualified() {
		$ref = $this->parser->parse( '\WP_Widget_Media::render_control_template_scripts()' );
		$this->assertInstanceOf( Method_Reference::class, $ref );
		$this->assertInstanceOf( Name\FullyQualified::class, $ref->class );
		$this->assertSame( 'WP_Widget_Media', $ref->class->name );
		$this->assertSame( 'render_control_template_scripts', $ref->method->name );
	}

	public function test_method_with_description() {
		$ref = $this->parser->parse( '\WP_Widget_Media::render_control_template_scripts() Where the JS template is located.' );
		$this->assertInstanceOf( Method_Reference::class, $ref );
		$this->assertSame( 'Where the JS template is located.', $ref->description );
	}

	public function test_method_multi_segment_class() {
		$ref = $this->parser->parse( '\WpOrg\Requests\Auth\Basic::curl_before_send()' );
		$this->assertInstanceOf( Method_Reference::class, $ref );
		$this->assertInstanceOf( Name\FullyQualified::class, $ref->class );
		$this->assertSame( 'WpOrg\Requests\Auth\Basic', $ref->class->name );
		$this->assertSame( 'curl_before_send', $ref->method->name );
	}

	// --- Property references ---

	public function test_property_with_sigil() {
		$ref = $this->parser->parse( 'wpdb::$field_types' );
		$this->assertInstanceOf( Property_Reference::class, $ref );
		$this->assertSame( 'wpdb', $ref->class->name );
		$this->assertSame( 'field_types', $ref->property->name );
	}

	public function test_property_fully_qualified() {
		$ref = $this->parser->parse( '\Foo::$prop' );
		$this->assertInstanceOf( Property_Reference::class, $ref );
		$this->assertInstanceOf( Name\FullyQualified::class, $ref->class );
		$this->assertSame( 'prop', $ref->property->name );
	}

	// --- @see prefix stripping ---

	public function test_strips_see_prefix() {
		$ref = $this->parser->parse( '@see foo()' );
		$this->assertInstanceOf( Function_Reference::class, $ref );
		$this->assertSame( 'foo', $ref->name->name );
	}

	public function test_strips_see_prefix_with_class_ref() {
		$ref = $this->parser->parse( '@see wp_should_load_block_assets_on_demand()' );
		$this->assertInstanceOf( Function_Reference::class, $ref );
		$this->assertSame( 'wp_should_load_block_assets_on_demand', $ref->name->name );
	}

	// --- Raw fallback ---

	public function test_raw_empty() {
		$ref = $this->parser->parse( '' );
		$this->assertInstanceOf( Raw_Reference::class, $ref );
		$this->assertSame( '', $ref->value );
	}

	public function test_raw_see_only() {
		$ref = $this->parser->parse( '@see' );
		$this->assertInstanceOf( Raw_Reference::class, $ref );
	}

	public function test_raw_unrecognized() {
		$ref = $this->parser->parse( '::incomplete' );
		$this->assertInstanceOf( Raw_Reference::class, $ref );
	}
}
