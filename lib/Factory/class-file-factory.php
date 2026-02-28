<?php
/**
 * File_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt\{Class_, Const_, Declare_, Function_, InlineHTML, Interface_, Trait_};
use WP_Parser\Reflection\File;
use WP_Parser\Source_File;

/**
 * Factory for creating File objects from php-parser AST.
 */
class File_Factory {

	public function __construct( private Docblock_Factory $docblock_factory ) {
	}

	/**
	 * Create a File from an AST.
	 *
	 * @param array       $nodes The php-parser AST.
	 * @param Source_File $source_file Source file.
	 *
	 * @return File
	 */
	public function create( array $nodes, Source_File $source_file ): File {
		$file = new File();
		$file->set_path( $source_file->get_filename() );
		$file->set_root( $source_file->get_base_dir() );

		$doc_comment = $this->get_file_doc_comment( $nodes );

		$docblock = null;
		if ( $doc_comment ) {
			$docblock = $this->docblock_factory->create( $doc_comment );
		}
		if ( $docblock === null ) {
			$docblock = $this->docblock_factory->create_empty();
		}

		$file->set_doc_block( $docblock );

		return $file;
	}

	private function get_file_doc_comment( array $nodes ): ?Doc {
		$comment_nodes = [];
		$node          = null;

		foreach ( $nodes as $node ) {
			$comment_nodes[] = $node;
			if ( ! $node instanceof Declare_ && ! $node instanceof InlineHTML ) {
				break;
			}
		}

		if ( $node === null ) {
			return null;
		}

		$comments     = array_merge( [], ...array_map( fn( $node ) => $node->getComments(), $comment_nodes ) );
		$doc_comments = array_values( array_filter( $comments, fn( $comment ) => $comment instanceof Doc ) );

		if ( count( $doc_comments ) === 0 ) {
			return null;
		}

		if (
			(
				! $node instanceof Const_ && ! $node instanceof Class_ &&
				! $node instanceof Function_ && ! $node instanceof Interface_ &&
				! $node instanceof Trait_
			)
			|| count( $doc_comments ) > 1
		) {
			return $doc_comments[0];
		}

		return null;
	}
}
