<?php 

	$network = $vars["network"];

	$form_data = "<h3>" . elgg_echo("socialink:login_and_link") . "</h3>";
	
	$form_data .= "<div class='description'>" . elgg_echo("socialink:link_account:description") . "</div>";
	
	$form_data .= "<div><label>" . elgg_echo("username") . "</label></div>";
	$form_data .= elgg_view("input/text", array("internalname" => "username"));
	
	$form_data .= "<div><label>" . elgg_echo("password") . "</label></div>";
	$form_data .= elgg_view("input/password", array("internalname" => "password"));
	
	$form_data .= "<div>";
	$form_data .= "<input type='checkbox' name='persistent' value='true' />" . elgg_echo("user:persistent");
	$form_data .= "</div>";
	
	$form_data .= "<div>";
	$form_data .= elgg_view("input/submit", array("value" => elgg_echo("login")));
	$form_data .= "&nbsp;<a href=\"{$vars['url']}account/forgotten_password.php\" target='_blank'>" . elgg_echo('user:password:lost') . "</a>";
	$form_data .= "</div>";
	
	
	$form_data .= elgg_view("input/hidden", array("internalname" => "socialink_link_network", "value" => $network));
	echo elgg_view("input/form", array("body" => $form_data,
											"action" => $vars["url"] . "action/login"));
