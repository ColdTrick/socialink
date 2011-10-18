<?php 

	define("externalpage", true);
	
	if(!isloggedin() && !empty($_SESSION["socialink_token"])){
		$network = get_input("network");
		$network_string = elgg_echo("socialink:network:" . $network);
		
		$allow_create = get_plugin_setting($network . "_allow_create", "socialink");
		
		// build page elements
		$title_text = sprintf(elgg_echo("socialink:no_linked_account:title"), $network_string);
		$title = elgg_view_title($title_text);
		
		$body = elgg_view("socialink/no_linked_account", array("network" => $network, "allow_create" => $allow_create));
		
		// build page
		$page_data =  $title . $body;
		
		// draw page
		page_draw($title_text, elgg_view_layout("onw_column", $page_data));
	} else {
		forward();
	}
