<?php

namespace Tumblr\API;

/* @class Request
 * Abstract base class which extends the Authentication base class with API request helpers
 */
abstract class Request extends Authentication
{
	// API Request endpoint
	const API_BASE = "http://api.tumblr.com/v2/blog/%s%s";
		
	// This method is used for API calls which don't need OAuth signing
	private function makeCurlRequest($method, $url, $params = array(), $headers = array()) {
		$ch = curl_init($url);
		
		switch(strtoupper($method)) {
			case 'GET':
				curl_setopt($ch, CURLOPT_HTTPGET, 1);
				if(!empty($params)) {
					curl_setopt($ch, CURLOPT_URL, $url . "?" . http_build_query($params));
				}
				break;
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, 1);
				if(!empty($params)) {
					curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
				}
				break;
			default:
				throw new \Tumblr\Exception(sprintf("Unsupported HTTP Method: %s", $method), \Tumblr\Exception::INVALID_PARAMS);
				break;		
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		
		// Curl requires an array of individual header lines rather than key=>value pairs
		if(!empty($headers)) {
			$curl_headers = array();
			foreach($headers as $key=>$value) {
				$curl_headers[] = $key . ": " . $value;
			}
			curl_setopt($ch, CURLOPT_HTTPHEADERS, $curl_headers);
		}
		
		$response = curl_exec($ch);
		
		// Would much prefer to limit to 200/201, but sadly the Avatar endpoint returns a 301 so making this less restrictive
		$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($response_code >= 200 && $response_code < 400) {
			return $response;
		}
		
		return FALSE;
	}
	
	// This method is used to make API requests that need OAuth signing
	private function makeOAuthRequest($method, $url, $params = array(), $headers = array()) {
		switch(strtoupper($method)) {
			case 'GET':
				$method = OAUTH_HTTP_METHOD_GET;
				break;
			case 'POST':
				$method = OAUTH_HTTP_METHOD_POST;
				break;
			default:
				throw new \Tumblr\Exception(sprintf("Unsupported HTTP Method: %s", $method), \Tumblr\Exception::INVALID_PARAMS);
				break;
		}
		
		if($this->oauth->fetch($url, $params, $method, $headers)) {
			return $this->oauth->getLastResponse();
		}
		
		return FALSE;
	}
	
	// This is the method you use to actually make API requests
	// generally you shouldn't use this directly, but rather one of the helper methods exposed by Tumblr\API
	public function request($method, $path, $params = array(), $oauth = true) {
		if(!$this->base_hostname || !$this->api_key || !$this->api_secret) {
			throw new \Tumblr\Exception("API not configured with hostname, key, and/or secret.", \Tumblr\Exception::NOT_CONFIGURED);
		}
		
		$url = sprintf(self::API_BASE, $this->base_hostname, $path);
		
		if($oauth) {
			$response = $this->makeOAuthRequest($method, $url, $params);
		} else {
			$response = $this->makeCurlRequest($method, $url, $params);
		}
		
		if($response !== FALSE) {
			$json = json_decode($response);
			
			if(!$json || !property_exists($json, "meta")) {
				throw new \Tumblr\Exception("API didn't return a valid JSON response.", \Tumblr\Exception::INVALID_JSON_RESPONSE);
			}
			
			// Yes, this is duplicative, but errors can return a 200 response but with an error flag set on the meta status
			if($json->meta->status < 200 && $json->meta->status >= 400) {
				throw new \Tumblr\Exception(sprintf("API returned HTTP Code #%d -- %s", $json->meta->status, $json->meta->msg), \Tumblr\Exception::API_RETURNED_ERROR);
			}
			
			return $json->response;
		}
		
		return FALSE;
	}
}