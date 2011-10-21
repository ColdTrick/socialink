<?php 

	$user = elgg_get_page_owner_entity();
	$vars["user"] = $user;
	$usersettings = $vars["entity"];
	
	if($user->getGUID() == elgg_get_logged_in_user_guid()){
		echo "<div id='socialink_usersettings'>";
		// is Twitter available for users
		if(socialink_twitter_available()){
			echo elgg_view("plugins/socialink/twitter", $vars);
		}
		
		// is Facebook available for users
		if(socialink_facebook_available()){
			echo elgg_view("plugins/socialink/facebook", $vars);
		}
	
		// is LinkedIn available for users
		if(socialink_linkedin_available()){
			echo elgg_view("plugins/socialink/linkedin", $vars);
		}
		echo "</div>";
	} else {
		echo elgg_echo("socialink:usersettings:no_access");
	}

?>