<?php

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
							if(login($user, true)){
								// permanent login
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
						$callback_url = elgg_get_site_url() . "socialink/" . $action . "/" . $network;
	
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