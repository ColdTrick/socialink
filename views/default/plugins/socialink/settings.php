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
	
	if(($profile_fields = elgg_get_config('profile_fields')) && is_array($profile_fields)){
		foreach($profile_fields as $name => $type){
			$lan_key = "profile:" . $name;
			
			if($lan_key == elgg_echo($lan_key)){
				$translation = $name;
			} else {
				$translation = elgg_echo($lan_key);
			}
			
			$profile_options[$name] = $translation . " (" . $name . ")";
		}
	}
	
	
	if($plugin->enable_twitter == "yes"){
		$twitter_show = "socialink_settings_show";
	}
	if($plugin->enable_facebook == "yes"){
		$facebook_show = "socialink_settings_show";
	}
	if($plugin->enable_linkedin == "yes"){
		$linkedin_show = "socialink_settings_show";
	}
	
?>
<div>
	<table>
		<tr>
			<td><?php echo elgg_echo("socialink:settings:enable:twitter"); ?>&nbsp;</td>
			<td><?php echo elgg_view("input/dropdown", array("name" => "params[enable_twitter]", "value" => $plugin->enable_twitter, "options_values" => $yesno_options)); ?></td>
		</tr>
		<tr>
			<td><?php echo elgg_echo("socialink:settings:enable:facebook"); ?>&nbsp;</td>
			<td><?php echo elgg_view("input/dropdown", array("name" => "params[enable_facebook]", "value" => $plugin->enable_facebook, "options_values" => $yesno_options)); ?></td>
		</tr>
		<tr>
			<td><?php echo elgg_echo("socialink:settings:enable:linkedin"); ?>&nbsp;</td>
			<td><?php echo elgg_view("input/dropdown", array("name" => "params[enable_linkedin]", "value" => $plugin->enable_linkedin, "options_values" => $yesno_options)); ?></td>
		</tr>
	</table>
</div>

<div id="socialink_settings_accordion">

	<!-- Twitter settings -->
	<div id="socialink_settings_twitter" class="elgg-module elgg-module-info <?php echo $twitter_show; ?>">
		<div class="elgg-head"><h3><?php echo elgg_echo("socialink:settings:twitter:header"); ?></h3></div>
		
		<div class="elgg-body">
			<div><?php echo elgg_echo("socialink:settings:twitter:api:consumer_key"); ?></div>
			<?php echo elgg_view("input/text", array("name" => "params[twitter_consumer_key]", "value" => $plugin->twitter_consumer_key)); ?>
			
			<div><?php echo elgg_echo("socialink:settings:twitter:api:consumer_secret"); ?></div>
			<?php echo elgg_view("input/text", array("name" => "params[twitter_consumer_secret]", "value" => $plugin->twitter_consumer_secret)); ?>
			
			<br /><br />
			
			<div><?php echo elgg_echo("socialink:settings:twitter:allow_login"); ?></div>
			<?php echo elgg_view("input/dropdown", array("name" => "params[twitter_allow_login]", "value" => $plugin->twitter_allow_login, "options_values" => $yesno_options)); ?>
			
			<div><?php echo elgg_echo("socialink:settings:twitter:allow_create"); ?></div>
			<?php echo elgg_view("input/dropdown", array("name" => "params[twitter_allow_create]", "value" => $plugin->twitter_allow_create, "options_values" => $yesno_options)); ?>
			
			<?php if(elgg_is_active_plugin("thewire")){ ?>
			
			<div><?php echo elgg_echo("socialink:settings:twitter:allow_in"); ?></div>
			<?php 
				echo elgg_view("input/dropdown", array("name" => "params[twitter_allow_in]", "value" => $plugin->twitter_allow_in, "options_values" => $in_options));
			} else {
				echo elgg_view("input/hidden", array("name" => "params[twitter_allow_in]", "value" => "no"));
			}?>
			
			<br /><br />
			
			<div><?php echo elgg_echo("socialink:settings:twitter:sync_profile_fields"); ?></div>
			<?php 
				if($fields = socialink_get_network_fields("twitter")){
					?>
					<table class="elgg-table">
						<tr>
							<th><?php echo elgg_echo("socialink:settings:twitter:twitter_field"); ?></th>
							<th><?php echo elgg_echo("socialink:settings:profile_field"); ?></th>
						</tr>
						<?php 
							foreach($fields as $settings_name => $network_name){
								$setting = "twitter_profile_" . $settings_name;
								
								echo "<tr>\n";
								echo "<td>" . elgg_echo("socialink:twitter:field:" . $settings_name) . "</td>\n";
								echo "<td>" . elgg_view("input/dropdown", array("name" => "params[" . $setting . "]", "options_values" => $profile_options, "value" => $plugin->$setting)) . "</td>\n";
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
	<div id="socialink_settings_facebook" class="elgg-module elgg-module-info <?php echo $facebook_show; ?>">
		<div class="elgg-head"><h3><?php echo elgg_echo("socialink:settings:facebook:header"); ?></h3></div>
		
		<div class="elgg-body">
			<div><?php echo elgg_echo("socialink:settings:facebook:api:app_id"); ?></div>
			<?php echo elgg_view("input/text", array("name" => "params[facebook_app_id]", "value" => $plugin->facebook_app_id)); ?>
			
			<div><?php echo elgg_echo("socialink:settings:facebook:api:app_secret"); ?></div>
			<?php echo elgg_view("input/text", array("name" => "params[facebook_app_secret]", "value" => $plugin->facebook_app_secret)); ?>
			
			<div><?php echo elgg_echo("socialink:settings:facebook:api:api_key"); ?></div>
			<?php echo elgg_view("input/text", array("name" => "params[facebook_api_key]", "value" => $plugin->facebook_api_key)); ?>
			
			<br /><br />
			
			<div><?php echo elgg_echo("socialink:settings:facebook:allow_login"); ?></div>
			<?php echo elgg_view("input/dropdown", array("name" => "params[facebook_allow_login]", "value" => $plugin->facebook_allow_login, "options_values" => $yesno_options)); ?>
			
			<div><?php echo elgg_echo("socialink:settings:facebook:allow_create"); ?></div>
			<?php echo elgg_view("input/dropdown", array("name" => "params[facebook_allow_create]", "value" => $plugin->facebook_allow_create, "options_values" => $yesno_options)); ?>
			
			<br /><br />
			
			<div><?php echo elgg_echo("socialink:settings:facebook:sync_profile_fields"); ?></div>
			<?php 
				if($fields = socialink_get_network_fields("facebook")){
					?>
					<table class="elgg-table">
						<tr>
							<th><?php echo elgg_echo("socialink:settings:facebook:facebook_field"); ?></th>
							<th><?php echo elgg_echo("socialink:settings:profile_field"); ?></th>
						</tr>
						<?php 
							foreach($fields as $settings_name => $network_name){
								$setting = "facebook_profile_" . $settings_name;
								
								echo "<tr>\n";
								echo "<td>" . elgg_echo("socialink:facebook:field:" . $settings_name) . "</td>\n";
								echo "<td>" . elgg_view("input/dropdown", array("name" => "params[" . $setting . "]", "options_values" => $profile_options, "value" => $plugin->$setting)) . "</td>\n";
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
	<div id="socialink_settings_linkedin" class="elgg-module elgg-module-info <?php echo $linkedin_show; ?>">
		<div class="elgg-head"><h3><?php echo elgg_echo("socialink:settings:linkedin:header"); ?></h3></div>
		
		<div class="elgg-body">
			<div><?php echo elgg_echo("socialink:settings:linkedin:api:consumer_key"); ?></div>
			<?php echo elgg_view("input/text", array("name" => "params[linkedin_consumer_key]", "value" => $plugin->linkedin_consumer_key)); ?>
			
			<div><?php echo elgg_echo("socialink:settings:linkedin:api:consumer_secret"); ?></div>
			<?php echo elgg_view("input/text", array("name" => "params[linkedin_consumer_secret]", "value" => $plugin->linkedin_consumer_secret)); ?>
			
			<br /><br />
			
			<div><?php echo elgg_echo("socialink:settings:linkedin:allow_login"); ?></div>
			<?php echo elgg_view("input/dropdown", array("name" => "params[linkedin_allow_login]", "value" => $plugin->linkedin_allow_login, "options_values" => $yesno_options)); ?>
			
			<div><?php echo elgg_echo("socialink:settings:linkedin:allow_create"); ?></div>
			<?php echo elgg_view("input/dropdown", array("name" => "params[linkedin_allow_create]", "value" => $plugin->linkedin_allow_create, "options_values" => $yesno_options)); ?>
			
			<br /><br />
			
			<div><?php echo elgg_echo("socialink:settings:linkedin:sync_profile_fields"); ?></div>
			<?php 
				if($fields = socialink_get_network_fields("linkedin")){
					?>
					<table class="elgg-table">
						<tr>
							<th><?php echo elgg_echo("socialink:settings:linkedin:linkedin_field"); ?></th>
							<th><?php echo elgg_echo("socialink:settings:profile_field"); ?></th>
						</tr>
						<?php 
							foreach($fields as $settings_name => $network_name){
								$setting = "linkedin_profile_" . $settings_name;
								
								echo "<tr>\n";
								echo "<td>" . elgg_echo("socialink:linkedin:field:" . $settings_name) . "</td>\n";
								echo "<td>" . elgg_view("input/dropdown", array("name" => "params[" . $setting . "]", "options_values" => $profile_options, "value" => $plugin->$setting)) . "</td>\n";
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
	
</div>

<script type="text/javascript">
	$('#socialink_settings_accordion').accordion({
		"header" : "div.elgg-head",
		"autoHeight" : false
	});
</script>