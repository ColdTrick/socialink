<?php

if (elgg_is_logged_in() || empty($_SESSION["socialink_token"])) {
	forward();
}

$network = get_input("network");
$network_string = elgg_echo("socialink:network:" . $network);

$allow_create = elgg_get_plugin_setting($network . "_allow_create", "socialink");

// build page elements
$title_text = elgg_echo("socialink:no_linked_account:title", array($network_string));

$body = elgg_view("socialink/no_linked_account", array("network" => $network, "allow_create" => $allow_create));

// build page
$page_data = elgg_view_layout("one_column", array(
	"title" => $title_text,
	"content" => $body
));

// draw page
echo elgg_view_page($title_text, $page_data);
