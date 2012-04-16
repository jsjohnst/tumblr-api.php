<?php

namespace Tumblr\Post;

/* @class BaseClass
 * This abstract class is shared by all the different Post types
 */
abstract class BaseClass
{
	// date format string which the Tumblr API expects
	const DATE_FORMAT = "Y-m-d H:i:s T";
	
	// These are the fields present on all Post types
	protected $type;
	protected $state;
	protected $tags;
	protected $date;
	protected $markdown;
	protected $slug;
	protected $id;
	
	// when this is enabled, the __set will reject invalid attempts to update property values
	private $_enforce_set = true;
	
	// Populates this object with the fields from the provided Post object if provided
	public function __construct($post = null) {
		$this->_enforce_set = false;
		if(!is_null($post)) {
			foreach(get_object_vars($post) as $key=>$value) {
				$this->$key = $value;
			}
		}
		$this->type = end(explode("\\", strtolower(get_called_class())));
		$this->_enforce_set = true;
	}
	
	// standard getter
	public function __get($key) {
		return property_exists($this, $key) ? $this->$key : null;
	}
	
	// this setter adds logic to handle conversion of several format types
	// as well as enforces access rules on certain fields
	public function __set($key, $value) {
		switch(strtolower($key)) {
			case 'date':
				$value = gmdate(self::DATE_FORMAT, strtotime($value));
				break;
			case 'type':
				if($this->_enforce_set) throw new \Tumblr\Exception("Can not change Post type after creation.", \Tumblr\Exception::INVALID_PARAMS);
				break;
			case 'tags':
				if(is_array($value)) {
					$value = implode(", ", $value);
				}
				break;
		}
		
		if($this->_enforce_set && !property_exists($this, $key)) {
			throw new \Tumblr\Exception(sprintf("Unknown Tumblr Post field: %s", $key), \Tumblr\Exception::UNKNOWN_POST_FIELD);
		}
		
		$this->$key = $value;
	}
	
	// This method serializes the object into an options array for submitting to the API
	public function serialize() {
		$vars = array();
		// We do this because we only want the defined properties on the class
		// otherwise if we were saving a post which we retrieved from the API, the additional fields would cause it to be rejected
		foreach(get_class_vars(get_class($this)) as $key=>$value) {
			$vars[$key] = $this->$key;
		}
		return $vars;
	}
}

