<?php
/**
 * Docblock_Codeblock_Visitor
 *
 * @package WP_Parser\v2\Reflection\Visitors
 */

namespace WP_Parser\Visitors;

use PhpParser\{Comment\Doc, Node, NodeVisitorAbstract};

enum Codeblock_State {
	case Start;
	case DocBlock;
	case Summary;
	case Description;
	case CodeBlock;
	case End;
}

enum Codeblock_Token_Type {
	case Whitespace;
	case Tag;
	case Text;
	case CloseDoc;
	case OpenDoc;
	case EOL;
}

class Codeblock_Token {
	public const Whitespace = Codeblock_Token_Type::Whitespace;
	public const Tag        = Codeblock_Token_Type::Tag;
	public const Text       = Codeblock_Token_Type::Text;
	public const CloseDoc   = Codeblock_Token_Type::CloseDoc;
	public const OpenDoc    = Codeblock_Token_Type::OpenDoc;
	public const EOL        = Codeblock_Token_Type::EOL;

	public function __construct( public Codeblock_Token_Type $type, public string $value ) {
	}
}

class Codeblock_Token_Iterator implements \Iterator {
	public function __construct( private array $tokens ) {
	}

	public function current(): mixed {
		return current( $this->tokens );
	}

	public function next(): void {
		next( $this->tokens );
	}

	public function key(): string|int|null {
		return key( $this->tokens );
	}

	public function valid(): bool {
		return $this->key() !== null;
	}

	public function rewind(): void {
		reset( $this->tokens );
	}

	public function consume( Codeblock_Token_Type $type ): ?Codeblock_Token {
		if ( ! $this->valid() ) {
			return null;
		}

		if ( $this->current()->type === $type ) {
			$token = $this->current();
			$this->next();

			return $token;
		}

		return null;
	}

	public function is_empty(): bool {
		return empty( $this->tokens );
	}

	public function all( callable $callback ): bool {
		return array_all( $this->tokens, $callback );
	}
}

class Docblock_Codeblock_Visitor extends NodeVisitorAbstract {

	public function enterNode( Node $node ) {
		$doc = $node->getDocComment();

		if ( ! $doc ) {
			return null;
		}

		if ( ! str_starts_with( trim( $doc->getText() ), '/**' ) ) {
			return null;
		}

		$reformatted_doc = $this->format_indented_codeblocks( $doc );

		if ( $reformatted_doc !== $doc ) {
			$node->setDocComment( $reformatted_doc );
		}
	}

	/**
	 * Get the indentation width of a line (number of leading spaces/tabs).
	 *
	 * @param string $line The line to measure
	 *
	 * @return int Number of spaces (tabs count as 4 spaces)
	 */
	function get_indentation_width( string $line ): int {
		if ( preg_match( '/^([ \t]+)/', $line, $matches ) ) {
			$indent = $matches[1];
			$indent = str_replace( "\t", '    ', $indent );

			return strlen( $indent );
		}

		return 0;
	}

	/**
	 * Extract indented code blocks from a docblock text.
	 *
	 * @param Doc $docblock The raw docblock text
	 *
	 * @return Doc
	 */
	function format_indented_codeblocks( Doc $docblock ): Doc {
		$docblock_text = $docblock->getText();
		$lines         = preg_split( '/[\r\n]+/', $docblock_text, -1, PREG_SPLIT_NO_EMPTY );

		$codeblocks = $this->extract_codeblocks( $lines );

		if ( empty( $codeblocks ) ) {
			return $docblock;
		}

		$line_prefix = '* ';
		if ( preg_match( '/^([ \t]++)\*/m', $docblock_text, $matches ) ) {
			$line_prefix = $matches[1] . $line_prefix;
		}

		$with_line_numbers = static function ( array $lines ) {
			foreach ( $lines as $key => $line ) {
				yield $key + 1 => $line;
			}
		};

		$codeblock = reset( $codeblocks );
		$lines     = $with_line_numbers( $lines );

		$formatted_lines = '';

		foreach ( $lines as $line_num => $line ) {
			if ( ! $codeblock || $line_num !== $codeblock['startLine'] ) {
				$formatted_lines .= "$line\n";
				continue;
			}

			$formatted_lines .= $line_prefix . "```\n";

			foreach ( $codeblock['lines'] as $key => $codeblock_line ) {
				$formatted_lines .= $line_prefix . $codeblock_line;
				if ( array_key_last( $codeblock['lines'] ) !== $key ) {
					$lines->next();
				}
			}

			$formatted_lines .= $line_prefix . "```\n";
			$codeblock       = next( $codeblocks );
		}

		return new Doc(
			$formatted_lines,
			$docblock->getStartLine(),
			$docblock->getStartFilePos(),
			$docblock->getStartTokenPos(),
			$docblock->getEndLine(),
			$docblock->getEndFilePos(),
			$docblock->getEndTokenPos(),
		);
	}

	function pretty_print_line( Codeblock_Token_Iterator $tokens ): string {
		$line = '';

		foreach ( $tokens as $token ) {
			$line .= $token->value;
			if ( $token->type === Codeblock_Token::CloseDoc ) {
				break;
			}
		}

		$line = rtrim( $line, "\n" );
		$line = preg_replace( '/^(?:\t| {4})/', '', $line );

		return "$line\n";
	}

	function is_blank_line( Codeblock_Token_Iterator $tokens ): bool {
		return $tokens->is_empty() ||
			$tokens->all(
				fn( $token ) => $token->type === Codeblock_Token::Whitespace
					|| $token->type === Codeblock_Token::EOL
			);
	}

	/**
	 * @param string $line
	 *
	 * @return Codeblock_Token_Iterator<Codeblock_Token>
	 */
	function tokenize_line( string $line ): Codeblock_Token_Iterator {
		$tokens = preg_split( '#([ \t]+|/\*\*|\*/)#', $line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		$tokens = array_map(
			fn( $value ) => match ( true ) {
				(bool) preg_match( '/[ \t]/', $value ) => new Codeblock_Token( Codeblock_Token::Whitespace, $value ),
				(bool) preg_match( '/^@\w+/', $value ) => new Codeblock_Token( Codeblock_Token::Tag, $value ),
				$value === '/**' => new Codeblock_Token( Codeblock_Token::OpenDoc, $value ),
				$value === '*/' => new Codeblock_Token( Codeblock_Token::CloseDoc, $value ),
				default => new Codeblock_Token( Codeblock_Token::Text, $value ),
			},
			$tokens
		);

		$tokens[] = new Codeblock_Token( Codeblock_Token::EOL, "\n" );

		return new Codeblock_Token_Iterator( $tokens );
	}

	private function extract_codeblocks( array $lines ): array {
		$state             = Codeblock_State::Start;
		$prev_line_blank   = false;
		$current_codeblock = null;
		$blocks            = [];

		foreach ( $lines as $i => $line ) {
			$line_num = $i + 1;

			// Remove the leading * and normalize
			$line = preg_replace( '/^[ \t]*+(?:\*(?!\/) ?+)?/', '', $line );

			$tokens = $this->tokenize_line( $line );

			$is_blank_line = $this->is_blank_line( $tokens );

			// Get indentation width
			$whitespace   = $tokens->consume( Codeblock_Token::Whitespace );
			$indent_width = $whitespace ? $this->get_indentation_width( $whitespace->value ) : 0;

			$prev_state = $state;

			$first_non_whitespace_token = $tokens->current();

			// If we encounter an phpdoc open token token after we've opened the docblock,
			// interpret it as text.
			if ( $first_non_whitespace_token->type === Codeblock_Token::OpenDoc && $state !== Codeblock_State::Start ) {
				$first_non_whitespace_token = new Codeblock_Token(
					Codeblock_Token::Text,
					$first_non_whitespace_token->value
				);
			}

			$state = match ( [ $state, $first_non_whitespace_token->type ] ) {
				[ Codeblock_State::Start, Codeblock_Token::OpenDoc ] => Codeblock_State::DocBlock,
				[ Codeblock_State::DocBlock, Codeblock_Token::Tag ],
				[ Codeblock_State::DocBlock, Codeblock_Token::CloseDoc ],
				[ Codeblock_State::Summary, Codeblock_Token::Tag ],
				[ Codeblock_State::Summary, Codeblock_Token::CloseDoc ],
				[ Codeblock_State::Description, Codeblock_Token::CloseDoc ],
				[ Codeblock_State::CodeBlock, Codeblock_Token::Tag ],
				[ Codeblock_State::CodeBlock, Codeblock_Token::CloseDoc ],
				[ Codeblock_State::Description, Codeblock_Token::Tag ] => Codeblock_State::End,
				[ Codeblock_State::DocBlock, Codeblock_Token::Text ] => Codeblock_State::Summary,
				[ Codeblock_State::Summary, Codeblock_Token::Text ] => match ( true ) {
					str_ends_with( rtrim( $line ), '.' ) => Codeblock_State::Description,
					default => $state,
				},
				[ Codeblock_State::Summary, Codeblock_Token::EOL ] => Codeblock_State::Description,
				[ Codeblock_State::Description, Codeblock_Token::Text ] => match ( true ) {
					$prev_line_blank && $indent_width >= 4 => Codeblock_State::CodeBlock,
					default => $state,
				},
				[ Codeblock_State::CodeBlock, Codeblock_Token::Text ] => match ( true ) {
					$indent_width < 4 => Codeblock_State::Description,
					default => $state,
				},
				default => $state
			};

			$tokens->rewind();

			if ( $state === Codeblock_State::CodeBlock && $state !== $prev_state ) {
				$current_codeblock = [
					'lines'     => [ $this->pretty_print_line( $tokens ) ],
					'startLine' => $line_num,
				];
			}

			if ( $state === Codeblock_State::CodeBlock && $state === $prev_state ) {
				$current_codeblock['lines'][] = $this->pretty_print_line( $tokens );
			}

			if ( $prev_state === Codeblock_State::CodeBlock && $state !== $prev_state ) {
				if ( $prev_line_blank ) {
					array_pop( $current_codeblock['lines'] );
				}

				$blocks[]          = $current_codeblock;
				$current_codeblock = null;
			}

			$prev_line_blank = $is_blank_line;

			if ( $state === Codeblock_State::End ) {
				break;
			}
		}

		if ( ! empty( $currentBlock ) ) {
			$blocks[] = $current_codeblock;
		}

		return $blocks;
	}
}
