<?php
/**
 * Methods, constants, and variables common to loggers and writers,
 * and Singleton boilerplate.
 */
namespace Mint;

abstract class Singleton {

	protected static $_instance = array();

	/**
	 * Prevent direct object creation
	 */
	final private function  __construct() { }

	/**
	 * Prevent object cloning
	 */
	final private function  __clone() { }

	/**
	 * Returns new or existing singleton instance
	 *
	 * @return obj self::$_instance
	 */
	final public static function get_instance() {
		/*
		 * If you extend this class, self::$_instance will be part of the base class.
		 * In the sinlgeton pattern, if you have multiple classes extending this class,
		 * self::$_instance will be overwritten with the most recent class instance
		 * that was instantiated.  Thanks to late static binding we use get_called_class()
		 * to grab the caller's class name, and store a key=>value pair for each
		 * classname=>instance in self::$_instance for each subclass.
		 */
		$class = get_called_class();
		if ( ! isset( self::$_instance[$class] ) ) {
			self::$_instance[$class] = new $class();

			// Run's the class's _init() method, where the class can hook into actions and filters, and do any other initialization it needs
			self::$_instance[$class]->_init();
		}
		return self::$_instance[$class];
	}

}

//EOF