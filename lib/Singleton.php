<?php

/* @class Singleton
 * Abstract base class used to implement the Singleton pattern
 */
abstract class Singleton
{
	private static $singleton_instance = null;
	private function __construct() {}
	
	protected static function getInstance() {
		if(is_null(self::$singleton_instance)) {
			// without using LSB, we would get the wrong class name here (aka Singleton, rather than the derived class)
			$class_name = get_called_class();
			self::$singleton_instance = new $class_name();
		}
		return self::$singleton_instance;
	}
}