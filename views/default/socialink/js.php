<?php 

?>

function socialink_toggle_network_configure(elem, network){
	if($(elem).val() == "yes"){
		$('#socialink_' + network + '_sync_configure').addClass('socialink_network_sync_allow');
	} else {
		$('#socialink_' + network + '_sync_configure').removeClass('socialink_network_sync_allow');
		$('#socialink_' + network + '_sync_fields').hide();
	}
}