<?php 
	$user = $vars["user"];
	$usersettings = $vars["entity"];
	
	// for yes/no pulldowns
	$yesno_options_values = array(
		"no" => elgg_echo("option:no"),
		"yes" => elgg_echo("option:yes")
	);
	
	echo "<div>";
	echo "<div class='socialink_usersettings_network_icon' id='socialink_usersettings_openbibid_icon'></div>";
	echo "<div class='socialink_usersettings_network_config'>";
		
	if($keys = socialink_openbibid_is_connected($user->getGUID())){
		$openbibid_remove_link = elgg_add_action_tokens_to_url($vars["url"] . "action/socialink/remove?service=openbibid");
		
		$link_begin = "<a href='" . $openbibid_remove_link . "'>";
		$link_end = "</a>";
		
		echo "<div>" . sprintf(elgg_echo("socialink:usersettings:openbibid:remove"), $link_begin, $link_end) . "</div>";
		
		// check for the wire
//		if(is_plugin_enabled("thewire")){
//			echo "<div>";
//			echo elgg_echo("socialink:usersettings:openbibid:thewire");
//			echo "&nbsp;" . elgg_view("input/pulldown", array("internalname" => "params[thewire_post_openbibid]", "options_values" => $yesno_options_values, "value" => $usersettings->thewire_post_openbibid));
//			echo "</div>";
//		}
		
		// configure profile synchronisation
		/*if($fields = socialink_get_configured_network_fields("openbibid")){
			$network_name = elgg_echo("socialink:network:openbibid");
			
			echo "<br />";
			echo "<div>";
			echo sprintf(elgg_echo("socialink:usersettings:profile_sync"), $network_name);
			echo "&nbsp;" . elgg_view("input/pulldown", array("internalname" => "params[openbibid_sync_allow]", "options_values" => array_reverse($yesno_options_values), "value" => $usersettings->openbibid_sync_allow, "js" => "onchange='socialink_toggle_network_configure(this, \"openbibid\");'"));
			echo "&nbsp;<span id='socialink_openbibid_sync_configure' ";
			if($usersettings->openbibid_sync_allow != "no"){
				echo "class='socialink_network_sync_allow'";
			}
			echo "><a href='javascript:void(0);' onclick='$(\"#socialink_openbibid_sync_fields\").toggle();'>" . elgg_echo("socialink:configure") . "</a></span>";
			echo "</div>";
			
			echo "<table id='socialink_openbibid_sync_fields'>";
			echo "<tr>";
			echo "<th>" . sprintf(elgg_echo("socialink:usersettings:profile_field"), $network_name) . "</th>";
			echo "<th>" . elgg_echo("socialink:usersettings:profile_sync:allow") . "</th>";
			echo "<tr>";
			
			foreach($fields as $setting_name => $profile_field){
				$setting = "openbibid_sync_" . $setting_name;
				$network_string = elgg_echo("socialink:openbibid:field:" . $setting_name);
				
				$lan_key = "profile:" . $profile_field;
				if($lan_key == elgg_echo($lan_key)){
					$profile_string = $profile_field;
				} else {
					$profile_string = elgg_echo($lan_key);
				}
				
				echo "<tr>";
				echo "<td>" . sprintf(elgg_echo("socialink:usersettings:profile_sync:explain"), $network_string, $profile_string) . "</td>";
				echo "<td>" . elgg_view("input/pulldown", array("internalname" => "params[" . $setting . "]", "options_values" => array_reverse($yesno_options_values, true), "value" => $usersettings->$setting)) . "</td>";
				echo "<tr>";
			}
			
			echo "</table>";
		}*/
		
		if(isadminloggedin()){
			echo "<br />";
			$rights = socialink_openbibid_validate_user_permission($user->getGUID());
			echo "check rights: " . var_export($rights, true) . "<br />";
			
			$ip = socialink_openbibid_validate_ip_permission();
			echo "check IP: " . var_export($ip, true) . "<br />";
		}
		
	} else {
		$link_begin = "<a href='" . $vars["url"] . "pg/socialink/forward/openbibid/authorize' target='_self'>";
		$link_end = "</a>";
		
		echo sprintf(elgg_echo("socialink:usersettings:openbibid:not_connected"), $link_begin, $link_end);
	}
	echo "</div>";
	echo "<div class='clearfloat'></div>";
	echo "</div>";
	