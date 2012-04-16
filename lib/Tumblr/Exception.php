<?php

namespace Tumblr;

class Exception extends \Exception
{
	const NOT_CONFIGURED = 1;
	const INVALID_JSON_RESPONSE = 2;
	const API_RETURNED_ERROR = 3;
	const INVALID_PARAMS = 4;
	const UNKNOWN_POST_FIELD = 5;
}