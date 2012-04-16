<?php

namespace Tumblr\API;

/* @class Authentication
 * Abstract base class which sets up the needed pieces to handle OAuth authentication
 */
abstract class Authentication extends \Singleton
{
	// OAuth request endpoints
	const REQUEST_TOKEN_URL = "http://www.tumblr.com/oauth/request_token";
	const AUTHORIZE_URL = "http://www.tumblr.com/oauth/authorize?oauth_token=%s";
	const ACCESS_TOKEN_URL = "http://www.tumblr.com/oauth/access_token";
	
	// API configuration
	protected $base_hostname;
	protected $api_key;
	protected $api_secret;
	protected $token;
	protected $token_secret;
	
	// PECL OAuth resource handle
	protected $oauth;
	
	// Sets the oauth_token / oauth_token_secret
	protected function setToken($token, $token_secret) {
		if(!($this->oauth instanceOf \OAuth)) {
			throw new \Tumblr\Exception("API not configured with hostname, key, and/or secret.", \Tumblr\Exception::NOT_CONFIGURED);
		}
		
		$this->token = $token;
		$this->token_secret = $token_secret;
		
		$this->oauth->setToken($this->token, $this->token_secret);
	}
	
	// This method sets the API configuration
	// api_key / api_secret are option in case you want to change the base_hostname mid-request
	// so they don't have to be provided every time
	public static function configure($base_hostname, $api_key = null, $api_secret = null) {
		$instance = self::getInstance();
		$instance->base_hostname = $base_hostname;
		
		if(!is_null($api_key) && !is_null($api_secret)) {
			$instance->api_key = $api_key;
			$instance->api_secret = $api_secret;
			$instance->oauth = new \OAuth($instance->api_key, $instance->api_secret);
			$instance->oauth->enableDebug();
		}
	}
	
	// This method handles authenticating the specific calling user user for the API
	// This method is polymorphic and actually handles multiple invocation use cases
	// Invocation #1: When not authenticated at all
	//		::authenticate(); -> { token: "rtoken", token_secret: "rtoken_secret", authorize_url: "" }
	// Invocation #2: Following the user visiting the authorization URL and receiving the callback
	//		::authenticate(rtoken, rtoken_secret, verifier); -> { token: "ptoken", token_secret: "ptoken_secret" }
	// Invocation #3: Once you have the permanent tokens from #2, you can save them and call like this from then on
	//		::authenticate(ptoken, ptoken_secret);
	public static function authenticate($token = null, $token_secret = null, $verifier = null) {
		$instance = self::getInstance();
		
		if(!($instance->oauth instanceOf \OAuth)) {
			throw new \Tumblr\Exception("API not configured with hostname, key, and/or secret.", \Tumblr\Exception::NOT_CONFIGURED);
		}
		
		$ret = new \stdClass();
		$ret->token = $token;
		$ret->token_secret = $token_secret;
		
		if(is_null($token) || is_null($token_secret)) {
			// Invocation path #1
			$rt_info = $instance->oauth->getRequestToken(self::REQUEST_TOKEN_URL);
			
			$ret->token = $rt_info["oauth_token"];
			$ret->token_secret = $rt_info["oauth_token_secret"];
			$ret->authorize_url = sprintf(self::AUTHORIZE_URL, $rt_info["oauth_token"]);
		} else {
			// Invocation path #2 and #3 share this branch, but only #2 goes into the verifier phase below
			$instance->setToken($token, $token_secret);
			
			if(!is_null($verifier)) {
				// Invocation path #2
				$at_info = $instance->oauth->getAccessToken(self::ACCESS_TOKEN_URL, "", $verifier);

				$instance->setToken($at_info["oauth_token"], $at_info["oauth_token_secret"]);
				
				$ret->token = $at_info["oauth_token"];
				$ret->token_secret = $at_info["oauth_token_secret"];
			}
		}
		
		return $ret;
	}
}