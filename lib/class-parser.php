<?php
/**
 * Parser
 *
 * @package WP_Parser
 */

namespace WP_Parser;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use WP_Parser\Factory\Class_Factory;
use WP_Parser\Factory\Constant_Factory;
use WP_Parser\Factory\Docblock_Factory;
use WP_Parser\Factory\Docblock_Tag_Factory;
use WP_Parser\Factory\File_Factory;
use WP_Parser\Factory\Function_Call_Factory;
use WP_Parser\Factory\Function_Factory;
use WP_Parser\Factory\Hook_Factory;
use WP_Parser\Factory\Include_Factory;
use WP_Parser\Factory\Method_Call_Factory;
use WP_Parser\Factory\Method_Factory;
use WP_Parser\Factory\Param_Factory;
use WP_Parser\Factory\Property_Factory;
use WP_Parser\Reflection\File;
use WP_Parser\Visitors\Docblock_Codeblock_Visitor;
use WP_Parser\Visitors\Class_Visitor;
use WP_Parser\Visitors\Comment_Stripping_Visitor;
use WP_Parser\Visitors\Constant_Visitor;
use WP_Parser\Visitors\Function_Visitor;
use WP_Parser\Visitors\Hook_Visitor;
use WP_Parser\Visitors\Include_Visitor;
use WP_Parser\Visitors\Interface_Visitor;
use WP_Parser\Visitors\Method_Visitor;
use WP_Parser\Visitors\Name_Context_Visitor;
use WP_Parser\Visitors\Namespace_Visitor;
use WP_Parser\Visitors\Uses_Visitor;

/**
 * Main parser class.
 */
class Parser {

	private string $root_dir;
	private array $files = [];

	private Docblock_Factory $docblock_factory;
	private File_Factory $file_factory;
	private Class_Factory $class_factory;
	private Function_Factory $function_factory;
	private Method_Factory $method_factory;
	private Property_Factory $property_factory;
	private Include_Factory $include_factory;
	private Method_Call_Factory $method_call_factory;
	private Function_Call_Factory $function_call_factory;
	private Constant_Factory $constant_factory;
	private Hook_Factory $hook_factory;
	private \PhpParser\Parser $parser;

	public function __construct( string $root_dir ) {
		$this->parser = ( new ParserFactory() )->createForNewestSupportedVersion();

		$this->root_dir = $root_dir;

		// Initialize factories
		$this->docblock_factory = new Docblock_Factory( new Docblock_Tag_Factory() );
		$param_factory          = new Param_Factory();

		$this->file_factory          = new File_Factory( $this->docblock_factory );
		$this->class_factory         = new Class_Factory( $this->docblock_factory );
		$this->function_factory      = new Function_Factory( $this->docblock_factory, $param_factory );
		$this->method_factory        = new Method_Factory( $this->docblock_factory, $param_factory );
		$this->property_factory      = new Property_Factory( $this->docblock_factory );
		$this->include_factory       = new Include_Factory();
		$this->method_call_factory   = new Method_Call_Factory();
		$this->function_call_factory = new Function_Call_Factory();
		$this->constant_factory      = new Constant_Factory();
		$this->hook_factory          = new Hook_Factory();
	}

	/**
	 * Add a file to parse.
	 *
	 * @param string $filename The filename relative to root_dir.
	 *
	 * @return void
	 */
	public function add_file( string $filename ): void {
		$this->files[] = $filename;
	}

	/**
	 * Parse all added files.
	 *
	 * @return File[]
	 */
	public function parse(): array {
		$files  = [];

		foreach ( $this->files as $filename ) {
			$files[] = $this->parse_file( $filename );
		}

		return $files;
	}

	public function parse_file( string $filename ): ?File {
		$path  = rtrim( $this->root_dir, '/' ) . '/' . ltrim( $filename, '/' );
		$code  = file_get_contents( $path );
		$nodes = $this->parser->parse( $code );

		if ( $nodes === null ) {
			return null;
		}

		$file = $this->file_factory->create( $nodes, $filename, '/' . basename( $this->root_dir ) );

		$scope = new Scope();
		$scope->push( $file );

		$this->docblock_factory->set_scope( $scope );

		$name_resolver        = new NameResolver();
		$comment_stripper     = new Comment_Stripping_Visitor();
		$codeblock_visitor    = new Docblock_Codeblock_Visitor();
		$name_context_visitor = new Name_Context_Visitor();

		$namespace_visitor = new Namespace_Visitor( $scope );
		$class_visitor     = new Class_Visitor( $scope, $this->class_factory, $this->property_factory );
		$interface_visitor = new Interface_Visitor();
		$function_visitor  = new Function_Visitor( $scope, $this->function_factory );
		$method_visitor    = new Method_Visitor( $scope, $this->method_factory );
		$uses_visitor      = new Uses_Visitor( $scope, $this->function_call_factory, $this->method_call_factory );
		$include_visitor   = new Include_Visitor( $scope, $this->include_factory );
		$constant_visitor  = new Constant_Visitor( $scope, $this->constant_factory );
		$hook_visitor      = new Hook_Visitor( $scope, $this->hook_factory, $this->docblock_factory );

		// Stage 1: name resolution and cleanup.
		$traverser = new NodeTraverser();
		$traverser->addVisitor( $name_resolver );
		$traverser->addVisitor( $comment_stripper );
		$traverser->addVisitor( $codeblock_visitor );
		$traverser->addVisitor( $name_context_visitor );
		$traverser->traverse( $nodes );

		// Stage 2: collect reflection objects.
		$traverser = new NodeTraverser();
		$traverser->addVisitor( $namespace_visitor );
		$traverser->addVisitor( $class_visitor );
		$traverser->addVisitor( $interface_visitor );
		$traverser->addVisitor( $function_visitor );
		$traverser->addVisitor( $method_visitor );
		$traverser->addVisitor( $uses_visitor );
		$traverser->addVisitor( $include_visitor );
		$traverser->addVisitor( $constant_visitor );
		$traverser->addVisitor( $hook_visitor );
		$traverser->traverse( $nodes );

		return $file;
	}
}
