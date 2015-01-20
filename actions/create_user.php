<?php
	
$forward_url = REFERER;

if (!elgg_is_logged_in() && !empty($_SESSION["socialink_token"])) {
	$token = $_SESSION["socialink_token"];
	
	$network = get_input("network");
	$email = get_input("email");
	
	switch ($network) {
		case "twitter":
			$user = socialink_twitter_create_user($token, $email);
			
			break;
		case "linkedin":
			$user = socialink_linkedin_create_user($token);
			
			break;
		case "facebook":
			$user = socialink_facebook_create_user($token);
			
			break;
		case "wordpress":
			$user = socialink_wordpress_create_user($token);
			
			break;
		default:
			register_error(elgg_echo("socialink:actions:create_user:error:network"));
			break;
	}
	
	if (!empty($user) && ($user instanceof ElggUser)) {
		$forward_url = "";
		unset($_SESSION["socialink_token"]);
		
		// notify the user
		system_message(elgg_echo("registerok", array(elgg_get_site_entity()->name)));
		
		try {
			// user is validated, so login
			if (login($user)) {
				// log last network
				elgg_set_plugin_user_setting("last_login_network", $network, $user->getGUID(), "socialink");
				
				// check if we need to forward to something
				if (!empty($_SESSION["last_forward_from"])) {
					$forward_url = $_SESSION["last_forward_from"];
					unset($_SESSION["last_forward_from"]);
				}
			}
		} catch (Exception $e) {}
	} else {
		register_error(elgg_echo("registerbad"));
	}
} else {
	register_error(elgg_echo("socialink:actions:create_user:error:loggedin"));
}

forward($forward_url);
	