<?php
/**
 * All OpenBibId releated helper functions are bundeld here
 */

/**
 * Get the API object
 *
 * @param array $keys API keys
 *
 * @return bool|OpenBibId
 */
function socialink_openbibid_get_api_object($keys) {
	$result = false;

	if (!empty($keys) && is_array($keys)) {
		$consumer_key = $keys["consumer_key"];
		$consumer_secret = $keys["consumer_secret"];
			
		if (isset($keys["oauth_token"]) && isset($keys["oauth_secret"])) {
			$oauth_token = $keys["oauth_token"];
			$oauth_secret = $keys["oauth_secret"];
		} else {
			$oauth_token = null;
			$oauth_secret = null;
		}
			
		$result = new OpenBibId($consumer_key, $consumer_secret, $oauth_token, $oauth_secret);
		
		if ($proxy_settings = socialink_get_proxy_settings()) {
			$result->setProxySettings($proxy_settings);
		}
	}

	return $result;
}

/**
 * Check if the user is connected to a OpenBibId account
 *
 * @param int $user_guid the user_guid to check
 *
 * @return bool
 */
function socialink_openbibid_is_connected($user_guid = 0) {
	$result = false;

	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}

	if (!empty($user_guid) && ($keys = socialink_openbibid_available())) {
		$oauth_token = elgg_get_plugin_user_setting("openbibid_oauth_token", $user_guid, "socialink");
		$oauth_secret = elgg_get_plugin_user_setting("openbibid_oauth_secret", $user_guid, "socialink");
			
		if (!empty($oauth_token) && !empty($oauth_secret)) {
			$result = $keys;
			$result["oauth_token"] = $oauth_token;
			$result["oauth_secret"] = $oauth_secret;
		}
	}

	return $result;
}

/**
 * Get the URL to authorize OpenBibId
 *
 * @param string $callback the callback URL
 *
 * @return bool|string
 */
function socialink_openbibid_get_authorize_url($callback = NULL) {
	global $SESSION;

	$result = false;

	if ($keys = socialink_openbibid_available()) {
		if ($api = socialink_openbibid_get_api_object($keys)) {
			if ($token = $api->getRequestToken($callback)) {
				// save token in session for use after authorization
				$SESSION["socialink_openbibid"] = array(
					"oauth_token" => $token["oauth_token"],
					"oauth_token_secret" => $token["oauth_token_secret"],
				);

				$result = $api->getAuthorizeURL($token["oauth_token"]);
			}
		}
	}

	return $result;
}

/**
 * Get a OpenBibId access token
 *
 * @param string $oauth_verifier the API state
 *
 * @return bool|string
 */
function socialink_openbibid_get_access_token($oauth_verifier = NULL) {
	global $SESSION;

	$result = true;

	if ($keys = socialink_openbibid_available()) {
		if (isset($SESSION["socialink_openbibid"])) {
			// retrieve stored tokens
			$keys["oauth_token"] = $SESSION["socialink_openbibid"]["oauth_token"];
			$keys["oauth_secret"] = $SESSION["socialink_openbibid"]["oauth_token_secret"];
			$SESSION->offsetUnset("socialink_openbibid");

			// fetch an access token
			if ($api = socialink_openbibid_get_api_object($keys)) {
				$result = $api->getAccessToken($oauth_verifier);
			}
		} elseif (isset($SESSION["socialink_token"])) {
			$result = $SESSION["socialink_token"];
		}
	}

	return $result;
}

/**
 * Authorize a OpenBibId account
 *
 * @param int $user_guid the user_guid to authorize
 *
 * @return bool
 */
function socialink_openbibid_authorize($user_guid = 0) {
	$result = false;
	
	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	
	$oauth_verifier = get_input("oauth_verifier", NULL);
	
	if (!empty($user_guid) && ($token = socialink_openbibid_get_access_token($oauth_verifier))) {
		if (isset($token["oauth_token"]) && isset($token["oauth_token_secret"])) {
			// only one user per tokens
			$params = array(
				"type" => "user",
				"limit" => false,
				"site_guids" => false,
				"plugin_id" => "socialink",
				"plugin_user_setting_name_value_pairs" => array(
					"openbibid_user_id" => $token["userId"]
				)
			);
				
			// find hidden users (just created)
			$access_status = access_get_show_hidden_status();
			access_show_hidden_entities(true);
	
			if ($users = elgg_get_entities_from_plugin_user_settings($params)) {
				foreach ($users as $user) {
					// revoke access
					elgg_set_plugin_user_setting("openbibid_oauth_token", NULL, $user->getGUID(), "socialink");
					elgg_set_plugin_user_setting("openbibid_oauth_secret", NULL, $user->getGUID(), "socialink");
					elgg_set_plugin_user_setting("openbibid_user_id", NULL, $user->getGUID(), "socialink");
				}
			}
	
			// restore hidden status
			access_show_hidden_entities($access_status);
				
			// register user"s access tokens
			elgg_set_plugin_user_setting("openbibid_user_id", $token["userId"], $user_guid, "socialink");
			elgg_set_plugin_user_setting("openbibid_oauth_token", $token["oauth_token"], $user_guid, "socialink");
			elgg_set_plugin_user_setting("openbibid_oauth_secret", $token["oauth_token_secret"], $user_guid, "socialink");
	
			$result = true;
		}
	}
	
	return $result;
}

/**
 * Update the OpenBibId connection settings
 *
 * @param string $token     the new API tokens
 * @param int    $user_guid the user_guid to update
 *
 * @return bool
 */
function socialink_openbibid_update_connection($token, $user_guid = 0) {
	$result = false;
	
	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	
	if (!empty($token) && !empty($user_guid) && socialink_openbibid_is_connected($user_guid)) {
		elgg_set_plugin_user_setting("openbibid_oauth_token", $token["oauth_token"], $user_guid, "socialink");
		elgg_set_plugin_user_setting("openbibid_oauth_secret", $token["oauth_token_secret"], $user_guid, "socialink");
		elgg_set_plugin_user_setting("openbibid_user_id", $token["userId"], $user_guid, "socialink");
		
		$result = true;
	}
	
	return $result;
}

/**
 * Remove the OpenBibId connection
 *
 * @param int $user_guid the user_guid to remove to connection for
 *
 * @return bool
 */
function socialink_openbibid_remove_connection($user_guid = 0) {
	$result = false;
	
	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	
	if (!empty($user_guid) && socialink_openbibid_is_connected($user_guid)) {
		elgg_set_plugin_user_setting("openbibid_oauth_token", null, $user_guid, "socialink");
		elgg_set_plugin_user_setting("openbibid_oauth_secret", null, $user_guid, "socialink");
		elgg_set_plugin_user_setting("openbibid_user_id", null, $user_guid, "socialink");
			
		$result = true;
	}
	
	return $result;
}

/**
 * Validate user permissions on OpenBibId
 *
 * @param int    $user_guid  the user_guid to check the permissions for
 * @param string $permission the permission to check
 *
 * @return bool
 */
function socialink_openbibid_validate_user_permission($user_guid = 0, $permission = "read") {
	$result = false;
	
	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	
	if (!empty($user_guid) && ($keys = socialink_openbibid_is_connected($user_guid))) {
		if ($api = socialink_openbibid_get_api_object($keys)) {
			$user_id = elgg_get_plugin_user_setting("openbibid_user_id", $user_guid, "socialink");
			$consumer_key = $keys["consumer_key"];
			
			$url = $api->host . "permissions/user/" . $user_id . "/consumer/" . $consumer_key . "/" . $permission;
			
			if ($response = $api->get($url)) {
				if ($response->code == "OK") {
					$result = true;
				}
			}
		}
	}
	
	return $result;
}

/**
 * Check if the IP address of the user is allowed to connect to OpenBibId
 *
 * @param string $ip         the IP address
 * @param string $permission the permission to check
 *
 * @return bool
 */
function socialink_openbibid_validate_ip_permission($ip = null, $permission = "read") {
	$result = false;
	
	if (empty($ip)) {
		$ip = $_SERVER["REMOTE_ADDR"];
	}
	
	if (!empty($ip) && ($keys = socialink_openbibid_available())) {
		if ($api = socialink_openbibid_get_api_object($keys)) {
			$params = array(
				"ip" => $ip
			);
			$consumer_key = $keys["consumer_key"];
			
			$url = "permissions/ip/consumer/" . $consumer_key . "/" . $permission;
			
			if ($response = $api->get($url, $params)) {
				if ($response->code == "OK") {
					$result = true;
				}
			}
		}
	}
	
	return $result;
}
