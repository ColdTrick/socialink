<?php

$user = elgg_get_page_owner_entity();

if ($user->getGUID() == elgg_get_logged_in_user_guid()) {
	echo "<div id='socialink_usersettings'>";

	// show settings ofr available networks
	if ($networks = socialink_get_available_networks()) {
		foreach ($networks as $network) {
			echo elgg_view("plugins/socialink/usersettings/" . $network, $vars);
		}
	}
	
	echo "</div>";
} else {
	echo elgg_echo("socialink:usersettings:no_access");
}
