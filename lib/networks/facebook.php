<?php
/**
 * All Facebook releated helper functions are bundeld here
 */

define("FACEBOOK_OAUTH_BASE_URL", "https://www.facebook.com/dialog/oauth?client_id=");

/**
 * Check if the user is connected to a Facebook account
 *
 * @param int $user_guid the user_guid to check
 *
 * @return bool|Facebook\FacebookSession
 */
function socialink_facebook_is_connected($user_guid = 0) {
	
	$user_guid = sanitise_int($user_guid, false);
	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	
	if (empty($user_guid)) {
		return false;
	}
	
	if (!socialink_facebook_available()) {
		return false;
	}
	
	$token = elgg_get_plugin_user_setting("facebook_access_token", $user_guid, "socialink");
	if (empty($token)) {
		return false;
	}
	
	return new Facebook\FacebookSession($token);
}

/**
 * Get the URL to authorize Facebook
 *
 * @param string $callback the callback URL
 *
 * @return bool|string
 */
function socialink_facebook_get_authorize_url($callback) {
	
	if (empty($callback)) {
		return false;
	}

	if (!socialink_facebook_available()) {
		return false;
	}
	
	$_SESSION["socialink_facebook"] = array(
		"callback" => $callback
	);
	
	$redirect = new Facebook\FacebookRedirectLoginHelper($callback);
	
	$scope = array("offline_access", "publish_stream", "user_about_me", "user_location", "email");
	return $redirect->getLoginUrl($scope);
}

/**
 * Get a Facebook access token
 *
 * @return bool|string
 */
function socialink_facebook_get_access_token() {
	
	if (!socialink_facebook_available()) {
		return false;
	}
	
	if (isset($_SESSION["socialink_facebook"]) && isset($_SESSION["socialink_facebook"]["callback"])) {
		
		$callback = $_SESSION["socialink_facebook"]["callback"];
		$redirect = new Facebook\FacebookRedirectLoginHelper($callback);
		$session = $redirect->getSessionFromRedirect();
		
		if (empty($session)) {
			return false;
		}
		
		unset($_SESSION["socialink_facebook"]);
		
		$token = $session->getAccessToken();
		if (empty($token)) {
			return false;
		}
		
		$token->extend();
		
		return (string) $token;
	} elseif (isset($_SESSION["socialink_token"])) {
		return $_SESSION["socialink_token"];
	}
	
	return false;
}

/**
 * Authorize a Facebook account
 *
 * @param int $user_guid the user_guid to authorize
 *
 * @return bool
 */
function socialink_facebook_authorize($user_guid = 0) {
	
	$user_guid = sanitise_int($user_guid, false);
	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	
	if (empty($user_guid)) {
		return false;
	}
	
	$token = socialink_facebook_get_access_token();
	if (empty($token)) {
		return false;
	}
	
	$user_id = socialink_facebook_get_user_id_from_access_token($token);
	if (empty($user_id)) {
		return false;
	}
	
	// only one user per tokens
	$params = array(
		"type" => "user",
		"limit" => false,
		"site_guids" => false,
		"plugin_id" => "socialink",
		"plugin_user_setting_name_value_pairs" => array(
			"facebook_user_id" => $user_id
		)
	);
	
	// find hidden users (just created)
	$access_status = access_get_show_hidden_status();
	access_show_hidden_entities(true);
	
	$users = new ElggBatch("elgg_get_entities_from_plugin_user_settings", $params);
	foreach ($users as $user) {
		// revoke access
		elgg_unset_plugin_user_setting("facebook_access_token", $user->getGUID(), "socialink");
		elgg_unset_plugin_user_setting("facebook_user_id", $user->getGUID(), "socialink");
	}
	
	// restore hidden status
	access_show_hidden_entities($access_status);
	
	// register user's access tokens
	elgg_set_plugin_user_setting("facebook_access_token", $token, $user_guid, "socialink");
	elgg_set_plugin_user_setting("facebook_user_id", $user_id, $user_guid, "socialink");
	
	return true;
}

/**
 * Remove the Facebook connection
 *
 * @param int $user_guid the user_guid to remove to connection for
 *
 * @return bool
 */
function socialink_facebook_remove_connection($user_guid = 0) {
	
	$user_guid = sanitise_int($user_guid, false);
	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	
	if (empty($user_guid)) {
		return false;
	}
	
	if (!socialink_facebook_is_connected($user_guid)) {
		return false;
	}
	
	// remove plugin settings
	elgg_unset_plugin_user_setting("facebook_access_token", $user_guid, "socialink");
	elgg_unset_plugin_user_setting("facebook_user_id", $user_guid, "socialink");
	
	return true;
}

/**
 * Post a message on Facebook
 *
 * @param string $message   the message to post
 * @param int    $user_guid the user who posts
 *
 * @return bool
 */
function socialink_facebook_post_message($message, $user_guid = 0) {
	
	if (empty($message)) {
		return false;
	}
	
	$user_guid = sanitise_int($user_guid, false);
	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	
	if (empty($user_guid)) {
		return false;
	}
	
	$session = socialink_facebook_is_connected($user_guid);
	if (empty($session)) {
		return false;
	}
	
	$request = new FaceBook\FacebookRequest($session, "POST", "/me/feed", array("message" => $message));
	
	// set correct proxy settings (if needed)
	$curl_http_client = socialink_facebook_get_curl_http_client();
	$request->setHttpClientHandler($curl_http_client);
	
	try {
		$api_result = $request->execute()->getGraphObject(Facebook\GraphUser::className());
	} catch(Exception $e) {}
	
	if (empty($api_result)) {
		return false;
	}
	
	return true;
}

/**
 * Get profile information from Facebook
 *
 * @param int $user_guid the user_guid to get the information from
 *
 * @return bool|Facebook\GraphUser
 */
function socialink_facebook_get_profile_information($user_guid = 0) {
	
	$user_guid = sanitise_int($user_guid, false);
	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	
	if (empty($user_guid)) {
		return false;
	}
	
	$session = socialink_facebook_is_connected($user_guid);
	if (empty($session)) {
		return false;
	}
	
	$request = new FaceBook\FacebookRequest($session, "GET", "/me");
	
	// set correct proxy settings (if needed)
	$curl_http_client = socialink_facebook_get_curl_http_client();
	$request->setHttpClientHandler($curl_http_client);
	
	try {
		$api_result = $request->execute()->getGraphObject(Facebook\GraphUser::className());
	} catch(Exception $e) {}
	
	if (empty($api_result)) {
		return false;
	}
	
	return $api_result;
}

/**
 * Update the Elgg profile with Facebook data
 *
 * @param int $user_guid the user_guid of the profile to update
 *
 * @return void
 */
function socialink_facebook_sync_profile_metadata($user_guid = 0) {
	
	$user_guid = sanitise_int($user_guid, false);
	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	
	if (empty($user_guid)) {
		return;
	}
	
	$user = get_user($user_guid);
	if (empty($user) || !socialink_facebook_is_connected($user_guid)) {
		return;
	}
	
	// does the user allow sync
	if (elgg_get_plugin_user_setting("facebook_sync_allow", $user->getGUID(), "socialink") === "no") {
		return;
	}
	
	// get configured fields and network fields
	$profile_fields = elgg_get_config("profile_fields");
	$configured_fields = socialink_get_configured_network_fields("facebook");
	$network_fields = socialink_get_network_fields("facebook");
	
	if (empty($profile_fields) || empty($configured_fields) || empty($network_fields)) {
		return;
	}
	
	// ask the api for all fields
	$api_result = socialink_facebook_get_profile_information($user->getGUID());
	if (!empty($api_result)) {
		
		// check settings for each field
		foreach ($configured_fields as $setting_name => $profile_field) {
			$setting = "facebook_sync_" . $setting_name;
			
			if (elgg_get_plugin_user_setting($setting, $user->getGUID(), "socialink") === "no") {
				continue;
			}
			
			$api_setting = $network_fields[$setting_name];
			
			$temp_result = call_user_func(array($api_result, $api_setting));
			
			// are we dealing with a tags profile field type
			$field_type = elgg_extract($profile_field, $profile_fields);
			if ($field_type === "tags") {
				$temp_result = string_to_tag_array($temp_result);
			}
			
			// check if the user has this metadata field, to get access id
			$params = array(
				"guid" => $user->getGUID(),
				"metadata_name" => $profile_field,
				"limit" => false
			);
			
			if ($metadata = elgg_get_metadata($params)) {
				if (is_array($metadata)) {
					$access_id = $metadata[0]->access_id;
				} else {
					$access_id = $metadata->access_id;
				}
			} else {
				$access_id = get_default_access($user);
			}
			
			// remove metadata to set new values
			elgg_delete_metadata($params);
			
			// make new metadata field
			if (empty($temp_result)) {
				continue;
			}
			
			if (is_array($temp_result)) {
				foreach ($temp_result as $index => $temp_value) {
					if ($index > 0) {
						$multiple = true;
					} else {
						$multiple = false;
					}
					
					create_metadata($user->getGUID(), $profile_field, $temp_value, 'text', $user->getGUID(), $access_id, $multiple);
				}
			} else {
				create_metadata($user->getGUID(), $profile_field, $temp_result, 'text', $user->getGUID(), $access_id);
			}
		}
	}
	
	// sync profile icon, only if the user has no icon
	if (empty($user->icontime)) {
		socialink_facebook_sync_profile_icon($user->getGUID());
	}
}

/**
 * Update the user profile with the Facebook profile image
 *
 * @param int $user_guid the user_guid to update
 *
 * @return bool
 */
function socialink_facebook_sync_profile_icon($user_guid = 0) {
	
	$user_guid = sanitise_int($user_guid, false);
	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	
	if (empty($user_guid)) {
		return false;
	}
	
	$session = socialink_facebook_is_connected($user_guid);
	if (empty($session)) {
		return false;
	}
	
	$user = get_user($user_guid);
	if (empty($user)) {
		return false;
	}
	
	$request = new FaceBook\FacebookRequest($session, "GET", "/me/picture", array("redirect" => "false", "type" => "large"));
	
	// set correct proxy settings (if needed)
	$curl_http_client = socialink_facebook_get_curl_http_client();
	$request->setHttpClientHandler($curl_http_client);
	
	try {
		$api_result = $request->execute()->getGraphObject();
	} catch(Exception $e) {}
	
	if (empty($api_result)) {
		return false;
	}
	
	$profile_icon_url = $api_result->getProperty("url");
	if (empty($profile_icon_url)) {
		return false;
	}
	
	if (!file_get_contents($profile_icon_url)) {
		return false;
	}
	
	$icon_sizes = elgg_get_config("icon_sizes");
	if (empty($icon_sizes)) {
		return false;
	}
	
	$result = false;
	$fh = new ElggFile();
	$fh->owner_guid = $user->getGUID();

	foreach ($icon_sizes as $name => $properties) {
		$resize = get_resized_image_from_existing_file(
				$profile_icon_url,
				$properties["w"],
				$properties["h"],
				$properties["square"],
				0, 0, 0, 0,
				$properties["upscale"]
		);
			
		if (!empty($resize)) {
			$fh->setFilename("profile/" . $user->getGUID() . $name . ".jpg");
			$fh->open("write");
			$fh->write($resize);
			$fh->close();
				
			$result = true;
		}
	}

	if (!empty($result)) {
		$user->icontime = time();
			
		// trigger event to let others know the icon was updated
		elgg_trigger_event("profileiconupdate", $user->type, $user);
	}
	
	return $result;
}

/**
 * Create a user based on Facebook information
 *
 * @param string $token Facebook access token
 *
 * @return bool|ElggUser
 */
function socialink_facebook_create_user($token) {
	
	if (empty($token)) {
		return false;
	}
	
	if (!socialink_facebook_available()) {
		return false;
	}
	
	$session = new Facebook\FacebookSession($token);
	if (empty($session)) {
		return false;
	}
	
	$request = new FaceBook\FacebookRequest($session, "GET", "/me");
	
	// set correct proxy settings (if needed)
	$curl_http_client = socialink_facebook_get_curl_http_client();
	$request->setHttpClientHandler($curl_http_client);
		
	try {
		$api_result = $request->execute()->getGraphObject(Facebook\GraphUser::className());
	} catch(Exception $e) {}
	
	if (empty($api_result)) {
		return false;
	}
	
	// get user information
	$name = $api_result->getName();
	$email = $api_result->getEmail();
	
	if (get_user_by_email($email)) {
		register_error(elgg_echo("socialink:networks:create_user:error:email"));
		return false;
	}
	
	$pwd = generate_random_cleartext_password();
	$username = socialink_create_username_from_email($email);
	
	try {
		$user_guid = register_user($username, $pwd, $name, $email);
		if (empty($user_guid)) {
			return false;
		}
		
		// show hidden entities
		$access = access_get_show_hidden_status();
		access_show_hidden_entities(true);
		
		$user = get_user($user_guid);
		if (empty($user)) {
			access_show_hidden_entities($access);
			
			return false;
		}
		
		// register user's access tokens
		elgg_set_plugin_user_setting("facebook_access_token", $token, $user_guid, "socialink");
		elgg_set_plugin_user_setting("facebook_user_id", $api_result->getId(), $user_guid, "socialink");
		
		// no need for uservalidationbyemail
		elgg_unregister_plugin_hook_handler("register", "user", "uservalidationbyemail_disable_new_user");
		
		// sync user data
		socialink_facebook_sync_profile_metadata($user->getGUID());
		
		// trigger hook for registration
		$params = array(
			"user" => $user,
			"password" => $pwd,
			"friend_guid" => 0,
			"invitecode" => ""
		);
		
		if (elgg_trigger_plugin_hook("register", "user", $params, true) !== false) {
			access_show_hidden_entities($access);
			
			// return the user
			return $user;
		}
		
		// restore hidden entities
		access_show_hidden_entities($access);
	} catch(Exception $e){}
	
	return false;
}

/**
 * Is the connection to Facebook still valid
 *
 * @param int $user_guid the user_guid to check
 *
 * @return bool
 */
function socialink_facebook_validate_connection($user_guid = 0) {
	
	$user_guid = sanitise_int($user_guid, false);
	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	
	if (empty($user_guid)) {
		return false;
	}
	
	// can we get a user
	$session = socialink_facebook_is_connected($user_guid);
	if (empty($session)) {
		return false;
	}
	
	try {
		return $session->validate();
	} catch (Exceptions $e) {}
	
	return false;
}

/**
 * Get the Facebook user_id for a given access token
 *
 * @param AccessToken|string $access_token a valid Facebook access token
 *
 * @return bool|string
 */
function socialink_facebook_get_user_id_from_access_token($access_token) {
	
	if (empty($access_token)) {
		return false;
	}
	
	$session = new Facebook\FacebookSession($access_token);
	if (empty($session)) {
		return false;
	}
	
	$token = $session->getAccessToken();
	if (empty($token)) {
		return false;
	}
	
	$token_info = $token->getInfo();
	if (empty($token_info)) {
		return false;
	}
	
	return $token_info->getId();
}

/**
 * Get the Facebook cUrl client with the correct proxy settings for use with FacebookRequest
 *
 * @return \Facebook\HttpClients\FacebookCurlHttpClient
 */
function socialink_facebook_get_curl_http_client() {
	static $result;
	
	if (!isset($result)) {
		$result = new Facebook\HttpClients\FacebookCurlHttpClient();
		
		$proxy_settings = socialink_get_proxy_settings();
		if (!empty($proxy_settings)) {
			
			$curl_client = new Facebook\HttpClients\FacebookCurl();
			$curl_client->setopt(CURLOPT_PROXY, $proxy_settings["CURLOPT_PROXY"]);
			
			if (!empty($proxy_settings["CURLOPT_PROXYPORT"])) {
				$curl_client->setopt(CURLOPT_PROXYPORT, $proxy_settings["CURLOPT_PROXYPORT"]);
			}
			
			$result = new Facebook\HttpClients\FacebookCurlHttpClient($curl_client);
		}
	}
	
	return $result;
}
