<?php
/**
 * Standard_Tag_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\DocBlock\Tag;
use phpDocumentor\Reflection\DocBlock\TagFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Author;
use phpDocumentor\Reflection\DocBlock\Tags\Covers;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\ExtendsFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\Factory;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\ImplementsFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\MethodFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\MixinFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\ParamFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\PropertyFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\PropertyReadFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\PropertyWriteFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\ReturnFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\TemplateCovariantFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\TemplateFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\ThrowsFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Factory\VarFactory;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use phpDocumentor\Reflection\DocBlock\Tags\InvalidTag;
use phpDocumentor\Reflection\DocBlock\Tags\Link as LinkTag;
use phpDocumentor\Reflection\DocBlock\Tags\Since;
use phpDocumentor\Reflection\DocBlock\Tags\Source;
use phpDocumentor\Reflection\DocBlock\Tags\Version;
use WP_Parser\Tag\Global_Tag;
use WP_Parser\Tag\See_Tag;
use WP_Parser\Tag\Uses_Tag;
use phpDocumentor\Reflection\FqsenResolver;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Context as TypeContext;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Webmozart\Assert\Assert;

/**
 * Creates a Tag object given the contents of a tag.
 *
 * This Factory is capable of determining the appropriate class for a tag and
 * instantiate it using its `create` factory method. The `create` factory method
 * of a Tag can have a variable number of arguments; this way you can pass the
 * dependencies that you need to construct a tag object.
 *
 * This Factory also features a Service Locator component that is used to pass
 * the right dependencies to the `create` method of a tag; each dependency
 * should be registered as a service or as a parameter.
 */
class Standard_Tag_Factory implements TagFactory {

	/** PCRE regular expression matching a tag name. */
	public const REGEX_TAGNAME = '[\w\-\_\\\\:]+';

	/**
	 * @var array<string, class-string<Tag>|Tag|Factory> An array with a tag as a key, and an
	 *                               FQCN to a class that handles it as an array value.
	 */
	private array $tag_handler_mappings = [
		'author'     => Author::class,
		'covers'     => Covers::class,
		'deprecated' => Deprecated::class,
		'link'       => LinkTag::class,
		'global'     => Global_Tag::class,
		'see'        => See_Tag::class,
		'since'      => Since::class,
		'source'     => Source::class,
		'uses'       => Uses_Tag::class,
		'version'    => Version::class,
	];

	/**
	 * @var array<class-string<Tag>> An array with an annotation as a key, and an
	 *      FQCN to a class that handles it as an array value.
	 */
	private array $annotation_mappings = [];

	/**
	 * @var ReflectionParameter[][] a lazy-loading cache containing parameters
	 *      for each tagHandler that has been used.
	 */
	private array $tag_handler_parameter_cache = [];

	private FqsenResolver $fqsen_resolver;

	/**
	 * @var mixed[] an array representing a simple Service Locator where we can store parameters and
	 *     services that can be inserted into the Factory Methods of Tag Handlers.
	 */
	private array $service_locator = [];

	private function __construct( FqsenResolver $fqsen_resolver ) {
		$this->fqsen_resolver = $fqsen_resolver;

		$this->add_service( $fqsen_resolver, FqsenResolver::class );
	}

	/**
	 * Initialize this tag factory with the means to resolve an FQSEN and
	 * register all default tag handlers.
	 *
	 * @param FqsenResolver $fqsen_resolver The FQSEN resolver.
	 *
	 * @return self
	 */
	public static function create_instance( FqsenResolver $fqsen_resolver ): self {
		$tag_factory         = new self( $fqsen_resolver );
		$description_factory = new DescriptionFactory( $tag_factory );
		$type_resolver       = new TypeResolver( $fqsen_resolver );

		$phpstan_tag_factory = new PHPStan_Tag_Factory(
			new ParamFactory( $type_resolver, $description_factory ),
			new VarFactory( $type_resolver, $description_factory ),
			new ReturnFactory( $type_resolver, $description_factory ),
			new PropertyFactory( $type_resolver, $description_factory ),
			new PropertyReadFactory( $type_resolver, $description_factory ),
			new PropertyWriteFactory( $type_resolver, $description_factory ),
			new MethodFactory( $type_resolver, $description_factory ),
			new MixinFactory( $type_resolver, $description_factory ),
			new ImplementsFactory( $type_resolver, $description_factory ),
			new ExtendsFactory( $type_resolver, $description_factory ),
			new TemplateFactory( $type_resolver, $description_factory ),
			new TemplateCovariantFactory( $type_resolver, $description_factory ),
			new ThrowsFactory( $type_resolver, $description_factory ),
		);

		$tag_factory->add_service( $description_factory );
		$tag_factory->add_service( $type_resolver );
		$tag_factory->registerTagHandler( 'param', $phpstan_tag_factory );
		$tag_factory->registerTagHandler( 'var', $phpstan_tag_factory );
		$tag_factory->registerTagHandler( 'return', $phpstan_tag_factory );
		$tag_factory->registerTagHandler( 'property', $phpstan_tag_factory );
		$tag_factory->registerTagHandler( 'property-read', $phpstan_tag_factory );
		$tag_factory->registerTagHandler( 'property-write', $phpstan_tag_factory );
		$tag_factory->registerTagHandler( 'method', $phpstan_tag_factory );
		$tag_factory->registerTagHandler( 'mixin', $phpstan_tag_factory );
		$tag_factory->registerTagHandler( 'extends', $phpstan_tag_factory );
		$tag_factory->registerTagHandler( 'implements', $phpstan_tag_factory );
		$tag_factory->registerTagHandler( 'template', $phpstan_tag_factory );
		$tag_factory->registerTagHandler( 'template-covariant', $phpstan_tag_factory );
		$tag_factory->registerTagHandler( 'template-extends', $phpstan_tag_factory );
		$tag_factory->registerTagHandler( 'template-implements', $phpstan_tag_factory );
		$tag_factory->registerTagHandler( 'throws', $phpstan_tag_factory );

		return $tag_factory;
	}

	/**
	 * Create a tag from a tag line string.
	 *
	 * @param string           $tagLine The tag line to parse.
	 * @param TypeContext|null $context The type context for resolving FQSENs.
	 *
	 * @return Tag
	 */
	public function create( string $tagLine, ?TypeContext $context = null ): Tag {
		if ( ! $context ) {
			$context = new TypeContext( '' );
		}

		[ $tag_name, $tag_body ] = $this->extract_tag_parts( $tagLine );

		return $this->create_tag( trim( $tag_body ), $tag_name, $context );
	}

	/**
	 * @param mixed $value
	 */
	public function addParameter( string $name, $value ): void {
		$this->service_locator[ $name ] = $value;
	}

	/** {@inheritDoc} */
	public function addService( object $service ): void {
		$this->service_locator[ get_class( $service ) ] = $service;
	}

	/**
	 * Add a service to the service locator with an optional alias.
	 *
	 * @param object      $service The service to add.
	 * @param string|null $alias Optional alias for the service.
	 */
	public function add_service( object $service, ?string $alias = null ): void {
		$this->service_locator[ $alias ?? get_class( $service ) ] = $service;
	}

	/**
	 * Register a tag handler for a given tag name.
	 *
	 * @param string                        $tagName The tag name.
	 * @param class-string<Tag>|Tag|Factory $handler The handler class or factory.
	 */
	public function registerTagHandler( string $tagName, $handler ): void {
		Assert::stringNotEmpty( $tagName );
		if ( strpos( $tagName, '\\' ) !== false && $tagName[0] !== '\\' ) {
			throw new InvalidArgumentException(
				'A namespaced tag must have a leading backslash as it must be fully qualified'
			);
		}

		if ( is_object( $handler ) ) {
			Assert::isInstanceOf( $handler, Factory::class );
			$this->tag_handler_mappings[ $tagName ] = $handler;

			return;
		}

		Assert::classExists( $handler );
		Assert::implementsInterface( $handler, Tag::class );
		$this->tag_handler_mappings[ $tagName ] = $handler;
	}

	/**
	 * Extracts all components for a tag.
	 *
	 * @param string $tag_line The tag line to parse.
	 *
	 * @return string[]
	 */
	private function extract_tag_parts( string $tag_line ): array {
		$matches = [];
		if ( ! preg_match( '/^@(' . self::REGEX_TAGNAME . ')((?:[\s\(\{])\s*([^\s].*)|$)/us', $tag_line, $matches ) ) {
			throw new InvalidArgumentException(
				'The tag "' . $tag_line . '" does not seem to be wellformed, please check it for errors'
			);
		}

		return array_slice( $matches, 1 );
	}

	/**
	 * Creates a new tag object with the given name and body or returns null if the tag name was
	 * recognized but the body was invalid.
	 *
	 * @param string      $body The tag body.
	 * @param string      $name The tag name.
	 * @param TypeContext $context The type context.
	 *
	 * @return Tag
	 */
	private function create_tag( string $body, string $name, TypeContext $context ): Tag {
		$handler_class_name = $this->find_handler_class_name( $name, $context );
		$arguments          = $this->get_arguments_for_parameters_from_wiring(
			$this->fetch_parameters_for_handler_factory_method( $handler_class_name ),
			$this->get_service_locator_with_dynamic_parameters( $context, $name, $body )
		);

		if ( array_key_exists( 'tagLine', $arguments ) ) {
			$arguments['tagLine'] = sprintf( '@%s %s', $name, $body );
		}

		try {
			$callable = [ $handler_class_name, 'create' ];
			Assert::isCallable( $callable );
			/** @phpstan-var callable(string): ?Tag $callable */
			$tag = call_user_func_array( $callable, $arguments );

			return $tag ?? InvalidTag::create( $body, $name );
		} catch ( InvalidArgumentException $e ) {
			return InvalidTag::create( $body, $name )->withError( $e );
		}
	}

	/**
	 * Determines the Fully Qualified Class Name of the Factory or Tag.
	 *
	 * @param string      $tag_name The tag name.
	 * @param TypeContext $context The type context.
	 *
	 * @return class-string<Tag>|Tag|Factory
	 */
	private function find_handler_class_name( string $tag_name, TypeContext $context ) {
		$handler_class_name = Generic::class;
		if ( isset( $this->tag_handler_mappings[ $tag_name ] ) ) {
			$handler_class_name = $this->tag_handler_mappings[ $tag_name ];
		} elseif ( $this->is_annotation( $tag_name ) ) {
			$tag_name = (string) $this->fqsen_resolver->resolve( $tag_name, $context );
			if ( isset( $this->annotation_mappings[ $tag_name ] ) ) {
				$handler_class_name = $this->annotation_mappings[ $tag_name ];
			}
		}

		return $handler_class_name;
	}

	/**
	 * Retrieves the arguments that need to be passed to the Factory Method with the given Parameters.
	 *
	 * @param ReflectionParameter[] $parameters The parameters.
	 * @param mixed[]               $locator The service locator.
	 *
	 * @return mixed[] A series of values that can be passed to the Factory Method of the tag.
	 */
	private function get_arguments_for_parameters_from_wiring( array $parameters, array $locator ): array {
		$arguments = [];
		foreach ( $parameters as $parameter ) {
			$type      = $parameter->getType();
			$type_hint = null;
			if ( $type instanceof ReflectionNamedType ) {
				$type_hint = $type->getName();
				if ( $type_hint === 'self' ) {
					$declaring_class = $parameter->getDeclaringClass();
					if ( $declaring_class !== null ) {
						$type_hint = $declaring_class->getName();
					}
				}
			}

			$parameter_name = $parameter->getName();
			if ( isset( $locator[ $type_hint ?? '' ] ) ) {
				$arguments[ $parameter_name ] = $locator[ $type_hint ?? '' ];
				continue;
			}

			if ( isset( $locator[ $parameter_name ] ) ) {
				$arguments[ $parameter_name ] = $locator[ $parameter_name ];
				continue;
			}

			$arguments[ $parameter_name ] = null;
		}

		return $arguments;
	}

	/**
	 * Retrieves a series of ReflectionParameter objects for the static 'create' method of the given
	 * tag handler class name.
	 *
	 * @param class-string<Tag>|Tag|Factory $handler The handler.
	 *
	 * @return ReflectionParameter[]
	 */
	private function fetch_parameters_for_handler_factory_method( $handler ): array {
		$handler_class_name = is_object( $handler ) ? get_class( $handler ) : $handler;

		if ( ! isset( $this->tag_handler_parameter_cache[ $handler_class_name ] ) ) {
			$method_reflection                                        = new ReflectionMethod(
				$handler_class_name,
				'create'
			);
			$this->tag_handler_parameter_cache[ $handler_class_name ] = $method_reflection->getParameters();
		}

		return $this->tag_handler_parameter_cache[ $handler_class_name ];
	}

	/**
	 * Returns a copy of this class' Service Locator with added dynamic parameters,
	 * such as the tag's name, body and Context.
	 *
	 * @param TypeContext $context The Context (namespace and aliases) that may be
	 *  passed and is used to resolve FQSENs.
	 * @param string      $tag_name The name of the tag.
	 * @param string      $tag_body The body of the tag.
	 *
	 * @return mixed[]
	 */
	private function get_service_locator_with_dynamic_parameters(
		TypeContext $context,
		string $tag_name,
		string $tag_body
	): array {
		return array_merge(
			$this->service_locator,
			[
				'name'             => $tag_name,
				'body'             => $tag_body,
				TypeContext::class => $context,
			]
		);
	}

	/**
	 * Returns whether the given tag belongs to an annotation.
	 *
	 * @param string $tag_content The tag content.
	 *
	 * @return bool
	 */
	private function is_annotation( string $tag_content ): bool {
		return false;
	}
}
