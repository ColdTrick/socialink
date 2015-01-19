<?php

$graphics_base = elgg_normalize_url("mod/socialink/_graphics/");

?>
#socialink_usersettings_twitter_icon {
	background: url("<?php echo $graphics_base; ?>twitter/logo_withbird_90x17_allblue.png") transparent top left no-repeat;
	width: 90px;
	height: 17px;
}

#socialink_usersettings_facebook_icon {
	background: url("<?php echo $graphics_base; ?>facebook/logo_90x20.png") transparent top left no-repeat;
	width: 90px;
	height: 20px;
}

#socialink_usersettings_linkedin_icon {
	background: url("<?php echo $graphics_base; ?>linkedin/logo_84x22.png") transparent top left no-repeat;
	width: 84px;
	height: 22px;
}

#socialink_usersettings_wordpress_icon {
	background: url("<?php echo $graphics_base; ?>wordpress/logo_88x20.png") transparent top left no-repeat;
	width: 88px;
	height: 20px;
}

#socialink_login a{
	margin-left: 5px;
	vertical-align: middle;
}

#socialink_usersettings .socialink_usersettings_network_icon {
	margin: 5px;
	float: left;
	display: block;
}

#socialink_usersettings .socialink_usersettings_network_config {
	margin-left: 150px;
	padding-bottom: 5px;
	border-bottom: 1px dotted #CECECE;
	margin-bottom: 5px;
}

#socialink_twitter_sync_configure,
#socialink_facebook_sync_configure,
#socialink_linkedin_sync_configure {
	display: none;
}

#socialink_twitter_sync_configure.socialink_network_sync_allow,
#socialink_facebook_sync_configure.socialink_network_sync_allow,
#socialink_linkedin_sync_configure.socialink_network_sync_allow {
	display: inline-block;
}

#socialink_twitter_sync_fields,
#socialink_facebook_sync_fields,
#socialink_linkedin_sync_fields {
	display: none;
	width: 100%;
}

#socialink_twitter_sync_fields th,
#socialink_facebook_sync_fields th,
#socialink_linkedin_sync_fields th {
	text-align: left;
}

#socialink_share {
	width: 400px;
	margin-bottom: 0px;
}

#socialink_share textarea {
	height: 60px;
}

#socialink_share input[type='submit'] {
	margin: 0;
}

#socialink_share_characters_remaining {
	float: right;
}

#socialink_share_characters_remaining.negative {
	color: red;
}

#socialink_share_actions a {
	text-decoration: none;
}

#socialink_share img,
#socialink_share_actions img {
	padding-right: 5px;
	vertical-align: text-top;
}

#socialink_link_account_wrapper,
#socialink_create_account_wrapper {
	width: 380px;
	float: left;
	padding: 20px;
	min-height: 260px;
}

#socialink_no_linked_account_spacer {
	float: left;
	width: 50px;
	padding-top: 130px;
	font-size: 16px;
	color: #CCCCCC;
	text-align: center;
}

#socialink_no_linked_account_container label {
	font-size: 100%;
}
