<?php

	if(!class_exists("OpenBibId")){
		require_once(dirname(dirname(dirname(__FILE__))) . "/vendors/openbibid/openBibId.php");
	}
	
	function socialink_openbibid_get_api_object($keys){
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
				
			$result = new openBibId($consumer_key, $consumer_secret, $oauth_token, $oauth_secret);
		}
	
		return $result;
	}
	
	function socialink_openbibid_is_connected($user_guid = 0){
		$result = false;
	
		if(empty($user_guid)){
			$user_guid = get_loggedin_userid();
		}
	
		if(!empty($user_guid) && ($keys = socialink_openbibid_available())){
			$oauth_token = get_plugin_usersetting("openbibid_oauth_token", $user_guid, "socialink");
			$oauth_secret = get_plugin_usersetting("openbibid_oauth_secret", $user_guid, "socialink");
				
			if(!empty($oauth_token) && !empty($oauth_secret)){
				$result = $keys;
				$result["oauth_token"] = $oauth_token;
				$result["oauth_secret"] = $oauth_secret;
			}
		}
	
		return $result;
	}
	
	function socialink_openbibid_get_authorize_url($callback = NULL) {
		global $SESSION;
	
		$result = false;
	
		if($keys = socialink_openbibid_available()){
			if($api = socialink_openbibid_get_api_object($keys)){
				if($token = $api->getRequestToken($callback)){
					// save token in session for use after authorization
					$SESSION['socialink_openbibid'] = array(
							'oauth_token' => $token['oauth_token'],
							'oauth_token_secret' => $token['oauth_token_secret'],
					);
	
					$result = $api->getAuthorizeURL($token['oauth_token']);
				}
			}
		}
	
		return $result;
	}
	
	function socialink_openbibid_get_access_token($oauth_verifier = NULL) {
		global $SESSION;
	
		$result = true;
	
		if($keys = socialink_openbibid_available()){
			if(isset($SESSION['socialink_openbibid'])){
				// retrieve stored tokens
				$keys['oauth_token'] = $SESSION['socialink_openbibid']['oauth_token'];
				$keys['oauth_secret'] = $SESSION['socialink_openbibid']['oauth_token_secret'];
				$SESSION->offsetUnset('socialink_openbibid');
	
				// fetch an access token
				if($api = socialink_openbibid_get_api_object($keys)){
					$result = $api->getAccessToken($oauth_verifier);
				}
			} elseif(isset($SESSION["socialink_token"])){
				$result = $SESSION["socialink_token"];
			}
		}
	
		return $result;
	}
	
	function socialink_openbibid_authorize($user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = get_loggedin_userid();
		}
		
		$oauth_verifier = get_input('oauth_verifier', NULL);
		
		if(!empty($user_guid) && ($token = socialink_openbibid_get_access_token($oauth_verifier))){
			if (isset($token['oauth_token']) && isset($token['oauth_token_secret'])) {
				// only one user per tokens
				$values = array(
					'plugin:settings:socialink:openbibid_user_id' => $token['userId']
				);
					
				// find hidden users (just created)
				$access_status = access_get_show_hidden_status();
				access_show_hidden_entities(true);
		
				if ($users = get_entities_from_private_setting_multi($values, 'user', '', 0, '', false, 0, false, -1)) {
					foreach ($users as $user) {
						// revoke access
						set_plugin_usersetting('openbibid_oauth_token', NULL, $user->getGUID(), "socialink");
						set_plugin_usersetting('openbibid_oauth_secret', NULL, $user->getGUID(), "socialink");
						set_plugin_usersetting('openbibid_user_id', NULL, $user->getGUID(), "socialink");
					}
				}
		
				// restore hidden status
				access_show_hidden_entities($access_status);
					
				// register user's access tokens
				set_plugin_usersetting('openbibid_user_id', $token['userId'], $user_guid, "socialink");
				set_plugin_usersetting('openbibid_oauth_token', $token['oauth_token'], $user_guid, "socialink");
				set_plugin_usersetting('openbibid_oauth_secret', $token['oauth_token_secret'], $user_guid, "socialink");
		
				$result = true;
			}
		}
		
		return $result;
	}
	
	function socialink_openbibid_update_connection($token, $user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = get_loggedin_userid();
		}
		
		if(!empty($token) && !empty($user_guid) && socialink_openbibid_is_connected($user_guid)){
			set_plugin_usersetting("openbibid_oauth_token", $token["oauth_token"], $user_guid, "socialink");
			set_plugin_usersetting("openbibid_oauth_secret", $token["oauth_token_secret"], $user_guid, "socialink");
			set_plugin_usersetting("openbibid_user_id", $token['userId'], $user_guid, "socialink");
			
			$result = true;
		}
		
		return $result;
	}
	
	function socialink_openbibid_remove_connection($user_guid = 0){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = get_loggedin_userid();
		}
		
		if(!empty($user_guid) && socialink_openbibid_is_connected($user_guid)){
			set_plugin_usersetting("openbibid_oauth_token", null, $user_guid, "socialink");
			set_plugin_usersetting("openbibid_oauth_secret", null, $user_guid, "socialink");
			set_plugin_usersetting("openbibid_user_id", null, $user_guid, "socialink");
				
			$result = true;
		}
		
		return $result;
	}
	
	function socialink_openbibid_validate_user_permission($user_guid = 0, $permission = "read"){
		$result = false;
		
		if(empty($user_guid)){
			$user_guid = get_loggedin_userid();
		}
		
		if(!empty($user_guid) && ($keys = socialink_openbibid_is_connected($user_guid))){
			if($api = socialink_openbibid_get_api_object($keys)){
				$user_id = get_plugin_usersetting("openbibid_user_id", $user_guid, "socialink");
				$consumer_key = $keys["consumer_key"];
				
				$url = $api->host . "permissions/user/" . $user_id . "/consumer/" . $consumer_key . "/" . $permission;
				
				if($response = $api->get($url)){
					if($response->code == "OK"){
						$result = true;
					}
				}
			}
		}
		
		return $result;
	}
	
	function socialink_openbibid_validate_ip_permission($ip = null, $permission = "read"){
		$result = false;
		
		if(empty($ip)){
			$ip = $_SERVER["REMOTE_ADDR"];
		}
		
		if(!empty($ip) && ($keys = socialink_openbibid_available())){
			if($api = socialink_openbibid_get_api_object($keys)){
				$params = array(
					"ip" => $ip
				);
				$consumer_key = $keys["consumer_key"];
				
				$url = "permissions/ip/consumer/" . $consumer_key . "/" . $permission;
				
				if($response = $api->get($url, $params)){
					if($response->code == "OK"){
						$result = true;
					}
				}
			}
		}
		
		return $result;
	}