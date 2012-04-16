<?php

require("defaults.php");
require("lib/autoloader.php");

Tumblr\API::configure(BASE_HOSTNAME, API_KEY, API_SECRET);
Tumblr\API::authenticate(TOKEN, TOKEN_SECRET);

var_dump(Tumblr\API::getPosts());

$cls = new Tumblr\Post\Audio();
$cls->tags = array("foo","bar");
$cls->date = "today";
$cls->external_url = "http://localhost/foo1.mp3";

var_dump($cls->serialize());

//var_dump(Tumblr\API::submitPost($cls, "off"));

