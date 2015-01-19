<?php

$user_guid = elgg_get_logged_in_user_guid();
if (empty($user_guid)) {
	return;
}

$networks = socialink_get_user_networks($user_guid);
if (empty($networks)) {
	return;
}

// load fancybox
elgg_load_js("lightbox");
elgg_load_css("lightbox");

$current_url = elgg_extract("current_url", $vars, current_page_url());
$entity = elgg_extract("entity", $vars);

echo "<div id='socialink_share_actions'>";
foreach ($networks as $network) {
	$href = elgg_normalize_url("socialink/share?network=" . $network . "&url=" . $current_url);
	$icon = elgg_normalize_url("mod/socialink/_graphics/" . $network . "/login.png");
	
	echo "<div>";
	echo "<a href='" . $href . "'>";
	echo "<img src='" . $icon . "' />";
	echo elgg_echo("socialink:share:" . $network);
	echo "</a>";
	echo "</div>";
}
echo "</div>";

?>
<script type="text/javascript">
	$(document).ready(function() {
		$("#socialink_share_actions a").click(function() {
			var title = document.title;
			var url = $(this).attr("href") + "&title=" + title;
			
			$.fancybox({
				'href'	: url,
				'type'	: 'ajax'
			});

			return false;
		});
	});
</script>
