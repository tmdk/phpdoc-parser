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
		$const_parser  = new ConstExprParser( $config );
		$this->parser = new PhpDocParser(
			$config,
			new TypeParser( $config, $const_parser ),
			$const_parser
		);

		$this->factories = $factories;
	}

	public function create( string $tagLine, ?TypeContext $context = null ): Tag {
		// Prefix continuation lines with '*' so the lexer consumes it as part of TOKEN_PHPDOC_EOL.
		$tagLine = str_replace( "\n", "\n*", $tagLine );

		// Escape double quotes to prevent greedy matching of TOKEN_DOCTRINE_ANNOTATION_STRING
		// (everthing between quotes, including newlines).
		$tagLine = str_replace( '"', self::DQUOTE_ESCAPE, $tagLine );

		$tokens = $this->lexer->tokenize( $tagLine . "\n" );

		// Restore double quotes.
		foreach ( $tokens as &$token ) {
			if ( ! str_contains( $token[ Lexer::VALUE_OFFSET ], self::DQUOTE_ESCAPE ) ) {
				continue;
			}

			$token[ Lexer::VALUE_OFFSET ] = str_replace(
				self::DQUOTE_ESCAPE,
				'"',
				$token[ Lexer::VALUE_OFFSET ]
			);
		}

		unset( $token );

		$token_iterator = new TokenIterator( $tokens );
		$ast           = $this->parser->parseTag( $token_iterator );

		if ( property_exists( $ast->value, 'description' ) ) {
			$description = $ast->value->description;

			// The phpstan parser stops when it sees another tag, so we simply append the rest of
			// the tokens. This ensures compatibility with the WordPress array shape doc format.
			$tokens_after_description = array_slice( $tokens, $token_iterator->currentTokenIndex() );

			foreach ( $tokens_after_description as $token ) {
				if ( $token[ Lexer::TYPE_OFFSET ] === Lexer::TOKEN_PHPDOC_EOL ) {
					$description .= "\n";
					continue;
				}
				$description .= $token[ Lexer::VALUE_OFFSET ];
			}

			$ast->value->setAttribute( 'description', rtrim( $description, "\n" ) );
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
}
