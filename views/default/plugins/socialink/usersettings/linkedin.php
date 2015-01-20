<?php

$user = elgg_get_page_owner_entity();
$plugin = elgg_extract("entity", $vars);

// for yes/no dropdowns
$yesno_options_values = array(
	"no" => elgg_echo("option:no"),
	"yes" => elgg_echo("option:yes")
);

echo "<div>";
echo "<div class='socialink_usersettings_network_icon' id='socialink_usersettings_linkedin_icon'></div>";
echo "<div class='socialink_usersettings_network_config'>";
	
if (socialink_linkedin_is_connected($user->getGUID())) {
	$linkedin_remove_link = elgg_add_action_tokens_to_url("action/socialink/remove?service=linkedin");
	
	$link_begin = "<a href='" . $linkedin_remove_link . "'>";
	$link_end = "</a>";
	
	echo "<div>" . elgg_echo("socialink:usersettings:linkedin:remove", array($link_begin, $link_end)) . "</div>";
	
	// configure profile synchronisation
	if ($fields = socialink_get_configured_network_fields("linkedin")) {
		$network_name = elgg_echo("socialink:network:linkedin");
		
		echo "<br />";
		echo "<div>";
		echo elgg_echo("socialink:usersettings:profile_sync", array($network_name));
		echo "&nbsp;" . elgg_view("input/dropdown", array(
			"name" => "params[linkedin_sync_allow]",
			"options_values" => array_reverse($yesno_options_values),
			"value" => $plugin->getUserSetting("linkedin_sync_allow"),
			"onchange" => "socialink_toggle_network_configure(this, 'linkedin');"
		));
		echo "&nbsp;<span id='socialink_linkedin_sync_configure' ";
		if ($plugin->getUserSetting("linkedin_sync_allow") != "no") {
			echo "class='socialink_network_sync_allow'";
		}
		echo ">";
		echo elgg_view("output/url", array(
			"text" => elgg_echo("socialink:configure"),
			"href" => "#socialink_linkedin_sync_fields",
			"rel" => "toggle"
		));
		echo "</span>";
		echo "</div>";
		
		echo "<table id='socialink_linkedin_sync_fields' class='elgg-table'>";
		echo "<tr>";
		echo "<th>" . elgg_echo("socialink:usersettings:profile_field", array($network_name)) . "</th>";
		echo "<th>" . elgg_echo("socialink:usersettings:profile_sync:allow") . "</th>";
		echo "<tr>";
		
		foreach ($fields as $setting_name => $profile_field) {
			$setting = "linkedin_sync_" . $setting_name;
			$network_string = elgg_echo("socialink:linkedin:field:" . $setting_name);
			
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
				"options_values" => array_reverse($yesno_options_values, true),
				"value" => $plugin->getUserSetting($setting)
			)) . "</td>";
			echo "<tr>";
		}
		
		echo "</table>";
	}
	
} else {
	$url = elgg_normalize_url("socialink/forward/linkedin/authorize");
	$link_begin = "<a href='" . $url . "' target='_self'>";
	$link_end = "</a>";
	
	echo elgg_echo("socialink:usersettings:linkedin:not_connected", array($link_begin, $link_end));
}

echo "</div>";
echo "<div class='clearfloat'></div>";
echo "</div>";
