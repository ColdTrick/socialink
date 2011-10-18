<?php 

	gatekeeper();
	
	$service = get_input("service");
	
	switch($service){
		case "twitter":
			if(socialink_twitter_available()){
				if(socialink_twitter_is_connected()){
					if(socialink_twitter_remove_connection()){
						system_message(elgg_echo("socialink:actions:remove:twitter:success"));
					} else {
						register_error(elgg_echo("socialink:actions:remove:twitter:error:remove"));
					}
				} else {
					register_error(elgg_echo("socialink:actions:remove:twitter:error:connected"));
				}
			} else {
				register_error(elgg_echo("socialink:actions:remove:twitter:error:unavailable"));
			}
			break;
		case "linkedin":
			if(socialink_linkedin_available()){
				if(socialink_linkedin_is_connected()){
					if(socialink_linkedin_remove_connection()){
						system_message(elgg_echo("socialink:actions:remove:linkedin:success"));
					} else {
						register_error(elgg_echo("socialink:actions:remove:linkedin:error:remove"));
					}
				} else {
					register_error(elgg_echo("socialink:actions:remove:linkedin:error:connected"));
				}
			} else {
				register_error(elgg_echo("socialink:actions:remove:linkedin:error:unavailable"));
			}
			break;
		case "facebook":
			if(socialink_facebook_available()){
				if(socialink_facebook_is_connected()){
					if(socialink_facebook_remove_connection()){
						system_message(elgg_echo("socialink:actions:remove:facebook:success"));
					} else {
						register_error(elgg_echo("socialink:actions:remove:facebook:error:remove"));
					}
				} else {
					register_error(elgg_echo("socialink:actions:remove:facebook:error:connected"));
				}
			} else {
				register_error(elgg_echo("socialink:actions:remove:facebook:error:unavailable"));
			}
			break;
		default:
			register_error(elgg_echo("socialink:actions:remove:error:unknown_service"));
			break;
	}


	forward($_SERVER["HTTP_REFERER"]);

?>