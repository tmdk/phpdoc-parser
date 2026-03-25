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

		$this->assertStringMatchesFormat(
			'%s'
			, $this->export_data['classes'][0]['doc']['long_description']
		);
	}

	/**
	 * Test that hooks which aren't documented don't receive docs from another node.
	 */
	public function test_undocumented_hook() {

		$this->assertHookHasDocs(
			'undocumented_hook'
			, array(
				'description' => '',
			)
		);
	}

	/**
	 * Test that hook docbloks are picked up.
	 */
	public function test_hook_docblocks() {

		$this->assertHookHasDocs(
			'test_action'
			, array( 'description' => 'A test action.' )
		);

		$this->assertHookHasDocs(
			'test_filter'
			, array( 'description' => 'A filter.' )
		);

		$this->assertHookHasDocs(
			'test_ref_array_action'
			, array( 'description' => 'A reference array action.' )
		);

		$this->assertHookHasDocs(
			'test_ref_array_filter'
			, array( 'description' => 'A reference array filter.' )
		);
	}

	/**
	 * Test that file-level docs are exported.
	 */
	public function test_file_docblocks() {

		$this->assertFileHasDocs(
			array( 'description' => 'This is the file-level docblock summary.' )
		);
	}

	/**
	 * Test that function docs are exported.
	 */
	public function test_function_docblocks() {

		$this->assertFunctionHasDocs(
			'test_func'
			, array(
				'description' => 'This is a function docblock.',
				'long_description' => '<p>This function is just a test, but we\'ve added this description anyway.</p>',
				'tags' => array(
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
			)
		);
	}

	/**
	 * Test that class docs are exported.
	 */
	public function test_class_docblocks() {

		$this->assertClassHasDocs(
			'Test_Class'
			, array( 'description' => 'This is a class docblock.' )
		);
	}

	/**
	 * Test that method docs are exported.
	 */
	public function test_method_docblocks() {

		$this->assertMethodHasDocs(
			'Test_Class'
			, 'test_method'
			, array( 'description' => 'This is a method docblock.' )
		);
	}

	/**
	 * Test that function docs are exported.
	 */
	public function test_property_docblocks() {

		$this->assertPropertyHasDocs(
			'Test_Class'
			, '$a_string'
			, array( 'description' => 'This is a docblock for a class property.' )
		);
	}

	/**
	 * Test that @deprecated tag with version and description is exported.
	 */
	public function test_deprecated_tag() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'deprecated_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'deprecated' ) );
		$this->assertCount( 1, $tags );
		$this->assertStringContainsString( '3.0.0', $tags[0]['content'] );
	}

	/**
	 * Test that @deprecated without version is exported.
	 */
	public function test_deprecated_no_version() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'deprecated_no_version' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'deprecated' ) );
		$this->assertCount( 1, $tags );
		$this->assertStringContainsString( 'something_else', $tags[0]['content'] );
	}

	/**
	 * Test that @author name-only is exported.
	 */
	public function test_author_name_only() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'authored_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'author' ) );
		$this->assertCount( 1, $tags );
		$this->assertStringContainsString( 'John Doe', $tags[0]['content'] );
	}

	/**
	 * Test that @author with email is exported.
	 */
	public function test_author_with_email() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'authored_email_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'author' ) );
		$this->assertCount( 1, $tags );
		$this->assertStringContainsString( 'Jane Smith', $tags[0]['content'] );
	}

	/**
	 * Test that @link URL is exported.
	 */
	public function test_link_tag() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'linked_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'link' ) );
		$this->assertCount( 1, $tags );
		$this->assertStringContainsString( 'https://example.com/docs', $tags[0]['content'] );
	}

	/**
	 * Test that @link with description is exported.
	 */
	public function test_link_with_description() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'linked_desc_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'link' ) );
		$this->assertCount( 1, $tags );
		$this->assertStringContainsString( 'API documentation', $tags[0]['content'] );
	}

	/**
	 * Test that @version is exported.
	 */
	public function test_version_tag() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'versioned_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'version' ) );
		$this->assertCount( 1, $tags );
		$this->assertStringContainsString( '2.1.0', $tags[0]['content'] );
	}

	/**
	 * Test that @source is exported.
	 */
	public function test_source_tag() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'sourced_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'source' ) );
		$this->assertCount( 1, $tags );
	}

	/**
	 * Test that @uses docblock tag is exported.
	 */
	public function test_uses_tag() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'uses_tag_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'uses' ) );
		$this->assertCount( 1, $tags );
	}

	/**
	 * Test that @covers is exported.
	 */
	public function test_covers_tag() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'covers_tag_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'covers' ) );
		$this->assertCount( 1, $tags );
	}

	/**
	 * Test that @global is exported.
	 */
	public function test_global_tag() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'global_tag_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'global' ) );
		$this->assertCount( 1, $tags );
	}

	/**
	 * Test that a malformed @since tag (empty) is still exported.
	 */
	public function test_malformed_since() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'malformed_since_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'since' ) );
		$this->assertCount( 1, $tags );
		$this->assertEmpty( $tags[0]['content'] ?? '' );
	}

	/**
	 * Test that @param without type still exports.
	 */
	public function test_param_no_type() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'param_no_type_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $tags );
		$this->assertEquals( '$value', $tags[0]['variable'] );
	}

	/**
	 * Test that a broken @return (no type) still exports.
	 */
	public function test_broken_return() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'broken_return_func' );
		$this->assertIsArray( $func );

		$tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $tags );
	}

	/**
	 * Test that an empty docblock exports with empty description.
	 */
	public function test_empty_docblock() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'empty_docblock_func' );
		$this->assertIsArray( $func );
		$this->assertEmpty( $func['doc']['description'] );
		$this->assertEmpty( $func['doc']['long_description'] );
	}

	/**
	 * Test that an undocumented function has empty doc fields.
	 */
	public function test_no_docblock() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'undocumented_func' );
		$this->assertIsArray( $func );
		$this->assertEmpty( $func['doc']['description'] );
	}

	/**
	 * Test that inline {@link} tags are preserved in descriptions.
	 */
	public function test_inline_link() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'inline_link_func' );
		$this->assertIsArray( $func );
		$this->assertStringContainsString( 'link', $func['doc']['description'] );
	}

	/**
	 * Test that inline {@see} tags are preserved in descriptions.
	 */
	public function test_inline_see() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'inline_see_func' );
		$this->assertIsArray( $func );
		$this->assertStringContainsString( 'WP_Query', $func['doc']['description'] );
	}

	/**
	 * Test that markdown formatting is preserved in descriptions.
	 */
	public function test_markdown_in_description() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'markdown_func' );
		$this->assertIsArray( $func );
		$this->assertStringContainsString( 'bold', $func['doc']['description'] );
		$this->assertStringContainsString( 'italic', $func['doc']['description'] );
		$this->assertStringContainsString( 'code', $func['doc']['description'] );
	}

	/**
	 * Test that code blocks appear in long descriptions.
	 */
	public function test_code_block_in_description() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'code_block_func' );
		$this->assertIsArray( $func );
		$this->assertNotEmpty( $func['doc']['long_description'] );
		$this->assertStringContainsString( 'do_something', $func['doc']['long_description'] );
	}

	/**
	 * Test that lists are preserved in long descriptions.
	 */
	public function test_lists_in_description() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'list_func' );
		$this->assertIsArray( $func );
		$this->assertNotEmpty( $func['doc']['long_description'] );
		$this->assertStringContainsString( 'Item one', $func['doc']['long_description'] );
	}

	/**
	 * Test that multi-paragraph long descriptions have paragraph breaks.
	 */
	public function test_multi_paragraph_description() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'multi_paragraph_func' );
		$this->assertIsArray( $func );
		$this->assertStringContainsString( 'Second paragraph', $func['doc']['long_description'] );
		$this->assertStringContainsString( 'Third paragraph', $func['doc']['long_description'] );
	}

	/**
	 * Test that HTML entities are preserved in descriptions.
	 */
	public function test_html_entities() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'html_entities_func' );
		$this->assertIsArray( $func );
		$this->assertStringContainsString( '&amp;', $func['doc']['description'] );
	}

	/**
	 * Test that multi-line descriptions are joined into a single line.
	 */
	public function test_line_joining() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'line_joining_func' );
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

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_intersection_phpdoc' );
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

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_generic_types' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$this->assertNotEmpty( $param_tags[0]['types'] );
	}

	/**
	 * Test array shapes in @param.
	 */
	public function test_phpdoc_array_shapes() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_array_shapes' );
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

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_list_shapes' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$this->assertNotEmpty( $param_tags[0]['types'] );
	}

	/**
	 * Test object shapes in @param.
	 */
	public function test_phpdoc_object_shapes() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_object_shapes' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$this->assertNotEmpty( $param_tags[0]['types'] );
	}

	/**
	 * Test callable types in @param.
	 */
	public function test_phpdoc_callable_types() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_callable_types' );
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

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_conditional_types' );
		$this->assertIsArray( $func );

		$return_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $return_tags );
		$this->assertNotEmpty( $return_tags[0]['types'] );
	}

	/**
	 * Test key-of type.
	 */
	public function test_phpdoc_key_of() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_key_of' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$this->assertEquals( '$key', $param_tags[0]['variable'] );
	}

	/**
	 * Test value-of type.
	 */
	public function test_phpdoc_value_of() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_value_of' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$this->assertNotEmpty( $param_tags[0]['types'] );
	}

	/**
	 * Test class-string types.
	 */
	public function test_phpdoc_class_string() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_class_string' );
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

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_const_expressions' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 2, $param_tags );
	}

	/**
	 * Test offset access types.
	 */
	public function test_phpdoc_offset_access() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_offset_access' );
		$this->assertIsArray( $func );

		$return_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $return_tags );
		$this->assertNotEmpty( $return_tags[0]['types'] );
	}

	/**
	 * Test parenthesized types.
	 */
	public function test_phpdoc_parenthesized_types() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_parenthesized_types' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$this->assertNotEmpty( $param_tags[0]['types'] );
	}

	/**
	 * Test static return type in PHPDoc.
	 */
	public function test_phpdoc_static_return() {

		$class = $this->find_entity_data_in( $this->export_data, 'classes', 'PHPDoc_Return_Types' );
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

		$class = $this->find_entity_data_in( $this->export_data, 'classes', 'PHPDoc_Return_Types' );
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

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_never_type' );
		$this->assertIsArray( $func );

		$return_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $return_tags );
		$this->assertEquals( [ 'never' ], $return_tags[0]['types'] );
	}

	/**
	 * Test void return type in PHPDoc.
	 */
	public function test_phpdoc_void_type() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_void_type' );
		$this->assertIsArray( $func );

		$return_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'return' ) );
		$this->assertCount( 1, $return_tags );
		$this->assertEquals( [ 'void' ], $return_tags[0]['types'] );
	}

	/**
	 * Test no-return type in PHPDoc.
	 */
	public function test_phpdoc_no_return_type() {

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_no_return_type' );
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

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_type_hash_args' );
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

		$func = $this->find_entity_data_in( $this->export_data, 'functions', 'test_type_hash_special' );
		$this->assertIsArray( $func );

		$param_tags = array_values( array_filter( $func['doc']['tags'], fn( $t ) => $t['name'] === 'param' ) );
		$this->assertCount( 1, $param_tags );
		$content = $param_tags[0]['content'];
		$this->assertStringContainsString( '$0', $content );
		$this->assertStringContainsString( '$font-family', $content );
	}
}
