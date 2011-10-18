<?php 

	$network = $vars["network"];
	$allow_create = $vars["allow_create"];
	
	$network_string = elgg_echo("socialink:network:" . $network);
	
	$link_form = elgg_view("socialink/forms/link_account", array("network" => $network));

	if($allow_create == "yes") {
		$create_form = elgg_view("socialink/forms/create_account", array("network" => $network));
	}

?>
<div class="contentWrapper">
	<div><?php echo sprintf(elgg_echo("socialink:create_account:description"), $network_string); ?></div>
	<br />
	
	<div id="socialink_no_linked_account_container">
		<div id="socialink_link_account_wrapper"><?php echo $link_form; ?></div>
		
		<?php if(!empty($create_form)){ ?>
		<div id="socialink_no_linked_account_spacer"><?php echo strtoupper(elgg_echo("socialink:or")); ?></div>
		<div id="socialink_create_account_wrapper"><?php echo $create_form; ?></div>
		<?php } ?>
		
		<div class="clearfloat"></div>
	</div>
</div>