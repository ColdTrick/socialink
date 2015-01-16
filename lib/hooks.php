<?php
/**
 * All plugin hook handler are bundled here
 */

/**
 * Synchronize social network information to a profile
 *
 * @param string $hook_name    the name of the hook
 * @param string $entity_type  the type of the hook
 * @param mixed  $return_value current return value
 * @param array  $params       supplied params
 *
 * @return void
 */
function socialink_sync_network_hook($hook_name, $entity_type, $return_value, $params) {

	if (!empty($params) && is_array($params)) {
		if (isset($params["user"]) && isset($params["network"])) {
			$user = $params["user"];
			$network = $params["network"];

			if (socialink_is_available_network($network)) {
				switch ($network) {
					case "twitter":
						socialink_twitter_sync_profile_metadata($user->getGUID());
						break;
					case "linkedin":
						socialink_linkedin_sync_profile_metadata($user->getGUID());
						break;
					case "facebook":
						socialink_facebook_sync_profile_metadata($user->getGUID());
						break;
				}
			}
		}
	}
}

/**
 * Add url's to the allowed url's in walled garden mode
 *
 * @param string $hook_name    the name of the hook
 * @param string $entity_type  the type of the hook
 * @param mixed  $return_value current return value
 * @param array  $params       supplied params
 *
 * @return array
 */
function socialink_walled_garden_hook($hook_name, $entity_type, $return_value, $params) {
	$result = $return_value;
	
	// add socialink to the public pages
	$result[] = "socialink/.*";
	$result[] = "action/socialink/.*";
	
	return $result;
}

/**
 * Validate a new user if he/she comes from some social networks
 *
 * @param string $hook         the name of the hook
 * @param string $type         the type of the hook
 * @param mixed  $return_value current return value
 * @param array  $params       supplied params
 *
 * @return void
 */
function socialink_register_user_hook($hook, $type, $return_value, $params) {
	$result = $return_value;
	
	if (!empty($params) && is_array($params)) {
		$user = elgg_extract("user", $params);
		
		if (elgg_instanceof($user, "user")) {
			$network = get_input("network");
			
			switch ($network) {
				case "facebook":
				case "wordpress":
					// user is validated by facebook or wordpress
					elgg_set_user_validation_status($user->getGUID(), true, "email");
			
					break;
			}
		}
	}
	
	return $result;
}
