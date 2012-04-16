<?php

namespace Tumblr;

/* @class API
 * This class exposes the Tumblr API w/ convenience methods for all the API endpoints
 */
class API extends \Tumblr\API\Request
{
	public static function getInfo() {
		$instance = self::getInstance();
		return $instance->request("GET", "/info", array("api_key"=>$instance->api_key), false);
	}
	
	public static function getAvatar($size = null) {
		$path = "/avatar";
		
		if(!is_null($size)) {
			$path .= "/" . $size;
		}
		
		$instance = self::getInstance();
		return $instance->request("GET", $path, null, false);
	}
	
	public static function getFollowers($limit = null, $offset = null) {
		$params = array();
		
		if(!is_null($limit)) {
			$params["limit"] = $limit;
		}
		
		if(!is_null($offset)) {
			$params["offset"] = $offset;
		}
		
		$instance = self::getInstance();
		return $instance->request("GET", "/followers", $params);
	}
	
	public static function getPostByID($id) {
		return self::getPosts(array("id"=>$id));
	}
	
	public static function getPostsByType($type, $limit = null, $offset = null) {
		return self::getPosts(array(
			"type" => $type,
			"limit" => $limit,
			"offset" => $offset
		));
	}
	
	public static function getPostsByTag($tag, $limit = null, $offset = null) {
		return self::getPosts(array(
			"tag" => $tag,
			"limit" => $limit,
			"offset" => $offset
		));
	}
	
	public static function getPosts($options = array()) {
		if(!is_array($options)) {
			throw new \Tumblr\Exception("Invalid parameter passed to \\Tumblr\\API::getPosts().", \Tumblr\Exception::INVALID_PARAMS);
		}
		
		$instance = self::getInstance();
		$options["api_key"] = $instance->api_key;
		
		$path = "/posts";
		if(isset($options["type"])) {
			$path .= "/" . $options["type"];
			unset($options["type"]);
		}
		
		$decorated = true;
		if(isset($options["_decorated"])) {
			$decorated = (bool) $options["_decorated"];
			unset($options["_decorated"]);
		}
		
		$obj = $instance->request("GET", $path, $options, false);
		
		if($obj !== FALSE && $decorated) {
			$obj->raw_posts = $obj->posts;
			$obj->posts = array();
			foreach($obj->raw_posts as $key=>$post) {
				$class_name = "Tumblr\\Post\\" . $post->type;
				$obj->posts[$key] = new $class_name($post);
			}
		}
		
		return $obj;
	}
	
	public static function getQueuedPosts() {
		$instance = self::getInstance();
		return $instance->request("GET", "/posts/queue");
	}
	
	public static function getDraftPosts() {
		$instance = self::getInstance();
		return $instance->request("GET", "/posts/draft");
	}
	
	public static function getSubmissionPosts() {
		$instance = self::getInstance();
		return $instance->request("GET", "/posts/submission");
	}
	
	public static function reblogPost($id, $reblog_key, $comment = "") {
		$options = array(
			"id" => $id,
			"reblog_key" => $reblog_key,
			"comment" => $comment
		);
		
		$instance = self::getInstance();
		return $instance->request("POST", "/post/reblog", $options);
	}
	
	public static function deletePost($id) {	
		$instance = self::getInstance();
		return $instance->request("POST", "/post/delete", array( "id" => $id ));
	}

	public static function submitPost(\Tumblr\Post\BaseClass $post, $tweet = null) {
		$options = array();
		
		foreach($post->serialize() as $key=>$value) {
			if(!is_null($value)) {
				$options[$key] = $value;
			}
		}
		
		if(!is_null($tweet)) {
			$options["tweet"] = $tweet;
		}
		
		$path = isset($options["id"]) ? "/post/edit" : "/post";
		
		$instance = self::getInstance();
		return $instance->request("POST", $path, $options);
	}
}
