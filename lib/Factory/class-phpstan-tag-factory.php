<?php
/**
 * PHPStan_Tag_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\AbstractPHPStanFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\PHPStanFactory;
use phpDocumentor\Reflection\DocBlock\Tags\InvalidTag;
use phpDocumentor\Reflection\Types\Context as TypeContext;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\ParserException;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use RuntimeException;

/**
 * Enhanced version of AbstractPHPStanFactory that addresses a few issues:
 *
 * - Fix incorrect parsing of description continuation lines beginning with '*',
 * - Prevent lexing of double quotes as TOKEN_DOCTRINE_ANNOTATION_STRING,
 * - Fix wrong name in InvalidTag when caused by RuntimeException.
 */
class PHPStan_Tag_Factory extends AbstractPHPStanFactory {

	private const DQUOTE_ESCAPE = '%%PHPSTAN_FACTORY_DQUOTE%%';

	private PhpDocParser $parser;
	private Lexer $lexer;
	/** @var PHPStanFactory[] */
	private array $factories;

	public function __construct( PHPStanFactory ...$factories ) {
		parent::__construct( ...$factories );

		// Re-initialize parser and lexer since parent's fields are private.
		$config       = new ParserConfig( [ 'indexes' => true, 'lines' => true ] );
		$this->lexer  = new Lexer( $config );
		$const_parser = new ConstExprParser( $config );
		$this->parser = new PhpDocParser(
			$config,
			new TypeParser( $config, $const_parser ),
			$const_parser
		);

		$this->factories = $factories;
	}

	public function create( string $tagLine, ?TypeContext $context = null ): Tag {
		try {
			$tokens = $this->tokenize_line( $tagLine );
			$ast    = $this->parser->parseTag( $tokens );
			if ( property_exists( $ast->value, 'description' ) === true ) {
				$ast->value->setAttribute(
					'description',
					rtrim( $ast->value->description . $tokens->joinUntil( Lexer::TOKEN_END ), "\n" )
				);
			}
		} catch ( ParserException $e ) {
			return InvalidTag::create( $tagLine, '' )->withError( $e );
		}

		if ( $context === null ) {
			$context = new TypeContext( '' );
		}

		try {
			foreach ( $this->factories as $factory ) {
				if ( $factory->supports( $ast, $context ) ) {
					return $factory->create( $ast, $context );
				}
			}
		} catch ( RuntimeException $e ) {
			return InvalidTag::create( (string) $ast->value, ltrim( $ast->name, '@' ) )->withError( $e );
		}

		return InvalidTag::create( (string) $ast->value, ltrim( $ast->name, '@' ) );
	}

	private function tokenize_line( string $tag_line ): TokenIterator {
		// 1. Prefix continuation lines with '* ' so the lexer consumes it as part of TOKEN_PHPDOC_EOL.
		// 2. Escape double quotes to prevent greedy matching of TOKEN_DOCTRINE_ANNOTATION_STRING
		//    (everthing between quotes, including newlines).
		$tag_line = str_replace(
			[ "\n", '"' ],
			[ "\n* ", self::DQUOTE_ESCAPE ],
			$tag_line
		);

		$tag_line = str_replace( '"', self::DQUOTE_ESCAPE, $tag_line );

		$tokens = $this->lexer->tokenize( $tag_line . "\n" );
		$fixed  = [];

		foreach ( $tokens as $token ) {
			$token_value = $token[ Lexer::VALUE_OFFSET ];

			// Remove prefix and horizontal whitespace from EOL tokens so they
			// don't end up in the description.
			if ( $token[ Lexer::TYPE_OFFSET ] === Lexer::TOKEN_PHPDOC_EOL ) {
				$token[ Lexer::VALUE_OFFSET ] = trim( $token_value, " \t*" );
			}

			// Restore escaped double quotes.
			if ( str_contains( $token_value, self::DQUOTE_ESCAPE ) ) {
				$token[ Lexer::VALUE_OFFSET ] = str_replace(
					self::DQUOTE_ESCAPE,
					'"',
					$token_value
				);
			}

			$fixed[] = $token;
		}

		return new TokenIterator( $fixed );
	}
}
