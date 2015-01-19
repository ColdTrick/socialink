<?php

$network = elgg_extract("network", $vars);
$allow_create = elgg_extract("allow_create", $vars);

$network_string = elgg_echo("socialink:network:" . $network);

$link_form = elgg_view("socialink/forms/link_account", array("network" => $network));

if ($allow_create == "yes") {
	$create_form = elgg_view("socialink/forms/create_account", array("network" => $network));
}

?>
<div><?php echo elgg_echo("socialink:create_account:description", array($network_string)); ?></div>
<br />

<div id="socialink_no_linked_account_container">
	<?php
		echo $link_form;
		
		if (!empty($create_form)) {
	?>
		<div id="socialink_no_linked_account_spacer"><?php echo strtoupper(elgg_echo("socialink:or")); ?></div>
	<?php
		echo $create_form;
		}
	?>
</div>
