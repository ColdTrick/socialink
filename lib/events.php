<?php
/**
 * All event handlers are bundled here
 */

/**
 * listen to the object creation event
 *
 * @param string     $event       the name of the event
 * @param string     $entity_type the type of the event
 * @param ElggObject $entity      supplied entity
 *
 * @return void
 */
function socialink_create_object_handler($event, $entity_type, $entity) {
	
	if (!empty($entity) && elgg_instanceof($entity, "object", "thewire")) {
		if ($networks = socialink_get_available_networks()) {
			foreach ($networks as $network) {
				$setting = elgg_get_plugin_user_setting("thewire_post_" . $network, $entity->getOwner(), "socialink");

				if ($setting == "yes") {
					$connected = "socialink_" . $network . "_is_connected";
						
					if (is_callable($connected) && call_user_func($connected)) {
						$post_message = "socialink_" . $network . "_post_message";

						if (is_callable($post_message)) {
							call_user_func($post_message, $entity->description, $entity->getOwner());
						}
					}
				}
			}
		}
	}
}

/**
 * Listen to the login event
 *
 * @param string   $event  the name of the event
 * @param string   $type   the type of the event
 * @param ElggUser $entity supplied entity
 *
 * @return void
 */
function socialink_login_user_handler($event, $type, $entity) {
	
	if (empty($entity) || !elgg_instanceof($entity, "user")) {
		return;
	}
	
	// check if the user wishes to link a network
	$link_network = get_input("socialink_link_network");
		
	if (!empty($link_network) && socialink_is_available_network($link_network)) {
		switch ($link_network) {
			case "twitter":
				socialink_twitter_authorize($entity->getGUID());
				break;
			case "facebook":
				socialink_facebook_authorize($entity->getGUID());
				break;
			case "linkedin":
				socialink_linkedin_authorize($entity->getGUID());
				break;
			case "wordpress":
				socialink_wordpress_authorize($entity->getGUID());
				break;
		}

		unset($_SESSION["socialink_token"]);
		
		// sync network data
		elgg_trigger_plugin_hook("socialink:sync", "user", array("user" => $entity, "network" => $link_network));
	}
	
	// check if network connections are still valid
	$networks = socialink_get_user_networks($entity->getGUID());
	if (empty($networks)) {
		return;
	}
	
	foreach ($networks as $network) {
		$response = socialink_validate_network($network, $entity->getGUID());
		
		if ($response === false) {
			// disconnect from this network and report to user
			$function = "socialink_" . $network . "_remove_connection";
			
			if (is_callable($function)) {
				call_user_func($function, $entity->getGUID());
				register_error(sprintf(elgg_echo("socialink:network_invalid"), elgg_echo("socialink:network:" . $network)));
			}
		}
	}
}
