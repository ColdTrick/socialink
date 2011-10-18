<?php 

	if(!class_exists("LinkedIn")){
		require_once(dirname(dirname(dirname(__FILE__))) . "/vendors/simple_linkedin/linkedin_3.0.1.class.php");
	}
	
	function socialink_linkedin_get_api_object($keys){
		$result = false;
		
		if(!empty($keys) && is_array($keys)){
			$api_config = array(
				"appKey" => $keys["consumer_key"],
				"appSecret" => $keys["consumer_secret"]
			);
			
			try {
				$api = new LinkedIn($api_config);
				
				if(isset($keys["oauth_token"]) && isset($keys["oauth_secret"])){
					$tokens = array(
						"oauth_token" => $keys["oauth_token"],
						"oauth_token_secret" => $keys["oauth_secret"]
					);
					
					$api->setTokenAccess($tokens);
				}
				
				// set response format to JSON 
				$api->setResponseFormat(LinkedIn::_RESPONSE_JSON);
				
				$result = $api;
			} catch(Exception $e){}
		}
		
		return $result;
	}

	function socialink_linkedin_is_connected($user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = get_loggedin_userid();
		}
		
		if(!empty($user_guid) && ($keys = socialink_linkedin_available())){
			$oauth_token = get_plugin_usersetting("linkedin_oauth_token", $user_guid, "socialink");
			$oauth_secret = get_plugin_usersetting("linkedin_oauth_secret", $user_guid, "socialink");
			
			if(!empty($oauth_token) && !empty($oauth_secret)){
				$result = $keys;
				$result["oauth_token"] = $oauth_token;
				$result["oauth_secret"] = $oauth_secret;
			}
		}
		
		return $result;
	}
	
	function socialink_linkedin_verify_response($response){
		$result = false;
		
		if(!empty($response) && is_array($response)){
			if(array_key_exists("success", $response)){
				if($response["success"] === true){
					$result = $response["linkedin"];
				}
			}
		}
		
		return $result;
	}
	
	function socialink_linkedin_get_authorize_url($callback = NULL) {
		global $SESSION;
		
		$result = false;
	
		if($keys = socialink_linkedin_available()){
			if($api = socialink_linkedin_get_api_object($keys)){
				try {
					$api->setCallbackUrl($callback);
					$response = $api->retrieveTokenRequest();
					
					if($token = socialink_linkedin_verify_response($response)){
						//$token = $response["linkedin"];
						
						// save token in session for use after authorization
						$SESSION['socialink_linkedin'] = array(
							'oauth_token' => $token["oauth_token"],
							'oauth_token_secret' => $token["oauth_token_secret"],
						);
					
						$result = LinkedIn::_URL_AUTH . $token["oauth_token"];
					}
				} catch(Exception $e){}
			}
		}
		
		return $result;
	}
	
	function socialink_linkedin_get_access_token($oauth_verifier = NULL) {
		global $SESSION;
		
		$result = false;
	
		if($keys = socialink_linkedin_available()){
			if(isset($SESSION["socialink_linkedin"])){
				if($api = socialink_linkedin_get_api_object($keys)){
					// retrieve stored tokens
					$oauth_token = $SESSION['socialink_linkedin']['oauth_token'];
					$oauth_token_secret = $SESSION['socialink_linkedin']['oauth_token_secret'];
					$SESSION->offsetUnset('socialink_linkedin');
				
					// fetch an access token
					try {
						$response = $api->retrieveTokenAccess($oauth_token, $oauth_token_secret, $oauth_verifier);
						
						$result = socialink_linkedin_verify_response($response);
					} catch(Exception $e){}
				}
			} elseif($SESSION["socialink_token"]) {
				$result = $SESSION["socialink_token"];
			}
		}
		
		return $result;
	}
	
	function socialink_linkedin_authorize($user_guid = 0) {
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = get_loggedin_userid();
		}
		
		$oauth_verifier = get_input('oauth_verifier', NULL);
		
		if(!empty($user_guid) && ($token = socialink_linkedin_get_access_token($oauth_verifier))){
			if (isset($token['oauth_token']) && isset($token['oauth_token_secret'])) {
				// only one user per tokens
				$values = array(
					'plugin:settings:socialink:linkedin_oauth_token' => $token['oauth_token'],
					'plugin:settings:socialink:linkedin_oauth_secret' => $token['oauth_token_secret'],
				);
			
				// find hidden users (just created)
				$access_status = access_get_show_hidden_status();
				access_show_hidden_entities(true);
				
				if ($users = get_entities_from_private_setting_multi($values, 'user', '', 0, '', false, 0, false, -1)) {
					foreach ($users as $user) {
						// revoke access
						set_plugin_usersetting('linkedin_oauth_token', NULL, $user->getGUID(), "socialink");
						set_plugin_usersetting('linkedin_oauth_secret', NULL, $user->getGUID(), "socialink");
					}
				}
				
				// restore hidden status
				access_show_hidden_entities($access_status);
				
				// register user's access tokens
				set_plugin_usersetting('linkedin_oauth_token', $token['oauth_token'], $user_guid, "socialink");
				set_plugin_usersetting('linkedin_oauth_secret', $token['oauth_token_secret'], $user_guid, "socialink");
			
				$result = true;
			}
		}
		
		return $result;
	}
	
	function socialink_linkedin_remove_connection($user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = get_loggedin_userid();
		}
		
		if(!empty($user_guid) && ($keys = socialink_linkedin_is_connected($user_guid))){
			if($api = socialink_linkedin_get_api_object($keys)){
				// remove LinkedIn subscription
				try {
					$api->revoke();
				} catch(Exception $e){}
			}
			
			// remove plugin settings
			set_plugin_usersetting("linkedin_oauth_token", null, $user_guid, "socialink");
			set_plugin_usersetting("linkedin_oauth_secret", null, $user_guid, "socialink");
			
			$result = true;
		}
		
		return $result;
	}

	function socialink_linkedin_post_message($message, $user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = get_loggedin_userid();
		}
		
		if(!empty($message) && !empty($user_guid) && ($keys = socialink_linkedin_is_connected($user_guid))){
			if($api = socialink_linkedin_get_api_object($keys)){
				$api->setResponseFormat(LinkedIn::_RESPONSE_XML); // update network does not support JSON response
				try{
					$response = $api->updateNetwork($message);
					$result = socialink_linkedin_verify_response($response);
				} catch(Exception $e){ }
			}
		}
		
		return $result;
	}
	
	function socialink_linkedin_get_profile_information($user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = get_loggedin_userid();
		}
		
		if(!empty($user_guid) && ($keys = socialink_linkedin_is_connected($user_guid))){
			if($api = socialink_linkedin_get_api_object($keys)){
				try {
					$response = $api->profile("~:(first-name,last-name,industry,public-profile-url,location:(name))");
					
					if($result = socialink_linkedin_verify_response($response)){
						$temp = json_decode($result);
						$temp->socialink_name = ucwords($temp->firstName . " " . $temp->lastName);
						$result = json_encode($temp);
					}
				} catch(Exception $e){}
			}
		}
		
		return $result;
	}
	
	function socialink_linkedin_sync_profile_metadata($user_guid = 0){
		global $CONFIG;
		
		if(empty($user_guid)){
			$user_guid = get_loggedin_userid();
		}
		
		// can we get a user
		if(($user = get_user($user_guid)) && socialink_linkedin_is_connected($user_guid)){
			// does the user allow sync
			if(get_plugin_usersetting("linkedin_sync_allow", $user->getGUID(), "socialink") != "no"){
				// get configured fields and network fields
				if(($configured_fields = socialink_get_configured_network_fields("linkedin")) && ($network_fields = socialink_get_network_fields("linkedin"))){
					// ask the api for all fields
					if($api_result = socialink_linkedin_get_profile_information($user->getGUID())){
						$api_result = json_decode($api_result);
						
						// check settings for each field
						foreach($configured_fields as $setting_name => $profile_field){
							$setting = "linkedin_sync_" . $setting_name;
							
							if(get_plugin_usersetting($setting, $user->getGUID(), "socialink") != "no"){
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
								if($metadata = get_metadata_byname($user->getGUID(), $profile_field)){
									if(is_array($metadata)){
										$access_id = $metadata[0]->access_id;
									} else {
										$access_id = $metadata->access_id;
									}
								} else {
									$access_id = get_default_access($user);
								}
								
								// remove metadata to set new values
								remove_metadata($user->getGUID(), $profile_field);
								
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
	
	function socialink_linkedin_create_user($token, $email){
		$result = false;
		
		if(!empty($token) && is_array($token) && !empty($email)){
			if(!get_user_by_email($email) && is_email_address($email)){
				$keys = socialink_linkedin_available();
				
				$keys["oauth_token"] = $token["oauth_token"];
				$keys["oauth_secret"] = $token["oauth_token_secret"];
				
				if($api = socialink_linkedin_get_api_object($keys)){
					try {
						$response = $api->profile("~:(first-name,last-name)");
					} catch(Exception $e){}
					
					if($api_result = socialink_linkedin_verify_response($response)){
						$api_result = json_decode($api_result);
						
						$name = $api_result->firstName . " " . $api_result->lastName;
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
									set_plugin_usersetting('linkedin_oauth_token', $token['oauth_token'], $user_guid, "socialink");
									set_plugin_usersetting('linkedin_oauth_secret', $token['oauth_token_secret'], $user_guid, "socialink");
									
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
	
	function socialink_linkedin_validate_connection($user_guid = 0){
		global $CONFIG;
		$result = true;
		
		if(empty($user_guid)){
			$user_guid = get_loggedin_userid();
		}
		
		// can we get a user
		if($keys = socialink_linkedin_is_connected($user_guid)){
			if($api = socialink_linkedin_get_api_object($keys)){
				try {
					$response = $api->profile("~:(first-name,last-name)");
					$result = socialink_linkedin_verify_response($response);
				} catch(Exception $e){}
			}
		}
		return $result;		
	}

?>