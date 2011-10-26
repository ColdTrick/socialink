<?php
/*
 * @author Kilian Marjew (kilian@marjew.nl)
 * @url http://genusapis.marjew.nl/
 */

require_once('GenusApis.php');
session_start();
header('Content-Type: text/html; charset=utf-8');

// Url of this script.
define("SCRIPT_URL", "enter script location here");

//Hyves API version to use:
define("HA_VERSION", "1.2.1");

// catch the possible exceptions
try {

// Declare oauth_consumer
$oOAuthConsumer = new OAuthConsumer("your api key", " your api secret");

// Init GenusApis
$oGenusApis = new GenusApis($oOAuthConsumer, HA_VERSION);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "default";

switch($action) {
	case 'default':
		// Default page
		echo "<a href=\"".SCRIPT_URL."?action=authorize\">Authorize</a><br />";
		break;
	case 'authorize':
		// Create request token and authorize it (causes redirect).
		$oRequestToken = $oGenusApis->retrieveRequesttoken(array("friends.get", "users.get", "albums.getByUser"));
		$_SESSION['requesttoken_'.$oRequestToken->getKey()] = serialize($oRequestToken);
		$oGenusApis->redirectToAuthorizeUrl($oRequestToken, SCRIPT_URL."?action=authorized");
		break;
	case 'authorized':
		// Authorized page, hyves will redirect to this page (callback).
		$oauth_token = $_REQUEST['oauth_token'];
		$oRequestToken = getRequestTokenFromSession($oauth_token);
		$oAccessToken = $oGenusApis->retrieveAccesstoken($oRequestToken);
		$local_token = md5($oAccessToken->getKey());
		$_SESSION['localtoken_'.$local_token] = serialize($oAccessToken);
		$overviewUrl = SCRIPT_URL . "?action=overview&local_token=" . $local_token;
		header("Location: " . $overviewUrl);
		break;
	case 'overview':
		$local_token = $_REQUEST['local_token'];
		echo "<a href=\"".SCRIPT_URL."?action=friends&local_token=".$local_token."\">friends.get</a><br />";
		echo "<a href=\"".SCRIPT_URL."?action=users&local_token=".$local_token."\">users.get</a><br />";
		echo "<a href=\"".SCRIPT_URL."?action=usersresponsefields&local_token=".$local_token."\">users.get with ha_responsefields</a><br />";
		echo "<a href=\"".SCRIPT_URL."?action=mediaalbums&local_token=".$local_token."\">albums.getByUser</a><br />";
		break;		
	case 'friends':
		// Example method friends.get
		$local_token = $_REQUEST['local_token'];
		$oAccessToken = getAccessTokenFromSession($local_token);
		$oXml = $oGenusApis->doMethod("friends.get", array(), $oAccessToken);
		echo "<pre>";
		print_r($oXml);
		echo "</pre>";
		break;
	case 'users':
		// Example method users.get with loggedin userid
		$local_token = $_REQUEST['local_token'];
		$oAccessToken = getAccessTokenFromSession($local_token);
		$oXml = $oGenusApis->doMethod("users.get", array("userid" => $oAccessToken->getUserid()), $oAccessToken);
		echo "<pre>";
		print_r($oXml);
		echo "</pre>";
		break;
	case 'usersresponsefields':
		// Example method users.get with loggedin userid and responsefields
		$local_token = $_REQUEST['local_token'];
		$oAccessToken = getAccessTokenFromSession($local_token);
		$oXml = $oGenusApis->doMethod("users.get", array("userid" => $oAccessToken->getUserid(), "ha_responsefields" => "profilepicture,whitespaces"), $oAccessToken);
		echo "<pre>";
		print_r($oXml);
		echo "</pre>";
		break;
	case 'mediaalbums':
		// Example method media.getAlbums with loggedin userid
		$local_token = $_REQUEST['local_token'];
		$oAccessToken = getAccessTokenFromSession($local_token);
		$oXml = $oGenusApis->doMethod("albums.getByUser", array("userid" => $oAccessToken->getUserid()), $oAccessToken);
		echo "<pre>";
		print_r($oXml);
		echo "</pre>";
		break;
	case 'invalidsession':
		echo "Error! The session is expired.";
		break;
}

} 
catch(GeneralException $e)
{
	echo "General Exception occured:<br>Code: ".$e->getCode()."<br>Message: ".$e->getMessage();
}
catch(HyvesApiException $e)
{
	echo "HyvesApi Exception occured:<br>Code: ".$e->getCode()."<br>Message: ".$e->getMessage();
}



// example storage for requesttoken
function getRequestTokenFromSession($oauth_token) {
	if (!isset($_SESSION['requesttoken_'.$oauth_token])) {
		header("Location: ".SCRIPT_URL."?action=invalidsession");
	}
	return unserialize($_SESSION['requesttoken_'.$oauth_token]);
}

// example storage for accesstoken
function getAccessTokenFromSession($local_token) {
	if (!isset($_SESSION['localtoken_'.$local_token])) {
		header("Location: ".SCRIPT_URL."?action=invalidsession");
	}
	return unserialize($_SESSION['localtoken_'.$local_token]);
}
?>
