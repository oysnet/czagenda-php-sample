<?php

session_start();
include_once "./init.php";

if (empty($_GET['redirect_to'])) {
	$redirect_to = '/add_event.php';
} else {
	$redirect_to = $_GET['redirect_to'];
}


if (!empty($_GET['oauth_callback_confirmed']) && $_GET['oauth_callback_confirmed'] == 'False') {
	
	die("You have not authorized the application");
	
}

try
{
	//  STEP 1:  If we do not have an OAuth token yet, go get one
	if (empty($_GET["oauth_token"]))
	{
		// get a request token
		$tokenResultParams = OAuthRequester::requestRequestToken(CZAGENDA_CONSUMER_KEY, 0, $method='GET');
		
		//  redirect to the czagenda authorization page, they will redirect back
		header("Location: " . CZAGENDA_AUTHORIZE_URL . "?oauth_callback=".urlencode(AUTH_URL . "?redirect_to=" . $redirect_to)."&oauth_token=" . $tokenResultParams['token']);
	}
	else {
		//  STEP 2:  Get an access token
		$oauthToken = $_GET["oauth_token"];
		
		$tokenResultParams = $_GET;
		
		try {
		    OAuthRequester::requestAccessToken(CZAGENDA_CONSUMER_KEY, $oauthToken, 0, 'GET', $_GET);
		}
		catch (OAuthException2 $e)
		{
			var_dump($e);
		    // Something wrong with the oauth_token.
		    // Could be:
		    // 1. Was already ok
		    // 2. We were not authorized
		    return;
		}
		
		$_SESSION['auth'] = TRUE;
		header("Location: " . $redirect_to);
		
	}
}
catch(OAuthException2 $e) {
	echo "OAuthException:  " . $e->getMessage();
	var_dump($e);
}
?>