<?php

gatekeeper();

$service = get_input("service");

if (socialink_is_supported_network($service)) {
	$network_string = elgg_echo("socialink:network:" . $service);
	
	if (socialink_is_available_network($service)) {
		if (call_user_func("socialink_" . $service . "_is_connected")) {
			if (call_user_func("socialink_" . $service . "_remove_connection")) {
				system_message(elgg_echo("socialink:actions:remove:success", array($network_string)));
			} else {
				register_error(elgg_echo("socialink:actions:remove:error:remove", array($network_string)));
			}
		} else {
			register_error(elgg_echo("socialink:actions:remove:error:connected", array($network_string)));
		}
	} else {
		register_error(elgg_echo("socialink:actions:remove:error:unavailable", array($network_string)));
	}
} else {
	register_error(elgg_echo("socialink:actions:remove:error:unknown_service"));
}

forward(REFERER);
