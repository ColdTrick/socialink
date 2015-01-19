<?php

$user = elgg_get_page_owner_entity();
$plugin = elgg_extract("entity", $vars);

// for yes/no pulldowns
$yesno_options_values = array(
	"no" => elgg_echo("option:no"),
	"yes" => elgg_echo("option:yes")
);

echo "<div>";
echo "<div class='socialink_usersettings_network_icon' id='socialink_usersettings_wordpress_icon'></div>";
echo "<div class='socialink_usersettings_network_config'>";
	
if ($keys = socialink_wordpress_is_connected($user->getGUID())) {
	$wordpress_remove_link = elgg_add_action_tokens_to_url("action/socialink/remove?service=wordpress");
	
	$link_begin = "<a href='" . $wordpress_remove_link . "'>";
	$link_end = "</a>";
	
	echo "<div>" . elgg_echo("socialink:usersettings:wordpress:remove", array($link_begin, $link_end)) . "</div>";
	
} else {
	$url = elgg_normalize_url("socialink/forward/wordpress/authorize");
	
	$link_begin = "<a href='" . $url . "' target='_self'>";
	$link_end = "</a>";
	
	echo sprintf(elgg_echo("socialink:usersettings:wordpress:not_connected"), $link_begin, $link_end);
}

echo "</div>";
echo "<div class='clearfloat'></div>";
echo "</div>";
