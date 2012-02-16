<?php
	
	// retrieve current server configuration
	$pageURL = 'http';
	if (! empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	 $pageURL .= "://";
	 if ($_SERVER["SERVER_PORT"] != "80") {
	  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
	 } else {
	  $pageURL .= $_SERVER["SERVER_NAME"];
	 }
	$pageURL.='/authorize.php';
	define("AUTH_URL", $pageURL);
	
	include_once "../libs/oauth-php/OAuthStore.php";
	include_once "../libs/oauth-php/OAuthRequester.php";
	include_once "./settings.php";
	
	define("CZAGENDA_OAUTH_HOST", "http://api-master.czagenda.oxys.net/api");
	define("CZAGENDA_ACCESS_TOKEN_URL", CZAGENDA_OAUTH_HOST . "/oauth-token/_access-token");
	define("CZAGENDA_REQUEST_TOKEN_URL", CZAGENDA_OAUTH_HOST . "/oauth-token/_request-token");
	
	define("CZAGENDA_AUTHORIZE_URL", "http://auth.czagenda.oxys.net/oauth/authorize/");
	
	define('OAUTH_TMP_DIR',function_exists('sys_get_temp_dir')?sys_get_temp_dir():realpath($_ENV["TMP"]));
	
	// Init the OAuthStore
	$options=array('consumer_key'=>CZAGENDA_CONSUMER_KEY,'consumer_secret'=>CZAGENDA_CONSUMER_SECRET,'server_uri'=>CZAGENDA_OAUTH_HOST,'request_token_uri'=>CZAGENDA_REQUEST_TOKEN_URL,'authorize_uri'=>CZAGENDA_AUTHORIZE_URL,'access_token_uri'=>CZAGENDA_ACCESS_TOKEN_URL);
	OAuthStore::instance("Session",$options);
?>