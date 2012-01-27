<?php 

	$plugin = $vars["entity"];
	
	$yesno_options = array(
		"no" => elgg_echo("option:no"),
		"yes" => elgg_echo("option:yes")
	);
	
	$in_options = array(
		"no" => elgg_echo("option:no"),
		"fifteenmin" => elgg_echo("socialink:settings:in_options:fifteenmin"),
		"halfhour" => elgg_echo("socialink:settings:in_options:halfhour")
	);
	
	$profile_options = array(
		"" => elgg_echo("socialink:settings:profile_fields:dont_sync")
	);
	
	if(!empty($vars["config"]->profile) && is_array($vars["config"]->profile)){
		foreach($vars["config"]->profile as $name => $type){
			$lan_key = "profile:" . $name;
			
			if($lan_key == elgg_echo($lan_key)){
				$translation = $name;
			} else {
				$translation = elgg_echo($lan_key);
			}
			
			$profile_options[$name] = $translation . " (" . $name . ")";
		}
	}
?>
<script type="text/javascript">
	function socialink_settings_show_section(section, elem){
		if($(elem).val() == "yes"){
			$('#socialink_settings_' + section).show();
		} else {
			$('#socialink_settings_' + section).hide();
		}
	}
</script>

<div>
	<h3 class="settings"><?php echo elgg_echo("socialink:settings:general:header"); ?></h3>
	
	<table>
		<tr>
			<td><?php echo elgg_echo("socialink:settings:enable:twitter"); ?>&nbsp;</td>
			<td><?php echo elgg_view("input/pulldown", array("internalname" => "params[enable_twitter]", "value" => $plugin->enable_twitter, "options_values" => $yesno_options, "js" => "onchange='socialink_settings_show_section(\"twitter\", this);'")); ?></td>
		</tr>
		<tr>
			<td><?php echo elgg_echo("socialink:settings:enable:facebook"); ?>&nbsp;</td>
			<td><?php echo elgg_view("input/pulldown", array("internalname" => "params[enable_facebook]", "value" => $plugin->enable_facebook, "options_values" => $yesno_options, "js" => "onchange='socialink_settings_show_section(\"facebook\", this);'")); ?></td>
		</tr>
		<tr>
			<td><?php echo elgg_echo("socialink:settings:enable:linkedin"); ?>&nbsp;</td>
			<td><?php echo elgg_view("input/pulldown", array("internalname" => "params[enable_linkedin]", "value" => $plugin->enable_linkedin, "options_values" => $yesno_options, "js" => "onchange='socialink_settings_show_section(\"linkedin\", this);'")); ?></td>
		</tr>
		<tr>
			<td><?php echo elgg_echo("socialink:settings:enable:openbibid"); ?>&nbsp;</td>
			<td><?php echo elgg_view("input/pulldown", array("internalname" => "params[enable_openbibid]", "value" => $plugin->enable_openbibid, "options_values" => $yesno_options, "js" => "onchange='socialink_settings_show_section(\"openbibid\", this);'")); ?></td>
		</tr>
	</table>
	
	<div><?php echo elgg_echo("socialink:settings:proxy:host"); ?></div>
	<?php echo elgg_view("input/text", array("internalname" => "params[proxy_host]", "value" => $plugin->proxy_host)); ?>
	
	<div><?php echo elgg_echo("socialink:settings:proxy:port"); ?></div>
	<?php echo elgg_view("input/text", array("internalname" => "params[proxy_port]", "value" => $plugin->proxy_port)); ?>
</div>

<div id="socialink_settings_accordion">

	<!-- Twitter settings -->
	<div id="socialink_settings_twitter" <?php if($plugin->enable_twitter == "yes") echo "class='socialink_settings_show'"; ?>>
		<h3 class="settings"><?php echo elgg_echo("socialink:settings:twitter:header"); ?></h3>
		
		<div>
			<div><?php echo elgg_echo("socialink:settings:twitter:api:consumer_key"); ?></div>
			<?php echo elgg_view("input/text", array("internalname" => "params[twitter_consumer_key]", "value" => $plugin->twitter_consumer_key)); ?>
			
			<div><?php echo elgg_echo("socialink:settings:twitter:api:consumer_secret"); ?></div>
			<?php echo elgg_view("input/text", array("internalname" => "params[twitter_consumer_secret]", "value" => $plugin->twitter_consumer_secret)); ?>
			
			<br /><br />
			
			<div><?php echo elgg_echo("socialink:settings:twitter:allow_login"); ?></div>
			<?php echo elgg_view("input/pulldown", array("internalname" => "params[twitter_allow_login]", "value" => $plugin->twitter_allow_login, "options_values" => $yesno_options)); ?>
			
			<div><?php echo elgg_echo("socialink:settings:twitter:allow_create"); ?></div>
			<?php echo elgg_view("input/pulldown", array("internalname" => "params[twitter_allow_create]", "value" => $plugin->twitter_allow_create, "options_values" => $yesno_options)); ?>
			
			<?php if(is_plugin_enabled("thewire")){ ?>
			
			<div><?php echo elgg_echo("socialink:settings:twitter:allow_in"); ?></div>
			<?php 
				echo elgg_view("input/pulldown", array("internalname" => "params[twitter_allow_in]", "value" => $plugin->twitter_allow_in, "options_values" => $in_options));
			} else {
				echo elgg_view("input/hidden", array("internalname" => "params[twitter_allow_in]", "value" => "no"));
			}?>
			
			<br /><br />
			
			<div><?php echo elgg_echo("socialink:settings:twitter:sync_profile_fields"); ?></div>
			<?php 
				if($fields = socialink_get_network_fields("twitter")){
					?>
					<table>
						<tr>
							<th><?php echo elgg_echo("socialink:settings:twitter:twitter_field"); ?></th>
							<th><?php echo elgg_echo("socialink:settings:profile_field"); ?></th>
						</tr>
						<?php 
							foreach($fields as $settings_name => $network_name){
								$setting = "twitter_profile_" . $settings_name;
								
								echo "<tr>\n";
								echo "<td>" . elgg_echo("socialink:twitter:field:" . $settings_name) . "</td>\n";
								echo "<td>" . elgg_view("input/pulldown", array("internalname" => "params[" . $setting . "]", "options_values" => $profile_options, "value" => $plugin->$setting)) . "</td>\n";
								echo "</tr>\n";
							}
						?>
					</table>
					<?php 
				}
			?>
		</div>
	</div>
	<!-- End Twitter settings -->

	<!-- Facebook settings -->
	<div id="socialink_settings_facebook" <?php if($plugin->enable_facebook == "yes") echo "class='socialink_settings_show'"; ?>>
		<h3 class="settings"><?php echo elgg_echo("socialink:settings:facebook:header"); ?></h3>
		
		<div>
			<div><?php echo elgg_echo("socialink:settings:facebook:api:app_id"); ?></div>
			<?php echo elgg_view("input/text", array("internalname" => "params[facebook_app_id]", "value" => $plugin->facebook_app_id)); ?>
			
			<div><?php echo elgg_echo("socialink:settings:facebook:api:app_secret"); ?></div>
			<?php echo elgg_view("input/text", array("internalname" => "params[facebook_app_secret]", "value" => $plugin->facebook_app_secret)); ?>
			
			<div><?php echo elgg_echo("socialink:settings:facebook:api:api_key"); ?></div>
			<?php echo elgg_view("input/text", array("internalname" => "params[facebook_api_key]", "value" => $plugin->facebook_api_key)); ?>
			
			<br /><br />
			
			<div><?php echo elgg_echo("socialink:settings:facebook:allow_login"); ?></div>
			<?php echo elgg_view("input/pulldown", array("internalname" => "params[facebook_allow_login]", "value" => $plugin->facebook_allow_login, "options_values" => $yesno_options)); ?>
			
			<div><?php echo elgg_echo("socialink:settings:facebook:allow_create"); ?></div>
			<?php echo elgg_view("input/pulldown", array("internalname" => "params[facebook_allow_create]", "value" => $plugin->facebook_allow_create, "options_values" => $yesno_options)); ?>
			
			<br /><br />
			
			<div><?php echo elgg_echo("socialink:settings:facebook:sync_profile_fields"); ?></div>
			<?php 
				if($fields = socialink_get_network_fields("facebook")){
					?>
					<table>
						<tr>
							<th><?php echo elgg_echo("socialink:settings:facebook:facebook_field"); ?></th>
							<th><?php echo elgg_echo("socialink:settings:profile_field"); ?></th>
						</tr>
						<?php 
							foreach($fields as $settings_name => $network_name){
								$setting = "facebook_profile_" . $settings_name;
								
								echo "<tr>\n";
								echo "<td>" . elgg_echo("socialink:facebook:field:" . $settings_name) . "</td>\n";
								echo "<td>" . elgg_view("input/pulldown", array("internalname" => "params[" . $setting . "]", "options_values" => $profile_options, "value" => $plugin->$setting)) . "</td>\n";
								echo "</tr>\n";
							}
						?>
					</table>
					<?php 
				}
			?>
		</div>
	</div>
	<!-- End Facebook settings -->

	<!-- LinkedIn settings -->
	<div id="socialink_settings_linkedin" <?php if($plugin->enable_linkedin == "yes") echo "class='socialink_settings_show'"; ?>>
		<h3 class="settings"><?php echo elgg_echo("socialink:settings:linkedin:header"); ?></h3>
		
		<div>
			<div><?php echo elgg_echo("socialink:settings:linkedin:api:consumer_key"); ?></div>
			<?php echo elgg_view("input/text", array("internalname" => "params[linkedin_consumer_key]", "value" => $plugin->linkedin_consumer_key)); ?>
			
			<div><?php echo elgg_echo("socialink:settings:linkedin:api:consumer_secret"); ?></div>
			<?php echo elgg_view("input/text", array("internalname" => "params[linkedin_consumer_secret]", "value" => $plugin->linkedin_consumer_secret)); ?>
			
			<br /><br />
			
			<div><?php echo elgg_echo("socialink:settings:linkedin:allow_login"); ?></div>
			<?php echo elgg_view("input/pulldown", array("internalname" => "params[linkedin_allow_login]", "value" => $plugin->linkedin_allow_login, "options_values" => $yesno_options)); ?>
			
			<div><?php echo elgg_echo("socialink:settings:linkedin:allow_create"); ?></div>
			<?php echo elgg_view("input/pulldown", array("internalname" => "params[linkedin_allow_create]", "value" => $plugin->linkedin_allow_create, "options_values" => $yesno_options)); ?>
			
			<br /><br />
			
			<div><?php echo elgg_echo("socialink:settings:linkedin:sync_profile_fields"); ?></div>
			<?php 
				if($fields = socialink_get_network_fields("linkedin")){
					?>
					<table>
						<tr>
							<th><?php echo elgg_echo("socialink:settings:linkedin:linkedin_field"); ?></th>
							<th><?php echo elgg_echo("socialink:settings:profile_field"); ?></th>
						</tr>
						<?php 
							foreach($fields as $settings_name => $network_name){
								$setting = "linkedin_profile_" . $settings_name;
								
								echo "<tr>\n";
								echo "<td>" . elgg_echo("socialink:linkedin:field:" . $settings_name) . "</td>\n";
								echo "<td>" . elgg_view("input/pulldown", array("internalname" => "params[" . $setting . "]", "options_values" => $profile_options, "value" => $plugin->$setting)) . "</td>\n";
								echo "</tr>\n";
							}
						?>
					</table>
					<?php 
				}
			?>
		</div>
	</div>
	<!-- End LinkedIn settings -->
	
	<!-- OpenBibId settings -->
	<div id="socialink_settings_openbibid" <?php if($plugin->enable_openbibid == "yes") echo "class='socialink_settings_show'"; ?>>
		<h3 class="settings"><?php echo elgg_echo("socialink:settings:openbibid:header"); ?></h3>
		
		<div>
			<div><?php echo elgg_echo("socialink:settings:openbibid:api:consumer_key"); ?></div>
			<?php echo elgg_view("input/text", array("internalname" => "params[openbibid_consumer_key]", "value" => $plugin->openbibid_consumer_key)); ?>
			
			<div><?php echo elgg_echo("socialink:settings:openbibid:api:consumer_secret"); ?></div>
			<?php echo elgg_view("input/text", array("internalname" => "params[openbibid_consumer_secret]", "value" => $plugin->openbibid_consumer_secret)); ?>
			
			<br /><br />
			
			<div><?php echo elgg_echo("socialink:settings:openbibid:allow_login"); ?></div>
			<?php echo elgg_view("input/pulldown", array("internalname" => "params[openbibid_allow_login]", "value" => $plugin->openbibid_allow_login, "options_values" => $yesno_options)); ?>
			
			<?php /* ?>
			<div><?php echo elgg_echo("socialink:settings:openbibid:allow_create"); ?></div>
			<?php echo elgg_view("input/pulldown", array("internalname" => "params[openbibid_allow_create]", "value" => $plugin->openbibid_allow_create, "options_values" => $yesno_options)); ?>
			
			<br /><br />
			
			<div><?php echo elgg_echo("socialink:settings:openbibid:sync_profile_fields"); ?></div>
			<?php 
				if($fields = socialink_get_network_fields("openbibid")){
					?>
					<table>
						<tr>
							<th><?php echo elgg_echo("socialink:settings:openbibid:openbibid_field"); ?></th>
							<th><?php echo elgg_echo("socialink:settings:profile_field"); ?></th>
						</tr>
						<?php 
							foreach($fields as $settings_name => $network_name){
								$setting = "openbibid_profile_" . $settings_name;
								
								echo "<tr>\n";
								echo "<td>" . elgg_echo("socialink:openbibid:field:" . $settings_name) . "</td>\n";
								echo "<td>" . elgg_view("input/pulldown", array("internalname" => "params[" . $setting . "]", "options_values" => $profile_options, "value" => $plugin->$setting)) . "</td>\n";
								echo "</tr>\n";
							}
						?>
					</table>
					<?php 
				}
			?>
			<?php */ ?>
		</div>
	</div>
	<!-- End OpenBibId settings -->
	
</div>