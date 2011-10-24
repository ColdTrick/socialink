<?php 

	function socialink_twitter_get_api_object($keys){
		$result = false;
		
		if(!empty($keys) && is_array($keys)){
			$consumer_key = $keys["consumer_key"];
			$consumer_secret = $keys["consumer_secret"];
			
			if(isset($keys["oauth_token"]) && isset($keys["oauth_secret"])){
				$oauth_token = $keys["oauth_token"];
				$oauth_secret = $keys["oauth_secret"];
			} else {
				$oauth_token = null;
				$oauth_secret = null;
			}
			
			$result = new TwitterOAuth($consumer_key, $consumer_secret, $oauth_token, $oauth_secret);
		}
		
		return $result;
	}

	function socialink_twitter_is_connected($user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		if(!empty($user_guid) && ($keys = socialink_twitter_available())){
			$oauth_token = elgg_get_plugin_user_setting("twitter_oauth_token", $user_guid, "socialink");
			$oauth_secret = elgg_get_plugin_user_setting("twitter_oauth_secret", $user_guid, "socialink");
			
			if(!empty($oauth_token) && !empty($oauth_secret)){
				$result = $keys;
				$result["oauth_token"] = $oauth_token;
				$result["oauth_secret"] = $oauth_secret;
			}
		}
		
		return $result;
	}
	
	function socialink_twitter_get_authorize_url($callback = NULL) {
		global $SESSION;
		
		$result = false;
		
		if($keys = socialink_twitter_available()){
			
			if($api = socialink_twitter_get_api_object($keys)){
				try {
					$token = $api->getRequestToken($callback);
					// save token in session for use after authorization
					$SESSION['socialink_twitter'] = array(
						'oauth_token' => $token['oauth_token'],
						'oauth_token_secret' => $token['oauth_token_secret'],
					);
				
					$result = $api->getAuthorizeURL($token['oauth_token']);
				} catch(Exception $e){
					var_dump($e);
				}
			}
		}
		
		return $result;
	}
	
	function socialink_twitter_get_access_token($oauth_verifier = NULL) {
		global $SESSION;
		
		$result = true;
	
		if($keys = socialink_twitter_available()){
			if(isset($SESSION['socialink_twitter'])){
				// retrieve stored tokens
				$keys['oauth_token'] = $SESSION['socialink_twitter']['oauth_token'];
				$keys['oauth_secret'] = $SESSION['socialink_twitter']['oauth_token_secret'];
				$SESSION->offsetUnset('socialink_twitter');
				
				// fetch an access token
				if($api = socialink_twitter_get_api_object($keys)){
					$result = $api->getAccessToken($oauth_verifier);
				}
			} elseif(isset($SESSION["socialink_token"])){
				$result = $SESSION["socialink_token"];
			}
		}
		
		return $result;
	}
	
	function socialink_twitter_authorize($user_guid = 0, $token = null) {
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		$oauth_verifier = get_input('oauth_verifier', NULL);
		
		if(!empty($user_guid) && ($token = socialink_twitter_get_access_token($oauth_verifier))){
			
			if (isset($token["oauth_token"]) && isset($token["oauth_token_secret"])) {
				// only one user per tokens
				$params = array(
					"type" => "user",
					"limit" => false,
					"site_guids" => false,
					"plugin_id" => "socialink",
					"plugin_user_setting_name_value_pairs" => array(
						"twitter_oauth_token" => $token["oauth_token"],
						"twitter_oauth_secret" => $token["oauth_token_secret"]
					)
				);
				
				// find hidden users (just created)
				$access_status = access_get_show_hidden_status();
				access_show_hidden_entities(true);
				
				if ($users = elgg_get_entities_from_plugin_user_settings($params)) {
					foreach ($users as $user) {
						// revoke access
						elgg_unset_plugin_user_setting("twitter_oauth_token", $user->getGUID(), "socialink");
						elgg_unset_plugin_user_setting("twitter_oauth_secret", $user->getGUID(), "socialink");
						elgg_unset_plugin_user_setting("twitter_screen_name", $user->getGUID(), "socialink");
						elgg_unset_plugin_user_setting("twitter_user_id", $user->getGUID(), "socialink");
					}
				}
				
				// restore hidden status
				access_show_hidden_entities($access_status);
					
				// register user"s access tokens
				elgg_set_plugin_user_setting("twitter_user_id", $token["user_id"], $user_guid, "socialink");
				elgg_set_plugin_user_setting("twitter_screen_name", $token["screen_name"], $user_guid, "socialink");
				elgg_set_plugin_user_setting("twitter_oauth_token", $token["oauth_token"], $user_guid, "socialink");
				elgg_set_plugin_user_setting("twitter_oauth_secret", $token["oauth_token_secret"], $user_guid, "socialink");
				
				$result = true;
			}
		}
		
		return $result;
	}
	
	function socialink_twitter_remove_connection($user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		if(!empty($user_guid) && socialink_twitter_is_connected()){
			elgg_unset_plugin_user_setting("twitter_oauth_token", $user_guid, "socialink");
			elgg_unset_plugin_user_setting("twitter_oauth_secret", $user_guid, "socialink");
			elgg_unset_plugin_user_setting("twitter_screen_name", $user_guid, "socialink");
			elgg_unset_plugin_user_setting("twitter_user_id", $user_guid, "socialink");
			
			$result = true;
		}
		
		return $result;
	}
	
	function socialink_twitter_post_message($message, $user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		if(!empty($message) && !empty($user_guid) && ($keys = socialink_twitter_is_connected($user_guid))){
			if($api = socialink_twitter_get_api_object($keys)){
				$url = "statuses/update";
				$params = array("status" => $message);
				
				$result = $api->post($url, $params);
			}
		}
		
		return $result;
	}
	
	function socialink_twitter_get_profile_information($user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		if(!empty($user_guid) && ($keys = socialink_twitter_is_connected($user_guid))){
			if($api = socialink_twitter_get_api_object($keys)){
				$url = "users/show";
				$params = array(
					"screen_name" => elgg_get_plugin_user_setting("twitter_screen_name", $user_guid, "socialink")
				);
				
				if($result = $api->get($url, $params)){
					$result->socialink_profile_url = "http://www.twitter.com/" . $result->screen_name;
				}
			}
		}
		
		return $result;
	}
	
	function socialink_twitter_sync_profile_metadata($user_guid = 0){
		global $CONFIG;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		// can we get a user
		if(($user = get_user($user_guid)) && socialink_twitter_is_connected($user_guid)){
			// does the user allow sync
			if(elgg_get_plugin_user_setting("twitter_sync_allow", $user->getGUID(), "socialink") != "no"){
				// get configured fields and network fields
				if(($configured_fields = socialink_get_configured_network_fields("twitter")) && ($network_fields = socialink_get_network_fields("twitter"))){
					// ask the api for all fields
					if($api_result = socialink_twitter_get_profile_information($user->getGUID())){
						
						// check settings for each field
						foreach($configured_fields as $setting_name => $profile_field){
							$setting = "twitter_sync_" . $setting_name;
							
							if(elgg_get_plugin_user_setting($setting, $user->getGUID(), "socialink") != "no"){
								$api_setting = $network_fields[$setting_name];
								
								// get the correct value from api result
								$temp_result = $api_result->$api_setting;
								
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
	
	function socialink_twitter_create_user($token, $email){
		$result = false;
		
		if(!empty($token) && is_array($token) && !empty($email)){
			if(!get_user_by_email($email) && is_email_address($email)){
				$keys = socialink_twitter_available();
				
				$keys["oauth_token"] = $token["oauth_token"];
				$keys["oauth_secret"] = $token["oauth_token_secret"];
				
				if($api = socialink_twitter_get_api_object($keys)){
					try {
						$url = "users/show";
						$params = array(
							"screen_name" => $token["screen_name"]
						);
						$api_result = $api->get($url, $params);
					} catch(Exception $e){}
					
					if(!empty($api_result)){
						$name = $api_result->name;
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
									elgg_set_plugin_user_setting('twitter_user_id', $token['user_id'], $user_guid, "socialink");
									elgg_set_plugin_user_setting('twitter_screen_name', $token['screen_name'], $user_guid, "socialink");
									elgg_set_plugin_user_setting('twitter_oauth_token', $token['oauth_token'], $user_guid, "socialink");
									elgg_set_plugin_user_setting('twitter_oauth_secret', $token['oauth_token_secret'], $user_guid, "socialink");
									
									// return the user
									$result = $user;
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
	
	function socialink_twitter_validate_connection($user_guid = 0){
		global $CONFIG;
		$result = true;
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		// can we get a user
		if($keys = socialink_twitter_is_connected($user_guid)){
			if($api = socialink_twitter_get_api_object($keys)){
				try {
					$url = "account/verify_credentials";
					$response = $api->get($url, $params);
					
					if(!empty($response) && !empty($response->error)){
						$result = false;	
					}
				} catch (Exception $e){}
			}
		}
		return $result;		
	}
	
	function socialink_twitter_in_cron_hook($hook, $type, $returnvalue, $params){
		global $CONFIG, $SESSION;
		$result = $returnvalue;
		
		$setting_name = "plugin:settings:socialink:twitter_in";
		$setting_value = "yes";
		
		$options = array(
			"type" => "user",
			"limit" => false,
			"relationship" => "member_of_site",
			"relationship_guid" => $CONFIG->site_guid,
			"inverse_relationship" => true,
			"joins" => array("JOIN " . $CONFIG->dbprefix . "private_settings ps ON e.guid = ps.entity_guid"),
			"wheres" => array("(ps.name='" . $setting_name . "' AND ps.value = '" . $setting_value . "')")
		);
		
		if($users = elgg_get_entities_from_relationship($options)){
			// this could take a long time
			set_time_limit(0);
			
			foreach($users as $user){
				$SESSION["user"] = $user;
				
				if($keys = socialink_twitter_is_connected($user->getGUID())){
					$since_id = elgg_get_plugin_user_setting("twitter_in_since_id", $user->getGUID(), "socialink");
					$filter = elgg_get_plugin_user_setting("twitter_in_filter", $user->getGUID(), "socialink");
					
					if($api = socialink_twitter_get_api_object($keys)){
						try {
							$url = "statuses/user_timeline";
							$params = array(
								"since_id" => $since_id,
								"include_rts" => true,
								"count" => 200
							);
							
							$response = $api->get($url, $params);
							
							if(!empty($response) && empty($response->error)){
								// proces tweets oldest to newest
								$response = array_reverse($response);
								
								// determen access_id
								$access_id = get_default_access($user);
								if($access_id == ACCESS_PRIVATE){
									$access_id = ACCESS_LOGGED_IN;
								}
								
								foreach($response as $tweet){
									$since_id = $tweet->id_str;
									
									$text = elgg_substr(strip_tags($tweet->text), 0, 160);
									
									if(empty($filter) || stristr($text, $filter)){
										$post = new ElggObject();
										$post->subtype = "thewire";
										$post->owner_guid = $user->getGUID();
										$post->container_guid = $user->getGUID();
										
										$post->access_id = $access_id;
										
										$post->description = $text;
										
										$post->method = "Twitter";
										$post->parent = 0;
										
										if($post->save()){
											if($created = strtotime($tweet->created_at)){
												
												$sql = "UPDATE " . $CONFIG->dbprefix . "entities SET time_created = " . $created . ", time_updated = " . $created . " WHERE guid = " . $post->getGUID();
												
												update_data($sql);
											}
										}
									}
								}
								
								// update since_id
								elgg_set_plugin_user_setting("twitter_in_since_id", $since_id, $user->getGUID(), "socialink");
							}
						} catch(Exception $e){}
					}
				}
				
				unset($SESSION["user"]);
			}
		}
		
		return $result;
	}
	
	function socialink_twitter_usersettings_save($hook, $type, $returnvalue, $params){
		$result = $returnvalue;
		
		if(!empty($params) && is_array($params)){
			if(array_key_exists("user", $params) && array_key_exists("plugin", $params) && array_key_exists("name", $params) && array_key_exists("value", $params)){
				$user = $params["user"];
				$plugin = $params["plugin"];
				$name = $params["name"];
				$value = $params["value"];
				
				if(($plugin == "socialink") && ($name = "twitter_in") && ($value == "yes")){
					$prev = elgg_get_plugin_user_setting("twitter_in", $user->getGUID(), "socialink");
					
					if($prev != "yes"){
						if($keys = socialink_twitter_is_connected($user->getGUID())){
							if($api = socialink_twitter_get_api_object($keys)){
								try {
									$url = "statuses/user_timeline";
									$params = array(
										"include_rts" => true,
										"count" => 1
									);
									
									$response = $api->get($url, $params);
									
									if(!empty($response) && empty($response->error)){
										foreach($response as $tweet){
											elgg_set_plugin_user_setting("twitter_in_since_id", $tweet->id_str, $user->getGUID(), "socialink");
											break;
										}
									}
								} catch(Exception $e){}
							}
						}
					}
				}
			}
		}
		
		return $result;
	}
	
	// register plugin hooks
	elgg_register_plugin_hook_handler("plugin:usersetting", "user", "socialink_twitter_usersettings_save");
