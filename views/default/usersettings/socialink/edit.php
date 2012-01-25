<?php 

	$user = page_owner_entity();
	$vars["user"] = $user;
	$usersettings = $vars["entity"];
	
	
	
	if($user->getGUID() == get_loggedin_userid()){
		echo "<div id='socialink_usersettings'>";
		// is Twitter available for users
		if(socialink_twitter_available()){
			echo elgg_view("usersettings/socialink/twitter", $vars);
		}
		
		// is Facebook available for users
		if(socialink_facebook_available()){
			echo elgg_view("usersettings/socialink/facebook", $vars);
		}
	
		// is LinkedIn available for users
		if(socialink_linkedin_available()){
			echo elgg_view("usersettings/socialink/linkedin", $vars);
		}
		
		// is OpenBibId available for users
		if(socialink_openbibid_available()){
			
			echo elgg_view("usersettings/socialink/openbibid", $vars);
		}
		
		echo "</div>";
	} else {
		echo elgg_echo("socialink:usersettings:no_access");
	}
	