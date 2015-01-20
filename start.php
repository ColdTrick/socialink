<?php
/**
 * Main plugin file
 */

require_once(dirname(__FILE__) . "/lib/functions.php");
require_once(dirname(__FILE__) . "/lib/hooks.php");
require_once(dirname(__FILE__) . "/lib/events.php");
require_once(dirname(__FILE__) . "/lib/page_handlers.php");
require_once(dirname(__FILE__) . "/vendor/autoload.php");

// register default Elgg events
elgg_register_event_handler("init", "system", "socialink_init");

/**
 * Get called during system init
 *
 * @return void
 */
function socialink_init() {
	
	// register classes
	elgg_register_classes(dirname(__FILE__) . "/vendors/oauth/classes");
	elgg_register_class("LinkedIn", dirname(__FILE__) . "/vendors/simple_linkedin/linkedin_3.1.1.class.php");
	
	// register SociaLink libraries
	elgg_register_library("socialink:facebook", dirname(__FILE__) . "/lib/networks/facebook.php");
	elgg_register_library("socialink:linkedin", dirname(__FILE__) . "/lib/networks/linkedin.php");
	elgg_register_library("socialink:twitter", dirname(__FILE__) . "/lib/networks/twitter.php");
	elgg_register_library("socialink:wordpress", dirname(__FILE__) . "/lib/networks/wordpress.php");
	
	// extend CSS
	elgg_extend_view("css/elgg", "socialink/css/site");
	elgg_extend_view("css/admin", "socialink/css/admin");
	
	// extend JS
	elgg_extend_view("js/elgg", "socialink/js");
	
	// extend login box
	elgg_extend_view("forms/login", "socialink/login");
	
	// register page handler
	elgg_register_page_handler("socialink", "socialink_page_handler");
	
	// register event handlers
	//register_elgg_event_handler("create", "object", "socialink_create_object_handler");
	elgg_register_event_handler("login:after", "user", "socialink_login_user_handler", 450);
	
	// hooks
	elgg_register_plugin_hook_handler("socialink:sync", "user", "socialink_sync_network_hook");
	elgg_register_plugin_hook_handler("public_pages", "walled_garden", "socialink_walled_garden_hook");
	elgg_register_plugin_hook_handler("register", "user", "socialink_register_user_hook", 450);
	
	// register actions
	elgg_register_action("socialink/remove", dirname(__FILE__) . "/actions/remove.php");
	elgg_register_action("socialink/create_user", dirname(__FILE__) . "/actions/create_user.php", "public");
	elgg_register_action("socialink/share", dirname(__FILE__) . "/actions/share.php");
	
	// load necesary files
	socialink_load_networks();
	
	// twitter in
	if (elgg_is_active_plugin("thewire") && socialink_is_available_network("twitter")) {
		$setting = elgg_get_plugin_setting("twitter_allow_in", "socialink");
		
		switch ($setting) {
			case "fifteenmin":
			case "halfhour":
				elgg_register_plugin_hook_handler("cron", $setting, "socialink_twitter_in_cron_hook");
				break;
		}
	}
}
	