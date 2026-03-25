<?php

/**
 * An abstract base class.
 */
abstract class Abstract_Base {

	/**
	 * An abstract method.
	 *
	 * @param string $value The value.
	 * @return bool The result.
	 */
	abstract public function abstract_method( $value );

	/**
	 * A concrete method in an abstract class.
	 */
	public function concrete_method() {
		return true;
	}
}

/**
 * A final class that cannot be extended.
 */
final class Final_Class {

	/**
	 * A final method that cannot be overridden.
	 */
	final public function final_method() {}

	/**
	 * A regular method in a final class.
	 */
	public function regular_method() {}
}

/**
 * A class demonstrating static properties and methods.
 */
class Static_Members {

	/**
	 * A static property.
	 *
	 * @var int
	 */
	public static $counter = 0;

	/**
	 * A static method.
	 */
	public static function increment() {
		self::$counter++;
	}

	/**
	 * A protected static method.
	 */
	protected static function reset() {
		self::$counter = 0;
	}
}

/**
 * A class demonstrating property visibility and defaults.
 */
class Property_Showcase {

	/**
	 * A public property.
	 *
	 * @var string
	 */
	public $pub_prop = 'hello';

	/**
	 * A protected property.
	 *
	 * @var array
	 */
	protected $prot_prop = [];

	/**
	 * A private property.
	 *
	 * @var int
	 */
	private $priv_prop = 42;

	/**
	 * A property with an array default.
	 *
	 * @var array
	 */
	public $arr_default = array( 'a', 'b', 'c' );

	/**
	 * A property with a null default.
	 *
	 * @var mixed
	 */
	public $null_default = null;

	/**
	 * A property with a constant default.
	 *
	 * @var bool
	 */
	public $bool_default = true;
}

/**
 * A class that uses a trait.
 */
class Trait_User {
	public function own_method() {}
}

/**
 * A class with closures and arrow functions.
 */
class Closure_Class {

	/**
	 * A method that uses closures.
	 */
	public function with_closures() {
		$fn    = function( $x ) { return $x * 2; };
		$arrow = fn( $x ) => $x * 2;

		return $fn( 1 ) + $arrow( 2 );
	}
}
