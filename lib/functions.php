<?php 

	global $SOCIALINK_PROXY_SETTINGS;

	function socialink_load_networks(){
		global $SOCIALINK_PROXY_SETTINGS;
		
		if($networks = socialink_get_available_networks()){
			foreach($networks as $network){
				elgg_load_library("socialink:" . $network);
			}
			
			// get proxy settings
			$proxy_host = elgg_get_plugin_setting("proxy_host", "socialink");
			$proxy_port = (int) elgg_get_plugin_setting("proxy_port", "socialink");
			
			if(!empty($proxy_host)){
				$SOCIALINK_PROXY_SETTINGS = array(
					"host" => $proxy_host,
					"port" => $proxy_port
				);
			}
		}
	}

	function socialink_twitter_available(){
		$result = false;
		
		if(elgg_get_plugin_setting("enable_twitter", "socialink") == "yes"){
			$consumer_key = elgg_get_plugin_setting("twitter_consumer_key", "socialink");
			$consumer_secret = elgg_get_plugin_setting("twitter_consumer_secret", "socialink");
			
			if(!empty($consumer_key) && !empty($consumer_secret)){
				$result = array(
					"consumer_key" => $consumer_key,
					"consumer_secret" => $consumer_secret,
				);
			}
		}
		
		return $result;
	}
	
	function socialink_facebook_available(){
		$result = false;
		
		if(elgg_get_plugin_setting("enable_facebook", "socialink") == "yes"){
			$app_id = elgg_get_plugin_setting("facebook_app_id", "socialink");
			$app_secret = elgg_get_plugin_setting("facebook_app_secret", "socialink");
			
			if(!empty($app_id) && !empty($app_secret)){
				$result = array(
					"app_id" => $app_id,
					"app_secret" => $app_secret
				);
			}
		}
		
		return $result;
	}
	
	function socialink_linkedin_available(){
		$result = false;
		
		if(elgg_get_plugin_setting("enable_linkedin", "socialink") == "yes"){
			$consumer_key = elgg_get_plugin_setting("linkedin_consumer_key", "socialink");
			$consumer_secret = elgg_get_plugin_setting("linkedin_consumer_secret", "socialink");
			
			if(!empty($consumer_key) && !empty($consumer_secret)){
				$result = array(
					"consumer_key" => $consumer_key,
					"consumer_secret" => $consumer_secret,
				);
			}
		}
		
		return $result;
	}
	
	function socialink_hyves_available(){
		$result = false;
		
		if(elgg_get_plugin_setting("enable_hyves", "socialink") == "yes"){
			$consumer_key = elgg_get_plugin_setting("hyves_consumer_key", "socialink");
			$consumer_secret = elgg_get_plugin_setting("hyves_consumer_secret", "socialink");
			
			if(!empty($consumer_key) && !empty($consumer_secret)){
				$result = array(
					"consumer_key" => $consumer_key,
					"consumer_secret" => $consumer_secret,
				);
			}
		}
		
		return $result;
	}
	
	function socialink_openbibid_available(){
		$result = false;
	
		if(elgg_get_plugin_setting("enable_openbibid", "socialink") == "yes"){
			$consumer_key = elgg_get_plugin_setting("openbibid_consumer_key", "socialink");
			$consumer_secret = elgg_get_plugin_setting("openbibid_consumer_secret", "socialink");
				
			if(!empty($consumer_key) && !empty($consumer_secret)){
				$result = array(
					"consumer_key" => $consumer_key,
					"consumer_secret" => $consumer_secret,
				);
			}
		}
	
		return $result;
	}
	
	function socialink_wordpress_available(){
		$result = false;
	
		if(elgg_get_plugin_setting("enable_wordpress", "socialink") == "yes"){
			$url = elgg_get_plugin_setting("wordpress_url", "socialink");
			$consumer_key = elgg_get_plugin_setting("wordpress_consumer_key", "socialink");
			$consumer_secret = elgg_get_plugin_setting("wordpress_consumer_secret", "socialink");
				
			if(!empty($url) && !empty($consumer_key) && !empty($consumer_secret)){
				$result = array(
					"url" => $url,
					"consumer_key" => $consumer_key,
					"consumer_secret" => $consumer_secret,
				);
			}
		}
	
		return $result;
	}

	function socialink_get_supported_networks(){
		return array("twitter", "linkedin", "facebook", "hyves", "openbibid", "wordpress");
	}
	
	function socialink_is_supported_network($network){
		$result = false;
		
		if(!empty($network) && ($networks = socialink_get_supported_networks())){
			$result = in_array($network, $networks);
		}
		
		return $result;
	}
	
	function socialink_get_available_networks(){
		static $available;
		
		if(!isset($available)){
			if($networks = socialink_get_supported_networks()){
				$available = array();
				
				foreach($networks as $network){
					$function = "socialink_" . $network . "_available";
					
					if(is_callable($function) && call_user_func($function)){
						$available[] = $network;
					}
				}
			} else {
				$available = false;
			}
		}
		
		return $available;
	}
	
	function socialink_is_available_network($network){
		$result = false;
		
		if(!empty($network) && ($networks = socialink_get_available_networks())){
			$result = in_array($network, $networks);
		}
		
		return $result;
	}
	
	/**
	 * Returns all the networks the user is connected to or an empty array if none available
	 */
	function socialink_get_user_networks($user_guid){
		$result = array();
		
		if(empty($user_guid)){
			$user_guid = elgg_get_logged_in_user_guid();
		}
		
		if($available_networks = socialink_get_available_networks()){
			foreach($available_networks as $network){
				$function = "socialink_" . $network . "_is_connected";
				
				if(is_callable($function) && call_user_func($function, $user_guid)){
					$result[] = $network;
				}
			}
		}
		
		return $result;
	}
	
	function socialink_validate_network($network, $user_guid){
		$result = true;
		
		$function = "socialink_" . $network . "_validate_connection";
		
		if(is_callable($function)){
			$result = call_user_func($function, $user_guid);
		}
		
		return $result;
	}
	
	function socialink_get_proxy_settings(){
		global $SOCIALINK_PROXY_SETTINGS;
	
		$result = false;
	
		if(!empty($SOCIALINK_PROXY_SETTINGS)){
			$result = $SOCIALINK_PROXY_SETTINGS;
		}
	
		return $result;
	}
	
	/**
	 * get an array of the supported network fields
	 * 
	 * result is in format
	 * 		settings_name => network_name
	 * 
	 * @param $network
	 * @return unknown_type
	 */
	function socialink_get_network_fields($network){
		$result = false;
		
		if(!empty($network) && socialink_is_supported_network($network)){
			$fields = array(
				"twitter" => array(
					"name" => "name",
					"location" => "location",
					"url" => "url",
					"description" => "description",
					"screen_name" => "screen_name",
					"profile_url" => "socialink_profile_url",
				),
				"linkedin" => array(
					"firstname" => "firstName",
					"lastname" => "lastName",
					"name" => "socialink_name",
					"profile_url" => "publicProfileUrl",
					"location" => "location->name",
					"industry" => "industry"
				),
				"facebook" => array(
					"name" => "name",
					"firstname" => "first_name",
					"lastname" => "last_name",
					"profile_url" => "link",
					"email" => "email",
					"location" => "location",
					"gender" => "gender",
					"about" => "about",
					"bio" => "bio",
					"hometown" => "hometown"
				),
				"hyves" => array(
					"name" => "displayname",
					"firstname" => "firstname",
					"lastname" => "lastname",
					"profile_url" => "url",
					"gender" => "gender",
					"city" => "cityname",
					"country" => "countryname",
					"about" => "aboutme"
				)
			);
			
			if(array_key_exists($network, $fields)){
				$result = $fields[$network];
			}
		}
		
		return $result;
	}
	
	function socialink_get_configured_network_fields($network){
		$result = false;
		
		if(!empty($network) && socialink_is_available_network($network)){
			if(($fields = socialink_get_network_fields($network)) && !empty($fields)){
				$temp = array();
				
				foreach($fields as $setting_name => $network_name){
					if(($profile_field = elgg_get_plugin_setting($network . "_profile_" . $setting_name, "socialink")) && !empty($profile_field)){
						$result[$setting_name] = $profile_field;
					}
				}
				
				if(!empty($temp)){
					$result = $temp;
				}
			}
		}
		
		return $result;
	}
	
	function socialink_create_username_from_email($email){
		$result = false;
		
		if(!empty($email) && is_email_address($email)){
			list($username) = explode("@", $email);
			
			// filter invalid characters from username from validate_username()
			$username = preg_replace('/[^a-zA-Z0-9]/', "", $username);
			
			// check for min username length
			$minchars = (int) elgg_get_config("minusername");
			if (empty($minchars)) {
				$minchars = 4;
			}
			
			$username = str_pad($username, $minchars, "0", STR_PAD_RIGHT);
			
			// show hidden entities
			$access = access_get_show_hidden_status();
			access_show_hidden_entities(TRUE);
			
			// check if username extist
			if(get_user_by_username($username)){
				$i = 1;
				while(get_user_by_username($username . $i)){
					$i++;
				}
				
				$username = $username . $i;
			}
			
			// restore access settings
			access_show_hidden_entities($access);
			
			// return username
			$result = $username;
		}
		
		return $result;
	}
	
	function socialink_prepare_login(){
		
		if(empty($_SESSION["last_forward_from"])){
			$site_url = elgg_get_site_url();
			$referer = $_SERVER["HTTP_REFERER"];
			
			if(($site_url != $referer) && stristr($referer, $site_url)){
				$_SESSION["last_forward_from"] = $referer;
			}
		}
	}
	