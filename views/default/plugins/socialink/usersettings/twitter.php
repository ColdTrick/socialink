<?php

$user = elgg_get_page_owner_entity();
$plugin = elgg_extract("entity", $vars);

// for yes/no dropdowns
$noyes_options_values = array(
	"no" => elgg_echo("option:no"),
	"yes" => elgg_echo("option:yes")
);

echo "<div>";
echo "<div class='socialink_usersettings_network_icon' id='socialink_usersettings_twitter_icon'></div>";
echo "<div class='socialink_usersettings_network_config'>";
	
// is the user conntected
if (socialink_twitter_is_connected($user->getGUID())) {
	$twitter_remove_link = elgg_add_action_tokens_to_url("action/socialink/remove?service=twitter");
	$twitter_screen_name = $plugin->getUserSetting("twitter_screen_name");
	
	$link_begin = "<a href='" . $twitter_remove_link . "'>";
	$link_end = "</a>";
	
	echo "<div>" . elgg_echo("socialink:usersettings:twitter:remove", array($twitter_screen_name, $link_begin, $link_end)) . "</div>";
	
	// configure profile synchronisation
	if ($fields = socialink_get_configured_network_fields("twitter")) {
		$network_name = elgg_echo("socialink:network:twitter");
		
		echo "<br />";
		echo "<div>";
		echo elgg_echo("socialink:usersettings:profile_sync", array($network_name));
		echo "&nbsp;" . elgg_view("input/dropdown", array(
			"name" => "params[twitter_sync_allow]",
			"options_values" => array_reverse($noyes_options_values),
			"value" => $plugin->getUserSetting("twitter_sync_allow"),
			"onchange" => "socialink_toggle_network_configure(this, 'twitter');"
		));
		echo "&nbsp;<span id='socialink_twitter_sync_configure' ";
		if ($plugin->getUserSetting("twitter_sync_allow") != "no") {
			echo "class='socialink_network_sync_allow'";
		}
		echo ">";
		echo elgg_view("output/url", array(
			"text" => elgg_echo("socialink:configure"),
			"href" => "#socialink_twitter_sync_fields",
			"rel" => "toggle"
		));
		
		echo "</span>";
		echo "</div>";
		
		echo "<table id='socialink_twitter_sync_fields' class='elgg-table'>";
		echo "<tr>";
		echo "<th>" . elgg_echo("socialink:usersettings:profile_field", array($network_name)) . "</th>";
		echo "<th>" . elgg_echo("socialink:usersettings:profile_sync:allow") . "</th>";
		echo "<tr>";
		
		foreach ($fields as $setting_name => $profile_field) {
			$setting = "twitter_sync_" . $setting_name;
			$network_string = elgg_echo("socialink:twitter:field:" . $setting_name);
			
			$lan_key = "profile:" . $profile_field;
			if ($lan_key == elgg_echo($lan_key)) {
				$profile_string = $profile_field;
			} else {
				$profile_string = elgg_echo($lan_key);
			}
			
			echo "<tr>";
			echo "<td>" . elgg_echo("socialink:usersettings:profile_sync:explain", array($network_string, $profile_string)) . "</td>";
			echo "<td>" . elgg_view("input/dropdown", array(
				"name" => "params[" . $setting . "]",
				"options_values" => array_reverse($noyes_options_values, true),
				"value" => $plugin->getUserSetting($setting)
			)) . "</td>";
			echo "<tr>";
		}
		
		echo "</table>";
	}
	
	// twitter in
	if (elgg_is_active_plugin("thewire")) {
		
		if (($twitter_in = $plugin->twitter_allow_in) && ($twitter_in != "no")) {
			echo "<br />";
			echo "<div>";
			echo elgg_echo("socialink:usersettings:twitter:in");
			echo "&nbsp;" . elgg_view("input/dropdown", array("name" => "params[twitter_in]", "value" => $plugin->getUserSetting("twitter_in"), "options_values" => $noyes_options_values));
			echo "<br />";
			echo elgg_echo("socialink:usersettings:twitter:in:filter");
			echo "&nbsp;<input type='text' name='params[twitter_in_filter]' value='" . htmlentities($plugin->getUserSetting("twitter_in_filter"), ENT_QUOTES, "UTF-8") . "' size='15' />";
			echo "</div>";
		}
	} else {
		echo elgg_view("input/hidden", array("name" => "params[twitter_in]", "value" => "no"));
	}
	
} else {
	$url = elgg_normalize_url("socialink/forward/twitter/authorize");
	$link_begin = "<a href='" . $url . "' target='_self'>";
	$link_end = "</a>";
	
	echo elgg_echo("socialink:usersettings:twitter:not_connected", array($link_begin, $link_end));
}

echo "</div>";
echo "<div class='clearfloat'></div>";
echo "</div>";
