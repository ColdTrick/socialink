<?php 

	define("FACEBOOK_OAUTH_BASE_URL", "https://www.facebook.com/dialog/oauth?client_id=");

	function socialink_facebook_get_api_object($keys){
		$result = false;
		
		if(!empty($keys) && is_array($keys)){
			$config = array(
				"appId" => $keys["app_id"],
				"secret" => $keys["app_secret"]
			);
			
			$result = new FacebookProxy($config);
			
			if($proxy_settings = socialink_get_proxy_settings()){
				$result->setProxySettings($proxy_settings);
			}
		}
		
		return $result;
	}
	
	function socialink_facebook_is_connected($user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		if(!empty($user_guid) && ($keys = socialink_facebook_available())){
			$token = elgg_get_plugin_user_setting("facebook_access_token", $user_guid, "socialink");
			
			if(!empty($token)){
				$result = $keys;
				$result["access_token"] = $token;
			}
		}
		
		return $result;
	}
	
	function socialink_facebook_get_authorize_url($callback){
		global $SESSION;
		
		$result = false;
		
		if(!empty($callback) && ($keys = socialink_facebook_available())){
			if($api = socialink_facebook_get_api_object($keys)){
				$state = generate_action_token(time());
				$app_id = $api->getAppId();
				$callback = urlencode($callback);
				$scope = "offline_access,publish_stream,user_about_me,user_location,email";
				
				$SESSION["socialink_facebook"] = array(
					"state" => $state,
					"callback" => $callback
				);
				
				$result = FACEBOOK_OAUTH_BASE_URL . $app_id . "&redirect_uri=" . $callback . "&state=" . $state . "&scope=" . $scope;
			}
		}
		
		return $result;
	}
	
	function socialink_facebook_get_access_token($state = NULL) {
		global $SESSION;
		
		$result = false;
		
		$error = get_input("error");
		
		if(empty($error) && ($keys = socialink_facebook_available())){
			if(isset($SESSION["socialink_facebook"]) && isset($SESSION["socialink_facebook"]["state"])){
				$session_state = $SESSION["socialink_facebook"]["state"];
				$session_callback = $SESSION["socialink_facebook"]["callback"];
				
				if($state == $session_state){
					$SESSION->offsetUnset('socialink_facebook');
					// fetch an access token
					if($api = socialink_facebook_get_api_object($keys)){
						
						$url = "oauth/access_token";
						$params = array(
							"client_id" => $api->getAppId(),
							"client_secret" => $api->getApiSecret(),
							"code" => get_input("code")
						);
						
						$base_url = Facebook::$DOMAIN_MAP["graph"];
						
						$url_post_fix = http_build_query($params, null, "&");
						// session callback not in params as it is already html encoded
						$url = $base_url . $url . "?redirect_uri=" . $session_callback . "&" . $url_post_fix;
						
						$ch = curl_init($url);
						curl_setopt($ch, CURLOPT_HEADER, false);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						
						$response = curl_exec($ch);
						curl_close($ch);
						
						if(!empty($response)){
							list($dummy, $token) = explode("=", $response);
							
							if(!empty($token)){
								$result = $token;
							}
						}
					}
				}
			} elseif(isset($SESSION["socialink_token"])) {
				$result = $SESSION["socialink_token"];
			}
		} elseif(!empty($error)){
			$msg = urldecode(get_input("error_description"));
			register_error($msg);
		}
		
		return $result;
	}

	function socialink_facebook_authorize($user_guid = 0) {
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		$state = get_input('state', NULL);
		
		if(!empty($user_guid) && ($token = socialink_facebook_get_access_token($state))){
			// only one user per tokens
			$params = array(
				"type" => "user",
				"limit" => false,
				"site_guids" => false,
				"plugin_id" => "socialink",
				"plugin_user_setting_name_value_pairs" => array(
					"facebook_access_token" => $token
				)
			);
			
			// find hidden users (just created)
			$access_status = access_get_show_hidden_status();
			access_show_hidden_entities(true);
			
			if ($users = elgg_get_entities_from_plugin_user_settings($params)) {
				foreach ($users as $user) {
					// revoke access
					elgg_unset_plugin_user_setting("facebook_access_token", $user->getGUID(), "socialink");
				}
			}
			
			// restore hidden status
			access_show_hidden_entities($access_status);
			
			// register user's access tokens
			$result = elgg_set_plugin_user_setting("facebook_access_token", $token, $user_guid, "socialink");
		}
		
		return $result;
	}

	function socialink_facebook_remove_connection($user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		if(!empty($user_guid) && socialink_facebook_is_connected($user_guid)){
			// remove plugin settings
			$result = elgg_unset_plugin_user_setting("facebook_access_token", $user_guid, "socialink");
		}
		
		return $result;
	}

	function socialink_facebook_post_message($message, $user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		if(!empty($message) && !empty($user_guid) && ($keys = socialink_facebook_is_connected($user_guid))){
			if($api = socialink_facebook_get_api_object($keys)){
				$url = "me/feed";
				$method = "POST";
				$params = array(
					"access_token" => $keys["access_token"],
					"message" => $message,
				);
				
				try {
					$result = $api->api($url, $method, $params);
				} catch(Exception $e){}
			}
		}
		
		return $result;
	}
	
	function socialink_facebook_get_profile_information($user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		if(!empty($user_guid) && ($keys = socialink_facebook_is_connected($user_guid))){
			if($api = socialink_facebook_get_api_object($keys)){
				$url = "me";
				$params = array(
					"access_token" => $keys["access_token"],
					"fields" => "name,first_name,last_name,link,email,location,gender,about,bio,hometown"
				);
				
				try{
					$result = $api->api($url, $params);
				} catch(Exception $e){}
			}
		}
		
		return $result;
	}
	
	function socialink_facebook_sync_profile_metadata($user_guid = 0){
		global $CONFIG;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		// can we get a user
		if(($user = get_user($user_guid)) && socialink_facebook_is_connected($user_guid)){
			// does the user allow sync
			if(elgg_get_plugin_user_setting("facebook_sync_allow", $user->getGUID(), "socialink") != "no"){
				// get configured fields and network fields
				if(($configured_fields = socialink_get_configured_network_fields("facebook")) && ($network_fields = socialink_get_network_fields("facebook"))){
					// ask the api for all fields
					if($api_result = socialink_facebook_get_profile_information($user->getGUID())){
						
						// check settings for each field
						foreach($configured_fields as $setting_name => $profile_field){
							$setting = "facebook_sync_" . $setting_name;
							
							if(elgg_get_plugin_user_setting($setting, $user->getGUID(), "socialink") != "no"){
								$api_setting = $network_fields[$setting_name];
								
								$temp_result = $api_result[$api_setting];
								
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
				
				// sync profile icon, only if the user has no icon
				if(empty($user->icontime)){
					socialink_facebook_sync_profile_icon($user->getGUID());
				}
			}
		}
	}
	
	function socialink_facebook_sync_profile_icon($user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		if(($user = get_user($user_guid)) && ($keys = socialink_facebook_is_connected($user_guid))){
			if($profile_info = socialink_facebook_get_profile_information($user_guid)){
				$url = Facebook::$DOMAIN_MAP["graph"] . $profile_info["id"] . "/picture/";
				
				$params = array(
					"type" => "large"
				);
				
				$url = elgg_http_add_url_query_elements($url, $params);
				
				if(file_get_contents($url)){
					$icon_sizes = elgg_get_config("icon_sizes");
					
					if(!empty($icon_sizes)){
						$fh = new ElggFile();
						$fh->owner_guid = $user->getGUID();
						
						foreach($icon_sizes as $name => $properties){
							$resize = get_resized_image_from_existing_file($url, $properties["w"], $properties["h"], $properties["square"], 0, 0, 0, 0, $properties["upscale"]);
							
							if(!empty($resize)){
								$fh->setFilename("profile/" . $user->getGUID() . $name . ".jpg");
								$fh->open("write");
								$fh->write($resize);
								$fh->close();
					
								$result = true;
							}
						}
					}
						
					if(!empty($result)){
						$user->icontime = time();
					
						// trigger event to let others know the icon was updated
						elgg_trigger_event("profileiconupdate", $user->type, $user);
					}
				}
			}
		}
		
		return $result;
	}
	
	function socialink_facebook_create_user($token){
		$result = false;
		
		if(!empty($token)){
			if($keys = socialink_facebook_available()){
				if($api = socialink_facebook_get_api_object($keys)){
					$url = "me";
					$params = array(
						"access_token" => $token,
						"fields" => "name,email"
					);
					
					try {
						$api_result = $api->api($url, $params);
					} catch(Exception $e){}
					
					if(!empty($api_result)){
						$name = $api_result["name"];
						$email = $api_result["email"];
						
						if(!get_user_by_email($email)){
							$pwd = generate_random_cleartext_password();
							$username = socialink_create_username_from_email($email);
							
							try {
								if($user_guid = register_user($username, $pwd, $name, $email)){
									// show hidden entities
									$access = access_get_show_hidden_status();
									access_show_hidden_entities(TRUE);
									
									if($user = get_user($user_guid)){
										// register user's access tokens
										elgg_set_plugin_user_setting('facebook_access_token', $token, $user_guid, "socialink");
										
										// no need for uservalidationbyemail
										elgg_unregister_plugin_hook_handler("register", "user", "uservalidationbyemail_disable_new_user");
										
										// sync user data
										socialink_facebook_sync_profile_metadata($user->getGUID());
										
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
						} else {
							register_error(elgg_echo("socialink:networks:create_user:error:email"));
						}
					}
				}
			}
		}
		
		return $result;
	}

	function socialink_facebook_validate_connection($user_guid = 0){
		$result = true;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		// can we get a user
		if($keys = socialink_facebook_is_connected($user_guid)){
			if($api = socialink_facebook_get_api_object($keys)){
				
				$url = "me";
				$params = array(
					"access_token" =>  $keys["access_token"],
					"fields" => "name"
				);
				
				try {
					$api_result = $api->api($url, $params);
				} catch(Exception $e){
					$result = false;	
				}
			}
		}
		return $result;		
	}
	