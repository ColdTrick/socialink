<?php

$message = get_input("message");
$network = get_input("network");

$max_length = 140;
if ($user_guid = elgg_get_logged_in_user_guid()) {
	if (!empty($message) && !empty($network)) {
		if (socialink_is_supported_network($network)) {
			if (strlen($message) < $max_length) {
				// post to the network
				call_user_func("socialink_" . $network . "_post_message", $message, $user_guid);
			}
		}
	}
}

exit();
