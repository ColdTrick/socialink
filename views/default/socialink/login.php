<?php 
	
	if($networks = socialink_get_available_networks()){
		echo "<div id='socialink_login'>";
		echo elgg_echo("socialink:login");
		
		foreach($networks as $network){
			if(elgg_get_plugin_setting($network . "_allow_login", "socialink") == "yes"){
				$network_text = elgg_echo("socialink:network:" . $network);
				
				echo "<a href='" . $vars["url"] . "socialink/forward/" . $network . "/login' title='" . elgg_echo("socialink:login:network", array($network_text)) . "' target='_self'>";
				echo "<img alt='" . $network_text . "' src='" . $vars["url"] . "mod/socialink/_graphics/" . $network . "/login.png' />";
				echo "</a>";
			}
		}
		
		echo "</div>";
	}
