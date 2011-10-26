<?php

	function socialink_create_object_handler($event, $entity_type, $entity){
		
		if(!empty($entity) && elgg_instanceof($entity, "object", "thewire")){
			if($networks = socialink_get_available_networks()){
				foreach($networks as $network){
					$setting = elgg_get_plugin_user_setting("thewire_post_" . $network, $entity->getOwner(), "socialink");

					if($setting == "yes"){
						$connected = "socialink_" . $network . "_is_connected";
							
						if(call_user_func($connected)){
							$post_message = "socialink_" . $network . "_post_message";

							call_user_func($post_message, $entity->description, $entity->getOwner());
						}
					}
				}
			}
		}
	}
	
	function socialink_validate_user_handler($event, $type, $entity){
	
		if(!empty($entity) && elgg_instanceof($entity, "user")){
			$network = get_input("network");
				
			switch($network){
				case "facebook":
					// user is validated by facebook
					elgg_set_user_validation_status($entity->getGUID(), true, "email");
						
					// break event chain
					return false;
					break;
			}
		}
	}
	
	function socialink_login_user_handler($event, $type, $entity){
		global $SESSION;
	
		if(!empty($entity) && elgg_instanceof($entity, "user")){
			// check if the user wishes to link a network
			$link_network = get_input("socialink_link_network");
				
			if(!empty($link_network) && socialink_is_available_network($link_network)){
				switch($link_network){
					case "twitter":
						socialink_twitter_authorize($entity->getGUID());
						break;
					case "facebook":
						socialink_facebook_authorize($entity->getGUID());
						break;
					case "linkedin":
						socialink_linkedin_authorize($entity->getGUID());
						break;
					case "hyves":
						socialink_hyves_authorize($entity->getGUID());
						break;
				}
	
				$SESSION->offsetUnset("socialink_token");
			}
			
			// check if network connections are still valid
			$networks = socialink_get_user_networks($entity->getGUID());
			if(!empty($networks)){
				foreach($networks as $network){
					$response = socialink_validate_network($network, $entity->getGUID());
					
					if($response === false){
						// disconnect from this network and report to user
						call_user_func("socialink_" . $network . "_remove_connection", $entity->getGUID());
						register_error(sprintf(elgg_echo("socialink:network_invalid"), elgg_echo("socialink:network:" . $network)));
					}
				}
			}
		}
	}