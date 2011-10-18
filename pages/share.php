<?php 

	global $CONFIG;

	if(isloggedin()){
		$url = get_input("url");
		$title = get_input("title");
		$max_length = 140;
		
		if(!empty($url)){
			$url = trigger_plugin_hook("shorten", "url", array("url" => $url), $url);
		}
		
		$message = html_entity_decode($title) . " (" . $url . ")"; 
		
		$network = get_input("network");
		if($network){
			$form_body .= elgg_view("input/hidden", array("internalname" => "network","value" => $network));
			$form_body .= elgg_view("input/plaintext", array("internalname" => "message","value" => $message, "js" => "onKeyDown='socialink_share_update_remaining(this);' onKeyUp='socialink_share_update_remaining(this);'"));
			$form_body .= "<div id='socialink_share_characters_remaining'>" . ($max_length - strlen($message)) . "</div>";
			$form_body .= elgg_view("input/submit", array("value" => elgg_echo("socialink:share")));
			
			$form = elgg_view("input/form", array("internalid" => "socialink_share_form", "action" => $CONFIG->wwwroot . "action/socialink/share" , "body" => $form_body, "js" => "onsubmit='return socialink_share_post();'"));
			
			?>
			<div class="contentWrapper" id="socialink_share">
				<div>
					<img src='<?php echo $CONFIG->wwwroot; ?>mod/socialink/_graphics/<?php echo $network; ?>/login.png'>
					<?php echo elgg_echo("socialink:share:" . $network); ?>
				</div>
				<?php echo $form; ?>
			</div>

			<script type="text/javascript">
				function socialink_share_update_remaining(elem){
					var max_length = <?php echo $max_length; ?>;
	
					var new_val = max_length - $(elem).val().length;
					if(new_val < 0){
						$("#socialink_share_characters_remaining").addClass("negative").html(new_val);
					} else {
						$("#socialink_share_characters_remaining").removeClass("negative").html(new_val);
					}				
				}	
	
				function socialink_share_post(){
					var max_length = <?php echo $max_length; ?>;
					var new_val = max_length - $("#socialink_share_form textarea").val().length;

					if(new_val >= 0){
						var action = $("#socialink_share_form").attr("action");
						$.post(action, $("#socialink_share_form").serialize());
						$.fancybox.close();
					} else {
						alert("<?php echo elgg_echo("socialink:share:too_long"); ?>");
					}
					return false;
				}
			</script>
			<?php 
		}
	}
?>