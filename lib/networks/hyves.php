<?php

	define("SOCIALINK_HYVES_API_VERSION", "2.1");

	function socialink_hyves_get_api_object($keys){
		$result = false;
	
		if(!empty($keys) && is_array($keys)){
			$oauth_consumer = new GenusOAuthConsumer($keys["consumer_key"], $keys["consumer_secret"]);
			
			$result = new GenusApis($oauth_consumer, SOCIALINK_HYVES_API_VERSION);
		}
	
		return $result;
	}
	
	function socialink_hyves_is_connected($user_guid = 0){
		$result = false;
	
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
	
		if(!empty($user_guid) && ($keys = socialink_hyves_available())){
			$oauth_token = elgg_get_plugin_user_setting("hyves_oauth_token", $user_guid, "socialink");
			$oauth_secret = elgg_get_plugin_user_setting("hyves_oauth_secret", $user_guid, "socialink");
			$user_id = elgg_get_plugin_user_setting("hyves_user_id", $user_guid, "socialink");
			$methods = elgg_get_plugin_user_setting("hyves_methods", $user_guid, "socialink");
			$expires = elgg_get_plugin_user_setting("hyves_expires", $user_guid, "socialink");
				
			if(!empty($oauth_token) && !empty($oauth_secret)){
				$result = $keys;
				$result["oauth_token"] = $oauth_token;
				$result["oauth_secret"] = $oauth_secret;
				$result["user_id"] = $user_id;
				$result["methods"] = unserialize($methods);
				$result["expires"] = $expires;
			}
		}
	
		return $result;
	}
	
	function socialink_hyves_get_authorize_url($callback = NULL) {
		global $SESSION;
	
		$result = false;
		
		if($keys = socialink_hyves_available()){
			if($api = socialink_hyves_get_api_object($keys)){
				try {
					$methods = array(
						"users.get"
					);
					
					$token = $api->retrieveRequesttoken($methods, "infinite");
					
					// save token in session for use after authorization
					$SESSION['socialink_hyves'] = serialize($token);
					
					$result = $api->getAuthorizeUrl($token, $callback);
				} catch(Exception $e){
					var_dump($e);
				}
			}
		}
	
		return $result;
	}
	
	function socialink_hyves_get_access_token($oauth_verifier = NULL) {
		global $SESSION;
	
		$result = true;
		
		if($keys = socialink_hyves_available()){
			if(isset($SESSION['socialink_hyves'])){
				// retrieve stored tokens
				$token = unserialize($SESSION['socialink_hyves']);
				$SESSION->offsetUnset('socialink_hyves');
				
				// fetch an access token
				if($api = socialink_hyves_get_api_object($keys)){
					$result = $api->retrieveAccesstoken($token);
				}
			} elseif(isset($SESSION["socialink_token"])){
				$result = unserialize($SESSION["socialink_token"]);
			}
		}
	
		return $result;
	}
	
	function socialink_hyves_authorize($user_guid = 0, $token = null) {
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		$oauth_verifier = get_input('oauth_verifier', NULL);
		
		if(!empty($user_guid) && ($token = socialink_hyves_get_access_token($oauth_verifier))){
			if ($token->getKey() && $token->getSecret()) {
				// only one user per tokens
				$params = array(
					"type" => "user",
					"limit" => false,
					"site_guids" => false,
					"plugin_id" => "socialink",
					"plugin_user_setting_name_value_pairs" => array(
						"hyves_oauth_token" => $token->getKey(),
						"hyves_oauth_secret" => $token->getSecret()
					)
				);
				
				// find hidden users (just created)
				$access_status = access_get_show_hidden_status();
				access_show_hidden_entities(true);
				
				if ($users = elgg_get_entities_from_plugin_user_settings($params)) {
					foreach ($users as $user) {
						// revoke access
						elgg_unset_plugin_user_setting("hyves_oauth_token", $user->getGUID(), "socialink");
						elgg_unset_plugin_user_setting("hyves_oauth_secret", $user->getGUID(), "socialink");
						elgg_unset_plugin_user_setting("hyves_user_id", $user->getGUID(), "socialink");
						elgg_unset_plugin_user_setting("hyves_methods", $user->getGUID(), "socialink");
						elgg_unset_plugin_user_setting("hyves_expires", $user->getGUID(), "socialink");
					}
				}
				
				// restore hidden status
				access_show_hidden_entities($access_status);
					
				// register user"s access tokens
				elgg_set_plugin_user_setting("hyves_user_id", $token->getUserid(), $user_guid, "socialink");
				elgg_set_plugin_user_setting("hyves_oauth_token", $token->getKey(), $user_guid, "socialink");
				elgg_set_plugin_user_setting("hyves_oauth_secret", $token->getSecret(), $user_guid, "socialink");
				elgg_set_plugin_user_setting("hyves_methods", serialize($token->getMethods()), $user_guid, "socialink");
				elgg_set_plugin_user_setting("hyves_expires", $token->getExpiredate(), $user_guid, "socialink");
	
				$result = true;
			}
		}
	
		return $result;
	}
	
	function socialink_hyves_remove_connection($user_guid = 0){
		$result = false;
	
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
	
		if(!empty($user_guid) && socialink_hyves_is_connected()){
			elgg_unset_plugin_user_setting("hyves_oauth_token", $user_guid, "socialink");
			elgg_unset_plugin_user_setting("hyves_oauth_secret", $user_guid, "socialink");
			elgg_unset_plugin_user_setting("hyves_user_id", $user_guid, "socialink");
			elgg_unset_plugin_user_setting("hyves_methods", $user_guid, "socialink");
			elgg_unset_plugin_user_setting("hyves_expires", $user_guid, "socialink");
				
			$result = true;
		}
	
		return $result;
	}
	
	function socialink_hyves_get_profile_information($user_guid = 0){
		$result = false;
	
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		if(!empty($user_guid) && ($keys = socialink_hyves_is_connected($user_guid))){
			if($api = socialink_hyves_get_api_object($keys)){
				$token = new GenusOAuthAccessToken($keys["oauth_token"], $keys["oauth_secret"], $keys["user_id"], $keys["methods"], $keys["expires"]);
				
				$method = "users.get";
				$params = array(
					"userid" => $token->getUserid(),
					"ha_responsefields" => "cityname,countryname,tags,aboutme"
				);
				
				try {
					if($api_result = $api->doMethod($method, $params, $token)){
						$result = $api_result->user;
					}
				} catch(Exception $e){}
			}
		}
	
		return $result;
	}
	
	function socialink_hyves_sync_profile_metadata($user_guid = 0){
		global $CONFIG;
	
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		// can we get a user
		if(($user = get_user($user_guid)) && socialink_hyves_is_connected($user_guid)){
			// does the user allow sync
			if(elgg_get_plugin_user_setting("hyves_sync_allow", $user->getGUID(), "socialink") != "no"){
				// get configured fields and network fields
				if(($configured_fields = socialink_get_configured_network_fields("hyves")) && ($network_fields = socialink_get_network_fields("hyves"))){
					// ask the api for all fields
					if($api_result = socialink_hyves_get_profile_information($user->getGUID())){
						// check settings for each field
						foreach($configured_fields as $setting_name => $profile_field){
							$setting = "hyves_sync_" . $setting_name;
							
							if(elgg_get_plugin_user_setting($setting, $user->getGUID(), "socialink") != "no"){
								$api_setting = $network_fields[$setting_name];
								
								// get the correct value from api result
								if(stristr($api_setting, "->")){
									$temp_fields = explode("->", $api_setting);
									$temp_result = $api_result;
									
									for($i = 0; $i < count($temp_fields); $i++){
										$temp_result = $temp_result->$temp_fields[$i];
									}
								} else {
									$temp_result = $api_result->$api_setting;
								}
								
								// are we dealing with a tags profile field type
								if(!empty($CONFIG->profile) && is_array($CONFIG->profile)){
									if(array_key_exists($profile_field, $CONFIG->profile) && $CONFIG->profile[$profile_field] == "tags"){
										$temp_result = string_to_tag_array($temp_result);
									}
								}
								// check if the user has this metadata field, to get access id
								$params = array(
									"guid" => $user->getGUID(),
									"metadata_name" => $profile_field,
									"limit" => false
								);
	
								if($metadata = elgg_get_metadata($params)){
									if(is_array($metadata)){
										$access_id = $metadata[0]->access_id;
									} else {
										$access_id = $metadata->access_id;
									}
								} else {
									$access_id = get_default_access($user);
								}
	
								// remove metadata to set new values
								elgg_delete_metadata($params);
	
								// make new metadata field
								if(!empty($temp_result)){
									if(is_array($temp_result)){
										foreach($temp_result as $index => $temp_value){
											if($index > 0){
												$multiple = true;
											} else {
												$multiple = false;
											}
												
											create_metadata($user->getGUID(), $profile_field, $temp_value, 'text', $user->getGUID(), $access_id, $multiple);
										}
									} else {
										create_metadata($user->getGUID(), $profile_field, $temp_result, 'text', $user->getGUID(), $access_id);
									}
								}
							}
						}
					}
				}
			}
		}
	}
	
	function socialink_hyves_create_user(GenusOAuthAccessToken $token, $email){
		$result = false;
		
		if(!empty($token) && !empty($email)){
			if(!get_user_by_email($email) && is_email_address($email)){
				$keys = socialink_hyves_available();
	
				if($api = socialink_hyves_get_api_object($keys)){
					$method = "users.get";
					$params = array(
						"userid" => $token->getUserid()
					);
					
					try {
						if($responce = $api->doMethod($method, $params, $token)){
							$api_result = $responce->user;
						}
					} catch(Exception $e){}
							
					if(!empty($api_result)){
						$name = $api_result->firstname . " " . $api_result->lastname;
						$pwd = generate_random_cleartext_password();
	
						$username = socialink_create_username_from_email($email);
	
						try {
							// register user
							if($user_guid = register_user($username, $pwd, $name, $email)){
								// show hidden entities
								$access = access_get_show_hidden_status();
								access_show_hidden_entities(TRUE);
	
								if($user = get_user($user_guid)){
									// save user tokens
									elgg_set_plugin_user_setting("hyves_user_id", $token->getUserid(), $user_guid, "socialink");
									elgg_set_plugin_user_setting("hyves_oauth_token", $token->getKey(), $user_guid, "socialink");
									elgg_set_plugin_user_setting("hyves_oauth_secret", $token->getSecret(), $user_guid, "socialink");
									elgg_set_plugin_user_setting("hyves_methods", serialize($token->getMethods()), $user_guid, "socialink");
									elgg_set_plugin_user_setting("hyves_expires", $token->getExpiredate(), $user_guid, "socialink");
									
									// trigger hook for registration
									$params = array(
										"user" => $user,
										"password" => $pwd,
										"friend_guid" => 0,
										"invitecode" => ""
									);
										
									if(elgg_trigger_plugin_hook("register", "user", $params, true) !== false){
										// return the user
										$result = $user;
									}
								}
	
								// restore hidden entities
								access_show_hidden_entities($access);
							}
						} catch(Exception $e){}
					}
				}
			} else {
				register_error(elgg_echo("socialink:networks:create_user:error:email"));
			}
		}
	
		return $result;
	}
	
	function socialink_hyves_validate_connection($user_guid = 0){
		$result = true;
	
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
	
		// can we get a user
		if($keys = socialink_hyves_is_connected($user_guid)){
			if($api = socialink_hyves_get_api_object($keys)){
				$token = new GenusOAuthAccessToken($keys["oauth_token"], $keys["oauth_secret"], $keys["user_id"], $keys["methods"], $keys["expires"]);
				
				$method = "users.get";
				$params = array(
					"userid" => $token->getUserid()
				);
				
				try {
					$api_result = $api->doMethod($method, $params, $token);
				} catch(Exception $e){
					$result = false;
				}
			}
		}
		return $result;
	}