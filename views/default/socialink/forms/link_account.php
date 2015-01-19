<?php

$network = elgg_extract("network", $vars);

$form_data = "<div class='mbs'>" . elgg_echo("socialink:link_account:description") . "</div>";

$form_data .= "<div>";
$form_data .= "<label>" . elgg_echo("loginusername") . "</label>";
$form_data .= elgg_view("input/text", array("name" => "username"));
$form_data .= "</div>";

$form_data .= "<div>";
$form_data .= "<label>" . elgg_echo("password") . "</label>";
$form_data .= elgg_view("input/password", array("name" => "password"));
$form_data .= "</div>";

$form_data .= "<div>";
$form_data .= "<input type='checkbox' name='persistent' value='true' />" . elgg_echo("user:persistent");
$form_data .= "</div>";

$form_data .= "<div>";
$form_data .= elgg_view("input/submit", array("value" => elgg_echo("login")));
$form_data .= "&nbsp;" . elgg_view("output/url", array(
	"href" => "forgotpassword",
	"text" => elgg_echo("user:password:lost"),
	"target" => "_blank"
));
$form_data .= "</div>";

$form_data .= elgg_view("input/hidden", array("name" => "socialink_link_network", "value" => $network));

$form = elgg_view("input/form", array(
	"body" => $form_data,
	"action" => "action/login"
));

echo elgg_view_module("popup",
	elgg_echo("socialink:login_and_link"),
	$form,
	array("id" => "socialink_link_account_wrapper")
);
