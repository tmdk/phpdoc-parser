<?php
/**
 * Method_Call_Factory
 *
 * @package WP_Parser\Factory
 */

namespace WP_Parser\Factory;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use WP_Parser\Formatter\Templated_String;
use WP_Parser\Formatter\Templated_String_Printer;
use WP_Parser\Reflection\Class_;
use WP_Parser\Reflection\Method_Call;
use WP_Parser\Reflection\Name;
use WP_Parser\Scope;

/**
 * Class Method_Call_Factory
 */
class Method_Call_Factory {

	/**
	 * Mapping of global variable names to their class names.
	 */
	public const GLOBAL_VAR_MAPPING = [
		'authordata'          => 'WP_User',
		'custom_background'   => 'Custom_Background',
		'custom_image_header' => 'Custom_Image_Header',
		'phpmailer'           => 'PHPMailer',
		'post'                => 'WP_Post',
		'userdata'            => 'WP_User',
		'wp'                  => 'WP',
		'wp_admin_bar'        => 'WP_Admin_Bar',
		'wp_customize'        => 'WP_Customize_Manager',
		'wp_embed'            => 'WP_Embed',
		'wp_filesystem'       => 'WP_Filesystem',
		'wp_hasher'           => 'PasswordHash',
		'wp_json'             => 'Services_JSON',
		'wp_list_table'       => 'WP_List_Table',
		'wp_locale'           => 'WP_Locale',
		'wp_object_cache'     => 'WP_Object_Cache',
		'wp_query'            => 'WP_Query',
		'wp_rewrite'          => 'WP_Rewrite',
		'wp_roles'            => 'WP_Roles',
		'wp_scripts'          => 'WP_Scripts',
		'wp_styles'           => 'WP_Styles',
		'wp_the_query'        => 'WP_Query',
		'wp_widget_factory'   => 'WP_Widget_Factory',
		'wp_xmlrpc_server'    => 'wp_xmlrpc_server',
		'wpdb'                => 'wpdb',
	];

	/**
	 * Mapping of function calls to their return type class names.
	 */
	public const FUNCTION_RETURN_MAPPING = [
		'get_current_screen()' => 'WP_Screen',
		'_get_list_table()'    => 'WP_List_Table',
		'wp_get_theme()'       => 'WP_Theme',
	];

	private Templated_String_Printer $printer;

	public function __construct() {
		$this->printer = new Templated_String_Printer();
	}

	public function create( New_|MethodCall|StaticCall $node, Scope $scope ): ?Method_Call {
		$class = $scope->closest( Class_::class );

		$method_call = match ( true ) {
			$node instanceof New_ => $this->from_new( $node, $class ),
			$node instanceof MethodCall => $this->from_method_call( $node, $class ),
			$node instanceof StaticCall => $this->from_static_call( $node, $class ),
			default => null
		};

		if ( $method_call === null ) {
			return null;
		}

		$method_call->set_line( $node->getStartLine() );
		$method_call->set_end_line( $node->getEndLine() );

		return $method_call;
	}

	private function from_new( New_ $node, Class_ $class = null ): ?Method_Call {
		$class_name = $this->get_class_for_new( $node->class, $class );

		if ( $class_name === null ) {
			return null;
		}

		$method_call = new Method_Call();
		$method_call->set_name( '__construct' );
		$method_call->set_class( $class_name );

		return $method_call;
	}

	private function get_class_for_new( Node $node, Class_ $class = null ): string|Templated_String|null {
		if ( $node instanceof Node\Stmt\Class_ ) {
			$extends = $node->extends;

			return $extends ? $this->printer->print_name( $extends ) . '@anonymous' : 'class@anonymous';
		}

		return $this->get_class( $node, $class );
	}

	private function from_method_call( MethodCall $node, Class_ $class = null ): Method_Call {
		$method_call = new Method_Call();

		$method_call->set_name( $this->get_name( $node->name ) );
		$method_call->set_class( $this->get_class( $node->var, $class ) );
		$method_call->set_static( false );

		return $method_call;
	}

	private function from_static_call( StaticCall $node, Class_ $class = null ): Method_Call {
		$method_call = new Method_Call();
		$method_call->set_name( $this->get_name( $node->name ) );
		$method_call->set_class( $this->get_class( $node->class, $class ) );
		$method_call->set_static( true );

		return $method_call;
	}

	private function get_name( Node $node ): string {
		return match ( true ) {
			$node instanceof Node\Identifier => $node->toString(),
			$node instanceof Node\Expr => (string) $this->printer->print_node( $node ),
			default => assert( false, new \InvalidArgumentException( 'Unexpected node type: ' . $node::class ) )
		};
	}

	private function get_class( Node $node, Class_ $class = null ): string|Templated_String {
		if ( $this->is_class_reference( $node ) && $class ) {
			$class_name = $this->resolve_special_classname( $node, $class );
		} elseif ( $this->is_global_var( $node ) ) {
			$class_name = $this->get_class_for_global( $node );
		} elseif ( $node instanceof Node\Name ) {
			$class_name = $this->printer->print_name( $node );
		} elseif ( $node instanceof Node\Expr ) {
			$class_name = $this->printer->print_expr( $node );
		} elseif ( $node instanceof Node\Stmt\Class_ ) {
			$class_name = $node->name->toString();
		} else {
			$exception  = new \InvalidArgumentException( 'Unexpected node type: ' . $node::class );
			$class_name = assert( false, $exception );
		}

		if ( array_key_exists( (string) $class_name, self::FUNCTION_RETURN_MAPPING ) ) {
			$class_name = self::FUNCTION_RETURN_MAPPING[ (string) $class_name ];

			return Templated_String::from_name( new Name( $class_name, fully_qualified: true ) );
		}

		return $class_name;
	}

	private function is_class_reference( Node $node ): bool {
		return ( $node instanceof Node\Name && $node->isSpecialClassName() )
			|| ( $node instanceof Node\Expr\Variable && $node->name === 'this' );
	}

	private function resolve_special_classname( Node $node, Class_ $class ): string|Templated_String {
		assert( $node instanceof Node\Name || $node instanceof Node\Expr\Variable );

		return match ( $node->name ) {
			'parent' => $class->get_extends(),
			'self', 'this' => Templated_String::from_name( $class->get_fully_qualified_name() ),
			default => $node->toString(),
		};
	}

	/**
	 * Check if a node is a known global variable.
	 *
	 * @param Node $node The node to check.
	 *
	 * @return bool True if the node is a known global variable.
	 */
	private function is_global_var( Node $node ): bool {
		return $node instanceof Node\Expr\Variable
			&& is_string( $node->name )
			&& array_key_exists( $node->name, self::GLOBAL_VAR_MAPPING );
	}

	/**
	 * Get the class name for a global variable, preserving the $ prefix.
	 *
	 * @param Node $node The variable node.
	 *
	 * @return string The variable name with $ prefix.
	 */
	private function get_class_for_global( Node $node ): string {
		assert( $node instanceof Node\Expr\Variable );

		return '$' . $node->name;
	}

}
