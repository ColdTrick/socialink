<?php 

	require_once(dirname(__FILE__) . "/lib/functions.php");

	function socialink_init(){
		// extend CSS
		elgg_extend_view("css", "socialink/css");
		elgg_extend_view("css", "fancybox/css");
		
		// extend JS
		elgg_extend_view("js/initialise_elgg", "socialink/js");
		
		// extend metatags
		elgg_extend_view("metatags", "socialink/metatags");
		
		// extend login box
		elgg_extend_view("account/forms/login", "socialink/login");
		
		// register page handler
		register_page_handler("socialink", "socialink_page_handler");
		
		// load necesary files
		socialink_load_networks();
		
		// twitter in
		if(is_plugin_enabled("thewire") && socialink_is_available_network("twitter")){
			$setting = get_plugin_setting("twitter_allow_in", "socialink");
			
			switch($setting){
				case "fifteenmin":
				case "halfhour":
					register_plugin_hook("cron", $setting, "socialink_twitter_in_cron_hook");
					break;
			}
		}
	}
	
	function socialink_pagesetup(){
		
	}
	
	function socialink_page_handler($page){
		global $CONFIG;
		
		switch($page[0]){
			case "authorize":
				gatekeeper();
				
				$user = get_loggedin_user();
				
				switch($page[1]){
					case "twitter":
						if(socialink_twitter_authorize()){
							system_message(elgg_echo("socialink:authorize:twitter:success"));
						} else {
							register_error(elgg_echo("socialink:authorize:twitter:failed"));
						}
						break;
					case "linkedin":
						if(socialink_linkedin_authorize()){
							system_message(elgg_echo("socialink:authorize:linkedin:success"));
						} else {
							register_error(elgg_echo("socialink:authorize:linkedin:failed"));
						}
						break;
					case "facebook":
						if(socialink_facebook_authorize()){
							system_message(elgg_echo("socialink:authorize:facebook:success"));
						} else {
							register_error(elgg_echo("socialink:authorize:facebook:failed"));
						}
						break;
				}
				
				if(!empty($page[1]) && socialink_is_available_network($page[1])){
					trigger_plugin_hook("socialink:sync", "user", array("user" => $user, "network" => $page[1]));
				}
				
				forward("pg/settings/plugins/" . $user->username);
				break;
			case "login":
				if(!isloggedin() && isset($page[1])){
					$network = $page[1];
					$network_name = elgg_echo("socialink:network:" . $network);
					
					$error_msg_no_user = sprintf(elgg_echo("socialink:login:error:no_user"), $network_name, $network_name);
					
					// find hidden users (just created)
					$access_status = access_get_show_hidden_status();
					access_show_hidden_entities(true);
					
					switch($network){
						case "twitter":
							$token = socialink_twitter_get_access_token(get_input('oauth_verifier'));
							
							if (isset($token['oauth_token']) && isset($token['oauth_token_secret'])) {
								$values = array(
									'plugin:settings:socialink:twitter_oauth_token' => $token['oauth_token'],
									'plugin:settings:socialink:twitter_oauth_secret' => $token['oauth_token_secret']
								);
								
								if ($users = get_entities_from_private_setting_multi($values, 'user', '', 0, '', 1, 0, false, -1)) {
									$user = $users[0];
								} else { 
									$_SESSION["socialink_token"] = $token;
									forward("pg/socialink/no_linked_account/twitter");
								}
							} else {
								register_error($error_msg_no_user);
							}
							break;
						case "linkedin":
							$token = socialink_linkedin_get_access_token(get_input('oauth_verifier'));
							
							if (isset($token['oauth_token']) && isset($token['oauth_token_secret'])) {
								$values = array(
									'plugin:settings:socialink:linkedin_oauth_token' => $token['oauth_token'],
									'plugin:settings:socialink:linkedin_oauth_secret' => $token['oauth_token_secret']
								);
								if ($users = get_entities_from_private_setting_multi($values, 'user', '', 0, '', 1, 0, false, -1)) {
									$user = $users[0];
								} else {
									$_SESSION["socialink_token"] = $token;
									forward("pg/socialink/no_linked_account/linkedin");
								}
							} else {
								register_error($error_msg_no_user);
							}
							break;
						case "facebook":
							$state = get_input('state', NULL);
							
							if($token = socialink_facebook_get_access_token($state)){
								$values = array(
									'plugin:settings:socialink:facebook_access_token' => $token
								);
			
								if ($users = get_entities_from_private_setting_multi($values, 'user', '', 0, '', 1, 0, false, -1)) {
									$user = $users[0];
								} else {
									$_SESSION["socialink_token"] = $token;
									forward("pg/socialink/no_linked_account/facebook");
								}
							} else {
								register_error($error_msg_no_user);
							}
							break;
					}
					
					if($user instanceof ElggUser){
						$hold = false;
						
						if($user->isBanned()){
							$hold = true;
						}
						
						if(!$user->isAdmin() && !$user->validated){
							$hold = true;
							
							// give plugins a chance to respond
							if (!trigger_plugin_hook('unvalidated_login_attempt','user',array('entity' => $user))) {
								// if plugins have not registered an action, the default action is to
								// trigger the validation event again and assume that the validation
								// event will display an appropriate message
								trigger_elgg_event('validate', 'user', $user);
							}
						}
						
						if(!$hold){
							if(login($user, true)){ // permanent login
								// log last network
								set_plugin_usersetting("last_login_network", $network, $user->getGUID(), "socialink");
								
								// sync network data
								trigger_plugin_hook("socialink:sync", "user", array("user" => $user, "network" => $network));
								
								// set message and forward to correct page
								system_message(elgg_echo('loginok'));
								
								if (isset($_SESSION['last_forward_from']) && $_SESSION['last_forward_from']) {
									$forward_url = $_SESSION['last_forward_from'];
									unset($_SESSION['last_forward_from']);
									forward($forward_url);
								} elseif (get_input('returntoreferer')) {
									forward($_SERVER['HTTP_REFERER']);
								} else {
									forward("pg/dashboard/");
								}
							}
						} else {
							register_error(elgg_echo('loginerror'));
						}
					}
					
					// restore hidden status
					access_show_hidden_entities($access_status);
				}
				break;
			case "no_linked_account":
				if(!isloggedin()){
					switch($page[1]){
						case "linkedin":
						case "facebook":
						case "twitter":
							set_input("network", $page[1]);
							include(dirname(__FILE__) . "/pages/no_linked_account.php");
							break;
					}
				}
				break;
			case "share":
				if(isloggedin()){
					include(dirname(__FILE__) . "/pages/share.php");
					exit();
				}
				break;
			case "forward":
				if(isset($page[1]) && isset($page[2])){
					$network = $page[1];
					$action = $page[2];
					
					$allowed_actions = array("login", "authorize");
					
					if(socialink_is_available_network($network) && in_array($action, $allowed_actions)){
						$callback_url = $CONFIG->wwwroot . "pg/socialink/" . $action . "/" . $network;
						
						$forward_url = "";
						switch($network){
							case "linkedin":
								$forward_url = socialink_linkedin_get_authorize_url($callback_url);
								break;
							case "facebook":
								$forward_url = socialink_facebook_get_authorize_url($callback_url);
								break;
							case "twitter":
								$forward_url = socialink_twitter_get_authorize_url($callback_url);
								break;
						}
						
						forward($forward_url);
					}
				}
				break;
		}
		
		forward();
	}
	
	function socialink_create_object_handler($event, $entity_type, $entity){
		
		if(!empty($entity) && ($entity instanceof ElggObject)){
			if($entity->getSubtype() == "thewire"){
				if($networks = socialink_get_available_networks()){
					foreach($networks as $network){
						$setting = get_plugin_usersetting("thewire_post_" . $network, $entity->getOwner(), "socialink");
						
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
	}
	
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
	
	function socialink_validate_user_handler($event, $type, $entity){
		
		if(!empty($entity) && ($entity instanceof ElggUser)){
			$network = get_input("network");
			
			switch($network){
				case "facebook":
					// user is validated by facebook
					set_user_validation_status($entity->getGUID(), true, "email");
					
					// break event chain
					return false;
					break;
			}
		}
	}
	
	function socialink_login_user_handler($event, $type, $entity){
		global $SESSION;
		
		if(!empty($entity) && ($entity instanceof ElggUser)){
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

	// register default Elgg events
	register_elgg_event_handler("init", "system", "socialink_init");
	register_elgg_event_handler("pagesetup", "system", "socialink_pagesetup");

	// register event handlers
	//register_elgg_event_handler("create", "object", "socialink_create_object_handler");
	register_elgg_event_handler("validate", "user", "socialink_validate_user_handler", 450);
	register_elgg_event_handler("login", "user", "socialink_login_user_handler", 450);
	
	// hooks
	register_plugin_hook('socialink:sync', 'user', 'socialink_sync_network_hook');
	
	// register actions
	register_action("socialink/remove", false, dirname(__FILE__) . "/actions/remove.php");
	register_action("socialink/create_user", true, dirname(__FILE__) . "/actions/create_user.php");
	register_action("socialink/share", false, dirname(__FILE__) . "/actions/share.php");
?>