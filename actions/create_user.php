<?php 

	global $CONFIG;
	
	$forward_url = REFERER;
	
	if(!elgg_is_logged_in() && !empty($_SESSION["socialink_token"])){
		$token = $_SESSION["socialink_token"];
		
		$network = get_input("network");
		$email = get_input("email");
		
		switch($network){
			case "twitter":
				$user = socialink_twitter_create_user($token, $email);
				
				break;
			case "linkedin":
				$user = socialink_linkedin_create_user($token, $email);
				
				break;
			case "facebook":
				$user = socialink_facebook_create_user($token);
				
				break;
			default:
				register_error(elgg_echo("socialink:actions:create_user:error:network"));
				break;
		}
		
		if(!empty($user) && ($user instanceof ElggUser)){
			$forward_url = "";
			unset($_SESSION["socialink_token"]);
			
			// notify the user
			system_message(sprintf(elgg_echo("registerok"), $CONFIG->sitename));
			
			// request validation on this email address
			request_user_validation($user->getGUID());
			
			// reload user
			invalidate_cache_for_entity($user->getGUID());
			$user = get_user($user->getGUID());
			
			if($user->isEnabled() && $user->validated){
				// user is validated, so login
				if(login($user)){
					// log last network
					elgg_set_plugin_user_setting("last_login_network", $network, $user->getGUID(), "socialink");
					
					// check if we need to forward to something
					if (!empty($_SESSION['last_forward_from'])) {
						$forward_url = $_SESSION['last_forward_from'];
						unset($_SESSION['last_forward_from']);
					}
				}
			} else {
				// disable the user until validated
				$user->disable('new_user', false);
			}
		}
	} else {
		register_error(elgg_echo("socialink:actions:create_user:error:loggedin"));
	}

	forward($forward_url);
	