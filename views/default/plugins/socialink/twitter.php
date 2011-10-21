<?php 

	$user = $vars["user"];
	$usersettings = $vars["entity"];
	
	// for yes/no dropdowns
	$noyes_options_values = array(
		"no" => elgg_echo("option:no"),
		"yes" => elgg_echo("option:yes")
	);
	
	echo "<div>";
	echo "<div class='socialink_usersettings_network_icon' id='socialink_usersettings_twitter_icon'></div>";
	echo "<div class='socialink_usersettings_network_config'>";
		
	// is the user conntected
	if(socialink_twitter_is_connected($user->getGUID())){
		$twitter_remove_link = elgg_add_action_tokens_to_url($vars["url"] . "action/socialink/remove?service=twitter");
		$twitter_screen_name = elgg_get_plugin_user_setting("twitter_screen_name", $user->getGUID(), "socialink");
		
		$link_begin = "<a href='" . $twitter_remove_link . "'>";
		$link_end = "</a>";
		
		echo "<div>" . sprintf(elgg_echo("socialink:usersettings:twitter:remove"), $twitter_screen_name, $link_begin, $link_end) . "</div>";
		
		// check for the wire
//		if(is_plugin_enabled("thewire")){
//			echo "<div>";
//			echo elgg_echo("socialink:usersettings:twitter:thewire");
//			echo "&nbsp;" . elgg_view("input/dropdown", array("name" => "params[thewire_post_twitter]", "options_values" => $yesno_options_values, "value" => $usersettings->thewire_post_twitter));
//			echo "</div>";
//		}
		
		// configure profile synchronisation
		if($fields = socialink_get_configured_network_fields("twitter")){
			$network_name = elgg_echo("socialink:network:twitter");
			
			echo "<br />";
			echo "<div>";
			echo sprintf(elgg_echo("socialink:usersettings:profile_sync"), $network_name);
			echo "&nbsp;" . elgg_view("input/dropdown", array("name" => "params[twitter_sync_allow]", "options_values" => array_reverse($noyes_options_values), "value" => $usersettings->twitter_sync_allow, "js" => "onchange='socialink_toggle_network_configure(this, \"twitter\");'"));
			echo "&nbsp;<span id='socialink_twitter_sync_configure' ";
			if($usersettings->twitter_sync_allow != "no"){
				echo "class='socialink_network_sync_allow'";
			}
			echo "><a href='javascript:void(0);' onclick='$(\"#socialink_twitter_sync_fields\").toggle();'>" . elgg_echo("socialink:configure") . "</a></span>";
			echo "</div>";
			
			echo "<table id='socialink_twitter_sync_fields'>";
			echo "<tr>";
			echo "<th>" . sprintf(elgg_echo("socialink:usersettings:profile_field"), $network_name) . "</th>";
			echo "<th>" . elgg_echo("socialink:usersettings:profile_sync:allow") . "</th>";
			echo "<tr>";
			
			foreach($fields as $setting_name => $profile_field){
				$setting = "twitter_sync_" . $setting_name;
				$network_string = elgg_echo("socialink:twitter:field:" . $setting_name);
				
				$lan_key = "profile:" . $profile_field;
				if($lan_key == elgg_echo($lan_key)){
					$profile_string = $profile_field;
				} else {
					$profile_string = elgg_echo($lan_key);
				}
				
				echo "<tr>";
				echo "<td>" . sprintf(elgg_echo("socialink:usersettings:profile_sync:explain"), $network_string, $profile_string) . "</td>";
				echo "<td>" . elgg_view("input/dropdown", array("name" => "params[" . $setting . "]", "options_values" => array_reverse($noyes_options_values, true), "value" => $usersettings->$setting)) . "</td>";
				echo "<tr>";
			}
			
			echo "</table>";
		}
		
		// twitter in
		if(elgg_is_active_plugin("thewire")){
			
			if(($twitter_in = elgg_get_plugin_setting("twitter_allow_in", "socialink")) && ($twitter_in != "no")){
				echo "<br />";
				echo "<div>";
				echo elgg_echo("socialink:usersettings:twitter:in");
				echo "&nbsp;" . elgg_view("input/dropdown", array("name" => "params[twitter_in]", "value" => $usersettings->twitter_in, "options_values" => $noyes_options_values));
				echo "<br />";
				echo elgg_echo("socialink:usersettings:twitter:in:filter");
				echo "&nbsp;<input type='text' name='params[twitter_in_filter]' value='" . htmlentities($usersettings->twitter_in_filter, ENT_QUOTES, "UTF-8") . "' size='15' />";
				echo "</div>";
			}
		} else {
			echo elgg_view("input/hidden", array("name" => "params[twitter_in]", "value" => "no"));
		}
		
	} else {
		$link_begin = "<a href='" . $vars["url"] . "socialink/forward/twitter/authorize' target='_self'>";
		$link_end = "</a>";
		
		echo sprintf(elgg_echo("socialink:usersettings:twitter:not_connected"), $link_begin, $link_end);
	}
	echo "</div>";
	echo "<div class='clearfloat'></div>";
	echo "</div>";
