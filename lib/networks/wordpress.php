<?php
/**
 * All Wordpress releated helper functions are bundeld here
 */

/**
 * Get the API object
 *
 * @param array $keys API keys
 *
 * @return bool|WordPress
 */
function socialink_wordpress_get_api_object($keys) {
	$result = false;

	if (!empty($keys) && is_array($keys)) {
		$url = $keys["url"];
		$consumer_key = $keys["consumer_key"];
		$consumer_secret = $keys["consumer_secret"];

		if (isset($keys["oauth_token"]) && isset($keys["oauth_secret"])) {
			$oauth_token = $keys["oauth_token"];
			$oauth_secret = $keys["oauth_secret"];
		} else {
			$oauth_token = null;
			$oauth_secret = null;
		}

		$result = new WordPress($url, $consumer_key, $consumer_secret, $oauth_token, $oauth_secret);
		
		if ($proxy_settings = socialink_get_proxy_settings()) {
			$result->setProxySettings($proxy_settings);
		}
	}

	return $result;
}

/**
 * Check if the user is connected to a Wordpress account
 *
 * @param int $user_guid the user_guid to check
 *
 * @return bool
 */
function socialink_wordpress_is_connected($user_guid = 0) {
	$result = false;

	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}

	if (!empty($user_guid) && ($keys = socialink_wordpress_available())) {
		$oauth_token = elgg_get_plugin_user_setting("wordpress_oauth_token", $user_guid, "socialink");
		$oauth_secret = elgg_get_plugin_user_setting("wordpress_oauth_secret", $user_guid, "socialink");

		if (!empty($oauth_token) && !empty($oauth_secret)) {
			$result = $keys;
			$result["oauth_token"] = $oauth_token;
			$result["oauth_secret"] = $oauth_secret;
		}
	}

	return $result;
}

/**
 * Validate a Wordpress API response
 *
 * @param string $response the API reponse
 *
 * @return bool
 */
function socialink_wordpress_validate_response($response) {
	$result = false;
	
	if (!empty($response) && ($response instanceof stdClass)) {
		if ($response->result) {
			$result = true;
		}
	}
	
	return $result;
}

/**
 * Get the URL to authorize Wordpress
 *
 * @param string $callback the callback URL
 *
 * @return bool|string
 */
function socialink_wordpress_get_authorize_url($callback = NULL) {
	global $SESSION;

	$result = false;

	if ($keys = socialink_wordpress_available()) {
		if ($api = socialink_wordpress_get_api_object($keys)) {
			if ($token = $api->getRequestToken($callback)) {
				// save token in session for use after authorization
				$SESSION["socialink_wordpress"] = array(
						"oauth_token" => $token["oauth_token"],
						"oauth_token_secret" => $token["oauth_token_secret"],
				);

				$result = $api->getAuthorizeURL($token["oauth_token"], $callback);
			}
		}
	}

	return $result;
}

/**
 * Get a Wordpress access token
 *
 * @param string $oauth_verifier the API state
 *
 * @return bool|array
 */
function socialink_wordpress_get_access_token($oauth_verifier = NULL) {
	global $SESSION;

	$result = true;

	if ($keys = socialink_wordpress_available()) {
		if (isset($SESSION["socialink_wordpress"])) {
			// retrieve stored tokens
			$keys["oauth_token"] = $SESSION["socialink_wordpress"]["oauth_token"];
			$keys["oauth_secret"] = $SESSION["socialink_wordpress"]["oauth_token_secret"];
			$SESSION->offsetUnset("socialink_wordpress");

			// fetch an access token
			if ($api = socialink_wordpress_get_api_object($keys)) {
				$result = $api->getAccessToken($oauth_verifier);
			}
		} elseif (isset($SESSION["socialink_token"])) {
			$result = $SESSION["socialink_token"];
		}
	}

	return $result;
}

/**
 * Authorize a Wordpress account
 *
 * @param int $user_guid the user_guid to authorize
 *
 * @return bool
 */
function socialink_wordpress_authorize($user_guid = 0) {
	$result = false;

	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}

	$oauth_verifier = get_input("oauth_verifier", NULL);

	if (!empty($user_guid) && ($token = socialink_wordpress_get_access_token($oauth_verifier))) {
		if (isset($token["oauth_token"]) && isset($token["oauth_token_secret"])) {
			// get the WordPress username
			if ($userdata = socialink_wordpress_get_user_data_from_token($token)) {
				// only one user per tokens
				$params = array(
					"type" => "user",
					"limit" => false,
					"site_guids" => false,
					"plugin_id" => "socialink",
					"plugin_user_setting_name_value_pairs" => array(
						"wordpress_userid" => $userdata->ID
					)
				);
					
				// find hidden users (just created)
				$access_status = access_get_show_hidden_status();
				access_show_hidden_entities(true);
	
				if ($users = elgg_get_entities_from_plugin_user_settings($params)) {
					foreach ($users as $user) {
						// revoke access
						elgg_set_plugin_user_setting("wordpress_userid", NULL, $user->getGUID(), "socialink");
						elgg_set_plugin_user_setting("wordpress_oauth_token", NULL, $user->getGUID(), "socialink");
						elgg_set_plugin_user_setting("wordpress_oauth_secret", NULL, $user->getGUID(), "socialink");
					}
				}
	
				// restore hidden status
				access_show_hidden_entities($access_status);
					
				// register user"s access tokens
				elgg_set_plugin_user_setting("wordpress_userid", $userdata->ID, $user_guid, "socialink");
				elgg_set_plugin_user_setting("wordpress_oauth_token", $token["oauth_token"], $user_guid, "socialink");
				elgg_set_plugin_user_setting("wordpress_oauth_secret", $token["oauth_token_secret"], $user_guid, "socialink");
	
				$result = true;
			}
		}
	}

	return $result;
}

/**
 * Update the connection settings of a user
 *
 * @param array $token     new API token
 * @param int   $user_guid the user_guid to update
 *
 * @return bool
 */
function socialink_wordpress_update_connection($token, $user_guid = 0) {
	$result = false;
	
	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	
	if (!empty($token) && !empty($user_guid) && socialink_wordpress_is_connected($user_guid)) {
		elgg_set_plugin_user_setting("wordpress_oauth_token", $token["oauth_token"], $user_guid, "socialink");
		elgg_set_plugin_user_setting("wordpress_oauth_secret", $token["oauth_token_secret"], $user_guid, "socialink");
			
		$result = true;
	}
	
	return $result;
}

/**
 * Remove the Wordpress connection
 *
 * @param int $user_guid the user_guid to remove to connection for
 *
 * @return bool
 */
function socialink_wordpress_remove_connection($user_guid = 0) {
	$result = false;

	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}

	if (!empty($user_guid) && socialink_wordpress_is_connected($user_guid)) {
		elgg_set_plugin_user_setting("wordpress_userid", null, $user_guid, "socialink");
		elgg_set_plugin_user_setting("wordpress_oauth_token", null, $user_guid, "socialink");
		elgg_set_plugin_user_setting("wordpress_oauth_secret", null, $user_guid, "socialink");
		
		$result = true;
	}

	return $result;
}

/**
 * Get Wordpress user data from an API token
 *
 * @param array $token API token
 *
 * @return bool|mixed
 */
function socialink_wordpress_get_user_data_from_token($token) {
	$result = false;
	
	if (!empty($token) && is_array($token)) {
		if ($keys = socialink_wordpress_available()) {
			$keys["oauth_token"] = elgg_extract("oauth_token", $token);
			$keys["oauth_secret"] = elgg_extract("oauth_token_secret", $token, elgg_extract("oauth_secret", $token));
			
			if ($api = socialink_wordpress_get_api_object($keys)) {
				$url = "oauth/userData";
				
				if (($response = $api->get($url)) && socialink_wordpress_validate_response($response)) {
					$result = $response;
				}
			}
		}
	}
	
	return $result;
}

/**
 * Get Wordpress user data
 *
 * @param int $user_guid the user_guid to get the data for
 *
 * @return bool|mixed
 */
function socialink_wordpress_get_user_data($user_guid = 0) {
	$result = false;
	
	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	
	if (!empty($user_guid)) {
		if ($keys = socialink_wordpress_is_connected($user_guid)) {
			
			$result = socialink_wordpress_get_user_data_from_token($keys);
		}
	}
	
	return $result;
}

/**
 * Create a user based on Wordpress information
 *
 * @param string $token Wordpress access token
 *
 * @return bool|ElggUser
 */
function socialink_wordpress_create_user($token) {
	$result = false;
	
	if (!empty($token) && is_array($token)) {
		if ($userdata = socialink_wordpress_get_user_data_from_token($token)) {
			$email = $userdata->email;
			
			if (!get_user_by_email($email) && is_email_address($email)) {
				$username = $userdata->username;
				$displayname = $userdata->displayname;
				
				// show hidden entities
				$access = access_get_show_hidden_status();
				access_show_hidden_entities(true);
				
				// check if the WordPress username is available on Elgg
				if (get_user_by_username($username)) {
					// make a new username based on the email address
					$username = socialink_create_username_from_email($email);
				}
				
				$pwd = generate_random_cleartext_password();
				
				try {
					if ($user_guid = register_user($username, $pwd, $displayname, $email)) {
						
						if ($user = get_user($user_guid)) {
							// save user tokens
							elgg_set_plugin_user_setting("wordpress_userid", $userdata->ID, $user_guid, "socialink");
							elgg_set_plugin_user_setting("wordpress_oauth_token", $token["oauth_token"], $user_guid, "socialink");
							elgg_set_plugin_user_setting("wordpress_oauth_secret", $token["oauth_token_secret"], $user_guid, "socialink");
								
							// trigger hook for registration
							$params = array(
								"user" => $user,
								"password" => $pwd,
								"friend_guid" => 0,
								"invitecode" => ""
							);
								
							if (elgg_trigger_plugin_hook("register", "user", $params, true) !== false) {
								// return the user
								$result = $user;
							}
						}
					}
				} catch (Exception $e) {
					//echo $e->getMessage();
				}
				
				// restore hidden entities
				access_show_hidden_entities($access);
			} else {
				register_error(elgg_echo("socialink:networks:create_user:error:email"));
			}
		}
	}
	
	return $result;
}
