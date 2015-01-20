<?php

$network = elgg_extract("network", $vars);
$network_string = elgg_echo("socialink:network:" . $network);

$form_data = "<div class='mbs'>" . elgg_echo("socialink:create_account:" . $network . ":description") . "</div>";

if (in_array($network, array("twitter"))) {
	$form_data .= "<div>";
	$form_data .= "<label>" . elgg_echo("email") . "</label>";
	$form_data .= elgg_view("input/email", array("name" => "email"));
	$form_data .= "</div>";
}

$form_data .= "<div>";
$form_data .= elgg_view("input/hidden", array("name" => "network", "value" => $network));
$form_data .= elgg_view("input/submit", array("value" => elgg_echo("register")));
$form_data .= "</div>";

$form = elgg_view("input/form", array(
	"body" => $form_data,
	"action" => "action/socialink/create_user"
));

$form .= "<div class='elgg-quiet mts'>" . elgg_echo("socialink:create_account:disclaimer") . "</div>";

echo elgg_view_module("popup", elgg_echo("register"), $form, array("id" => "socialink_create_account_wrapper"));
