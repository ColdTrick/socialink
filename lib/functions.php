<?php
/**
 * All helper functions are bundled here
 */

/**
 * Load all the configured social network libraries
 *
 * @return void
 */
function socialink_load_networks() {
	
	if ($networks = socialink_get_available_networks()) {
		foreach ($networks as $network) {
			elgg_load_library("socialink:" . $network);
		}
	}
}

/**
 * Check if the Twitter link is available
 *
 * @return bool|array
 */
function socialink_twitter_available() {
	$result = false;
	
	if (elgg_get_plugin_setting("enable_twitter", "socialink") == "yes") {
		$consumer_key = elgg_get_plugin_setting("twitter_consumer_key", "socialink");
		$consumer_secret = elgg_get_plugin_setting("twitter_consumer_secret", "socialink");
		
		if (!empty($consumer_key) && !empty($consumer_secret)) {
			$result = array(
				"consumer_key" => $consumer_key,
				"consumer_secret" => $consumer_secret,
			);
		}
	}
	
	return $result;
}

/**
 * Check if the Facebook link is available
 *
 * @return bool
 */
function socialink_facebook_available() {
	static $result;
	
	if (!isset($result)) {
		$result = false;
		
		if (elgg_get_plugin_setting("enable_facebook", "socialink") == "yes") {
			$app_id = elgg_get_plugin_setting("facebook_app_id", "socialink");
			$app_secret = elgg_get_plugin_setting("facebook_app_secret", "socialink");
			
			if (!empty($app_id) && !empty($app_secret)) {
				// set defaults
				Facebook\FacebookSession::setDefaultApplication($app_id, $app_secret);
				
				$result = true;
			}
		}
	}
	
	return $result;
}

/**
 * Check if the LinkedIn link is available
 *
 * @return bool|array
 */
function socialink_linkedin_available() {
	$result = false;
	
	if (elgg_get_plugin_setting("enable_linkedin", "socialink") == "yes") {
		$consumer_key = elgg_get_plugin_setting("linkedin_consumer_key", "socialink");
		$consumer_secret = elgg_get_plugin_setting("linkedin_consumer_secret", "socialink");
		
		if (!empty($consumer_key) && !empty($consumer_secret)) {
			$result = array(
				"consumer_key" => $consumer_key,
				"consumer_secret" => $consumer_secret,
			);
		}
	}
	
	return $result;
}

/**
 * Check if the Wordpress link is available
 *
 * @return bool|array
 */
function socialink_wordpress_available() {
	$result = false;

	if (elgg_get_plugin_setting("enable_wordpress", "socialink") == "yes") {
		$url = elgg_get_plugin_setting("wordpress_url", "socialink");
		$consumer_key = elgg_get_plugin_setting("wordpress_consumer_key", "socialink");
		$consumer_secret = elgg_get_plugin_setting("wordpress_consumer_secret", "socialink");
			
		if (!empty($url) && !empty($consumer_key) && !empty($consumer_secret)) {
			$result = array(
				"url" => $url,
				"consumer_key" => $consumer_key,
				"consumer_secret" => $consumer_secret,
			);
		}
	}

	return $result;
}

/**
 * Get all the supported social networks
 *
 * @return array
 */
function socialink_get_supported_networks() {
	return array("twitter", "linkedin", "facebook", "wordpress");
}

/**
 * Check if a social network is supported
 *
 * @param string $network the name of the network to check
 *
 * @return bool
 */
function socialink_is_supported_network($network) {
	$result = false;
	
	if (!empty($network) && ($networks = socialink_get_supported_networks())) {
		$result = in_array($network, $networks);
	}
	
	return $result;
}

/**
 * Get all available social networks
 *
 * @return bool|array
 */
function socialink_get_available_networks() {
	static $available;
	
	if (!isset($available)) {
		if ($networks = socialink_get_supported_networks()) {
			$available = array();
			
			foreach ($networks as $network) {
				$function = "socialink_" . $network . "_available";
				
				if (is_callable($function) && call_user_func($function)) {
					$available[] = $network;
				}
			}
		} else {
			$available = false;
		}
	}
	
	return $available;
}

/**
 * Check if a social network is available
 *
 * @param string $network the name of the social network
 *
 * @return bool
 */
function socialink_is_available_network($network) {
	$result = false;
	
	if (!empty($network) && ($networks = socialink_get_available_networks())) {
		$result = in_array($network, $networks);
	}
	
	return $result;
}

/**
 * Returns all the networks the user is connected
 *
 * @param int $user_guid the user to check
 *
 * @return array
 */
function socialink_get_user_networks($user_guid) {
	$result = array();
	
	if (empty($user_guid)) {
		$user_guid = elgg_get_logged_in_user_guid();
	}
	
	if ($available_networks = socialink_get_available_networks()) {
		foreach ($available_networks as $network) {
			$function = "socialink_" . $network . "_is_connected";
			
			if (is_callable($function) && call_user_func($function, $user_guid)) {
				$result[] = $network;
			}
		}
	}
	
	return $result;
}

/**
 * Validate that the user is still connected to a social network
 *
 * @param string $network   the social network to check
 * @param int    $user_guid the user to check for
 *
 * @return bool
 */
function socialink_validate_network($network, $user_guid) {
	$result = true;
	
	$function = "socialink_" . $network . "_validate_connection";
	
	if (is_callable($function)) {
		$result = call_user_func($function, $user_guid);
	}
	
	return $result;
}

/**
 * Get the global proxy settings
 *
 * @return bool|array
 */
function socialink_get_proxy_settings() {
	static $result;
	
	if (!isset($result)) {
		$result = false;
		
		// get proxy host setting
		$proxy_host = elgg_get_plugin_setting("proxy_host", "socialink");
		
		if (!empty($proxy_host)) {
			$result["CURLOPT_PROXY"] = $proxy_host;
			
			// get proxy port setting
			$proxy_port = (int) elgg_get_plugin_setting("proxy_port", "socialink");
			if (!empty($proxy_host)) {
				$result["CURLOPT_PROXYPORT"] = $proxy_port;
			}
		}
	}

	return $result;
}

/**
 * Get an array of the supported network fields
 *
 * result is in format
 * 		settings_name => network_name
 *
 * @param string $network the social network to get the fields for
 *
 * @return bool|array
 */
function socialink_get_network_fields($network) {
	$result = false;
	
	if (!empty($network) && socialink_is_supported_network($network)) {
		$fields = array(
			"twitter" => array(
				"name" => "name",
				"location" => "location",
				"url" => "url",
				"description" => "description",
				"screen_name" => "screen_name",
				"profile_url" => "socialink_profile_url",
			),
			"linkedin" => array(
				"firstname" => "firstName",
				"lastname" => "lastName",
				"email" => "emailAddress",
				"name" => "socialink_name",
				"profile_url" => "publicProfileUrl",
				"location" => "location->name",
				"industry" => "industry"
			),
			"facebook" => array(
				"name" => "getName",
				"firstname" => "getFirstName",
				"lastname" => "getLastName",
				"profile_url" => "getLink",
				"email" => "getEmail",
				"location" => "getLocation()->getCity",
				"gender" => "getGender",
			)
		);
		
		if (array_key_exists($network, $fields)) {
			$result = $fields[$network];
		}
	}
	
	return $result;
}

/**
 * Get the profile field configuration for a social network
 *
 * @param string $network the name of the social network
 *
 * @return bool|array
 */
function socialink_get_configured_network_fields($network) {
	$result = false;
	
	if (!empty($network) && socialink_is_available_network($network)) {
		if (($fields = socialink_get_network_fields($network)) && !empty($fields)) {
			$temp = array();
			
			foreach ($fields as $setting_name => $network_name) {
				if (($profile_field = elgg_get_plugin_setting($network . "_profile_" . $setting_name, "socialink")) && !empty($profile_field)) {
					$result[$setting_name] = $profile_field;
				}
			}
			
			if (!empty($temp)) {
				$result = $temp;
			}
		}
	}
	
	return $result;
}

/**
 * Create a username from an e-mail address
 *
 * @param string $email the email address
 *
 * @return bool|string
 */
function socialink_create_username_from_email($email) {
	$result = false;
	
	if (!empty($email) && is_email_address($email)) {
		list($username) = explode("@", $email);
		
		// filter invalid characters from username from validate_username()
		$username = preg_replace('/[^a-zA-Z0-9]/', "", $username);
		
		// check for min username length
		$minchars = (int) elgg_get_config("minusername");
		if (empty($minchars)) {
			$minchars = 4;
		}
		
		$username = str_pad($username, $minchars, "0", STR_PAD_RIGHT);
		
		// show hidden entities
		$access = access_get_show_hidden_status();
		access_show_hidden_entities(TRUE);
		
		// check if username extist
		if (get_user_by_username($username)) {
			$i = 1;
			while (get_user_by_username($username . $i)) {
				$i++;
			}
			
			$username = $username . $i;
		}
		
		// restore access settings
		access_show_hidden_entities($access);
		
		// return username
		$result = $username;
	}
	
	return $result;
}

/**
 * Prepare some login variables
 *
 * @return void
 */
function socialink_prepare_login() {
	
	if (empty($_SESSION["last_forward_from"])) {
		$site_url = elgg_get_site_url();
		$referer = $_SERVER["HTTP_REFERER"];
		
		if (($site_url != $referer) && stristr($referer, $site_url)) {
			$_SESSION["last_forward_from"] = $referer;
		}
	}
}
