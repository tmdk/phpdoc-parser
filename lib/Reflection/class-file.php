<?php
/**
 * File
 *
 * @package WP_Parser\Reflection
 */

namespace WP_Parser\Reflection;

use WP_Parser\Attributes\Serialized_Name;

/**
 * Represents a PHP file.
 */
class File implements Has_Uses, Has_Hooks {
	#[Serialized_Name( 'file' )]
	private ?DocBlock $doc_block = null;

	#[Serialized_Name( 'path' )]
	private string $path;

	#[Serialized_Name( 'root' )]
	private string $root;

	/** @var Uses|null */
	#[Serialized_Name( 'uses' )]
	private ?Uses $uses = null;

	/** @var Include_[]|null */
	#[Serialized_Name( 'includes' )]
	private ?array $includes = null;

	/** @var Include_[]|null */
	#[Serialized_Name( 'constants' )]
	private ?array $constants = null;

	/** @var Hook[]|null */
	#[Serialized_Name( 'hooks' )]
	private ?array $hooks = null;

	#[Serialized_Name( 'functions' )]
	private ?array $functions = null;

	#[Serialized_Name( 'classes' )]
	private ?array $classes = null;

	/**
	 * Set the file docblock.
	 *
	 * @param DocBlock|null $doc_block
	 *
	 * @return void
	 */
	public function set_doc_block( ?DocBlock $doc_block ): void {
		$this->doc_block = $doc_block;
	}

	/**
	 * Set the file path.
	 *
	 * @param string $path
	 *
	 * @return void
	 */
	public function set_path( string $path ): void {
		$this->path = $path;
	}

	/**
	 * Set the root directory.
	 *
	 * @param string $root
	 *
	 * @return void
	 */
	public function set_root( string $root ): void {
		$this->root = $root;
	}

	/**
	 * Add a function.
	 *
	 * @param Function_ $function
	 *
	 * @return void
	 */
	public function add_function( Function_ $function ): void {
		$this->functions[] = $function;
	}

	/**
	 * Add a class.
	 *
	 * @param Class_ $class
	 *
	 * @return void
	 */
	public function add_class( Class_ $class ): void {
		$this->classes[] = $class;
	}

	/**
	 * Add a include.
	 *
	 * @param Include_ $include
	 *
	 * @return void
	 */
	public function add_include( Include_ $include ): void {
		$this->includes[] = $include;
	}

	public function get_uses(): ?Uses {
		return $this->uses;
	}

	public function add_function_use( Function_Call $function_call ): void {
		$this->uses ??= new Uses();
		$this->uses->add_function( $function_call );
	}

	public function add_method_use( Method_Call $method_call ): void {
		$this->uses ??= new Uses();
		$this->uses->add_method( $method_call );
	}

	public function add_constant( Constant $constant ): void {
		$this->constants[] = $constant;
	}

	public function add_hook( Hook $hook ): void {
		$this->hooks[] = $hook;
	}

	public function get_doc_block(): ?DocBlock {
		return $this->doc_block;
	}

	public function get_path(): string {
		return $this->path;
	}

	public function get_root(): string {
		return $this->root;
	}

	public function get_includes(): ?array {
		return $this->includes;
	}

	public function get_constants(): ?array {
		return $this->constants;
	}

	public function get_hooks(): ?array {
		return $this->hooks;
	}

	public function get_functions(): ?array {
		return $this->functions;
	}

	public function get_classes(): ?array {
		return $this->classes;
	}
}
