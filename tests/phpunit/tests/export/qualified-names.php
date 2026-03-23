<?php
/**
 * Tests for qualified name display in exported data.
 */

namespace WP_Parser\Tests;

class Export_Qualified_Names extends Export_UnitTestCase {

	public function test_php_parameter_types() {
		$global_ns = $this->parse_string(
			<<<'PHP'
			function func( WP_Post $post, \WP_Term $term ) {}
			PHP
		);

		$function = $this->find_entity_data_in( $global_ns, 'functions', 'func' );
		$this->assertIsArray( $function );
		$this->assertArrayPathEquals( $function, 'arguments.0.type', '\WP_Post' );
		$this->assertArrayPathEquals( $function, 'arguments.1.type', '\WP_Term' );
	}

	public function test_php_parameter_types_in_namespace() {
		$non_global_ns = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			function func( \WP_Query $q, \WP_Post $p, \My_Plugin\Options $opts ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $non_global_ns, 'functions', 'func' );
		$this->assertIsArray( $func );
		$this->assertArrayPathEquals( $func, 'arguments.0.type', '\WP_Query' );
		$this->assertArrayPathEquals( $func, 'arguments.1.type', '\WP_Post' );
		$this->assertArrayPathEquals( $func, 'arguments.2.type', '\My_Plugin\Options' );
	}

	public function test_php_parameter_types_unqualified_in_namespace() {
		$non_global_ns = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			function func( Options $opts ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $non_global_ns, 'functions', 'func' );
		$this->assertIsArray( $func );
		$this->assertArrayPathEquals( $func, 'arguments.0.type', '\My_Plugin\Options' );
	}

	public function test_php_parameter_types_use_import() {
		$non_global_ns = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			use WP_Post;
			function func( WP_Post $post ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $non_global_ns, 'functions', 'func' );
		$this->assertIsArray( $func );
		$this->assertArrayPathEquals( $func, 'arguments.0.type', '\WP_Post' );
	}

	public function test_php_parameter_types_group_use_import() {
		$non_global_ns = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			use Vendor\Package\{ClassA, ClassB, ClassC};
			function func( ClassA $a, ClassB $b, ClassC $c ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $non_global_ns, 'functions', 'func' );
		$this->assertIsArray( $func );
		$this->assertArrayPathEquals( $func, 'arguments.0.type', '\Vendor\Package\ClassA' );
		$this->assertArrayPathEquals( $func, 'arguments.1.type', '\Vendor\Package\ClassB' );
		$this->assertArrayPathEquals( $func, 'arguments.2.type', '\Vendor\Package\ClassC' );
	}

	public function test_php_param_defaults_global_ns() {
		$global_ns = $this->parse_string(
			<<<'PHP'
			function func( $active = WP_Ability::STATUS_ACTIVE, $default = \My_Plugin\Options::DEFAULT_VALUE ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $global_ns, 'functions', 'func' );
		$this->assertIsArray( $func );
		$this->assertArrayPathEquals( $func, 'arguments.0.default', 'WP_Ability::STATUS_ACTIVE' );
		$this->assertArrayPathEquals( $func, 'arguments.1.default', '\My_Plugin\Options::DEFAULT_VALUE' );
	}

	public function test_php_param_defaults_non_global_ns() {
		$non_global_ns = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			function func( $status = \WP_Post::STATUS, $default = Options::DEFAULT_VALUE ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $non_global_ns, 'functions', 'func' );
		$this->assertIsArray( $func );
		$this->assertArrayPathEquals( $func, 'arguments.0.default', '\WP_Post::STATUS' );
		$this->assertArrayPathEquals( $func, 'arguments.1.default', 'Options::DEFAULT_VALUE' );
	}

	public function test_property_defaults_global_ns() {
		$global_ns = $this->parse_string(
			<<<'PHP'
			class Foo {
				public $status  = WP_Post::PUBLISHED;
				public $default = \My_Plugin\Options::DEFAULT_VALUE;
			}
			PHP
		);

		$class   = $this->find_entity_data_in( $global_ns, 'classes', 'Foo' );
		$status  = $this->find_entity_data_in( $class, 'properties', '$status' );
		$default = $this->find_entity_data_in( $class, 'properties', '$default' );

		$this->assertArrayPathEquals( $status, 'default', '\WP_Post::PUBLISHED' );
		$this->assertArrayPathEquals( $default, 'default', '\My_Plugin\Options::DEFAULT_VALUE' );
	}

	public function test_property_defaults_non_global_ns() {
		$non_global_ns = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			class Foo {
				public $status  = \WP_Post::PUBLISHED;
				public $default = Options::DEFAULT_VALUE;
			}
			PHP
		);

		$class   = $this->find_entity_data_in( $non_global_ns, 'classes', 'Foo' );
		$status  = $this->find_entity_data_in( $class, 'properties', '$status' );
		$default = $this->find_entity_data_in( $class, 'properties', '$default' );

		$this->assertArrayPathEquals( $status, 'default', '\WP_Post::PUBLISHED' );
		$this->assertArrayPathEquals( $default, 'default', '\My_Plugin\Options::DEFAULT_VALUE' );
	}

	public function test_class_extends_and_implements_global_ns() {
		$global_ns = $this->parse_string(
			<<<'PHP'
			class Foo extends Walker implements \Iterator, \My_Plugin\Loadable {}
			PHP
		);

		$class = $this->find_entity_data_in( $global_ns, 'classes', 'Foo' );
		$this->assertIsArray( $class );
		$this->assertArrayPathEquals( $class, 'extends', '\Walker' );
		$this->assertArrayPathEquals( $class, 'implements.0', '\Iterator' );
		$this->assertArrayPathEquals( $class, 'implements.1', '\My_Plugin\Loadable' );
	}

	public function test_class_extends_and_implements_non_global_ns() {
		$non_global_ns = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			class Foo extends \WP_List_Table implements \Iterator, \My_Plugin\Loadable {}
			class Bar extends \My_Plugin\Base {}
			PHP
		);

		$foo = $this->find_entity_data_in( $non_global_ns, 'classes', 'Foo' );
		$this->assertArrayPathEquals( $foo, 'extends', '\WP_List_Table' );
		$this->assertArrayPathEquals( $foo, 'implements.0', '\Iterator' );
		$this->assertArrayPathEquals( $foo, 'implements.1', '\My_Plugin\Loadable' );

		$bar = $this->find_entity_data_in( $non_global_ns, 'classes', 'Bar' );
		$this->assertArrayPathEquals( $bar, 'extends', '\My_Plugin\Base' );
	}

	public function test_hook_arguments_global_ns() {
		$global_ns = $this->parse_string(
			<<<'PHP'
			do_action( 'my_hook', WP_Term::TYPE, \WP_Error::CODE, \My_Plugin\Options::TRIGGER );
			PHP
		);

		$hook = $this->find_entity_data_in( $global_ns, 'hooks', 'my_hook' );
		$this->assertIsArray( $hook );
		$this->assertArrayPathEquals( $hook, 'arguments.0', 'WP_Term::TYPE' );
		$this->assertArrayPathEquals( $hook, 'arguments.1', '\WP_Error::CODE' );
		$this->assertArrayPathEquals( $hook, 'arguments.2', '\My_Plugin\Options::TRIGGER' );
	}

	public function test_hook_arguments_non_global_ns() {
		$non_global_ns = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			do_action( 'my_hook', \WP_Post::STATUS, Options::TRIGGER );
			PHP
		);

		$hook = $this->find_entity_data_in( $non_global_ns, 'hooks', 'my_hook' );
		$this->assertIsArray( $hook );
		$this->assertArrayPathEquals( $hook, 'arguments.0', '\WP_Post::STATUS' );
		$this->assertArrayPathEquals( $hook, 'arguments.1', 'Options::TRIGGER' );
	}

	public function test_uses_method_class_global_ns() {
		$global_ns = $this->parse_string(
			<<<'PHP'
			WP_Abilities_Registry::get();
			\My_Plugin\Options::load();
			PHP
		);

		$this->assertArrayPathEquals( $global_ns, 'uses.methods.0.class', '\WP_Abilities_Registry' );
		$this->assertArrayPathEquals( $global_ns, 'uses.methods.1.class', '\My_Plugin\Options' );
	}

	public function test_uses_method_class_non_global_ns() {
		$non_global_ns = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			\WP_Query::get_instance();
			Options::get_instance();
			PHP
		);

		$this->assertArrayPathEquals( $non_global_ns, 'uses.methods.0.class', '\WP_Query' );
		$this->assertArrayPathEquals( $non_global_ns, 'uses.methods.1.class', '\My_Plugin\Options' );
	}

	public function test_uses_function_name_global_ns() {
		$global_ns = $this->parse_string(
			<<<'PHP'
			wp_query_posts();
			My_Plugin\init();
			PHP
		);

		$this->assertArrayPathEquals( $global_ns, 'uses.functions.0.name', 'wp_query_posts' );
		$this->assertArrayPathEquals( $global_ns, 'uses.functions.1.name', 'My_Plugin\init' );
	}

	public function test_uses_function_name_non_global_ns() {
		$non_global_ns = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			wp_query_posts();
			\My_Plugin\init();
			PHP
		);

		$this->assertArrayPathEquals( $non_global_ns, 'uses.functions.0.name', 'wp_query_posts' );
		$this->assertArrayPathEquals( $non_global_ns, 'uses.functions.1.name', 'My_Plugin\init' );
	}

	public function test_phpdoc_param_types_global_ns() {
		$global_ns = $this->parse_string(
			<<<'PHP'
			/**
			 * @param WP_Term            $term
			 * @param WP_Query           $q
			 * @param \My_Plugin\Options $opts
			 */
			function func( $term, $q, $opts ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $global_ns, 'functions', 'func' );
		$this->assertArrayPathEquals( $func, 'doc.tags.0.types', [ '\WP_Term' ] );
		$this->assertArrayPathEquals( $func, 'doc.tags.1.types', [ '\WP_Query' ] );
		$this->assertArrayPathEquals( $func, 'doc.tags.2.types', [ '\My_Plugin\Options' ] );
	}

	public function test_phpdoc_param_types_non_global_ns() {
		$non_global_ns = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			/**
			 * @param \WP_Query          $q
			 * @param \WP_Post           $p
			 * @param \My_Plugin\Options $opts
			 */
			function func( $q, $p, $opts ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $non_global_ns, 'functions', 'func' );
		$this->assertArrayPathEquals( $func, 'doc.tags.0.types', [ '\WP_Query' ] );
		$this->assertArrayPathEquals( $func, 'doc.tags.1.types', [ '\WP_Post' ] );
		$this->assertArrayPathEquals( $func, 'doc.tags.2.types', [ '\My_Plugin\Options' ] );
	}

	public function test_phpdoc_return_types() {
		$data = $this->parse_string(
			<<<'PHP'
			/** @return WP_Error */
			function func1() {}
			/** @return WP_Ability */
			function func2() {}
			/** @return \WP_Error */
			function func3() {}
			/** @return \WP_Post[] */
			function func4() {}
			/** @return \My_Plugin\Options */
			function func5() {}
			PHP
		);

		$func1 = $this->find_entity_data_in( $data, 'functions', 'func1' );
		$func2 = $this->find_entity_data_in( $data, 'functions', 'func2' );
		$func3 = $this->find_entity_data_in( $data, 'functions', 'func3' );
		$func4 = $this->find_entity_data_in( $data, 'functions', 'func4' );
		$func5 = $this->find_entity_data_in( $data, 'functions', 'func5' );

		$this->assertArrayPathEquals( $func1, 'doc.tags.0.types', [ '\WP_Error' ] );
		$this->assertArrayPathEquals( $func2, 'doc.tags.0.types', [ '\WP_Ability' ] );
		$this->assertArrayPathEquals( $func3, 'doc.tags.0.types', [ '\WP_Error' ] );
		$this->assertArrayPathEquals( $func4, 'doc.tags.0.types', [ '\WP_Post[]' ] );
		$this->assertArrayPathEquals( $func5, 'doc.tags.0.types', [ '\My_Plugin\Options' ] );
	}

	public function test_phpdoc_throws_types_global_ns() {
		$global_ns = $this->parse_string(
			<<<'PHP'
			/**
			 * @throws LogicException
			 * @throws \My_Plugin\ParseException
			 */
			function func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $global_ns, 'functions', 'func' );
		$this->assertArrayPathEquals( $func, 'doc.tags.0.types', [ '\LogicException' ] );
		$this->assertArrayPathEquals( $func, 'doc.tags.1.types', [ '\My_Plugin\ParseException' ] );
	}

	public function test_phpdoc_throws_types_non_global_ns() {
		$non_global_ns = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			/**
			 * @throws \WP_HTML_Unsupported_Exception
			 * @throws \InvalidArgumentException
			 * @throws \My_Plugin\ParseException
			 */
			function func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $non_global_ns, 'functions', 'func' );
		$this->assertArrayPathEquals( $func, 'doc.tags.0.types', [ '\WP_HTML_Unsupported_Exception' ] );
		$this->assertArrayPathEquals( $func, 'doc.tags.1.types', [ '\InvalidArgumentException' ] );
		$this->assertArrayPathEquals( $func, 'doc.tags.2.types', [ '\My_Plugin\ParseException' ] );
	}

	public function test_phpdoc_var_types() {
		$data = $this->parse_string(
			<<<'PHP'
			class Foo {
				/** @var WP_Comment */
				public $comment;
				/** @var \My_Plugin\Options */
				public $opts;
			}
			PHP
		);

		$class   = $this->find_entity_data_in( $data, 'classes', 'Foo' );
		$comment = $this->find_entity_data_in( $class, 'properties', '$comment' );
		$opts    = $this->find_entity_data_in( $class, 'properties', '$opts' );

		$this->assertArrayPathEquals( $comment, 'doc.tags.0.types', [ '\WP_Comment' ] );
		$this->assertArrayPathEquals( $opts, 'doc.tags.0.types', [ '\My_Plugin\Options' ] );
	}

	public function test_phpdoc_global_types() {
		$this->markTestSkipped( 'Todo' );
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * @global WP_Locale $locale
			 * @global \My_Plugin\Options $opts
			 */
			function func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'func' );
		$this->assertArrayPathEquals( $func, 'doc.tags.0.content', '\WP_Locale $locale' );
		$this->assertArrayPathEquals( $func, 'doc.tags.1.content', '\My_Plugin\Options $opts' );
	}

	public function test_phpdoc_property_types() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * @property      WP_Term           $term
			 * @property-read \My_Plugin\Options $opts
			 */
			class Foo {}
			PHP
		);

		$class = $this->find_entity_data_in( $data, 'classes', 'Foo' );
		$this->assertIsArray( $class );
		$this->assertArrayPathEquals( $class, 'doc.tags.0.types', [ '\WP_Term' ] );
		$this->assertArrayPathEquals( $class, 'doc.tags.1.types', [ '\My_Plugin\Options' ] );
	}

	public function test_phpdoc_see_references_global_ns() {
		$global_ns = $this->parse_string(
			<<<'PHP'
			/**
			 * @see \WP_Term
			 * @see \WP_Abilities_Registry
			 * @see \WP_Abilities_Registry::get_instance()
			 * @see \My_Plugin\Options
			 * @see wp_nav_menu()
			 */
			function func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $global_ns, 'functions', 'func' );
		$this->assertArrayPathEquals( $func, 'doc.tags.0.refers', '\WP_Term' );
		$this->assertArrayPathEquals( $func, 'doc.tags.1.refers', '\WP_Abilities_Registry' );
		$this->assertArrayPathEquals( $func, 'doc.tags.2.refers', '\WP_Abilities_Registry::get_instance()' );
		$this->assertArrayPathEquals( $func, 'doc.tags.3.refers', '\My_Plugin\Options' );
		$this->assertArrayPathEquals( $func, 'doc.tags.4.refers', 'wp_nav_menu()' );
	}

	public function test_phpdoc_see_references_non_global_ns() {
		$non_global_ns = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			/**
			 * @see \WP_Query
			 * @see \WP_Query::get_posts()
			 * @see Options::load()
			 * @see \My_Plugin\Options::validate()
			 * @see wp_nav_menu()
			 */
			function func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $non_global_ns, 'functions', 'func' );
		$this->assertArrayPathEquals( $func, 'doc.tags.0.refers', '\WP_Query' );
		$this->assertArrayPathEquals( $func, 'doc.tags.1.refers', '\WP_Query::get_posts()' );
		$this->assertArrayPathEquals( $func, 'doc.tags.2.refers', 'Options::load()' );
		$this->assertArrayPathEquals( $func, 'doc.tags.3.refers', '\My_Plugin\Options::validate()' );
		$this->assertArrayPathEquals( $func, 'doc.tags.4.refers', 'wp_nav_menu()' );
	}

	public function test_phpdoc_see_references_chained_method() {
		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * @see get_current_screen()->add_help_tab()
			 * @see get_current_screen()->remove_help_tab()
			 */
			function func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'func' );
		$this->assertArrayPathEquals( $func, 'doc.tags.0.refers', 'get_current_screen()->add_help_tab()' );
		$this->assertArrayPathEquals( $func, 'doc.tags.1.refers', 'get_current_screen()->remove_help_tab()' );
	}

	public function test_phpdoc_see_references_self() {
		$data = $this->parse_string(
			<<<'PHP'
			class Foo {
				/**
				 * @see self::$var
				 * @see self::CONST
				 * @see self::method()
				 */
				public function bar() {}
			}
			PHP
		);

		$class  = $this->find_entity_data_in( $data, 'classes', 'Foo' );
		$method = $this->find_entity_data_in( $class, 'methods', 'bar' );
		$this->assertArrayPathEquals( $method, 'doc.tags.0.refers', 'self::$var' );
		$this->assertArrayPathEquals( $method, 'doc.tags.1.refers', 'self::CONST' );
		$this->assertArrayPathEquals( $method, 'doc.tags.2.refers', 'self::method()' );
	}

	public function test_hook_doc_param_types_global_ns() {
		$global_ns = $this->parse_string(
			<<<'PHP'
			/**
			 * Fires something.
			 *
			 * @param WP_Term            $term
			 * @param WP_Error           $err
			 * @param \My_Plugin\Options $opts
			 */
			do_action( 'my_action', $term, $err, $opts );
			PHP
		);

		$hook = $this->find_entity_data_in( $global_ns, 'hooks', 'my_action' );
		$this->assertIsArray( $hook );
		$this->assertArrayPathEquals( $hook, 'doc.tags.0.types', [ '\WP_Term' ] );
		$this->assertArrayPathEquals( $hook, 'doc.tags.1.types', [ '\WP_Error' ] );
		$this->assertArrayPathEquals( $hook, 'doc.tags.2.types', [ '\My_Plugin\Options' ] );
	}

	public function test_hook_doc_param_types_non_global_ns() {
		$non_global_ns = $this->parse_string(
			<<<'PHP'
			namespace My_Plugin;
			/**
			 * Fires something.
			 *
			 * @param \WP_Post           $post
			 * @param \WP_Query          $q
			 * @param \My_Plugin\Options $opts
			 */
			do_action( 'my_action_ns', $post, $q, $opts );
			PHP
		);

		$hook = $this->find_entity_data_in( $non_global_ns, 'hooks', 'my_action_ns' );
		$this->assertIsArray( $hook );
		$this->assertArrayPathEquals( $hook, 'doc.tags.0.types', [ '\WP_Post' ] );
		$this->assertArrayPathEquals( $hook, 'doc.tags.1.types', [ '\WP_Query' ] );
		$this->assertArrayPathEquals( $hook, 'doc.tags.2.types', [ '\My_Plugin\Options' ] );
	}
}
