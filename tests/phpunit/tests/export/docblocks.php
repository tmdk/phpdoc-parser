<?php

/**
 * A test case for exporting docblocks.
 */

namespace WP_Parser\Tests;

/**
 * Test that docblocks are exported correctly.
 */
class Export_Docblocks extends Export_UnitTestCase {

	/**
	 * Test that line breaks are removed when the description is exported.
	 */
	public function test_linebreaks_removed() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * This is a class docblock.
			 *
			 * This is the more wordy description: This is a comment with two *'s at the start,
			 * which means that it is a doc comment. Docblock comments are comment blocks used
			 * to document code. This one documents the Test_Class class.
			 *
			 * @since 3.5.2
			 */
			class Test_Class {}
			PHP
		);

		$class = $this->find_entity_data_in( $data, 'classes', 'Test_Class' );
		$this->assertIsArray( $class );

		$this->assertStringMatchesFormat(
			'%s'
			, $class['doc']['long_description']
		);
	}

	/**
	 * Test that hooks which aren't documented don't receive docs from another node.
	 */
	public function test_undocumented_hook() {

		$data = $this->parse_string(
			<<<'PHP'
			do_action( 'undocumented_hook' );
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'hooks', 'undocumented_hook' );
		$this->assertIsArray( $hook );
		$this->assertEquals( '', $hook['doc']['description'] );
	}

	/**
	 * Test that hook docbloks are picked up.
	 */
	public function test_hook_docblocks() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A test action.
			 *
			 * @since 3.7.0
			 *
			 * @param WP_Post $post Post object.
			 */
			do_action( 'test_action', $post );

			/**
			 * A filter.
			 */
			$var = apply_filters( 'test_filter', $var );

			/**
			 * A reference array action.
			 */
			do_action_ref_array( 'test_ref_array_action', array( &$var ) );

			/**
			 * A reference array filter.
			 */
			$var = apply_filters_ref_array( 'test_ref_array_filter', array( &$var ) );
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'hooks', 'test_action' );
		$this->assertIsArray( $hook );
		$this->assertEquals( 'A test action.', $hook['doc']['description'] );

		$hook = $this->find_entity_data_in( $data, 'hooks', 'test_filter' );
		$this->assertIsArray( $hook );
		$this->assertEquals( 'A filter.', $hook['doc']['description'] );

		$hook = $this->find_entity_data_in( $data, 'hooks', 'test_ref_array_action' );
		$this->assertIsArray( $hook );
		$this->assertEquals( 'A reference array action.', $hook['doc']['description'] );

		$hook = $this->find_entity_data_in( $data, 'hooks', 'test_ref_array_filter' );
		$this->assertIsArray( $hook );
		$this->assertEquals( 'A reference array filter.', $hook['doc']['description'] );
	}

	/**
	 * Test that file-level docs are exported.
	 */
	public function test_file_docblocks() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * This is the file-level docblock summary.
			 *
			 * This is the file-level docblock description, which may span multiple lines. In
			 * fact, this one does. It spans more than two full lines, continuing on to the
			 * third line.
			 *
			 * @since 1.5.0
			 */
			PHP
		);

		$this->assertEquals( 'This is the file-level docblock summary.', $data['file']['description'] );
	}

	/**
	 * Test that function docs are exported.
	 */
	public function test_function_docblocks() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * This is a function docblock.
			 *
			 * This function is just a test, but we've added this description anyway.
			 *
			 * @since 2.6.0
			 *
			 * @param string $var A string value.
			 * @param int    $num A number.
			 *
			 * @return bool Whether the function was called correctly.
			 */
			function test_func( $var, $num ) {
				return true;
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_func' );
		$this->assertIsArray( $func );
		$this->assertEquals( 'This is a function docblock.', $func['doc']['description'] );
		$this->assertEquals( '<p>This function is just a test, but we\'ve added this description anyway.</p>', $func['doc']['long_description'] );
		$this->assertEquals(
			array(
				array(
					'name' => 'since',
					'content' => '2.6.0',
				),
				array(
					'name' => 'param',
					'content' => 'A string value.',
					'types' => array( 'string' ),
					'variable' => '$var',
				),
				array(
					'name' => 'param',
					'content' => 'A number.',
					'types' => array( 'int' ),
					'variable' => '$num',
				),
				array(
					'name' => 'return',
					'content' => 'Whether the function was called correctly.',
					'types' => array( 'bool' ),
				),
			),
			$func['doc']['tags']
		);
	}

	/**
	 * Test that class docs are exported.
	 */
	public function test_class_docblocks() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * This is a class docblock.
			 */
			class Test_Class {}
			PHP
		);

		$class = $this->find_entity_data_in( $data, 'classes', 'Test_Class' );
		$this->assertIsArray( $class );
		$this->assertEquals( 'This is a class docblock.', $class['doc']['description'] );
	}

	/**
	 * Test that method docs are exported.
	 */
	public function test_method_docblocks() {

		$data = $this->parse_string(
			<<<'PHP'
			class Test_Class {
				/**
				 * This is a method docblock.
				 *
				 * @since 4.5.0
				 *
				 * @param mixed $var A parameter.
				 * @param array $arr Another parameter.
				 *
				 * @return mixed The first param.
				 */
				public function test_method( $var, $arr ) {
					return $var;
				}
			}
			PHP
		);

		$method = $this->find_entity_data_in( $data, 'classes', 'Test_Class', 'methods', 'test_method' );
		$this->assertIsArray( $method );
		$this->assertEquals( 'This is a method docblock.', $method['doc']['description'] );
	}

	/**
	 * Test that function docs are exported.
	 */
	public function test_property_docblocks() {

		$data = $this->parse_string(
			<<<'PHP'
			class Test_Class {
				/**
				 * This is a docblock for a class property.
				 *
				 * @since 3.0.0
				 *
				 * @var string
				 */
				public $a_string;
			}
			PHP
		);

		$property = $this->find_entity_data_in( $data, 'classes', 'Test_Class', 'properties', '$a_string' );
		$this->assertIsArray( $property );
		$this->assertEquals( 'This is a docblock for a class property.', $property['doc']['description'] );
	}

	/**
	 * Test that @deprecated tag with version and description is exported.
	 */
	public function test_deprecated_tag() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A deprecated function.
			 *
			 * @since 1.0.0
			 * @deprecated 3.0.0 Use new_func() instead.
			 */
			function deprecated_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'deprecated_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'deprecated' ) );
		$this->assertCount( 1, $tags );
		$this->assertStringContainsString( '3.0.0', $tags[0]['content'] );
	}

	/**
	 * Test that @deprecated without version is exported.
	 */
	public function test_deprecated_no_version() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Deprecated without version.
			 *
			 * @deprecated Use something_else() instead.
			 */
			function deprecated_no_version() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'deprecated_no_version' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'deprecated' ) );
		$this->assertCount( 1, $tags );
		$this->assertStringContainsString( 'something_else', $tags[0]['content'] );
	}

	/**
	 * Test that @author name-only is exported.
	 */
	public function test_author_name_only() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function by a single author.
			 *
			 * @author John Doe
			 */
			function authored_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'authored_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'author' ) );
		$this->assertCount( 1, $tags );
		$this->assertStringContainsString( 'John Doe', $tags[0]['content'] );
	}

	/**
	 * Test that @author with email is exported.
	 */
	public function test_author_with_email() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with author name and email.
			 *
			 * @author Jane Smith <jane@example.com>
			 */
			function authored_email_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'authored_email_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'author' ) );
		$this->assertCount( 1, $tags );
		$this->assertStringContainsString( 'Jane Smith', $tags[0]['content'] );
	}

	/**
	 * Test that @link URL is exported.
	 */
	public function test_link_tag() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with a link tag.
			 *
			 * @link https://example.com/docs
			 */
			function linked_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'linked_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'link' ) );
		$this->assertCount( 1, $tags );
		$this->assertStringContainsString( 'https://example.com/docs', $tags[0]['content'] );
	}

	/**
	 * Test that @link with description is exported.
	 */
	public function test_link_with_description() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with a link and description.
			 *
			 * @link https://example.com/api API documentation
			 */
			function linked_desc_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'linked_desc_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'link' ) );
		$this->assertCount( 1, $tags );
		$this->assertStringContainsString( 'API documentation', $tags[0]['content'] );
	}

	/**
	 * Test that @version is exported.
	 */
	public function test_version_tag() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with a version tag.
			 *
			 * @version 2.1.0
			 */
			function versioned_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'versioned_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'version' ) );
		$this->assertCount( 1, $tags );
		$this->assertStringContainsString( '2.1.0', $tags[0]['content'] );
	}

	/**
	 * Test that @source is exported.
	 */
	public function test_source_tag() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with a source tag.
			 *
			 * @source 10 20 Some source content.
			 */
			function sourced_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'sourced_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'source' ) );
		$this->assertCount( 1, $tags );
	}

	/**
	 * Test that @uses docblock tag is exported.
	 */
	public function test_uses_tag() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with a uses tag.
			 *
			 * @uses \WP_Query::get_posts() To fetch the posts.
			 */
			function uses_tag_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'uses_tag_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'uses' ) );
		$this->assertCount( 1, $tags );
	}

	/**
	 * Test that @covers is exported.
	 */
	public function test_covers_tag() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with a covers tag.
			 *
			 * @covers \WP_Query::get_posts
			 */
			function covers_tag_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'covers_tag_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'covers' ) );
		$this->assertCount( 1, $tags );
	}

	/**
	 * Test that @global is exported.
	 */
	public function test_global_tag() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with a global tag.
			 *
			 * @global WP_Locale $wp_locale WordPress date/time locale object.
			 */
			function global_tag_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'global_tag_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'global' ) );
		$this->assertCount( 1, $tags );
		$this->assertEquals( [ '\WP_Locale' ], $tags[0]['types'] );
		$this->assertEquals( '$wp_locale', $tags[0]['variable'] );
		$this->assertStringContainsString( 'locale object', $tags[0]['content'] );
	}

	/**
	 * Test that a malformed @since tag (empty) is still exported.
	 */
	public function test_malformed_since() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with a malformed since tag.
			 *
			 * @since
			 */
			function malformed_since_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'malformed_since_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'since' ) );
		$this->assertCount( 1, $tags );
		$this->assertEmpty( $tags[0]['content'] ?? '' );
	}

	/**
	 * Test that @param without type still exports.
	 */
	public function test_param_no_type() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with a param without type.
			 *
			 * @param $value
			 */
			function param_no_type_func( $value ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'param_no_type_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $tags );
		$this->assertEquals( '$value', $tags[0]['variable'] );
	}

	/**
	 * Test that a broken @return (no type) still exports.
	 */
	public function test_broken_return() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with a broken return tag.
			 *
			 * @return
			 */
			function broken_return_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'broken_return_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $tags );
	}

	/**
	 * Test that invalid tags are exported with their body in content.
	 */
	public function test_invalid_tags_generic_handler() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with various invalid tags handled by the generic handler.
			 *
			 * @param without proper format
			 * @return
			 * @var
			 * @throws
			 */
			function invalid_tags_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'invalid_tags_func' );
		$this->assertIsArray( $func );

		$tags = $func['doc']['tags'];

		$param = array_values( array_filter( $tags, fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param );
		$this->assertStringContainsString( 'without proper format', $param[0]['content'] );

		$return = array_values( array_filter( $tags, fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $return );
		$this->assertArrayHasKey( 'content', $return[0] );

		$var = array_values( array_filter( $tags, fn( $t ) => $t['name'] === 'var' ) );
		$this->assertCount( 1, $var );
		$this->assertArrayHasKey( 'content', $var[0] );

		$throws = array_values( array_filter( $tags, fn( $t ) => $t['name'] === 'throws' ) );
		$this->assertCount( 1, $throws );
		$this->assertArrayHasKey( 'content', $throws[0] );
	}

	/**
	 * Test that an empty docblock exports with empty description.
	 */
	public function test_empty_docblock() {

		$data = $this->parse_string(
			<<<'PHP'
			/** */
			function empty_docblock_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'empty_docblock_func' );
		$this->assertIsArray( $func );
		$this->assertEmpty( $func['doc']['description'] );
		$this->assertEmpty( $func['doc']['long_description'] );
	}

	/**
	 * Test that an undocumented function has empty doc fields.
	 */
	public function test_no_docblock() {

		$data = $this->parse_string(
			<<<'PHP'
			function undocumented_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'undocumented_func' );
		$this->assertIsArray( $func );
		$this->assertEmpty( $func['doc']['description'] );
	}

	/**
	 * Test that inline {@link} tags are preserved in descriptions.
	 */
	public function test_inline_link() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with inline {@link https://example.com link text} in the description.
			 */
			function inline_link_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'inline_link_func' );
		$this->assertIsArray( $func );
		$this->assertStringContainsString( 'link', $func['doc']['description'] );
	}

	/**
	 * Test that inline {@see} tags are preserved in descriptions.
	 */
	public function test_inline_see() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with inline {@see WP_Query} reference.
			 */
			function inline_see_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'inline_see_func' );
		$this->assertIsArray( $func );
		$this->assertStringContainsString( 'WP_Query', $func['doc']['description'] );
	}

	/**
	 * Test that markdown formatting is preserved in descriptions.
	 */
	public function test_markdown_in_description() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with **bold** and *italic* and `code` in the description.
			 */
			function markdown_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'markdown_func' );
		$this->assertIsArray( $func );
		$this->assertStringContainsString( 'bold', $func['doc']['description'] );
		$this->assertStringContainsString( 'italic', $func['doc']['description'] );
		$this->assertStringContainsString( 'code', $func['doc']['description'] );
	}

	/**
	 * Test that code blocks appear in long descriptions.
	 */
	public function test_code_block_in_description() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with a code block.
			 *
			 *     $result = do_something();
			 *     echo $result;
			 */
			function code_block_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'code_block_func' );
		$this->assertIsArray( $func );
		$this->assertNotEmpty( $func['doc']['long_description'] );
		$this->assertStringContainsString( 'do_something', $func['doc']['long_description'] );
	}

	/**
	 * Test that lists are preserved in long descriptions.
	 */
	public function test_lists_in_description() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with lists.
			 *
			 * - Item one
			 * - Item two
			 * - Item three
			 *
			 * 1. First
			 * 2. Second
			 */
			function list_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'list_func' );
		$this->assertIsArray( $func );
		$this->assertNotEmpty( $func['doc']['long_description'] );
		$this->assertStringContainsString( 'Item one', $func['doc']['long_description'] );
	}

	/**
	 * Test that multi-paragraph long descriptions have paragraph breaks.
	 */
	public function test_multi_paragraph_description() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * First paragraph of the long description.
			 *
			 * Second paragraph of the long description.
			 *
			 * Third paragraph of the long description.
			 */
			function multi_paragraph_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'multi_paragraph_func' );
		$this->assertIsArray( $func );
		$this->assertStringContainsString( 'Second paragraph', $func['doc']['long_description'] );
		$this->assertStringContainsString( 'Third paragraph', $func['doc']['long_description'] );
	}

	/**
	 * Test that HTML entities are preserved in descriptions.
	 */
	public function test_html_entities() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with HTML entities &amp; special &lt;characters&gt;.
			 */
			function html_entities_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'html_entities_func' );
		$this->assertIsArray( $func );
		$this->assertStringContainsString( '&amp;', $func['doc']['description'] );
	}

	/**
	 * Test that multi-line descriptions are joined into a single line.
	 */
	public function test_line_joining() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * This is a description that spans
			 * multiple lines but should be joined
			 * into a single line.
			 */
			function line_joining_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'line_joining_func' );
		$this->assertIsArray( $func );
		$desc = $func['doc']['description'];
		$this->assertStringNotContainsString( "\n", $desc );
		$this->assertStringContainsString( 'spans', $desc );
		$this->assertStringContainsString( 'single line', $desc );
	}

	/**
	 * Test intersection types in @param.
	 */
	public function test_phpdoc_intersection_types() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Intersection type in @param.
			 *
			 * @param Countable&Traversable $collection A collection.
			 * @return Countable&Traversable The same collection.
			 */
			function test_intersection_phpdoc( $collection ) {
				return $collection;
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_intersection_phpdoc' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$type_str = implode( '|', $param_tags[0]['types'] );
		$this->assertStringContainsString( 'Countable', $type_str );
		$this->assertStringContainsString( 'Traversable', $type_str );

		$return_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $return_tags );
	}

	/**
	 * Test generic types in @param.
	 */
	public function test_phpdoc_generic_types() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Generic type in @param.
			 *
			 * @param Collection<string, WP_Post> $posts A collection of posts.
			 * @return array<int, string> Mapped values.
			 */
			function test_generic_types( $posts ) {
				return [];
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_generic_types' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$this->assertNotEmpty( $param_tags[0]['types'] );
	}

	/**
	 * Test array shapes in @param.
	 */
	public function test_phpdoc_array_shapes() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Array shape in @param.
			 *
			 * @param array{id: int, name: string, active?: bool} $data The data shape.
			 * @return array{success: bool, message: string} The result shape.
			 */
			function test_array_shapes( $data ) {
				return [ 'success' => true, 'message' => '' ];
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_array_shapes' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$type_str = implode( '|', $param_tags[0]['types'] );
		$this->assertStringContainsString( 'array', $type_str );
	}

	/**
	 * Test list shapes in @param.
	 */
	public function test_phpdoc_list_shapes() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * List shape in @param.
			 *
			 * @param list{int, string} $pair A typed pair.
			 */
			function test_list_shapes( $pair ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_list_shapes' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$this->assertNotEmpty( $param_tags[0]['types'] );
	}

	/**
	 * Test object shapes in @param.
	 */
	public function test_phpdoc_object_shapes() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Object shape in @param.
			 *
			 * @param object{name: string, age: int} $person A person object.
			 */
			function test_object_shapes( $person ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_object_shapes' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$this->assertNotEmpty( $param_tags[0]['types'] );
	}

	/**
	 * Test callable types in @param.
	 */
	public function test_phpdoc_callable_types() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Callable type in @param.
			 *
			 * @param callable(int, string): bool $callback A callback function.
			 */
			function test_callable_types( $callback ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_callable_types' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$type_str = implode( '|', $param_tags[0]['types'] );
		$this->assertStringContainsString( 'callable', $type_str );
	}

	/**
	 * Test conditional return types.
	 */
	public function test_phpdoc_conditional_types() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Conditional return type.
			 *
			 * @template T
			 * @param T $value The value.
			 * @return ($value is string ? int : float) The conditional result.
			 */
			function test_conditional_types( $value ) {
				return is_string( $value ) ? strlen( $value ) : 1.0;
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_conditional_types' );
		$this->assertIsArray( $func );

		$return_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $return_tags );
		$this->assertNotEmpty( $return_tags[0]['types'] );
	}

	/**
	 * Test key-of type.
	 */
	public function test_phpdoc_key_of() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Key-of type.
			 *
			 * @param key-of<array{a: int, b: string}> $key A key from the shape.
			 */
			function test_key_of( $key ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_key_of' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$this->assertEquals( '$key', $param_tags[0]['variable'] );
	}

	/**
	 * Test value-of type.
	 */
	public function test_phpdoc_value_of() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Value-of type.
			 *
			 * @param value-of<array{a: int, b: string}> $value A value from the shape.
			 */
			function test_value_of( $value ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_value_of' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$this->assertNotEmpty( $param_tags[0]['types'] );
	}

	/**
	 * Test class-string types.
	 */
	public function test_phpdoc_class_string() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Class-string type.
			 *
			 * @param class-string<WP_Post> $class The class name.
			 * @return class-string The result class.
			 */
			function test_class_string( $class ) {
				return $class;
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_class_string' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$type_str = implode( '|', $param_tags[0]['types'] );
		$this->assertStringContainsString( 'class-string', $type_str );
	}

	/**
	 * Test const expression types.
	 */
	public function test_phpdoc_const_expressions() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Const expression types.
			 *
			 * @param Foo::BAR $value A constant value.
			 * @param Foo::BAR_* $pattern A constant pattern.
			 */
			function test_const_expressions( $value, $pattern ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_const_expressions' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 2, $param_tags );
	}

	/**
	 * Test offset access types.
	 */
	public function test_phpdoc_offset_access() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Offset access type.
			 *
			 * @template T of array
			 * @template K of key-of<T>
			 * @param T $arr The array.
			 * @param K $key The key.
			 * @return T[K] The value.
			 */
			function test_offset_access( $arr, $key ) {
				return $arr[ $key ];
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_offset_access' );
		$this->assertIsArray( $func );

		$return_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $return_tags );
		$this->assertNotEmpty( $return_tags[0]['types'] );
	}

	/**
	 * Test parenthesized types.
	 */
	public function test_phpdoc_parenthesized_types() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Parenthesized type.
			 *
			 * @param (int|string)[] $items Array of int or string.
			 */
			function test_parenthesized_types( $items ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_parenthesized_types' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$this->assertNotEmpty( $param_tags[0]['types'] );
	}

	/**
	 * Test static return type in PHPDoc.
	 */
	public function test_phpdoc_static_return() {

		$data = $this->parse_string(
			<<<'PHP'
			class PHPDoc_Return_Types {
				/**
				 * Static return type in PHPDoc.
				 *
				 * @return static The current instance.
				 */
				public function test_static_return() {
					return new static();
				}
			}
			PHP
		);

		$class = $this->find_entity_data_in( $data, 'classes', 'PHPDoc_Return_Types' );
		$this->assertIsArray( $class );
		$method = $this->find_entity_data_in( $class, 'methods', 'test_static_return' );
		$this->assertIsArray( $method );

		$return_tags = array_values( array_filter( $method['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $return_tags );
		$this->assertEquals( [ 'static' ], $return_tags[0]['types'] );
	}

	/**
	 * Test $this return type in PHPDoc.
	 */
	public function test_phpdoc_this_return() {

		$data = $this->parse_string(
			<<<'PHP'
			class PHPDoc_Return_Types {
				/**
				 * $this return type.
				 *
				 * @return $this The current instance for chaining.
				 */
				public function test_this_return() {
					return $this;
				}
			}
			PHP
		);

		$class = $this->find_entity_data_in( $data, 'classes', 'PHPDoc_Return_Types' );
		$this->assertIsArray( $class );
		$method = $this->find_entity_data_in( $class, 'methods', 'test_this_return' );
		$this->assertIsArray( $method );

		$return_tags = array_values( array_filter( $method['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $return_tags );
		$this->assertEquals( [ '$this' ], $return_tags[0]['types'] );
	}

	/**
	 * Test never return type in PHPDoc.
	 */
	public function test_phpdoc_never_type() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * Never/void/no-return types.
			 *
			 * @return never This never returns.
			 */
			function test_never_type() {
				throw new \Exception();
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_never_type' );
		$this->assertIsArray( $func );

		$return_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $return_tags );
		$this->assertEquals( [ 'never' ], $return_tags[0]['types'] );
	}

	/**
	 * Test void return type in PHPDoc.
	 */
	public function test_phpdoc_void_type() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * @return void
			 */
			function test_void_type() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_void_type' );
		$this->assertIsArray( $func );

		$return_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $return_tags );
		$this->assertEquals( [ 'void' ], $return_tags[0]['types'] );
	}

	/**
	 * Test no-return type in PHPDoc.
	 */
	public function test_phpdoc_no_return_type() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * @return no-return
			 */
			function test_no_return_type() {
				exit;
			}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_no_return_type' );
		$this->assertIsArray( $func );

		$return_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $return_tags );
		$this->assertEquals( [ 'no-return' ], $return_tags[0]['types'] );
	}

	/**
	 * Test WordPress @type argument hash with simple scalar types.
	 *
	 * The @type entries are embedded in the param tag content.
	 */
	public function test_phpdoc_type_hash_args() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * WordPress @type argument hash — simple scalars.
			 *
			 * @param array $args {
			 *     Optional. Arguments.
			 *
			 *     @type string $name         The name.
			 *     @type int|string $value    The value.
			 *     @type int[] $ids           Array of IDs.
			 *     @type WP_Post $post        A post object.
			 *     @type 'auto'|'low'|'high' $priority Priority level.
			 *     @type array<string, mixed> $schema  The schema.
			 * }
			 */
			function test_type_hash_args( $args = array() ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_type_hash_args' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$this->assertEquals( [ 'array' ], $param_tags[0]['types'] );

		// The @type entries should be present in the param content.
		$content = $param_tags[0]['content'];
		$this->assertStringContainsString( '$name', $content );
		$this->assertStringContainsString( '$value', $content );
		$this->assertStringContainsString( '$ids', $content );
		$this->assertStringContainsString( '$post', $content );
		$this->assertStringContainsString( '$priority', $content );
		$this->assertStringContainsString( '$schema', $content );
	}

	/**
	 * Test WordPress @type with special variable names.
	 */
	public function test_phpdoc_type_hash_special() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * WordPress @type with optional and numeric entries.
			 *
			 * @param array $args {
			 *     Arguments.
			 *
			 *     @type string $0             First positional arg.
			 *     @type string $font-family   Font family.
			 *     @type array  ...$0 {
			 *         Nested args.
			 *
			 *         @type string $key A key.
			 *     }
			 * }
			 */
			function test_type_hash_special( $args = array() ) {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'test_type_hash_special' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$content = $param_tags[0]['content'];
		$this->assertStringContainsString( '$0', $content );
		$this->assertStringContainsString( '$font-family', $content );
	}

	/**
	 * Test that @see tags with references and descriptions are exported.
	 */
	public function test_see_tag_with_reference() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with @see tags that have references and descriptions.
			 *
			 * @see Some_Class::method() Does something useful.
			 * @see https://example.com/docs
			 * @see Another_Class
			 */
			function see_tag_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'see_tag_func' );
		$this->assertIsArray( $func );

		$see_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'see' ) );
		$this->assertCount( 3, $see_tags );

		$this->assertEquals( 'Some_Class::method()', $see_tags[0]['refers'] );
		$this->assertEquals( 'Does something useful.', $see_tags[0]['content'] );

		$this->assertStringContainsString( 'https://example.com/docs', $see_tags[1]['refers'] );

		$this->assertEquals( 'Another_Class', $see_tags[2]['refers'] );
	}

	/**
	 * Test that @uses tags with references and descriptions are exported.
	 */
	public function test_uses_tag_with_reference() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with @uses tags with references and descriptions.
			 *
			 * @uses \WP_Query::get_posts() Fetches the posts.
			 * @uses \wp_list_pluck() For extracting fields.
			 */
			function uses_tag_with_desc_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'uses_tag_with_desc_func' );
		$this->assertIsArray( $func );

		$uses_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'uses' ) );
		$this->assertCount( 2, $uses_tags );

		$this->assertStringContainsString( 'get_posts', $uses_tags[0]['refers'] );
		$this->assertEquals( 'Fetches the posts.', $uses_tags[0]['content'] );

		$this->assertStringContainsString( 'wp_list_pluck', $uses_tags[1]['refers'] );
	}

	/**
	 * Test that @link without description generates an HTML anchor tag.
	 */
	public function test_link_no_description_generates_html() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with a @link tag that has no description (triggers HTML link generation).
			 *
			 * @link https://developer.wordpress.org/reference
			 */
			function link_no_desc_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'link_no_desc_func' );
		$this->assertIsArray( $func );

		$link_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'link' ) );
		$this->assertCount( 1, $link_tags );
		$this->assertStringContainsString( '<a href=', $link_tags[0]['content'] );
		$this->assertStringContainsString( 'developer.wordpress.org', $link_tags[0]['content'] );
		$this->assertArrayHasKey( 'link', $link_tags[0] );
	}

	/**
	 * Test that @link with trailing dot strips the dot from the URL.
	 */
	public function test_link_trailing_dot() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with a @link tag that has a URL ending in a period.
			 *
			 * @link https://example.com/docs.
			 */
			function link_trailing_dot_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'link_trailing_dot_func' );
		$this->assertIsArray( $func );

		$link_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'link' ) );
		$this->assertCount( 1, $link_tags );
		// The trailing dot should be preserved after the </a> tag, not in the href.
		$this->assertStringContainsString( '</a>.', $link_tags[0]['content'] );
	}

	/**
	 * Test that valid @author with email generates a mailto link.
	 */
	public function test_author_email_generates_mailto() {

		$data = $this->parse_string(
			<<<'PHP'
			/**
			 * A function with a valid @author tag with name and email.
			 *
			 * @author WordPress Core Team <team@wordpress.org>
			 */
			function author_valid_email_func() {}
			PHP
		);

		$func = $this->find_entity_data_in( $data, 'functions', 'author_valid_email_func' );
		$this->assertIsArray( $func );

		$author_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'author' ) );
		$this->assertCount( 1, $author_tags );
		$this->assertStringContainsString( 'mailto:', $author_tags[0]['content'] );
		$this->assertStringContainsString( 'WordPress Core Team', $author_tags[0]['content'] );
	}

	/**
	 * Test that deprecated hooks are exported.
	 */
	public function test_deprecated_hooks() {

		$data = $this->parse_string(
			<<<'PHP'
			do_action_deprecated( 'deprecated_action', array( $arg ), '3.0.0', 'new_action' );
			apply_filters_deprecated( 'deprecated_filter', array( $val ), '2.5.0' );
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'hooks', 'deprecated_action' );
		$this->assertIsArray( $hook );
		$this->assertEquals( 'action_deprecated', $hook['type'] );

		$hook = $this->find_entity_data_in( $data, 'hooks', 'deprecated_filter' );
		$this->assertIsArray( $hook );
		$this->assertEquals( 'filter_deprecated', $hook['type'] );
	}

	/**
	 * Test that ref array hooks are exported with correct type.
	 */
	public function test_ref_array_hooks() {

		$data = $this->parse_string(
			<<<'PHP'
			do_action_ref_array( 'ref_array_action_2', array( &$a, &$b ) );
			PHP
		);

		$hook = $this->find_entity_data_in( $data, 'hooks', 'ref_array_action_2' );
		$this->assertIsArray( $hook );
		$this->assertEquals( 'action_reference', $hook['type'] );
	}
}
