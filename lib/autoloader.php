<?php

function __autoload($class) {
	// Explode the class namespace into pieces
	$parts = explode("\\", $class);
	
	require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . ".php");
}