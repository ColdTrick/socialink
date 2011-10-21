<?php

	function socialink_sync_network_hook($hook_name, $entity_type, $return_value, $params){
	
		if(!empty($params) && is_array($params)){
			if(isset($params["user"]) && isset($params["network"])){
				$user = $params["user"];
				$network = $params["network"];
	
				if(socialink_is_available_network($network)){
					switch($network){
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
