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
			$class_name = get_called_class();
			self::$singleton_instance = new $class_name();
		}
		return self::$singleton_instance;
	}
}