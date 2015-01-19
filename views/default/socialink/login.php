<?php

$networks = socialink_get_available_networks();
if (empty($networks)) {
	return;
}

echo "<div id='socialink_login'>";
echo elgg_echo("socialink:login");

foreach ($networks as $network) {
	if (elgg_get_plugin_setting($network . "_allow_login", "socialink") == "yes") {
		$network_text = elgg_echo("socialink:network:" . $network);
		
		$href = elgg_normalize_url("socialink/forward/" . $network . "/login");
		$icon = elgg_normalize_url("mod/socialink/_graphics/" . $network . "/login.png");
		
		echo "<a href='" . $href . "' title='" . elgg_echo("socialink:login:network", array($network_text)) . "' target='_self'>";
		echo "<img alt='" . $network_text . "' src='" . $icon . "' />";
		echo "</a>";
	}
}

echo "</div>";
