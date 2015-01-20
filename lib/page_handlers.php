<?php
/**
 * All page handlers are bundled here
 */

/**
 * The socialink page handler
 *
 * @param array $page page elements
 *
 * @return bool
 */
function socialink_page_handler($page) {
	$result = false;
	
	switch ($page[0]) {
		case "authorize":
			elgg_gatekeeper();

			$user = elgg_get_logged_in_user_entity();

			switch ($page[1]) {
				case "twitter":
				case "linkedin":
				case "facebook":
				case "wordpress":
					if (call_user_func("socialink_" . $page[1] . "_authorize")) {
						system_message(elgg_echo("socialink:authorize:success", array(elgg_echo("socialink:network:" . $page[1]))));
					} else {
						register_error(elgg_echo("socialink:authorize:failed", array(elgg_echo("socialink:network:" . $page[1]))));
					}
					break;
			}

			if (!empty($page[1]) && socialink_is_available_network($page[1])) {
				elgg_trigger_plugin_hook("socialink:sync", "user", array("user" => $user, "network" => $page[1]));
			}

			forward("settings/plugins/" . $user->username . "/socialink");
			break;
		case "login":
			if (elgg_is_logged_in() || !isset($page[1])) {
				// invalid input
				forward();
			}
			
			$network = $page[1];
			$network_name = elgg_echo("socialink:network:" . $network);
				
			$error_msg_no_user = elgg_echo("socialink:login:error:no_user", array($network_name, $network_name));

			if (!socialink_is_available_network($network)) {
				// unavailable network
				forward();
			}
			
			// find hidden users (just created)
			$access_status = access_get_show_hidden_status();
			access_show_hidden_entities(true);
			
			switch ($network) {
				case "twitter":
					$token = socialink_twitter_get_access_token(get_input("oauth_verifier"));
					
					if (!isset($token["oauth_token"]) || !isset($token["oauth_token_secret"])) {
						register_error($error_msg_no_user);
						break;
					}
					
					$params = array(
						"type" => "user",
						"limit" => 1,
						"site_guids" => false,
						"plugin_id" => "socialink",
						"plugin_user_setting_name_value_pairs" => array(
							"twitter_oauth_token" => $token["oauth_token"],
							"twitter_oauth_secret" => $token["oauth_token_secret"]
						)
					);
					
					$users = elgg_get_entities_from_plugin_user_settings($params);
					if (!empty($users)) {
						$user = $users[0];
					} else {
						$_SESSION["socialink_token"] = $token;
						forward("socialink/no_linked_account/twitter");
					}
					
					break;
				case "linkedin":
					$token = socialink_linkedin_get_access_token(get_input("oauth_verifier"));
						
					if (!isset($token["oauth_token"]) || !isset($token["oauth_token_secret"])) {
						register_error($error_msg_no_user);
						break;
					}
					
					$params = array(
						"type" => "user",
						"limit" => 1,
						"site_guids" => false,
						"plugin_id" => "socialink",
						"plugin_user_setting_name_value_pairs" => array(
							"linkedin_oauth_token" => $token["oauth_token"],
							"linkedin_oauth_secret" => $token["oauth_token_secret"]
						)
					);
					
					$users = elgg_get_entities_from_plugin_user_settings($params);
					if (!empty($users)) {
						$user = $users[0];
					} else {
						$_SESSION["socialink_token"] = $token;
						forward("socialink/no_linked_account/linkedin");
					}
					break;
				case "facebook":
					$token = socialink_facebook_get_access_token();
					
					if (empty($token)) {
						register_error($error_msg_no_user);
						break;
					}
					
					$user_id = socialink_facebook_get_user_id_from_access_token($token);
					if (empty($user_id)) {
						register_error($error_msg_no_user);
						break;
					}
					
					$params = array(
						"type" => "user",
						"limit" => 1,
						"site_guids" => false,
						"plugin_id" => "socialink",
						"plugin_user_setting_name_value_pairs" => array(
							"facebook_user_id" => $user_id
						)
					);
					
					$users = elgg_get_entities_from_plugin_user_settings($params);
					if (!empty($users)) {
						$user = $users[0];
					} else {
						$_SESSION["socialink_token"] = $token;
						forward("socialink/no_linked_account/facebook");
					}
					break;
				case "wordpress":
					$token = socialink_wordpress_get_access_token(get_input("oauth_token"));
					
					if (isset($token['oauth_token']) && isset($token['oauth_token_secret'])) {
						if ($userdata = socialink_wordpress_get_user_data_from_token($token)) {
							
							$params = array(
								"type" => "user",
								"limit" => 1,
								"site_guids" => false,
								"plugin_id" => "socialink",
								"plugin_user_setting_name_value_pairs" => array(
									"wordpress_userid" => $userdata->ID
								)
							);
							
							if ($users = elgg_get_entities_from_plugin_user_settings($params)) {
								$user = $users[0];
								
								socialink_wordpress_update_connection($token, $user->getGUID());
							} else {
								$_SESSION["socialink_token"] = $token;
								forward("socialink/no_linked_account/wordpress");
							}
						}
					} else {
						register_error($error_msg_no_user);
					}
					break;
			}
			
			if (!empty($user) && elgg_instanceof($user, "user")) {
				try {
					
					// permanent login
					login($user, true);
					
					// log last network
					elgg_set_plugin_user_setting("last_login_network", $network, $user->getGUID(), "socialink");

					// sync network data
					elgg_trigger_plugin_hook("socialink:sync", "user", array("user" => $user, "network" => $network));

					// set message and forward to correct page
					system_message(elgg_echo("loginok"));

					if (isset($_SESSION["last_forward_from"]) && $_SESSION["last_forward_from"]) {
						$forward_url = $_SESSION["last_forward_from"];
						unset($_SESSION["last_forward_from"]);
						forward($forward_url);
					} elseif (get_input("returntoreferer")) {
						forward(REFERER);
					} else {
						forward();
					}
				} catch (LoginException $e) {
					// validation mechanisme should report that you are not authenticated. Currently uservalidation by email doesn't do that
					//register_error($e->getMessage());
					forward();
				}
			}
				
			// restore hidden status
			access_show_hidden_entities($access_status);
			
			forward();
			break;
		case "no_linked_account":
			if (elgg_is_logged_in()) {
				forward();
			}
			
			switch ($page[1]) {
				case "linkedin":
				case "facebook":
				case "twitter":
				case "wordpress":
					$result = true;
					
					set_input("network", $page[1]);
					
					include(dirname(dirname(__FILE__)) . "/pages/no_linked_account.php");
					break;
			}
			
			break;
		case "share":
			elgg_gatekeeper();
			
			$result = true;
			
			include(dirname(dirname(__FILE__)) . "/pages/share.php");
			break;
		case "forward":
			if (!isset($page[1]) && !isset($page[2])) {
				// invalid input
				break;
			}
			
			$network = $page[1];
			$action = $page[2];
			
			$allowed_actions = array("login", "authorize");
			
			if (!socialink_is_available_network($network) || !in_array($action, $allowed_actions)) {
				// unavailable network or unsupported action
				break;
			}
			
			if ($action == "login") {
				socialink_prepare_login();
			}
			
			$callback_url = elgg_get_site_url() . "socialink/" . $action . "/" . $network;
			
			$forward_url = "";
			switch ($network) {
				case "linkedin":
					$forward_url = socialink_linkedin_get_authorize_url($callback_url);
					break;
				case "facebook":
					$forward_url = socialink_facebook_get_authorize_url($callback_url);
					break;
				case "twitter":
					$forward_url = socialink_twitter_get_authorize_url($callback_url);
					break;
				case "wordpress":
					$forward_url = socialink_wordpress_get_authorize_url($callback_url);
					break;
			}
			
			forward($forward_url);
			break;
	}
	
	return $result;
}
