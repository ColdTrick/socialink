<?php 

?>
#socialink_settings_twitter,
#socialink_settings_facebook,
#socialink_settings_linkedin {
	display: none;
}

#socialink_settings_twitter.socialink_settings_show,
#socialink_settings_facebook.socialink_settings_show,
#socialink_settings_linkedin.socialink_settings_show {
	display: block;
}

#socialink_settings_twitter table,
#socialink_settings_facebook table,
#socialink_settings_linkedin table {
	width: 100%;
}

#socialink_settings_twitter table th,
#socialink_settings_facebook table th,
#socialink_settings_linkedin table th {
	text-align: left;
}

#socialink_usersettings_twitter_icon {
	background: url("<?php echo $vars["url"]; ?>mod/socialink/_graphics/twitter/logo_withbird_90x17_allblue.png") transparent top left no-repeat;
	width: 90px;
	height: 17px;
}

#socialink_usersettings_facebook_icon {
	background: url("<?php echo $vars["url"]; ?>mod/socialink/_graphics/facebook/logo_90x20.png") transparent top left no-repeat;
	width: 90px;
	height: 20px;
}

#socialink_usersettings_linkedin_icon {
	background: url("<?php echo $vars["url"]; ?>mod/socialink/_graphics/linkedin/logo_84x22.png") transparent top left no-repeat;
	width: 84px;
	height: 22px;

}

#socialink_usersettings_openbibid_icon {
	background: url("<?php echo $vars["url"]; ?>mod/socialink/_graphics/openbibid/logo_85x84.jpg") transparent top left no-repeat;
	width: 85px;
	height: 84px;

}

#socialink_login a{
	margin-left: 5px;
	vertical-align: middle;
}

#socialink_login .disclaimer {
	color: gray;
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
	border: 1px solid #CCCCCC;
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	min-height: 250px;
	-moz-box-shadow: 2px 4px 9px #c8c6d2; /* Firefox/Mozilla */  
	-webkit-box-shadow: 2px 4px 9px #c8c6d2; /*Safari/Chrome */  
	box-shadow: 2px 4px 9px #c8c6d2; /* Opera & hoe het zou moeten */
}

#socialink_link_account_wrapper h2,
#socialink_create_account_wrapper h2 {
	margin: 0;
	padding: 0 0 5px;
}

#socialink_no_linked_account_spacer {
	float: left;
	width: 50px;
	padding-top: 100px;
	font-size: 16px;
	color: #CCCCCC;
	text-align: center;
}

#socialink_create_account_wrapper .disclaimer {
	color: #9E9E9E;
}

#socialink_no_linked_account_container label {
	font-size: 100%;
}

#socialink_no_linked_account_container .description {
	margin-bottom: 5px;
}
