<?php 

	$network = $vars["network"];
	$network_string = elgg_echo("socialink:network:" . $network);

	$form_data = "<h3>" . elgg_echo("register") . "</h3>";

	$form_data .= "<div class='description'>" . elgg_echo("socialink:create_account:" . $network . ":description") . "</div>";
	
	if(in_array($network, array("linkedin", "twitter"))){
		$form_data .= "<div><label>" . elgg_echo("email") . "</label></div>";
		$form_data .= elgg_view("input/email", array("internalname" => "email"));
	}
	
	$form_data .= "<div>";
	$form_data .= elgg_view("input/hidden", array("internalname" => "network", "value" => $network));
	$form_data .= elgg_view("input/submit", array("value" => elgg_echo("register")));
	$form_data .= "</div>";
	
	echo elgg_view("input/form", array("body" => $form_data,
										"action" => $vars["url"] . "action/socialink/create_user"));
	
	echo "<div class='disclaimer'>" . elgg_echo("socialink:create_account:disclaimer") . "</div>";
